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
namespace Bread\Networking\HTTP\Request;

use ArrayAccess, Countable, IteratorAggregate, ArrayIterator;

class Query implements ArrayAccess, Countable, IteratorAggregate
{

    protected $query;

    public function __construct($query)
    {
        parse_str($query, $this->query);
    }

    public function __toString()
    {
        return http_build_query($this->query);
    }

    public function offsetExists($offset)
    {
        return isset($this->query[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->query[$offset]) ? $this->query[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->query[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->query[$offset]);
    }

    public function count()
    {
        return count($this->query);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->query);
    }
}
