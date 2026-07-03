# Deploying Reportly to cPanel

This app is built to run on shared cPanel hosting with **Git Version Control** available but **no SSH, Composer, or Node access**. That shapes several decisions documented here — don't "fix" them by ripping out `vendor/`/`public/build/` from git without re-reading this file.

## One-time setup

1. **cPanel → Git Version Control → Create**, pointing at `https://github.com/nishchay-jdg/reportly.git`, branch `main`. cPanel clones it into a repo path (e.g. `/home/USER/repositories/reportly`).

2. **Document root.** Laravel's entry point is `public/`, not the repo root — cPanel needs the domain's document root pointed at `<repo-path>/public`, not `<repo-path>`. If you're deploying to an addon domain or subdomain, set its document root there in cPanel → Domains. If you're stuck serving from the account's root `public_html` and can't repoint it, you'll need cPanel's "Deploy" copy-to-`public_html` step to place `public/`'s contents at the webroot while keeping the rest of the app outside it — ask your host if unsure, since serving the whole repo (with `.env`, `app/`, etc.) from a public webroot is a real security hole.

3. **Create the MySQL database** via cPanel → MySQL Databases, and a DB user with full privileges on it.

4. **Copy `.env.example` to `.env`** in the deployed app directory and fill in for production:
   - `APP_ENV=production`
   - `APP_DEBUG=false` — never `true` in production; it leaks stack traces (including env values) to visitors.
   - `APP_URL=https://yourdomain.com`
   - `DB_*` — the database/user/password created above.
   - `MAIL_MAILER=smtp` with cPanel's mail host/port/credentials (or your own SMTP provider). Leaving it as `log` means no notification email — comment alerts, first-view alerts, and agreement-signed emails — ever actually sends.
   - `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` are all `database` by default — that's intentional, since shared hosting usually has no Redis and no persistent queue worker (see below).

5. **Generate the app key** — there's no SSH to run `php artisan key:generate`, so either:
   - Use cPanel's "Terminal" feature if your plan includes one, or
   - Generate it locally (`php artisan key:generate --show`) and paste the value into `.env`'s `APP_KEY` on the server.

## Every deploy

cPanel's Git Version Control has a "Pull or Deploy" / "Update from Remote" action — use that to pull `main`. Since there's no Composer/Node step available, `vendor/` and `public/build/` are committed to git and travel with the pull; you don't need to run an install step.

After pulling, you still need to run migrations — there's no SSH, so use one of:
- cPanel's Terminal (if available on your plan): `php artisan migrate --force`
- A cPanel **Cron Job** that runs once (`php /path/to/app/artisan migrate --force`), which you trigger manually via "Run Now" if cPanel supports it, then disable/delete afterward
- If neither is available, ask your host — some cPanel setups expose a "Run Composer/Artisan Commands" button in the Git Version Control UI itself

**`--force` is required** — Laravel refuses to run migrations in `APP_ENV=production` without it, since it's a safety check against exactly this kind of unattended deploy.

## Filesystem permissions

These directories are written to at runtime and must be writable by the web server user:
- `storage/` (logs, framework cache/sessions/views)
- `public/media/` — media library uploads, created per-organization on first upload
- `public/brand-logos/` — brand kit logos, created per-organization on first upload

Both `public/media` and `public/brand-logos` are git-ignored (see `.gitignore`) since they're user-uploaded runtime content, not source — they won't exist after a fresh clone until someone uploads something, at which point the app creates them itself (`File::ensureDirectoryExists()`). No manual `mkdir` needed, just confirm the web server user can write to `public/`.

## Things that are intentionally different from a typical Laravel deploy

- **No queue worker.** Notification emails (`ReportFirstViewed`, `NewCommentPosted`, `AgreementSigned`) send synchronously inside the request instead of being queued, because there's no persistent process to run `php artisan queue:work` on shared hosting — only cron. A slow SMTP response adds latency to that one request rather than silently vanishing into an unprocessed queue.
- **No WebSockets.** Live comment updates on the share page poll the server every 5 seconds instead of using broadcasting, for the same reason.
- **Media and brand-logo uploads live under `public/`, not the `storage/` disk.** The `storage:link` symlink Laravel normally uses for public file access doesn't reliably survive being re-created on every git-based deploy on shared hosting, so both features write directly to `public/media/{org_id}/...` and `public/brand-logos/{org_id}/...` and reference them with a plain `asset()` URL.

## Smoke test after deploying

1. Visit the site — should show the Reportly landing page, not a Laravel error page.
2. Register an account, confirm the org gets created.
3. Create a project from a template, save an edit, confirm it persists.
4. Create a public share link, open it in an incognito tab, leave a comment.
5. Check `storage/logs/laravel.log` for any errors from the above before calling it done.
