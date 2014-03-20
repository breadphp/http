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
namespace Bread\Networking\HTTP\Connectors;

use Bread\Event;
use Bread\Networking\HTTP;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Streaming\Bucket;
use Bread\Networking\HTTP\Parsers\MultipartFormData;

class Apache2 extends Event\Emitter implements HTTP\Interfaces\Server
{


    public $loop;

    private $context;

    private static $fileKeys = array(
        'error',
        'name',
        'size',
        'tmp_name',
        'type'
    );

    public function __construct(Event\Interfaces\Loop $loop, array $context = array())
    {
        $this->loop = $loop;
        $this->context = $context;
    }

    public function run()
    {
        $headers = apache_request_headers();
        $connection = new Apache2\Connection($this->loop);
        $request = new Request($connection, $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['SERVER_PROTOCOL'], $headers);
        if (isset($headers['Content-Type'])) {
            $contentType = $headers['Content-Type'];
            if (preg_match('|^multipart/form-data-apache|', $contentType)) {
                $request->headers['Content-Type'] = preg_replace('/-apache/', '', $contentType);
            }
            if (preg_match('|^multipart/form-data|', $request->headers['Content-Type'])) {
                $this->onAfter('request', function ($request, $response) {
                    $parts = array();
                    switch ($request->method) {
                        case 'xPOST':
                            $parts = array();
                            foreach ($_POST as $name => $value) {
                                if (empty($value)) {
                                    continue;
                                }
                                $entry = array();
                                $entry['size'] = strlen(trim($value));
                                $entry['body'] = trim($value);
                                $entry['headers']['Content-Type'] = 'text/plain';
                                $parts[$name][] = $entry;
                            }
                            $files = array_map(array(
                                $this,
                                'extractFile'
                            ), $_FILES);
                            $parts = array_merge($parts, $files);
                            $request->emit('parts', array(
                                $parts
                            ));
                            break;
                        default:
                            new MultipartFormData($request);
                    }
                });
            }
        }
        $connection->on('end', function () use ($request) {
            $request->emit('end');
        });
        $connection->on('data', function ($data) use ($request, $connection) {
            $request->emit('data', array(
                $data
            ));
            if (feof($connection->input)) {
                $request->end();
            }
        });
        $request->on('pause', function () use ($connection) {
            $connection->emit('pause');
        });
        $request->on('resume', function () use ($connection) {
            $connection->emit('resume');
        });
        $response = new Response($request);
        $response->once('headers', function ($response) {
            foreach (apache_response_headers() as $name => $value) {
                header_remove($name);
            }
            header($response->statusLine);
            foreach ($response->headers as $name => $values) {
                $name = implode('-', array_map('ucfirst', explode('-', $name)));
                foreach ($values as $value) {
                    header("{$name}: {$value}", false);
                }
            }
            header("X-Powered-By: " . __CLASS__);
        });
        $this->emit('request', array(
            $request,
            $response
        ));
        $this->loop->run();
    }

    public function listen($port, $host = '127.0.0.1')
    {
        return $this;
    }

    public function getPort()
    {
        return (int) $_SERVER['SERVER_PORT'];
    }

    public function shutdown()
    {
        $this->loop->stop();
    }

}
