# LocalGov Drupal default roles

Provides the default roles used by LocalGov Drupal:

 * Author (`localgov_author`)
 * Content Designer (`localgov_editor`)

## Adding default permissions

Modules that wish to provide default permissions for these roles can implement
`hook_localgov_roles_default()`. An example can be found in the test module
`tests/localgov_roles_test_one/localgov_roles_test_one.module`.

```
function hook_localgov_roles_default() {
  return [
    // @codingStandardsIgnoreLine
    \Drupal\localgov_roles\RolesHelper::ROLE_CONSTANT => [
      'name of permission',
      'name of another permission',
    ],
  ];
}
```

If you can be certain that this module is included you can import the namespace
with a `use` statement and don't need to use the fully-qualified name, at which
point you can use the alias and drop the codingStandardsIgnoreLine.
