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
        $this->assertGreaterThan(0, dir_size($this->root()));
        $this->assertSame(0, dir_size($this->root() . '/does-not-exist'));
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
        $this->assertSame([], list_files($this->root() . '/nope', $this->settings));
    }

    public function testListDirectories(): void
    {
        $dirs = list_directories($this->root(), $this->settings);
        $this->assertContains('Sub Folder', $dirs);
        $this->assertNotContains('resources', $dirs, 'ignored folder');
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

    public function testIsSafePath(): void
    {
        $this->assertTrue(is_safe_path(''), 'the empty (home) path is allowed');
        $this->assertTrue(is_safe_path('Sub Folder/inner.txt'));
        $this->assertFalse(is_safe_path('.'));
        $this->assertFalse(is_safe_path('..'), 'a bare parent segment is rejected');
        $this->assertFalse(is_safe_path('a/../b'));
        $this->assertFalse(is_safe_path('a//b'), 'an empty segment is rejected');
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
        // A JPEG carrying an EXIF Model tag surfaces it under a friendly label.
        $this->assertSame('GFE Cam', image_exif($this->root() . '/photo.jpg', 'jpg')['Model'] ?? null);
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

        // Missing sort field falls back to 0 / '' without error.
        $this->assertCount(1, sort_entries([['name' => 'x', 'size' => 1, 'date' => 1]], 'type', SORT_ASC));
    }

    public function testGetLineCount(): void
    {
        $this->assertSame(3, get_line_count($this->root() . '/notes.txt'));
        $this->assertSame(0, get_line_count($this->root() . '/missing.txt'));
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
        $this->assertStringContainsString('G-TESTID', $html, 'GA tag rendered when ID is set');
    }

    public function testTemplateFooterBranches(): void
    {
        // Not on the search page, with a full URL: renders path + search form.
        $_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__, 2) . '/index.php';
        ob_start();
        template_footer('http://gfe.test/Docs/');
        $withPath = (string) ob_get_clean();
        $this->assertStringContainsString('http://gfe.test/Docs/', $withPath);
        $this->assertStringContainsString('Search for files', $withPath);

        // On the search page, no full URL: no path row, no bottom search form.
        $_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__, 2) . '/search.php';
        ob_start();
        template_footer('');
        $onSearch = (string) ob_get_clean();
        $this->assertStringNotContainsString('Search for files', $onSearch);
    }
}
