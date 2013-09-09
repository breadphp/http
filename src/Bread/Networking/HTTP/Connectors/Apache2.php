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

class Apache2 extends Event\Emitter implements HTTP\Interfaces\Server
{

    public $loop;

    private static $fileKeys = array(
        'error',
        'name',
        'size',
        'tmp_name',
        'type'
    );

    public function __construct(Event\Interfaces\Loop $loop)
    {
        $this->loop = $loop;
    }

    public function run()
    {
        $headers = apache_request_headers();
        if (isset($headers['Content-Type'])) {
            $contentType = $headers['Content-Type'];
            if (preg_match('|^multipart/form-data|', $contentType)) {
                $this->onAfter('request', function ($request, $response) {
                    $parts = array();
                    $collapse = function (&$res, $source, $pk = null) use (&$collapse) {
                        foreach ($source as $k => $v) {
                            $tk = $pk ? "{$pk}[{$k}]" : $k;
                            if (!is_array($v)) {
                                $res[$tk] = $v;
                            } else {
                                $collapse($res, $v, $tk);
                            }
                        }
                    };
                    $files = array_map(array(
                        $this,
                        'extractFile'
                    ), $_FILES);
                    $data = array_replace_recursive($_POST, $files);
                    $request->emit('parts', array(
                        $data
                    ));
                    $collapse($parts, $data);
                    array_walk($parts, function ($part, $name) use ($request) {
                        $request->emit('part', array(
                            $part,
                            $name
                        ));
                    });
                });
            }
        }
        $connection = new Apache2\Connection($this->loop);
        $request = new Request($connection, $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['SERVER_PROTOCOL'], $headers);
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

    public function listen($port = 80, $host = '127.0.0.1')
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

    protected function normalizeFile($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        
        $keys = array_keys($data);
        sort($keys);
        
        if (self::$fileKeys != $keys || !isset($data['name']) || !is_array($data['name'])) {
            return $data;
        }
        
        $files = $data;
        foreach (self::$fileKeys as $k) {
            unset($files[$k]);
        }
        
        foreach (array_keys($data['name']) as $key) {
            $files[$key] = $this->normalizeFile(
                array(
                    'error' => $data['error'][$key],
                    'name' => $data['name'][$key],
                    'size' => $data['size'][$key],
                    'tmp_name' => $data['tmp_name'][$key],
                    'type' => $data['type'][$key]
                ));
        }
        return $files;
    }

    protected function extractFile($data)
    {
        $file = $this->normalizeFile($data);
        if (is_array($file)) {
            $keys = array_keys($file);
            sort($keys);
            
            if ($keys == self::$fileKeys) {
                if (UPLOAD_ERR_NO_FILE == $file['error']) {
                    $file = null;
                } else {
                    $file = array(
                        'name' => $file['name'],
                        'type' => $file['type'],
                        'size' => $file['size'],
                        'data' => fopen($file['tmp_name'], 'r')
                    );
                }
            } else {
                $file = array_map(array(
                    $this,
                    'extractFile'
                ), $file);
            }
        }
        return $file;
    }
}
