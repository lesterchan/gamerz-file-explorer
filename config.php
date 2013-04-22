<?php
/*
+----------------------------------------------------------------+
|																							|
|	GaMerZ File Explorer Version 1.20											|
|	Copyright (c) 2004-2008 Lester "GaMerZ" Chan							|
|																							|
|	File Written By:																	|
|	- Lester "GaMerZ" Chan															|
|	- http://lesterchan.net															|
|																							|
|	File Information:																	|
|	- Configuration File																|
|	- config.php																		|
|																							|
+----------------------------------------------------------------+
*/


### The Absolute Path Of The Folder That You Want To Show It's Contents (Without Trailing Slash)
// Example: /home/user/public_html/files
$root_directory = '';

### The URL To That Folder (Without Trailing Slash)
// Example: http://www.yoursite.com/files
$root_url = '';

### The Absolute Path Of The Folder You Uploaded The Files Of GaMerZ File Explorer (Without Trailing Slash)
// Example: /home/user/public_html/gfe
$gfe_directory = '';

### The URL That Folder (Without Trailing Slash)
// Example: http://www.yoursite.com/gfe
$gfe_url = '';

### Your Site Name
$site_name = 'GaMerZ.File.Explorer';

### Root File Name
$root_filename = 'index.php';

### Enable The Use Of Nice URL (Requires Apache To Have mod_rewrite Enabled)
// true: Enable | false: Disable
$nice_url = true;

### Enable Searching Of Files (Please Disable This If You Are On A High Traffic Site)
// true: Enable | false: Disable
$can_search = true;

### Default Sort Field
// name | size | type | date
$default_sort_by = 'date';

### Default Sort Order
// asc | desc
$default_sort_order = 'desc';
?>