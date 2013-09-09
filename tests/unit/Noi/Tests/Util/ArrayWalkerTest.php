<?php
namespace Noi\Tests\Util;

use Noi\Util\ArrayWalker;

class ArrayWalkerTest extends \PHPUnit_Framework_TestCase
{
    private $walker;
    private $mockObject;

    public function setUp()
    {
        $this->mockObject = $this->createMockObjectElement(array('foo', 'trim'));
    }

    protected function createArrayWalker($traversable)
    {
        return new ArrayWalker($traversable);
    }

    protected function createMockObjectElement($methods)
    {
        return $this->getMock('stdClass', $methods);
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
}
