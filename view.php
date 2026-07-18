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
if (str_contains($file, '../') || str_contains($file, './') || str_contains($file, '//')) {
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
    $download_filename = preg_replace('/\s+/', '_', $filename) ?? $filename;
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($download_filename) . '"');
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

### Display Text
if (in_array($file_ext, $settings['text_ext'], true)) {
    $lines = get_line_count($full_path);
    $lines_text = $lines === 1 ? 'Line' : 'Lines';
    $text_size = format_size((int) filesize($full_path));
    ?>
    <?php template_header(' - Viewing Text File - ' . $file_name, $breadcrumbs); ?>

            <div class="card">
                <div class="card-header"><?php echo htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="card-body">
                    <pre class="mb-0"><code><?php echo htmlspecialchars((string) file_get_contents($full_path), ENT_QUOTES, 'UTF-8'); ?></code></pre>
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
    $image_size = format_size((int) filesize($full_path));
    $image_name_escaped = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
    ?>
    <?php template_header(' - Viewing Image - ' . $file_name, $breadcrumbs); ?>

            <div class="card">
                <div class="card-header"><?php echo $image_name_escaped; ?></div>
                <div class="card-body text-center">
                    <img class="img-fluid" src="<?php echo htmlspecialchars($full_url, ENT_QUOTES, 'UTF-8'); ?>"
                         width="<?php echo (int) $image_width; ?>" height="<?php echo (int) $image_height; ?>"
                         alt="Viewing Image - <?php echo $image_name_escaped; ?>">
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Width: <?php echo (int) $image_width; ?>px</li>
                    <li class="list-group-item">Height: <?php echo (int) $image_height; ?>px</li>
                    <li class="list-group-item">Size: <?php echo $image_size; ?></li>
                </ul>
                <div class="card-footer text-center">
                    <a href="<?php echo url($file, 'download'); ?>" title="Download" class="btn btn-primary">Download</a>
                </div>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
### Display PDF
} elseif ($file_ext === 'pdf') {
    $pdf_size = format_size((int) filesize($full_path));
    $pdf_name_escaped = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
    ?>
    <?php template_header(' - Viewing PDF - ' . $file_name, $breadcrumbs); ?>

            <div class="card">
                <div class="card-header"><?php echo $pdf_name_escaped; ?></div>
                <div class="card-body p-0">
                    <object class="gfe-embed-pdf" data="<?php echo htmlspecialchars($full_url_href, ENT_QUOTES, 'UTF-8'); ?>" type="application/pdf">
                        <p class="gfe-embed-fallback">This PDF can&rsquo;t be displayed here. <a href="<?php echo htmlspecialchars($full_url_href, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Open it in a new tab</a> or <a href="<?php echo url($file, 'download'); ?>">download it</a>.</p>
                    </object>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Size: <?php echo $pdf_size; ?></li>
                </ul>
                <div class="card-footer text-center">
                    <a href="<?php echo url($file, 'download'); ?>" title="Download" class="btn btn-primary">Download</a>
                </div>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
### Display Video
} elseif (in_array($file_ext, $settings['video_ext'], true)) {
    $video_size = format_size((int) filesize($full_path));
    $video_name_escaped = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
    ?>
    <?php template_header(' - Viewing Video - ' . $file_name, $breadcrumbs); ?>

            <div class="card">
                <div class="card-header"><?php echo $video_name_escaped; ?></div>
                <div class="card-body text-center">
                    <video class="gfe-embed-video" src="<?php echo htmlspecialchars($full_url_href, ENT_QUOTES, 'UTF-8'); ?>" controls preload="metadata">Your browser cannot play this video.</video>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Size: <?php echo $video_size; ?></li>
                </ul>
                <div class="card-footer text-center">
                    <a href="<?php echo url($file, 'download'); ?>" title="Download" class="btn btn-primary">Download</a>
                </div>
            </div>
    <?php template_footer($full_url, $full_url_href); ?>
    <?php
### Display Audio
} elseif (in_array($file_ext, $settings['audio_ext'], true)) {
    $audio_size = format_size((int) filesize($full_path));
    $audio_name_escaped = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
    ?>
    <?php template_header(' - Viewing Audio - ' . $file_name, $breadcrumbs); ?>

            <div class="card">
                <div class="card-header"><?php echo $audio_name_escaped; ?></div>
                <div class="card-body text-center">
                    <audio class="gfe-embed-audio" src="<?php echo htmlspecialchars($full_url_href, ENT_QUOTES, 'UTF-8'); ?>" controls preload="metadata">Your browser cannot play this audio.</audio>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Size: <?php echo $audio_size; ?></li>
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
