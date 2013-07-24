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

use ArrayAccess, Countable, IteratorAggregate, ArrayIterator;

class Bag implements ArrayAccess, Countable, IteratorAggregate
{

    protected $bag = array();

    public function __construct(array $bag = array())
    {
        foreach ($bag as $key => $values) {
            $this->set($key, $values);
        }
    }

    public function has($offset)
    {
        return isset($this->bag[$offset]);
    }

    public function get($offset)
    {
        return isset($this->bag[$offset]) ? $this->bag[$offset] : null;
    }

    public function set($offset, $value)
    {
        $this->bag[$offset] = $value;
    }

    public function remove($offset)
    {
        unset($this->bag[$offset]);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    public function count()
    {
        return count($this->bag);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->bag);
    }
}
