# API de Autenticación

Base URL local:

- http://localhost:8000/api

Autenticación:

- Tipo: Bearer Token (Laravel Sanctum)
- Header: Authorization: Bearer <token>

## Endpoints públicos

### POST /register

Descripción:

- Registra un nuevo usuario y retorna token.

Body JSON:

```json
{
  "name": "Noe",
  "email": "noe@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

Respuestas:

- 201 Created

```json
{
  "message": "Registro exitoso.",
  "user": {
    "id": 1,
    "name": "Noe",
    "email": "noe@example.com"
  },
  "token": "<token>",
  "token_type": "Bearer"
}
```

### POST /login

Descripción:

- Inicia sesión y retorna token.

Rate limit:

- 5 intentos por minuto por combinación email + IP.

Body JSON:

```json
{
  "email": "noe@example.com",
  "password": "Password123!"
}
```

Respuestas:

- 200 OK
- 422 Unprocessable Entity (credenciales inválidas)
- 429 Too Many Requests

429 JSON:

```json
{
  "message": "Demasiadas solicitudes. Intenta nuevamente en unos segundos.",
  "error": "too_many_requests",
  "retry_after": 60
}
```

### POST /forgot-password

Descripción:

- Solicita correo de recuperación.
- Por seguridad, siempre responde 200 con mensaje genérico.

Rate limit:

- 3 intentos por minuto por combinación email + IP.

Body JSON:

```json
{
  "email": "noe@example.com"
}
```

Respuestas:

- 200 OK

```json
{
  "message": "Si el correo existe, se enviaron instrucciones de recuperación."
}
```

- 429 Too Many Requests (mismo formato JSON de arriba)

### POST /reset-password

Descripción:

- Restablece contraseña usando token de recuperación.
- Invalida tokens previos y retorna nuevo token de sesión.

Body JSON:

```json
{
  "token": "<token_recibido_por_email>",
  "email": "noe@example.com",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

Respuestas:

- 200 OK

```json
{
  "message": "Contraseña restablecida correctamente.",
  "token": "<token>",
  "token_type": "Bearer"
}
```

- 422 Unprocessable Entity (token inválido/expirado o validación)

## Endpoints protegidos

### GET /me

Descripción:

- Retorna perfil del usuario autenticado.

Header:

- Authorization: Bearer <token>

Respuesta:

- 200 OK

```json
{
  "user": {
    "id": 1,
    "name": "Noe",
    "email": "noe@example.com"
  },
  "roles": ["usuario"],
  "permissions": ["profile.read"]
}
```

### POST /change-password

Descripción:

- Cambia contraseña autenticado.
- Revoca tokens previos y retorna nuevo token.

Header:

- Authorization: Bearer <token>

Body JSON:

```json
{
  "current_password": "Password123!",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

Respuestas:

- 200 OK

```json
{
  "message": "Contraseña actualizada correctamente.",
  "token": "<token>",
  "token_type": "Bearer"
}
```

- 422 Unprocessable Entity (contraseña actual incorrecta o validación)

### POST /refresh-token

Descripción:

- Revoca token actual y emite uno nuevo.

Header:

- Authorization: Bearer <token>

Respuestas:

- 200 OK

```json
{
  "message": "Token renovado correctamente.",
  "token": "<token>",
  "token_type": "Bearer"
}
```

### POST /logout

Descripción:

- Cierra sesión revocando el token actual.

Header:

- Authorization: Bearer <token>

Respuestas:

- 200 OK

```json
{
  "message": "Sesión cerrada correctamente."
}
```

## Roles y permisos

Roles iniciales:

- admin
- usuario

Permisos iniciales:

- profile.read
- users.read
- users.manage

Reglas aplicadas por defecto:

- Registro nuevo usuario: asigna rol `usuario`
- `usuario`: permiso `profile.read`
- `admin`: todos los permisos iniciales

## Endpoints de práctica de autorización

### GET /admin/ping

Descripción:

- Requiere rol `admin`.

Header:

- Authorization: Bearer <token>

Respuestas:

- 200 OK (si tiene rol admin)
- 403 Forbidden (si no tiene rol admin)

### GET /admin/users

Descripción:

- Requiere permiso `users.read`.
- Devuelve listado paginado de usuarios con sus roles y permisos.

Query params opcionales:

- `page` (int, default 1)
- `per_page` (int, default 15, max 100)
- `search` (string, busca por nombre o email)
- `sort_by` (string, permitido: `id`, `name`, `email`)
- `sort_dir` (string, `asc` o `desc`)

Header:

- Authorization: Bearer <token>

Respuestas:

- 200 OK (si tiene permiso)
- 403 Forbidden (si no tiene permiso)

Estructura estándar de paginación (reutilizable en endpoints de alto volumen):

```json
{
  "items": [
    {
      "id": 1,
      "name": "Noe",
      "email": "noe@example.com",
      "roles": ["admin"],
      "permissions": ["users.read", "users.manage"]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 120,
    "last_page": 8,
    "from": 1,
    "to": 15,
    "has_more_pages": true
  },
  "links": {
    "first": "http://localhost:8000/api/admin/users?page=1",
    "last": "http://localhost:8000/api/admin/users?page=8",
    "prev": null,
    "next": "http://localhost:8000/api/admin/users?page=2"
  }
}
```

### GET /admin/roles

Descripción:

- Requiere permiso `users.read`.
- Lista roles disponibles con sus permisos (paginado).

Query params opcionales:

- `page` (int, default 1)
- `per_page` (int, default 15, max 100)
- `search` (string, busca por nombre de rol)
- `sort_by` (string, permitido: `id`, `name`)
- `sort_dir` (string, `asc` o `desc`)

### GET /admin/permissions

Descripción:

- Requiere permiso `users.read`.
- Lista permisos disponibles (paginado).

Query params opcionales:

- `page` (int, default 1)
- `per_page` (int, default 15, max 100)
- `search` (string, busca por nombre de permiso)
- `sort_by` (string, permitido: `id`, `name`)
- `sort_dir` (string, `asc` o `desc`)

Respuesta de ambos endpoints:

- Usan la estructura estándar de paginación (`items`, `pagination`, `links`).

### POST /admin/roles

Descripción:

- Requiere permiso `users.manage`.
- Crea un nuevo rol.

Body JSON:

```json
{
  "name": "editor"
}
```

### PATCH /admin/roles/{id}

Descripción:

- Requiere permiso `users.manage`.
- Actualiza nombre de rol.

Body JSON:

```json
{
  "name": "editor-jr"
}
```

### DELETE /admin/roles/{id}

Descripción:

- Requiere permiso `users.manage`.
- Elimina rol (excepto roles base `admin` y `usuario`).

### POST /admin/roles/{id}/sync-permissions

Descripción:

- Requiere permiso `users.manage`.
- Reemplaza permisos del rol por la lista enviada.

Body JSON:

```json
{
  "permissions": ["users.read", "users.manage"]
}
```

### POST /admin/permissions

Descripción:

- Requiere permiso `users.manage`.
- Crea un nuevo permiso.

Body JSON:

```json
{
  "name": "posts.publish"
}
```

### PATCH /admin/permissions/{id}

Descripción:

- Requiere permiso `users.manage`.
- Actualiza nombre de permiso.

Body JSON:

```json
{
  "name": "posts.publish.own"
}
```

### DELETE /admin/permissions/{id}

Descripción:

- Requiere permiso `users.manage`.
- Elimina permiso (excepto permisos base `profile.read`, `users.read`, `users.manage`).

### POST /admin/users/{id}/assign-role

Descripción:

- Requiere permiso `users.manage`.
- Asigna un rol al usuario.

Body JSON:

```json
{
  "role": "admin"
}
```

### POST /admin/users/{id}/remove-role

Descripción:

- Requiere permiso `users.manage`.
- Quita un rol al usuario.

Body JSON:

```json
{
  "role": "admin"
}
```

### POST /admin/users/{id}/sync-roles

Descripción:

- Requiere permiso `users.manage`.
- Reemplaza roles actuales por la lista enviada.

Body JSON:

```json
{
  "roles": ["usuario", "admin"]
}
```

### POST /admin/users/{id}/give-permission

Descripción:

- Requiere permiso `users.manage`.
- Asigna permiso directo al usuario.

Body JSON:

```json
{
  "permission": "users.read"
}
```

### POST /admin/users/{id}/revoke-permission

Descripción:

- Requiere permiso `users.manage`.
- Revoca permiso directo del usuario.

Body JSON:

```json
{
  "permission": "users.read"
}
```

### POST /admin/users/{id}/sync-permissions

Descripción:

- Requiere permiso `users.manage`.
- Reemplaza permisos directos por la lista enviada.

Body JSON:

```json
{
  "permissions": ["users.read", "users.manage"]
}
```

## Configuración para Next.js

Variables importantes en backend:

- FRONTEND_URL=http://localhost:3000
- CORS_ALLOWED_ORIGINS=http://localhost:3000

Notas:

- El correo de recuperación genera URL hacia FRONTEND_URL/reset-password?token=...&email=...
- Los errores de validación usan formato estándar de Laravel con código 422.
- Para inicializar roles/permisos en desarrollo ejecuta: `php artisan db:seed`.
