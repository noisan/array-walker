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
        return array_map(function ($element) use ($method, $args) {
            if (is_object($element)) {
                return call_user_func_array(array($element, $method), $args);
            } else {
                return call_user_func_array($method, array_merge(array($element), $args));
            }
        }, $this->getArrayCopy());
    }
}
