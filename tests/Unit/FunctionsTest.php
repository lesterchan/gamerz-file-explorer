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
        $this->assertNotContains('phpinfo.php', $names, 'ignored filename');
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
        $this->assertNotContains('phpinfo.php', $fileNames, 'ignored filename');
        $this->assertNotContains('.htaccess', $fileNames, 'ignored extension');
        $this->assertContains('Sub Folder', $dirNames);
        $this->assertNotContains('resources', $dirNames, 'ignored folder');
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
        $this->assertStringContainsString('fa-sort-up', create_sort_image('name', 'name', 'asc'));
        $this->assertStringContainsString('fa-sort-down', create_sort_image('name', 'name', 'desc'));
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
