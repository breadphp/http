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

class Request extends Message
{

    public $receivedLength = 0;

    public $requestLine;

    public $method;

    public $uri;

    public $query;

    public $cookies = array();

    public function __construct(Networking\Interfaces\Connection $connection, $method = 'GET', $uri = '/', $protocol = 'HTTP/1.1', $headers = array(), $body = null)
    {
        $this->requestLine = implode(' ', array(
            $method,
            $uri,
            $protocol
        ));
        $this->method = $method;
        $this->uri = parse_url($uri, PHP_URL_PATH);
        $this->query = new Request\Query(parse_url($uri, PHP_URL_QUERY));
        parent::__construct($connection, $protocol, $this->requestLine, $headers, $body);
        // TODO Not here
        $this->cookies = new Message\Bag($this->explodeHeader('Cookie'));
    }

    public function __get($name)
    {
        switch ($name) {
            case 'host':
                return isset($this->headers['Host']) ? $this->headers['Host'] : null;
            default:
                return parent::__get($name);
        }
    }

    public function negotiate($header, $supported, &$weightedMatches = array())
    {
        if (!$this->headers[$header]) {
            return array_shift($supported);
        }
        $parsedHeader = explode(',', $this->headers[$header]);
        foreach ($parsedHeader as $i => $r) {
            $parsedHeader[$i] = self::parseAndNormalizeMediaRange($r);
        }
        $weightedMatches = array();
        foreach ($supported as $index => $mimeType) {
            list ($quality, $fitness) = self::qualityAndFitnessParsed($mimeType, $parsedHeader);
            if (!empty($quality)) {
                $preference = 0 - $index;
                $weightedMatches[] = array(
                    array(
                        $quality,
                        $fitness,
                        $preference
                    ),
                    $mimeType
                );
            }
        }
        array_multisort($weightedMatches);
        $firstChoice = array_pop($weightedMatches);
        return (empty($firstChoice[0][0]) ? null : $firstChoice[1]);
    }

    protected static function parseAndNormalizeMediaRange($mediaRange)
    {
        $parsedMediaRange = self::parseMediaRange($mediaRange);
        $params = $parsedMediaRange[2];
        if (!isset($params['q']) || !is_numeric($params['q']) || floatval($params['q']) > 1 || floatval($params['q']) < 0) {
            $parsedMediaRange[2]['q'] = '1';
        }
        return $parsedMediaRange;
    }

    protected static function parseMediaRange($mediaRange)
    {
        $parts = explode(';', $mediaRange);
        $params = array();
        foreach ($parts as $i => $param) {
            if (strpos($param, '=') !== false) {
                list ($k, $v) = explode('=', trim($param));
                $params[$k] = $v;
            }
        }
        $fullType = trim($parts[0]);
        if ($fullType == '*') {
            $fullType = '*/*';
        }
        list ($type, $subtype) = explode('/', $fullType);
        if (!$subtype) {
            throw new Client\Exceptions\BadRequest('Malformed media-range: ' . $mediaRange);
        }
        $plusPos = strpos($subtype, '+');
        if (false !== $plusPos) {
            $genericSubtype = substr($subtype, $plusPos + 1);
        } else {
            $genericSubtype = $subtype;
        }
        return array(
            trim($type),
            trim($subtype),
            $params,
            $genericSubtype
        );
    }

    protected static function qualityParsed($mimeType, $parsedRanges)
    {
        list ($q, $fitness) = self::qualityAndFitnessParsed($mimeType, $parsedRanges);
        return $q;
    }

    protected static function qualityAndFitnessParsed($mimeType, $parsedRanges)
    {
        $bestFitness = -1;
        $bestFitQuality = 0;
        list ($targetType, $targetSubtype, $targetParams) = self::parseAndNormalizeMediaRange($mimeType);
        foreach ($parsedRanges as $item) {
            list ($type, $subtype, $params) = $item;
            if (($type == $targetType || $type == '*' || $targetType == '*') && ($subtype == $targetSubtype || $subtype == '*' || $targetSubtype == '*')) {
                $paramMatches = 0;
                foreach ($targetParams as $k => $v) {
                    if ($k != 'q' && isset($params[$k]) && $v == $params[$k]) {
                        $paramMatches++;
                    }
                }
                $fitness = ($type == $targetType && $targetType != '*') ? 100 : 0;
                $fitness += ($subtype == $targetSubtype && $targetSubtype != '*') ? 10 : 0;
                $fitness += $paramMatches;
                if ($fitness > $bestFitness) {
                    $bestFitness = $fitness;
                    $bestFitQuality = $params['q'];
                }
            }
        }
        return array(
            (float) $bestFitQuality,
            $bestFitness
        );
    }

    protected static function quality($mimeType, $ranges)
    {
        $parsedRanges = explode(',', $ranges);
        foreach ($parsedRanges as $i => $r) {
            $parsedRanges[$i] = self::parseAndNormalizeMediaRange($r);
        }
        return self::qualityParsed($mimeType, $parsedRanges);
    }
}
