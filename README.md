GaMerZ File Explorer
====================

Enables you to browse a folder on the web like Windows Explorer. It has the ability to search for folders and files too.

## Installation

#### Config
* `$root_directory = '';` - The Absolute Path Of The Folder That You Want To Show It's Contents (Without Trailing Slash). Example: `/home/user/public_html/files`
* `$root_url = '';` - The URL To That Folder (Without Trailing Slash). Example: `http://yoursite.com/files`
* `$gfe_directory = '';` - The Absolute Path Of The Folder You Uploaded The Files Of GaMerZ File Explorer (Without Trailing Slash). Note: You Can Upload GaMerZ File Explorer Into The Same Folder As The Contents That You Want To Show. Example: `/home/user/public_html/gfe`
* `$gfe_url = '';` - The URL That Folder (Without Trailing Slash). Note: You Can Upload GaMerZ File Explorer Into The Same Folder As The Contents That You Want To Show. Example: `$gfe_url = 'http://yoursite.com/gfe';`
* `$site_name = 'GaMerZ File Explorer';` - Your Site Name
* `$root_filename = 'index.php';` - Webserver Directory Index. Note: Normally You Do Not Need To Change This.
* `$nice_url = false;` - Example Nice URL: `http://yoursite.com/gfe/browse/folder1/`. Example Normal URL: `http://yoursite.com/gfe/index.php?dir=folder1`.
 * If You Want To Use 'Nice URL', Set It To 'true' Instead Of 'false' And Do The Following:
  * Upload '.htaccess' To The Folder Where You Uploaded GaMerZ File Explorer.
  * Open up '.htaccess' And Replace All References Of `/files/` To The Folder Path After Your Domain Name Of `$gfe_url`.
  * For Example: `$gfe_url = 'http://yoursite.com/gfe';`
  * Your Should Replace `/files/` To `/gfe/`
* `$can_search = true;` - By setting This To 'true', You Allow Users To Search For Files In GaMerZ File Explorer. It Is Best To Set It To 'false' If You Are On A High Traffic Site.
* `$default_sort_by = 'date';` - Default Sort Field. Values Can Be `name`, `size`, `type` or `date`.
* `$default_sort_order = 'desc';` - Default Sort Order. Values Can Be `asc` or `desc`.

#### Upload These Files To The Directory You Specify In `$gfe_directory`
* Folder: resources
* File: .htaccess (might be hidden)
* File: config.php
* File: functions.php
* File: index.php
* File: search.php
* File: settings.php
* File: view.php

## Changelog

#### Version 1.20 (01-02-2006)
* NEW: XHTML 1.1 Comptible Now

#### Version 1.20 Beta 3 (24-10-2006)
* FIXED: Error Displaying File Size More Than 2GB

#### Version 1.20 Beta 2 (25-03-2005)
* NEW: Added Default Sort Options
* NEW: HTML View Using IFRAME
* NEW: Added HTML View/HTML Source Option For HTML Files
* NEW: Added A JavaScript File Called javasript.js
* FIXED: Moved <style></style> Before </head>
* FIXED: Changed content-type To utf-8

#### Version 1.20 Beta (01-02-2005)
* NEW: Search Engine Now Implemented
* NEW: Added GB To The File Size
* NEW: .w3x Extensions Added
* FIXED: File Type Will Be 'Unknown' If File Type Is Not Registered In settings.php Instead Of Blank

#### Version 1.10 (01-12-2005)
* NEW: Now Support Nice URL Via Apache's mod_rewrite. User Can Choose To Enable/Disble Nice URL Option It In config.php
* NEW: Rewrote The Codes That Displays The Files And Folders, Now There Will Be No '/' In Front Of Any Folders Or Files
* NEW: settings.php Will Now Contain Most Of The Default Settings, So For Future Versions, You Do Not Need To Overwrite config.php Anymore
* NEW: Ability To Sort By Type
* NEW: Proper HTML Error Page
* NEW: title="" Being Added To Almost Every <td>
* NEW: favicon.ico Added
* NEW: .mdb|.mov|.msi|.ra|.rm|.tif|.wma|.wmv Extensions Added
* FIXED: Extension Not Showing When It Is In Upper Case
* FIXED: Files Listed In $ignore_files And $ignore_folders Will Now Be More Specified. If Ignore File Is 'test/test.htm', Only 'test.htm' In 'test' Folder Will Be Ignored Rather Than 'test.htm' Throughout All The Folders
* FIXED: No More Use Of PHP Short Tag
* FIXED: Unknown Or Undefined File Extension, The File Extension Image Will Now Be unknown.gif
* FIXED: Invalid Checking Of Directory in view.php
* FIXED: Grammer Mistakes For Singular And Pural
* FIXED: No Extension Given If There Is Spaces In The File Name That Is Being Downloaded

#### Version 1.00 (09-09-2005)
* NEW: Public Release Of GaMerZ File Explorer