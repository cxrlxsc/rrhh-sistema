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
| **Nómina** | Cálculo masivo por período con ISSS, AFP, **renta por tramos**, horas extra y aportes patronales. Recibo en PDF por empleado. |
| **Asistencia** | Kiosco público de marcaje por escaneo de QR, con control de tardanzas, cálculo de tiempo extraordinario y protección anti doble escaneo. |
| **Prestaciones** | **Aguinaldo** por antigüedad con su parte exenta de renta, **vacaciones** con saldo de días y el 30% de recargo, y **liquidación / finiquito** con indemnización y proporcionales. |
| **Exportaciones** | Archivos CSV de planilla previsional para ISSS y AFP, e informe anual de retenciones de renta. |
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

## 4. Prestaciones laborales

Las reglas del Código de Trabajo están parametrizadas en
[`config/prestaciones.php`](config/prestaciones.php); una empresa puede otorgar
más que el mínimo legal sin tocar una línea de código.

| Prestación | Regla implementada | Motor |
|---|---|---|
| **Aguinaldo** | 15 / 19 / 21 días de salario según la antigüedad al 12 de diciembre; proporcional para quien no cumple el año. Exento de renta hasta dos salarios mínimos; no cotiza ISSS ni AFP. | [`CalculadoraAguinaldo`](app/Services/CalculadoraAguinaldo.php) |
| **Vacaciones** | 15 días por año continuo, pagados con el 30% de recargo. Control de saldo (ganados − tomados) y validación de traslapes. | [`CalculadoraVacaciones`](app/Services/CalculadoraVacaciones.php) |
| **Horas extra** | Recargo del 100% sobre la hora ordinaria, descontando el refrigerio y con tope diario. | [`CalculadoraHorasExtra`](app/Services/CalculadoraHorasExtra.php) |
| **Liquidación** | Indemnización de 30 días por año (topada a 4 salarios mínimos diarios) solo en despido injustificado; prestación por renuncia de 15 días por año con 2 años de antigüedad; vacación y aguinaldo proporcionales en todos los casos. | [`CalculadoraLiquidacion`](app/Services/CalculadoraLiquidacion.php) |

La retención de renta de los ingresos extraordinarios (aguinaldo gravado, bonos)
se calcula **al margen**: se aplica la tabla al total y se resta lo que ya
correspondía al salario ordinario, que es como tributa realmente.

> Los montos y porcentajes deben verificarse contra la normativa vigente antes
> de usar el sistema en producción. El cálculo respeta lo que diga la configuración.

### Integración entre módulos

El kiosco no es una isla: al cerrar la jornada calcula los minutos extra, y esos
minutos entran a la planilla del período como ingreso gravable.

```
marcaje de salida → minutos_extra → horas extra del período
                  → bonificaciones de la planilla → base imponible → renta
```

## 5. Instalación local

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

## 6. Pruebas

```bash
php artisan test
```

| Suite | Cubre |
|---|---|
| `CalculadoraNominaTest` | Los cuatro tramos de renta, techos de ISSS y AFP, costo patronal y cuadre del líquido. |
| `KioscoAsistenciaTest` | Entrada puntual/tardía, doble escaneo, jornada mínima, DUI sin guion, empleado inactivo, token. |
| `RolesYPermisosTest` | Aislamiento entre roles y que un empleado no descargue el recibo de otro. |
| `GeneracionPlanillaTest` | Cálculo del período, no duplicación, exclusión de inactivos y validación del período. |
| `PrestacionesTest` | Tramos de aguinaldo, proporcional, exención de renta, recargo de vacaciones, saldo de días, topes de hora extra e indemnización. |
| `PrestacionesFlujoTest` | Generación de aguinaldo, validación de saldo y traslapes de vacaciones, finiquito que da de baja al empleado, exportaciones CSV. |
| `VistasAdministrativasTest` | Humo: todas las pantallas administrativas renderizan. |

---

## 7. Despliegue

**Hosting gratuito (InfinityFree):** guía paso a paso en
**[DEPLOY-INFINITYFREE.md](DEPLOY-INFINITYFREE.md)**. La estructura de carpetas
cambia porque `open_basedir` obliga a meter toda la aplicación dentro de
`htdocs/`, protegida por doble `.htaccess`.

**cPanel / VPS:** guía completa en **[DEPLOY.md](DEPLOY.md)**:
estructura de carpetas fuera de `public_html`, `.htaccess`, base de datos,
variables de entorno ([`.env.production.example`](.env.production.example)),
optimización con `config:cache`, cron del scheduler y checklist de verificación.
