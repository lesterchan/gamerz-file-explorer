<?php

declare(strict_types=1);

/**
 * Integration coverage harness. Runs a single entry-point script in this isolated
 * process against the fixture content, collecting line coverage that is flushed
 * from a shutdown handler — so scripts that call exit() are still recorded.
 *
 * Driven entirely by environment variables set by tests/coverage.php:
 *   GFE_TEST_ROOT   fixture content directory
 *   GFE_TARGET      entry script to run (index.php|search.php|view.php|404.php)
 *   GFE_QUERY       raw query string for $_GET
 *   GFE_COV_OUT     path to write the serialized coverage to
 *   GFE_TEST_NICE / GFE_TEST_SEARCH / GFE_TEST_GA  config toggles (read by fixtures/config.php)
 */

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\PHP as PhpReport;

// Never executable over the web — these are CLI-only developer tools.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$repoRoot = dirname(__DIR__, 2);
require $repoRoot . '/vendor/autoload.php';

$target = (string) getenv('GFE_TARGET');
$covOut = (string) getenv('GFE_COV_OUT');
$outFile = (string) getenv('GFE_OUT_FILE');

// Measure the six application files (config.php is deployment-specific and shadowed).
$filter = new Filter();
foreach (['functions.php', 'settings.php', 'index.php', 'search.php', 'view.php', '404.php'] as $file) {
    $filter->includeFile($repoRoot . '/' . $file);
}
$coverage = new CodeCoverage((new Selector())->forLineCoverage($filter), $filter);
$coverage->start('integration:' . $target);

// Flush coverage and captured output even when the script under test calls exit().
register_shutdown_function(static function () use ($coverage, $covOut, $outFile): void {
    $output = '';
    while (ob_get_level() > 0) {
        $output = ob_get_clean() . $output;
    }
    if ($outFile !== '') {
        file_put_contents($outFile, $output);
    }
    $coverage->stop();
    (new PhpReport())->process($coverage, $covOut);
});

// Build the request environment.
parse_str((string) getenv('GFE_QUERY'), $_GET);
$_SERVER['REQUEST_URI'] = '/' . $target . '?' . (string) getenv('GFE_QUERY');
$_SERVER['SCRIPT_FILENAME'] = $repoRoot . '/' . $target;

// Make the entry script pick up the test config via the include path, then run it.
set_include_path(__DIR__ . '/../fixtures' . PATH_SEPARATOR . get_include_path());
chdir($repoRoot);

// The shutdown handler captures and flushes this buffer (it survives exit()).
ob_start();
require $repoRoot . '/' . $target;
