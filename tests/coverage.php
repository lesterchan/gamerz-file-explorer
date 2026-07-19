<?php

declare(strict_types=1);

/**
 * Coverage orchestrator: runs the PHPUnit unit suite and the process-isolated
 * integration scenarios, merges their line coverage, writes reports, and fails
 * (exit code 1) unless every executable line of the application is covered.
 *
 * Usage: php tests/coverage.php
 */

use SebastianBergmann\CodeCoverage\CodeCoverage;

// Never executable over the web — these are CLI-only developer tools.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$repoRoot = dirname(__DIR__);
require $repoRoot . '/vendor/autoload.php';
require __DIR__ . '/support/content.php';

if (! extension_loaded('pcov') && ! extension_loaded('xdebug')) {
    fwrite(STDERR, "No coverage driver (pcov or xdebug) available.\n");
    exit(1);
}

$build = $repoRoot . '/build';
@mkdir($build, 0777, true);
array_map('unlink', glob($build . '/*.cov') ?: []);

$phpBin = PHP_BINARY;
$pcovFlag = extension_loaded('pcov') ? '-d pcov.enabled=1' : '';

// Shared fixture content for the integration runs.
$content = gfe_make_content();
register_shutdown_function(static fn () => gfe_rrmdir($content));

echo "==> Running unit suite\n";
$unitCov = $build . '/unit.cov';
passthru(sprintf(
    '%s %s %s/vendor/bin/phpunit --coverage-php=%s',
    escapeshellarg($phpBin),
    $pcovFlag,
    escapeshellarg($repoRoot),
    escapeshellarg($unitCov)
), $unitExit);
if ($unitExit !== 0) {
    fwrite(STDERR, "Unit suite failed.\n");
    exit($unitExit);
}

/**
 * Integration scenarios. Each runs one entry script and asserts on its output,
 * so the suite verifies behaviour (security, regressions, happy paths) — not just
 * that lines executed. Keys: target, query, env, expect (all must appear),
 * absent (none may appear).
 *
 * @var list<array{
 *     target: string, query?: string, env?: array<string,string>,
 *     expect?: list<string>, absent?: list<string>
 * }> $scenarios
 */
$scenarios = [
    // --- index.php ---
    ['target' => 'index.php', 'expect' => ['notes.txt', 'Sub Folder', 'My File.txt', 'aria-sort=', 'aria-hidden="true"',
        'href="#gfe-content"', '<main id="gfe-content">', 'rel="canonical" href="http://gfe.test/"'],
        'absent' => ['config.php']], // ignored file is hidden from the listing
    ['target' => 'index.php', 'query' => 'dir=Sub Folder', 'expect' => ['inner.txt', 'Parent Directory']],
    ['target' => 'index.php', 'query' => 'by=name&order=asc', 'expect' => ['notes.txt']],
    ['target' => 'index.php', 'query' => 'by=size&order=asc', 'expect' => ['notes.txt']],
    ['target' => 'index.php', 'query' => 'by=bogus', 'expect' => ['notes.txt']],
    // Sorting by type: files sort by type, directories fall back to name — both still render.
    ['target' => 'index.php', 'query' => 'by=type&order=asc', 'expect' => ['Sub Folder', 'notes.txt']],
    ['target' => 'index.php', 'query' => 'dir=Empty', 'expect' => ['This folder is empty']],
    // Security: path traversal is rejected, not served.
    ['target' => 'index.php', 'query' => 'dir=../etc', 'expect' => ['Invalid Directory'], 'absent' => ['root:']],
    ['target' => 'index.php', 'query' => 'dir=%2e%2e/secret', 'expect' => ['Invalid Directory']],
    // A bare '..' segment (no trailing slash) must not list the parent of the web root.
    ['target' => 'index.php', 'query' => 'dir=..', 'expect' => ['Invalid Directory']],
    ['target' => 'index.php', 'query' => 'dir=.', 'expect' => ['Invalid Directory']],
    ['target' => 'index.php', 'query' => 'dir=Sub Folder/..', 'expect' => ['Invalid Directory']],
    ['target' => 'index.php', 'query' => 'dir=resources', 'expect' => ['Invalid Directory']], // ignored folder
    // A folder nested under an ignored folder must not be listable either.
    ['target' => 'index.php', 'query' => 'dir=resources/nested', 'expect' => ['Invalid Directory']],
    ['target' => 'index.php', 'query' => 'dir=nope', 'expect' => ['Invalid Directory']], // unreadable directory
    // Deployment-specific ignores from config.php (GFE_IGNORE_* merged into settings) are hidden.
    ['target' => 'index.php', 'absent' => ['secret-note.txt', 'draft.bak', 'hidden.txt']],
    ['target' => 'index.php', 'query' => 'dir=private', 'expect' => ['Invalid Directory']], // config.php-ignored folder
    ['target' => 'index.php', 'env' => ['GFE_TEST_NICE' => 'false'], 'expect' => ['index.php?dir=']],
    ['target' => 'index.php', 'env' => ['GFE_TEST_GA' => ''], 'absent' => ['G-TESTID']], // analytics off
    // --- search.php ---
    ['target' => 'search.php', 'expect' => ['All Folders', 'Sub Folder']],
    ['target' => 'search.php', 'query' => 'search=notes', 'expect' => ['notes.txt']],
    ['target' => 'search.php', 'query' => 'search=zzzzz', 'expect' => ['No files match']],
    ['target' => 'search.php', 'query' => 'search=inner&in=Sub Folder', 'expect' => ['inner.txt']],
    // The 'in' filter matches on a folder boundary: 'Sub' must not match the 'Sub Folder/' path.
    ['target' => 'search.php', 'query' => 'search=inner&in=Sub', 'expect' => ['No files match'], 'absent' => ['inner.txt']],
    // Match against the folder path, not just the name: 'Folder' only hits via the path.
    ['target' => 'search.php', 'query' => 'search=Folder&match=path', 'expect' => ['inner.txt', 'Sub Folder']],
    ['target' => 'search.php', 'query' => 'search=notes&by=name&order=asc', 'expect' => ['notes.txt']],
    ['target' => 'search.php', 'query' => 'search=notes&by=size', 'expect' => ['notes.txt']],
    ['target' => 'search.php', 'query' => 'search=notes&by=bogus', 'expect' => ['notes.txt']],
    // Security: a quote-breakout XSS payload is neutralised by htmlspecialchars(ENT_QUOTES).
    ['target' => 'search.php', 'query' => 'search=%22+onmouseover%3D%22alert(1)',
        'expect' => ['&quot; onmouseover=&quot;'], 'absent' => ['" onmouseover="']],
    ['target' => 'search.php', 'query' => 'search=x', 'env' => ['GFE_TEST_SEARCH' => 'false'],
        'expect' => ['Disabled']],
    // --- view.php ---
    ['target' => 'view.php', 'query' => 'file=notes.txt', 'expect' => ['line one', 'Viewing Text File']],
    // Previous/Next follow the chosen sort: by name ascending, code.php sits between clip.mp4 and escape.txt.
    // The sort travels in the query string, and never appears in the path.
    ['target' => 'view.php', 'query' => 'file=code.php&by=name&order=asc',
        'expect' => ['viewing/clip.mp4/?by=name&amp;order=asc', 'viewing/escape.txt/?by=name&amp;order=asc'],
        'absent' => ['sortby/name']],
    ['target' => 'view.php', 'query' => 'file=code.php', 'expect' => ['&lt;?php']], // source shown escaped
    ['target' => 'view.php', 'query' => 'file=pixel.png', 'expect' => ['Viewing Image', 'img-fluid',
        'content="summary_large_image"', 'og:image" content="http://gfe.test/pixel.png"']], // the image is its own social preview
    ['target' => 'view.php', 'query' => 'file=photo.jpg', 'expect' => ['Viewing Image', 'Model', 'GFE Cam']], // EXIF panel

    ['target' => 'view.php', 'query' => 'file=broken.png', 'expect' => ['File Is Not A Valid Image']],
    ['target' => 'view.php', 'query' => 'file=report.pdf', 'expect' => ['Viewing PDF', 'gfe-embed-pdf']], // inline PDF
    ['target' => 'view.php', 'query' => 'file=report.pdf&dl=1', 'expect' => ['%PDF-1.4 fake']], // download serves bytes
    ['target' => 'view.php', 'query' => 'file=clip.mp4', 'expect' => ['Viewing Video', 'gfe-embed-video']], // inline video
    ['target' => 'view.php', 'query' => 'file=song.mp3', 'expect' => ['Viewing Audio', 'gfe-embed-audio']], // inline audio
    ['target' => 'view.php', 'query' => 'file=archive.bin',
        'expect' => ['Viewing File', 'previewed in the browser'], 'absent' => ['binary']], // non-viewable card, no auto-download
    ['target' => 'view.php', 'query' => 'file=archive.bin&dl=1', 'expect' => ['binary']], // Download button serves bytes
    // Regression: a space encoded as '+' (Apache re-encodes to %2B) must resolve to the real file.
    ['target' => 'view.php', 'query' => 'file=My%2BFile.txt',
        'expect' => ['spaced filename', 'Viewing Text File'], 'absent' => ['File Does Not Exist']],
    // Security: traversal and source/config files cannot be read.
    ['target' => 'view.php', 'query' => 'file=../etc/passwd', 'expect' => ['Invalid Directory'], 'absent' => ['root:']],
    ['target' => 'view.php', 'query' => 'file=..', 'expect' => ['Invalid Directory']], // bare '..' segment
    ['target' => 'view.php', 'query' => 'file=Sub Folder/..', 'expect' => ['Invalid Directory']],
    ['target' => 'view.php', 'query' => 'file=config.php', 'expect' => ['Invalid Directory'], 'absent' => ['GFE_ROOT_DIR']],
    ['target' => 'view.php', 'query' => 'file=functions.php', 'expect' => ['Invalid Directory']],
    ['target' => 'view.php', 'query' => 'file=.htaccess', 'expect' => ['Invalid Directory']], // ignored filename
    ['target' => 'view.php', 'query' => 'file=backup.htaccess', 'expect' => ['Invalid Extension']], // ignored extension
    // Deployment-specific ignores from config.php cannot be viewed/downloaded either.
    ['target' => 'view.php', 'query' => 'file=secret-note.txt', 'expect' => ['Invalid Directory']], // config.php-ignored filename
    ['target' => 'view.php', 'query' => 'file=draft.bak', 'expect' => ['Invalid Extension']], // config.php-ignored extension
    ['target' => 'view.php', 'query' => 'file=private/hidden.txt', 'expect' => ['Invalid Directory']], // inside config.php-ignored folder
    // File nested inside an ignored folder is not viewable/downloadable through the viewer.
    ['target' => 'view.php', 'query' => 'file=resources/icon.png', 'expect' => ['Invalid Directory'], 'absent' => ['img-fluid']],
    ['target' => 'view.php', 'query' => 'file=dangling.link', 'expect' => ['File Does Not Exist']], // broken symlink
    // Symlink resolving outside the root is rejected by the realpath containment check.
    ['target' => 'view.php', 'query' => 'file=escape.txt', 'expect' => ['Invalid Directory'], 'absent' => ['Viewing Text File']],
    ['target' => 'view.php', 'query' => 'file=nope.txt', 'expect' => ['File Does Not Exist']],
    ['target' => 'view.php', 'expect' => ['Invalid Directory']], // empty file parameter
    // --- 404.php ---
    ['target' => '404.php', 'expect' => ['404 - File Not Found']],
];

echo '==> Running ' . count($scenarios) . " integration scenarios\n";
$assertionFailures = [];
foreach ($scenarios as $i => $scenario) {
    $target = $scenario['target'];
    $query = $scenario['query'] ?? '';
    $covOut = sprintf('%s/int-%02d.cov', $build, $i);
    $outFile = sprintf('%s/int-%02d.out', $build, $i);
    $env = array_merge($_ENV, getenv(), [
        'GFE_TEST_ROOT' => $content,
        'GFE_TEST_NICE' => 'true',
        'GFE_TEST_SEARCH' => 'true',
        'GFE_TEST_GA' => 'G-TESTID',
        'GFE_TARGET' => $target,
        'GFE_QUERY' => $query,
        'GFE_COV_OUT' => $covOut,
        'GFE_OUT_FILE' => $outFile,
    ], $scenario['env'] ?? []);

    $cmd = sprintf('%s %s %s', escapeshellarg($phpBin), $pcovFlag, escapeshellarg(__DIR__ . '/integration/harness.php'));
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $repoRoot, $env);
    if (! is_resource($proc)) {
        fwrite(STDERR, "Failed to start scenario {$i}\n");
        exit(1);
    }
    stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    if (! is_file($covOut)) {
        fwrite(STDERR, "Scenario {$i} ({$target} {$query}) produced no coverage (exit {$code}).\n{$err}\n");
        exit(1);
    }

    $output = is_file($outFile) ? (string) file_get_contents($outFile) : '';
    $label = trim("{$target} {$query}");
    foreach ($scenario['expect'] ?? [] as $needle) {
        if (! str_contains($output, $needle)) {
            $assertionFailures[] = "[{$label}] expected to contain: {$needle}";
        }
    }
    foreach ($scenario['absent'] ?? [] as $needle) {
        if (str_contains($output, $needle)) {
            $assertionFailures[] = "[{$label}] expected NOT to contain: {$needle}";
        }
    }
}

if ($assertionFailures !== []) {
    fwrite(STDERR, "\nBehavioural assertions failed:\n  " . implode("\n  ", $assertionFailures) . "\n");
    exit(1);
}
echo "All behavioural assertions passed.\n";

echo "==> Merging coverage\n";
/** @var CodeCoverage $merged */
$merged = include $unitCov;
foreach (glob($build . '/int-*.cov') ?: [] as $file) {
    $merged->merge(include $file);
}

// Reports.
(new SebastianBergmann\CodeCoverage\Report\Clover())->process($merged, $build . '/clover.xml');
(new SebastianBergmann\CodeCoverage\Report\Html\Facade())->process($merged, $build . '/html');

// Enforce 100% on every measured file.
$failed = false;
printf("\n%-16s %-14s %s\n", 'File', 'Lines', 'Coverage');
printf("%s\n", str_repeat('-', 44));
foreach ($merged->getReport()->getIterator() as $item) {
    if (! $item instanceof SebastianBergmann\CodeCoverage\Node\File) {
        continue;
    }
    $executed = $item->numberOfExecutedLines();
    $executable = $item->numberOfExecutableLines();
    $pct = $item->percentageOfExecutedLines()->asFloat();
    printf("%-16s %5d/%-8d %6.2f%%\n", $item->name(), $executed, $executable, $pct);
    if ($executed < $executable) {
        $failed = true;
        foreach ($item->lineCoverageData() as $line => $tests) {
            if ($tests !== null && $tests === []) {
                fwrite(STDERR, sprintf("  UNCOVERED %s:%d\n", $item->name(), $line));
            }
        }
    }
}

echo "\n";
if ($failed) {
    fwrite(STDERR, "Coverage is below 100%.\n");
    exit(1);
}
echo "100% line coverage.\n";
