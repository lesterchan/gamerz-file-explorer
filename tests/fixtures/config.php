<?php

declare(strict_types=1);

/**
 * Env-driven test config. Shadows the real config.php on the include_path during
 * tests so the entry scripts run against the fixture content and toggles.
 */

// Never executable over the web — CLI-only test fixture.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

define('GFE_ROOT_DIR', getenv('GFE_TEST_ROOT') ?: sys_get_temp_dir());
define('GFE_ROOT_URL', 'http://gfe.test');
define('GFE_DIR', getenv('GFE_TEST_ROOT') ?: sys_get_temp_dir());
define('GFE_URL', 'http://gfe.test');
define('GFE_SITE_NAME', 'Test Files');
define('GFE_SITE_DESCRIPTION', 'Test description');
define('GFE_ROOT_FILENAME', 'index.php');
define('GFE_NICE_URL', getenv('GFE_TEST_NICE') !== 'false');
define('GFE_CAN_SEARCH', getenv('GFE_TEST_SEARCH') !== 'false');
define('GFE_DEFAULT_SORT_BY', 'date');
define('GFE_DEFAULT_SORT_ORDER', 'desc');
define('GFE_GA_MEASUREMENT_ID', getenv('GFE_TEST_GA') ?: '');
