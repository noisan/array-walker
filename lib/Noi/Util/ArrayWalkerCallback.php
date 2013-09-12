<?php
namespace Noi\Util;

use ArrayAccess;

/**
 * @author Akihiro Yamanoi <akihiro.yamanoi@gmail.com>
 */
class ArrayWalkerCallback implements ArrayAccess
{
    private $traversable;
    private $callback;
    private $offset = 0;

    public function __construct($traversable, $callback)
    {
        $this->traversable = $traversable;
        $this->callback = $callback;
    }

    public function __invoke(/* $args */)
    {
        $args = func_get_args();
        $padded = array_pad($args, (0 < $this->offset) ? $this->offset - 1 : 0, null);
        array_splice($padded, $this->offset, 0, array(null));  // insert place holder

        $result = array();
		foreach ($this->traversable as $key => &$element) {
            // function call
            $padded[$this->offset] = &$element;
            $result[$key] = call_user_func_array($this->callback, $padded);
		}
		return $result;
    }

    public function offsetGet($offset)
    {
        // specify argument position
        $clone = clone $this;
		$clone->offset = $offset;
		return $clone;
    }

    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetSet($offset, $value)
    {
        // ignore
    }

    public function offsetUnset($offset)
    {
        // ignore
    }
}
