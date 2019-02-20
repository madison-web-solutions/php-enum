<?php
declare(strict_types=1);

namespace MadisonSolutions\Enum;

use JsonSerializable;

/**
 * The Abstract Enum class
 *
 * It is intended that each different Enum created will be a class which
 * extends from this Enum class.  Child classes must implement the
 * definitions() method to define the members of the Enum.
 */
abstract class Enum implements JsonSerializable
{
    /**
     * Get the definitions for this Enum
     *
     * Should return an array where keys correspond to the 'name' of each
     * member, and values should be arrays containing the 'data' of each member
     */
    abstract public static function definitions() : array;

    /**
     * Cache loaded Enums for performance
     *
     * @var array
     */
    private static $cache = [];


    /**
     * Get all the members of this enum in an array
     *
     * @return array The members of the enum, array keys are the 'name' of each
     *               member, array values are Enum object instances
     */
    public static function members() : array
    {
        $called_class = get_called_class();
        if (! isset(Enum::$cache[$called_class])) {
            Enum::$cache[$called_class] = [];
            foreach (static::definitions() as $name => $data) {
                if ($name === '') {
                    throw new \Exception("Cannot create Enum with name = ''");
                }
                Enum::$cache[$called_class][$name] = new static((string) $name, $data);
            }
        }
        return Enum::$cache[$called_class];
    }

    /**
     * Get a subset of the members of this Enum
     *
     * Get a subset of the members of this Enum, filtered with a user-defined
     * callback function.  The returned Enum members are those for which the
     * filter function returns a truthy value.
     *
     * @param callable $filter Filter function to be applied to each Enum member
     * @return array The subset of the Enum members
     */
    public static function subset(callable $filter) : array
    {
        return array_filter(static::members(), $filter);
    }

    /**
     * Determine whether this Enum class has a member with the given name
     *
     * @param ?string $name The name to look for
     * @return bool True if the member exists, False otherwise
     */
    public static function has(?string $name) : bool
    {
        return array_key_exists($name, static::members());
    }

    /**
     * Get the member with the given name
     *
     * @param string $name The name to look for
     * @throws UnexpectedValueException If the Enum class does not contain a
     *         member with the specified name
     * @return Enum The Enum member
     */
    public static function named(string $name)
    {
        $members = static::members();
        if (!array_key_exists($name, $members)) {
            throw new \UnexpectedValueException("Enum ".get_called_class()." has no member $name");
        }
        return $members[$name];
    }

    /**
     * Get the member with the given name, if it exists
     *
     * This is similar to named()  except if the member doesn't exists, no
     * Exception is thrown, instead null is returned.
     *
     * @param ?string $name The name to look for
     * @return ?Enum The Enum member, or null
     */
    public static function maybeNamed(?string $name)
    {
        return @static::members()[$name];
    }

    /**
     * Get the name of an Enum member
     *
     * If passed a member of this Enum class, returns its name. If passed a
     * name of one of the members of this Enum class, it is returned unchanged.
     * Otherwise null is returned.
     *
     * This function can be used to 'normalize' an Enum value ready to be
     * stored in a database or similar. Any acceptable representation of one
     * of the Enum members can be supplied, and the return value will be the
     * member name.
     *
     * @param mixed $val Instance of Enum class, or name of a member
     * @return ?string The member name, or null if this Enum has no such member
     */
    public static function nameOf($val)
    {
        if ($val instanceof static) {
            return $val->name;
        }
        if (is_int($val) || is_float($val)) {
            $val = (string) $val;
        }
        if (is_string($val) && static::has($val)) {
            return $val;
        }
        return null;
    }

    /**
     * Unrecognised static calls are assumed to be requests for a specific
     * member.  For example if the Enum called 'Fruit' has a member named
     * 'apple', then the member can be retrieved with Fruit::apple().
     */
    public static function __callStatic($name, $args)
    {
        return static::named($name);
    }

    /**
     * Fetch a random member from the Enum
     *
     * @return Enum The randomly chosen member
     */
    public static function randomMember()
    {
        $members = array_values(static::members());
        return $members[rand(0, count($members) - 1)];
    }

    /**
     * The unique (within the Enum class) name of the member
     *
     * @var string
     */
    protected $name;

    /**
     * Data associated with the member
     *
     * @var array
     */
    protected $data;

    /**
     * Create an instance of an Enum class
     *
     * @param string $name The name of the member
     * @param array $data Arbitrary data associated with the member
     */
    protected function __construct(string $name, array $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * Unrecognised properties are assumed to be found in the member's internal
     * associated data.
     */
    public function __get($key)
    {
        if ($key == 'name') {
            return $this->name;
        }
        return @$this->data[$key];
    }

    /**
     * Data properties cannot be set on instances - must be in definitions
     */
    public function __set($key, $value)
    {
        throw new \Exception("Cannot set data on Enum instance");
    }

    /**
     * Unrecognised properties are assumed to be found in the member's internal
     * associated data.
     */
    public function __isset($key)
    {
        if ($key == 'name') {
            return true;
        }
        return array_key_exists($key, $this->data);
    }

    /**
     * The member name is used as the string representation.
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Convert the instance into an array
     *
     * @return array Array representation of the instance
     */
    public function toArray() : array
    {
        return [
            'name' => $this->name,
        ] + $this->data;
    }

    /**
     * Prepare the instance for JSON serialization
     *
     * @return array Array representation of the instance
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
