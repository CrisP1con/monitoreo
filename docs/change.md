# Change log — System Monitor

## 2026-10-01 — Primera versión funcional

Se creó el proyecto independiente `pc-diagnostics-dashboard` para recibir, almacenar y visualizar diagnósticos enviados por agentes Windows.

### Arquitectura creada

- Laravel 12.69.3 con PHP 8.3.
- Vue 3 + Inertia + TypeScript.
- Tailwind CSS para la interfaz.
- SQLite para desarrollo local.
- Laravel Reverb preparado para comunicación en tiempo real.
- Autenticación basada en el starter kit de Laravel.
- Verificación de email desactivada para el entorno actual.

### Identidad visual y navegación

- Se reemplazó la marca genérica de Laravel por `System Monitor`.
- Se agregó un logo SVG relacionado con monitoreo de equipos.
- Se eliminaron enlaces genéricos a GitHub, documentación y Laravel.
- Se agregó la sección `Conectividad`.
- Se agregó la sección `Registros`.
- Se reemplazó la pantalla inicial genérica por una pantalla orientada al monitoreo.

### Conectividad de agentes

Se implementó la gestión de conexiones desde `/connectivity`.

Cada conexión contiene:

- Nombre descriptivo.
- Identificador del dispositivo.
- Token de autenticación.
- Estado revocado/no revocado.
- Fecha del último contacto.
- Cantidad de reportes recibidos.

La pantalla permite:

- Crear una conexión.
- Ver el endpoint de recepción.
- Copiar el token.
- Copiar un `agentsettings.json` listo para usar.
- Rotar el token.
- Revocar la conexión.

El token completo se muestra solamente durante su generación o rotación. En la base de datos se almacena mediante hash.

### API de recepción

Endpoint:

```text
POST /api/diagnostics/reports
```

Autenticación:

```text
X-Diagnostics-Token: dc_<token_id>_<secret>
```

La API valida:

- Token válido y no revocado.
- Identificador del reporte.
- Fechas.
- Tipo de incidente.
- Hasta 200 eventos.
- Nivel, importancia, categoría, proveedor, ID y mensaje de cada evento.

La ruta tiene límite de solicitudes mediante throttle.

### Almacenamiento histórico

Se agregaron las tablas:

- `agent_connections`: equipos/agentes registrados.
- `diagnostic_reports`: reportes históricos enviados por cada agente.

Cada reporte conserva:

- Eventos filtrados completos en JSON.
- Resumen analizado en JSON.
- Estado del sistema.
- Ventana de tiempo analizada.
- Tipo de incidente.
- Cantidad de eventos.
- Indicador de apagado inesperado.

Los reportes se deduplican por `agent_connection_id` y `report_key`. No se configuró limpieza automática del histórico.

### Telemetría periódica

Se agregó una segunda vía de datos para métricas del equipo, sin mezclarla con el diagnóstico de eventos:

- Tabla `telemetry_samples` en Laravel.
- Endpoint `POST /api/diagnostics/telemetry`.
- SQLite local `telemetry.db` dentro de la carpeta del agente.
- Captura de CPU, GPU, placa madre, disco y memoria cuando los sensores están disponibles.
- Muestras locales solamente cuando cambia significativamente un valor.
- Envío agrupado cada 5 minutos.
- Reintento automático: las muestras quedan pendientes en SQLite si Laravel no responde.
- Revisión y envío de nuevos eventos del Visor de eventos en el mismo ciclo de 5 minutos.
- Vista de detalle con temperatura actual, máxima, promedio y tabla histórica.

La telemetría usa el mismo ejecutable, token y conexión que los reportes de eventos. No se creó un segundo agente.

### Análisis de incidentes

El servidor genera un resumen con detección de:

- GPU: NVIDIA, OpenGL, DWM, Display y DXGKRNL.
- Disco/hardware: WHEA, Disk, NTFS, Storport, StorAHCI y Volmgr.
- Apagado inesperado: eventos 41 y 6008.
- Recomendaciones asociadas a la cadena probable del fallo.

### Registros

Se implementaron:

```text
GET /records
GET /records/{agentConnection}
```

La vista principal muestra una tarjeta por PC o servidor, con:

- Estado de contacto.
- Estado de la conexión.
- Cantidad de reportes.
- Último incidente.
- Cantidad de eventos.
- Indicador de apagado inesperado.

El detalle de una PC muestra:

- Resumen del incidente.
- Cadena probable.
- Recomendaciones.
- Historial de reportes.
- Estado del sistema.
- Tabla de eventos.
- Filtro por categoría.

### Histórico de hardware

El detalle conserva únicamente la lectura actual y un enlace a una página dedicada:

```text
GET /records/{agentConnection}/hardware
```

La página muestra hasta 5000 muestras ordenadas de la más reciente a la más antigua, con CPU general, CPU Package, máxima de cores, Core 1..N, GPU, placa madre, disco y memoria. Las muestras existentes antes de habilitar los cores mantienen sus valores anteriores y muestran `-` en las columnas nuevas.

El agente guarda las muestras localmente en `telemetry.db`, solo conserva una nueva fila cuando hay un cambio significativo y las sube en lotes cada cinco minutos. El esquema local se amplía sin borrar la base existente.

### Agente Windows

El agente está en el proyecto separado:

```text
C:\qr-boda\pc-diagnostics-agent
```

Ejecutable publicado:

```text
C:\qr-boda\pc-diagnostics-agent\publish\PcDiagnostics.Agent.exe
```

Configuración:

```text
C:\qr-boda\pc-diagnostics-agent\publish\agentsettings.json
```

El agente consulta los registros `System` y `Application`, filtra críticos, errores, advertencias y proveedores/eventos relevantes, y envía un JSON al endpoint configurado.

El archivo local `agent-state.json` guarda el último envío exitoso. No es el histórico del sistema; solamente evita volver a analizar la misma ventana.

### Ejecución local verificada

Procesos utilizados:

```text
composer run dev
php artisan reverb:start
```

El agente fue ejecutado manualmente y devolvió código 0. La prueba confirmó un reporte recibido en Laravel.

Pruebas ejecutadas en el dashboard:

```text
php artisan test --compact
npm run build
vendor/bin/pint --dirty --format agent
```

Resultado registrado: 29 pruebas aprobadas y compilación frontend exitosa.

## Pendientes recomendados

- Agregar pruebas específicas de autenticación y validación del endpoint.
- Agregar limpieza configurable del histórico por antigüedad.
- Agregar paginación real para historiales grandes.
- Agregar estado de salud del agente: último contacto, último error y versión.
- Agregar un instalador o tarea programada para desplegar el agente automáticamente.
- Considerar firma adicional del payload si el endpoint se expone fuera de la red local.
- Configurar HTTPS, `APP_KEY` segura y secretos fuera del repositorio antes de producción.
