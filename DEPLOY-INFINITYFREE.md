# Despliegue en InfinityFree (hosting gratuito)

Guía específica para InfinityFree. Para cPanel de pago o un VPS, ver [DEPLOY.md](DEPLOY.md).

**El paquete ya está compilado y esperando en `C:\rrhh-deploy\`.** Esta guía es
para subirlo. No necesitas ejecutar nada en tu equipo.

---

## Qué hay en `C:\rrhh-deploy\`

| Archivo | Para qué sirve |
|---|---|
| `sistema-rrhh.zip` (32.5 MB) | Todo el sistema comprimido, listo para subir |
| `htdocs/` | Lo mismo pero descomprimido, por si prefieres subir por FTP |
| `unzip.php` | Descomprime el zip **en el servidor**, para no subir 14,535 archivos uno por uno |
| `base-de-datos.sql` | Respaldo con la estructura y los datos de demostración |
| `limpieza-demo.sql` | Quita tu cuenta personal de la base publicada |

---

## Por qué la estructura es distinta a la de cPanel

InfinityFree aplica `open_basedir` sobre `htdocs/`: **PHP no puede leer ni un
archivo fuera de esa carpeta**. La estructura normal de Laravel —donde el código
vive un nivel arriba de la carpeta pública— es imposible aquí.

La solución fue meter todo adentro y blindar la carpeta del código:

```
htdocs/
├── index.php          ← adaptado: busca la app en ./laravel/
├── .htaccess          ← reescritura + bloqueo de /laravel/
├── build/             ← CSS y JS ya compilados
├── favicon.ico
└── laravel/           ← la aplicación completa
    ├── .htaccess      ← "Require all denied": nadie entra por web
    ├── .env           ← credenciales
    ├── app/ config/ database/ resources/ routes/ storage/ vendor/
    └── artisan
```

Hay **dos barreras** sobre `laravel/`: una regla en el `.htaccess` de arriba y
un `.htaccess` propio que niega todo acceso. Con una sola bastaría, pero si el
servidor ignora una, la otra sigue en pie. Sin esto, cualquiera podría abrir
`tudominio.com/laravel/.env` y leer la contraseña de tu base de datos.

---

## Paso 1 · Crear la cuenta y el sitio

1. Registrarse en [infinityfree.com](https://infinityfree.com) y crear un
   **Hosting Account**.
2. Elegir un subdominio gratuito (por ejemplo `rrhh-demo.infinityfreeapp.com`)
   o conectar un dominio propio.
3. En el panel, entrar a **Select PHP Version** y elegir **PHP 8.2 o 8.3**.
   El sistema requiere 8.2 como mínimo; con una versión anterior no arranca.
4. Esperar. Un sitio nuevo puede tardar hasta 72 horas en propagarse, aunque
   normalmente son minutos.

---

## Paso 2 · Crear la base de datos

Panel → **MySQL Databases**:

1. Crear una base. InfinityFree le pone el prefijo de tu cuenta, algo como
   `if0_12345678_rrhh`.
2. Anotar los **cuatro** datos que da la pantalla:
   - Nombre de la base (`if0_12345678_rrhh`)
   - Usuario (`if0_12345678`)
   - Contraseña (la que definiste)
   - **Host** (`sqlXXX.infinityfree.com`) — este es el que más se olvida:
     **no es `localhost`**, y ese error produce el clásico "error 500 sin explicación".

---

## Paso 3 · Importar los datos

Panel → **phpMyAdmin** → seleccionar tu base → pestaña **Importar**:

1. Subir `C:\rrhh-deploy\base-de-datos.sql` y ejecutar.
2. Deben aparecer **21 tablas**. Esto sustituye a `php artisan migrate`, que
   aquí no se puede ejecutar porque no hay terminal.
3. Ir a la pestaña **SQL**, pegar el contenido de `limpieza-demo.sql` y
   ejecutarlo: elimina tu cuenta personal del servidor público.

---

## Paso 4 · Subir los archivos

### ⚠️ El descompresor del panel NO sirve para esto

Probado en el despliegue real: la opción *Upload & Unzip* del administrador de
archivos **extrae únicamente los archivos sueltos de la raíz del zip; no crea
subcarpetas** (y además omite los archivos de 0 bytes, como `favicon.ico`).

El resultado es un `htdocs/` con `index.php`, `.htaccess` y `robots.txt`, pero
sin `build/` ni `laravel/`. Partir el zip en pedazos tampoco resuelve nada,
porque el problema no es el tamaño sino que el extractor no sabe crear
directorios.

**La única vía fiable es FTP.**

### FTP con FileZilla

1. Panel → **FTP Accounts**: anotar host (normalmente `ftpupload.net`),
   usuario (`if0_XXXXXXXX`) y contraseña.
2. Conectarse con [FileZilla](https://filezilla-project.org/) por el puerto 21.
3. En FileZilla, **Editar → Configuración → Transferencias**: bajar
   *Transferencias simultáneas máximas* a **2**. InfinityFree corta la conexión
   con demasiadas simultáneas (`421 Too many connections`).
4. Menú **Servidor → Forzar mostrar archivos ocultos**, para poder verificar
   que los `.htaccess` llegaron.
5. Entrar a `htdocs/` en el panel derecho y arrastrar **el contenido** de
   `C:\rrhh-deploy\htdocs\` (el contenido, no la carpeta que lo envuelve).
6. Son 7,687 archivos: calcula entre 20 y 40 minutos. Si quedan transferencias
   fallidas, clic derecho en la cola → **Reiniciar y volver a encolar**.

---

## Paso 5 · Configurar el `.env`

Editar `htdocs/laravel/.env` desde el administrador de archivos del panel y
rellenar los cuatro valores marcados con `<< >>`:

```dotenv
APP_URL=http://rrhh-demo.infinityfreeapp.com
DB_HOST=sqlXXX.infinityfree.com
DB_DATABASE=if0_12345678_rrhh
DB_USERNAME=if0_12345678
DB_PASSWORD=tu_contraseña
```

Todo lo demás ya viene configurado, incluida la `APP_KEY` —que no se puede
generar en el servidor porque requiere terminal, así que va incluida—.

---

## Paso 6 · Probar

| Prueba | Resultado esperado |
|---|---|
| `http://TU-DOMINIO` | Página de bienvenida de Laravel |
| `/login` con `admin@rrhh.test` / `password` | Entra al panel con gráficas |
| El panel se ve **con estilos** | Confirma que `build/` subió bien |
| `/kiosco` | Reloj marcador funcionando |
| Escanear o escribir `04567890-1` en el kiosco | Registra entrada |
| `/planillas` → PDF de un recibo | Descarga el comprobante |
| `/laravel/.env` | **Debe dar 403 o 404.** Si muestra texto, el `.htaccess` no subió |

Ese último es el más importante. Compruébalo antes de compartir el enlace.

---

## Paso 7 · SSL (después de que todo funcione)

1. Panel → **Free SSL Certificate**, emitir el certificado para tu dominio.
2. Cuando esté activo, editar dos archivos:
   - `htdocs/.htaccess`: descomentar las tres líneas del bloque *Forzar HTTPS*.
   - `htdocs/laravel/.env`: `APP_URL` a `https://...` y `SESSION_SECURE_COOKIE=true`.

Hacerlo **antes** de tener el certificado deja el sitio inaccesible y sin
manera de iniciar sesión.

---

## Limitaciones que vas a notar

Son del hosting gratuito, no del sistema:

| Limitación | Consecuencia práctica |
|---|---|
| **Sin cron** | El planificador de Laravel no corre. No es grave: la planilla y el aguinaldo se generan con los botones de la interfaz. Los comandos `planilla:generar` y `aguinaldo:generar` quedan disponibles para cuando migres a un servidor con terminal. |
| **Sin terminal ni Composer** | Cada actualización implica volver a subir los archivos y, si hay migraciones nuevas, aplicarlas por phpMyAdmin. |
| **Sin correo saliente** | La recuperación de contraseña no envía nada. El `.env` usa `MAIL_MAILER=log` para que escriba el mensaje en el archivo de log en vez de fallar con un error. |
| **Límite de inodes (~30,000)** | El paquete usa 14,535 archivos, así que hay margen; pero no subas `node_modules/` ni las dependencias de desarrollo. |
| **Límite de visitas diarias** | Con tráfico de portafolio no lo vas a alcanzar. |

---

## Si algo falla

**Primero:** poner `APP_DEBUG=true` en el `.env`, reproducir el error, leerlo y
**volver a ponerlo en `false`**. Con `true` se exponen rutas del servidor y
fragmentos de configuración.

**El log** está en `htdocs/laravel/storage/logs/laravel.log`, y se lee desde el
administrador de archivos del panel.

| Síntoma | Causa habitual |
|---|---|
| Error 500 sin detalle | `DB_HOST` puesto como `localhost` en vez de `sqlXXX.infinityfree.com` |
| "No application encryption key" | Se borró la línea `APP_KEY` del `.env` |
| El sitio se ve sin estilos | Falta la carpeta `build/`, o quedó el archivo `hot` de Vite (ya viene excluido del paquete) |
| Todas las rutas dan 404 menos la raíz | El `.htaccess` de `htdocs/` no subió; los archivos que empiezan con punto a veces quedan ocultos en FileZilla |
| "Vite manifest not found" | No subió `build/manifest.json` |
| Pantalla en blanco | Versión de PHP menor a 8.2 en *Select PHP Version* |
| Error de permisos al escribir | La carpeta `laravel/storage/` debe existir completa con sus subcarpetas |

---

## Cuando quieras migrar a un servidor de verdad

Todo lo que hiciste aquí se aprovecha. En un VPS o cPanel con terminal solo
cambian dos cosas: `index.php` vuelve a su versión original de Laravel (buscando
la app un nivel arriba) y `htdocs/laravel/` se mueve fuera de la carpeta
pública. Los pasos están en [DEPLOY.md](DEPLOY.md), y ahí sí funcionan el cron,
las colas y el correo.
