# SDD — System Monitor

## 1. Objetivo

Construir un sistema de monitoreo para PCs y servidores Windows que recopile únicamente eventos relevantes del Visor de eventos, los envíe mediante un agente liviano a Laravel y permita consultar incidentes e históricos desde una interfaz web.

El sistema debe facilitar el diagnóstico de:

- Pantallas congeladas.
- Fallas gráficas o de drivers.
- Reinicios y apagados inesperados.
- Errores de kernel y hardware.
- Problemas de disco o sistema de archivos.
- Aplicaciones y servicios que fallan.
- Errores relevantes de Windows Update.

El producto está dividido en dos proyectos:

```text
C:\qr-boda\pc-diagnostics-agent
C:\qr-boda\pc-diagnostics-dashboard
```

## 2. Componentes

### 2.1 Agente Windows

Aplicación .NET 8 self-contained para Windows x64.

Responsabilidades:

1. Leer los logs `System` y `Application`.
2. Limitar la consulta a una ventana desde el último envío exitoso.
3. Filtrar eventos importantes.
4. Clasificar los eventos.
5. Generar el payload JSON.
6. Enviarlo con el token de la conexión.
7. Guardar el último envío exitoso en `agent-state.json`.

El agente no conserva el histórico completo. El histórico central se conserva en Laravel.

### 2.2 Dashboard Laravel

Responsabilidades:

- Autenticar usuarios del panel.
- Crear y administrar conexiones de agentes.
- Recibir reportes.
- Validar y autenticar cada envío.
- Analizar eventos.
- Guardar históricos.
- Mostrar tarjetas por equipo.
- Mostrar detalles e incidentes por equipo.

## 3. Stack

### Backend

- Laravel 12.69.3.
- PHP 8.3.
- SQLite para desarrollo local.
- Eloquent ORM.
- Form Requests para validación.
- Laravel Reverb preparado para tiempo real.
- Rate limiting para el endpoint del agente.

### Frontend

- Vue 3.
- Inertia.js.
- TypeScript.
- Tailwind CSS.

### Agente

- .NET 8.
- `System.Diagnostics.EventLog`.
- `System.Net.Http.Json`.
- Publicación self-contained `win-x64`.

## 4. Flujo principal

```text
Programador de tareas de Windows
        ↓
PcDiagnostics.Agent.exe
        ↓
Lee System/Application
        ↓
Filtra eventos importantes
        ↓
POST /api/diagnostics/reports
        ↓  X-Diagnostics-Token
Laravel autentica y valida
        ↓
Analiza y guarda DiagnosticReport
        ↓
Registros muestra PC e incidentes
```

## 5. Eventos que recopila el agente

El agente consulta únicamente niveles:

- `1`: crítico.
- `2`: error.
- `3`: advertencia.

No recopila por defecto eventos informativos ni verbose.

### IDs relevantes

Se consideran importantes, entre otros:

```text
41, 46, 51, 55,
1000, 1001, 1002, 1010, 1060,
117, 129, 141, 142, 153, 157,
4101, 4109,
6008,
7000, 7023, 7026, 7031, 7034
```

Para Windows Update se consideran los IDs:

```text
20, 21, 31, 34
```

También se incluyen eventos cuyo proveedor o mensaje contenga términos relacionados con:

```text
whea
nvidia
nvoglv64.dll
dwm.exe
display
dxgkrnl
storport
storahci
disk
ntfs
volmgr
```

### Categorías

El agente clasifica los eventos en:

- `GPU`.
- `Disco/Hardware`.
- `Reinicio/apagado`.
- `Windows Update`.
- `Servicio/driver`.
- `Aplicación`.
- `Sistema`.

Los eventos de GPU, disco/hardware y reinicio/apagado se marcan con importancia alta. El resto de eventos relevantes se marca inicialmente como media cuando no existe una regla específica.

## 6. Payload JSON

Ejemplo conceptual:

```json
{
  "report_key": "sha256-del-reporte",
  "collected_at": "2026-10-01T16:10:00Z",
  "started_at": "2026-10-01T15:55:00Z",
  "ended_at": "2026-10-01T16:10:00Z",
  "incident_type": "important_events",
  "device": {
    "id": "server",
    "name": "server-test",
    "operating_system": "Windows",
    "agent_version": "1.0.0"
  },
  "events": [],
  "system_state": {
    "free_space_c_gb": 100.5
  }
}
```

El agente serializa las propiedades en `snake_case`, que es el formato que espera Laravel.

## 7. Estado local del agente

Archivo:

```text
C:\qr-boda\pc-diagnostics-agent\publish\agent-state.json
```

Contenido:

```json
{
  "last_successful_utc": "2026-10-01T16:10:01Z"
}
```

Este archivo solamente sirve como cursor local. Se actualiza después de recibir una respuesta HTTP exitosa de Laravel.

Si se elimina, el agente vuelve a consultar el período definido por `lookback_minutes`.

## 8. Configuración del agente

Archivo:

```text
agentsettings.json
```

Campos:

```json
{
  "endpoint": "http://127.0.0.1:8000/api/diagnostics/reports",
  "token": "dc_...",
  "device_id": "server",
  "device_name": "server-test",
  "agent_version": "1.0.0",
  "lookback_minutes": 15
}
```

El archivo real contiene un secreto y no debe publicarse ni subirse al repositorio.

## 9. Base de datos

### `agent_connections`

Representa una PC o servidor conectado.

Contiene:

- Usuario propietario.
- Nombre.
- `device_id`.
- `token_id`.
- Hash del token.
- Último contacto.
- Revocación.
- Fechas de creación y actualización.

### `diagnostic_reports`

Representa cada lote de eventos recibido.

Contiene:

- Conexión del agente.
- `report_key` único por conexión.
- Tipo de incidente.
- Fechas de recolección y ventana.
- Cantidad de eventos.
- Indicador de apagado inesperado.
- `summary` JSON generado por Laravel.
- `events` JSON enviado por el agente.
- `system_state` JSON.
- Fechas de creación y actualización.

La restricción única evita duplicar el mismo reporte si el agente lo reintenta.

No hay retención automática todavía. El histórico permanece hasta que se elimine manualmente o se elimine la conexión asociada.

### `telemetry_samples`

Guarda las mediciones periódicas recibidas desde el agente:

- Conexión del agente.
- Fecha de captura.
- Temperatura de CPU.
- Temperatura de GPU.
- Temperatura de placa madre.
- Temperatura de disco.
- Porcentaje de memoria utilizada.

Tiene una clave única por conexión y fecha de captura para evitar duplicados.

## 9.1 Telemetría local y envío por lotes

El mismo `PcDiagnostics.Agent.exe` puede ejecutar el modo de telemetría cuando `telemetry_enabled` está activado.

El agente:

1. Lee los sensores cada segundo.
2. Guarda la primera medición.
3. Ignora lecturas repetidas.
4. Guarda una nueva muestra cuando cambia al menos 1 °C o 1 % de memoria.
5. Conserva las muestras pendientes en `telemetry.db`.
6. Envía hasta 5000 muestras cada 5 minutos.
7. Vuelve a consultar y envía los eventos nuevos de `System` y `Application` cada 5 minutos.
8. Marca las muestras como enviadas únicamente después de una respuesta HTTP exitosa.

La temperatura depende de los sensores expuestos por el hardware. Algunos equipos pueden no informar temperatura de disco, placa madre o GPU. En esos casos el campo se envía como `null`.

Configuración:

```json
{
  "telemetry_enabled": true,
  "telemetry_sample_interval_seconds": 1,
  "telemetry_upload_interval_seconds": 300,
  "telemetry_change_threshold_c": 1
}
```

Los eventos no se duplican en SQLite: siguen disponibles en el Visor de eventos de Windows y el archivo `agent-state.json` marca hasta qué instante fueron confirmados por Laravel. Si la API falla, el agente conserva ese cursor sin avanzar y vuelve a consultar la ventana en el siguiente ciclo.

No existe un segundo agente: eventos y telemetría son responsabilidades del mismo proceso.

## 10. API

### Recepción

```text
POST /api/diagnostics/reports
```

Headers:

```text
Accept: application/json
X-Diagnostics-Token: dc_<token_id>_<secret>
```

Respuestas esperadas:

- `201`: reporte nuevo guardado.
- `200`: reporte repetido/actualizado con la misma clave.
- `401`: token ausente, inválido o revocado.
- `422`: payload inválido.
- `429`: límite de solicitudes superado.

## 11. Seguridad

- Tokens aleatorios generados por Laravel.
- Hash del token guardado en la base de datos.
- Token completo visible solamente al crearlo o rotarlo.
- Revocación de conexiones.
- Autenticación separada para agentes y usuarios del panel.
- Validación estricta del payload.
- Límite de 200 eventos por reporte.
- Throttle del endpoint.
- No se permite que el agente consulte reportes de otras PCs.
- El `agentsettings.json` debe tratarse como secreto.
- Para producción se debe usar HTTPS.

## 12. Interfaz web

### Conectividad

Ruta:

```text
/connectivity
```

Permite registrar el equipo y obtener la configuración del agente.

### Registros

Rutas:

```text
/records
/records/{agentConnection}
```

La primera muestra una tarjeta por PC/servidor. La segunda muestra el detalle y el histórico de esa conexión.

La vista de detalle separa:

- Resumen.
- Cadena probable.
- Recomendaciones.
- Estado del equipo.
- Historial.
- Eventos filtrables.

El histórico de sensores se resuelve en una página separada (`/records/{agentConnection}/hardware`) para evitar mezclar una tabla potencialmente grande con los eventos del incidente. Cada muestra puede contener la temperatura general, Package, máxima de cores y un objeto JSON con las lecturas individuales (`1`, `2`, `3`, `4`, etc.). Los cores se renderizan dinámicamente según el hardware detectado.

La tabla `telemetry_samples` mantiene estas columnas adicionales:

- `cpu_package_c`.
- `cpu_core_max_c`.
- `cpu_cores` como JSON.

El agente usa LibreHardwareMonitor para identificar los sensores de CPU y mantiene SQLite como cola local hasta que Laravel confirma el lote recibido.

## 13. Ejecución local

Desde `C:\qr-boda\pc-diagnostics-dashboard`:

```powershell
composer run dev
```

Para Reverb:

```powershell
php artisan reverb:start
```

Desde `C:\qr-boda\pc-diagnostics-agent\publish`:

```powershell
.\PcDiagnostics.Agent.exe
```

Para automatizarlo, crear una tarea programada de Windows que ejecute el agente con la frecuencia deseada.

## 14. Criterios de aceptación

1. Un usuario autenticado puede crear una conexión.
2. Laravel genera endpoint, token y configuración del agente.
3. Un agente válido puede enviar reportes.
4. Un token inválido recibe `401`.
5. Un token revocado no puede enviar reportes.
6. Laravel guarda el resumen y los eventos en JSON.
7. Los reportes quedan disponibles como histórico.
8. La vista Registros muestra una tarjeta por PC.
9. El detalle muestra eventos y categorías.
10. Se identifica un apagado inesperado mediante IDs 41 y 6008.
11. Los errores gráficos y de almacenamiento generan resumen específico.
12. El agente crea `agent-state.json` después de un envío exitoso.
13. El mismo reporte no se duplica si se reenvía.

## 15. Fuera de alcance actual

- Recolección de todos los eventos informativos.
- Captura continua en tiempo real desde Windows.
- Volcado de memoria remoto.
- Análisis automático de archivos `.dmp`.
- Control remoto de la PC.
- Instalación automática como servicio de Windows.
- Multiusuario con roles avanzados.
- Retención y archivado automático.
- Alertas por email, Telegram o WhatsApp.
- HTTPS y despliegue público ya configurados.
