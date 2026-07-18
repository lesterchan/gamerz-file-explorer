<?php

declare(strict_types=1);

/**
 * GaMerZ File Explorer — shared functions.
 *
 * The GfeSettings and GfeEntry array-shape type aliases are declared globally
 * in phpstan.neon.dist so every file can reference them.
 */

### Function: Format A Byte Count Into A Human-Readable String
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

### Function: Build A Safe Content-Disposition Header Value For A Download
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

### Function: Reject Path Traversal — No '.'/'..' Segment And No Empty Segment
function is_safe_path(string $path): bool
{
    $segments = explode('/', $path);
    return ! in_array('.', $segments, true)
        && ! in_array('..', $segments, true)
        && ! str_contains($path, '//');
}

### Function: Recursively Total The Size Of A Directory
function dir_size(string $dir): int
{
    $handle = @opendir($dir);
    if ($handle === false) {
        return 0;
    }
    $total = 0;
    while (($filename = readdir($handle)) !== false) {
        if (in_array($filename, ['.', '..', '.git', '.svn'], true)) {
            continue;
        }
        $path = $dir . '/' . $filename;
        if (is_file($path)) {
            $total += (int) filesize($path);
        } elseif (is_dir($path)) {
            $total += dir_size($path);
        }
    }
    closedir($handle);
    return $total;
}

### Function: Recursively Collect Every File Under A Path (Used By Search)
/**
 * @param  GfeSettings $settings
 * @return list<GfeEntry>
 */
function list_files(string $path, array $settings): array
{
    $handle = @opendir($path);
    if ($handle === false) {
        return [];
    }
    $files = [];
    while (($filename = readdir($handle)) !== false) {
        if (in_array($filename, ['.', '..', '.git', '.svn'], true)) {
            continue;
        }
        $full = $path . '/' . $filename;
        $relative = substr($full, strlen(GFE_ROOT_DIR) + 1);
        $folder = substr($relative, 0, -(strlen($filename) + 1));
        if (is_dir($full)) {
            $files = array_merge($files, list_files($full, $settings));
            continue;
        }
        if (! is_file($full)) {
            continue;
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (
            in_array($ext, $settings['ignore_ext'], true)
            || in_array($relative, $settings['ignore_files'], true)
            || in_array($folder, $settings['ignore_folders'], true)
            || empty($settings['extensions'][$ext][0])
        ) {
            continue;
        }
        $files[] = [
            'name' => $filename,
            'ext' => $ext,
            'path' => $relative,
            'type' => $settings['extensions'][$ext][0],
            'size' => (int) filesize($full),
            'date' => (int) filemtime($full),
        ];
    }
    closedir($handle);
    return $files;
}

### Function: Recursively Collect Every Sub-Directory Path (Used By Search Filter)
/**
 * @param  GfeSettings $settings
 * @return list<string>
 */
function list_directories(string $path, array $settings): array
{
    $handle = @opendir($path);
    if ($handle === false) {
        return [];
    }
    $directories = [];
    while (($filename = readdir($handle)) !== false) {
        if (in_array($filename, ['.', '..', '.git', '.svn'], true)) {
            continue;
        }
        $full = $path . '/' . $filename;
        if (! is_dir($full)) {
            continue;
        }
        $relative = substr($full, strlen(GFE_ROOT_DIR) + 1);
        if (! in_array($relative, $settings['ignore_folders'], true)) {
            $directories[] = $relative;
        }
        $directories = array_merge($directories, list_directories($full, $settings));
    }
    closedir($handle);
    return $directories;
}

### Function: List The Files And Directories In A Single Directory Level
/**
 * @param  GfeSettings $settings
 * @return array{files: list<GfeEntry>, directories: list<GfeEntry>}
 */
function list_directory(string $path, array $settings, string $prefix): array
{
    $handle = @opendir($path);
    if ($handle === false) {
        display_error('Invalid Directory');
    }
    $files = [];
    $directories = [];
    while (($filename = readdir($handle)) !== false) {
        if (in_array($filename, ['.', '..', '.git', '.svn'], true)) {
            continue;
        }
        $full = $path . '/' . $filename;
        if (is_file($full) && ! in_array($prefix . $filename, $settings['ignore_files'], true)) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (! in_array($ext, $settings['ignore_ext'], true)) {
                $files[] = [
                    'name' => $filename,
                    'ext' => $ext,
                    'type' => $settings['extensions'][$ext][0] ?? 'Unknown',
                    'size' => (int) filesize($full),
                    'date' => (int) filemtime($full),
                ];
            }
        }
        if (is_dir($full) && ! in_array($prefix . $filename, $settings['ignore_folders'], true)) {
            $directories[] = [
                'name' => $filename,
                'size' => dir_size($full),
                'date' => (int) filemtime($full),
            ];
        }
    }
    closedir($handle);
    return ['files' => $files, 'directories' => $directories];
}

### Function: Determine The Font Awesome Icon Class For A File Extension
/**
 * @param array<string, array{0: string, 1: string}> $extensions
 */
function file_icon(string $ext, array $extensions): string
{
    return $extensions[$ext][1] ?? 'fa-regular fa-file';
}

### Function: Render A File Table Row (Shared By The Listing And Search Results)
/**
 * @param GfeEntry                                    $entry
 * @param array<string, array{0: string, 1: string}> $extensions
 */
function file_row(array $entry, string $linkPath, array $extensions, string $extraHtml = ''): string
{
    $name = htmlspecialchars($entry['name'], ENT_QUOTES, 'UTF-8');
    $size = format_size($entry['size']);
    $type = htmlspecialchars($entry['type'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
    $date = date('jS F Y', $entry['date']);
    return '<tr>'
        . '<td><a href="' . url($linkPath, 'file') . '" title="File: ' . $name . ' (' . $size . ')">'
        . '<i class="fa-fw ' . file_icon($entry['ext'] ?? '', $extensions) . '" aria-hidden="true"></i>&nbsp;' . $name . '</a>' . $extraHtml . '</td>'
        . '<td>' . $size . '</td>'
        . '<td>' . $type . '</td>'
        . '<td>' . $date . '</td>'
        . '</tr>';
}

### Function: Extract A Short EXIF Summary From An Image (Gated — Needs The exif Extension)
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

### Function: Build The Inline Embed Markup For A Playable Media File (PDF/Video/Audio)
/**
 * @param  GfeSettings $settings
 * @return array{label: string, class: string, html: string}
 */
function media_embed(string $ext, string $srcHref, string $downloadUrl, array $settings): array
{
    $src = htmlspecialchars($srcHref, ENT_QUOTES, 'UTF-8');
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

### Function: Sort A List Of Entries By A Field And Order
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

### Function: Count The Number Of Lines In A Text File
function get_line_count(string $file): int
{
    $handle = @fopen($file, 'rb');
    if ($handle === false) {
        return 0;
    }
    $lines = 0;
    while (! feof($handle)) {
        fgets($handle);
        $lines++;
    }
    fclose($handle);
    return max($lines - 1, 0);
}

### Function: Build A Link For A Directory, File Or Download
function url(string $path, string $mode, string $sortBy = '', string $sortOrder = ''): string
{
    $path = str_replace('%2F', '/', urlencode(urldecode($path)));
    switch ($mode) {
        case 'dir':
            if ($path === 'home') {
                $link = GFE_URL . '/' . GFE_ROOT_FILENAME;
                $nice = GFE_URL . '/';
            } else {
                $link = GFE_URL . '/' . GFE_ROOT_FILENAME . '?' . http_build_query(['dir' => $path]);
                $nice = GFE_URL . '/browse/' . $path . '/';
            }
            if ($sortBy !== '') {
                $link .= (str_contains($link, '?') ? '&' : '?') . http_build_query(['by' => $sortBy, 'order' => $sortOrder]);
                $nice .= 'sortby/' . $sortBy . '/sortorder/' . $sortOrder . '/';
            }
            break;
        case 'file':
            $link = GFE_URL . '/view.php?' . http_build_query(['file' => $path]);
            $nice = GFE_URL . '/viewing/' . $path . '/';
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

### Function: Build The Toggle Link For A Sortable Column Header
function create_sort_url(string $sortBy, string $beforePath, string $currentName, int $currentSortOrder): string
{
    $beforePath = str_replace('%2F', '/', urlencode(urldecode($beforePath)));
    $currentName = str_replace('%2F', '/', urlencode(urldecode($currentName)));
    $order = $currentSortOrder === SORT_DESC ? 'asc' : 'desc';
    if ($currentName === '') {
        $link = '?' . http_build_query(['by' => $sortBy, 'order' => $order]);
        $nice = GFE_URL . '/sortby/' . $sortBy . '/sortorder/' . $order . '/';
    } else {
        $dir = $beforePath . $currentName;
        $link = '?' . http_build_query(['dir' => $dir, 'by' => $sortBy, 'order' => $order]);
        $nice = GFE_URL . '/browse/' . $dir . '/sortby/' . $sortBy . '/sortorder/' . $order . '/';
    }
    return GFE_NICE_URL ? $nice : $link;
}

### Function: Render The Sort Direction Icon For A Column Header
function create_sort_image(string $sortBy, string $currentSortBy, string $currentSortOrder): string
{
    if ($currentSortBy === $sortBy) {
        return $currentSortOrder === 'asc'
            ? '<i class="fa-solid fa-fw fa-caret-up" aria-hidden="true"></i>'
            : '<i class="fa-solid fa-fw fa-caret-down" aria-hidden="true"></i>';
    }
    return '<i class="fa-solid fa-fw fa-sort" aria-hidden="true"></i>';
}

### Function: Build The Breadcrumb Trail
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
    $html = '<li class="breadcrumb-item"><a href="' . url('home', 'dir', $sortBy, $sortOrder) . '"><i class="fa-solid fa-fw fa-house" aria-hidden="true"></i>Home</a></li>';

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
        $html .= '<li class="breadcrumb-item"><a href="' . url(rtrim($trail, '/'), 'dir', $sortBy, $sortOrder) . '">'
            . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a></li>';
    }
    if (! empty($context['current_directory_name'])) {
        $html .= '<li class="breadcrumb-item active" aria-current="page">'
            . htmlspecialchars($context['current_directory_name'], ENT_QUOTES, 'UTF-8') . '</li>';
    }
    if (! empty($context['file_name'])) {
        $html .= '<li class="breadcrumb-item active" aria-current="page">'
            . htmlspecialchars($context['file_name'], ENT_QUOTES, 'UTF-8') . '</li>';
    }
    if (! empty($context['search_keyword'])) {
        $html .= '<li class="breadcrumb-item"><a href="' . GFE_URL . '/search.php">Search</a></li>';
        $html .= '<li class="breadcrumb-item active" aria-current="page">'
            . htmlspecialchars($context['search_keyword'], ENT_QUOTES, 'UTF-8') . '</li>';
    }
    return $html;
}

### Function: Display An Error Message And Stop
function display_error(string $msg): never
{
    template_header(' - Error - ' . $msg, breadcrumbs([]));
    echo '<div class="alert alert-danger" role="alert"><strong>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8')
        . '</strong>. You can <a href="' . GFE_URL . '">go back to the main site</a> or '
        . '<a href="' . GFE_URL . '" onclick="history.back(); return false;">go back to the previous page</a>.</div>';
    template_footer();
    exit();
}

### Function: Render The Page Header And Open The Body
function template_header(string $title, string $breadcrumbs): void
{
    $requestUri = htmlspecialchars(GFE_URL . ($_SERVER['REQUEST_URI'] ?? ''), ENT_QUOTES, 'UTF-8');
    $fullTitle = htmlspecialchars(GFE_SITE_NAME . $title, ENT_QUOTES, 'UTF-8');
    $siteName = htmlspecialchars(GFE_SITE_NAME, ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(GFE_SITE_DESCRIPTION, ENT_QUOTES, 'UTF-8');
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
        <meta property="og:url" content="<?php echo $requestUri; ?>">
        <meta property="og:image" content="<?php echo GFE_URL; ?>/resources/icon.png">
        <meta property="og:description" content="<?php echo $description; ?>">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="<?php echo $fullTitle; ?>">
        <meta name="twitter:url" content="<?php echo $requestUri; ?>">
        <meta name="twitter:image" content="<?php echo GFE_URL; ?>/resources/icon.png">
        <meta name="twitter:description" content="<?php echo $description; ?>">
        <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
        <link rel="icon" href="<?php echo GFE_URL; ?>/resources/favicon.ico" sizes="any">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css" integrity="sha512-hasIneQUHlh06VNBe7f6ZcHmeRTLIaQWFd43YriJ0UND19bvYRauxthDg8E4eVNPm9bRUhr5JGeqH7FRFXQu5g==" crossorigin="anonymous" referrerpolicy="no-referrer">
        <?php if (GFE_GA_MEASUREMENT_ID !== '') : ?>
            <?php $gaId = htmlspecialchars(GFE_GA_MEASUREMENT_ID, ENT_QUOTES, 'UTF-8'); ?>
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
            <main>
    <?php
}

### Function: Close The Body And Render The Page Footer
function template_footer(string $fullUrl = '', string $fullUrlHref = ''): void
{
    $start = defined('GFE_START') ? (float) GFE_START : microtime(true);
    $generatedIn = number_format(microtime(true) - $start, 5);
    $onSearch = basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'search.php';
    ?>
    <?php if ($fullUrl !== '') : ?>
            <div class="gfe-fullpath">
                <i class="fa-solid fa-fw fa-link" aria-hidden="true"></i>
                <a href="<?php echo htmlspecialchars($fullUrlHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($fullUrl, ENT_QUOTES, 'UTF-8'); ?></a>
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
