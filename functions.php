<?php

declare(strict_types=1);

/**
 * GaMerZ File Explorer — shared functions.
 *
 * The GfeSettings and GfeEntry array-shape type aliases are declared globally
 * in phpstan.neon.dist so every file can reference them.
 */

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_size(int|float $size): string
{
    if ($size / 1073741824 > 1) {
        return round($size / 1073741824, 1) . 'GB';
    }
    if ($size / 1048576 > 1) {
        return round($size / 1048576, 1) . 'MB';
    }
    if ($size / 1024 > 1) {
        return round($size / 1024, 1) . 'KB';
    }
    return round($size, 1) . 'b';
}

function content_disposition(string $filename): string
{
    $filename = basename($filename);
    // ASCII fallback for legacy clients: collapse whitespace, then replace any byte
    // that isn't printable ASCII or that would break out of the quoted string.
    $fallback = preg_replace('/\s+/', '_', $filename) ?? $filename;
    $fallback = preg_replace('/[^\x20-\x7E]/', '_', $fallback) ?? $fallback;
    $fallback = str_replace(['\\', '"'], '_', $fallback);
    // RFC 5987 copy preserves the real (possibly non-ASCII) name for modern clients.
    return 'attachment; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($filename);
}

function is_safe_path(string $path): bool
{
    $segments = explode('/', $path);
    return ! in_array('.', $segments, true)
        && ! in_array('..', $segments, true)
        && ! str_contains($path, '//')
        && ! str_contains($path, "\0");
}

/**
 * One SplFileInfo per entry caches its stat, so isDir()/isFile()/getSize()/getMTime()
 * on the same entry share a single syscall instead of one per call.
 */
function dir_iterator(string $path): ?FilesystemIterator
{
    try {
        return new FilesystemIterator($path);
    } catch (\Throwable) {
        return null;
    }
}

/**
 * Mirrors the listing's ignore rules so the reported size matches what is shown:
 * ignored files/extensions are skipped and ignored folders are not descended into.
 *
 * @param GfeSettings $settings
 */
function dir_size(string $dir, array $settings): int
{
    $iterator = dir_iterator($dir);
    if ($iterator === null) {
        return 0;
    }
    $total = 0;
    foreach ($iterator as $info) {
        $filename = $info->getFilename();
        if ($filename === '.git' || $filename === '.svn') {
            continue;
        }
        $full = $info->getPathname();
        $relative = substr($full, strlen(GFE_ROOT_DIR) + 1);
        if ($info->isDir()) {
            if (! in_array($relative, $settings['ignore_folders'], true)) {
                $total += dir_size($full, $settings);
            }
            continue;
        }
        if (! $info->isFile()) {
            continue;
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (
            in_array($relative, $settings['ignore_files'], true)
            || in_array($ext, $settings['ignore_ext'], true)
        ) {
            continue;
        }
        $total += $info->getSize();
    }
    return $total;
}

/**
 * An optional filter is applied during the walk, so search never materialises the
 * whole tree just to discard most of it.
 *
 * @param  GfeSettings                     $settings
 * @param  (callable(GfeEntry): bool)|null $filter   Only files passing the filter are kept.
 * @return list<GfeEntry>
 */
function list_files(string $path, array $settings, ?callable $filter = null): array
{
    $iterator = dir_iterator($path);
    if ($iterator === null) {
        return [];
    }
    $files = [];
    foreach ($iterator as $info) {
        $filename = $info->getFilename();
        if ($filename === '.git' || $filename === '.svn') {
            continue;
        }
        $full = $info->getPathname();
        $relative = substr($full, strlen(GFE_ROOT_DIR) + 1);
        if ($info->isDir()) {
            $files = array_merge($files, list_files($full, $settings, $filter));
            continue;
        }
        if (! $info->isFile()) {
            continue;
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $folder = substr($relative, 0, -(strlen($filename) + 1));
        if (
            in_array($ext, $settings['ignore_ext'], true)
            || in_array($relative, $settings['ignore_files'], true)
            || in_array($folder, $settings['ignore_folders'], true)
            || empty($settings['extensions'][$ext][0])
        ) {
            continue;
        }
        $entry = [
            'name' => $filename,
            'ext' => $ext,
            'path' => $relative,
            'type' => $settings['extensions'][$ext][0],
            'size' => $info->getSize(),
            'date' => $info->getMTime(),
        ];
        if ($filter === null || $filter($entry)) {
            $files[] = $entry;
        }
    }
    return $files;
}

/**
 * @param  GfeSettings $settings
 * @return list<string>
 */
function list_directories(string $path, array $settings): array
{
    $iterator = dir_iterator($path);
    if ($iterator === null) {
        return [];
    }
    $directories = [];
    foreach ($iterator as $info) {
        $filename = $info->getFilename();
        if ($filename === '.git' || $filename === '.svn') {
            continue;
        }
        if (! $info->isDir()) {
            continue;
        }
        $full = $info->getPathname();
        $relative = substr($full, strlen(GFE_ROOT_DIR) + 1);
        if (! in_array($relative, $settings['ignore_folders'], true)) {
            $directories[] = $relative;
        }
        $directories = array_merge($directories, list_directories($full, $settings));
    }
    return $directories;
}

/**
 * @param  GfeSettings $settings
 * @return array{files: list<GfeEntry>, directories: list<GfeEntry>}
 */
function list_directory(string $path, array $settings, string $prefix): array
{
    $iterator = dir_iterator($path);
    if ($iterator === null) {
        display_error('Invalid Directory');
    }
    $files = [];
    $directories = [];
    foreach ($iterator as $info) {
        $filename = $info->getFilename();
        if ($filename === '.git' || $filename === '.svn') {
            continue;
        }
        if ($info->isFile() && ! in_array($prefix . $filename, $settings['ignore_files'], true)) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (! in_array($ext, $settings['ignore_ext'], true)) {
                $files[] = [
                    'name' => $filename,
                    'ext' => $ext,
                    'type' => $settings['extensions'][$ext][0] ?? 'Unknown',
                    'size' => $info->getSize(),
                    'date' => $info->getMTime(),
                ];
            }
        }
        if ($info->isDir() && ! in_array($prefix . $filename, $settings['ignore_folders'], true)) {
            $directories[] = [
                'name' => $filename,
                'size' => dir_size($info->getPathname(), $settings),
                'date' => $info->getMTime(),
            ];
        }
    }
    return ['files' => $files, 'directories' => $directories];
}

/**
 * @param array<string, array{0: string, 1: string}> $extensions
 */
function file_icon(string $ext, array $extensions): string
{
    return $extensions[$ext][1] ?? 'fa-regular fa-file';
}

/**
 * @param GfeEntry                                    $entry
 * @param array<string, array{0: string, 1: string}> $extensions
 */
function file_row(array $entry, string $linkPath, array $extensions, string $extraHtml = '', string $sortBy = '', string $sortOrder = ''): string
{
    $name = esc($entry['name']);
    $size = format_size($entry['size']);
    $type = esc($entry['type'] ?? 'Unknown');
    $date = date('jS F Y', $entry['date']);
    return '<tr>'
        . '<td><a href="' . esc(url($linkPath, 'file', $sortBy, $sortOrder)) . '" title="File: ' . $name . ' (' . $size . ')">'
        . '<i class="fa-fw ' . file_icon($entry['ext'] ?? '', $extensions) . '" aria-hidden="true"></i>&nbsp;' . $name . '</a>' . $extraHtml . '</td>'
        . '<td>' . $size . '</td>'
        . '<td>' . $type . '</td>'
        . '<td>' . $date . '</td>'
        . '</tr>';
}

/**
 * @return array<string, string>
 */
function image_exif(string $path, string $ext): array
{
    if (! in_array($ext, ['jpg', 'jpeg', 'tif', 'tiff'], true) || ! function_exists('exif_read_data')) {
        return [];
    }
    $exif = @exif_read_data($path);
    if (! is_array($exif)) {
        return [];
    }
    $summary = [];
    foreach (['Make' => 'Camera', 'Model' => 'Model', 'DateTimeOriginal' => 'Taken'] as $key => $label) {
        $raw = $exif[$key] ?? '';
        $value = is_scalar($raw) ? trim((string) $raw) : '';
        if ($value !== '') {
            $summary[$label] = $value;
        }
    }
    return $summary;
}

/**
 * @param  GfeSettings $settings
 * @return array{label: string, class: string, html: string}
 */
function media_embed(string $ext, string $srcHref, string $downloadUrl, array $settings): array
{
    $src = esc($srcHref);
    if ($ext === 'pdf') {
        return [
            'label' => 'PDF',
            'class' => 'p-0',
            'html' => '<object class="gfe-embed-pdf" data="' . $src . '" type="application/pdf">'
                . '<p class="gfe-embed-fallback">This PDF can&rsquo;t be displayed here. '
                . '<a href="' . $src . '" target="_blank" rel="noopener">Open it in a new tab</a> or '
                . '<a href="' . $downloadUrl . '">download it</a>.</p></object>',
        ];
    }
    if (in_array($ext, $settings['video_ext'], true)) {
        return [
            'label' => 'Video',
            'class' => 'text-center',
            'html' => '<video class="gfe-embed-video" src="' . $src . '" controls preload="metadata">'
                . 'Your browser cannot play this video.</video>',
        ];
    }
    return [
        'label' => 'Audio',
        'class' => 'text-center',
        'html' => '<audio class="gfe-embed-audio" src="' . $src . '" controls preload="metadata">'
            . 'Your browser cannot play this audio.</audio>',
    ];
}

/**
 * Locates the current file among its already-sorted folder siblings and returns the
 * markup for the Previous/Next controls. A neighbour that exists links to its viewing
 * page; at a folder edge the corresponding control is a disabled placeholder so the
 * layout stays stable. A file alone in its folder renders both sides disabled, keeping
 * the footer identical to a multi-file folder. Only a file that is not among its
 * siblings at all yields empty controls.
 *
 * @param  list<GfeEntry> $files  Sibling files in the same folder, already sorted in listing order.
 * @return array{prev: string, next: string}
 */
function sibling_nav(array $files, string $fileName, string $prefix, string $sortBy = '', string $sortOrder = ''): array
{
    $index = null;
    foreach ($files as $i => $entry) {
        if ($entry['name'] === $fileName) {
            $index = $i;
            break;
        }
    }
    if ($index === null) {
        return ['prev' => '', 'next' => ''];
    }
    $prev = $files[$index - 1] ?? null;
    $next = $files[$index + 1] ?? null;
    return [
        'prev' => $prev !== null
            ? '<a href="' . esc(url($prefix . $prev['name'], 'file', $sortBy, $sortOrder)) . '" class="btn btn-outline-primary" title="Previous: ' . esc($prev['name']) . '"><i class="fa-solid fa-fw fa-chevron-left" aria-hidden="true"></i>&nbsp;Previous</a>'
            : '<span class="btn btn-outline-secondary disabled" aria-disabled="true"><i class="fa-solid fa-fw fa-chevron-left" aria-hidden="true"></i>&nbsp;Previous</span>',
        'next' => $next !== null
            ? '<a href="' . esc(url($prefix . $next['name'], 'file', $sortBy, $sortOrder)) . '" class="btn btn-outline-primary" title="Next: ' . esc($next['name']) . '">Next&nbsp;<i class="fa-solid fa-fw fa-chevron-right" aria-hidden="true"></i></a>'
            : '<span class="btn btn-outline-secondary disabled" aria-disabled="true">Next&nbsp;<i class="fa-solid fa-fw fa-chevron-right" aria-hidden="true"></i></span>',
    ];
}

function sort_field(string $sortBy): string
{
    return in_array($sortBy, ['name', 'size', 'type', 'date'], true) ? $sortBy : 'date';
}

function sort_direction(string $sortOrder): int
{
    return $sortOrder === 'asc' ? SORT_ASC : SORT_DESC;
}

/**
 * @param  list<GfeEntry> $entries
 * @return list<GfeEntry>
 */
function sort_entries(array $entries, string $sortBy, int $sortOrder): array
{
    /**
     * @param GfeEntry $a
     * @param GfeEntry $b
     */
    usort($entries, static function (array $a, array $b) use ($sortBy): int {
        if ($sortBy === 'name' || $sortBy === 'type') {
            return strcasecmp((string) ($a[$sortBy] ?? ''), (string) ($b[$sortBy] ?? ''));
        }
        return ($a[$sortBy] ?? 0) <=> ($b[$sortBy] ?? 0);
    });
    if ($sortOrder === SORT_DESC) {
        $entries = array_reverse($entries);
    }
    return $entries;
}

function count_lines(string $text): int
{
    if ($text === '') {
        return 0;
    }
    // Count line breaks; a final line without a trailing newline still counts.
    return substr_count($text, "\n") + (str_ends_with($text, "\n") ? 0 : 1);
}

/**
 * Sort order is a view preference, not part of a resource's identity, so it rides in
 * the query string — and only when it differs from the default. An empty column counts
 * as the default too, so callers can pass through unset values without special-casing.
 */
function is_default_sort(string $sortBy, string $sortOrder): bool
{
    return $sortBy === '' || ($sortBy === GFE_DEFAULT_SORT_BY && $sortOrder === GFE_DEFAULT_SORT_ORDER);
}

function url(string $path, string $mode, string $sortBy = '', string $sortOrder = ''): string
{
    $path = str_replace('%2F', '/', urlencode(urldecode($path)));
    // A non-default sort travels as a query string appended to whichever URL is built below.
    $sortQuery = is_default_sort($sortBy, $sortOrder) ? '' : http_build_query(['by' => $sortBy, 'order' => $sortOrder]);
    switch ($mode) {
        case 'dir':
            if ($path === 'home') {
                $link = GFE_URL . '/' . GFE_ROOT_FILENAME;
                $nice = GFE_URL . '/';
            } else {
                $link = GFE_URL . '/' . GFE_ROOT_FILENAME . '?' . http_build_query(['dir' => $path]);
                $nice = GFE_URL . '/browse/' . $path . '/';
            }
            if ($sortQuery !== '') {
                $link .= (str_contains($link, '?') ? '&' : '?') . $sortQuery;
                $nice .= '?' . $sortQuery;
            }
            break;
        case 'file':
            $link = GFE_URL . '/view.php?' . http_build_query(['file' => $path]);
            $nice = GFE_URL . '/viewing/' . $path . '/';
            if ($sortQuery !== '') {
                $link .= '&' . $sortQuery;
                $nice .= '?' . $sortQuery;
            }
            break;
        case 'download':
            $link = GFE_URL . '/view.php?' . http_build_query(['file' => $path, 'dl' => 1]);
            $nice = GFE_URL . '/download/' . $path . '/';
            break;
        default:
            $link = GFE_URL;
            $nice = GFE_URL;
    }
    return GFE_NICE_URL ? $nice : $link;
}

/**
 * Flips the order for the clicked column, then defers to url() so the sort rides in the
 * query string (and disappears entirely when the toggle lands back on the site default).
 */
function create_sort_url(string $sortBy, string $beforePath, string $currentName, int $currentSortOrder): string
{
    $order = $currentSortOrder === SORT_DESC ? 'asc' : 'desc';
    $path = $currentName === '' ? 'home' : $beforePath . $currentName;
    return url($path, 'dir', $sortBy, $order);
}

function create_sort_image(string $sortBy, string $currentSortBy, string $currentSortOrder): string
{
    if ($currentSortBy === $sortBy) {
        return $currentSortOrder === 'asc'
            ? '<i class="fa-solid fa-fw fa-caret-up" aria-hidden="true"></i>'
            : '<i class="fa-solid fa-fw fa-caret-down" aria-hidden="true"></i>';
    }
    return '<i class="fa-solid fa-fw fa-sort" aria-hidden="true"></i>';
}

/**
 * @param array{
 *     directory_names?: list<string>,
 *     current_directory_name?: string,
 *     file?: string,
 *     file_name?: string,
 *     search_keyword?: string,
 *     sort_by?: string,
 *     sort_order?: string
 * } $context
 */
function breadcrumbs(array $context): string
{
    $sortBy = $context['sort_by'] ?? '';
    $sortOrder = $context['sort_order'] ?? '';
    $html = '<li class="breadcrumb-item"><a href="' . esc(url('home', 'dir', $sortBy, $sortOrder)) . '"><i class="fa-solid fa-fw fa-house" aria-hidden="true"></i>Home</a></li>';

    $directoryNames = $context['directory_names'] ?? [];
    if (! empty($context['file'])) {
        $directoryNames = explode('/', $context['file']);
        array_pop($directoryNames);
    }
    $trail = '';
    foreach ($directoryNames as $name) {
        if ($name === '') {
            continue;
        }
        $trail .= $name . '/';
        $html .= '<li class="breadcrumb-item"><a href="' . esc(url(rtrim($trail, '/'), 'dir', $sortBy, $sortOrder)) . '">'
            . esc($name) . '</a></li>';
    }
    if (! empty($context['current_directory_name'])) {
        $html .= '<li class="breadcrumb-item active" aria-current="page">'
            . esc($context['current_directory_name']) . '</li>';
    }
    if (! empty($context['file_name'])) {
        $html .= '<li class="breadcrumb-item active" aria-current="page">'
            . esc($context['file_name']) . '</li>';
    }
    if (! empty($context['search_keyword'])) {
        $html .= '<li class="breadcrumb-item"><a href="' . GFE_URL . '/search.php">Search</a></li>';
        $html .= '<li class="breadcrumb-item active" aria-current="page">'
            . esc($context['search_keyword']) . '</li>';
    }
    return $html;
}

function display_error(string $msg): never
{
    template_header(' - Error - ' . $msg, breadcrumbs([]));
    echo '<div class="alert alert-danger" role="alert"><strong>' . esc($msg)
        . '</strong>. You can <a href="' . GFE_URL . '">go back to the main site</a> or '
        . '<a href="' . GFE_URL . '" onclick="history.back(); return false;">go back to the previous page</a>.</div>';
    template_footer();
    exit();
}

function template_header(string $title, string $breadcrumbs, string $canonical = '', string $previewImage = ''): void
{
    $requestUri = esc(GFE_URL . ($_SERVER['REQUEST_URI'] ?? ''));
    // Prefer the canonical (nice-URL) permalink; fall back to the request URI.
    $canonicalUrl = $canonical !== '' ? esc($canonical) : $requestUri;
    $fullTitle = esc(GFE_SITE_NAME . $title);
    $siteName = esc(GFE_SITE_NAME);
    $description = esc(GFE_SITE_DESCRIPTION);
    // When viewing an image, preview that image in social cards; otherwise fall back to the app icon.
    $previewUrl = $previewImage !== '' ? esc($previewImage) : GFE_URL . '/resources/icon.png';
    $twitterCard = $previewImage !== '' ? 'summary_large_image' : 'summary';
    ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="x-dns-prefetch-control" content="on">
        <script>
            (function () {
                var KEY = 'gfe-theme';
                var media = window.matchMedia('(prefers-color-scheme: dark)');
                var root = document.documentElement;
                var pref = function () {
                    try {
                        return localStorage.getItem(KEY) || 'auto';
                    } catch (e) {
                        return 'auto';
                    }
                };
                var apply = function () {
                    var choice = pref();
                    var resolved = choice === 'light' || choice === 'dark' ? choice : (media.matches ? 'dark' : 'light');
                    root.setAttribute('data-bs-theme', resolved);
                    root.setAttribute('data-gfe-theme', choice);
                };
                apply();
                media.addEventListener('change', function () {
                    if (pref() === 'auto') {
                        apply();
                    }
                });
                window.gfeSetTheme = function (choice) {
                    try {
                        localStorage.setItem(KEY, choice);
                    } catch (e) {}
                    apply();
                    if (window.gfeSyncTheme) {
                        window.gfeSyncTheme();
                    }
                };
            })();
        </script>
        <title><?php echo $fullTitle; ?></title>
        <meta name="copyright" content="Copyright &copy; <?php echo date('Y'); ?> Lester Chan, All Rights Reserved.">
        <meta name="author" content="Lester Chan">
        <meta name="description" content="<?php echo $description; ?>">
        <meta property="og:site_name" content="<?php echo $siteName; ?>">
        <meta property="og:title" content="<?php echo $fullTitle; ?>">
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo $canonicalUrl; ?>">
        <meta property="og:image" content="<?php echo $previewUrl; ?>">
        <meta property="og:description" content="<?php echo $description; ?>">
        <meta name="twitter:card" content="<?php echo $twitterCard; ?>">
        <meta name="twitter:title" content="<?php echo $fullTitle; ?>">
        <meta name="twitter:url" content="<?php echo $canonicalUrl; ?>">
        <meta name="twitter:image" content="<?php echo $previewUrl; ?>">
        <meta name="twitter:description" content="<?php echo $description; ?>">
        <link rel="canonical" href="<?php echo $canonicalUrl; ?>">
        <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
        <link rel="icon" href="<?php echo GFE_URL; ?>/resources/favicon.ico" sizes="any">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css" integrity="sha512-hasIneQUHlh06VNBe7f6ZcHmeRTLIaQWFd43YriJ0UND19bvYRauxthDg8E4eVNPm9bRUhr5JGeqH7FRFXQu5g==" crossorigin="anonymous" referrerpolicy="no-referrer">
        <?php if (GFE_GA_MEASUREMENT_ID !== '') : ?>
            <?php $gaId = esc(GFE_GA_MEASUREMENT_ID); ?>
        <link rel="dns-prefetch" href="https://www.googletagmanager.com">
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $gaId; ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo $gaId; ?>');
        </script>
        <?php endif; ?>
        <link rel="stylesheet" href="<?php echo GFE_URL; ?>/resources/style.css?v=<?php echo GFE_VERSION; ?>">
    </head>
    <body>
        <a class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-primary" href="#gfe-content">Skip to content</a>
        <div class="container gfe-shell my-4">
            <div class="gfe-topbar">
                <a class="gfe-brand text-decoration-none" href="<?php echo GFE_URL; ?>">
                    <span class="gfe-brand-mark"><i class="fa-solid fa-hard-drive" aria-hidden="true"></i></span>
                    <span>
                        <span class="gfe-title"><?php echo $siteName; ?></span>
                        <span class="gfe-subtitle d-block">Browse, search &amp; download files</span>
                    </span>
                </a>
                <div class="gfe-topbar-actions">
                    <div class="gfe-theme-switch" role="group" aria-label="Colour theme">
                        <button type="button" data-gfe-set="light" title="Light theme" aria-label="Light theme"><i class="fa-solid fa-sun" aria-hidden="true"></i></button>
                        <button type="button" data-gfe-set="auto" title="Match system" aria-label="Match system theme"><i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i></button>
                        <button type="button" data-gfe-set="dark" title="Dark theme" aria-label="Dark theme"><i class="fa-solid fa-moon" aria-hidden="true"></i></button>
                    </div>
                </div>
            </div>
            <nav class="gfe-path" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php echo $breadcrumbs; ?>
                </ol>
            </nav>
            <main id="gfe-content">
    <?php
}

function template_footer(string $fullUrl = '', string $fullUrlHref = ''): void
{
    $start = defined('GFE_START') ? (float) GFE_START : microtime(true);
    $generatedIn = number_format(microtime(true) - $start, 5);
    $onSearch = basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'search.php';
    ?>
    <?php if ($fullUrl !== '') : ?>
            <div class="gfe-fullpath">
                <i class="fa-solid fa-fw fa-link" aria-hidden="true"></i>
                <a href="<?php echo esc($fullUrlHref); ?>"><?php echo esc($fullUrl); ?></a>
            </div>
    <?php endif; ?>
    <?php if (GFE_CAN_SEARCH && ! $onSearch) : ?>
            <form class="row row-cols-lg-auto g-2 align-items-center mb-3" method="get" action="<?php echo GFE_URL; ?>/search.php">
                <div class="col-12">
                    <label class="visually-hidden" for="search-bottom-keyword">Search for files</label>
                    <input type="text" class="form-control" id="search-bottom-keyword" name="search" placeholder="Search for files ...">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
                <div class="col-12">
                    <small class="text-body-secondary"><a href="<?php echo GFE_URL; ?>/search.php">Advanced Search</a></small>
                </div>
            </form>
    <?php endif; ?>
            </main>
            <footer class="gfe-footer text-center">
                <small class="text-body-secondary">
                    Powered By <a href="https://github.com/lesterchan/gamerz-file-explorer">GaMerZ File Explorer Version <?php echo GFE_VERSION; ?></a>. Page Generated In <?php echo $generatedIn; ?>s.
                </small>
                <br>
                <small class="text-body-secondary">
                    Copyright &copy; <?php echo date('Y'); ?> <a href="https://lesterchan.net">Lester Chan</a>, All Rights Reserved.
                </small>
            </footer>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js" integrity="sha512-7Pi/otdlbbCR+LnW+F7PwFcSDJOuUJB3OxtEHbg4vSMvzvJjde4Po1v4BR9Gdc9aXNUNFVUY+SK51wWT8WF0Gg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js" integrity="sha512-D9gUyxqja7hBtkWpPWGt9wfbfaMGVt9gnyCvYa+jojwwPHLCzUm5i8rpk7vD7wNee9bA35eYIjobYPaQuKS1MQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="<?php echo GFE_URL; ?>/resources/script.js?v=<?php echo GFE_VERSION; ?>"></script>
    </body>
</html>
    <?php
}
