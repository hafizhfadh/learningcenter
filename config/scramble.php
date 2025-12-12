<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'api_path' => 'api',
    'api_domain' => null,
    'info' => [
        'version' => env('API_VERSION', '1.1.0'),
        'description' => <<<'MARKDOWN'
The Learning Center API documentation is generated automatically using Scramble.

### Authentication Overview

The API uses a dual-token authentication model:

- `APP_TOKEN` – A client identifier header required when calling `POST /login`. This value is configured per client application.
- `token` – A standard Bearer auth token returned from `/login` and used in the `Authorization` header on protected routes.
- `app_token` – An enhanced token returned from `/login` which contains user-specific claims (user ID, organization, issued-at, expiry) and must be sent on all protected routes in addition to the Bearer token.

### Request Flow

1. Configure your client `APP_TOKEN` (provided by the platform) in your backend or frontend environment.
2. Call `POST /login` with:
   - Body: `email`, `password`
   - Header: `APP_TOKEN: {client-app-token}`
3. On success, store both:
   - `data.token` as `Authorization: Bearer {token}`
   - `data.app_token` as `APP_TOKEN: {app_token}` for protected routes
4. For all protected endpoints, send both headers:
   - `Authorization: Bearer {token}`
   - `APP_TOKEN: {app_token}`

### Error Semantics

- `401 Unauthorized` – Missing or invalid auth token or app token.
- `403 Forbidden` – Valid but expired `app_token` or insufficient permissions.

Refer to individual endpoint documentation for detailed request and response schemas.
MARKDOWN,
    ],
    'servers' => null,
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],
    'extensions' => [],
];

