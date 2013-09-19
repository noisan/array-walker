ArrayWalker
===========

An OOP wrapper for the built-in array_walk() and array_map().


Installation
------------

Using [Composer](http://getcomposer.org/), just `$ composer require noi/array-walker` package or:

```json
{
    "require": {
        "noi/array-walker": "dev-master"
    }
}
```

Usage
-----

Example 1:
```php
<?php
$names = array('*APPLE*', '*ORANGE*', '*LEMON*');

$walker = new \Noi\Util\ArrayWalker($names);
$result = $walker->trim('*')->strtolower()->ucfirst();

assert($result->getArrayCopy() === array('Apple', 'Orange', 'Lemon'));
assert((array) $result === array('Apple', 'Orange', 'Lemon'));
```
The following code returns the same result as the above:
```php
// ...
$result = $walker->map(function ($name) {
    return ucfirst(strtolower(trim($name, '*')));
});
```

Example 2:
```php
<?php
$dom = new \DOMDocument();
$dom->loadXML('<users><user>Alice</user><user>Bob</user></users>');
$users = $dom->getElementsByTagName('user');

$walker = new \Noi\Util\ArrayWalker($users);
$walker->setAttribute('type', 'engineer');

assert(trim($dom->saveHtml()) ==
    '<users><user type="engineer">Alice</user><user type="engineer">Bob</user></users>');
```
The following code returns the same result as the above:
```php
// ...
$walker->walk(function ($node) {
    $node->setAttribute('type', 'engineer');
});
```


License
-------

ArrayWalker is licensed under the MIT License - see the `LICENSE` file for details.
