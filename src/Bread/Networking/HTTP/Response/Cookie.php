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
namespace Bread\Networking\HTTP\Response;

use DateTime, InvalidArgumentException;

/**
 * Represents a HTTP cookie
 */
class Cookie
{

    protected $name;

    protected $value;

    protected $domain;

    protected $expire;

    protected $path;

    protected $secure;

    protected $httpOnly;

    /**
     * Constructs a cookie
     *
     * @param string $name
     *            The name of the cookie
     * @param string $value
     *            The value of the cookie
     * @param integer|string|DateTime $expire
     *            The time the cookie expires
     * @param string $path
     *            The path on the server in which the cookie will be available on
     * @param string $domain
     *            The domain that the cookie is available to
     * @param Boolean $secure
     *            Whether the cookie should only be transmitted over a secure HTTPS connection from the client
     * @param Boolean $httpOnly
     *            Whether the cookie will be made accessible only through the HTTP protocol
     *            
     * @throws InvalidArgumentException
     */
    public function __construct($name, $value = null, $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        if (preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new InvalidArgumentException(sprintf('The cookie name "%s" contains invalid characters.', $name));
        }
        if (empty($name)) {
            throw new InvalidArgumentException('The cookie name cannot be empty.');
        }
        if ($expire instanceof DateTime) {
            $expire = $expire->format('U');
        } elseif (!is_numeric($expire)) {
            $expire = strtotime($expire);
            if (false === $expire || -1 === $expire) {
                throw new InvalidArgumentException('The cookie expiration time is not valid.');
            }
        }
        $this->name = $name;
        $this->value = $value;
        $this->domain = $domain;
        $this->expire = $expire;
        $this->path = empty($path) ? '/' : $path;
        $this->secure = (bool) $secure;
        $this->httpOnly = (bool) $httpOnly;
    }

    /**
     * Returns the cookie as a string
     *
     * @return string The cookie
     */
    public function __toString()
    {
        $str = urlencode($this->name) . '=';
        if ('' === (string) $this->value) {
            $str .= 'deleted; expires=' . gmdate("D, d-M-Y H:i:s T", time() - 31536001);
        } else {
            $str .= urlencode($this->value);
            if ($this->expire !== 0) {
                $str .= '; expires=' . gmdate("D, d-M-Y H:i:s T", $this->expire);
            }
        }
        if (null !== $this->path) {
            $str .= '; path=' . $this->path;
        }
        if (null !== $this->domain) {
            $str .= '; domain=' . $this->domain;
        }
        if (true === $this->secure) {
            $str .= '; secure';
        }
        if (true === $this->httpOnly) {
            $str .= '; httponly';
        }
        return $str;
    }

    /**
     * Checks whether the cookie should only be transmitted over a secure HTTPS connection from the client
     *
     * @return bool
     */
    public function isSecure()
    {
        return $this->secure;
    }

    /**
     * Checks whether the cookie will be made accessible only through the HTTP protocol
     *
     * @return bool
     */
    public function isHttpOnly()
    {
        return $this->httpOnly;
    }

    /**
     * Whether this cookie is about to be cleared
     *
     * @return bool
     */
    public function isCleared()
    {
        return $this->expire < time();
    }
}
