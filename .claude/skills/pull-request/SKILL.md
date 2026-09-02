---
name: pull-request
description: >-
  Open a GitHub pull request for the Sensei LMS Certificates plugin the way this
  repo requires. Use this whenever the user wants to create, open, draft, or "put
  up" a PR, or says things like "open a PR", "create a pull request", "make a PR
  for this branch", or "PR this". It fills the repo's PULL_REQUEST_TEMPLATE.md
  from the actual diff against trunk, commits the changelog entry (or applies the
  No Changelog label), assigns the required milestone, and stops for approval
  before pushing. Prefer this skill over a bare `gh pr create` so the PR carries
  its changelog entry and milestone, and so the body matches the template
  reviewers expect.
---

# Open a Sensei LMS Certificates Pull Request

Fill the repo's PR template from the diff, do the required chores (changelog,
milestone), and stop for approval before `gh pr create` — which pushes the branch.

## Who this is for

This skill assumes **write access to `woocommerce/sensei-certificates`** — it
assigns the milestone (needs triage/write permission). One step doesn't apply to
fork / external contributors:

- **Milestone:** external contributors can't assign one — leave it; a maintainer
  sets the milestone on their side.

Everything else (template body, changelog entry, testing instructions,
stop-before-push) applies to everyone.

## Base branch

The base is always `trunk`. Compare against it, not `main`.

## Steps

### 1. Preconditions

- Confirm the current branch is not `trunk`. If it is, stop — nothing to PR.
- Confirm `gh` is authenticated: `gh auth status`. If not, ask the user to run
  `! gh auth login` themselves.

### 2. Understand the change from the diff

Describe what the diff does, not how you got there — commit-by-commit narration
is noise to a reviewer.

```bash
git merge-base trunk HEAD          # fork point
git log --oneline trunk..HEAD      # commits on this branch
git diff --stat trunk...HEAD       # files touched
git diff trunk...HEAD              # the actual change — read this
```

Read the diff. From it, decide the **title** — one line, imperative, concise, no
conventional-commit prefix (no `feat:` / `fix:`). Example:
`Add course link to quiz page breadcrumb`.

### 3. Fill the repo's PR template

Do not invent your own headings. Read the template and fill it in:

```bash
cat .github/PULL_REQUEST_TEMPLATE.md
```

Fill each section from the diff. Guidance per section:

- **`Resolves #`** — fill it in as `Resolves #<n>` if the user gave an issue
  number, or one appears in the branch name or the commit messages. If there's no
  issue, **remove this line entirely** — a bare `Resolves #` is noise. Never guess
  a number.
- **`## Proposed Changes`** — the reviewer-facing summary. Lead with the
  **problem or need** and the **user impact** (new capability, bug fixed,
  performance, accessibility, behavior change or trade-off), then the approach at a
  high level. Don't re-list the modified files or narrate implementation mechanics
  — the diff already shows that; prose that just restates it wastes the reviewer's
  time. If the change is user-visible, a reader should understand *what changes for
  them* without opening the diff.
- **`## Screenshots`** — keep this section only when the change is **visual**.
  Judge that from the diff: it touches front-end/editor surfaces — `assets/`
  (JS/CSS/SCSS), block markup, `render.php`, front-end templates, or editor
  components. If it's not visual (pure PHP logic, REST, data, tooling, tests),
  **remove this whole section**. When you keep it, leave the template's empty
  Before/After table for the user to fill (for net-new UI with no "before" state,
  replace it with a note to paste a single screenshot or short video). You cannot
  capture the images yourself, so at the approval gate (step 5) remind the user to
  attach them.
- **`## Testing Instructions`** — a checkbox list (`- [ ] step`) of manual steps a
  human follows to verify the change (click paths, expected on-screen results,
  edge cases), so the reviewer can tick each as they test. **Never list running
  automated suites as a step** — there are none; the reviewer verifies behavior by
  hand. If the change has no manual surface (e.g. test-only or pure internal
  refactor), say so briefly instead of padding with "run the tests".
- **`## New/Updated Hooks`** — fill only if the diff adds or changes an action or
  filter; describe each and its args, and plan to add the **Hooks** label. If the
  diff touches no hooks, **remove this whole section** (heading, comment, and
  placeholder) from the body rather than leaving an empty `*`.
- **`## Deprecated Code`** — fill only if the diff deprecates something; name the
  replacement and plan to add the **Deprecation** label. If nothing is deprecated,
  **remove this whole section** from the body.

### 4. Changelog — a committed entry, not the PR body

This repo uses Jetpack changelogger; entries live as files under `changelog/` and
are rolled into `changelog.txt` on release. There is no CI to generate one, so the
entry is a file you commit to the branch (or the PR carries the **No Changelog**
label).

First check whether the branch already has an entry — a dev may have run
`npm run changelog` during development and committed it:

```bash
git diff --name-only --diff-filter=A trunk...HEAD -- changelog/
```

If that lists a file, the entry already exists — note it in the summary you show
the user.

- **User-facing change (no committed entry yet):** tell the user to run
  `npm run changelog` and commit the new `changelog/` file with the PR — it's
  interactive, so don't run it for them. Write the **Message** as the user-facing
  outcome (one sentence — the effect, not the implementation). When the
  significance is ambiguous (e.g. a `fix/` branch that repairs behavior but also
  adds a small element), lean toward `Patch` / `Fixed` — match how the work is
  framed rather than over-classifying it as a feature.
- **Internal-only change** (refactor, test-only, tooling — no user-facing effect):
  plan to apply the **`No Changelog`** label to the PR. Say this in the summary you
  show the user.

### 5. Stop and confirm — this is the push gate

Show the user the proposed **title** and the **filled template body** (call out the
changelog choice and any labels: Hooks / Deprecation / No Changelog). If you kept a
**Screenshots** section, remind the user to attach before/after images — you can't
capture them, and the placeholder ships empty otherwise. Then wait for explicit
approval. Do not run `gh pr create` until they say go — it pushes the branch and
opens the PR, which is outward-facing and hard to walk back.

### 6. Create the PR

After approval:

```bash
gh pr create --base trunk --title "<title>" --body "<filled template body>"
```

`gh` pushes the current branch as part of this. Capture the PR number it prints.
Apply any labels you flagged (e.g. `No Changelog`, `Hooks`, `Deprecation`), and
assign the PR to its author (`@me` is the authenticated user opening it):

```bash
gh pr edit <PR_NUMBER> --add-label "No Changelog"
gh pr edit <PR_NUMBER> --add-assignee "@me"
```

### 7. Assign the milestone (required)

Find the next shipping milestone (lowest-versioned open one) and assign it. Use
`sort -V` (version sort) — a plain string sort mis-orders `2.5.5` vs `2.10.0`:

```bash
next=$(gh api 'repos/woocommerce/sensei-certificates/milestones?state=open' --jq '.[].title' | sort -V | head -1)
gh pr edit <PR_NUMBER> --milestone "$next"
```

### 8. Wrap up

Give the user the PR URL.
