# Clínica Norte — Guía de Despliegue (Demo Nube)

## Requisitos del servidor

| Componente | Versión mínima |
|---|---|
| PHP | 8.2+ |
| MySQL | 8.0+ |
| Composer | 2.x |
| Extensiones PHP | pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, gd, zip |

---

## 1. Clonar el repositorio

```bash
git clone <URL_REPOSITORIO> clinica_norte
cd clinica_norte
```

---

## 2. Instalar dependencias

```bash
composer install --no-dev --optimize-autoloader
```

---

## 3. Configurar entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los valores reales:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TU_DOMINIO_O_IP

DB_HOST=...
DB_DATABASE=clinica_norte
DB_USERNAME=...
DB_PASSWORD=...

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=no-reply@clinica-norte.bo

LIBELULA_APPKEY=...
CLINIC_BANK_NAME=...
CLINIC_ACCOUNT_NUMBER=...
```

---

## 4. Base de datos

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan clinica:ensure-admin
```

En `APP_ENV=production`, el seeder inicializa roles y permisos sin crear cuentas de personal demo y omite el inventario de reactivos de demostración. El comando `clinica:ensure-admin` solicitará la contraseña de forma oculta y su confirmación; usa una contraseña única que cumpla la política configurada. No pases contraseñas como argumentos de terminal.

> **Nota:** `db:seed` carga automáticamente (en este orden):
>
> 1. **AuthSeeder** — roles y permisos del sistema; las cuentas de personal demo solo se crean fuera de producción.
> 2. **ImagingEquipmentSeeder** — equipos de rayos X, ecógrafo y tomógrafo.
> 3. **CatalogSeeder** — 10 categorías y 46 exámenes del catálogo.
> 4. **ExamRequirementsSeeder** — requisitos previos por examen (ayuno, preparación, etc.).
> 5. **LaboratoryExamParametersSeeder** — parámetros analíticos por categoría de laboratorio.
> 6. **PaymentMethodSeeder** — métodos de pago (Efectivo, QR).
> 7. **ReactivosSeeder** — proveedores y reactivos de demostración (solo fuera de producción).
>
> En VPS con catálogo ya cargado, solo requisitos (reemplaza los de cada examen del catálogo):
>
> ```bash
> php artisan db:seed --class=ExamRequirementsSeeder --force
> ```
>
> Solo preparación clínica (ayuno, muestra, medicación, etc.); sin repetir orden médica ni CI en cada examen.
>
> Los seeders son **idempotentes**: no duplican registros si se ejecutan más de una vez.

### Datos demo (solo desarrollo o staging aislado)

No ejecutes estos seeders en producción: crean usuarios y datos de prueba con credenciales predecibles.

En desarrollo o staging aislado, tras el seed base, se pueden poblar pacientes de prueba adicionales:

```bash
php artisan db:seed --class=DoctorsDemoSeeder --force
php artisan db:seed --class=PatientsDemoSeeder --force
# o, cuando existan más módulos:
php artisan db:seed --class=DemoDataSeeder --force
```

| Médico demo | Portal (email) | Contraseña |
|-------------|----------------|------------|
| Dra. Laura Fernández Soria | medico1@tecnoweb.shop | Medico@2026$ |

```bash
php artisan db:seed --class=OrdersDemoSeeder --force
```

Crea **30 órdenes** (`ORD-DEMO-2026-0001` … `0030`): 15 laboratorio (bioquímico responsable) y 15 imagen (tecnólogo), fechas **01–20/05/2026**, médicos alternados, comprobante pendiente por orden.

| Paciente demo | CI | Portal (email) | Contraseña |
|---------------|-----|----------------|------------|
| María Elena Vargas Ríos | 70123401 | paciente@tecnoweb.shop | Paciente@2026! |
| Carlos Alberto Mendoza Salazar | 70123402 | paciente1@tecnoweb.shop | Paciente@2026! |
| Sofía Patricia Quispe Mamani | 70123403 | paciente2@tecnoweb.shop | Paciente@2026! |

---

## 5. Storage y permisos

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 6. Publicar assets de Filament

```bash
php artisan filament:assets
```

---

## 7. Optimizar para producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
```

---

## 8. Cola de trabajos (opcional para demo)

El sistema usa `QUEUE_CONNECTION=database`. Para que los emails y PDFs
se procesen en segundo plano, iniciar el worker:

```bash
php artisan queue:work --tries=3 --timeout=60
```

En un servidor real se recomienda `supervisor` para mantener el worker activo.

---

## 9. Credenciales del demo (solo desarrollo o staging aislado)

Fuera de producción, el **AuthSeeder** crea cuentas con contraseñas fijas de demostración. La tabla siguiente es solo para entornos aislados de desarrollo o staging; nunca uses estas credenciales en un servidor accesible desde Internet. En producción, sigue el procedimiento de la sección 4 para crear el administrador.

| Rol | Email | Contraseña | Acceso |
|---|---|---|---|
| Administrador | `admin@tecnoweb.shop` | `Admin@2026!` | Panel `/admin` |
| Recepcionista | `recepcionista@tecnoweb.shop` | `Recep@2026!` | Panel `/admin` |
| Tecnólogo de Imagen | `tecnologo@tecnoweb.shop` | `Tecno@2026!` | Panel `/admin` |
| Bioquímico | `bioquimico@tecnoweb.shop` | `Bio@2026!` | Panel `/admin` |
| Médico derivante | `medico@tecnoweb.shop` | `Medico@2026!` | Portal `/medico/login` |

Los pacientes se crean al registrar pacientes con correo (cuenta portal automática).

Para crear o restablecer el administrador principal:

```bash
php artisan clinica:ensure-admin
```

El comando pedirá una contraseña oculta y su confirmación; no muestra ni imprime la contraseña.

Para cuentas con rol **Paciente**, suele usarse el patrón de prueba `2026CN` + últimos 4 dígitos del CI (si así se configuró al crear el usuario).

> Panel de administración: `https://TU_DOMINIO/admin`  
> Portal del paciente: `https://TU_DOMINIO/paciente/login`

---

## 10. Guion de demostración (~12 minutos)

Orden sugerido para mostrar el flujo al ingeniero o cliente:

1. **Inicio (`/admin`)** — Revisar indicadores del dashboard (órdenes, resultados pendientes, comprobantes, etc.) según permisos del usuario demo.
2. **Pacientes** — Alta o búsqueda de un paciente; enfatizar datos mínimos y CI.
3. **Órdenes** — Crear orden (laboratorio o imagen), asignar responsable y, si aplica, médico derivante.
4. **Muestras** (laboratorio) — Registrar muestra vinculada a la orden; mostrar flujo de estados si hay datos.
5. **Estudios de imagen** — Crear o consultar estudio; equipo y archivo adjunto.
6. **Resultados** — Cargar o revisar resultado; validar y generar PDF; mencionar portal del paciente.
7. **Pagos / comprobantes** — Comprobante pendiente y registro de pago; descarga de PDF si está disponible.
8. **Portal del paciente** — Iniciar sesión como Paciente; “Mis resultados” y “Mis comprobantes”.
9. **Notificaciones** (Administrador) — Bandeja de notificaciones internas y recurso **Sistema → Notificaciones** si aplica.

---

## 11. Checklist final antes de presentación

- [ ] `APP_DEBUG=false` en `.env`
- [ ] `APP_URL` apunta al dominio/IP real
- [ ] Migraciones ejecutadas sin errores (`php artisan migrate:status`)
- [ ] Assets compilados (`public/build/` existe y tiene archivos)
- [ ] Assets de Filament publicados (`public/css/filament/` existe)
- [ ] `public/css/clinica-norte-admin.css` presente (tema personalizado)
- [ ] `php artisan storage:link` ejecutado
- [ ] Queue worker corriendo (o usar `QUEUE_CONNECTION=sync` para demo simple)
- [ ] Correo configurado (o mantener `MAIL_MAILER=log` para demo sin email real)

---

## 12. Comandos rápidos para reiniciar el demo

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:assets
```
