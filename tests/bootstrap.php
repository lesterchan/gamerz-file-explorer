<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap for the unit tests. Builds the fixture content, defines the
 * GFE_* constants via the shared env-driven test config, then loads the app's
 * functions so they can be exercised directly.
 */
// Never executable over the web — these are CLI-only developer tools.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require __DIR__ . '/support/content.php';

$root = gfe_make_content();
putenv('GFE_TEST_ROOT=' . $root);
putenv('GFE_TEST_NICE=true');
putenv('GFE_TEST_SEARCH=true');
putenv('GFE_TEST_GA=G-TESTID');
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/index.php';

require __DIR__ . '/fixtures/config.php';
// settings.php both defines GFE_VERSION and returns the settings array — require it once.
$GLOBALS['gfe_settings'] = require dirname(__DIR__) . '/settings.php';
require dirname(__DIR__) . '/functions.php';

register_shutdown_function(static function () use ($root): void {
    gfe_rrmdir($root);
});
