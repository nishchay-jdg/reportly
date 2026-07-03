# Reportly

Send a live link. Not another PDF.

Reportly is a multi-tenant tool for sales, SEO, and marketing teams to build client reports, proposals, pricing pages, and agreements as real HTML/CSS/JS — then share them as a single link clients can view, comment on, and sign, instead of sending static files.

## Features

- **Multi-file editor** — HTML/CSS/JS files per project with a CodeMirror-based editor (autocomplete, syntax highlighting) and a live preview pane.
- **Starter templates** — blank, SEO report, pricing page, proposal, and agreement/NDA (with a typed signature, terms checkbox, and email notification on signing).
- **Shareable links** — public or password-protected, optional expiry, custom or random slugs.
- **Client comments** — Figma-style click-to-pin comments and replies directly on the live report, with guest identity handled via a long-lived cookie so guests can only delete their own comments.
- **Approval/sign-off** — clients can approve a report or request changes right from the share page.
- **Version history** — snapshot a project and restore it later; restoring always backs up the current state first.
- **Media library** — upload once, reuse the file's URL across any report.
- **Dashboard at scale** — server-side search, sort, tagging, and folders, built to stay fast with hundreds of reports.
- **Email notifications** — notify your team when a client first opens a report, leaves a comment, or signs an agreement.
- **Admin panel** — platform admins can see all organizations, users, and projects across tenants.

## Stack

- Laravel (Blade, Eloquent, Breeze auth)
- MySQL
- Alpine.js + Tailwind CSS
- CodeMirror 6 (via ESM CDN)
- Vite

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure a MySQL database in `.env`, then:

```bash
php artisan migrate
npm run build
php artisan serve
```

For local development, set `MAIL_MAILER=log` so notification emails write to `storage/logs/laravel.log` instead of sending.

## Deployment notes

This app is built to run on shared hosting without SSH, Composer, or Node access at the server (only Git deployment via cPanel):

- `vendor/` and `public/build/` are committed so no build step is needed on the server.
- Comment updates use polling rather than WebSockets, since there's no persistent socket/queue infrastructure.
- Notification emails send synchronously rather than through a queue, since there's no queue worker — only cron.
