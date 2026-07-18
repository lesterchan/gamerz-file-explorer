<?php

declare(strict_types=1);

namespace GfeTests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the non-exiting helpers in functions.php.
 *
 * @runTestsInSeparateProcesses disabled — these functions are pure or filesystem-only.
 */
final class FunctionsTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $settings;

    protected function setUp(): void
    {
        $this->settings = $GLOBALS['gfe_settings'];
    }

    private function root(): string
    {
        return (string) getenv('GFE_TEST_ROOT');
    }

    public function testFormatSizeCoversEveryUnit(): void
    {
        $this->assertSame('2GB', format_size(2 * 1073741824));
        $this->assertSame('3MB', format_size(3 * 1048576));
        $this->assertSame('4KB', format_size(4 * 1024));
        $this->assertSame('512b', format_size(512));
    }

    public function testDirSize(): void
    {
        $root = $this->root();
        $this->assertGreaterThan(0, dir_size($root, $this->settings));
        $this->assertSame(0, dir_size($root . '/does-not-exist', $this->settings));

        // A folder with only plain files totals their bytes exactly.
        $this->assertSame(
            (int) filesize($root . '/Sub Folder/inner.txt'),
            dir_size($root . '/Sub Folder', $this->settings)
        );

        // Ignored files, ignored extensions and ignored folders are excluded from the size:
        // the ignore-aware total equals a naive walk minus exactly those bytes.
        $naive = static function (string $dir) use (&$naive): int {
            $total = 0;
            foreach (scandir($dir) ?: [] as $item) {
                // Mirror dir_size's structural skips so the only difference is the ignore list.
                if ($item === '.' || $item === '..' || $item === '.git' || $item === '.svn') {
                    continue;
                }
                $path = $dir . '/' . $item;
                if (is_file($path)) {
                    $total += (int) filesize($path);
                } elseif (is_dir($path)) {
                    /** @var callable(string): int $naive */
                    $total += $naive($path);
                }
            }
            return $total;
        };
        $ignoredBytes = (int) filesize($root . '/config.php')       // ignored filename
            + (int) filesize($root . '/.htaccess')                  // ignored extension
            + (int) filesize($root . '/backup.htaccess')            // ignored extension
            + (int) filesize($root . '/resources/icon.png');        // inside an ignored folder
        $this->assertSame($naive($root) - $ignoredBytes, dir_size($root, $this->settings));
    }

    public function testListFilesReturnsVisibleFilesAndHonoursIgnores(): void
    {
        $files = list_files($this->root(), $this->settings);
        $names = array_column($files, 'name');

        $this->assertContains('notes.txt', $names);
        $this->assertContains('My File.txt', $names);
        $this->assertContains('inner.txt', $names, 'recurses into sub-folders');
        $this->assertNotContains('.htaccess', $names, 'ignored extension');
        $this->assertNotContains('config.php', $names, 'ignored filename');
        $this->assertNotContains('archive.bin', $names, 'unmapped extension');
        $this->assertNotContains('icon.png', $names, 'inside ignored folder');
        $this->assertNotContains('HEAD', $names, 'inside a skipped VCS folder');
        $this->assertSame([], list_files($this->root() . '/nope', $this->settings));

        // The optional filter is applied during the walk.
        $onlyTxt = list_files($this->root(), $this->settings, static fn (array $f): bool => ($f['ext'] ?? '') === 'txt');
        $txtNames = array_column($onlyTxt, 'name');
        $this->assertContains('notes.txt', $txtNames);
        $this->assertNotContains('pixel.png', $txtNames, 'the filter excludes non-matching files');
    }

    public function testListDirectories(): void
    {
        $dirs = list_directories($this->root(), $this->settings);
        $this->assertContains('Sub Folder', $dirs);
        $this->assertNotContains('resources', $dirs, 'ignored folder');
        $this->assertNotContains('.git', $dirs, 'skipped VCS folder');
        $this->assertSame([], list_directories($this->root() . '/nope', $this->settings));
    }

    public function testListDirectoryHappyPath(): void
    {
        $listing = list_directory($this->root(), $this->settings, '');
        $fileNames = array_column($listing['files'], 'name');
        $dirNames = array_column($listing['directories'], 'name');

        $this->assertContains('notes.txt', $fileNames);
        $this->assertNotContains('config.php', $fileNames, 'ignored filename');
        $this->assertNotContains('.htaccess', $fileNames, 'ignored extension');
        $this->assertContains('Sub Folder', $dirNames);
        $this->assertNotContains('resources', $dirNames, 'ignored folder');
    }

    public function testEsc(): void
    {
        $this->assertSame('&lt;a&gt; &quot;x&quot; &amp; &#039;y&#039;', esc('<a> "x" & \'y\''));
    }

    public function testIsSafePath(): void
    {
        $this->assertTrue(is_safe_path(''), 'the empty (home) path is allowed');
        $this->assertTrue(is_safe_path('Sub Folder/inner.txt'));
        $this->assertFalse(is_safe_path('.'));
        $this->assertFalse(is_safe_path('..'), 'a bare parent segment is rejected');
        $this->assertFalse(is_safe_path('a/../b'));
        $this->assertFalse(is_safe_path('a//b'), 'an empty segment is rejected');
        $this->assertFalse(is_safe_path("a\0b"), 'a null byte is rejected');
    }

    public function testContentDispositionEncodesFilename(): void
    {
        // Spaces collapse to underscores in the ASCII fallback; the real name is in filename*.
        $this->assertSame(
            'attachment; filename="My_File.txt"; filename*=UTF-8\'\'My%20File.txt',
            content_disposition('My File.txt')
        );
        // Quotes, backslashes and control characters cannot break out of the quoted fallback.
        $header = content_disposition("a\"b\\c\nd.txt");
        $this->assertStringContainsString('filename="a_b_c_d.txt"', $header);
        // A non-ASCII name survives via the RFC 5987 copy and is sanitised in the fallback.
        $unicode = content_disposition('café.pdf');
        $this->assertStringContainsString("filename*=UTF-8''caf%C3%A9.pdf", $unicode);
        $this->assertStringContainsString('filename="caf__.pdf"', $unicode);
        // A leading path is reduced to the basename.
        $this->assertStringContainsString('filename="b.txt"', content_disposition('a/b.txt'));
    }

    public function testImageExif(): void
    {
        // A non-image extension short-circuits before touching the exif extension.
        $this->assertSame([], image_exif($this->root() . '/pixel.png', 'png'));

        if (! function_exists('exif_read_data')) {
            $this->markTestSkipped('The exif extension is not loaded.');
        }
        // A file with no EXIF header yields nothing (exif_read_data returns false).
        $this->assertSame([], image_exif($this->root() . '/notes.txt', 'jpg'));
        // Make, Model and DateTimeOriginal each map to a friendly label, in that order.
        $this->assertSame(
            ['Camera' => 'GFE', 'Model' => 'GFE Cam', 'Taken' => '2026:07:18 12:34:56'],
            image_exif($this->root() . '/photo.jpg', 'jpg')
        );
    }

    public function testFileIcon(): void
    {
        $this->assertSame('fa-solid fa-file-lines', file_icon('txt', $this->settings['extensions']));
        $this->assertSame('fa-regular fa-file', file_icon('unknownext', $this->settings['extensions']));
    }

    public function testSortEntries(): void
    {
        $entries = [
            ['name' => 'banana', 'size' => 30, 'date' => 3],
            ['name' => 'apple', 'size' => 10, 'date' => 1],
            ['name' => 'Cherry', 'size' => 20, 'date' => 2],
        ];

        $byNameAsc = sort_entries($entries, 'name', SORT_ASC);
        $this->assertSame(['apple', 'banana', 'Cherry'], array_column($byNameAsc, 'name'));

        $byNameDesc = sort_entries($entries, 'name', SORT_DESC);
        $this->assertSame(['Cherry', 'banana', 'apple'], array_column($byNameDesc, 'name'));

        $bySizeAsc = sort_entries($entries, 'size', SORT_ASC);
        $this->assertSame([10, 20, 30], array_column($bySizeAsc, 'size'));

        // Sorting by type uses a case-insensitive string compare over multiple entries.
        $byType = sort_entries([
            ['name' => 'a', 'type' => 'PNG Image', 'size' => 1, 'date' => 1],
            ['name' => 'b', 'type' => 'gzip Archive', 'size' => 1, 'date' => 1],
            ['name' => 'c', 'type' => 'JPEG Image', 'size' => 1, 'date' => 1],
        ], 'type', SORT_ASC);
        $this->assertSame(['gzip Archive', 'JPEG Image', 'PNG Image'], array_column($byType, 'type'));

        // A missing sort field falls back to '' for every element, so the comparator runs
        // over 2+ entries without error and leaves their relative order stable.
        $missing = sort_entries([
            ['name' => 'first', 'size' => 1, 'date' => 1],
            ['name' => 'second', 'size' => 2, 'date' => 2],
        ], 'type', SORT_ASC);
        $this->assertSame(['first', 'second'], array_column($missing, 'name'));
    }

    public function testSiblingNav(): void
    {
        $files = [
            ['name' => 'a.txt', 'ext' => 'txt', 'type' => 'Text', 'size' => 1, 'date' => 1],
            ['name' => 'b.txt', 'ext' => 'txt', 'type' => 'Text', 'size' => 1, 'date' => 2],
            ['name' => 'c.txt', 'ext' => 'txt', 'type' => 'Text', 'size' => 1, 'date' => 3],
        ];

        // A middle file links to both neighbours.
        $middle = sibling_nav($files, 'b.txt', '');
        $this->assertStringContainsString('href="' . url('a.txt', 'file') . '"', $middle['prev']);
        $this->assertStringContainsString('Previous', $middle['prev']);
        $this->assertStringContainsString('href="' . url('c.txt', 'file') . '"', $middle['next']);
        $this->assertStringContainsString('Next', $middle['next']);

        // The first file has no previous — a disabled placeholder — but still links forward.
        $first = sibling_nav($files, 'a.txt', '');
        $this->assertStringContainsString('disabled', $first['prev']);
        $this->assertStringNotContainsString('href=', $first['prev']);
        $this->assertStringContainsString('href="' . url('b.txt', 'file') . '"', $first['next']);

        // The last file has no next — a disabled placeholder — but still links back.
        $last = sibling_nav($files, 'c.txt', '');
        $this->assertStringContainsString('href="' . url('b.txt', 'file') . '"', $last['prev']);
        $this->assertStringContainsString('disabled', $last['next']);
        $this->assertStringNotContainsString('href=', $last['next']);

        // The folder prefix is prepended to the neighbour's viewing link.
        $nested = sibling_nav($files, 'a.txt', 'Docs/');
        $this->assertStringContainsString('href="' . url('Docs/b.txt', 'file') . '"', $nested['next']);

        // A lone file yields no controls; an unknown file yields none either.
        $this->assertSame(['prev' => '', 'next' => ''], sibling_nav([$files[0]], 'a.txt', ''));
        $this->assertSame(['prev' => '', 'next' => ''], sibling_nav($files, 'missing.txt', ''));
    }

    public function testSortField(): void
    {
        $this->assertSame('name', sort_field('name'));
        $this->assertSame('size', sort_field('size'));
        $this->assertSame('type', sort_field('type'));
        $this->assertSame('date', sort_field('date'));
        $this->assertSame('date', sort_field('bogus'), 'an unknown column falls back to date');
        $this->assertSame('date', sort_field(''));
    }

    public function testSortDirection(): void
    {
        $this->assertSame(SORT_ASC, sort_direction('asc'));
        $this->assertSame(SORT_DESC, sort_direction('desc'));
        $this->assertSame(SORT_DESC, sort_direction(''), 'anything but asc is descending');
    }

    public function testCountLines(): void
    {
        $this->assertSame(0, count_lines(''), 'empty text has no lines');
        $this->assertSame(3, count_lines("a\nb\nc\n"), 'a trailing newline is not an extra line');
        $this->assertSame(3, count_lines("a\nb\nc"), 'a final line without a newline still counts');
        $this->assertSame(1, count_lines('single line'));
    }

    public function testUrlNiceMode(): void
    {
        // GFE_NICE_URL is true in the test config.
        $this->assertSame('http://gfe.test/', url('home', 'dir'));
        $this->assertSame('http://gfe.test/browse/Docs/', url('Docs', 'dir'));
        $this->assertSame(
            'http://gfe.test/browse/Docs/sortby/name/sortorder/asc/',
            url('Docs', 'dir', 'name', 'asc')
        );
        // url() uses urlencode(), so a space becomes '+' (round-trips via urldecode on read).
        $this->assertSame('http://gfe.test/viewing/a+b.txt/', url('a b.txt', 'file'));
        $this->assertSame('http://gfe.test/download/a+b.txt/', url('a b.txt', 'download'));
        $this->assertSame('http://gfe.test', url('x', 'unknown-mode'));
    }

    public function testCreateSortUrlNice(): void
    {
        $this->assertSame(
            'http://gfe.test/sortby/name/sortorder/asc/',
            create_sort_url('name', '', '', SORT_DESC)
        );
        $this->assertSame(
            'http://gfe.test/browse/Docs/sortby/size/sortorder/desc/',
            create_sort_url('size', '', 'Docs', SORT_ASC)
        );
    }

    public function testCreateSortImage(): void
    {
        $this->assertStringContainsString('fa-caret-up', create_sort_image('name', 'name', 'asc'));
        $this->assertStringContainsString('fa-caret-down', create_sort_image('name', 'name', 'desc'));
        $this->assertStringContainsString('fa-sort"', create_sort_image('name', 'size', 'asc'));
    }

    public function testBreadcrumbs(): void
    {
        $this->assertStringContainsString('Home', breadcrumbs([]));

        $withDirs = breadcrumbs([
            'directory_names' => ['One', '', 'Two'],
            'current_directory_name' => 'Two',
            'sort_by' => 'name',
            'sort_order' => 'asc',
        ]);
        $this->assertStringContainsString('One', $withDirs);
        $this->assertStringContainsString('aria-current="page"', $withDirs);

        $withFile = breadcrumbs(['file' => 'Docs/report.pdf', 'file_name' => 'report.pdf']);
        $this->assertStringContainsString('Docs', $withFile);
        $this->assertStringContainsString('report.pdf', $withFile);

        $withSearch = breadcrumbs(['search_keyword' => 'hello']);
        $this->assertStringContainsString('Search', $withSearch);
        $this->assertStringContainsString('hello', $withSearch);
    }

    public function testTemplateHeaderRendersAnalyticsWhenConfigured(): void
    {
        ob_start();
        template_header(' - Title', breadcrumbs([]));
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('<title>Test Files - Title</title>', $html);
        $this->assertStringContainsString('bootstrap.min.css', $html);
        $this->assertStringContainsString('<main id="gfe-content">', $html, 'content is wrapped in a main landmark');
        $this->assertStringContainsString('href="#gfe-content"', $html, 'a skip link targets the main landmark');
        $this->assertStringContainsString('rel="canonical"', $html);
        // Without a preview image the social card falls back to the app icon.
        $this->assertStringContainsString('content="summary"', $html);
        $this->assertStringContainsString('resources/icon.png', $html);
        $this->assertStringContainsString('G-TESTID', $html, 'GA tag rendered when ID is set');
    }

    public function testTemplateHeaderUsesPreviewImageForCards(): void
    {
        ob_start();
        template_header(' - Viewing Image', breadcrumbs([]), 'http://gfe.test/viewing/a.jpg/', 'http://gfe.test/a.jpg');
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('content="summary_large_image"', $html, 'an image preview uses a large card');
        $this->assertStringContainsString('property="og:image" content="http://gfe.test/a.jpg"', $html);
        $this->assertStringContainsString('name="twitter:image" content="http://gfe.test/a.jpg"', $html);
    }

    public function testTemplateFooterBranches(): void
    {
        // Not on the search page, with a full URL: renders path + search form.
        $_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__, 2) . '/index.php';
        ob_start();
        template_footer('http://gfe.test/Docs/');
        $withPath = (string) ob_get_clean();
        $this->assertStringContainsString('http://gfe.test/Docs/', $withPath);
        $this->assertStringContainsString('</main>', $withPath, 'the main landmark is closed');
        $this->assertStringContainsString('Search for files', $withPath);

        // On the search page, no full URL: no path row, no bottom search form.
        $_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__, 2) . '/search.php';
        ob_start();
        template_footer('');
        $onSearch = (string) ob_get_clean();
        $this->assertStringNotContainsString('Search for files', $onSearch);
    }
}
