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
namespace Bread\Networking\HTTP\Message;

class Header extends Bag
{

    /**
     * Constructor
     *
     * @param array $headers
     *            An array of HTTP headers
     */
    public function __construct(array $headers = array())
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Returns the headers as a string
     *
     * @return string The headers
     */
    public function __toString()
    {
        $headers = '';
        if (!$this->bag) {
            return $headers;
        }
        // $max = max(array_map('strlen', array_keys($this->bag))) + 1;
        // ksort($this->bag);
        foreach ($this->bag as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            foreach ($values as $value) {
                // $headers .= sprintf("%-{$max}s: %s\r\n", $name, $value);
                $headers .= sprintf("%s: %s\r\n", $name, $value);
            }
        }
        return $headers;
    }

    /**
     * Returns true if the given HTTP header contains the given value.
     *
     * @param string $key
     *            The HTTP header name
     * @param string $value
     *            The HTTP value
     *            
     * @return Boolean true if the value is contained in the header, false otherwise
     */
    public function contains($key, $value)
    {
        return in_array($value, $this->get($key, null, false));
    }

    /**
     * Replaces the current HTTP headers by a new set.
     *
     * @param array $headers
     *            An array of HTTP headers
     */
    public function replace(array $headers = array())
    {
        $this->bag = array();
        $this->add($headers);
    }

    /**
     * Adds new headers the current HTTP headers set.
     *
     * @param array $headers
     *            An array of HTTP headers
     */
    public function add(array $headers)
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Returns true if the HTTP header is defined.
     *
     * @param string $key
     *            The HTTP header
     *            
     * @return Boolean true if the parameter exists, false otherwise
     */
    public function has($key)
    {
        return array_key_exists(strtr(strtolower($key), '_', '-'), $this->bag);
    }

    /**
     * Returns a header value by name.
     *
     * @param string $key
     *            The header name
     * @param mixed $default
     *            The default value
     * @param Boolean $first
     *            Whether to return the first value or all header values
     *            
     * @return string array first header value if $first is true, an array of values otherwise
     */
    public function get($key, $default = null, $first = true)
    {
        $key = strtr(strtolower($key), '_', '-');
        
        if (!array_key_exists($key, $this->bag)) {
            if (null === $default) {
                return $first ? null : array();
            }
            
            return $first ? $default : array(
                $default
            );
        }
        
        if ($first) {
            return count($this->bag[$key]) ? $this->bag[$key][0] : $default;
        }
        
        return $this->bag[$key];
    }

    /**
     * Sets a header by name.
     *
     * @param string $key
     *            The key
     * @param string|array $values
     *            The value or an array of values
     * @param Boolean $replace
     *            Whether to replace the actual value of not (true by default)
     */
    public function set($key, $values, $replace = true)
    {
        $key = strtr(strtolower($key), '_', '-');
        
        $values = array_values((array) $values);
        
        if (true === $replace || !isset($this->bag[$key])) {
            $this->bag[$key] = $values;
        } else {
            $this->bag[$key] = array_merge($this->bag[$key], $values);
        }
    }

    /**
     * Removes a header.
     *
     * @param string $key
     *            The HTTP header name
     */
    public function remove($key)
    {
        $key = strtr(strtolower($key), '_', '-');
        unset($this->bag[$key]);
    }
}