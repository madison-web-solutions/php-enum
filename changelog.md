# Changelog

## [2.0.0] - 2019-05-30

### Added

 - This changelog.
 - Static `names()` method for getting all the member names of an Enum class.
 - Support for PHP's native serialize and unserialize and clone functionality.

 ### Breaking Changes

  - In version 1 we attempted to ensure that there was only ever 1 instance of
    each member of an Enum class in existence. You could use the `===` operator
    to compare Enum objects, eg `($fruit === Fruit::apple())`. Calling
    `Fruit::apple()` multiple times would always return the exact same object.
    However PHP's `serialize` and `clone` both provided ways to break this
    behaviour and allow multiple instances of the same Enum. So in version 2
    we've abandoned this concept. Calling `Fruit::apple()` twice will result in
    2 distinct instances of the apple Enum. The main consequence of this is that
    you'll now need to use the `==` operator to check for equality of Enum
    objects. Note that instances are still very lightweight - internally the
    only data they have is their names - other data is obtained by reference
    to a central cache.
