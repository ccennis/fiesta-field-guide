# base-laravel-react

Base scaffold for a Laravel + React project with a consistent JSON API pattern.

## Laravel API

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Controller.php` | Base controller with `success()`, `created()`, `noContent()`, `error()`, `notFound()` — all return `{ success, message, data, errors }` |
| `app/Services/BaseService.php` | Abstract base service to extend per feature |
| `app/Services/PostService.php` | Example service — `all()`, `find()`, `create()`, `update()`, `delete()` |
| `app/Models/Post.php` | Model with `title`, `body`, `user_id` + `user()` relationship |
| `app/Http/Controllers/PostController.php` | Thin controller delegating to `PostService` |
| `bootstrap/app.php` | Exception handler for `ModelNotFoundException` (404), `ValidationException` (422), generic `Throwable` (500) |
| `routes/api.php` | `Route::apiResource('posts', PostController::class)` |
| `database/migrations/..._create_posts_table.php` | `user_id` (FK), `title`, `body`, timestamps |

## React Frontend

| File | Purpose |
|------|---------|
| `resources/js/hooks/useApi.js` | `useApi()` hook with `useState`/`useCallback`, returns `{ data, loading, error, get, post, put, destroy }` |
| `resources/js/components/PostList.jsx` | Functional component fetching posts via `useApi` on mount |
| `resources/js/app.jsx` | Entry point — mounts `PostList` with `createRoot` |
| `vite.config.js` | Vite configured with `@vitejs/plugin-react` |

## Getting Started

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
```

## API Response Shape

All API responses follow this shape:

```json
{
    "success": true,
    "message": "OK",
    "data": null,
    "errors": null
}
```
