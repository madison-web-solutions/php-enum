# PHP Enum

System for defining something similar to Java or Swift Enum types in PHP.

## Example Use

Define an Enum by creating a class which extends from  \MadisonSolutions\Enum, and then define the possible Enum members with the `definitions()` method.

You can define any other methods you like in the class - in the example below, there's an additional `charge()` method for calculating shipping charges.

Note that the constructor method is protected - instances of the Enum class should not be created directly.

```
class ShippingMethod extends \MadisonSolutions\Enum
{
    public static function definitions() : array
    {
        return [
            'collection' => [
                'label' => 'Collection',
                'description' => 'Collect in person from our store.',
                'fixed_charge' => 0,
                'charge_per_kg' => 0,
            ],
            'van' => [
                'label' => 'Local van delivery',
                'description' => 'Local delivery by our own vans.',
                'fixed_charge' => 10,
                'charge_per_kg' => 0,
            ],
            'courier' => [
                'label' => 'Nationwide delivery',
                'description' => 'Nationwide delivery by courier service.',
                'fixed_charge' => 5,
                'charge_per_kg' => 3,
            ],
        ];
    }

    public function charge($weight) {
        return $this->fixed_charge + $weight * $this->charge_per_kg;
    }
}
```

#### Loop over the members

```
foreach (ShippingMethods::members() as $method) {
    echo $method->label . ': ' . $method->charge($weight) . "\n";
}
```

#### Get a subset of the members

```
$freeMethods = ShippingMethods::subset(function($method) {
    return $method->fixed_charge === 0 && $method->charge_per_kg === 0;
});
```

#### Access an instance

You should not directly create instances of any Enum class - instead use static methods on the class.

```
$van = ShippingMethods::van();
// or
$van = ShippingMethod::named('van');
```

#### Create an instance from untrusted input

```
$method = ShippingMethod::maybeNamed($_POST['shipping-method']);
if (! $method) {
    die("No such shipping method {$_POST['shipping-method']}");
}
```

#### Compare instances

```
if ($method === ShippingMethods::collection()) {
    echo "You are collecting in person.";
}
// or
if ($method->name === 'collection') {
    echo "You are collecting in person.";
}
// or
switch ($method) {
    case ShippingMethods::collection():
        echo "You are collecting in person.";
        break;
}
```

#### Load from / store to database

```
// store
$stringValue = ShippingMethods::nameOf($method);

// save $stringValue in database

// load
$method = ShippingMethod::maybeNamed($stringValue);
```

#### Type Hint

```
function validateShippingMethod($order, ShippingMethod $method) {
    if ($method === ShippingMethods::van()) {
        if (! $order->address->isLocal()) {
            die("Van delivery is only available for local addresses");
        }
    }
}
```
