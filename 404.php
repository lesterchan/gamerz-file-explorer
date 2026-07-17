<?php

declare(strict_types=1);

### Start Timer
define('GFE_START', microtime(true));

### Require Config, Setting And Function Files
require 'config.php';
$settings = require 'settings.php';
require 'functions.php';

### Display Error
display_error('404 - File Not Found');
