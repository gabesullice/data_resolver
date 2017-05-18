# Data Resolver

This is a helper module for extracting nested values from Drupal TypedData objects.

You can specify a "property path" like `uid.0.name.0.value` to extract an authors username given a Node as TypedData.

## Usage

```php
$resolver = DataResolver::create($node->getTypedData());

$resolution = $resolver->get('uid.0.name.0.value');

$username = $resolution->resolve(); // $username == 'gabe1991`
```

## Design decisions

The `get()` followed by `resolve()` method calls exist so that property resolution can be optimized in the future.

Eventually, this should work:

```php
$resolver = DataResolver::create($node->getTypedData());

$username = $resolver->get('uid.0.name.0.value');
$first_name = $resolver->get('uid.0.name.0.field_first_name');
$birth_year = $resolver->get('uid.0.name.0.field_birth_year');

list($username, $first_name, $birth_year) = $resolver->resolve($username, $first_name, $birth_year);

// $username == 'gabe1991', $first_name == 'gabe', $birth_year == 1991
```

This pattern would allow the resolver to internally optimize value lookups, minimizing n+1 queries.
