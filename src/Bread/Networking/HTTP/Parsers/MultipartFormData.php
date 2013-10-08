<?php
namespace Bread\Networking\HTTP\Parsers;

use Bread\Networking\HTTP\Request;

class MultipartFormData
{

    public function parse(Request $request, $data)
    {
        preg_match('/boundary=(.*)$/', $request->headers['Content-Type'], $matches);
        
        if (!count($matches)) {
            return null;
        }
        
        $boundary = $matches[1];
        
        $data = preg_split("/-+$boundary/", $data);
        
        array_pop($data);
        
        $parts = array();
        
        foreach ($data as $i => $part) {
            if (empty($part)) {
                continue;
            }
            
            if (strpos($part, 'application/octet-stream') !== false) {
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $part, $matches);
            } else {
                preg_match("/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s", $part, $matches);
            }
            if (isset($parts[$matches[1]])) {
                if (!is_array($parts[$matches[1]])) {
                    $parts[$matches[1]] = array($parts[$matches[1]]);
                }
                $parts[$matches[1]][] = $matches[2];
            } else {
                $parts[$matches[1]] = $matches[2];
            }
        }

        return $parts;
    }
}
