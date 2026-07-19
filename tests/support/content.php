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
    // A VCS directory — the deployment is served from a git checkout, so it must be
    // skipped by the walkers (not listed, searched, or counted toward folder sizes).
    @mkdir($root . '/.git', 0777, true);
    file_put_contents($root . '/.git/HEAD', "ref: refs/heads/master\n");
    // An empty directory (covers the "No files found" listing branch).
    @mkdir($root . '/Empty', 0777, true);
    // The two symlinks below need OS symlink support (the CI runs on Linux/macOS). If
    // symlink() silently fails, their scenarios mis-report as "File Does Not Exist"
    // rather than exercising the broken-symlink and containment branches they target.
    // A dangling symlink — exists but is neither a file nor a directory.
    @symlink($root . '/does-not-exist', $root . '/dangling.link');
    // A symlink whose target resolves outside the root — the realpath containment
    // check must reject it even though is_file() follows it to a real file.
    @symlink(dirname(__DIR__, 2) . '/composer.json', $root . '/escape.txt');
    // Name is not on the ignore list, but the extension is ($ignore_ext = htaccess).
    file_put_contents($root . '/backup.htaccess', "deny\n");

    // Text file with several lines (covers get_line_count multi-line path).
    file_put_contents($root . '/notes.txt', "line one\nline two\nline three\n");
    // Empty text file (covers the viewer's zero-line gutter and no-trailing-newline paths).
    file_put_contents($root . '/empty.txt', '');
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
    // A valid JPEG carrying an EXIF block (Make, Model = "GFE Cam", DateTimeOriginal)
    // so the image EXIF panel exercises all three friendly labels.
    file_put_contents($root . '/photo.jpg', base64_decode(
        '/9j/4QBoRXhpZgAASUkqAAgAAAADAA8BAgAEAAAAR0ZFABABAgAIAAAAMgAAAGmHBAABAAAA'
        . 'OgAAAAAAAABHRkUgQ2FtAAEAA5ACABQAAABMAAAAAAAAADIwMjY6MDc6MTggMTI6MzQ6NTYA'
        . '/+AAEEpGSUYAAQEBAGAAYAAA//4AO0NSRUFUT1I6IGdkLWpwZWcgdjEuMCAodXNpbmcgSUpH'
        . 'IEpQRUcgdjgwKSwgcXVhbGl0eSA9IDgwCv/bAEMABgQFBgUEBgYFBgcHBggKEAoKCQkKFA4P'
        . 'DBAXFBgYFxQWFhodJR8aGyMcFhYgLCAjJicpKikZHy0wLSgwJSgpKP/bAEMBBwcHCggKEwoK'
        . 'EygaFhooKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgo'
        . 'KP/AABEIAAIAAgMBIgACEQEDEQH/xAAfAAABBQEBAQEBAQAAAAAAAAAAAQIDBAUGBwgJCgv/'
        . 'xAC1EAACAQMDAgQDBQUEBAAAAX0BAgMABBEFEiExQQYTUWEHInEUMoGRoQgjQrHBFVLR8CQz'
        . 'YnKCCQoWFxgZGiUmJygpKjQ1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5'
        . 'eoOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna'
        . '4eLj5OXm5+jp6vHy8/T19vf4+fr/xAAfAQADAQEBAQEBAQEBAAAAAAAAAQIDBAUGBwgJCgv/'
        . 'xAC1EQACAQIEBAMEBwUEBAABAncAAQIDEQQFITEGEkFRB2FxEyIygQgUQpGhscEJIzNS8BVi'
        . 'ctEKFiQ04SXxFxgZGiYnKCkqNTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4'
        . 'eXqCg4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY'
        . '2dri4+Tl5ufo6ery8/T19vf4+fr/2gAMAwEAAhEDEQA/APlSiiigD//Z'
    ));
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
    file_put_contents($root . '/config.php', "<?php // deployment config\n");
    // Nested file inside a normal sub-folder.
    file_put_contents($root . '/Sub Folder/inner.txt', "inner\n");
    // File inside an ignored folder ($ignore_folders contains resources).
    file_put_contents($root . '/resources/icon.png', "icon\n");

    // Deployment-specific ignores contributed by config.php (GFE_IGNORE_* constants),
    // merged into the settings.php baseline: an ignored filename, an ignored extension,
    // and a folder ignored by name. All three must be hidden, uncounted and unviewable —
    // proving the config.php -> settings.php merge reaches every ignore-aware code path.
    @mkdir($root . '/private', 0777, true);
    file_put_contents($root . '/private/hidden.txt', "hidden\n");
    file_put_contents($root . '/secret-note.txt', "top secret\n");
    file_put_contents($root . '/draft.bak', "backup bytes\n");

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
