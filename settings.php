<?php

declare(strict_types=1);

### GaMerZ File Explorer Version (Please Do Not Edit This)
define('GFE_VERSION', '3.2.0');

### Date Format For Listing Rows And EXIF Capture Dates (PHP date() Syntax)
$date_format = 'j M Y, H:i';

### What Files Not To Show In The List
$ignore_files = [
    '.DS_Store',
    '.editorconfig',
    '.gitignore',
    '.htaccess',
    '404.php',
    'AGENTS.md',
    'CLAUDE.md',
    'composer.json',
    'composer.lock',
    'config.php',
    'Dockerfile',
    'functions.php',
    'index.php',
    'LICENSE.md',
    'phpcs.xml.dist',
    'phpstan.neon.dist',
    'phpunit.xml.dist',
    'README.md',
    'robots.txt',
    'search.php',
    'settings.php',
    'view.php',
];

### What Extensions Not To Show In The List
$ignore_ext = ['htaccess'];

### What Folders Not To Show In The List
$ignore_folders = [
    '.github',
    '.phpunit.cache',
    '.well-known',
    'build',
    'cgi-bin',
    'resources',
    'tests',
    'uploads',
    'vendor',
];

### File Extension To Be Parsed As Text
$text_ext = [
    'conf',
    'css',
    'csv',
    'htm',
    'html',
    'ini',
    'js',
    'json',
    'less',
    'md',
    'php',
    'scss',
    'sh',
    'sql',
    'svg',
    'ts',
    'txt',
    'xml',
    'yaml',
    'yml',
];

### File Extension To Be Parsed As Image
$image_ext = [
    'avif',
    'bmp',
    'gif',
    'jpeg',
    'jpg',
    'png',
    'webp',
];

### File Extension To Be Embedded As Video (Browser-Playable Formats Only)
$video_ext = [
    'mov',
    'mp4',
    'webm',
];

### File Extension To Be Embedded As Audio (Browser-Playable Formats Only)
$audio_ext = [
    'flac',
    'm4a',
    'mp3',
    'wav',
];

### File Extensions Description => [Label, Font Awesome 6 Icon Class]
$extensions = [
    '7z' => ['7-Zip Archive', 'fa-solid fa-file-zipper'],
    'ai' => ['Adobe Illustrator Artwork', 'fa-regular fa-file'],
    'avi' => ['AVI Video', 'fa-solid fa-file-video'],
    'avif' => ['AVIF Image', 'fa-solid fa-file-image'],
    'bmp' => ['Bitmap Image', 'fa-solid fa-file-image'],
    'conf' => ['Configuration File', 'fa-solid fa-file-code'],
    'css' => ['Cascading Style Sheet', 'fa-solid fa-file-code'],
    'csv' => ['CSV Document', 'fa-solid fa-file-csv'],
    'doc' => ['Microsoft Word Document', 'fa-solid fa-file-word'],
    'docx' => ['Microsoft Word Document', 'fa-solid fa-file-word'],
    'exe' => ['Application', 'fa-regular fa-file'],
    'flac' => ['FLAC Audio', 'fa-solid fa-file-audio'],
    'gif' => ['GIF Image', 'fa-solid fa-file-image'],
    'gz' => ['Gzip Archive', 'fa-solid fa-file-zipper'],
    'heic' => ['HEIC Image', 'fa-solid fa-file-image'],
    'htm' => ['HTML Document', 'fa-solid fa-file-code'],
    'html' => ['HTML Document', 'fa-solid fa-file-code'],
    'ico' => ['Icon', 'fa-solid fa-file-image'],
    'ini' => ['Configuration File', 'fa-solid fa-file-code'],
    'jpeg' => ['JPEG Image', 'fa-solid fa-file-image'],
    'jpg' => ['JPEG Image', 'fa-solid fa-file-image'],
    'js' => ['JavaScript File', 'fa-solid fa-file-code'],
    'json' => ['JSON Document', 'fa-solid fa-file-code'],
    'less' => ['LESS Stylesheet', 'fa-solid fa-file-code'],
    'm4a' => ['MPEG-4 Audio', 'fa-solid fa-file-audio'],
    'md' => ['Markdown Document', 'fa-solid fa-file-lines'],
    'mdb' => ['Microsoft Access Database', 'fa-regular fa-file'],
    'mid' => ['MIDI Music', 'fa-solid fa-file-audio'],
    'mkv' => ['Matroska Video', 'fa-solid fa-file-video'],
    'mov' => ['QuickTime Video', 'fa-solid fa-file-video'],
    'mp3' => ['MP3 Audio', 'fa-solid fa-file-audio'],
    'mp4' => ['MP4 Video', 'fa-solid fa-file-video'],
    'mpeg' => ['MPEG Video', 'fa-solid fa-file-video'],
    'mpg' => ['MPEG Video', 'fa-solid fa-file-video'],
    'msi' => ['Windows Installer Package', 'fa-regular fa-file'],
    'odt' => ['OpenDocument Text', 'fa-solid fa-file-word'],
    'otf' => ['OpenType Font', 'fa-regular fa-file'],
    'pdf' => ['PDF Document', 'fa-solid fa-file-pdf'],
    'php' => ['PHP File', 'fa-solid fa-file-code'],
    'png' => ['PNG Image', 'fa-solid fa-file-image'],
    'ppt' => ['Microsoft PowerPoint Presentation', 'fa-solid fa-file-powerpoint'],
    'pptx' => ['Microsoft PowerPoint Presentation', 'fa-solid fa-file-powerpoint'],
    'psd' => ['Adobe Photoshop Image', 'fa-regular fa-file'],
    'ra' => ['Real Media Audio', 'fa-solid fa-file-audio'],
    'rar' => ['RAR Archive', 'fa-solid fa-file-zipper'],
    'rm' => ['Real Media Video', 'fa-solid fa-file-video'],
    'rtf' => ['Rich Text Document', 'fa-solid fa-file-lines'],
    'scss' => ['SCSS Stylesheet', 'fa-solid fa-file-code'],
    'sh' => ['Shell Script', 'fa-solid fa-file-code'],
    'sql' => ['SQL Script', 'fa-solid fa-file-code'],
    'svg' => ['SVG Image', 'fa-solid fa-file-code'],
    'swf' => ['Flash Movie', 'fa-solid fa-file-video'],
    'tar' => ['Tar Archive', 'fa-solid fa-file-zipper'],
    'tif' => ['TIFF Image', 'fa-solid fa-file-image'],
    'tiff' => ['TIFF Image', 'fa-solid fa-file-image'],
    'ts' => ['TypeScript File', 'fa-solid fa-file-code'],
    'ttf' => ['TrueType Font', 'fa-regular fa-file'],
    'txt' => ['Text Document', 'fa-solid fa-file-lines'],
    'wav' => ['Waveform Audio', 'fa-solid fa-file-audio'],
    'webm' => ['WebM Video', 'fa-solid fa-file-video'],
    'webp' => ['WebP Image', 'fa-solid fa-file-image'],
    'wma' => ['Windows Media Audio', 'fa-solid fa-file-audio'],
    'wmv' => ['Windows Media Video', 'fa-solid fa-file-video'],
    'woff' => ['Web Font', 'fa-regular fa-file'],
    'woff2' => ['Web Font', 'fa-regular fa-file'],
    'xls' => ['Microsoft Excel Worksheet', 'fa-solid fa-file-excel'],
    'xlsx' => ['Microsoft Excel Worksheet', 'fa-solid fa-file-excel'],
    'xml' => ['XML Document', 'fa-solid fa-file-code'],
    'yaml' => ['YAML Document', 'fa-solid fa-file-code'],
    'yml' => ['YAML Document', 'fa-solid fa-file-code'],
    'zip' => ['ZIP Archive', 'fa-solid fa-file-zipper'],
];

### Append per-site ignores from config.php so this file stays identical across deployments.
$ignore_files = array_values(array_unique(array_merge($ignore_files, defined('GFE_IGNORE_FILES') ? GFE_IGNORE_FILES : [])));
$ignore_ext = array_values(array_unique(array_merge($ignore_ext, defined('GFE_IGNORE_EXT') ? GFE_IGNORE_EXT : [])));
$ignore_folders = array_values(array_unique(array_merge($ignore_folders, defined('GFE_IGNORE_FOLDERS') ? GFE_IGNORE_FOLDERS : [])));

return [
    'date_format' => $date_format,
    'ignore_files' => $ignore_files,
    'ignore_ext' => $ignore_ext,
    'ignore_folders' => $ignore_folders,
    'text_ext' => $text_ext,
    'image_ext' => $image_ext,
    'video_ext' => $video_ext,
    'audio_ext' => $audio_ext,
    'extensions' => $extensions,
];
