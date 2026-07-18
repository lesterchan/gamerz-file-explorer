<?php

declare(strict_types=1);

/**
 * Build a deterministic fixture content tree used by both the unit bootstrap and
 * the integration harness. Returns the created root directory path.
 */
function gfe_make_content(?string $root = null): string
{
    $root ??= sys_get_temp_dir() . '/gfe-fixture-' . bin2hex(random_bytes(6));
    gfe_rrmdir($root);
    @mkdir($root . '/Sub Folder', 0777, true);
    @mkdir($root . '/resources', 0777, true);
    // An empty directory (covers the "No files found" listing branch).
    @mkdir($root . '/Empty', 0777, true);
    // A dangling symlink — exists but is neither a file nor a directory.
    @symlink($root . '/does-not-exist', $root . '/dangling.link');
    // Name is not on the ignore list, but the extension is ($ignore_ext = htaccess).
    file_put_contents($root . '/backup.htaccess', "deny\n");

    // Text file with several lines (covers get_line_count multi-line path).
    file_put_contents($root . '/notes.txt', "line one\nline two\nline three\n");
    // Source file (highlight.js text view).
    file_put_contents($root . '/code.php', "<?php\necho 'hi';\n");
    // Filename with spaces — the nice-URL regression case.
    file_put_contents($root . '/My File.txt', "spaced filename\n");
    // A tiny valid 1x1 PNG (image view branch).
    file_put_contents($root . '/pixel.png', base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMEAQDdN8/vAAAAAElFTkSuQmCC'
    ));
    // A file that is not a real image but has an image extension (getimagesize-false branch).
    file_put_contents($root . '/broken.png', "not really a png\n");
    // Binary-ish file with an unknown extension (force-download branch).
    file_put_contents($root . '/archive.bin', "\x00\x01\x02binary\x03\x04");
    // A PDF (inline iframe embed branch).
    file_put_contents($root . '/report.pdf', "%PDF-1.4 fake\n");
    // A video and audio file (inline <video>/<audio> embed branches; content is not parsed).
    file_put_contents($root . '/clip.mp4', "fake mp4 bytes\n");
    file_put_contents($root . '/song.mp3', "fake mp3 bytes\n");
    // A file whose extension is ignored ($ignore_ext = htaccess).
    file_put_contents($root . '/.htaccess', "deny\n");
    // A file whose name is on the ignore list.
    file_put_contents($root . '/phpinfo.php', "<?php phpinfo();\n");
    // Nested file inside a normal sub-folder.
    file_put_contents($root . '/Sub Folder/inner.txt', "inner\n");
    // File inside an ignored folder ($ignore_folders contains resources).
    file_put_contents($root . '/resources/icon.png', "icon\n");

    return $root;
}

function gfe_rrmdir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    $items = scandir($dir) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        is_dir($path) ? gfe_rrmdir($path) : @unlink($path);
    }
    @rmdir($dir);
}
