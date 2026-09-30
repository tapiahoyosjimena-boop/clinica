# Clínica Norte — Sistema de Laboratorio

Sistema web de gestión clínica (laboratorio e imágenes) para **Clínica Norte**.

## Stack

- **Backend:** Laravel 11, Filament 3, PHP 8.2+
- **Base de datos:** MySQL
- **Frontend:** Blade, CSS/JS estáticos en `public/` (no requiere Node.js ni Vite)
- **Autenticación:** sesión web + Spatie Permission (panel admin, portal paciente, portal médico)

## Desarrollo local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Accesos habituales:

- Panel admin: `/admin`
- Portal paciente: `/paciente/login`
- Portal médico: `/medico/login`

## Documentación adicional

- Guía de despliegue y datos demo: [DEPLOY.md](DEPLOY.md)

## Comandos útiles

```bash
php artisan samples:setup-permissions   # permisos legacy de muestras (si aplica)
php artisan permissions:reconcile-users
php artisan clinica:ensure-admin
```
