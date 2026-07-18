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
            ? '<i class="fa-solid fa-fw fa-sort-up"></i>'
            : '<i class="fa-solid fa-fw fa-sort-down"></i>';
    }
    return '<i class="fa-solid fa-fw fa-sort"></i>';
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
    $html = '<li class="breadcrumb-item"><a href="' . url('home', 'dir', $sortBy, $sortOrder) . '"><i class="fa-solid fa-fw fa-house"></i>Home</a></li>';

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
        <style>
            :root,
            [data-bs-theme="light"] {
                --gfe-canvas: #eceef3;
                --gfe-surface: #ffffff;
                --gfe-surface-2: #f6f7f9;
                --gfe-border: #e4e6ec;
                --gfe-border-strong: #d3d7e0;
                --gfe-text: #1b1e27;
                --gfe-muted: #626878;
                --gfe-accent: #2563eb;
                --gfe-accent-hover: #1d4ed8;
                --gfe-accent-contrast: #ffffff;
                --gfe-link: #2563eb;
                --gfe-link-hover: #1e40af;
                --gfe-soft: rgba(37, 99, 235, .09);
                --gfe-soft-strong: rgba(37, 99, 235, .14);
                --gfe-ring: rgba(37, 99, 235, .30);
                --gfe-shadow: 0 1px 2px rgba(19, 22, 31, .05), 0 18px 40px -20px rgba(19, 22, 31, .28);
                --gfe-brand-1: #38bdf8;
                --gfe-brand-2: #2563eb;
                --ic-folder: #e2a10a;
                --ic-image: #15a34a;
                --ic-video: #9333ea;
                --ic-audio: #db2777;
                --ic-archive: #ea580c;
                --ic-pdf: #dc2626;
                --ic-code: #2563eb;
                --ic-doc: #2563eb;
                --ic-excel: #15a34a;
                --ic-ppt: #d9480f;
                --ic-generic: #6b7280;
            }
            [data-bs-theme="dark"] {
                --gfe-canvas: #0c0e13;
                --gfe-surface: #161922;
                --gfe-surface-2: #1b1f29;
                --gfe-border: #262b37;
                --gfe-border-strong: #343b4a;
                --gfe-text: #e7e9ef;
                --gfe-muted: #98a0b0;
                --gfe-accent: #3b82f6;
                --gfe-accent-hover: #60a5fa;
                --gfe-accent-contrast: #ffffff;
                --gfe-link: #93c5fd;
                --gfe-link-hover: #bfdbff;
                --gfe-soft: rgba(59, 130, 246, .14);
                --gfe-soft-strong: rgba(59, 130, 246, .20);
                --gfe-ring: rgba(59, 130, 246, .42);
                --gfe-shadow: 0 1px 2px rgba(0, 0, 0, .40), 0 22px 48px -24px rgba(0, 0, 0, .70);
                --gfe-brand-1: #38bdf8;
                --gfe-brand-2: #3b82f6;
                --ic-folder: #f5c451;
                --ic-image: #4ade80;
                --ic-video: #c084fc;
                --ic-audio: #f472b6;
                --ic-archive: #fb923c;
                --ic-pdf: #f87171;
                --ic-code: #60a5fa;
                --ic-doc: #60a5fa;
                --ic-excel: #4ade80;
                --ic-ppt: #fdba74;
                --ic-generic: #97a1b3;
            }

            body {
                background: var(--gfe-canvas);
                color: var(--gfe-text);
                -webkit-font-smoothing: antialiased;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }

            .gfe-shell {
                max-width: 1120px;
            }

            .gfe-topbar {
                display: flex;
                align-items: center;
                gap: 1rem;
                flex-wrap: wrap;
                margin-bottom: 1.25rem;
            }
            .gfe-brand {
                display: flex;
                align-items: center;
                gap: .85rem;
                min-width: 0;
            }
            .gfe-brand-mark {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2.9rem;
                height: 2.9rem;
                border-radius: 14px;
                background: linear-gradient(135deg, var(--gfe-brand-1), var(--gfe-brand-2));
                color: #fff;
                font-size: 1.25rem;
                box-shadow: 0 6px 16px -6px var(--gfe-ring);
                flex: none;
            }
            .gfe-title {
                font-size: 1.5rem;
                font-weight: 700;
                letter-spacing: -.02em;
                line-height: 1.1;
                margin: 0;
                color: var(--gfe-text);
            }
            .gfe-subtitle {
                margin: .1rem 0 0;
                font-size: .8125rem;
                color: var(--gfe-muted);
            }
            .gfe-topbar-actions {
                margin-left: auto;
            }

            .gfe-theme-switch {
                display: inline-flex;
                padding: 3px;
                gap: 2px;
                background: var(--gfe-surface-2);
                border: 1px solid var(--gfe-border);
                border-radius: 999px;
            }
            .gfe-theme-switch button {
                appearance: none;
                border: 0;
                background: transparent;
                color: var(--gfe-muted);
                width: 2rem;
                height: 2rem;
                border-radius: 999px;
                font-size: .8125rem;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: background-color .15s ease, color .15s ease;
            }
            .gfe-theme-switch button:hover {
                color: var(--gfe-text);
            }
            .gfe-theme-switch button[aria-pressed="true"] {
                background: var(--gfe-surface);
                color: var(--gfe-accent);
                box-shadow: 0 1px 2px rgba(0, 0, 0, .12);
            }
            [data-bs-theme="dark"] .gfe-theme-switch button[aria-pressed="true"] {
                background: var(--gfe-border);
                color: var(--gfe-link);
            }
            .gfe-theme-switch button:focus-visible {
                outline: 2px solid var(--gfe-ring);
                outline-offset: 2px;
            }

            .gfe-path {
                margin-bottom: 1rem;
            }
            .gfe-path .breadcrumb {
                margin: 0;
                padding: .5rem .85rem;
                background: var(--gfe-surface);
                border: 1px solid var(--gfe-border);
                border-radius: 10px;
                font-size: .875rem;
                --bs-breadcrumb-divider-color: var(--gfe-muted);
            }
            .gfe-path .breadcrumb-item + .breadcrumb-item::before {
                content: "\203A";
                color: var(--gfe-muted);
            }
            .gfe-path .breadcrumb-item a {
                color: var(--gfe-muted);
                text-decoration: none;
                font-weight: 500;
            }
            .gfe-path .breadcrumb-item a:hover {
                color: var(--gfe-accent);
            }
            .gfe-path .breadcrumb-item a .fa-house {
                margin-right: .5rem;
            }
            .gfe-path .breadcrumb-item.active {
                color: var(--gfe-text);
                font-weight: 600;
            }

            .gfe-surface {
                background: var(--gfe-surface);
                border: 1px solid var(--gfe-border);
                border-radius: 14px;
                box-shadow: var(--gfe-shadow);
                overflow: hidden;
            }
            .gfe-panel {
                background: var(--gfe-surface);
                border: 1px solid var(--gfe-border);
                border-radius: 14px;
                box-shadow: var(--gfe-shadow);
                padding: 1.5rem;
            }
            .gfe-table {
                margin: 0;
                color: var(--gfe-text);
                --bs-table-bg: transparent;
                border-color: var(--gfe-border);
            }
            .gfe-table thead th {
                background: var(--gfe-surface-2);
                border-bottom: 1px solid var(--gfe-border);
                color: var(--gfe-muted);
                font-size: .75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .04em;
                padding: .7rem 1rem;
                white-space: nowrap;
            }
            .gfe-table thead th a {
                color: inherit;
                display: flex;
                align-items: center;
                gap: .3rem;
            }
            .gfe-table thead th a:hover {
                color: var(--gfe-text);
            }
            .gfe-table thead th .fa-sort,
            .gfe-table thead th .fa-sort-up,
            .gfe-table thead th .fa-sort-down {
                opacity: .55;
                font-size: .8em;
            }
            .gfe-table tbody td,
            .gfe-table tfoot td {
                padding: .55rem 1rem;
                border-top: 1px solid var(--gfe-border);
                vertical-align: middle;
                color: var(--gfe-text);
                font-size: .875rem;
            }
            .gfe-table tbody tr:first-child td {
                border-top: 0;
            }
            .gfe-table tbody tr {
                transition: background-color .12s ease;
            }
            .gfe-table tbody tr:hover {
                background: var(--gfe-soft);
            }
            .gfe-table tbody tr:has(a) {
                cursor: pointer;
            }

            .gfe-table tbody td:first-child a {
                display: inline-flex;
                align-items: center;
                gap: .1rem;
                color: var(--gfe-text);
                text-decoration: none;
                font-weight: 500;
                max-width: 100%;
            }
            .gfe-table tbody td:first-child a:hover {
                color: var(--gfe-accent);
            }
            .gfe-table tbody td:first-child a > i.fa-fw:first-child {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                margin-right: .6rem;
                border-radius: 9px;
                font-size: .95rem;
                flex: none;
                background: color-mix(in srgb, currentColor 15%, transparent);
            }

            .gfe-table td:nth-child(2),
            .gfe-table th:nth-child(2),
            .gfe-table td:nth-child(4),
            .gfe-table th:nth-child(4) {
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }
            .gfe-table td:nth-child(2),
            .gfe-table th:nth-child(2) {
                text-align: right;
                color: var(--gfe-muted);
            }
            .gfe-table td:nth-child(3) {
                color: var(--gfe-muted);
            }
            .gfe-table td:nth-child(4) {
                color: var(--gfe-muted);
            }
            .gfe-table tfoot td {
                background: var(--gfe-surface-2);
                border-top: 1px solid var(--gfe-border-strong);
                color: var(--gfe-text);
            }
            .gfe-table tfoot td strong {
                font-weight: 600;
            }

            .gfe-row-parent td {
                background: var(--gfe-soft);
            }
            .gfe-row-parent a {
                color: var(--gfe-accent) !important;
                font-weight: 600 !important;
            }
            .gfe-row-parent a > i.fa-fw:first-child {
                color: var(--gfe-accent) !important;
            }
            .gfe-row-empty td {
                color: var(--gfe-muted);
                padding: 2.5rem 1rem;
            }

            .fa-folder { color: var(--ic-folder); }
            .fa-file-image { color: var(--ic-image); }
            .fa-file-video { color: var(--ic-video); }
            .fa-file-audio { color: var(--ic-audio); }
            .fa-file-zipper { color: var(--ic-archive); }
            .fa-file-pdf { color: var(--ic-pdf); }
            .fa-file-code { color: var(--ic-code); }
            .fa-file-word { color: var(--ic-doc); }
            .fa-file-excel,
            .fa-file-csv { color: var(--ic-excel); }
            .fa-file-powerpoint { color: var(--ic-ppt); }
            .fa-file-lines,
            .fa-regular.fa-file { color: var(--ic-generic); }

            .card {
                background: var(--gfe-surface);
                border: 1px solid var(--gfe-border);
                border-radius: 14px;
                box-shadow: var(--gfe-shadow);
                overflow: hidden;
            }
            .card-header {
                background: var(--gfe-surface-2);
                border-bottom: 1px solid var(--gfe-border);
                font-weight: 600;
                color: var(--gfe-text);
                padding: .8rem 1rem;
            }
            .card-body pre {
                background: var(--gfe-surface-2);
                border: 1px solid var(--gfe-border);
                border-radius: 10px;
                padding: 1rem;
                overflow: auto;
            }
            .card-body pre code {
                font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
                font-size: .85rem;
            }
            .list-group-item {
                background: var(--gfe-surface);
                border-color: var(--gfe-border);
                color: var(--gfe-muted);
                font-size: .875rem;
                font-variant-numeric: tabular-nums;
            }
            .card-footer {
                background: var(--gfe-surface-2);
                border-top: 1px solid var(--gfe-border);
                padding: .8rem 1rem;
            }

            .btn-primary {
                --bs-btn-bg: var(--gfe-accent);
                --bs-btn-border-color: var(--gfe-accent);
                --bs-btn-hover-bg: var(--gfe-accent-hover);
                --bs-btn-hover-border-color: var(--gfe-accent-hover);
                --bs-btn-active-bg: var(--gfe-accent-hover);
                --bs-btn-active-border-color: var(--gfe-accent-hover);
                --bs-btn-color: var(--gfe-accent-contrast);
                --bs-btn-hover-color: var(--gfe-accent-contrast);
                --bs-btn-active-color: var(--gfe-accent-contrast);
                font-weight: 600;
                border-radius: 9px;
            }
            .form-control,
            .form-select {
                background: var(--gfe-surface);
                border-color: var(--gfe-border-strong);
                color: var(--gfe-text);
                border-radius: 9px;
            }
            .form-control::placeholder {
                color: var(--gfe-muted);
            }
            .form-control:focus,
            .form-select:focus {
                background: var(--gfe-surface);
                border-color: var(--gfe-accent);
                color: var(--gfe-text);
                box-shadow: 0 0 0 3px var(--gfe-ring);
            }
            .col-form-label {
                color: var(--gfe-muted);
                font-weight: 600;
            }
            a {
                color: var(--gfe-link);
            }
            a:hover {
                color: var(--gfe-link-hover);
            }
            .text-body-secondary {
                color: var(--gfe-muted) !important;
            }
            .alert-danger {
                border-radius: 14px;
            }
            .gfe-fullpath {
                display: flex;
                align-items: center;
                gap: .5rem;
                margin: 1rem 0;
                padding: .5rem .85rem;
                background: var(--gfe-surface);
                border: 1px solid var(--gfe-border);
                border-radius: 10px;
                font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
                font-size: .8125rem;
                color: var(--gfe-muted);
                word-break: break-all;
            }
            .gfe-fullpath i {
                flex: none;
                color: var(--gfe-muted);
            }
            .gfe-fullpath a {
                color: var(--gfe-muted);
                text-decoration: none;
            }
            .gfe-fullpath a:hover {
                color: var(--gfe-accent);
                text-decoration: underline;
            }
            .gfe-footer {
                border-top: 1px solid var(--gfe-border);
                margin-top: 2rem;
                padding-top: 1.25rem;
            }

            .hljs { background: transparent; }
            .card-body pre code.hljs { padding: 0; }
            [data-bs-theme="dark"] .hljs { color: var(--gfe-text); }
            [data-bs-theme="dark"] .hljs-comment,
            [data-bs-theme="dark"] .hljs-quote { color: #7d8695; font-style: italic; }
            [data-bs-theme="dark"] .hljs-keyword,
            [data-bs-theme="dark"] .hljs-selector-tag,
            [data-bs-theme="dark"] .hljs-literal,
            [data-bs-theme="dark"] .hljs-name { color: #c084fc; }
            [data-bs-theme="dark"] .hljs-string,
            [data-bs-theme="dark"] .hljs-attr,
            [data-bs-theme="dark"] .hljs-symbol,
            [data-bs-theme="dark"] .hljs-bullet,
            [data-bs-theme="dark"] .hljs-addition { color: #7ee787; }
            [data-bs-theme="dark"] .hljs-number,
            [data-bs-theme="dark"] .hljs-meta { color: #79c0ff; }
            [data-bs-theme="dark"] .hljs-title,
            [data-bs-theme="dark"] .hljs-section,
            [data-bs-theme="dark"] .hljs-selector-id { color: #f5c451; }
            [data-bs-theme="dark"] .hljs-type,
            [data-bs-theme="dark"] .hljs-attribute { color: #ffa657; }
            [data-bs-theme="dark"] .hljs-tag { color: #a5d6ff; }

            @media (prefers-reduced-motion: reduce) {
                * { transition: none !important; }
            }
        </style>
    </head>
    <body>
        <div class="container gfe-shell my-4">
            <div class="gfe-topbar">
                <a class="gfe-brand text-decoration-none" href="<?php echo GFE_URL; ?>">
                    <span class="gfe-brand-mark"><i class="fa-solid fa-hard-drive"></i></span>
                    <span>
                        <span class="gfe-title"><?php echo $siteName; ?></span>
                        <span class="gfe-subtitle d-block">Browse, search &amp; download files</span>
                    </span>
                </a>
                <div class="gfe-topbar-actions">
                    <div class="gfe-theme-switch" role="group" aria-label="Colour theme">
                        <button type="button" data-gfe-set="light" title="Light theme" aria-label="Light theme"><i class="fa-solid fa-sun"></i></button>
                        <button type="button" data-gfe-set="auto" title="Match system" aria-label="Match system theme"><i class="fa-solid fa-circle-half-stroke"></i></button>
                        <button type="button" data-gfe-set="dark" title="Dark theme" aria-label="Dark theme"><i class="fa-solid fa-moon"></i></button>
                    </div>
                </div>
            </div>
            <nav class="gfe-path" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php echo $breadcrumbs; ?>
                </ol>
            </nav>
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
                <i class="fa-solid fa-fw fa-link"></i>
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
        <script>hljs.highlightAll();</script>
        <script>
            (function () {
                var buttons = document.querySelectorAll('.gfe-theme-switch button[data-gfe-set]');
                window.gfeSyncTheme = function () {
                    var current = document.documentElement.getAttribute('data-gfe-theme') || 'auto';
                    buttons.forEach(function (btn) {
                        btn.setAttribute('aria-pressed', btn.getAttribute('data-gfe-set') === current ? 'true' : 'false');
                    });
                };
                buttons.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        window.gfeSetTheme(btn.getAttribute('data-gfe-set'));
                    });
                });
                window.gfeSyncTheme();

                document.querySelectorAll('.gfe-table tbody tr').forEach(function (row) {
                    var link = row.querySelector('a');
                    if (! link) {
                        return;
                    }
                    row.addEventListener('click', function (e) {
                        if (e.target.closest('a') || String(window.getSelection())) {
                            return;
                        }
                        if (e.metaKey || e.ctrlKey) {
                            window.open(link.href, '_blank');
                        } else {
                            window.location.href = link.href;
                        }
                    });
                });
            })();
        </script>
    </body>
</html>
    <?php
}
