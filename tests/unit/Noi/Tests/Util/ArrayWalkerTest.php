<?php
namespace Noi\Tests\Util;

use Noi\Util\ArrayWalker;

class ArrayWalkerTest extends \PHPUnit_Framework_TestCase
{
    private $walker;
    private $mockObject;
    private $mockCallback;

    public function setUp()
    {
        $this->mockObject = $this->createMockObjectElement(array('foo', 'trim'));
        $this->mockCallback = $this->createMockCallback();
    }

    protected function createArrayWalker($traversable)
    {
        return new ArrayWalker($traversable);
    }

    protected function createMockObjectElement($methods)
    {
        return $this->getMock('stdClass', $methods);
    }

    protected function createMockCallback()
    {
        return $this->getMock('stdClass', array('__invoke'));
    }

    /**
     * @test
     * ja: 空の配列に対して、__call()の実行は、空のリストを返す。
     */
    public function magicCallMethod_ReturnsEmptyArray_ForEmptyArray()
    {
        // Setup
        $emptyArray = array();

        // Act
        $this->walker = $this->createArrayWalker($emptyArray);

        // Assert
        $this->assertEmpty($this->walker->ignoredMethod());
    }

    /**
     * @test
     * ja: 要素がオブジェクトなら、__call()の実行は、各要素に対するメソッド呼び出しとして解釈する。
     */
    public function magicCallMethod_InvokesSpecifiedMethod_ObjectElement()
    {
        // Setup
        $testArray = array($this->mockObject);
        $this->walker = $this->createArrayWalker($testArray);

        $param1 = 'test';
        $param2 = 123;
        $param3 = array();
        $param4 = (object) 'param4';

        // Expect
        $this->mockObject->expects($this->once())
                ->method('foo')->with($param1, $param2, $param3, $this->identicalTo($param4));

        // Act
        $this->walker->foo($param1, $param2, $param3, $param4);
    }

    /**
     * @test
     * ja: 要素が非オブジェクトなら、__call()の実行は、各要素に対する関数呼び出しとして解釈する。
     */
    public function magicCallMethod_InvokesSpecifiedFunction_NonObjectElement()
    {
        // Setup
        $testArray = array('*abc*');
        $expected = array('abc');
        $this->walker = $this->createArrayWalker($testArray);

        // Act
        $result = $this->walker->trim('*');

        // Assert
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * ja: __call()の戻り値は、各要素にメソッドまたは関数を適用した結果のリスト。
     */
    public function magicCallMethod_ReturnsArrayOfReturnedValues()
    {
        // Setup
        $testArray = array('__abc__', $this->mockObject);
        $expected = array('abc', 'success');

        $this->walker = $this->createArrayWalker($testArray);

        $this->mockObject->expects($this->any())
                ->method('trim')->with('_')
                ->will($this->returnValue('success'));

        // Act
        $result = $this->walker->trim('_');

        // Assert
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * ja: 空の配列の場合、walk()は、コールバックを呼ばず何もしない。
     */
    public function applyCallbackToEachElement_DoesNotRunCallback_forEmptyArray()
    {
        // Setup
        $emptyArray = array();
        $this->walker = $this->createArrayWalker($emptyArray);

        // Expect
        $this->mockCallback->expects($this->never())
                ->method('__invoke');

        // Act
        $this->walker->walk($this->mockCallback);
    }

    /**
     * @test
     * ja: 要素を持つなら、walk()は、各要素に対して与えられたコールバックを呼ぶ。
     */
    public function applyCallbackToEachElement_InvokesProvidedCallbackForEachElement()
    {
        // Setup
        $testArray = array($this->mockObject, 'abc', 123);
        $this->walker = $this->createArrayWalker($testArray);

        // Expect
        $this->mockCallback->expects($this->exactly(3))
                ->method('__invoke');

        // Act
        $this->walker->walk($this->mockCallback);
    }

    /**
     * @test
     * ja: walk()に渡したコールバックは、保持している各要素の値に変更を加えることができる。
     * (array_walk()と同じ)
     */
    public function providedCallback_CanChangeOriginalValue()
    {
        // Setup
        $origArray = array(1, 2, 3, 4, 5);
        $expected = array(10, 20, 30, 40, 50);
        $this->walker = $this->createArrayWalker($origArray);

        // Act
        $this->walker->walk(function (&$value) {
            $value *= 10;
        });

        // Assert
        $this->assertEquals($expected, $this->walker->getArrayCopy());
        $this->assertNotEquals($expected, $origArray);
    }

    /**
     * @test
     * ja: walk()に渡したコールバックは、引数の2番目に配列のキーが渡される。
     * (array_walk()と同じ)
     */
    public function providedCallback_SecondArgumentIsKey()
    {
        // Setup
        $testArray = array('first' => 1, 'second' => 2);
        $this->walker = $this->createArrayWalker($testArray);

        // Expect
        $this->mockCallback->expects($this->exactly(2))
                ->method('__invoke');

        $this->mockCallback->expects($this->at(0))
                ->method('__invoke')->with($this->anything(), 'first');

        $this->mockCallback->expects($this->at(1))
                ->method('__invoke')->with($this->anything(), 'second');

        // Act
        $this->walker->walk($this->mockCallback);
    }

    /**
     * @test
     * ja: each()は、walk()のエイリアス。
     */
    public function each_CallsWalk()
    {
        // Setup
        $mockWalker = $this->getMockBuilder(get_class($this->createArrayWalker(array('unused'))))
                ->setMethods(array('walk'))
                ->disableOriginalConstructor()->getMock();

        // Expect
        $mockWalker->expects($this->once())
                ->method('walk')->with($this->mockCallback);

        // Act
        $mockWalker->each($this->mockCallback);
    }
}
