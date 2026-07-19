<?php

declare(strict_types=1);

### The Absolute Path Of The Folder That You Want To Show Its Contents (Without Trailing Slash)
// Defaults to the Docker container root so `docker run` works unedited; change it for a real deployment.
define('GFE_ROOT_DIR', '/var/www/html');

### The URL To That Folder (Without Trailing Slash)
define('GFE_ROOT_URL', 'http://localhost:8080');

### The Absolute Path Of The Folder You Uploaded The Files Of GaMerZ File Explorer (Without Trailing Slash)
define('GFE_DIR', '/var/www/html');

### The URL To That Folder (Without Trailing Slash)
define('GFE_URL', 'http://localhost:8080');

### Your Site Name
define('GFE_SITE_NAME', 'GaMerZ File Explorer');

### Your Site Description
define('GFE_SITE_DESCRIPTION', 'Enables you to browse and search for folders/files on the web just like Windows Explorer.');

### Root File Name
define('GFE_ROOT_FILENAME', 'index.php');

### Enable The Use Of Nice URL (Requires Apache To Have mod_rewrite Enabled)
// true: Enable | false: Disable
define('GFE_NICE_URL', true);

### Enable Searching Of Files (Please Disable This If You Are On A High Traffic Site)
// true: Enable | false: Disable
define('GFE_CAN_SEARCH', true);

### Default Sort Field
// name | size | type | date
define('GFE_DEFAULT_SORT_BY', 'date');

### Default Sort Order
// asc | desc
define('GFE_DEFAULT_SORT_ORDER', 'desc');

### Extra Files/Extensions/Folders To Hide From The Listing
// Example: define('GFE_IGNORE_FOLDERS', ['private', 'staging']);
define('GFE_IGNORE_FILES', []);
define('GFE_IGNORE_EXT', []);
define('GFE_IGNORE_FOLDERS', []);

### Google Analytics Measurement ID (Leave Empty To Disable Tracking)
// Example: 'G-XXXXXXXXXX'
define('GFE_GA_MEASUREMENT_ID', '');
