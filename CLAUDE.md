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

## Keep in sync with the live deployment

This project is deployed at **files.lesterchan.net**, whose repo
(local checkout: `~/git/lesterchan/files.lesterchan.net`) carries a copy of these app
files alongside its served content. **Any change to the app code — bug fix, typo, security
fix — must be applied to both repos.** Deployment-specific values (`config.php` paths/URLs,
`settings.php` ignore lists) legitimately differ and are not mirrored.

## Conventions

- Procedural PHP, 4-space indent, short array syntax `[]`, `snake_case` functions/vars,
  `GFE_`-prefixed `SCREAMING_SNAKE` constants. `.editorconfig` is authoritative (utf-8, lf,
  final newline, trim trailing whitespace; `.yml` uses 2-space).
- Concatenation whitespace style is inconsistent between files (`functions.php` uses
  `' . $x . '`; `index.php`/`search.php`/`view.php` use `'.$x.'`). Match the file you edit.
- When outputting any dynamic value (filenames, paths, search terms, request values),
  escape it with `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` at the point of output.
  Pass raw values to `url()` — it URL-encodes internally, so escape the display copy, not
  the `url()` argument.
- Paths from `$_GET` are validated against `../`, `./`, and `//` in `index.php`/`view.php`;
  preserve that check when adding any new path input. `settings.php` ignore lists keep
  internal files (`config.php`, `functions.php`, …) out of listings — add new internal
  files there.

## Verify

- Lint all PHP: `find . -name '*.php' -exec php -lf {} \;`
- There is no test suite or build step.

## Releasing

- Bump `GFE_VERSION` in `settings.php` and add a `## Changelog` entry in `README.md`.
- `CLAUDE.md` is contributor tooling — it is **not** part of the distributable, so do not
  add it to the "Upload These Files" list in `README.md`.
