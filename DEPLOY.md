# Despliegue a producción (cPanel / hosting compartido)

Guía para pasar el sistema de `localhost` a un servidor real. Está escrita para
cPanel, que es el escenario típico de un hosting compartido en El Salvador,
pero los pasos 4 a 9 aplican a cualquier servidor.

---

## 0. Requisitos del servidor

| Requisito | Valor mínimo |
|---|---|
| PHP | 8.2 o superior (este proyecto usa Laravel 12) |
| Extensiones PHP | `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pcre`, `pdo_mysql`, `tokenizer`, `xml`, `gd` (necesaria para el QR de los gafetes) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Acceso | Terminal SSH de cPanel (recomendado) o Administrador de archivos |

En cPanel: **Select PHP Version** → elegir 8.2/8.3 y activar las extensiones de la tabla.

---

## 1. Preparar el paquete en la máquina local

Los assets se compilan **antes** de subir: un hosting compartido rara vez tiene Node.

```bash
# 1. Dependencias de producción (sin paquetes de desarrollo)
composer install --no-dev --optimize-autoloader

# 2. Compilar CSS/JS -> genera public/build
npm ci
npm run build
```

**No** se sube: `node_modules/`, `.env`, `storage/logs/*`, `database/database.sqlite`,
`tests/`, `.git/`.
**Sí** se sube: `vendor/` (o se ejecuta `composer install` en el servidor) y `public/build/`.

Comprimir todo en un `.zip` y subirlo con el Administrador de archivos de cPanel:
es mucho más rápido que subir miles de archivos por FTP.

---

## 2. Estructura de carpetas en el servidor

En cPanel el navegador entra a `public_html`, pero en Laravel **solo la carpeta
`public` debe ser accesible**. La forma segura:

```
/home/usuario/
├── rrhh-sistema/          <- todo el proyecto (fuera de public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── ...
│   └── public/            <- su contenido va a public_html
└── public_html/           <- index.php, .htaccess, build/, favicon
```

1. Descomprimir el proyecto en `/home/usuario/rrhh-sistema`.
2. Mover el **contenido** de `rrhh-sistema/public/` a `public_html/`.
3. Editar `public_html/index.php` y corregir las dos rutas:

```php
// Antes
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Después (subiendo un nivel más, hacia la carpeta del proyecto)
require __DIR__.'/../rrhh-sistema/vendor/autoload.php';
$app = require_once __DIR__.'/../rrhh-sistema/bootstrap/app.php';
```

> **Alternativa sin mover nada:** si el hosting permite cambiar el *Document Root*
> del dominio (cPanel → *Domains* → *Manage*), apuntarlo directamente a
> `/home/usuario/rrhh-sistema/public` y saltarse los pasos 2 y 3.
> Es la opción más limpia porque no hay que editar `index.php`.

---

## 3. Reescritura de URLs (.htaccess)

`public_html/.htaccess` debe conservar el archivo original de Laravel
(el que ya viene en `public/.htaccess`). Se recomienda añadir al inicio el
bloque de forzado a HTTPS:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Forzar HTTPS
    RewriteCond %{HTTPS} !=on
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

En la raíz del proyecto se incluye `.htaccess.cpanel` con este bloque ya armado:
copiarlo como `public_html/.htaccess` **solo si** se perdió el original.

---

## 4. Base de datos

1. cPanel → **MySQL Databases** → crear base (`usuario_rrhh`) y usuario.
2. Asignar el usuario a la base con **ALL PRIVILEGES**.
3. Anotar los nombres completos: cPanel les antepone el prefijo de la cuenta
   (`cpaneluser_rrhh`), y ese es el valor que va en el `.env`.

---

## 5. Archivo `.env` de producción

Copiar `.env.production.example` como `.env` en `/home/usuario/rrhh-sistema/`
y completar. Puntos críticos:

```dotenv
APP_ENV=production
APP_DEBUG=false          # obligatorio: en true se filtran credenciales
APP_URL=https://tudominio.com
DB_DATABASE=cpaneluser_rrhh
DB_USERNAME=cpaneluser_rrhh
DB_PASSWORD=********
SESSION_SECURE_COOKIE=true
KIOSCO_TOKEN=una_cadena_larga_y_aleatoria
```

Generar la llave de cifrado (sin ella la app no arranca):

```bash
php artisan key:generate
```

---

## 6. Migraciones y datos iniciales

```bash
cd ~/rrhh-sistema

# Estructura de tablas
php artisan migrate --force        # --force es obligatorio en producción

# Roles, permisos y las cuentas base
php artisan db:seed --class=RolesYPermisosSeeder --force
```

> `db:seed` completo también carga los datos de demostración (`DemoSeeder`).
> En un entorno real se ejecuta **solo** `RolesYPermisosSeeder` y luego se crea
> el administrador a mano:

```bash
php artisan tinker
>>> $u = App\Models\User::create(['name'=>'Admin','email'=>'admin@tudominio.com','password'=>Hash::make('CLAVE_FUERTE'),'activo'=>true]);
>>> $u->assignRole('admin');
```

---

## 7. Permisos de carpetas

```bash
chmod -R 755 ~/rrhh-sistema
chmod -R 775 ~/rrhh-sistema/storage ~/rrhh-sistema/bootstrap/cache
```

Si el hosting usa `suPHP`/`PHP-FPM` por usuario, `775` es suficiente.
**Nunca** usar `777`.

---

## 8. Optimización (obligatoria en producción)

```bash
php artisan config:cache     # cachea .env + config/
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> Cada vez que se cambie el `.env` o cualquier archivo de `config/`, hay que
> repetir `php artisan config:clear && php artisan config:cache`.
> Sin esto, cambiar el `.env` no surte efecto: el sistema sigue leyendo la caché.

Enlace simbólico de archivos públicos (si se llegan a subir fotos de empleados):

```bash
php artisan storage:link
```

---

## 9. Tareas programadas y colas

cPanel → **Cron Jobs** → agregar (una vez por minuto):

```
* * * * * cd /home/usuario/rrhh-sistema && php artisan schedule:run >> /dev/null 2>&1
```

Si se usan colas (envío de correos, PDF masivos), agregar también:

```
* * * * * cd /home/usuario/rrhh-sistema && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

### Nómina automática (opcional)

La planilla también se puede generar sin entrar al sistema. Para procesarla el
día 28 de cada mes a las 6:00 a.m.:

```
0 6 28 * * cd /home/usuario/rrhh-sistema && php artisan planilla:generar >> /dev/null 2>&1
```

El comando acepta `--mes`, `--anio` y `--usuario=correo@empresa.com` (para dejar
registrado a quién se le atribuye la generación). Es idempotente: si el período
ya se procesó, no duplica nada.

El aguinaldo tiene su propio comando, que conviene programar a principios de
diciembre para tenerlo listo antes del plazo legal de pago:

```
0 6 5 12 * cd /home/usuario/rrhh-sistema && php artisan aguinaldo:generar >> /dev/null 2>&1
```

---

## 10. Verificación posterior al despliegue

| Prueba | Resultado esperado |
|---|---|
| `https://tudominio.com` | Página de bienvenida, sin errores 500 |
| `https://tudominio.com/login` | Inicio de sesión funcional |
| Login como `admin` | Ve Panel, Empleados, Planillas, Asistencia y Usuarios |
| Login como `empleado` | Solo ve "Mis recibos" y "Mi asistencia" |
| `/kiosco?token=...` | Abre el reloj marcador |
| `/kiosco` sin token | Devuelve 403 (si `KIOSCO_TOKEN` está configurado) |
| Generar planilla | Se crean los recibos con ISSS, AFP y renta calculada |
| Descargar PDF | El recibo se descarga correctamente (requiere extensión `gd`) |
| `https://tudominio.com/.env` | **404** — si muestra contenido, la carpeta pública está mal configurada |

---

## 11. Errores frecuentes en cPanel

| Síntoma | Causa y solución |
|---|---|
| Error 500 sin detalle | Revisar `storage/logs/laravel.log`. Casi siempre son permisos de `storage/` o falta `APP_KEY`. |
| "No application encryption key" | Ejecutar `php artisan key:generate` y luego `config:cache`. |
| Pantalla sin estilos | Falta subir `public/build/`. Compilar con `npm run build` y volver a subir. |
| Rutas devuelven 404 salvo la raíz | `mod_rewrite` deshabilitado o `.htaccess` ausente en `public_html`. |
| Cambios en `.env` que no aplican | Caché de configuración: `php artisan config:clear && php artisan config:cache`. |
| "SQLSTATE[HY000] [1045]" | Usuario/contraseña de MySQL sin el prefijo de cPanel, o el usuario no está asignado a la base. |
| El QR del gafete no aparece | Falta la extensión `gd` (o `imagick`) en Select PHP Version. |
| Sesión que se cierra sola | `SESSION_DOMAIN` mal configurado; debe ser `.tudominio.com`. |

---

## 12. Actualizaciones posteriores

```bash
cd ~/rrhh-sistema
php artisan down                 # modo mantenimiento
git pull            # o subir el zip actualizado
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```
