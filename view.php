<?php

declare(strict_types=1);

### Start Timer
define('GFE_START', microtime(true));

### Require Config, Setting And Function Files
require 'config.php';
$settings = require 'settings.php';
require 'functions.php';

### Get And Check File Path
$file = urldecode(trim($_GET['file'] ?? ''));
if (! is_safe_path($file)) {
    display_error('Invalid Directory');
}
$parts = explode('/', $file);
$file_name = (string) array_pop($parts);

### Get File Extension
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

### Check Whether File Is In The Ignore Files
if ($file === '' || in_array($file, $settings['ignore_files'], true)) {
    display_error('Invalid Directory');
}

### Check Whether Extension Is In The Ignore Extensions
if (in_array($file_ext, $settings['ignore_ext'], true)) {
    display_error('Invalid Extension');
}

### Check Whether File Exists
$full_path = GFE_ROOT_DIR . '/' . $file;
if (! is_file($full_path)) {
    display_error('File Does Not Exist');
}

### Full URL (readable display) And Its URL-Encoded, Directly-Clickable Href
$full_url = GFE_ROOT_URL . '/' . $file;
$full_url_href = GFE_ROOT_URL . '/' . implode('/', array_map('rawurlencode', explode('/', $file)));

### Stream A File To The Browser As A Download
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

### If User Wants To Download
if (isset($_GET['dl']) && (int) $_GET['dl'] === 1) {
    stream_download($full_path, $file_name);
}

$breadcrumbs = breadcrumbs(['file' => $file, 'file_name' => $file_name]);

### Canonical (Nice-URL) Permalink For This File's Viewing Page
$canonical = url($file, 'file');

### Display Text
if (in_array($file_ext, $settings['text_ext'], true)) {
    $lines = get_line_count($full_path);
    $lines_text = $lines === 1 ? 'Line' : 'Lines';
    $text_size = format_size((int) filesize($full_path));
    ?>
    <?php template_header(' - Viewing Text File - ' . $file_name, $breadcrumbs, $canonical); ?>

            <div class="card">
                <div class="card-header"><?php echo esc($file_name); ?></div>
                <div class="card-body">
                    <pre class="mb-0"><code><?php echo esc((string) file_get_contents($full_path)); ?></code></pre>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><?php echo $lines . ' ' . $lines_text; ?></li>
                    <li class="list-group-item">Size: <?php echo $text_size; ?></li>
                </ul>
                <div class="card-footer text-center">
                    <a href="<?php echo url($file, 'download'); ?>" title="Download" class="btn btn-primary">Download</a>
                </div>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
### Display Image
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
                <div class="card-footer text-center">
                    <a href="<?php echo url($file, 'download'); ?>" title="Download" class="btn btn-primary">Download</a>
                </div>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
### Display Inline Media (PDF, Video Or Audio)
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
                <div class="card-footer text-center">
                    <a href="<?php echo url($file, 'download'); ?>" title="Download" class="btn btn-primary">Download</a>
                </div>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
### Otherwise Force A Download
} else {
    stream_download($full_path, $file_name);
}
