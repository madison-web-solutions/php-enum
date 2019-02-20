<?php

use MadisonSolutions\Enum\Enum;
use PHPUnit\Framework\TestCase;

class Fruit extends Enum
{
    public static function definitions() : array {
        return [
            'apple' => [
                'type' => 'Orchard',
            ],
            'pear' => [
                'type' => 'Orchard',
            ],
            'raspberry' => [
                'type' => 'Bramble',
            ],
            'tomato' => [
                'type' => 'Vine',
            ],
        ];
    }
}

class Vegetable extends Enum
{
    public static function definitions() : array {
        return [
            'potato' => [
                'type' => 'Root',
            ],
            'carrot' => [
                'type' => 'Root',
            ],
            'tomato' => [
                'type' => 'Vine',
            ],
        ];
    }
}

class EmptyStringTestEnum extends Enum
{
    public static function definitions() : array {
        return [
            '' => [
                'type' => 'Empty String',
            ],
        ];
    }
}

class NullKeyTestEnum extends Enum
{
    public static function definitions() : array {
        return [
            null => [
                'type' => 'Null',
            ],
        ];
    }
}

class IntegerKeyTestEnum extends Enum
{
    public static function definitions() : array {
        return [
            0 => [
                'label' => 'Zero',
            ],
        ];
    }
}

class EnumTest extends TestCase
{
    public function assertThrows($expectedExceptionClass, $callback, string $msg = '')
    {
        $thrown = null;
        try {
            $callback();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $this->assertInstanceOf($expectedExceptionClass, $thrown, $msg);
    }

    public function testCanAccessMembersWithStaticCall()
    {
        $apple = Fruit::apple();
        $this->assertInstanceOf(Fruit::class, $apple);
        $this->assertSame('apple', $apple->name);
    }

    public function testCanAccessMembersByName()
    {
        $apple = Fruit::named('apple');
        $this->assertInstanceOf(Fruit::class, $apple);
        $this->assertSame('apple', $apple->name);
        $apple1 = Fruit::maybeNamed('apple');
        $this->assertSame($apple, $apple1);
    }

    public function testCannotDirectlyCreateInstance()
    {
        $this->assertThrows(\Error::class, function () {
            $cherry = new Fruit('cherry', ['type' => 'Orchard']);
        });
    }

    public function testOnlyOneInstanceExistsForEachMember()
    {
        $apple1 = Fruit::apple();
        $apple2 = Fruit::apple();
        $this->assertSame($apple1, $apple2);
        $apple3 = Fruit::named('apple');
        $this->assertSame($apple1, $apple3);
    }

    public function testCanAccessMemberData()
    {
        $this->assertTrue(Fruit::has('apple'));
        $apple = Fruit::named('apple');
        $this->assertTrue(isset($apple->name));
        $this->assertSame('apple', $apple->name);
        $this->assertTrue(isset($apple->type));
        $this->assertSame('Orchard', $apple->type);
        $this->assertFalse(isset($apple->dummy));
        $this->assertNull($apple->dummy);
    }

    public function testCannotSetMemberData()
    {
        $apple = Fruit::named('apple');
        $this->assertThrows(\Exception::class, function () use (&$apple) {
            $apple->type = 'Tree';
        });
        $this->assertSame('Orchard', $apple->type);
    }

    public function testCanAcccessMemberCollections()
    {
        $all_fruit = Fruit::members();
        $this->assertInternalType('array', $all_fruit);
        $this->assertCount(4, $all_fruit);
        $this->assertContains(Fruit::apple(), $all_fruit);
        $this->assertArrayHasKey('pear', $all_fruit);
        $this->assertSame($all_fruit['pear'], Fruit::pear());

        $bramble_fruit = Fruit::subset(function ($fruit) {
            return $fruit->type === 'Bramble';
        });
        $this->assertInternalType('array', $bramble_fruit);
        $this->assertCount(1, $bramble_fruit);
        $this->assertContains(Fruit::raspberry(), $bramble_fruit);
        $this->assertArrayHasKey('raspberry', $bramble_fruit);
        $this->assertSame($bramble_fruit['raspberry'], Fruit::raspberry());
    }

    public function testCanGetRandomFruit()
    {
        $fruit = Fruit::randomMember();
        $this->assertInstanceOf(Fruit::class, $fruit);
    }

    public function testDifferentEnumClassesAreDistinct()
    {
        $fruit = Fruit::named('tomato');
        $veg = Vegetable::named('tomato');
        $this->assertInstanceOf(Fruit::class, $fruit);
        $this->assertNotInstanceOf(Vegetable::class, $fruit);
        $this->assertInstanceOf(Vegetable::class, $veg);
        $this->assertNotInstanceOf(Fruit::class, $veg);
        $this->assertSame($fruit->name, $veg->name);
        $this->assertNotSame($fruit, $veg);
    }

    public function testCannotGetNonMember()
    {
        $this->assertFalse(Fruit::has('dummy'));
        $this->assertThrows(\UnexpectedValueException::class, function () {
            $dummy = Fruit::named('dummy');
        });
        $this->assertNull(Fruit::maybeNamed('dummy'));
        $this->assertFalse(Fruit::has(null));
        $this->assertNull(Fruit::maybeNamed(null));
    }

    public function testNameOfMethodWorks()
    {
        $apple = Fruit::apple();
        $potato = Vegetable::potato();
        $this->assertSame('apple', Fruit::nameOf($apple));
        $this->assertSame('apple', Fruit::nameOf('apple'));
        $this->assertNull(Fruit::nameOf(''));
        $this->assertNull(Fruit::nameOf(null));
        $this->assertNull(Fruit::nameOf($potato));
        $this->assertNull(Fruit::nameOf('potato'));
    }

    public function testSwitchStatementsWork()
    {
        $matched = null;
        $fruit = Fruit::pear();
        switch ($fruit) {
            case Fruit::pear():
                $matched = 'pear';
                break;
            case Fruit::apple():
                $matched = 'apple';
                break;
            default:
                $matched = 'none';
                break;
        }
        $this->assertSame('pear', $matched);
    }

    public function testCanSerialize()
    {
        $apple = Fruit::apple();
        $array = ['name' => 'apple', 'type' => 'Orchard'];
        $this->assertSame('apple', (string) $apple);
        $this->assertSame($array, $apple->toArray());
        $this->assertSame(json_encode($array), json_encode($apple));
    }

    public function testCannotCreateEnumWithEmptyName()
    {
        $this->assertThrows(\Exception::class, function () {
            EmptyStringTestEnum::members();
        });
        $this->assertThrows(\Exception::class, function () {
            NullKeyTestEnum::members();
        });
    }

    public function testIntegerKeyBehaviour()
    {
        // Enum names are always strings, so even though the definition used an
        // integer, we should be able to access it via the string
        $this->assertTrue(IntegerKeyTestEnum::has('0'));
        $zero = IntegerKeyTestEnum::named('0');
        $this->assertInstanceOf(IntegerKeyTestEnum::class, $zero);
        $this->assertSame('Zero', $zero->label);
        $this->assertSame('0', $zero->name);
        // named() and has() only accept a string argument, however non-strict
        // typing in this file means we should be able to pass float or integer
        // zero and it will be converted to string '0'
        $this->assertTrue(IntegerKeyTestEnum::has(0));
        $this->assertSame($zero, IntegerKeyTestEnum::named(0));
        $this->assertTrue(IntegerKeyTestEnum::has(0.0));
        $this->assertSame($zero, IntegerKeyTestEnum::named(0.0));
        // nameOf should work with all representations
        $this->assertSame('0', IntegerKeyTestEnum::nameOf($zero));
        $this->assertSame('0', IntegerKeyTestEnum::nameOf('0'));
        $this->assertSame('0', IntegerKeyTestEnum::nameOf(0));
        $this->assertSame('0', IntegerKeyTestEnum::nameOf(0.0));
        // Make sure that zero isn't matching against other falsey values
        $this->assertFalse(IntegerKeyTestEnum::has(''));
        $this->assertFalse(IntegerKeyTestEnum::has(null));
        $this->assertFalse(IntegerKeyTestEnum::has(false));
        $this->assertNull(IntegerKeyTestEnum::maybeNamed(''));
        $this->assertNull(IntegerKeyTestEnum::maybeNamed(null));
        $this->assertNull(IntegerKeyTestEnum::maybeNamed(false));
    }
}
