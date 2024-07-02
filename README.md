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
