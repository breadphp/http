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
namespace Bread\Networking\HTTP;

use Bread\Networking\Interfaces\Connection;
use Bread\Networking;
use Bread\Streaming;
use DateTime;
use Bread\Networking\HTTP\Response\Cookie;

class Response extends Message
{

    public $statusLine;

    public $status;

    public $reason;

    public $request;

    public $cookies = array();

    public $messages = array();

    public static $statusCodes = array(
        100 => "Continue",
        101 => "Switching Protocols",
        102 => "Processing",
        122 => "Request-URI too long",
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        207 => "Multi-Status",
        226 => "IM Used",
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        306 => "Switch Proxy",
        307 => "Temporary Redirect",
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Request Controller Too Large",
        414 => "Request-URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed",
        418 => "I'm a teapot",
        422 => "Unprocessable Controller",
        423 => "Locked",
        424 => "Failed Dependency",
        425 => "Unordered Collection",
        426 => "Upgrade Required",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported",
        506 => "Variant Also Negotiates",
        507 => "Insufficient Storage",
        509 => "Bandwidth Limit Exceeded",
        510 => "Not Extended"
    );

    public function __construct(Request $request, $status = 200, $body = null, $headers = array())
    {
        $this->request = $request;
        $this->status($status);
        if (isset($this->request->headers['Connection'])) {
            $headers['Connection'] = $this->request->headers['Connection'];
        }
        if (isset($this->request->cookies['Messages'])) {
            $this->messages = json_decode($this->request->cookies['Messages'], true);
            $this->unsetCookie('Messages');
        }
        $this->onBefore('headers', function () {
            $this->headers['Set-Cookie'] = $this->cookies;
        });
        parent::__construct($this->request->connection, $this->request->protocol, $this->statusLine, $headers, $body);
        if ($range = $this->request->headers['Range']) {
            $this->onceBefore('headers', 
                function () use($range)
                {
                    $this->connection->loop->removeReadStream($this->body);
                    $this->range($range);
                    $this->connection->loop->addReadStream($this->body, array(
                        $this,
                        'write'
                    ));
                });
        }
    }

    public function __toString()
    {
        $this->headers['Set-Cookie'] = $this->cookies;
        return parent::__toString();
    }

    public function end($data = null)
    {
        parent::end($data);
        if ('close' === $this->request->headers['Connection']) {
            $this->close();
        }
    }

    public function status($status)
    {
        $this->status = $status;
        $this->reason = self::$statusCodes[$this->status];
        $this->startLine = $this->statusLine = implode(' ', array(
            $this->request->protocol,
            $this->status,
            $this->reason
        ));
        if (in_array($status, array(
            301,
            302,
            303,
            304,
            401
        ))) {
            $this->setCookie(new Cookie('Messages', json_encode($this->messages)));
            $this->onceAfter('headers', array(
                $this,
                'flush'
            ));
        }
    }

    public function setCookie($cookie, $value = null, $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        if (is_string($cookie)) {
            $cookie = new Cookie($cookie, $value, $expire, $path, $domain, $secure, $httpOnly);
        }
        $this->cookies[] = $cookie;
        return $cookie;
    }

    public function unsetCookie($name, $path = '/', $domain = null)
    {
        $this->setCookie(new Cookie($name, null, 1, $path, $domain));
    }

    public function cache($since = 'now', $time = '+1 day')
    {
        if ($since instanceof DateTime) {
            $since = $since->format('U');
        } elseif (!is_integer($since)) {
            $since = strtotime($since);
        }
        if ($time instanceof DateTime) {
            $time = $time->format('U');
        } elseif (!is_integer($time)) {
            $time = strtotime($time);
        }
        $since = $since ? $since : time();
        $this->onceBefore('headers', function ($response) use ($since, $time) {
            $etag = sprintf('"%s"', $this->etag());
            $this->headers->add(array(
                'Last-Modified' => gmdate(self::DATETIME_FORMAT, $since),
                'Expires' => gmdate(self::DATETIME_FORMAT, $time),
                'Cache-Control' => 'public, max-age=' . ($time - time()),
                'Pragma' => 'cache',
                'ETag' => $etag
            ));
            if ($ifNoneMatch = $this->request->headers['If-None-Match']) {
                if ($ifNoneMatch == $etag) {
                    $this->status(304);
                }
            } elseif ($this->request->headers['If-Modified-Since'] == $this->headers['Last-Modified']) {
                $this->status(304);
            }
        });
    }

    public function disableCache()
    {
        $this->headers->add(array(
            'Expires' => gmdate(self::DATETIME_FORMAT, 0),
            'Last-Modified' => gmdate(self::DATETIME_FORMAT),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'no-cache'
        ));
    }

    public function inline($filename)
    {
        $this->headers['Content-Disposition'] = sprintf('inline; filename="%s"', $filename);
    }

    public function download($filename)
    {
        $this->headers['Content-Disposition'] = sprintf('attachment; filename="%s"', $filename);
    }

    public function message($message, $severity = LOG_INFO)
    {
        $args = func_get_args();
        if (!isset($this->messages[$severity])) {
            $this->messages[$severity] = array();
        }
        $this->messages[$severity][] = $message;
    }

    protected function etag()
    {
        $position = ftell($this->body);
        $hash = hash_init('md5');
        hash_update_stream($hash, $this->body, $this->length);
        $etag = hash_final($hash);
        fseek($this->body, $position);
        return $etag;
    }

    protected function setRange($range, $filesize, &$first, &$last)
    {
        list ($first, $last) = explode('-', $range) + array(
            '',
            ''
        );
        if ($first == '') {
            // Suffix byte range: gets last n bytes
            $suffix = $last;
            $last = $filesize - 1;
            $first = $filesize - $suffix;
            if ($first < 0)
                $first = 0;
        } elseif ($last == '' || $last > $filesize - 1) {
            $last = $filesize - 1;
        }
        if ($first > $last) {
            // Unsatisfiable range
            return false;
        }
        return true;
    }

    protected function range($range)
    {
        $ranges = array();
        list ($accept, $range) = explode('=', $range);
        $ranges = explode(',', $range);
        if (count($ranges)) {
            $total = $this->length;
            $boundary = uniqid();
            $this->status(206);
            $this->headers['Accept-Ranges'] = $accept;
            if (count($ranges) > 1) {
                $length = 0;
                $body = fopen('php://temp', 'r+');
                $boundaryLine = "\r\n--$boundary\r\n";
                $contentTypeFormat = "Content-Type: %s\r\n";
                $contentRangeFormat = "Content-Range: %s %s-%s/%s\r\n\r\n";
                foreach ($ranges as $range) {
                    if (!$this->setRange($range, $total, $first, $last)) {
                        $this->status(416);
                        $this->headers['Content-Range'] = "*/$total";
                        return false;
                    }
                    $length += fwrite($body, $boundaryLine);
                    $length += fwrite($body, sprintf($contentTypeFormat, $this->type));
                    $length += fwrite($body, sprintf($contentRangeFormat, $accept, $first, $last, $total));
                    fseek($this->body, $first);
                    $length += fwrite($body, fread($this->body, $last - $first + 1));
                }
                $length += fwrite($body, "\r\n--$boundary--\r\n");
                $this->length = $length;
                $this->type = "multipart/byteranges; boundary=$boundary";
                rewind($body);
                $this->body($body);
            } else {
                if (!$this->setRange($range, $total, $first, $last)) {
                    $this->status(416);
                    $this->headers['Content-Range'] = "*/$total";
                    return false;
                }
                $this->headers['Content-Range'] = "$accept $first-$last/$total";
                $this->length = $last - $first + 1;
                fseek($this->body, $first);
            }
        }
    }
}
