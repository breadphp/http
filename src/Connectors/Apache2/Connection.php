<?php
/**
 * Bread PHP Framework (http://github.com/saiv/Bread)
 * Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 *
 * Licensed under a Creative Commons Attribution 3.0 Unported License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright  Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 * @link       http://github.com/saiv/Bread Bread PHP Framework
 * @package    Bread
 * @since      Bread PHP Framework
 * @license    http://creativecommons.org/licenses/by/3.0/
 */
namespace Bread\Networking\HTTP\Connectors\Apache2;

use Bread\Event;
use Bread\Streaming;
use Bread\Networking;

// TODO Extend Streaming\Composite or Streaming\Through
class Connection extends Streaming\Stream implements Networking\Interfaces\Connection
{
    use Streaming\Traits\Pipe;

    public $input;

    public function __construct(Event\Interfaces\Loop $loop)
    {
        $this->input = fopen('php://input', 'r');
        $this->stream = fopen('php://output', 'w');
        parent::__construct($this->stream, $loop);
        $this->loop->addReadStream($this->input, array(
            $this,
            'handleData'
        ));
    }

    public function pause()
    {
        $this->loop->removeReadStream($this->input);
    }

    public function resume()
    {
        $this->loop->addReadStream($this->input, array(
            $this,
            'handleData'
        ));
    }

    public function handleData($stream)
    {
        $data = fread($stream, $this->bufferSize);
        $this->emit('data', array(
            $data,
            $this
        ));
        if (!is_resource($stream) || feof($stream)) {
            return false;
        }
    }

    public function handleClose()
    {
        if (is_resource($this->input)) {
            fclose($this->input);
        }
        parent::handleClose();
    }

    public function getRemoteAddress()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    public function getRemotePort()
    {
        return $_SERVER['REMOTE_PORT'];
    }

    public function isSecure()
    {
        if (isset($_SERVER['HTTPS'])) {
            return 'on' === strtolower($_SERVER['HTTPS']);
        }
        // TODO Trsut proxy?
        return false;
    }

    public function isClientIdentified()
    {
        if (!isset($_SERVER['SSL_CLIENT_M_SERIAL']) || !isset($_SERVER['SSL_CLIENT_V_END']) || !isset($_SERVER['SSL_CLIENT_VERIFY']) || $_SERVER['SSL_CLIENT_VERIFY'] !== 'SUCCESS' || !isset($_SERVER['SSL_CLIENT_I_DN'])) {
            return false;
        }
        if ($_SERVER['SSL_CLIENT_V_REMAIN'] <= 0) {
            return false;
        }
        return true;
    }

    public function getServerIdentity()
    {
        if (!isset($_SERVER['SSL_SERVER_CERT']) || !isset($_SERVER['SSL_SERVER_I_DN']) || !isset($_SERVER['SSL_SERVER_M_SERIAL']) || !isset($_SERVER['SSL_SERVER_M_VERSION']) || !isset($_SERVER['SSL_SERVER_S_DN'])) {
            return false;
        }
        return array(
            'pemcrt' => $_SERVER['SSL_SERVER_CERT'],
            'issuer' => $_SERVER['SSL_SERVER_I_DN'],
            'serial' => $_SERVER['SSL_SERVER_M_SERIAL'],
            'version' => $_SERVER['SSL_SERVER_M_VERSION'],
            'subject' => $_SERVER['SSL_SERVER_S_DN']
        );
    }

    public function getClientIdentity()
    {
        if (!$this->isClientIdentified()) {
            return false;
        }
        if (!isset($_SERVER['SSL_CLIENT_I_DN']) || !isset($_SERVER['SSL_CLIENT_M_SERIAL']) || !isset($_SERVER['SSL_CLIENT_M_VERSION']) || !isset($_SERVER['SSL_CLIENT_S_DN'])) {
            return false;
        }
        return array(
            'issuer' => $_SERVER['SSL_CLIENT_I_DN'],
            'serial' => $_SERVER['SSL_CLIENT_M_SERIAL'],
            'version' => $_SERVER['SSL_CLIENT_M_VERSION'],
            'subject' => $_SERVER['SSL_CLIENT_S_DN'],
            'subjectCN' => $_SERVER['SSL_CLIENT_S_DN_CN'],
            'subjectEmail' => $_SERVER['SSL_CLIENT_S_DN_Email']
        );
    }
}
