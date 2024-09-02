# LocalGov Drupal core functionality

LocalGov Drupal Core module, for helper functions and core dependencies.

## Default blocks
This module contains a mechanism that will place default blocks into your site's
active theme when other localgov modules are installed. This is intended to
reduce the work that site owners need to do when installing new features 
provided by localgov modules. If you don't want this to happen, you can turn it 
off by adding this to your site's settings.php file:

```php
$config['localgov_core.settings']['install_default_blocks'] = FALSE;
```

If you're a module maintainer and would like to use this feature, create a file 
in your module at config/localgov/block.description.yml. The description part of
the filename can be anything you like.

In that file, place the exported config yaml for a single block, and remove the 
following keys:
* uuid
* id
* theme

The default block installer will read the file, and create an instance of the 
block in the current active theme, along with localgov_base and 
localgov_scarfolk, if they exist and are enabled. An id for each instance will
be generated from the combination of theme and block plugin name.

Using this feature lets your blocks appear automatically in the right place in
existing localgov sites with custom themes. It also saves you having to manage
multiple block config files for localgov_base and localgov_scarfolk.
