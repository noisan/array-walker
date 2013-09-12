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
        return $this->createSelf($this->callEach($method, $args));
    }

    public function walk($callback)
    {
        return array_walk($this, $callback);
    }

    public function each($callback)
    {
        $this->walk($callback);
        return $this;
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

    public function __get($function)
    {
        $walker = $this;
        return new ArrayWalkerCallback(function ($offset, $args) use ($walker, $function) {
            return $walker->createSelf($this->callEach($function, $args, $offset));
        });
    }

    protected function callEach($callback, $args, $offset = 0)
    {
        $padded = array_pad($args, (0 < $offset) ? $offset - 1 : 0, null);
        array_splice($padded, $offset, 0, array(null));  // insert place holder

        $result = array();
        foreach ($this as $key => &$element) {
            if (is_object($element)) {
                $result[$key] = call_user_func_array(array($element, $callback), $args);
            } else {
                $padded[$offset] = &$element;
                $result[$key] = call_user_func_array($callback, $padded);
            }
        }
        return $result;
    }

    protected function createSelf($traversable)
    {
        return new static($traversable);
    }
}
