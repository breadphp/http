<?php
namespace Bread\Networking\HTTP\Parsers;

use Bread\Networking\HTTP\Request;

class MultipartFormData
{

    private $request;

    private $boundary;

    private $parts;

    private $currentPart;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->parts = array();
        $this->currentPart = array();
        preg_match('/boundary=(.*)$/', $this->request->headers['Content-Type'], $matches);
        $this->boundary = $matches[1];
        $this->request->on('data', array(
            $this,
            'parse'
        ));
        $this->request->on('end', function ()
        {
            $this->request->emit('parts', array(
                $this->parts
            ));
        });
    }

    public function parse($data)
    {
        $buffer = isset($this->currentPart['buffer']) ? $this->currentPart['buffer'] : '';
        if (! preg_match("/-+{$this->boundary}-*\r\n/", $buffer . $data)) {
            if ($this->currentPart && isset($this->currentPart['body'])) {
                $this->currentPart['size'] = strlen($buffer);
                fwrite($this->currentPart['body'], $buffer);
                $this->currentPart['buffer'] = $data;
            } else {
                $this->currentPart['buffer'] = $buffer . $data;
            }
        } else {
            $this->splitChunk($buffer . $data);
        }
    }

    public function splitChunk($chunk)
    {
        $parts = preg_split("/\r?\n?-+{$this->boundary}-*\r\n/", $chunk);
        $last = array_pop($parts);
        foreach (array_filter($parts) as $part) {
            if (!isset($this->currentPart['headers']) && !isset($this->currentPart['body'])) {
                $explode = explode("\r\n\r\n", $part, 2);
                if (isset($explode[1])) {
                    if ($headers = $this->headers($explode[0])) {
                        $this->currentPart['name'] = key($headers);
                        $this->currentPart['headers'] = array_merge($headers[key($headers)]['headers'], isset($this->currentPart['headers']) ? $this->currentPart['headers'] : array());
                        if (isset($headers[key($headers)]['meta'])) {
                            $this->currentPart['meta'] = $headers[key($headers)]['meta'];
                        }
                        $part = $explode[1];
                    } else {
                        $this->currentPart = array('buffer' => $part);
                    }
                } else {
                    $this->currentPart = array('buffer' => $part);
                }
            }
            if(!isset($this->currentPart['body']) && isset($this->currentPart['headers'])) {
                $this->currentPart['body'] = fopen('php://temp', 'a+');
            }
            if(isset($this->currentPart['headers'])){
                $this->currentPart['size'] = strlen($part);
                fwrite($this->currentPart['body'], $part);
                $this->normalizePart();
            }
        }
        $this->currentPart = array(
            'buffer' => $last
        );
    }

    protected function normalizePart()
    {
        $name = $this->currentPart['name'];
        unset($this->currentPart['name'], $this->currentPart['buffer']);
        $this->parts[$name][] = $this->currentPart;
        rewind($this->currentPart['body']);
        $this->request->emit('part', array(
            $this->currentPart
        ));
        $this->currentPart = array();
    }

    public function headers($data)
    {
        $headers = array();
        foreach (explode("\r\n", trim($data)) as $header) {
            preg_match("/(?<key>[^:]*):(?<value>.*)$/", $header, $values);
            if ($values['key'] === 'Content-Disposition') {
                foreach (explode(";", $values['value']) as $fields) {
                    preg_match('/(?<key>[^=]*)="(?<value>.*)"/', trim($fields), $field);
                    if ($field && $field['key'] === 'name') {
                        $name = $field['value'];
                        $headers[$name]['headers']['Content-Type'] = 'text/plain';
                    } elseif ($field) {
                        $headers[$name]['meta'][$field['key']] = trim($field['value']);
                    }
                }
            } else {
                $headers[$name]['headers'][$values['key']] = trim($values['value']);
            }
        }
        return $headers;
    }
}
