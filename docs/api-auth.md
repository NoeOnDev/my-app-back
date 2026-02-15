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
  }
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

## Configuración para Next.js

Variables importantes en backend:

- FRONTEND_URL=http://localhost:3000
- CORS_ALLOWED_ORIGINS=http://localhost:3000

Notas:

- El correo de recuperación genera URL hacia FRONTEND_URL/reset-password?token=...&email=...
- Los errores de validación usan formato estándar de Laravel con código 422.
