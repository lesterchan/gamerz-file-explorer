<?php
### Require Config, Setting And Function Files
require 'config.php';
require 'settings.php';
require 'functions.php';

### Start Timer
start_timer();

### Get And Check File Path
$file = ! empty($_GET['file']) ? urldecode(stripslashes(trim($_GET['file']))) : '';
if (strpos($file, '../') !== false || strpos($file, './') !== false || strpos($file, '//') !== false) {
    display_error('Invalid Directory');
}
$temp = explode('/', $file);
$file_name = $temp[count($temp) - 1];

### Get File Extension
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

### Check Whether File Is In The Ignore Files
if (in_array($file, $ignore_files, true)) {
    display_error('Invalid Directory');
}

### Check Whether Extension Is In The Ignore Extensions
if (in_array($file_ext, $ignore_ext, true)) {
    display_error('Invalid Extension');
}

### Check Whether File Exists
if (! is_file(GFE_ROOT_DIR.'/'.$file)) {
    display_error('File Does Not Exist');
}

### Full URL
$full_url = GFE_ROOT_URL.'/'.$file;

### If User Wants To Download Text Or Image
if (! empty($_GET['dl']) && (int) $_GET['dl'] === 1) {
    $download_filename = $file_name;
    $download_filename = preg_replace('/\s+/', '_', $download_filename);
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream');
    header('Content-Type: application/download');
    header('Content-Disposition: attachment; filename='.basename(GFE_ROOT_DIR.'/'.$download_filename).';');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: '.filesize(GFE_ROOT_DIR.'/'.$file));
    @readfile(GFE_ROOT_DIR.'/'.$file);
    exit();
}

### Display Text
if (in_array($file_ext, $text_ext, true)) {
    // Get Number Of Lines In Text File
    $lines = get_line_count(GFE_ROOT_DIR.'/'.$file);
    $lines_text = ($lines > 1 ? 'Lines' : 'Line');
    $text_size = format_size(filesize(GFE_ROOT_DIR.'/'.$file));
    ?>
    <?php template_header(' - Viewing Text File - '.$file_name); ?>

    <div class="card">
        <div class="card-header">
            <?php echo $file_name; ?>
        </div>
        <div class="card-block">
            <pre><code><?php echo htmlspecialchars(file_get_contents(GFE_ROOT_DIR.'/'.$file)); ?></code></pre>
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><?php echo $lines.' '.$lines_text; ?></li>
            <li class="list-group-item">Size: <?php echo $text_size; ?></li>
        </ul>
        <div class="card-footer text-center">
            <a href="<?php echo url($file, 'download'); ?>" title="Download" class="btn btn-primary">Download</a>
        </div>
    </div>
    <?php template_footer(); ?>
    <?php
### Dispay Image
} elseif (in_array($file_ext, $image_ext, true)) {
    $temp_getimagesize = getimagesize(GFE_ROOT_DIR.'/'.$file);
    if (! $temp_getimagesize) {
        display_error('File Is Not A Valid Image');
    }
    list($image_width, $image_height, $image_type, $image_attr) = $temp_getimagesize;
    $image_size = format_size(filesize(GFE_ROOT_DIR.'/'.$file));
    ?>
    <?php template_header(' - Viewing Image - '.$file_name); ?>

    <div class="card">
        <div class="card-header">
            <?php echo $file_name; ?>
        </div>
        <div class="card-block text-center">
            <img class="img-responsive" src="<?php echo GFE_ROOT_URL.'/'.$file; ?>" <?php echo $image_attr; ?>
                 alt="Viewing Image - <?php echo $file_name; ?>">
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">Width: <?php echo $image_width; ?>px</li>
            <li class="list-group-item">Height: <?php echo $image_height; ?>px</li>
            <li class="list-group-item">Size: <?php echo $image_size; ?></li>
        </ul>
        <div class="card-footer text-center">
            <a href="<?php echo url($file, 'download'); ?>" title="Download" class="btn btn-primary">Download</a>
        </div>
    </div>

    <?php template_footer(); ?>
    <?php
### Display Download
} else {
    $download_filename = $file_name;
    $download_filename = preg_replace('/\s+/', '_', $download_filename);
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream');
    header('Content-Type: application/download');
    header('Content-Disposition: attachment; filename='.basename(GFE_ROOT_DIR.'/'.$download_filename).';');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: '.filesize(GFE_ROOT_DIR.'/'.$file));
    @readfile(GFE_ROOT_DIR.'/'.$file);
    exit();
}
