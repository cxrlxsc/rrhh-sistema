# Sistema de Recursos Humanos y Planillas — El Salvador

Sistema web para la gestión de personal, control de asistencia biométrico por QR
y cálculo de nómina conforme a la legislación salvadoreña (ISSS, AFP e Impuesto
sobre la Renta con tabla de tramos del Ministerio de Hacienda).

**Stack:** Laravel 12 · PHP 8.2 · MySQL · Tailwind CSS · Blade · Chart.js
**Paquetes clave:** `spatie/laravel-permission` (roles), `barryvdh/laravel-dompdf` (recibos PDF), `simplesoftwareio/simple-qrcode` (gafetes)

---

## Módulos

| Módulo | Qué resuelve |
|---|---|
| **Empleados** | Directorio con búsqueda y filtros, ficha individual, alta/baja lógica (nunca se borra: se conserva el historial legal), gafete imprimible con QR. |
| **Departamentos** | Áreas de la empresa con conteo de personal y protección contra borrado si tienen empleados asignados. |
| **Nómina** | Cálculo masivo por período con ISSS, AFP, **renta por tramos** y aportes patronales. Recibo en PDF por empleado. |
| **Asistencia** | Kiosco público de marcaje por escaneo de QR, con control de tardanzas y protección anti doble escaneo. Reporte filtrable para RRHH. |
| **Usuarios y roles** | Tres niveles de acceso con permisos granulares y autoservicio para el empleado. |
| **Panel** | KPIs de plantilla, nómina, costo patronal real y pulso de asistencia del día. |

---

## 1. Cálculo de nómina

Toda la matemática vive en [`app/Services/CalculadoraNomina.php`](app/Services/CalculadoraNomina.php)
y las tasas en [`config/nomina.php`](config/nomina.php): una reforma fiscal se resuelve
editando configuración, sin tocar código.

**Orden legal del cálculo:**

```
1. Total devengado    = salario base + bonificaciones gravables
2. ISSS               = 3 %    sobre un techo cotizable de $1,000.00  (máx. $30.00)
3. AFP                = 7.25 % sobre un techo cotizable de $6,377.14
4. Base imponible     = devengado − ISSS − AFP
5. Renta              = cuota fija del tramo + % sobre el exceso
6. Líquido a recibir  = devengado − ISSS − AFP − renta − otras deducciones
```

**Tabla de retención mensual aplicada:**

| Tramo | Desde | Hasta | % | Sobre el exceso de | Cuota fija |
|---|---|---|---|---|---|
| I | $0.01 | $472.00 | Exento | — | — |
| II | $472.01 | $895.24 | 10 % | $472.00 | $17.67 |
| III | $895.25 | $2,038.10 | 20 % | $895.24 | $60.00 |
| IV | $2,038.11 | en adelante | 30 % | $2,038.10 | $288.57 |

Cada recibo guarda además la **base imponible** y el **tramo aplicado**, para que
la retención sea auditable, y los **aportes patronales** (ISSS 7.5 % + AFP 8.75 %),
que revelan el costo real de cada empleado para la empresa.

> La tabla quincenal también está incluida en la configuración; la calculadora
> acepta la periodicidad como parámetro.

El procesamiento del período vive en [`GeneradorPlanilla`](app/Services/GeneradorPlanilla.php),
compartido por la pantalla web y el comando de consola —pensado para el cron del
servidor— que es idempotente:

```bash
php artisan planilla:generar --mes=8 --anio=2026 --usuario=admin@rrhh.test
```

---

## 2. Roles y permisos

Implementado con `spatie/laravel-permission`. Los permisos se declaran en
[`RolesYPermisosSeeder`](database/seeders/RolesYPermisosSeeder.php).

| Rol | Alcance |
|---|---|
| **admin** | Control total, incluida la gestión de cuentas y roles. |
| **rrhh** | Empleados, departamentos, planillas y asistencia. No administra usuarios. |
| **empleado** | Solo autoservicio: `/mis-recibos` y `/mi-asistencia`. |

La separación se aplica en tres capas:

1. **Rutas** — middleware `permission:` por módulo.
2. **Policy** — [`PlanillaPolicy`](app/Policies/PlanillaPolicy.php) decide cada recibo:
   RRHH ve todos, el empleado únicamente el suyo.
3. **Vistas** — el menú y los botones se construyen según los permisos reales.

Un usuario se enlaza a su ficha de RRHH mediante `users.empleado_id`; el
autoservicio resuelve al empleado **desde la sesión, nunca desde la URL**.

---

## 3. Kiosco de marcaje y anti-spam

`/kiosco` corre en una tablet de recepción, sin sesión iniciada. Cuatro capas de
protección conviven en [`RegistroAsistencia`](app/Services/RegistroAsistencia.php):

| Capa | Regla |
|---|---|
| **Cooldown por empleado** | Ignora un segundo escaneo del mismo gafete antes de `KIOSCO_COOLDOWN` segundos (60 por defecto). Es la defensa contra el lector que dispara dos veces y cerraría la jornada un segundo después de abrirla. |
| **Jornada mínima** | La salida solo se acepta tras `KIOSCO_JORNADA_MINIMA` minutos de jornada. |
| **Rate limit por IP** | `throttle:kiosco` — 20 intentos por minuto contra el endpoint público. |
| **Token de dispositivo** | Con `KIOSCO_TOKEN` definido, el kiosco solo abre desde `/kiosco?token=...`. |

Además: índice único `(empleado_id, fecha)` y transacción con `lockForUpdate`
contra escaneos concurrentes; el DUI se normaliza (con o sin guion) porque los
lectores no siempre envían el mismo formato; y los gafetes desconocidos reciben
un mensaje genérico para no confirmar qué DUI existe en la base.

---

## 4. Instalación local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# Configurar DB_* en .env y luego:
php artisan migrate --seed
npm run dev
php artisan serve
```

**Cuentas de demostración** (contraseña `password` en las tres):

| Correo | Rol |
|---|---|
| `admin@rrhh.test` | admin |
| `rrhh@rrhh.test` | rrhh |
| `empleado@rrhh.test` | empleado |

El seeder carga 5 departamentos, 6 empleados con salarios que caen en los cuatro
tramos de renta y dos semanas de marcajes de asistencia.

---

## 5. Pruebas

```bash
php artisan test
```

| Suite | Cubre |
|---|---|
| `CalculadoraNominaTest` | Los cuatro tramos de renta, techos de ISSS y AFP, costo patronal y cuadre del líquido. |
| `KioscoAsistenciaTest` | Entrada puntual/tardía, doble escaneo, jornada mínima, DUI sin guion, empleado inactivo, token. |
| `RolesYPermisosTest` | Aislamiento entre roles y que un empleado no descargue el recibo de otro. |
| `GeneracionPlanillaTest` | Cálculo del período, no duplicación, exclusión de inactivos y validación del período. |
| `VistasAdministrativasTest` | Humo: todas las pantallas administrativas renderizan. |

---

## 6. Despliegue

Guía completa para cPanel / hosting compartido en **[DEPLOY.md](DEPLOY.md)**:
estructura de carpetas fuera de `public_html`, `.htaccess`, base de datos,
variables de entorno ([`.env.production.example`](.env.production.example)),
optimización con `config:cache`, cron del scheduler y checklist de verificación.
