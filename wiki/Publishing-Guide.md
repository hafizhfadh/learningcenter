# Publishing Guide

Back to [Home](Home.md)

This guide explains how to synchronize the repository’s `wiki/` folder with the GitHub Wiki so your documentation stays organized and up to date.

## Prerequisites
- GitHub Wiki enabled for your repository
- Access to push to the wiki (same repository permissions)
- Git installed locally

## Recommended structure
- Keep editable sources for wiki pages under `wiki/` in the main repository
- Use a shared sidebar (`_Sidebar.md`) and footer (`_Footer.md`) to ensure consistent navigation and metadata across all pages

## Quick publish (manual)
1. Clone the GitHub Wiki repository locally (appears as a separate repo):
   ```bash
   git clone https://github.com/hafizhfadh/learningcenter.wiki.git wiki-publish
   ```
2. Copy the local wiki content to the wiki repo:
   ```bash
   rsync -av --delete wiki/ wiki-publish/
   # Alternatively:
   # cp -R wiki/* wiki-publish/
   ```
3. Update the footer’s last updated timestamp:
   - Linux:
     ```bash
     LAST_UPDATED="$(date -u '+%Y-%m-%d %H:%M:%S UTC')"
     sed -i "s/{{LAST_UPDATED}}/${LAST_UPDATED}/" wiki-publish/_Footer.md
     ```
   - macOS:
     ```bash
     LAST_UPDATED="$(date -u '+%Y-%m-%d %H:%M:%S UTC')"
     sed -i '' "s/{{LAST_UPDATED}}/${LAST_UPDATED}/" wiki-publish/_Footer.md
     ```
   - Cross-platform (Perl):
     ```bash
     LAST_UPDATED="$(date -u '+%Y-%m-%d %H:%M:%S UTC')"
     perl -pi -e "s/\{\{LAST_UPDATED\}\}/${LAST_UPDATED}/g" wiki-publish/_Footer.md
     ```
4. Commit and push:
   ```bash
   cd wiki-publish
   git add .
   git commit -m "Publish wiki pages ($(date -u '+%Y-%m-%d'))"
   # Many GitHub Wikis use the 'master' branch by default; if your wiki uses 'main', adjust the push target accordingly.
   git push origin master
   ```

## Automation script (optional)
Add a helper script to your main repo (e.g. `scripts/publish-wiki.sh`) to streamline publishing:
```bash
#!/usr/bin/env bash
set -euo pipefail
REPO_SLUG="hafizhfadh/learningcenter"
WIKI_REPO_URL="https://github.com/${REPO_SLUG}.wiki.git"
WIKI_LOCAL_DIR="wiki-publish"
SOURCE_DIR="wiki"

if [ ! -d "$SOURCE_DIR" ]; then
  echo "Source wiki directory '$SOURCE_DIR' not found" >&2
  exit 1
fi

# Clone or update wiki repo
if [ ! -d "$WIKI_LOCAL_DIR/.git" ]; then
  git clone "$WIKI_REPO_URL" "$WIKI_LOCAL_DIR"
else
  (cd "$WIKI_LOCAL_DIR" && git pull --rebase)
fi

# Sync content
rsync -av --delete "$SOURCE_DIR"/ "$WIKI_LOCAL_DIR"/

# Update footer timestamp
LAST_UPDATED="$(date -u '+%Y-%m-%d %H:%M:%S UTC')"
perl -pi -e "s/\{\{LAST_UPDATED\}\}/${LAST_UPDATED}/g" "$WIKI_LOCAL_DIR/_Footer.md"

# Commit and push
cd "$WIKI_LOCAL_DIR"
if ! git diff --quiet; then
  git add .
  git commit -m "Publish wiki pages (${LAST_UPDATED})"
  # Change 'master' to 'main' if your wiki uses main
  git push origin master
else
  echo "No changes to publish"
fi
```

## Linking best practices
- Use relative links between wiki pages: `[Development](Development.md)`
- Link to repository files using canonical GitHub URLs when needed (e.g., Dockerfile, CI workflow)
- Keep page names simple and stable (avoid spaces where possible)

## Troubleshooting
- Permission errors: ensure you have push access to the wiki
- Broken links: verify page names and relative paths; check `_Sidebar.md` for navigation
- Footer not updating: confirm the placeholder `{{LAST_UPDATED}}` still exists and your replacement commands ran successfully

## See also
- [Home](Home.md)
- [References](References.md)