# GaMerZ File Explorer
Enables you to browse a folder on the web like Windows Explorer. It has the ability to search for folders and files too.

## Requirements
* PHP 8.1 or newer

## Installation

#### Config
* `GFE_ROOT_DIR` - The absolute path of the folder that you want to show its contents (without trailing slash).
 * Example: `/home/user/public_html/files`
* `GFE_ROOT_URL` - The URL to that folder (without trailing slash).
 * Example: `http://files.yoursite.com`
* `GFE_DIR` - The absolute path of the folder you uploaded the files of GaMerZ File Explorer (without trailing slash).
 * Note: You can upload GaMerZ File Explorer into the same folder as the contents that you want to show.
 * Example: `/home/user/public_html/files`
* `GFE_URL` - The URL to that folder (without trailing slash).
 * Note: You can upload GaMerZ File Explorer into the same folder as the contents that you want to show.
 * Example: `http://files.yoursite.com`
* `GFE_SITE_NAME` - Your site name
* `GFE_SITE_DESCRIPTION` - Your site description
* `GFE_ROOT_FILENAME` - Web Server directory index. Normally you do not need to change this.
* `GFE_NICE_URL` - Search engine friendly URLs. See below.
 * Example Nice URL: `http://files.yoursite.com/browse/folder1/`.
 * Example Normal URL: `http://files.yoursite.com/index.php?dir=folder1`.
* `GFE_CAN_SEARCH` - By setting to true, you allow users to search for files in GaMerZ File Explorer.
* `GFE_DEFAULT_SORT_BY` - Default sort field.
 * Values can be `name`, `size`, `type` or `date`.
* `GFE_DEFAULT_SORT_ORDER` - Default sort order.
 * Values can be `asc` or `desc`.

#### To Enable Search Engine Friendly URLs
If you are using Apache, upload `.htaccess` to the folder where you uploaded GaMerZ File Explorer.

If you are using Nginx, paste the below configuration in your nginx.conf file.
```nginx
# Deny web access to tooling / metadata files. They are hidden from the listing,
# but that alone does not stop Nginx from serving them. Denied by exact name so
# that browsable content files (.json/.md/.xml/...) are unaffected. The CLI-only
# tests directory is blocked wholesale.
location ~ /\.(?!well-known\/) { deny all; }
location ~* ^/(?:composer\.(?:json|lock)|phpstan\.neon\.dist|phpcs\.xml\.dist|phpunit\.xml\.dist|AGENTS\.md|CLAUDE\.md)$ { deny all; }
location ^~ /tests/ { deny all; }

location / {
    try_files $uri $uri/ /index.php;
}
rewrite ^/sortby/(.+[^/])/sortorder/(.+[^/])/?$ /index.php?by=$1&order=$2 last;
rewrite ^/browse/(.+[^/])/sortby/(.+[^/])/sortorder/(.+[^/])/?$ /index.php?dir=$1&by=$2&order=$3 last;
rewrite ^/browse/(.+[^/])/?$ /index.php?dir=$1 last;
rewrite ^/viewing/(.+[^/])/?$ /view.php?file=$1 last;
rewrite ^/download/(.+[^/])/?$ /view.php?file=$1&dl=1 last;
```

#### Upload These Files To The Directory You Specify In `GFE_DIR`
* Folder: resources
* File: .htaccess (might be hidden)
* File: 404.php
* File: config.php
* File: functions.php
* File: index.php
* File: search.php
* File: settings.php
* File: view.php

## Changelog

### Version 3.1.0 (18-07-2026)
* NEW: Redesigned the interface as a modern file explorer with a light and dark design system tuned for correct contrast
* NEW: Added a three-way colour theme switch (auto/light/dark) that remembers your choice
* NEW: Colour-coded file-type icons, a home icon in the breadcrumb, and a clickable current-path permalink aligned with the breadcrumb
* NEW: Entire listing rows are now clickable, not just the file/folder name
* NEW: View PDFs, videos, and audio inline (browser-playable formats), the same way images are shown
* NEW: The active sort column and its direction are highlighted
* NEW: Added a scalable SVG app icon (`resources/icon.svg`) and refreshed the PNG icon and favicon
* NEW: Show a short EXIF summary (camera, model, capture date) when viewing JPEG/TIFF images
* IMPROVED: Unified the listing typography with aligned figures, and hide the type column on small screens
* IMPROVED: Tightened spacing and border-radius consistency, and added clear keyboard focus styles
* IMPROVED: Search can match the full folder path, not just the file name, and each result shows its containing folder
* IMPROVED: Folder sizes now exclude ignored files and folders (matching the listing) and are computed in a single directory walk instead of two
* IMPROVED: Directory walks use one cached stat per entry (`FilesystemIterator`), search filters during the walk instead of building the whole file list first, and the text viewer reads each file once — fewer syscalls and less memory per uncached render
* IMPROVED: Accessibility — a `<main>` landmark, a keyboard skip-to-content link, `aria-sort` on the active sort column, and `aria-hidden` on decorative icons
* IMPROVED: Emit a canonical `<link>` (and align `og:url`/`twitter:url`) to the nice-URL permalink
* IMPROVED: Viewing an image now uses that image as the social share preview (`og:image` + a large Twitter card) instead of the app icon
* SECURITY: Reject a bare `..` path segment so a listing URL (`?dir=..`) can no longer show the parent of the web root
* SECURITY: Refuse to list a folder nested inside an ignored folder (e.g. `?dir=vendor/composer`), not just the ignored folder itself
* SECURITY: `view.php` now refuses to view or download a file nested inside an ignored folder (e.g. `?file=vendor/autoload.php`), matching the listing filter so hidden folders can't be read through the viewer
* SECURITY: Reject a null byte in the requested `dir`/`file` path so it can no longer reach a filesystem call
* SECURITY: `view.php` confirms the resolved file path stays inside the root (`realpath` containment), so a symlink pointing outside the served folder can't be viewed or downloaded
* FIX: The search "Search In" folder filter now matches on a folder boundary instead of any substring (so `Docs` no longer matches `MyDocs`)
* SECURITY: Downloads now send an RFC 5987 `Content-Disposition`, so filenames with quotes, control characters, or non-ASCII characters are delivered safely
* DEV: Moved the styles and scripts into `resources/style.css` and `resources/script.js`
* DEV: Added a `README`, an MIT `LICENSE`, and an `AGENTS.md` that points to `CLAUDE.md`
* DEV: Added a `robots.txt` that keeps crawlers off sort permutations, downloads, and search (both nice-URL and query forms)
* DEV: Tidied the `settings.php` ignore list to hide only files that exist — covering the `README`/`LICENSE`/`AGENTS.md` and other metadata
* DEV: Serve the favicon from `resources/favicon.ico` (linked in the template) and dropped the redundant root copy
* DEV: Pin the `exif` extension in CI to cover the EXIF summary; the runtime stays dependency-free and degrades gracefully without it
* DEV: Deduplicated shared markup and checks into helpers (`esc()`, `is_safe_path()`, `file_row()`, `media_embed()`, `sort_field()`, `sort_direction()`)
* DEV: Disabled the line-length lint sniff for the idiomatic long HTML/attribute lines

### Version 3.0.0 (18-07-2026)
* NEW: Requires PHP 8.1 or newer
* NEW: Upgraded the frontend to Bootstrap 5.3 and Font Awesome 6, added Subresource Integrity to all CDN assets, and dropped the jQuery dependency
* NEW: Recognises many more file types (webp, avif, svg, mp4, webm, mkv, json, md, yml, and more)
* NEW: Respects the visitor's light/dark colour scheme preference
* IMPROVED: Rewrote the code with `declare(strict_types=1)`, full type declarations, and removed all shared global state in favour of passed parameters
* IMPROVED: Sortable column headers are now real links instead of inline JavaScript
* FIX: Correctly resolve files and folders whose names contain spaces or other characters that Nice URLs encode
* SECURITY: Added .htaccess/Nginx rules and a CLI-only guard so tooling and metadata files (composer.json, *.dist, CLAUDE.md, tests) cannot be served
* DEV: Added PHPStan (level 6), PHP_CodeSniffer (PSR-12), and a PHPUnit suite with 100% coverage, run in GitHub Actions on PHP 8.1–8.4

### Version 2.1.0 (17-07-2026)
* SECURITY: Fixed reflected XSS via the `search` parameter and other output sinks by escaping all output with `htmlspecialchars()`
* FIX: Fixed deprecated `case` statement terminated with a semicolon in functions.php

### Version 2.0.0 Beta 2 (11-10-2018)
* NEW: Logo by @mirzazulfan
* NEW: Added .editorconfig and tidy up code

### Version 2.0.0 Beta 1 (21-09-2015)
* NEW: New design using Bootstrap with Font Awesome

#### Version 1.2.0 (01-02-2006)
* NEW: XHTML 1.1 Compatible Now

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
* NEW: Now Support Nice URL Via Apache's mod_rewrite. User Can Choose To Enable/Disable Nice URL Option It In config.php
* NEW: Rewrote The Codes That Displays The Files And Folders, Now There Will Be No '/' In Front Of Any Folders Or Files
* NEW: settings.php Will Now Contain Most Of The Default Settings, So For Future Versions, You Do Not Need To Overwrite config.php Anymore
* NEW: Ability To Sort By Type
* NEW: Proper HTML Error Page
* NEW: `title=""` Being Added To Almost Every `<td>`
* NEW: favicon.ico Added
* NEW: .mdb|.mov|.msi|.ra|.rm|.tif|.wma|.wmv Extensions Added
* FIXED: Extension Not Showing When It Is In Upper Case
* FIXED: Files Listed In $ignore_files And $ignore_folders Will Now Be More Specified. If Ignore File Is 'test/test.htm', Only 'test.htm' In 'test' Folder Will Be Ignored Rather Than 'test.htm' Throughout All The Folders
* FIXED: No More Use Of PHP Short Tag
* FIXED: Unknown Or Undefined File Extension, The File Extension Image Will Now Be unknown.gif
* FIXED: Invalid Checking Of Directory in view.php
* FIXED: Grammar Mistakes For Singular And Plural
* FIXED: No Extension Given If There Is Spaces In The File Name That Is Being Downloaded

#### Version 1.00 (09-09-2005)
* NEW: Public Release Of GaMerZ File Explorer
