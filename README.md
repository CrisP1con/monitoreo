# System Monitor

System Monitor es una plataforma para monitorear PCs y servidores Windows desde un panel web. Un agente liviano recopila eventos importantes del Visor de eventos y telemetría de hardware, guarda los datos localmente cuando es necesario y los envía a Laravel mediante una API autenticada.

## Qué hace

- Registra PCs o servidores mediante una conexión con token.
- Recibe eventos importantes de Windows, por ejemplo:
  - reinicios o apagados inesperados;
  - errores de GPU, NVIDIA, OpenGL, DWM y pantalla;
  - errores de disco, NTFS, almacenamiento y WHEA;
  - errores de servicios, drivers y aplicaciones;
  - errores relevantes de Windows Update.
- Guarda cada reporte con su resumen, cadena probable, recomendaciones y eventos originales.
- Captura telemetría de hardware:
  - temperatura general de CPU;
  - CPU Package y temperatura máxima de cores;
  - temperatura individual de Core 1, Core 2, etc.;
  - GPU, placa madre, disco y memoria.
- Evita guardar muestras repetidas si no cambió la temperatura significativamente.
- Conserva una cola local SQLite en el agente hasta que Laravel confirma el envío.
- Presenta un Dashboard general con conectividad, reportes, alertas y estado de cada equipo.
- Presenta el detalle de cada equipo con eventos filtrables y una página separada de histórico de hardware.

## Componentes

```text
C:\qr-boda\pc-diagnostics-dashboard   Aplicación web Laravel + Vue + Inertia
C:\qr-boda\pc-diagnostics-agent       Agente Windows .NET 8
C:\qr-boda\pc-diagnostics-portable    Aplicación portátil de análisis local
C:\qr-boda\pc-diagnostics-cloud        Esqueleto separado para una futura variante cloud
```

El agente publicado se encuentra en:

```text
C:\qr-boda\pc-diagnostics-agent\publish\PcDiagnostics.Agent.exe
```

## Requisitos

- PHP 8.3 o superior.
- Composer.
- Node.js y npm.
- SQLite para desarrollo, o MySQL/PostgreSQL en producción.
- .NET 8 SDK solo si se recompila el agente.
- Windows en los equipos monitoreados.
- Permisos elevados para leer todos los eventos y sensores de hardware.

## Desarrollo local

Desde el proyecto web:

```powershell
cd C:\qr-boda\pc-diagnostics-dashboard
composer install
copy .env.example .env
php artisan key:generate
New-Item -ItemType File -Force database\database.sqlite
php artisan migrate
npm install
npm run build
```

Para trabajar con recarga automática:

```powershell
composer run dev
```

El panel queda disponible normalmente en `http://127.0.0.1:8000`.

Si se usa broadcasting en tiempo real:

```powershell
php artisan reverb:start
```

El agente necesita una conexión creada desde **Conectividad**. Copiar la configuración generada a:

```text
C:\qr-boda\pc-diagnostics-agent\publish\agentsettings.json
```

Luego ejecutar PowerShell como administrador:

```powershell
cd C:\qr-boda\pc-diagnostics-agent\publish
Start-Process .\PcDiagnostics.Agent.exe -Verb RunAs
```

El agente no necesita una ventana visible para funcionar. Sus datos locales están en:

```text
agentsettings.json   Configuración y token
agent-state.json     Última ventana de eventos enviada correctamente
telemetry.db         Cola/histórico local de telemetría
agent.log            Registro técnico del agente
```

## Configuración del agente

Ejemplo de `agentsettings.json`:

```json
{
  "endpoint": "https://monitor.example.com/api/diagnostics/reports",
  "telemetry_endpoint": "https://monitor.example.com/api/diagnostics/telemetry",
  "token": "TOKEN_GENERADO_DESDE_CONECTIVIDAD",
  "device_id": "server-01",
  "device_name": "Servidor principal",
  "agent_version": "1.0.0",
  "lookback_minutes": 15,
  "telemetry_enabled": true,
  "telemetry_sample_interval_seconds": 1,
  "telemetry_upload_interval_seconds": 300,
  "telemetry_change_threshold_c": 1
}
```

El agente toma muestras aproximadamente cada segundo, pero solo conserva cambios relevantes. Envía los pendientes cada cinco minutos y también realiza un envío inicial al arrancar. Los eventos importantes se envían junto con el ciclo de reporte; si no hay eventos nuevos, no crea reportes vacíos.

## API

Todas las rutas del agente usan el header:

```http
X-Diagnostics-Token: TOKEN_DE_LA_CONEXION
```

### Reportes de eventos

```http
POST /api/diagnostics/reports
Content-Type: application/json
```

### Telemetría

```http
POST /api/diagnostics/telemetry
Content-Type: application/json
```

La telemetría se envía en lotes:

```json
{
  "samples": [
    {
      "captured_at": "2026-10-01T21:00:01Z",
      "cpu_temperature_c": 54.2,
      "cpu_package_c": 55.0,
      "cpu_core_max_c": 57.0,
      "cpu_cores": { "1": 53.0, "2": 55.0, "3": 57.0, "4": 52.0 },
      "gpu_temperature_c": 55.2,
      "motherboard_temperature_c": 38.3,
      "disk_temperature_c": 32.0,
      "memory_used_percent": 85.8
    }
  ]
}
```

Laravel valida el token, identifica la conexión y usa una clave única por equipo y fecha de captura para evitar duplicados.

## Uso del panel

- **Dashboard**: análisis general de todos los equipos, conectividad, cantidad de reportes, eventos y apagados inesperados.
- **Conectividad**: crear, rotar o revocar tokens y obtener la configuración del agente.
- **Registros**: tarjetas por PC o servidor.
- **Detalle de un registro**: diagnóstico principal, recomendaciones, eventos y estado del último reporte.
- **Histórico de hardware**: tabla completa de sensores, con columnas dinámicas para Core 1, Core 2, Core 3, etc.

La página de hardware se abre en:

```text
/records/{id}/hardware
```

## Producción

1. Crear un servidor con PHP 8.3, Composer, Node.js y una base de datos administrada.
2. Copiar el proyecto y configurar `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://monitor.example.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=system_monitor
DB_USERNAME=system_monitor
DB_PASSWORD=CAMBIAR_ESTA_CLAVE
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

3. Instalar y compilar:

```powershell
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

4. Servir `public/` mediante IIS, Nginx o Apache. Nunca exponer la raíz del proyecto.
5. Configurar HTTPS obligatorio.
6. Mantener un proceso de cola si se agregan jobs:

```powershell
php artisan queue:work --sleep=3 --tries=3
```

7. Ejecutar Reverb únicamente si se usa tiempo real:

```powershell
php artisan reverb:start
```

8. En cada Windows monitoreado, configurar el agente en el Programador de tareas:

- ejecutar con una cuenta con permisos de administrador;
- ejecutar aunque el usuario no haya iniciado sesión;
- iniciar al arrancar el equipo;
- usar `PcDiagnostics.Agent.exe` como programa;
- establecer como directorio de inicio `C:\qr-boda\pc-diagnostics-agent\publish`;
- evitar iniciar otra copia si el agente ya está ejecutándose.

El agente mantiene la telemetría local si el servidor no está disponible y la reintenta en el siguiente ciclo.

## Seguridad

- No subir `.env`, tokens ni `agentsettings.json` al repositorio.
- Usar HTTPS en producción.
- Rotar tokens si se sospecha exposición.
- Revocar conexiones que ya no estén autorizadas.
- Mantener límites de solicitudes y validación del payload.
- No mostrar el token completo después de crearlo.
- Limitar el acceso web con autenticación y, si corresponde, roles.
- Configurar backups de la base de datos.
- Mantener actualizado Windows, PHP, Laravel y el agente.

## Diagnóstico y mantenimiento

Archivos útiles del agente:

```text
C:\qr-boda\pc-diagnostics-agent\publish\agent.log
C:\qr-boda\pc-diagnostics-agent\publish\agent-state.json
C:\qr-boda\pc-diagnostics-agent\publish\telemetry.db
```

Comandos útiles del panel:

```powershell
php artisan route:list
php artisan migrate:status
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build
```

Si no aparecen lecturas de CPU, ejecutar el agente como administrador y revisar `agent.log`. LibreHardwareMonitor no siempre expone todos los sensores: la disponibilidad depende de la placa madre, CPU, GPU y permisos del equipo.

## Estado actual y próximos pasos

Actualmente están implementados el registro de conexiones, autenticación por token, reportes de eventos, telemetría, histórico de hardware, Dashboard general y ejecución local.

Como mejoras futuras se pueden agregar retención automática de telemetría, gráficos, alertas por email o mensajería, roles avanzados, instalación del agente como servicio de Windows y despliegue administrado en la nube.
