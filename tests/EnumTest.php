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
}
