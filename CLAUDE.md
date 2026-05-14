# lsuce-moodle — Claude Code Context

## Project Skills

Project-specific skills are in `.claude/skills/`. They are the Claude equivalents of the Cursor skills in `.cursor/skills/`.

| Skill | Trigger |
|---|---|
| `deploy-testrusso` | Deploying/merging to David's testrusso dev server via SSH |
| `github-pr` | GitHub PR review, comment, merge via `gh` CLI |
| `jira-md-board` | Jira MD board queries, ticket status, transitions |
| `moodle-php` | PHP plugin architecture, coding standards |
| `moodle-templates` | Mustache templates best practices |
| `moodle-js-modules` | AMD/ESM JavaScript modules |
| `moodle-webservices` | External API / web service classes |
| `moodle-phpunit` | PHPUnit tests |
| `moodle-behat` | Behat acceptance tests |
| `moodle-docker-cli` | Docker environment, CLI commands |
| `moodle-bootstrap-uiux` | Bootstrap 5 / UI/UX patterns |
| `babysit-pr` | Monitor a PR until merge-ready |

## Ticket Tracking

Work-in-progress assets and notes for Jira tickets live in `./tickets/<TICKET-KEY>/` at the repo root (e.g. `./tickets/MD-2148/`). Create this folder when starting work on a ticket and store any reference files, screenshots, or notes there.

**CRITICAL: `tickets/` is LOCAL-ONLY — NEVER commit it to git.**
- Never run `git add tickets/` or `git add -f tickets/`
- Never stage any file whose path starts with `tickets/`
- The folder is gitignored intentionally; `-f` bypasses that — do not use it here
- Bug notes, screenshots, playwright tests, PDFs, and research stay local forever

## Sub-Agents

**Default to Cursor CLI for all sub-agent work in this project.** Only fall back to the built-in `Agent` tool when the task has zero need for the codebase (e.g. running a known Python script, a web search, a one-shot shell command).

### Correct invocation

```bash
cd /home/homelab/work/lsu/lsuce-moodle && cursor agent "<prompt>"
```

- The shim at `~/.local/bin/cursor` routes `cursor agent` to `~/.local/bin/agent`. Any other flag (`--headless`, `--model`, etc.) errors out.
- Always `cd` to the repo root first — subdirectory sandboxing breaks cross-tree file access.
- Requires one-time browser auth via `agent login`. Credentials persist after that.

### When to use Cursor CLI (default)
- Code exploration, reading/writing PHP/JS/Mustache files
- Generating markdown documents about the codebase
- Any task where repo context helps

### When the built-in Agent tool is acceptable
- Running a specific known script (e.g. `python3 generate_pdf.py`)
- Pure web searches or API calls with no file writes into the repo
- Isolated shell commands that don't need codebase understanding

## Key Facts

- Moodle version: 4.5
- PHP: 8.3
- Database: MariaDB (Docker, port 13307)
- Web: localhost:8099
- **`lsuce-dvdcastro.ngrok.dev`** = the local Docker instance exposed via ngrok — NOT a remote server, do not SSH anywhere
- Remote dev server: `testrusso` → `/var/www/dcastr10`
- GitHub account: `dvdcastro`
- Jira: `lsu-oceit.atlassian.net`, project key `MD`
- Main branch: `develop` (PRs target `develop`; `feature/fourOneMerge` is old and unused)
- New target branch (in progress): `MOODLE_401_MAIN` — will replace `develop` as the merge target once created
- Plugin repos on GitHub (lsuonline org):
  - `moodle-block_backadel` → maps to `blocks/backadel/` in this monorepo
  - `moodle-block_simple_restore` → maps to `blocks/simple_restore/` in this monorepo
  - Each plugin repo receives its own filtered git history via `git filter-repo --subdirectory-filter`
- Run all Moodle commands via: `./run-docker-exec.sh <cmd>`
