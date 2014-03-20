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
namespace Bread\Networking\HTTP\Response;

use Bread\Networking\HTTP\Response;
use Bread\Event;

class Parser extends Event\Emitter
{

    const EXPECTING_EMPTY_LINE = 0;

    const EXPECTING_STATUS_LINE = 1;

    const EXPECTING_HEADER_LINE = 2;

    const EXPECTING_BODY = 4;

    /**
     * RFC 2616 Section 5.1
     *
     * Request-Line = Method SP Request-URI SP HTTP-Version CRLF
     */
    const STATUS_LINE_PATTERN = '/^(?<version>\S+) (?<status>[0-9]{3}) (?<reason>.+)$/';

    /**
     * RFC 2616 Section 4.2
     *
     * message-header = field-name ":" [ field-value ]
     */
    const HEADER_LINE_PATTERN = '/^(?<name>\S+):\s?(?<value>.*)$/i';

    const EMPTY_LINE_PATTERN = '/^$/';

    private $expecting;

    private $response;

    private $maxSize = 4096;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->response = null;
        $this->expecting = static::EXPECTING_STATUS_LINE;
        return $this;
    }

    public function parse($data, $connection)
    {
        list ($lines, $data) = explode("\r\n\r\n", $data, 2) + array(
            array(),
            null
        );
        foreach (explode("\r\n", $lines, -1) as $line) {
            switch ($this->expecting) {
                case static::EXPECTING_STATUS_LINE:
                    if (preg_match(static::STATUS_LINE_PATTERN, $line, $matches)) {
                        $this->response = new Response($connection, $matches['status'], null, array(), $matches['version']);
                        $this->response->on('end', function () {
                            $this->expecting = static::EXPECTING_STATUS_LINE;
                        })->on('close', function () {
                            $this->removeAllListeners();
                        });
                        $this->expecting = static::EXPECTING_HEADER_LINE;
                    }
                    break;
                case static::EXPECTING_HEADER_LINE:
                    if (preg_match(static::HEADER_LINE_PATTERN, $line, $matches)) {
                        $this->response->header($matches['name'], trim($matches['value']));
                    } elseif (preg_match(static::EMPTY_LINE_PATTERN, $line)) {
                        $this->emit('headers', array(
                            $this->response,
                            $data
                        ));
                    }
            }
        }
        if (!is_null($data)) {
            $this->emit('headers', array(
                $this->response,
                $data
            ));
        }
    }
}
