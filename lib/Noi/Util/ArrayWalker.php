<?php
namespace Noi\Util;

use ArrayIterator;
use Traversable;

/**
 * ArrayWalker, an OOP wrapper for array_walk() and array_map().
 *
 * Usage:
 *
 * <code>
 * <?php
 * $names = array('*APPLE*', '*ORANGE*', '*LEMON*');
 *
 * $walker = new \Noi\Util\ArrayWalker($names);
 * $result = $walker->trim('*')->strtolower()->ucfirst();
 *
 * assert($result->getArrayCopy() === array('Apple', 'Orange', 'Lemon'));
 * assert((array) $result === array('Apple', 'Orange', 'Lemon'));
 * assert($result[0] === 'Apple' and $result[1] === 'Orange' and $result[2] === 'Lemon');
 *
 * // The following code returns the same result as the above:
 * $result = $walker->map(function ($name) {
 *     return ucfirst(strtolower(trim($name, '*')));
 * });
 * </code>
 *
 * <code>
 * <?php
 * $dom = new \DOMDocument();
 * $dom->loadXML('<users><user>Alice</user><user>Bob</user></users>');
 * $users = $dom->getElementsByTagName('user');
 *
 * $walker = new \Noi\Util\ArrayWalker($users);
 * $walker->setAttribute('type', 'engineer');
 *
 * assert(trim($dom->saveHtml()) ==
 *     '<users><user type="engineer">Alice</user><user type="engineer">Bob</user></users>');
 *
 * // The following code returns the same result as the above:
 * $walker->walk(function ($node) {
 *     $node->setAttribute('type', 'engineer');
 * });
 * ?>
 * </code>
 *
 * @author Akihiro Yamanoi <akihiro.yamanoi@gmail.com>
 */
class ArrayWalker extends ArrayIterator
{
    /**
     * Constructor.
     *
     * @param mixed $traversable The array or object to be iterated on.
     */
    public function __construct($traversable)
    {
        if ($traversable instanceof Traversable) {
            parent::__construct(iterator_to_array($traversable));
        } else {
            parent::__construct((array) $traversable);
        }
    }

    /**
     * Proxy all methods to each element.
     *
     * @param string $method The method name to be called.
     * @param array $args The arguments to be passed to the method, as an indexed array.
     * @return ArrayWalker The newly created ArrayWalker.
     */
    public function __call($method, $args)
    {
        return $this->callEach($method, $args, 0);
    }

    /**
     * Applies the given callback to each element in the walker.
     *
     * This is a wrapper for the array_walk() function.
     * @see http://php.net/array_walk
     *
     * @param callable $callback
     * @return bool True on success or false on failure
     */
    public function walk($callback)
    {
        return array_walk($this, $callback);
    }

    /**
     * Same as walk() method, except that it returns self instance.
     *
     * @param type $callback
     * @return ArrayWalker This ArrayWalker instance.
     */
    public function each($callback)
    {
        $this->walk($callback);
        return $this;
    }

    /**
     * Applies the given callback to each element in the walker.
     *
     * This is a wrapper for the array_map() function.
     * @see http://php.net/array_map
     *
     * This method returns a new ArrayWalker with
     * the elements returned by the array_map().
     *
     * @param type $callback
     * @return ArrayWalker The newly created ArrayWalker
     */
    public function map($callback)
    {
        return $this->createSelf(array_map($callback, $this->getArrayCopy()));
    }

    /**
     * Applies the given callback to the current element in the walker.
     *
     * @param type $callback
     * @return mixed The return value of the callback, or false on error.
     */
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
            return $walker->callEach($function, $args, $offset);
        });
    }

    public function callEach($name, $args = array(), $offset = 0)
    {
        $padded = array_pad((array) $args, (0 < $offset) ? $offset - 1 : 0, null);
        array_splice($padded, $offset, 0, array(null));  // insert place holder

        $result = array();
        foreach ($this as $key => &$element) {
            if (is_object($element)) {
                $result[$key] = call_user_func_array(array($element, $name), $args);
            } else {
                $padded[$offset] = &$element;
                $result[$key] = call_user_func_array($name, $padded);
            }
        }
        return $this->createSelf($result);
    }

    protected function createSelf($traversable)
    {
        return new static($traversable);
    }
}
