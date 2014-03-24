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

use Bread\Networking;
use Bread\Event;
use DateTime;
use Bread\Networking\HTTP\Connectors\Apache2\Loop;
use Bread\Networking\HTTP\Connectors\Apache2;
use Bread\Networking\HTTP\Parsers\MultipartFormData;
use Bread\Console\Logger;

class Server extends Event\Emitter implements Interfaces\Server
{

    private $io;

    public function __construct(Event\Interfaces\Loop $loop, array $context = array())
    {
        $this->logger = new Logger();
        $this->io = new Networking\Server($loop, $context);
        $this->io->on('connection', function ($conn) {
            // TODO: chunked transfer encoding
            $parser = new Request\Parser();
            $parser->on('headers', function (Request $request, $data) use ($conn, $parser) {
                $request->emit('headers', array(
                    $request
                ));
                if (preg_match('|^multipart/form-data|', $request->headers['Content-Type'])) {
                    $multipart = new MultipartFormData($request);
                }
                $this->logger->log("New request from: {$request->connection->getRemoteAddress()}");
                $this->logger->log($request->startLine, 'light_blue');
                $this->logger->log((string) $request->headers, 'blue');
                $this->handleRequest($conn, $parser, $request, $data);
                $conn->removeListener('data', array(
                    $parser,
                    'parse'
                ));
                $conn->on('end', function () use ($request) {
                    $request->emit('end');
                });
                $conn->on('data', function ($data) use ($request) {
                    $request->emit('data', array(
                        $data
                    ));
                });
                $request->on('pause', function () use ($conn) {
                    $conn->emit('pause');
                });
                $request->on('resume', function () use ($conn) {
                    $conn->emit('resume');
                });
            });
            $conn->on('data', array(
                $parser,
                'parse'
            ));
        });
    }

    public function handleRequest(Networking\Interfaces\Connection $conn, Request\Parser $parser, Request $request, $data)
    {
        $response = new Response($request);
        $response->headers['Date'] = gmdate(Response::DATETIME_FORMAT);
        $response->headers['Server'] = __CLASS__;
        $response->once('headers', function ($response) use ($conn) {
            $color = $response->status >= 400 ? 'red' : 'green';
            $this->logger->log("Response sent to: {$conn->getRemoteAddress()}");
            $this->logger->log($response->startLine, "light_$color");
            $this->logger->log((string) $response->headers, $color);
            $conn->write((string) $response);
        });
        if (!$this->listeners('request')) {
            return $response->end();
        }
        if (isset($request->headers['Connection']) && 'close' === $request->headers['Connection']) {
            $parser->removeAllListeners();
        } else {
            $request->on('end', function () use ($conn, $parser) {
                $conn->on('data', array(
                    $parser->reset(),
                    'parse'
                ));
            });
        }
        $this->emit('request', array(
            $request,
            $response
        ));
        $request->on('data', function ($data) use ($request) {
            $request->receivedLength += strlen($data);
            if ($request->receivedLength >= $request->length) {
                $request->end();
            }
        });
        if ((isset($request->headers['Content-Length']) || isset($request->headers['Transfer-Encoding']))) {
            is_null($data) || $request->emit('data', array(
                $data
            ));
        } else {
            $request->end();
        }
    }

    public function listen($port, $host = '127.0.0.1')
    {
        return $this->io->listen($port, $host);
    }

    public function getPort()
    {
        return $this->io->getPort();
    }

    public function run()
    {
        return $this->io->run();
    }

    public function shutdown()
    {
        return $this->io->shutdown();
    }

    public static function factory($sapi, array $context = array())
    {
        switch ($sapi) {
          case 'cli':
              $loop = Event\Loop\Factory::create();
              return new Server($loop, $context);
          case 'cli-server':
          case 'apache2handler':
              $loop = new Loop();
              return new Apache2($loop, $context);
          default:
              throw new Exception(sprintf('SAPI %s not supported', $sapi));
        }
    }
}
