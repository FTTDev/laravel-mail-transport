# fleet-tracking-technology/laravel-mail-transport

**Fleet Tracking Technology** — Laravel mailer **`microsoft_graph`** (Symfony Microsoft Graph bridge) + optional **`MailTransportResolver`** for database-driven settings.

## Install from GitHub (Composer VCS)

In your Laravel app `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/YOUR_ORG/laravel-mail-transport.git"
    }
],
"require": {
    "fleet-tracking-technology/laravel-mail-transport": "dev-main"
}
```

Replace the URL with your real repo (HTTPS or `git@github.com:...`). Then run `composer update fleet-tracking-technology/laravel-mail-transport`.

If your app has `"minimum-stability": "stable"` (Laravel default), Composer may refuse `dev-main` unless you allow dev for this package only:

```json
"require": {
    "fleet-tracking-technology/laravel-mail-transport": "dev-main@dev"
}
```

Or tag a release on GitHub (e.g. `v1.0.0`) and require a normal range:

```json
"require": {
    "fleet-tracking-technology/laravel-mail-transport": "^1.0"
}
```

Private repo: use a [GitHub deploy token / SSH key](https://getcomposer.org/doc/articles/authentication.md) so Composer can clone.

## Troubleshooting: “Could not find a matching version” / stability

That message almost always means **Composer has no source for the package**:

1. **Not on Packagist** — this name is not published there yet. A bare `composer require fleet-tracking-technology/laravel-mail-transport` **without** a `repositories` entry will fail.
2. **Add `repositories` first** — use `path` (monorepo) or `vcs` (GitHub) as in the sections above, then `composer update`, or `composer require "fleet-tracking-technology/laravel-mail-transport:dev-main@dev"` after the VCS repo is in `composer.json`.
3. **Monorepo subfolder** — if the Git repo root is your whole app and `composer.json` for this library is only under `packages/...`, Composer’s `vcs` type **cannot** install that subfolder as a package. The GitHub repo for VCS install must have **`composer.json` at the repository root** (see “Publish this package to GitHub”).
4. **Wrong branch** — default branch might be `master` not `main`; use `dev-master` or rename the branch to match your constraint.

## Install (path / monorepo)

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/fleet-tracking-technology/laravel-mail-transport",
        "options": { "symlink": true }
    }
],
"require": {
    "fleet-tracking-technology/laravel-mail-transport": "@dev"
}
```

Run `composer update`. Laravel auto-discovers `LaravelMailTransportServiceProvider`.

## Publish this package to GitHub (first time)

Do **not** run `git init` inside this folder if it already lives inside another Git repo (nested `.git` causes confusion). Pick one:

### Option A — copy to a new folder (simplest)

1. On GitHub: **New repository** → name e.g. `laravel-mail-transport` → empty, no README.
2. Copy the whole `laravel-mail-transport` directory to a path **outside** your main app (e.g. `C:\src\laravel-mail-transport`).
3. In that copy:

```bash
git init
git add .
git commit -m "Initial commit: Fleet Tracking Technology laravel-mail-transport"
git branch -M main
git remote add origin https://github.com/YOUR_ORG/laravel-mail-transport.git
git push -u origin main
```

4. In the app, switch from `path` to `vcs` (see above) and run `composer update`.

### Option B — stay inside monorepo: `git subtree split`

From the **root** of the main repository:

```bash
git subtree split -P packages/fleet-tracking-technology/laravel-mail-transport -b fft-mail-transport-split
git push https://github.com/YOUR_ORG/laravel-mail-transport.git fft-mail-transport-split:main
```

Then use the GitHub URL as a `vcs` repository in other projects.

## Env-only

Set `MAIL_MAILER=microsoft_graph` and `MAIL_GRAPH_*` in `.env`. Optionally:

`php artisan vendor:publish --tag=mail-transport-config`

## Custom resolver (e.g. Spatie settings)

Implement `FleetTrackingTechnology\LaravelMailTransport\Contracts\MailTransportResolver` and register:

```php
$this->app->singleton(MailTransportResolver::class, YourResolver::class);
```

After runtime changes: `MailTransportApplier::applyFromContainer();` and `Mail::purge();`

## Test

`php artisan mail:test you@example.com`

## Requirements

- **PHP** 8.1+
- **Laravel** 10 / 11 / 12 (uses Symfony Mailer 6+ already shipped with Laravel)
- Microsoft Entra app with **Application** permission **Mail.Send** + admin consent

Graph sending is implemented with `symfony/http-client` (no separate `symfony/microsoft-graph-mailer` package), so it stays compatible with Laravel 10’s Symfony 6 mail stack.
