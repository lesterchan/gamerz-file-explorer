<?php

declare(strict_types=1);

define('GFE_START', microtime(true));

require 'config.php';
$settings = require 'settings.php';
require 'functions.php';

display_error('404 - File Not Found');
