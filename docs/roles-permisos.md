# Guía rápida de roles y permisos

Esta guía resume cómo practicar autorización en backend (Laravel) y frontend (Next.js).

## Paquete utilizado

- spatie/laravel-permission

## Qué ya está implementado

- Trait `HasRoles` en el modelo `User`.
- Middleware alias disponibles:
  - `role`
  - `permission`
  - `role_or_permission`
- Seeder base:
  - `Database\\Seeders\\RolesAndPermissionsSeeder`
- Estructura reusable de paginación API:
  - `App\\Support\\Pagination\\PaginatedResponse`
- Validación reusable de índices paginados:
  - `App\\Http\\Requests\\Api\\PaginatedIndexRequest`
- Helpers reusables de consulta:
  - `App\\Support\\Queries\\AppliesSearch`
  - `App\\Support\\Queries\\AppliesSorting`
- Servicio base reusable para listados admin:
  - `App\\Support\\Queries\\BaseAdminIndexQuery`

## Roles y permisos iniciales

Roles:

- `admin`
- `usuario`

Permisos:

- `profile.read`
- `users.read`
- `users.manage`

Asignación inicial:

- `admin` => todos los permisos iniciales.
- `usuario` => `profile.read`.

## Flujo actual de autenticación/autorización

- Al registrarse (`/api/register`) el usuario recibe rol `usuario`.
- En `/api/login` y `/api/me` se devuelven también:
  - `roles`
  - `permissions`

Esto permite que Next.js haga control de acceso por UI sin esperar otra consulta.

## Endpoints para practicar

- `GET /api/me` (autenticado)
- `GET /api/admin/ping` (rol `admin`)
- `GET /api/admin/users` (permiso `users.read`)
- `GET /api/admin/roles` (permiso `users.read`)
- `GET /api/admin/permissions` (permiso `users.read`)
- `POST /api/admin/roles` (permiso `users.manage`)
- `PATCH /api/admin/roles/{id}` (permiso `users.manage`)
- `DELETE /api/admin/roles/{id}` (permiso `users.manage`)
- `POST /api/admin/roles/{id}/sync-permissions` (permiso `users.manage`)
- `POST /api/admin/permissions` (permiso `users.manage`)
- `PATCH /api/admin/permissions/{id}` (permiso `users.manage`)
- `DELETE /api/admin/permissions/{id}` (permiso `users.manage`)
- `POST /api/admin/users/{id}/assign-role` (permiso `users.manage`)
- `POST /api/admin/users/{id}/remove-role` (permiso `users.manage`)
- `POST /api/admin/users/{id}/sync-roles` (permiso `users.manage`)
- `POST /api/admin/users/{id}/give-permission` (permiso `users.manage`)
- `POST /api/admin/users/{id}/revoke-permission` (permiso `users.manage`)
- `POST /api/admin/users/{id}/sync-permissions` (permiso `users.manage`)

## Ejemplos frontend (Next.js)

Regla por rol:

- Mostrar panel admin si `roles.includes('admin')`.

Regla por permiso:

- Mostrar tabla de usuarios si `permissions.includes('users.read')`.

Paginación estándar reusable:

- Consumir `items` para renderizar filas.
- Usar `pagination` para estado de páginas.
- Usar `links.next` y `links.prev` para navegación.
- Ya aplicada en: `/api/admin/users`, `/api/admin/roles`, `/api/admin/permissions`.

## Comandos útiles

Inicializar datos:

- `php artisan migrate`
- `php artisan db:seed`

Ejecutar tests auth + autorización:

- `php artisan test --filter=AuthApiTest`
