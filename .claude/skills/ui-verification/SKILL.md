---
name: ui-verification
description: >-
  Verify a Sensei LMS Certificates UI or behavior change end-to-end against the
  running WordPress site. Use after making a change that affects the certificate
  template designer, the generated PDF, the "View Certificate" link/block, or any
  admin/front-end surface, and whenever the user asks to "check it in the
  browser", "verify the UI", "see if it works", or "smoke-test" a change. Scopes
  what to check from `git diff`, drives the site via Chrome DevTools MCP, and
  captures screenshots. This is agent-driven manual verification, not an automated
  test suite — it does not run or write tests.
---

# Sensei LMS Certificates UI Verification

Confirm a change behaves correctly from a user's perspective by driving the real
site in a browser. Use this alongside — not instead of — writing automated tests
for the change (see AGENTS.md "Testing").

## Prerequisites

- A running WordPress site with **both** Sensei LMS and this plugin active. This
  repo has no wp-env/Docker of its own — use the local site this checkout lives
  under. Confirm the site's base URL and an admin login with the user before
  driving it.
- At least one **course a student can complete**, so a certificate gets
  generated. If none exists, create a course + a student enrollment first (or ask
  the user to point you at an existing completed course).
- Chrome DevTools MCP tools (`mcp__chrome-devtools__*`) available.
- For changes under `assets/`, run `npm run build:assets` first — the browser
  loads the built files in `assets/dist/`, not the source. Hard-reload after.

## Certificate surface map

Ranked by what a certificates change usually touches. Admin paths are stable;
front-end URLs depend on the site's permalinks — discover them by navigating.

| Area | Where | Verify when changing |
|------|-------|----------------------|
| Certificate Templates CPT | `/wp-admin/edit.php?post_type=certificate_template` | Template list, the design/write panels (background image, fonts, field placement) |
| Certificates settings | `/wp-admin/admin.php?page=sensei-settings` → Certificates tab | Settings save/load, "View in Learner Profile" / public-sharing options |
| Sensei Tools | `/wp-admin/admin.php?page=sensei-tools` | Bulk certificate generation / data cleanup actions |
| Single course (front end) | the course page, as a student who completed it | "View Certificate" button/link appears and links correctly |
| Certificate PDF | the "View Certificate" / download link | PDF renders, downloads, and the content (name, course, date) is correct |
| Learner profile | the student's public profile page | "View Certificate" link shows when enabled |
| Course List block / archive | `/courses/` or a page using the block | "View Certificate" link on completed courses |
| View Certificate block | block editor + the front-end page embedding it | Editor insert/config and front-end render |

## Workflow

### 1. Scope from the diff

```bash
git diff trunk...HEAD --name-only
```

Map changed files to surfaces; skip surfaces the diff doesn't touch:

- `admin/` or `classes/class-woothemes-sensei-certificate-templates.php` → template designer + write panels.
- `classes/class-woothemes-sensei-pdf-certificate.php`, `class-woothemes-sensei-certificates-tfpdf.php`, `class-vip-tfpdf.php`, `lib/tfpdf/` → generate and open a certificate PDF; inspect the rendered output.
- `assets/blocks/`, `classes/blocks/` → the View Certificate block, in both the editor and the front end.
- `templates/` → front-end certificate output.
- `classes/class-woothemes-sensei-certificates.php` → the broad one: settings tab, download flow, learner-profile link, course list columns. Check whichever the diff's methods drive.

### 2. Confirm the site is reachable

Navigate to the base URL and confirm it loads before driving flows. If assets
changed, confirm `npm run build:assets` ran.

### 3. Drive the relevant flows

For each in-scope surface, navigate, perform the action a user would, and confirm
the expected result. The core happy path for most changes:

1. As a student, complete (or open an already-completed) course.
2. Confirm the "View Certificate" link/button appears where expected.
3. Follow it; confirm the certificate PDF renders and downloads.
4. Confirm the certificate content is correct (student name, course title, date).

For admin/template changes, exercise the template designer: edit a template's
background/fonts/field placement, save, then re-generate a certificate and confirm
the change shows.

### 4. Capture evidence

Screenshot each verified surface (`mcp__chrome-devtools__take_screenshot`).
Capture both the before/after or the final state depending on what the change
affects.

### 5. Report

Summarize what you verified, per surface: what you did, what you expected, what
you saw, and attach the screenshots. Call out anything that didn't match — don't
claim a flow works unless you actually drove it.
