---
name: pull-request
description: >-
  Open a GitHub pull request for the Sensei LMS Certificates plugin the way this
  repo expects. Use this whenever the user wants to create, open, draft, or "put
  up" a PR, or says things like "open a PR", "create a pull request", "make a PR
  for this branch", or "PR this". It fills the repo's PULL_REQUEST_TEMPLATE.md
  from the actual diff against trunk and stops for approval before pushing.
  Prefer this over a bare `gh pr create` so the body matches the template
  reviewers expect and the PR gets its required milestone.
---

# Open a Sensei LMS Certificates Pull Request

Fill the repo's PR template from the diff and stop for approval before
`gh pr create` — which pushes the branch.

The repo is `woocommerce/sensei-certificates`. The base branch is `trunk`.

## Steps

### 1. Preconditions

- Confirm the current branch is not `trunk`. If it is, stop — nothing to PR.
- Confirm `gh` is authenticated: `gh auth status`. If not, ask the user to run
  `! gh auth login` themselves.

### 2. Understand the change from the diff

Describe what the diff does, not how you got there.

```bash
git merge-base trunk HEAD          # fork point
git log --oneline trunk..HEAD      # commits on this branch
git diff --stat trunk...HEAD       # files touched
git diff trunk...HEAD              # the actual change — read this
```

Read the diff. From it, decide the **title** — one line, imperative, concise, no
conventional-commit prefix (no `feat:` / `fix:`).

### 3. Fill the repo's PR template

Read it and fill it in — don't invent your own headings:

```bash
cat .github/PULL_REQUEST_TEMPLATE.md
```

Per section:

- **`Fixes #`** — fill as `Fixes #<n>` if the user gave an issue number, or one
  appears in the branch name or commit messages. If there's no issue, remove the
  line — a bare `Fixes #` is noise. Never guess a number.
- **`### Changes proposed in this Pull Request`** — the reviewer-facing summary.
  Lead with the problem/need and the user impact, then the approach at a high
  level. Don't re-list modified files or narrate implementation — the diff shows
  that.
- **`### Testing instructions`** — manual steps a reviewer follows to verify
  (click paths, expected on-screen results, edge cases). For certificate changes
  this usually means: complete a course as a student, then check the certificate
  renders, downloads as a PDF, and the "View Certificate" link/block appears.
  Never list running automated suites — there are none.
- **`### New/Updated Hooks`** — fill only if the diff adds or changes an action or
  filter; describe each and its args, and plan to add the **Hooks** label. If the
  diff touches no hooks, remove this whole section from the body.
- **`### Deprecated Code`** — fill only if the diff deprecates something; name the
  replacement and plan to add the **Deprecation** label. Otherwise remove the
  section.
- **`### Screenshot / Video`** — keep only when the change is visual (touches
  `assets/`, blocks, or `templates/`). You can't capture the images yourself, so
  at the approval gate remind the user to attach them. If the change isn't visual,
  remove the section.

### 4. Stop and confirm — this is the push gate

Show the user the proposed **title** and the **filled template body** (call out any
labels: Hooks / Deprecation). If you kept a Screenshot section, remind them to
attach images. Wait for explicit approval. Do not run `gh pr create` until they
say go — it pushes the branch and opens the PR, which is outward-facing and hard
to walk back.

### 5. Create the PR

After approval:

```bash
gh pr create --base trunk --title "<title>" --body "<filled template body>"
```

`gh` pushes the current branch as part of this. Capture the PR number it prints,
then apply any labels you flagged:

```bash
gh pr edit <PR_NUMBER> --add-label "Hooks"
```

### 6. Assign the milestone (required)

Find the next shipping milestone (lowest-versioned open one) and assign it. Use
`sort -V` — a plain sort mis-orders versions (e.g. `2.5.5` vs `2.10.0`):

```bash
next=$(gh api 'repos/woocommerce/sensei-certificates/milestones?state=open' --jq '.[].title' | sort -V | head -1)
gh pr edit <PR_NUMBER> --milestone "$next"
```

If the user named a target release, use that milestone instead of the lowest.

### 7. Wrap up

Give the user the PR URL.
