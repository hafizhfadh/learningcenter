# Learning Center Wiki

A modern Learning Management System (LMS) built with Laravel. It features course management, learning paths, progress tracking, and an administrative panel powered by Filament. The application is optimized for high concurrency using Laravel Octane atop FrankenPHP.

## Navigation
- [Development](Development.md)
- [Production Deployment](Production-Deployment.md)
- [Contribution Guidelines](Contribution-Guidelines.md)
- [Architecture & Infrastructure](Architecture-Infrastructure.md)
- [Makefile Cheat Sheet](Makefile-Cheat-Sheet.md)
- [References](References.md)
- [License](License.md)

## About
This wiki organizes the project documentation into focused pages based on the README. It also links to supplementary documents in the repository `docs/` directory and other relevant files.

### Supplementary Documents (from the repository)
- CI/CD Pipeline: [docs/CICD.md](../docs/CICD.md) • GitHub view: https://github.com/hafizhfadh/learningcenter/blob/main/docs/CICD.md
- Deployment Guide: [docs/DEPLOYMENT.md](../docs/DEPLOYMENT.md) • GitHub view: https://github.com/hafizhfadh/learningcenter/blob/main/docs/DEPLOYMENT.md
- Agent Guidelines: [AGENTS.md](../AGENTS.md) • GitHub view: https://github.com/hafizhfadh/learningcenter/blob/main/AGENTS.md

### Publishing to GitHub Wiki
To publish these pages to the GitHub Wiki:
1. Ensure your repository has Wiki enabled on GitHub.
2. Clone the wiki repository locally:
   ```bash
   git clone https://github.com/hafizhfadh/learningcenter.wiki.git wiki
   ```
3. Copy the files from this `wiki/` folder into the cloned `wiki` repo.
4. Commit and push:
   ```bash
   cd wiki
   git add .
   git commit -m "Publish wiki pages"
   git push origin master
   ```

Back to [Home](Home.md).