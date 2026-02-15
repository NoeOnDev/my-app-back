<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## API Auth Endpoints

Base URL local: `http://localhost:8000/api`

### Registro

- **POST** `/register`
- Body JSON:

```json
{
	"name": "Noe",
	"email": "noe@example.com",
	"password": "Password123!",
	"password_confirmation": "Password123!"
}
```

### Inicio de sesión

- **POST** `/login`
- Rate limit: **5 intentos por minuto** por combinación de `email + IP`
- Body JSON:

```json
{
	"email": "noe@example.com",
	"password": "Password123!"
}
```

### Cambio de contraseña

- **POST** `/change-password`
- Header: `Authorization: Bearer <token>`
- Body JSON:

```json
{
	"current_password": "Password123!",
	"password": "NewPassword123!",
	"password_confirmation": "NewPassword123!"
}
```

### Perfil autenticado

- **GET** `/me`
- Header: `Authorization: Bearer <token>`

### Cerrar sesión

- **POST** `/logout`
- Header: `Authorization: Bearer <token>`

### Refrescar token

- **POST** `/refresh-token`
- Header: `Authorization: Bearer <token>`

### Forgot password

- **POST** `/forgot-password`
- Rate limit: **3 intentos por minuto** por combinación de `email + IP`
- Body JSON:

```json
{
	"email": "noe@example.com"
}
```

### Reset password

- **POST** `/reset-password`
- Body JSON:

```json
{
	"token": "<token_recibido_por_email>",
	"email": "noe@example.com",
	"password": "NewPassword123!",
	"password_confirmation": "NewPassword123!"
}
```

> Para frontend externo, configura `FRONTEND_URL` en `.env`. El enlace del correo de recuperación apuntará a:
> `FRONTEND_URL/reset-password?token=...&email=...`

### Respuesta de autenticación

Los endpoints de registro e inicio de sesión retornan un token tipo Bearer:

```json
{
	"message": "Inicio de sesión exitoso.",
	"user": {
		"id": 1,
		"name": "Noe",
		"email": "noe@example.com"
	},
	"token": "<token>",
	"token_type": "Bearer"
}
```

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
