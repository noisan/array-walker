<?php
namespace Noi\Util;

use ArrayAccess;

/**
 * @author Akihiro Yamanoi <akihiro.yamanoi@gmail.com>
 */
class ArrayWalkerCallback implements ArrayAccess
{
    private $callback;
    private $offset;

    public function __construct($callback, $offset = 0)
    {
        $this->callback = $callback;
        $this->offset = $offset;
    }

    public function __invoke(/* $args */)
    {
        $args = func_get_args();
        return call_user_func($this->callback, $this->offset, $args);
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
