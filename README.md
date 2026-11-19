# Sistema de Asistencia CEA - Demo en PHP

Demo funcional en PHP + PostgreSQL, con el estilo visual de Loreto Cetal Noche
(barra oscura, sidebar con logo, tarjetas en degradado morado/azul).

## Requisitos
- PHP 8.x con la extensión `pdo_pgsql` habilitada
- PostgreSQL instalado y corriendo

## Instalación desde cero
1. `createdb asistencia_cea`
2. `psql -d asistencia_cea -f schema.sql`
3. Ajusta las credenciales en `config/db.php`
4. `php -S localhost:8000` desde la carpeta del proyecto
5. Abre `http://localhost:8000/`

## Si ya tenías la base de datos del schema anterior
Corre además el script de migración, que no borra nada de lo ya cargado:
```
psql -d asistencia_cea -f migracion_v2.sql
```
Esto agrega las tablas `carreras` y `justificaciones`, y el vínculo de cada
empleado a su carrera.

## Usuario de prueba
- Usuario: `admin`
- Contraseña: `admin123`

## Logo de la institución
Coloca un archivo `logo.png` dentro de `assets/` y aparecerá automáticamente
en el círculo del menú lateral (si no existe el archivo, se muestra un
círculo gris de relleno, sin errores).

## Pantallas
- `login.php` — inicio de sesión
- `dashboard.php` (Inicio) — resumen del día y últimas marcaciones
- `personal.php` (Personal) — docentes agrupados por carrera; registrar
  docente nuevo con su horario; editar el horario de un docente ya
  registrado (ej. al cambiar de semestre)
- `justificaciones.php` — justificar tardanzas/faltas con un motivo, e
  historial de justificaciones ya registradas
- `reportes.php` — resumen acumulado por docente/carrera y detalle de
  marcaciones filtrable por fecha
- `configuracion.php` — nota sobre el logo, estado del biométrico, alta y
  baja de **usuarios del sistema** (quién puede iniciar sesión), y dar de
  baja/reactivar **personal** (docentes o administrativos que se retiran)

`empleados.php` y `asistencias.php` se mantienen solo como redirecciones
hacia `personal.php` y `reportes.php` respectivamente, por si tenías algún
enlace viejo guardado.

## Notas importantes
- Esta demo no se conecta directamente al biométrico; la integración real
  se recomienda hacerla en el agente Node.js definido en la arquitectura
  del proyecto.
- Las contraseñas se guardan con `password_hash()` de PHP (bcrypt).
- Las carreras de ejemplo que trae el schema son solo un punto de partida;
  edítalas desde pgAdmin (tabla `carreras`) con los nombres reales del CEA.
