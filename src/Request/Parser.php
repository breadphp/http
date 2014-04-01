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
namespace Bread\Networking\HTTP\Request;

use Bread\Event;
use Bread\Networking\HTTP\Exception;
use Bread\Networking\HTTP\Server;
use Bread\Networking\HTTP\Client;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;

class Parser extends Event\Emitter
{

    const EXPECTING_EMPTY_LINE = 0;

    const EXPECTING_REQUEST_LINE = 1;

    const EXPECTING_HEADER_LINE = 2;

    const EXPECTING_BODY = 4;

    /**
     * RFC 2616 Section 5.1
     *
     * Request-Line = Method SP Request-URI SP HTTP-Version CRLF
     */
    const REQUEST_LINE_PATTERN = '/^(?<method>[A-Z]+) (?<uri>\S+) (?<version>\S+)$/';

    /**
     * RFC 2616 Section 4.2
     *
     * message-header = field-name ":" [ field-value ]
     */
    const HEADER_LINE_PATTERN = '/^(?<name>\S+):\s?(?<value>[^\r\n]*)$/i';

    const EMPTY_LINE_PATTERN = '/^$/';

    private $expecting;

    private $buffer = "";

    private $request;

    private $maxSize = 4096;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->expecting = static::EXPECTING_REQUEST_LINE;
        $this->request = null;
        $this->buffer = "";
        return $this;
    }

    public function parse($data, $connection)
    {
        if ($connection) {
            try {
                $this->tryToParse($data, $connection);
            } catch (Exception $exception) {
                $this->reset();
                $code = $exception->getCode();
                $connection->write(sprintf("HTTP/1.1 %s %s\r\n", $code, Response::$statusCodes[$code]));
                $connection->write("\r\n");
                $connection->write(sprintf("%s\r\n", $exception->getMessage()));
            }
        }
    }

    protected function tryToParse($data, $connection)
    {
        if (strlen($data) > $this->maxSize) {
            throw new Client\Exceptions\RequestEntityTooLarge($this->maxSize);
        }
        $this->buffer .= $data;
        if (false !== ($pos = strpos($this->buffer, "\r\n\r\n"))) {
            $lines = explode("\r\n", substr($this->buffer, 0, $pos + 2));
            $this->buffer = substr($this->buffer, $pos + 4);
        } else {
            $lines = explode("\r\n", $this->buffer);
            $this->buffer = array_pop($lines);
        }
        foreach ($lines as $line) {
            switch ($this->expecting) {
                case static::EXPECTING_REQUEST_LINE:
                    if (preg_match(static::REQUEST_LINE_PATTERN, $line, $matches)) {
                        $this->request = new Request($connection, $matches['method'], $matches['uri'], $matches['version']);
                        $this->request->on('end', function () {
                            $this->expecting = static::EXPECTING_REQUEST_LINE;
                        })->on('close', function () {
                            $this->removeAllListeners();
                        });
                        switch ($this->request->protocol) {
                            case 'HTTP/1.0':
                            case 'HTTP/1.1':
                                $this->expecting = static::EXPECTING_HEADER_LINE;
                                break;
                            default:
                                return $this->emit('headers', array(
                                    $this->request,
                                    $this->buffer
                                ));
                        }
                    } elseif (!preg_match(static::EMPTY_LINE_PATTERN, $line)) {
                        throw new Client\Exceptions\BadRequest('Expecting RFC2616 Request-Line');
                    }
                    break;
                case static::EXPECTING_HEADER_LINE:
                    if (!$this->request) {
                        throw new Server\Exceptions\InternalServerError();
                    }
                    if (preg_match(static::HEADER_LINE_PATTERN, $line, $matches)) {
                        $this->request->header($matches['name'], trim($matches['value']));
                    } elseif (preg_match(static::EMPTY_LINE_PATTERN, $line)) {
                        switch ($this->request->protocol) {
                            case 'HTTP/1.1':
                                if (!isset($this->request->headers['Host'])) {
                                    throw new Client\Exceptions\BadRequest('Host header required for HTTP/1.1');
                                }
                        }
                        return $this->emit('headers', array(
                            $this->request,
                            $this->buffer
                        ));
                    }
                    break;
                default:
                    throw new Client\Exceptions\BadRequest();
            }
        }
    }
}
