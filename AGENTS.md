Sensei LMS Certificates is a WordPress plugin that awards students a downloadable PDF certificate when they complete a Sensei LMS course. It extends [Sensei LMS](https://github.com/Automattic/sensei) (the sibling `sensei` plugin) and does not function without it — see the dependency section below.

## Read before writing code
This repo has no conventions docs of its own; it follows Sensei LMS's, canonical in the `sensei` repo.
- Writing or changing a **test**? Read Sensei LMS's [unit-test conventions](https://github.com/Automattic/sensei/blob/trunk/docs/conventions/unit-tests.md) first.
- Writing a **class, hook, meta key, block, or stylesheet**? Read Sensei LMS's [naming conventions](https://github.com/Automattic/sensei/blob/trunk/docs/conventions/naming.md) first.

Apply them to code you add. Do not rewrite surrounding code to match. Where they conflict with this repo's established patterns (legacy prefixes, existing `learner` identifiers), the local pattern wins — see Conventions.

## Repository layout
- `woothemes-sensei-certificates.php` — plugin entry point. Defines constants, runs the dependency checker, then boots `WooThemes_Sensei_Certificates`. Also the source of truth for `Requires PHP` and `Requires at least` (minimum WordPress).
- `classes/` — main plugin PHP source. Key files:
  - `class-woothemes-sensei-certificates.php` — main class (certificate generation, settings, download, hooks into Sensei).
  - `class-woothemes-sensei-certificate-templates.php` — the `certificate` / `certificate_template` custom post types and the design system.
  - `class-woothemes-sensei-pdf-certificate.php` + `class-woothemes-sensei-certificates-tfpdf.php` / `class-vip-tfpdf.php` — PDF rendering on top of the bundled tFPDF library.
  - `class-woothemes-sensei-certificates-dependency-checker.php` — gates activation on PHP version and the Sensei LMS dependency.
  - `blocks/`, `background-jobs/`, `tools/` — the "View Certificate" block, bulk certificate generation jobs, and Sensei LMS Tools integrations.
- `admin/` — admin post-type UI and write panels for the certificate template designer.
- `assets/` — JS/SCSS source and blocks; built artifacts land in `assets/dist/` (git-ignored, produced by the build).
- `templates/` — front-end certificate templates (overridable by themes).
- `lib/tfpdf/` — vendored tFPDF PDF library. Treat as read-only third-party code; excluded from linting.
- `lang/` — translations and the `.pot` file.
- `sensei-certificates-functions.php` — global template/helper functions.
- `.github/` — PR and issue templates only; there are no CI workflows in this repo.

## Sensei LMS dependency

Sensei LMS Certificates is a **runtime** extension of Sensei LMS — there is no build-time coupling to the `sensei` source, but at runtime the `sensei` plugin must be installed and active, or this plugin deactivates itself.

- The gate lives in `Woothemes_Sensei_Certificates_Dependency_Checker`: it requires the `Sensei_Main` class and the `sensei-version` option to be at least the `MINIMUM_SENSEI_VERSION` constant declared there (source of truth for the minimum Sensei LMS version).
- Code here consumes Sensei LMS APIs directly: the `Sensei()` global, `Sensei_Assets`, `Sensei_Settings`, and course/lesson data. When you touch these, check the sibling `sensei` checkout (usually `../sensei`) for the current signatures rather than guessing.
- **Do not vendor or duplicate Sensei LMS code here.** If a shared concern needs fixing, fix it in the `sensei` repo. Treat the `sensei` source as read-only from this repo.
- To exercise this plugin locally you need both plugins active in the same WordPress install with a course a student can complete.

## Development environment
- Match your Node version to `.nvmrc` (`nvm use` reads it) before any `npm` command, so the committed `package-lock.json` isn't rewritten.
- Install JS deps with `npm ci` and PHP dev tooling (PHPCS) with `composer install`.
- This repo has no wp-env / Docker sandbox of its own. Develop against a WordPress install that already has an active, compatible Sensei LMS — e.g. the local site this checkout lives under.

## Building
- `npm run build` — full release build: `build:assets` then `archive` (produces `sensei-certificates.zip` via `composer archive`).
- `npm run build:assets` — compile JS/CSS from `assets/` into `assets/dist/` (`wp-scripts build`). Run this after changing anything under `assets/`; the built files are what the browser loads.
- `npm run start` — webpack watch mode for iterative asset work.
- `npm run i18n:build` — regenerate `lang/sensei-certificates.pot` (needs `wp-cli`).

## Testing
- **Test-driven development**: Write tests for non-trivial new behavior and bug fixes — a failing test first, then implement until it passes. Skip tests only for trivial changes such as copy/string tweaks, config, mechanical renames, one-line passthroughs, and styling. If unsure whether a change needs a test, ask rather than skip by default.
- **No harness is wired up yet** (no PHPUnit, Jest, or Playwright config in this repo). Standing up the appropriate runner is part of doing a change properly — add PHPUnit (the WordPress-plugin standard, as the `sensei` repo uses) for PHP behavior, or coordinate with the maintainer. Do not treat the missing harness as license to skip tests, and do not fabricate `make test` / `npm test` commands that don't exist.
- **Ad-hoc UI verification**: For UI or behavior changes, use the `ui-verification` skill (`.claude/skills/ui-verification/SKILL.md`), which scopes from the diff and drives the running site via Chrome DevTools MCP. Requires a WordPress install with Sensei LMS active and a course a student can complete.

## Linting
- **PHPCS** (WordPress coding standards, config in `phpcs.xml.dist`): run `./vendor/bin/phpcs` after `composer install`. Auto-fix with `./vendor/bin/phpcbf`. `lib/`, `vendor/`, `node_modules/`, `build/`, and `tests/` are excluded. Enforced global prefixes are `sensei` and `woothemes`; text domain is `sensei-certificates`.
- **JS**: `npm run lint:js` (ESLint via `wp-scripts` on `assets/js`). Format with `npm run format:js`.
- **CSS/SCSS**: `npm run lint:css` (stylelint via `wp-scripts` on `assets/css`).
- **package.json**: `npm run lint:pkg-json`.
- There is no CI in this repo enforcing these, so run the relevant linters yourself before opening a PR.

## Conventions
- **Coding standards**: Follow the WordPress coding standards for the language you touch — [PHP](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/), [JavaScript](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/), [CSS](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/). Use long-form `array( ... )` in new PHP.
- **Naming — local exceptions to Sensei's conventions**: Follow Sensei LMS's naming conventions (linked under "Read before writing code") for new code, except where this repo's established patterns win: classes are prefixed `WooThemes_Sensei_` / `Woothemes_Sensei_`, and globals (functions, hooks) use the `sensei` / `woothemes` prefixes enforced by `phpcs.xml.dist`. Existing `learner`-based identifiers and DB keys (e.g. the `learner_id` post meta) can't be renamed — leave them. Do not rewrite surrounding code to match a new style.
- **Documenting hooks and functions**: Give new or changed actions/filters and public functions a docblock (params, return, `@since`). For `@since`, use the version being released (there is no `$$next-version$$` placeholder tooling in this repo). When deprecating code, name the replacement in the docblock.
- **Minimum supported versions**: The `woothemes-sensei-certificates.php` plugin header is the source of truth — `Requires PHP` (minimum PHP) and `Requires at least` (minimum WordPress). Keep it, `readme.txt`, and `SENSEI_CERTIFICATES_VERSION` in sync on a version bump. Don't use PHP features newer than the minimum.
- **Version bumps**: Keep the version in sync across `woothemes-sensei-certificates.php` (header + `SENSEI_CERTIFICATES_VERSION`), `package.json`, and `readme.txt` (`Stable tag`).

## Common pitfalls
- **Editing the `sensei` source or `lib/tfpdf/` from this repo.** Both are effectively read-only here — fix Sensei LMS concerns upstream in `sensei`; treat tFPDF as vendored third-party code.
- **Stale built assets**: changes under `assets/` don't appear in the browser until `npm run build:assets` (or `npm run start`) regenerates `assets/dist/`. Hard-reload after building.
- **Assuming Sensei LMS APIs**: signatures for `Sensei()`, `Sensei_Assets`, `Sensei_Settings`, etc. live in the `sensei` repo — verify there instead of guessing.
- **Forgetting the version-file sync** on a release (plugin header, `SENSEI_CERTIFICATES_VERSION`, `package.json`, `readme.txt`).
- **Using the wrong Node**: run `nvm use` first so `package-lock.json` isn't rewritten by a newer npm.
