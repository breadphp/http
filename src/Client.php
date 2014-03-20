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
use Bread\Promises\Deferred;
use Bread\Networking;
use Bread\Event;

class Client
{

    private $loop;

    private $protocol;

    private $resolver;

    public function __construct(Event\Interfaces\Loop $loop, $nameserver = '127.0.1.1', $protocol = 'HTTP/1.1')
    {
        $this->loop = $loop;
        $this->protocol = $protocol;
        $this->resolver = Networking\DNS\Resolver\Factory::create($nameserver, $loop);
    }

    public function get($url, $headers = array())
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($this->protocol === 'HTTP/1.1' && !isset($headers['Host'])) {
            $headers['Host'] = $host;
        }
        $deferred = new Deferred();
        $this->resolver->resolve($host)->then(function ($ip) use ($url, $headers, $deferred) {
            $port = parse_url($url, PHP_URL_PORT) ?  : 80;
            $client = new Networking\Client($this->loop);
            $client->connect($ip, $port)->then(function ($connection) use ($url, $headers, $deferred) {
                $path = parse_url($url, PHP_URL_PATH);
                $this->handleConnection($deferred, $connection);
                $request = new Request($connection, 'GET', $path, $this->protocol, $headers);
                $request->end($request);
            });
        }, function ($e) {
            var_dump($e->getMessage());
        });
        return $deferred->promise();
    }

    public function getJSON($url, $headers = array())
    {
        return $this->get($url, $headers)->then(function ($response, $data) {
            $json = $data;
            $response->on('data', function ($data) use (&$json) {
                $json .= $data;
            })->on('end', function () use ($promise, &$json) {
                $promise->resolve($json);
            });
        });
    }

    protected function handleConnection(Deferred $promise, Connection $connection)
    {
        $parser = new Response\Parser();
        $parser->on('headers', function (Response $response, $data) use ($connection, $parser, $promise) {
            $connection->removeListener('data', array(
                $parser,
                'parse'
            ));
            $connection->on('end', function () use ($response){
                $response->emit('end');
            });
            $connection->on('data', function ($data) use ($response) {
                $response->emit('data', array(
                    $data
                ));
            });
            $response->on('pause', function () use ($connection) {
                $connection->emit('pause');
            });
            $response->on('resume', function () use ($connection) {
                $connection->emit('resume');
            });
            $promise->resolve(array(
                $response,
                $data
            ));
        });
        $connection->on('data', array(
            $parser,
            'parse'
        ));
    }
}

