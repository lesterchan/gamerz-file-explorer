<?php
### The Absolute Path Of The Folder That You Want To Show It's Contents (Without Trailing Slash)
define( 'GFE_ROOT_DIR', '/home/user/public_html/files' );

### The URL To That Folder (Without Trailing Slash)
define( 'GFE_ROOT_URL', 'http://files.yoursite.com' );

### The Absolute Path Of The Folder You Uploaded The Files Of GaMerZ File Explorer (Without Trailing Slash)
define( 'GFE_DIR', '/home/user/public_html/files' );

### The URL That Folder (Without Trailing Slash)
define( 'GFE_URL', 'http://files.yoursite.com' );

### Your Site Name
define( 'GFE_SITE_NAME', 'GaMerZ File Explorer' );

### Your Site Description
define( 'GFE_SITE_DESCRIPTION', 'Enables you to browse and search for folders/files on the web just like Windows Explorer.' );

### Root File Name
define( 'GFE_ROOT_FILENAME', 'index.php' );

### Enable The Use Of Nice URL (Requires Apache To Have mod_rewrite Enabled)
// true: Enable | false: Disable
define( 'GFE_NICE_URL', true );

### Enable Searching Of Files (Please Disable This If You Are On A High Traffic Site)
// true: Enable | false: Disable
define( 'GFE_CAN_SEARCH', true );

### Default Sort Field
// name | size | type | date
define( 'GFE_DEFAULT_SORT_BY', 'date' );

### Default Sort Order
// asc | desc
define( 'GFE_DEFAULT_SORT_ORDER', 'desc' );