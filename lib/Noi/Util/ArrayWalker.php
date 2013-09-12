<?php
namespace Noi\Util;

use ArrayIterator;

/**
 *
 * @author Akihiro Yamanoi <akihiro.yamanoi@gmail.com>
 */
class ArrayWalker extends ArrayIterator
{
    public function __construct($traversable)
    {
        parent::__construct($traversable);
    }

    public function __call($method, $args)
    {
        $result = array();
        foreach ($this as $key => &$element) {
            if (is_object($element)) {
                $result[$key] = call_user_func_array(array($element, $method), $args);
            } else {
                $result[$key] = call_user_func_array($method, array_merge(array(&$element), $args));
            }
        }
        return $result;
    }

    public function walk($callback)
    {
        return array_walk($this, $callback);
    }

    public function each($callback)
    {
        return $this->walk($callback);
    }

    public function map($callback)
    {
        return $this->createSelf(array_map($callback, $this->getArrayCopy()));
    }

    public function apply($callback)
    {
        if (!$this->valid()) {
            return;
        }
        $key = $this->key();
        return call_user_func_array($callback, array(&$this[$key], $key));
    }

    public function __get($name)
    {
        return new ArrayWalkerCallback($this, $name);
    }

    protected function createSelf($traversable)
    {
        return new static($traversable);
    }
}
