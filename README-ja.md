ArrayWalker
===========

組み込み関数 array_walk() と array_map() のオブジェクト指向ラッパークラス


インストール
------------

[Composer](http://getcomposer.org/)を使用して `$ composer require noi/array-walker` を実行するか、
`composer.json` に以下を追加してください。

```json
{
    "require": {
        "noi/array-walker": "dev-master"
    }
}
```


使い方
------

例1: 文字列要素の一括処理

```php
<?php
$names = array('*APPLE*', '*ORANGE*', '*LEMON*');

$walker = new \Noi\Util\ArrayWalker($names);
$result = $walker->trim('*')->strtolower()->ucfirst();

assert($result->getArrayCopy() === array('Apple', 'Orange', 'Lemon'));
assert((array) $result === array('Apple', 'Orange', 'Lemon'));
```
以下のように明示的に`map()`メソッドを呼んでも、同様の結果が得られます。
```php
// ...
$result = $walker->map(function ($name) {
    return ucfirst(strtolower(trim($name, '*')));
});
```

配列の要素がオブジェクト以外の場合、
`$walker->trim()`の`trim`は「関数名」として解釈し、各要素に対して`trim()`関数を実行します。



例2: 複数オブジェクトの一括処理

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
以下のように明示的に`walk()`メソッドを呼んでも、同様の結果が得られます。
```php
// ...
$walker->walk(function ($node) {
    $node->setAttribute('type', 'engineer');
});
```

配列の要素がオブジェクトの場合、
`$walker->setAttribute()`の`setAttribute`は「メソッド名」と解釈して、
各要素の`setAttribute()`メソッドを呼びます。


ライセンス
----------

ArrayWalkerクラスのライセンスは、MITライセンスです。
詳しくは`LICENSE`ファイルの規約を確認してください。
