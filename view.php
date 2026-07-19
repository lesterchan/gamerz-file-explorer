<?php

declare(strict_types=1);

define('GFE_START', microtime(true));

require 'config.php';
$settings = require 'settings.php';
require 'functions.php';

$file = urldecode(trim($_GET['file'] ?? ''));
if (! is_safe_path($file)) {
    display_error('Invalid Directory');
}
$parts = explode('/', $file);
$file_name = (string) array_pop($parts);

$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

if ($file === '' || in_array($file, $settings['ignore_files'], true)) {
    display_error('Invalid Directory');
}

if (in_array($file_ext, $settings['ignore_ext'], true)) {
    display_error('Invalid Extension');
}

foreach ($settings['ignore_folders'] as $ignored_folder) {
    if (str_starts_with($file, $ignored_folder . '/')) {
        display_error('Invalid Directory');
    }
}

$full_path = GFE_ROOT_DIR . '/' . $file;
if (! is_file($full_path)) {
    display_error('File Does Not Exist');
}

// Confirm the resolved path stays inside the root, defeating symlinks that escape it.
$root_real = realpath(GFE_ROOT_DIR);
$full_path_real = realpath($full_path);
if ($root_real === false || $full_path_real === false || ! str_starts_with($full_path_real, $root_real . '/')) {
    display_error('Invalid Directory');
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
    $lines_text = $lines === 1 ? 'Line' : 'Lines';
    $text_size = format_size(strlen($text_content));
    ?>
    <?php template_header(' - Viewing Text File - ' . $file_name, $breadcrumbs, $canonical); ?>

            <div class="card">
                <div class="card-header"><?php echo esc($file_name); ?></div>
                <div class="card-body">
                    <pre class="mb-0"><code><?php echo esc($text_content); ?></code></pre>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><?php echo $lines . ' ' . $lines_text; ?></li>
                    <li class="list-group-item">Size: <?php echo $text_size; ?></li>
                </ul>
                <div class="card-footer">
                    <div class="d-flex align-items-center" role="group" aria-label="File navigation">
                        <div class="flex-fill text-start"><?php echo $nav['prev']; ?></div>
                        <div class="flex-fill text-center"><a href="<?php echo esc(url($file, 'download')); ?>" title="Download" class="btn btn-primary">Download</a></div>
                        <div class="flex-fill text-end"><?php echo $nav['next']; ?></div>
                    </div>
                </div>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
} elseif (in_array($file_ext, $settings['image_ext'], true)) {
    $imagesize = @getimagesize($full_path);
    if ($imagesize === false) {
        display_error('File Is Not A Valid Image');
    }
    [$image_width, $image_height] = $imagesize;
    $image_facts = [
        'Width' => (int) $image_width . 'px',
        'Height' => (int) $image_height . 'px',
        'Size' => format_size((int) filesize($full_path)),
    ] + image_exif($full_path, $file_ext);
    $image_name_escaped = esc($file_name);
    ?>
    <?php template_header(' - Viewing Image - ' . $file_name, $breadcrumbs, $canonical, $full_url_href); ?>

            <div class="card">
                <div class="card-header"><?php echo $image_name_escaped; ?></div>
                <div class="card-body text-center">
                    <img class="img-fluid" src="<?php echo esc($full_url); ?>"
                         width="<?php echo (int) $image_width; ?>" height="<?php echo (int) $image_height; ?>"
                         alt="Viewing Image - <?php echo $image_name_escaped; ?>">
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($image_facts as $fact_label => $fact_value) : ?>
                    <li class="list-group-item"><?php echo esc($fact_label); ?>: <?php echo esc($fact_value); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="card-footer">
                    <div class="d-flex align-items-center" role="group" aria-label="File navigation">
                        <div class="flex-fill text-start"><?php echo $nav['prev']; ?></div>
                        <div class="flex-fill text-center"><a href="<?php echo esc(url($file, 'download')); ?>" title="Download" class="btn btn-primary">Download</a></div>
                        <div class="flex-fill text-end"><?php echo $nav['next']; ?></div>
                    </div>
                </div>
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
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Size: <?php echo $media_size; ?></li>
                </ul>
                <div class="card-footer">
                    <div class="d-flex align-items-center" role="group" aria-label="File navigation">
                        <div class="flex-fill text-start"><?php echo $nav['prev']; ?></div>
                        <div class="flex-fill text-center"><a href="<?php echo esc(url($file, 'download')); ?>" title="Download" class="btn btn-primary">Download</a></div>
                        <div class="flex-fill text-end"><?php echo $nav['next']; ?></div>
                    </div>
                </div>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
} else {
    $file_size = format_size((int) filesize($full_path));
    $file_name_escaped = esc($file_name);
    ?>
    <?php template_header(' - Viewing File - ' . $file_name, $breadcrumbs, $canonical); ?>

            <div class="card">
                <div class="card-header"><?php echo $file_name_escaped; ?></div>
                <div class="card-body p-0"><p class="gfe-embed-fallback">This file can&rsquo;t be previewed in the browser. Use the Download button below.</p></div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Size: <?php echo $file_size; ?></li>
                </ul>
                <div class="card-footer">
                    <div class="d-flex align-items-center" role="group" aria-label="File navigation">
                        <div class="flex-fill text-start"><?php echo $nav['prev']; ?></div>
                        <div class="flex-fill text-center"><a href="<?php echo esc(url($file, 'download')); ?>" title="Download" class="btn btn-primary">Download</a></div>
                        <div class="flex-fill text-end"><?php echo $nav['next']; ?></div>
                    </div>
                </div>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
}
