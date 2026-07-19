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
* `GFE_URL` - The URL to GaMerZ File Explorer (without trailing slash).
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
* `GFE_IGNORE_FILES` / `GFE_IGNORE_EXT` / `GFE_IGNORE_FOLDERS` - Optional per-deployment additions to the ignore lists in `settings.php`. Whatever you list here is merged into (never replaces) the built-in baseline, so `settings.php` can stay identical across every deployment while each site hides its own extra files, extensions and folders. Leave them as empty arrays to add nothing.
 * Example: `define('GFE_IGNORE_FOLDERS', ['private', 'staging']);`
 * Note: Hiding a file from the listing is not access control — deny it at the web-server level too (see the Nginx block below) if it must not be served.

#### To Enable Search Engine Friendly URLs
If you are using Apache, upload `.htaccess` to the folder where you uploaded GaMerZ File Explorer.

If you are using Nginx, paste the below configuration in your nginx.conf file.
```nginx
# Deny web access to tooling / metadata files. They are hidden from the listing,
# but that alone does not stop Nginx from serving them. Denied by exact name so
# that browsable content files (.json/.md/.xml/...) are unaffected. The CLI-only
# tests directory is blocked wholesale.
location ~ /\.(?!well-known\/) { deny all; }
location ~* ^/(?:composer\.(?:json|lock)|phpstan\.neon\.dist|phpcs\.xml\.dist|phpunit\.xml\.dist|AGENTS\.md|CLAUDE\.md|Dockerfile)$ { deny all; }
location ^~ /tests/ { deny all; }

location / {
    try_files $uri $uri/ /index.php;
}
rewrite ^/browse/(.+[^/])/?$ /index.php?dir=$1 last;
rewrite ^/viewing/(.+[^/])/?$ /view.php?file=$1 last;
rewrite ^/download/(.+[^/])/?$ /view.php?file=$1&dl=1 last;
```

#### Upload These Files To The Directory You Serve At `GFE_URL`
* Folder: resources
* File: .htaccess (might be hidden)
* File: 404.php
* File: config.php
* File: functions.php
* File: index.php
* File: search.php
* File: settings.php
* File: view.php

## Run with Docker

The included `Dockerfile` builds a self-contained nginx + PHP-FPM stack (with the nice-URL
rewrites, static denies, and the CSP the inline PDF/media embeds rely on) — handy for trying
the app locally without configuring a web server yourself:

```bash
docker build -t gfe .
docker run --rm -p 8080:80 -v "$PWD":/var/www/html gfe
```

Then browse [http://localhost:8080](http://localhost:8080) — the shipped `config.php` already
points `GFE_ROOT_DIR` at the container root (`/var/www/html`) and the URLs at
`http://localhost:8080`, so no configuration is needed to try it. The bind-mount serves your
working tree live, so you can edit and refresh without rebuilding. For a real deployment, edit
those `config.php` values to your own path and URL.

## Changelog

### Version 3.2.0 (19-07-2026)
* IMPROVED: Interface text now uses sentence case consistently — error messages, the search form's labels and options, page titles, and the footer — instead of a mix of Title Case, and the search/filter placeholders share a single ellipsis
* FIXED: File sizes under 1 KB now display as `B` (bytes) instead of a lowercase `b`
* DEV: Removed the unused `GFE_DIR` config constant — the app locates its own files via relative includes, so only `GFE_ROOT_DIR` (content path) and `GFE_URL` (app URL) remain
* DEV: Added a `Dockerfile` that builds a self-contained nginx + PHP-FPM stack for running the app locally (`docker run -p 8080:80 -v "$PWD":/var/www/html gfe`); hidden from the listing and denied at the web-server level
* NEW: Per-deployment ignore lists — define `GFE_IGNORE_FILES`, `GFE_IGNORE_EXT` and/or `GFE_IGNORE_FOLDERS` in `config.php` to hide extra files/extensions/folders. They are merged into (never replace) the `settings.php` baseline, so `settings.php` can stay identical across deployments
* IMPROVED: A file that can't be previewed (archives, binaries, and other non-viewable types) now opens a viewing page with a "can't be previewed" message and a Download button, instead of triggering an immediate download — so viewing is consistent across every file type and downloading is always a deliberate action
* IMPROVED: The viewing page's Previous/Next controls now show as disabled placeholders when a file is alone in its folder, matching the first/last-file behaviour so the footer looks the same regardless of folder size
* NEW: Filter the current folder as you type — on a listing the top-bar search box narrows the current folder's rows instantly, while pressing Enter runs a full search across every folder
* NEW: Copy the current page's link to the clipboard with one click from the path bar
* NEW: Step between files on the viewing page with the Left/Right arrow keys, mirroring the Previous/Next buttons
* NEW: The text/source viewer now shows line numbers and a one-click Copy button for the file's contents
* IMPROVED: Search results highlight the matched term in the file name (and in the folder path when matching on path)
* SECURITY: File search no longer descends into ignored folders (`vendor`, `tests`, `build`, etc.), so files nested inside them can no longer surface in search results
* IMPROVED: Moved search into the top bar as a compact input-and-button group beside a tighter theme switch, replacing the bottom-of-page search form and its separate "Advanced Search" link
* FIXED: A listing or search table wider than the viewport can be scrolled sideways again on small screens — the rounded card was clipping the horizontal overflow it should have let scroll

### Version 3.1.0 (18-07-2026)
* NEW: Redesigned the interface as a modern file explorer with a light and dark design system tuned for correct contrast
* NEW: Added a three-way colour theme switch (auto/light/dark) that remembers your choice
* NEW: Colour-coded file-type icons, a home icon in the breadcrumb, and a clickable current-path permalink aligned with the breadcrumb
* NEW: Entire listing rows are now clickable, not just the file/folder name
* NEW: View PDFs, videos, and audio inline (browser-playable formats), the same way images are shown
* NEW: Step between files in a folder straight from the viewing page with Previous/Next controls that follow the listing's sort order (Previous left, Next right, and disabled at the first/last file)
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
* IMPROVED: Sort order now travels in the URL query string (`?by=&order=`) instead of the path, and is dropped entirely at the default — so `/browse/<path>/` and `/viewing/<file>` stay clean (the old `/sortby/.../sortorder/.../` paths are retired)
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
