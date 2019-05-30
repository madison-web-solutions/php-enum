<?php
declare(strict_types=1);

namespace MadisonSolutions\Enum;

use JsonSerializable;
use Serializable;
use UnexpectedValueException;

/**
 * The Abstract Enum class
 *
 * It is intended that each different Enum created will be a class which
 * extends from this Enum class.  Child classes must implement the
 * definitions() method to define the members of the Enum.
 */
abstract class Enum implements JsonSerializable, Serializable
{
    /**
     * Get the definitions for this Enum
     *
     * Should return an array where keys correspond to the 'name' of each
     * member, and values should be arrays containing the 'data' of each member
     */
    abstract public static function definitions() : array;

    /**
     * Cache loaded Enum data for performance
     *
     * @var array
     */
    private static $cache = [];

    /**
     * Initialize the current (static) Enum class
     *
     * Calls the static::definitions() function to load the definitions and
     * saves them to a central cache for efficient access.
     *
     * This must be called before any attempt to access members or member data.
     *
     * @throws UnexpectedValueException If the Enum class tries to define a
     *         member with an empty name (array key in the definition is '')
     */
    final private static function init()
    {
        if (! isset(Enum::$cache[static::class])) {
            $defns = static::definitions();
            if (array_key_exists('', $defns)) {
                throw new UnexpectedValueException("Cannot define Enum with name = '' in class " . static::class);
            }
            Enum::$cache[static::class] = $defns;
        }
    }

    /**
     * Get the names of members of this Enum in an array
     *
     * @return array The names of the members of the enum (strings)
     */
    public static function names() : array
    {
        static::init();
        return array_keys(Enum::$cache[static::class]);
    }

    /**
     * Get all the members of this enum in an array
     *
     * @return array The members of the enum, array keys are the 'name' of each
     *               member, array values are Enum object instances
     */
    public static function members() : array
    {
        $members = [];
        foreach (static::names() as $name) {
            $members[$name] = new static($name);
        }
        return $members;
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
        static::init();
        return array_key_exists($name, Enum::$cache[static::class]);
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
        $instance = static::maybeNamed($name);
        if (! $instance) {
            throw new UnexpectedValueException("Enum " . static::class . " has no member $name");
        }
        return $instance;
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
        return static::has($name) ? new static($name) : null;
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
        $names = static::names();
        return new static($names[rand(0, count($names) - 1)]);
    }

    /**
     * The unique (within the Enum class) name of the member
     *
     * @var string
     */
    protected $name;

    /**
     * Create an instance of an Enum class
     *
     * @param string $name The name of the member
     */
    final private function __construct(string $name)
    {
        $this->name = $name;
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
        return Enum::$cache[get_class($this)][$this->name][$key] ?? null;
    }

    /**
     * Data properties cannot be set on instances - must be in definitions
     */
    public function __set($key, $value)
    {
        throw new \Exception("Cannot set data on Enum instance");
    }

    /**
     * Data properties cannot be unset on instances - must be in definitions
     */
    public function __unset($key)
    {
        throw new \Exception("Cannot remove data from Enum instance");
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
        return array_key_exists($key, Enum::$cache[get_class($this)][$this->name]);
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
        ] + Enum::$cache[get_class($this)][$this->name];
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

    public function serialize()
    {
        return $this->name;
    }

    public function unserialize($name)
    {
        $instance = static::named($name);
        $this->name = $instance->name;
    }
}
