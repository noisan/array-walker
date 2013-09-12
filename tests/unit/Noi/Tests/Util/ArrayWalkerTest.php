<?php
namespace Noi\Tests\Util;

use Noi\Util\ArrayWalker;

class ArrayWalkerTest extends \PHPUnit_Framework_TestCase
{
    private $unused = null;
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

    protected function assertArrayWalkerEmpty($walker)
    {
        $this->assertInstanceOf('Noi\Util\ArrayWalker', $walker);
        $this->assertEmpty($walker->getArrayCopy());
        $this->assertInternalType('array', $walker->getArrayCopy());
    }

    protected function assertArrayWalkerEquals($expected, $walker)
    {
        $this->assertInstanceOf('Noi\Util\ArrayWalker', $walker);
        $this->assertEquals($expected, $walker->getArrayCopy());
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
        $this->assertArrayWalkerEmpty($this->walker->ignoredMethod());
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
        $this->assertArrayWalkerEquals($expected, $result);
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
        $this->assertArrayWalkerEquals($expected, $result);
    }

    /**
     * @test
     * ja: 空の配列の場合、walk()は、コールバックを呼ばず何もしない。
     */
    public function walk_DoesNotInvokeCallback_ForEmptyArray()
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
    public function walk_InvokesProvidedCallbackForEachElement()
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
     * ja: each()は、walk()メソッドを呼ぶ。
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

    /**
     * @test
     * ja: each()は、自分自身を返す。
     */
    public function each_ReturnsSelf()
    {
        // Setup
        $this->walker = $this->createArrayWalker(array());
        $this->unused = function () {};

        // Act
        $result = $this->walker->each($this->unused);

        // Assert
        $this->assertSame($this->walker, $result);
    }

    /**
     * @test
     * ja: 保持している配列が空の場合、map()は、空のArrayWalkerを返す。
     */
    public function map_ReturnsEmptyArray_forEmptyArray()
    {
        // Setup
        $emptyArray = array();
        $this->walker = $this->createArrayWalker($emptyArray);

        // Act
        $result = $this->walker->map($this->unused);

        // Assert
        $this->assertArrayWalkerEmpty($result);
    }

    /**
     * @test
     * ja: 保持している配列が空の場合、map()は、コールバックを呼ばない。
     */
    public function map_DoesNotInvokeProvidedCallback_forEmptyArray()
    {
        // Setup
        $emptyArray = array();
        $this->walker = $this->createArrayWalker($emptyArray);

        // Expect
        $this->mockCallback->expects($this->never())
                ->method('__invoke');

        // Act
        $this->walker->map($this->mockCallback);
    }

    /**
     * @test
     * ja: 要素を持つなら、map()は、与えられたコールバックを各要素に適用して
     *     結果の配列を新しいArrayWalkerとして返す。
     */
    public function map_ReturnsArrayWalkerOfReturnedValues()
    {
        // Setup
        $testArray = array('*A*', '*B*', '*C*');
        $expected = array('A', 'B', 'C');
        $this->walker = $this->createArrayWalker($testArray);

        // Act
        $result = $this->walker->map(function ($value) {
            return trim($value, '*');
        });

        // Assert
        $this->assertArrayWalkerEquals($expected, $result);
    }

    /**
     * @test
     * ja: イテレータが有効なエントリを指しているとき、
     *     apply()は、与えられたコールバックをその要素に適用する。
     */
    public function apply_InvokesProvidedCallbackWithCurrentElement()
    {
        // Setup
        $testEntry = 'test';
        $this->walker = $this->createArrayWalker(array($testEntry));

        // Expect
        $this->mockCallback->expects($this->once())
                ->method('__invoke')->with($testEntry);

        // Act
        $this->walker->apply($this->mockCallback);
    }

    /**
     * @test
     * ja: イテレータが無効なエントリを指しているとき、
     *     apply()は、コールバックを実行しない。
     */
    public function apply_DoesNotInvokeProvidedCallback_InvalidElement()
    {
        // Setup
        $this->walker = $this->createArrayWalker(array());

        // Expect
        $this->mockCallback->expects($this->never())
                ->method('__invoke');

        // Act
        $this->walker->apply($this->mockCallback);

        // Assert
        $this->assertFalse($this->walker->valid());
    }

    /**
     * @test
     * ja: apply()に渡したコールバックは、最初の引数が現在の要素。
     */
    public function providedCallback_ForApplyMethod_FirstArgumentIsCurrentElement()
    {
        // Setup
        $testArray = array('first' => 123, 'second' => 456);
        $this->walker = $this->createArrayWalker($testArray);

        // Expect
        $this->mockCallback->expects($this->exactly(2))
                ->method('__invoke');

        $this->mockCallback->expects($this->at(0))
                ->method('__invoke')->with(123, 'first');

        $this->mockCallback->expects($this->at(1))
                ->method('__invoke')->with(456, 'second');

        // Act
        $this->walker->apply($this->mockCallback);
        $this->walker->next();
        $this->walker->apply($this->mockCallback);
    }

    /**
     * @test
     * ja: apply()に渡したコールバックは、現在の要素に変更を加えることができる。
     */
    public function providedCallback_ForApplyMethod_CanModifyCurrentElement()
    {
        // Setup
        $origArray = array(' A ', ' B ', ' C ');
        $expected = array('a', ' B ', ' C ');
        $this->walker = $this->createArrayWalker($origArray);

        // Act
        $this->walker->apply(function (&$value) {
            $value = strtolower(trim($value));
        });

        // Assert
        $this->assertEquals($expected, $this->walker->getArrayCopy());
        $this->assertNotEquals($expected, $origArray);
    }

    /**
     * @test
     * ja: 非オブジェクトの要素に対して__call()で「関数」を呼ぶ時、
     *     デフォルトでは、各要素を関数の最初の引数とする。
     *
     * ※つまり、str_replace()等では意図しない動作をする。
     */
    public function argumentPosition_EachElementIsPassedAsFirstArgument_Default()
    {
        // Setup
        $testArray = array('*_scalar_*', 'another' => '*test_value*');
        $expected = array('_scalar_', 'another' => 'test_value');
        $this->walker = $this->createArrayWalker($testArray);

        // Act
        // same effect as:
        // $this->walker[$this->key()] = trim($this->walker->current(), '*');
        $result = $this->walker->trim('*');

        // Assert
        $this->assertArrayWalkerEquals($expected, $result);
    }

    /**
     * @test
     * ja: 非オブジェクトの要素に対して__call()で「関数」を呼ぶ時、
     *     引数位置を指定すると、各要素は関数の指定位置の引数になる。
     */
    public function argumentPosition_EachElementIsPassedAsSpecifiedOffsetArgument_OffsetSpecified()
    {
        // Setup
        $testArray = array('_scalar_', 'another' => '_test_value_');
        $expected1 = array('*scalar*', 'another' => '*test*value*');
        $expected2 = array('*scalar_', 'another' => '*test_value_');
        $this->walker = $this->createArrayWalker($testArray);

        // Act

        // same effect as:
        // $this->walker[$this->key()] = str_replace('_', '*', $this->walker->current());
        $result1 = $this->walker->str_replace[2]('_', '*');

        // same effect as:
        // $this->walker[$this->key()] = preg_replace('/_/', '*', $this->walker->current(), 1);
        $result2 = $this->walker->preg_replace[2]('/_/', '*', 1);

        // Assert
        $this->assertArrayWalkerEquals($expected1, $result1);
        $this->assertArrayWalkerEquals($expected2, $result2);
    }

    /**
     * @test
     * ja: 非オブジェクトの要素に対して__call()で「関数」を呼ぶ時、
     *     引数位置を指定すると、指定位置までの引数はnullで埋められる。
     */
    public function argumentPosition_ArgumentIsPaddedWithNulls_OffsetSpecified()
    {
        // Setup
        $testFormat = '%d, %d, %d';
        $testArray = array('123');
        $expected = array('0, 0, 123');
        $this->walker = $this->createArrayWalker($testArray);

        // Act
        // same effect as:
        // $this->walker[$this->key()] = sprintf($testFormat, null, null, $this->walker->current());
        $result = $this->walker->sprintf[3]($testFormat);

        // Assert
        $this->assertArrayWalkerEquals($expected, $result);
    }

    /**
     * @test
     * ja: 非オブジェクトの要素に対して__call()で「関数」を呼ぶ時、
     *     その関数は各要素の値に変更を加えることができる(引数位置指定なし)。
     */
    public function argumentPosition_CanChangeOriginalValue_Default()
    {
        // Setup
        $testArray = array(array('a'), array('b'));
        $expected = array(array('a', 'TEST'), array('b', 'TEST'));
        $this->walker = $this->createArrayWalker($testArray);

        // Act
        $this->walker->array_push('TEST');

        // Assert
        $this->assertArrayWalkerEquals($expected, $this->walker);
    }

    /**
     * @test
     * ja: 非オブジェクトの要素に対して__call()で「関数」を呼ぶ時、
     *     その関数は各要素の値に変更を加えることができる(引数位置指定あり)。
     */
    public function argumentPosition_CanChangeOriginalValue_OffsetSpecified()
    {
        if (!function_exists('mb_convert_variables')) {
            $this->markTestSkipped('This test requires the mb_convert_variables() function.');
        }

        // Setup
        // full-width "A" and "B"
        $testSJISArray = array("\x82\x60", "\x82\x61");
        $expectedUTF8 = array("\xEF\xBC\xA1", "\xEF\xBC\xA2");
        $this->walker = $this->createArrayWalker($testSJISArray);

        // Act
        $this->walker->mb_convert_variables[2]('UTF8', 'SJIS-win');

        // Assert
        $this->assertArrayWalkerEquals($expectedUTF8, $this->walker);
    }
}
