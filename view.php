<?php

declare(strict_types=1);

define('GFE_START', microtime(true));

require 'config.php';
$settings = require 'settings.php';
require 'functions.php';

$file = urldecode(trim($_GET['file'] ?? ''));
if (! is_safe_path($file)) {
    display_error('Invalid directory');
}
$parts = explode('/', $file);
$file_name = (string) array_pop($parts);

$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

if ($file === '' || in_array($file, $settings['ignore_files'], true)) {
    display_error('Invalid directory');
}

if (in_array($file_ext, $settings['ignore_ext'], true)) {
    display_error('Invalid extension');
}

foreach ($settings['ignore_folders'] as $ignored_folder) {
    if (str_starts_with($file, $ignored_folder . '/')) {
        display_error('Invalid directory');
    }
}

$full_path = GFE_ROOT_DIR . '/' . $file;
if (! is_file($full_path)) {
    display_error('File does not exist');
}

// Confirm the resolved path stays inside the root, defeating symlinks that escape it.
$root_real = realpath(GFE_ROOT_DIR);
$full_path_real = realpath($full_path);
if ($root_real === false || $full_path_real === false || ! str_starts_with($full_path_real, $root_real . '/')) {
    display_error('Invalid directory');
}

$full_url = GFE_ROOT_URL . '/' . $file;
$full_url_href = GFE_ROOT_URL . '/' . implode('/', array_map('rawurlencode', explode('/', $file)));

function stream_download(string $path, string $filename): never
{
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: ' . content_disposition($filename));
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    header('Pragma: public');
    readfile($path);
    exit();
}

if (isset($_GET['dl']) && (int) $_GET['dl'] === 1) {
    stream_download($full_path, $file_name);
}

// Sort order mirrors the listing so Previous/Next step in the order the user chose.
$get_sort_order = trim($_GET['order'] ?? '') ?: GFE_DEFAULT_SORT_ORDER;
$sort_order = sort_direction($get_sort_order);
$get_sort_by = trim($_GET['by'] ?? '') ?: GFE_DEFAULT_SORT_BY;
$sort_by = sort_field($get_sort_by);

$breadcrumbs = breadcrumbs([
    'file' => $file,
    'file_name' => $file_name,
    'sort_by' => $get_sort_by,
    'sort_order' => $get_sort_order,
]);

// Kept sort-free so a file has a single clean permalink.
$canonical = url($file, 'file');

$parent_dir = implode('/', $parts);
$parent_prefix = $parent_dir !== '' ? $parent_dir . '/' : '';
$parent_path = $parent_dir !== '' ? GFE_ROOT_DIR . '/' . $parent_dir : GFE_ROOT_DIR;
$siblings = sort_entries(list_directory($parent_path, $settings, $parent_prefix)['files'], $sort_by, $sort_order);
$nav = sibling_nav($siblings, $file_name, $parent_prefix, $get_sort_by, $get_sort_order);

if (in_array($file_ext, $settings['text_ext'], true)) {
    $text_content = (string) file_get_contents($full_path);
    $lines = count_lines($text_content);
    $lines_text = $lines === 1 ? 'line' : 'lines';
    $text_size = format_size(strlen($text_content));
    $text_meta = meta_strip([
        ['icon' => 'fa-list-ol', 'text' => $lines . ' ' . $lines_text, 'href' => null],
        ['icon' => 'fa-hard-drive', 'text' => $text_size, 'href' => null],
    ]);
    // Drop a single trailing newline so the numbered gutter lines up with the rendered code.
    $code_display = str_ends_with($text_content, "\n") ? substr($text_content, 0, -1) : $text_content;
    $gutter = $lines > 0 ? implode("\n", range(1, $lines)) : '';
    ?>
    <?php template_header(' - Viewing text file - ' . $file_name, $breadcrumbs, $canonical); ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><?php echo esc($file_name); ?></span>
                    <button type="button" class="btn btn-sm btn-outline-primary gfe-copy-code" title="Copy to clipboard" aria-label="Copy to clipboard" hidden><i class="fa-solid fa-copy" aria-hidden="true"></i></button>
                </div>
                <div class="card-body p-0">
                    <div class="gfe-code">
                        <pre class="gfe-code-gutter" aria-hidden="true"><?php echo $gutter; ?></pre>
                        <pre class="gfe-code-body mb-0"><code><?php echo esc($code_display); ?></code></pre>
                    </div>
                </div>
                <?php echo $text_meta; ?>
                <?php echo viewer_footer($nav, url($file, 'download')); ?>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
} elseif (in_array($file_ext, $settings['image_ext'], true)) {
    $imagesize = @getimagesize($full_path);
    if ($imagesize === false) {
        display_error('File is not a valid image');
    }
    [$image_width, $image_height] = $imagesize;
    $image_meta = meta_strip(array_merge(
        [
            ['icon' => 'fa-ruler-combined', 'text' => (int) $image_width . ' × ' . (int) $image_height, 'href' => null],
            ['icon' => 'fa-hard-drive', 'text' => format_size((int) filesize($full_path)), 'href' => null],
        ],
        image_exif($full_path, $file_ext, $settings['date_format'])
    ));
    $image_name_escaped = esc($file_name);
    ?>
    <?php template_header(' - Viewing image - ' . $file_name, $breadcrumbs, $canonical, $full_url_href); ?>

            <div class="card">
                <div class="card-header"><?php echo $image_name_escaped; ?></div>
                <div class="card-body text-center">
                    <img class="img-fluid" src="<?php echo esc($full_url); ?>"
                         width="<?php echo (int) $image_width; ?>" height="<?php echo (int) $image_height; ?>"
                         alt="Viewing image - <?php echo $image_name_escaped; ?>">
                </div>
                <?php echo $image_meta; ?>
                <?php echo viewer_footer($nav, url($file, 'download')); ?>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
} elseif (
    $file_ext === 'pdf'
    || in_array($file_ext, $settings['video_ext'], true)
    || in_array($file_ext, $settings['audio_ext'], true)
) {
    $media = media_embed($file_ext, $full_url_href, url($file, 'download'), $settings);
    $media_size = format_size((int) filesize($full_path));
    $media_name_escaped = esc($file_name);
    ?>
    <?php template_header(' - Viewing ' . $media['label'] . ' - ' . $file_name, $breadcrumbs, $canonical); ?>

            <div class="card">
                <div class="card-header"><?php echo $media_name_escaped; ?></div>
                <div class="card-body <?php echo $media['class']; ?>"><?php echo $media['html']; ?></div>
                <?php echo meta_strip([['icon' => 'fa-hard-drive', 'text' => $media_size, 'href' => null]]); ?>
                <?php echo viewer_footer($nav, url($file, 'download')); ?>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
} else {
    $file_size = format_size((int) filesize($full_path));
    $file_name_escaped = esc($file_name);
    ?>
    <?php template_header(' - Viewing file - ' . $file_name, $breadcrumbs, $canonical); ?>

            <div class="card">
                <div class="card-header"><?php echo $file_name_escaped; ?></div>
                <div class="card-body p-0"><p class="gfe-embed-fallback">This file can&rsquo;t be previewed in the browser. Use the Download button below.</p></div>
                <?php echo meta_strip([['icon' => 'fa-hard-drive', 'text' => $file_size, 'href' => null]]); ?>
                <?php echo viewer_footer($nav, url($file, 'download')); ?>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
}
