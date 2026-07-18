# CLAUDE.md

Guidance for working in the **GaMerZ File Explorer** codebase.

## What this is

A single-directory PHP web app (no framework, no Composer, no database) that lists,
searches, views, and downloads the contents of a configured root folder — styled like
Windows Explorer. Procedural PHP, distributed as a set of files users drop into a web
directory.

## Layout

| File | Role |
|------|------|
| `config.php` | Site constants (`GFE_ROOT_DIR`, `GFE_ROOT_URL`, `GFE_URL`, names, toggles). Edited per-install. |
| `settings.php` | Ignore lists (`$ignore_files`, `$ignore_ext`, `$ignore_folders`), extension→label/icon map, and `GFE_VERSION`. |
| `functions.php` | Shared helpers and the HTML templates (`template_header`, `template_footer`, `breadcrumbs`, `display_error`, listing/sort helpers). |
| `index.php` | Directory listing. Reads `$_GET['dir']`, `by`, `order`. |
| `search.php` | Search UI + results. Reads `$_GET['search']`, `in`, `by`, `order`. |
| `view.php` | View (text/image) or download a file. Reads `$_GET['file']`, `dl`. |
| `404.php` | Error page. |

Every entry point starts with `require 'config.php'; require 'settings.php'; require 'functions.php';` in that order.

## Conventions

- PHP 8.1+, `declare(strict_types=1)`, PSR-12, 4-space indent, short array syntax `[]`,
  `snake_case` functions/vars, `GFE_`-prefixed `SCREAMING_SNAKE` constants, `' . $x . '`
  concatenation spacing throughout. `.editorconfig` and `phpcs.xml.dist` are authoritative.
- `settings.php` `return`s a settings array; entry points do `$settings = require 'settings.php';`
  and pass it into the `functions.php` helpers. There is no shared global state.
- When outputting any dynamic value (filenames, paths, search terms, request values),
  escape it with `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` at the point of output.
  Pass raw values to `url()` — it URL-encodes internally, so escape the display copy.
- `urldecode()` the incoming `dir`/`file` request values **before** using them and before
  the `../`, `./`, `//` traversal check in `index.php`/`view.php`. Nice-URLs encode a space
  as `+`, which the web server passes as `%2B`; skipping the decode breaks paths with spaces
  (the 3.0.0 regression). Keep that decode-then-check order for any new path input.
- `settings.php` ignore lists keep internal files (`config.php`, `functions.php`, the
  Composer/PHPStan/PHPCS/PHPUnit files, …) out of listings; add new internal files there.
  Hiding is **not** access control — the `.htaccess`/Nginx deny rules and the CLI-only guard
  in `tests/` are what stop the web server serving dev/tooling files. Keep them in place.
- Frontend is Bootstrap 5.3 + Font Awesome 6 loaded from cdnjs with SRI hashes, no jQuery.
  If you bump a CDN version, update its `integrity` hash too.

## Verify

- Syntax-check all PHP: `find . -name '*.php' -exec php -lf {} \;`
- Static analysis (PHPStan level 6): `composer analyse`
- Coding standard (PSR-12): `composer lint` (`composer lint:fix` to autofix)
- Tests with a 100% line-coverage gate: `composer coverage` (PHPUnit unit tests +
  process-isolated integration scenarios, merged; also asserts behaviour — traversal, XSS,
  the spaces-in-nice-URL regression). `composer unit` runs just the unit suite.
- `composer test` runs all of the above; CI runs it on PHP 8.1–8.4. Composer is dev-only —
  the runtime stays dependency-free; the `GfeSettings`/`GfeEntry` array-shape aliases live
  in `phpstan.neon.dist`.

## Releasing

- Bump `GFE_VERSION` in `settings.php` and add a `## Changelog` entry in `README.md`.
- `CLAUDE.md` is contributor tooling — it is **not** part of the distributable, so do not
  add it to the "Upload These Files" list in `README.md`.
