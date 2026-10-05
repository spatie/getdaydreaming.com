# Daydreaming website

The landing page for Daydreaming, a Mac app that adapts a chosen wallpaper to the time and weather. The site is a small Laravel application with one Blade page and a Vite-built stylesheet and script.

## Local development

```sh
composer install
cp .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan serve
```

Run `php artisan test` for the homepage smoke test. No database, payment system, download endpoint, or license backend is needed for this site.
