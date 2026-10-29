# POSUCI 360 Conecta — Estado del proyecto

Última actualización: 2026-09-22 (barra superior más simple + menú de módulos accesible desde cualquier página + ajustes de celular).

## Portal más fácil y visual: navegación (2026-09-22)

La barra superior había ido creciendo con cada función nueva (modo fácil, notificaciones push, instalar app, campanita, cerrar sesión — 6 elementos sueltos) hasta verse recargada, sobre todo en celular.

- **Menú "⚙️ Ajustes"**: modo fácil, activar notificaciones, instalar app y salir ahora viven juntos en un solo menú desplegable. La barra superior queda con solo lo esencial: el logo, el nombre del actor, la campanita de notificaciones y "Ajustes".
- **Enlaces del menú con ícono**: cada módulo en el menú colapsable ahora tiene su emoji (💊 Medicamentos, 📅 Calendario, etc.), igual que ya tenían las tarjetas del Inicio — más fácil de escanear de un vistazo.
- **Botón flotante "🗺️ Módulos" desde cualquier página**: antes, la cuadrícula grande de íconos ("Explora todo") solo existía en el Inicio. Ahora un botón flotante (esquina inferior izquierda, junto al de "Leer esta página") la abre desde cualquier pantalla del portal, sin tener que volver al Inicio primero — incluye dos módulos que antes solo estaban en el Inicio y no en la barra (Mi progreso, Resumen para tu cita).
- **Ajustes de celular**: el calendario partía sus 7 botones (mes/semana/día/lista + navegación) en una sola fila que se desbordaba en pantallas angostas — ahora quedan en dos filas (navegación arriba, vistas abajo). Se agregaron ajustes de tamaño para pantallas de menos de 480px (mascota, tarjetas, botones del calendario) para que no se vean apretados.

Verificado con 2 pruebas automatizadas nuevas (284 en total, todas en verde) y un recorrido manual real contra el servidor de desarrollo: el menú de Ajustes, el lanzador de módulos, y el calendario en dos filas se ven correctamente en `/portal` y en páginas distintas al Inicio.

## Varios cuidadores autorizados por caso — verificado (2026-09-21)

El usuario pidió permitir más de un cuidador con acceso al portal por caso. Al revisar el código, resultó que **ya estaba construido**: `caregiverAuthorizations` en `PicsCase` siempre fue `HasMany` (no `HasOne`), la restricción única en la tabla es `[pics_case_id, caregiver_id]` (evita duplicar el mismo cuidador, nunca bloqueó agregar uno distinto), `CaseAccess` siempre revisa la autorización del cuidador específico que inició sesión (nunca asume "el" cuidador del caso), y el panel de staff (`CaregiverAuthorizationsRelationManager`) ya permite crear tantas autorizaciones como se quiera desde el botón "Crear". El diario ya es compartido entre todos los actores del caso (no por cuidador), y los recordatorios personales, las notificaciones de inactividad y las suscripciones push ya estaban correctamente aisladas por actor individual, no por "el cuidador".

Como no había nada que construir, esta entrada es de **verificación**: se agregaron 7 pruebas automatizadas nuevas que confirman con dos cuidadores reales en el mismo caso (ej: "esposa" e "hija") que: ambas pueden entrar de forma independiente, el diario se comparte correctamente entre ambas con la autoría correcta, los recordatorios personales de cada una siguen siendo privados entre sí, y el aviso de "el paciente no entró hoy" les llega a ambas de forma independiente. Se encontró y corrigió un bug de la propia prueba en el camino (no del producto): faltaba la contraseña del paciente en los datos de prueba, lo que hacía parecer que algo fallaba cuando en realidad la protección "solo notificar pacientes con cuenta de portal" estaba funcionando exactamente como debía.

282 pruebas en total, todas en verde.

## Checklist de primeros pasos (2026-09-21)

A diferencia del tour de bienvenida (que se ve una sola vez y luego desaparece), una tarjeta "🚀 Primeros pasos" en el Inicio con 4 pasos concretos: completar el "Antes y ahora", escribir la primera entrada del diario, agregar el primer recordatorio, y activar las notificaciones. Se muestra mientras falte al menos uno; se oculta sola en cuanto ya se hizo todo — sin necesidad de un botón de "descartar".

Reutiliza exactamente las mismas verificaciones actor-scoped que ya usa el sistema de puntos (`computeGamification`) para que ambos siempre coincidan.

Encontré y corregí en el camino un bug de mi propia prueba (no del producto): había nombrado a la paciente de prueba "Primeros pasos", que coincidía con el propio texto que estaba verificando en la barra de navegación — el mismo tipo de error que ya había pasado antes con "Modo fácil" como nombre de prueba en otra ronda.

Verificado con 2 pruebas automatizadas nuevas (275 en total, todas en verde) y un recorrido manual real: el checklist aparece correctamente para el paciente demo con sus 4 pasos pendientes.

## Gráficas de progreso (2026-09-21)

Nueva página "📈 Mi progreso" (`PortalProgressController`, Chart.js vía CDN — sin build): convierte en gráficas de tendencia lo que hoy solo se veía como listas.

- Una gráfica por tipo de lectura de monitoreo en casa que tenga al menos 2 registros (SpO2, frecuencia cardíaca, etc.).
- Una gráfica de bienestar en el tiempo: para el paciente, Ansiedad (HADS-A) y Ánimo (PHQ-9); para el cuidador, Carga del cuidador (PICS-F) — los mismos puntajes ya validados que usa el equipo en `PicsFollowup`, sin calcular ni interpretar nada nuevo.
- Si no hay suficientes datos (menos de 2 puntos), muestra un mensaje explicando qué falta en vez de una gráfica vacía o engañosa.

Verificado con 4 pruebas automatizadas nuevas (273 en total, todas en verde) y un recorrido manual real contra el servidor de desarrollo con el paciente demo.

## Centro de notificaciones dentro del portal (2026-09-21)

Campanita 🔔 en la barra superior (nuevo `NotificationCenterComponent`) que junta en un solo lugar los avisos que antes solo se veían si la persona entraba módulo por módulo a revisar. Patient/Caregiver ya eran `Notifiable` (vía `PortalAccountAuthenticatable`, usado para las invitaciones y el restablecimiento de contraseña), así que esto reutiliza directamente las tablas estándar de notificaciones de Laravel — nada nuevo que mantener aparte.

**Primer disparador:** cuando el equipo responde una solicitud de "Necesito ayuda" (acción "Responder" en `/pics/support-requests`), se le notifica automáticamente al paciente/cuidador que la creó (`SupportRequestAnsweredNotification`, canal panel + correo). Queda listo para sumar más disparadores (cita nueva, contenido educativo asignado, etc.) sin cambiar la arquitectura — cualquier notificación normal de Laravel dirigida a un Patient/Caregiver ya aparece en la campanita automáticamente.

**Nota operativa (no es un cambio de esta sesión, ya era así):** como todas las notificaciones de este proyecto usan `ShouldQueue`, necesitan un queue worker corriendo para entregarse — el script `composer run dev` ya lo levanta junto con el servidor (`queue:listen`); `serve.ps1` (el lanzador que se ha usado en esta sesión para pruebas rápidas) no lo incluye, así que las notificaciones quedan encoladas sin procesarse si se usa solo `serve.ps1`.

Verificado con 3 pruebas automatizadas nuevas (269 en total, todas en verde) y una verificación real contra la base de datos de desarrollo (con rollback, forzando el driver de cola a `sync` para confirmar el contenido de inmediato): la notificación se guarda correctamente, el título/cuerpo son los esperados, y el correo se renderiza sin errores.

## Revisión de permisos de los módulos nuevos (2026-09-21)

Con tanta superficie nueva agregada esta semana (calendario interactivo, push, modo fácil, resumen imprimible), tocaba revisar que la autorización siguiera siendo sólida en todo — algo que ya se había dejado pendiente en una ronda de ideas anterior.

**Resultado:** todo lo revisado (`CalendarComponent`, `PortalCalendarController`, `PortalSummaryController`, `PortalPushController`, `PortalHomeController::toggleEasyMode`) sigue el mismo patrón ya establecido — cada acción se limita al caso resuelto por el actor autenticado (`CaseAccess`/`currentCase()`), y los recordatorios personales, además, se limitan también por `created_by_type`/`created_by_id`. No encontré ninguna forma de que un paciente o cuidador lea o modifique algo de otro caso o de otro actor.

**Un detalle real que sí se corrigió:** la tabla `push_subscriptions` no tenía una restricción de unicidad real en `endpoint` (era `text`, sin índice). Un endpoint de Web Push identifica una única suscripción de navegador — ahora es `string(500)` con índice único, y `PortalPushController::subscribe()` hace el `updateOrCreate` por endpoint de forma global (no solo dentro de las suscripciones del actor). Esto además resuelve bien un caso real: una tableta familiar compartida donde el cuidador cierra sesión y el paciente se suscribe después con el mismo navegador — ahora la suscripción se reasigna correctamente al actor que se está suscribiendo, en vez de quedar huérfana o duplicada.

Verificado con 1 prueba automatizada nueva que reproduce exactamente ese escenario de dispositivo compartido (266 en total, todas en verde).

## Botón "Instalar app" (2026-09-20)

Complementa el trabajo de PWA de las notificaciones push: un botón "📲 Instalar app" en la barra superior que aprovecha que el portal ya tiene `manifest.json` + service worker para agregarlo a la pantalla de inicio con un toque — que se sienta como una app real (ícono propio, sin barra de navegador) en vez de depender de recordar una URL. En Android/Chrome usa el evento nativo `beforeinstallprompt`; en iOS (que no lo soporta) muestra instrucciones para "Agregar a pantalla de inicio" desde Safari, porque ahí instalar es un gesto manual del sistema. Se oculta solo si el portal ya está instalado (`display-mode: standalone`).

Verificado con 1 prueba automatizada nueva (265 en total, todas en verde) y un recorrido manual real: el botón y el script se sirven correctamente.

## Hablar en vez de escribir (2026-09-20)

Botón 🎤 junto a los campos de texto largos del diario ("¿Cómo estuvo el día?", "Mensaje para el paciente", "Un recuerdo significativo") y de "Necesito ayuda" ("Cuéntanos") — usa la Web Speech API nativa del navegador (`SpeechRecognition`/`webkitSpeechRecognition`, español) para dictar en vez de escribir, pensado para quien no se sienta cómodo escribiendo o tenga dificultad para teclear.

Decisiones de diseño:
- Sin librerías externas ni costo — es una API nativa del navegador (funciona en Chrome/Edge; en navegadores sin soporte, el botón simplemente no aparece, vía `body.voice-input-supported`, la misma técnica de progressive enhancement que ya se usó para "Leer esta página").
- Los botones están en el HTML servido por el servidor (no inyectados por JS), y el script los conecta por delegación de eventos a nivel de documento — así siguen funcionando después de que Livewire vuelva a pintar el formulario (por ejemplo tras un error de validación), sin tener que re-escanear el DOM.
- El texto reconocido se agrega al campo (no lo reemplaza), para poder dictar en varias tandas.

No se agregó a "Cómo me siento" porque ese formulario es todo de escalas/opciones (PHQ-9, HADS, etc.), sin campos de texto libre donde aplique.

Verificado con 2 pruebas automatizadas nuevas (264 en total, todas en verde) y un recorrido manual real contra el servidor de desarrollo: los botones aparecen en `/portal/diario` y `/portal/ayuda` con los `data-target` correctos.

## Notificaciones push reales (PWA) (2026-09-20)

La pieza más grande de la meta "necesidad diaria": el portal ya avisa aunque el paciente/cuidador no tenga el navegador abierto — la palanca más directa para que entren sin tener que acordarse solos.

**Qué se agregó:**
- Librería `minishlink/web-push` (Web Push estándar: VAPID + cifrado del payload) — nueva dependencia real de Composer.
- `php artisan agora:generate-vapid-keys` genera el par de llaves del servidor (una sola vez por entorno); se guardan en `.env` (`VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY`/`VAPID_SUBJECT`), nunca en el repositorio.
- `public/manifest.json` + `public/sw.js` (service worker) + ícono — el portal ya es instalable como app (PWA). El service worker no cachea nada (sin soporte offline): su único trabajo es mostrar la notificación push y abrir/enfocar el portal al tocarla.
- Botón "🔔 Activar notificaciones" en la barra superior (`public/js/portal-push.js`) — pide permiso, se suscribe con la Push API nativa del navegador, y guarda la suscripción en `push_subscriptions` (tabla nueva, morph a Patient/Caregiver — una persona puede tener varias, una por dispositivo).
- `App\Support\Posuci\WebPushSender` — envía a todas las suscripciones de un actor y **borra automáticamente** las que el navegador ya invalidó (permiso revocado, dispositivo desinstalado) cuando el servicio de push responde que expiraron.
- `agora:send-daily-portal-push` (corre todos los días 8am hora Colombia): manda un recordatorio matutino a quien ya activó las notificaciones y tenga un caso activo (no completed/cancelled). Sin `VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY` configuradas, no hace nada — no rompe el resto del portal.

**Decisión de alcance:** el contenido del push es un recordatorio genérico ("Buenos días 👋 ¿Cómo amaneciste?"), no un mensaje distinto por cada medicamento/cita en tiempo real — eso requeriría revisar horarios cada pocos minutos para cada paciente, un salto de complejidad mucho mayor. Esta primera versión cubre lo esencial (alcanzar a la persona aunque no abra la app) de forma confiable; mandar avisos por evento específico queda como posible siguiente paso.

**Nota de entorno local:** el runtime portátil de PHP no traía configurado un archivo de certificados CA ni `OPENSSL_CONF` — sin esto, cualquier llamada HTTPS saliente (incluido Web Push) fallaba. Se corrigió agregando `curl.cainfo`/`openssl.cafile` a `php.local.ini` y `OPENSSL_CONF` a `serve.ps1`, apuntando a un bundle de certificados ya confiable (el mismo que usa Composer). Esto también deja mejor preparado cualquier otro llamado HTTPS saliente futuro del proyecto.

**Verificado con 8 pruebas automatizadas nuevas** (262 en total, todas en verde; `phpunit.xml` fija `VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY` vacías para que la suite nunca intente una llamada de red real) y un **recorrido manual real de extremo a extremo contra el servidor de desarrollo**: se generaron llaves VAPID reales, se confirmó que `/manifest.json` y `/sw.js` se sirven correctamente, se suscribió al paciente demo por HTTP, se corrió el comando real (se saltó correctamente el caso demo por estar `completed`), y — en una prueba aislada con una suscripción de navegador válida simulada — se confirmó que la firma VAPID, el cifrado del payload y el envío real a un servicio de push (Google FCM) funcionan de principio a fin (respondió "410 Gone" para el endpoint falso, y la librería lo detectó correctamente como suscripción expirada). Lo único que esta sesión no puede probar es la recepción real en un navegador/dispositivo físico — eso requiere una prueba manual del usuario con el botón "Activar notificaciones".

## Consejo del día y aviso a la familia el mismo día (2026-09-19)

Dos piezas más de la meta "que sea una necesidad diaria", elegidas junto con las notificaciones push (siguiente entrada).

**Consejo del día** (`app/Support/Posuci/DailyTip.php`): una tarjeta en el Inicio con una frase corta de ánimo/autocuidado que cambia todos los días (24 frases, rotan por día del año — `dayOfYear % 24`, estable durante el mismo día). Todas son genéricas y no clínicas a propósito — nunca instrucciones de dosis ni nada que deba decidir el equipo tratante. Le da al paciente una razón para entrar aunque ya haya hecho todo lo de hoy.

**Aviso a la familia si el paciente no entró hoy** (`app/Console/Commands/NotifyCaregiverOfInactivePatientToday.php`, corre todos los días 8pm hora Colombia): distinto de la alerta al equipo por inactividad (que espera 15 días y avisa al staff clínico) — este es interno de familia, del mismo día: si el paciente con cuenta de portal no ha iniciado sesión hoy, se le avisa por correo al cuidador autorizado para que le recuerde o lo llame. No repite el aviso el mismo día (columna `last_inactivity_nudge_at` en `caregiver_authorizations`); se salta autorizaciones revocadas, pacientes sin cuenta de portal, y casos completed/cancelled.

Verificado con 9 pruebas automatizadas nuevas (254 en total, todas en verde) y un recorrido manual real: el consejo del día aparece correctamente en `/portal` con el paciente demo.

## Alerta al equipo por inactividad en el portal (2026-09-18)

Hasta ahora, `PortalEngagementService::inactivityAlerts()` ya calculaba qué casos llevan más de 15 días sin actividad de portal, o tienen un cuidador autorizado que nunca ha ingresado — pero solo se veía si alguien del staff entraba a la pantalla "Trazabilidad del portal" a revisarlo. Ahora es proactivo: un comando programado (`agora:check-portal-inactivity`, todos los días a las 7:30 a.m. hora Colombia) reutiliza exactamente ese mismo cálculo ya validado y le avisa al equipo del caso (auditor asignado, o líderes del programa — vía `StaffNotifier`, el mismo mecanismo de siempre) por notificación en el panel y correo.

Decisiones de diseño:
- No se repite el aviso todos los días para el mismo caso — una vez notificado, espera al menos 7 días antes de volver a avisar (columna nueva `last_inactivity_alert_at` en `pics_cases`). Sin esto, un caso inactivo generaría un correo diario indefinidamente.
- Reutiliza el cálculo ya existente y ya confiable de `PortalEngagementService::inactivityAlerts()` en vez de inventar un umbral nuevo — los mismos 15 días sin actividad y el mismo criterio de "cuidador nunca ingresó" que el staff ya ve hoy en el dashboard.
- Casos `completed`/`cancelled` nunca se notifican (igual que ya hacía `inactivityAlerts()`).
- La idea detrás de esta función: que "usar la app" tenga una consecuencia real de cuidado — si alguien deja de entrar, una persona del equipo se entera y puede llamar — no solo perderse en un dashboard que nadie revisa a diario.

Verificado con 6 pruebas automatizadas nuevas (245 en total, todas en verde) y una ejecución real del comando contra la base de datos de desarrollo (con transacción y rollback, sin dejar datos de prueba): confirmó correctamente que el único caso existente (`completed`) se excluye, y una verificación aparte confirmó que el correo y la notificación en panel se renderizan sin errores.

## Resumen imprimible para la cita médica (2026-09-17)

Nueva página `/portal/resumen-cita` (controlador `PortalSummaryController`, vista `portal/summary.blade.php`): una hoja de una sola página, lista para imprimir o mostrar en el celular en la próxima consulta, con botón "🖨️ Imprimir / Guardar como PDF" que usa `window.print()` del navegador — sin librería de PDF ni paso de build, igual espíritu que el resto del portal.

Contenido, todo tomado de datos ya estructurados y validados (nunca texto libre del diario, nunca nada inventado):
- Próximas citas/terapias agendadas.
- Medicamentos conciliados activos (nombre, dosis, vía, frecuencia, estado, instrucciones) — los suspendidos no aparecen.
- Metas de recuperación activas y el último avance reportado.
- Últimas 8 lecturas de monitoreo en casa.
- El autorreporte de bienestar más reciente, mostrado con los mismos semáforos clínicos (verde/amarillo/rojo) que ya usa el equipo en `PicsFollowup` — se reutiliza el cálculo existente, no se inventan puntos de corte nuevos.
- Dudas o dificultades reportadas que todavía no tienen respuesta del equipo.

Se agregó como una tarjeta más en "Explora todo" del Inicio. Lleva un aviso explícito de que es una ayuda para la conversación con el equipo médico, no un reemplazo de la valoración clínica.

Verificado con 3 pruebas automatizadas nuevas (239 en total, todas en verde) y un recorrido manual real contra el servidor de desarrollo con el paciente demo: todas las secciones se generan correctamente con sus datos reales.

## Calendario interactivo y modo fácil (2026-09-17)

Dos mejoras en paralelo, elegidas por el usuario entre varias propuestas.

**Calendario más interactivo** (`resources/views/livewire/portal/calendar-component.blade.php`, `app/Livewire/Portal/CalendarComponent.php`, `app/Http/Controllers/Portal/PortalCalendarController.php`):
- Tocar cualquier día del calendario abre un modal para agregar un recordatorio ahí mismo (`dateClick`), sin tener que bajar al formulario.
- Tocar uno de tus propios recordatorios (📌) abre el mismo modal en modo edición, con un botón para borrarlo.
- Arrastrar un recordatorio propio a otro día/hora lo reprograma (`eventDrop`) — únicamente tus recordatorios personales son arrastrables (`editable: true` solo en esos eventos); las citas y terapias las sigue controlando el equipo clínico.
- Filtros con casillas para mostrar/ocultar cada tipo de evento (cita, terapia, tarea, remisión, medicamento, mi recordatorio) — útil cuando hay muchos eventos el mismo mes.
- Vistas de lista y de día, además de mes/semana, para quien prefiera leer un renglón por evento en vez de una cuadrícula.
- Nuevos métodos en `CalendarComponent`: `saveReminder()` (crea o edita), `deleteReminder()`, `rescheduleReminder()` — los tres limitados por consulta a `created_by_type`/`created_by_id` del actor autenticado, así que nadie puede editar ni borrar el recordatorio de otra persona (ni siquiera el del otro miembro de la misma pareja paciente/cuidador).

**Modo fácil** (accesibilidad para cualquier edad):
- Botón "🔎 Modo fácil" en la barra superior del portal. Al activarlo, guarda la preferencia en la cuenta del actor (columna `portal_easy_mode` en `patients`/`caregivers`, no en el navegador) para que se mantenga entre visitas y dispositivos — mismo patrón que `has_seen_portal_tour`.
- Con el modo activo: letra más grande, más contraste en el texto secundario, botones y campos de formulario más grandes — pensado para personas mayores o con baja visión.
- Botón flotante "🔊 Leer esta página" que usa la Web Speech API nativa del navegador (sin librerías externas ni costo) para leer en voz alta el contenido de la página actual. Se oculta solo si el navegador no soporta la API (progressive enhancement) — nunca se muestra un botón que no vaya a funcionar.

Verificado con 8 pruebas automatizadas nuevas (236 en total, todas en verde) y un recorrido manual real contra el servidor de desarrollo: el modal de recordatorios, los filtros y las vistas de lista/día se generan correctamente en `/portal/calendario`; el modo fácil se activa/desactiva correctamente y cambia la clase del `<body>` y el texto del botón (se restableció al estado original del paciente demo después de la prueba).

## Ritual diario guiado "Tu día" (2026-09-16)

El paciente pidió que el portal se vuelva parte de su rutina de todo el día, no solo un lugar al que entrar de vez en cuando. En vez de agregar notificaciones push (que requieren infraestructura nueva) se rediseñó el Inicio: ahora, justo debajo de la tarjeta de nivel/racha, aparece "☀️ Tu día" — tres columnas (Mañana/Tarde/Noche) con un checklist de lo que ya hiciste hoy (✅/pendiente, con enlace directo al módulo) y, debajo, lo que tengas agendado en esa franja (citas, terapias, medicamentos con horario, tus recordatorios personales), tomado de las mismas fuentes ya usadas por el calendario.

Decisiones de diseño:
- Las franjas horarias son solo una sugerencia de cuándo suele hacerse cada cosa — el sistema no le exige al paciente hacerlo a esa hora exacta, ni oculta nada si lo hace en otro momento del día. Nunca se fabrica una obligación que no existe.
- El checklist ("¿Cómo amaneciste?", "Monitoreo en casa", "Avance de tus metas", "Escribe en tu diario") se marca como hecho únicamente si existe un registro real de ese actor con fecha de hoy — reutiliza exactamente las mismas consultas por actor que ya alimentan el sistema de puntos, así que ambos siempre coinciden.
- No se agregó tracking de "medicamento tomado" — el bloque de medicamentos sigue siendo solo informativo (igual que en el calendario), por la misma razón de seguridad del paciente ya documentada: nunca se inventa ni se confirma algo que el paciente no reportó.

Verificado con 3 pruebas automatizadas nuevas (231 en total, todas en verde) y un recorrido manual real contra el servidor de desarrollo: con el paciente demo, "Tu día" se ve correctamente con las cuatro tareas pendientes y los horarios de un medicamento (Enalapril) agendados para hoy.

## Confirmar/rechazar asistencia a citas (2026-09-16)

Cierra el ciclo del calendario recién construido: antes, citas y terapias eran de solo lectura para el paciente. Ahora, al hacer clic en una cita o terapia en `/portal/calendario`, aparece un modal con dos botones — "✅ Confirmaré" / "❌ No podré asistir" — que guarda la respuesta (`patient_response`, quién y cuándo respondió) y **notifica al staff automáticamente** (al auditor asignado al caso, o a quienes lideran el programa si aún no hay uno asignado — reutilizando `StaffNotifier`, extraído del mismo mecanismo ya usado para las solicitudes de ayuda). Así el staff se entera de una inasistencia probable con anticipación, en vez de el día de la cita. La respuesta también queda visible para el staff en la pestaña "Agenda coordinada" de cada caso.

Solo aplica a citas y terapias (`PicsAgendaItem::RESPONDABLE_TYPES`) — tareas y recordatorios internos no tienen sentido "confirmarlos". Una vez respondida, la cita muestra ✅ o ❌ en el calendario y ya no se puede volver a responder desde el portal.

Verificado con 4 pruebas automatizadas nuevas (228 en total, todas en verde) y un recorrido manual real: el modal aparece al hacer clic, y el correo de notificación al staff se renderiza correctamente tanto para confirmación como para inasistencia.

## Calendario visual del portal (2026-09-15)

El usuario pidió un módulo de citas más un calendario visual "como Google Calendar" donde el paciente vea citas, horarios de medicamentos y terapias, y pueda programar. Nuevo en `/portal/calendario`, usando **FullCalendar** (librería real vía CDN, sin necesitar Node.js — mismo patrón que `canvas-confetti`), con vista de mes/semana en español:

- **Citas y terapias**: `PicsAgendaItem` ganó el tipo "terapia" (antes solo cita/tarea/recordatorio) — el staff las sigue programando desde `/pics` igual que siempre, ahora el paciente las ve en su calendario.
- **Remisiones**: las que ya gestiona `PicsReferral` también aparecen.
- **Medicamentos**: se agregó un campo opcional `schedule_times` (horas concretas, ej. 08:00 y 20:00) al conciliar cada medicamento en `/pics`. **Deliberadamente no se intentó adivinar horarios a partir del texto libre de "frecuencia"** ("cada 12 horas") — eso habría sido un riesgo real de seguridad del paciente; si el staff no diligencia horarios concretos, el medicamento simplemente no aparece en el calendario.
- **Recordatorios personales** (`PersonalReminder`, modelo nuevo y mínimo): el paciente o el cuidador puede agregar sus propias notas ("tomar agua", "llamar a mi hermana") — **privadas por actor**: cada quien ve solo las suyas, nunca las del otro, y el staff no las ve en ningún recurso de `/pics`. Es su propio espacio de organización, no una cita clínica real.

El calendario es una vista de lectura combinada (mismo espíritu que `PicsAgendaService::upcoming()`, que ya unía remisiones + agenda para `/pics/agenda-coordinada`) servida por un endpoint JSON nuevo que FullCalendar consulta directamente al navegar de mes — no pasa por Livewire para la navegación, solo para agregar recordatorios (que sí dispara un refresco del calendario sin recargar la página).

Verificado con 5 pruebas automatizadas nuevas (224 en total, todas en verde) y un recorrido manual real: cita, terapia, remisión y medicamento con horario aparecen correctamente coloreados en el calendario del paciente demo; un medicamento sin horarios no genera eventos. En el camino confirmé (de nuevo) el problema ya documentado de mezclar `actingAs()` de dos guards en una misma prueba — se solucionó separando en dos métodos de prueba.

## Tour de bienvenida al modo aventura (2026-09-14)

Con las 10 pantallas ya en modo aventura, quedaba un riesgo real: alguien entra por primera vez, ve "Nivel 1 · 0 XP" y no entiende qué significa. Ahora, la primera vez que un paciente o cuidador entra a `/portal`, aparece un modal explicando en tres líneas qué son los puntos, las insignias y la racha — y deja explícito que **no es parte de la evaluación médica**, solo una capa de motivación; el equipo clínico solo ve la información real que la persona reporta, nunca los puntos.

Se guarda `has_seen_portal_tour` en `patients`/`caregivers` (no en `localStorage`) para que no vuelva a aparecer sin importar desde qué dispositivo entre la misma persona — relevante porque un paciente y su familia suelen alternar entre varios celulares/tablets.

Verificado con 2 pruebas automatizadas nuevas (219 en total, todas en verde) y un recorrido manual real: el tour aparece la primera vez, y tras cerrarlo (con el botón o la X, ambos lo marcan como visto) ya no vuelve a aparecer.

## "Modo aventura" en todo el portal (2026-09-13)

El usuario aprobó el piloto (Inicio + Metas) y pidió extenderlo a **todo** el portal, con un tono totalmente lúdico e inspirado en el modelo de interacción de Instagram/Facebook (feed, reacciones, racha de días). Las 8 pantallas restantes quedaron rediseñadas:

- **Racha de días (🔥)**: nueva en el Inicio, junto al nivel/XP — días consecutivos con actividad del propio actor, calculada en memoria a partir de las mismas fechas ya usadas para los puntos (nunca se guarda en base de datos).
- **Mi diario**: ahora es un feed tipo Instagram — cada entrada con avatar circular con la inicial del autor, y un botón de reacción ❤️ **decorativo y privado** (se guarda solo en `localStorage` del navegador de quien lo usa, no hay "otros" reaccionando — es cariño hacia lo que uno mismo escribió, no interacción social real).
- **Mis metas** ("Mis misiones"), **Preparación para el alta** (checklist con círculos que hacen "pop" al completarse y barra de progreso), **Medicamentos** y **Monitoreo en casa** (tarjetas grandes tipo "misión"/estadística con emoji e ícono de color por tipo), **Educación** (feed agrupado por categoría con "anillo de historia" estilo Instagram — degradado si no se ha leído, gris si ya se leyó) — todas con confeti (`celebrate`) al completar una acción con sentido de logro.
- **Cómo me siento** y **Antes y ahora**: mismo contenido y preguntas exactas (instrumentos clínicos validados — no se tocó ninguna pregunta, opción ni fórmula de puntaje), solo el empaque visual cambió a tarjetas del mismo lenguaje.
- **Necesito ayuda**: estilo de burbujas de chat (mi mensaje vs. la respuesta del equipo), sin confeti — no tiene sentido celebrar que alguien reporte una dificultad.
- Se sembraron datos de demostración reales para medicamentos conciliados, preparación de alta y educación en el caso demo (antes esos módulos estaban vacíos para el paciente/cuidador demo), incorporados directamente al seeder para que sobrevivan a un reseed.

Verificado con la suite completa (217 pruebas, todas en verde) y un recorrido manual real por las 10 pantallas del portal como paciente y como cuidador demo, confirmando que cada una muestra las clases y el contenido nuevo esperado.

## Piloto de "modo aventura" en el portal (2026-09-13)

A pedido del usuario: hacer el portal del paciente/familia más gráfico, intuitivo y lúdico — "como si el paciente estuviera jugando". Se acordó un piloto en dos pantallas (Inicio y Metas) antes de extenderlo a las otras 8, con tono **totalmente lúdico** (mascota, puntos, niveles, animación al lograr algo).

- **Puntos e insignias son puramente de interfaz, no clínicos**: se calculan al vuelo a partir de lo que el propio actor (paciente o cuidador) ya reportó — diario, avances de metas, "Cómo me siento", pasaporte, monitoreo, educación vista, temas de alta revisados, pasos de la ruta del cuidador, solicitudes — y **no se guardan en base de datos**. Quedó documentado explícitamente en el código para que nadie los confunda con un puntaje de salud.
- **Inicio** (`/portal`): mascota con saludo, tarjeta de nivel/XP con barra de progreso, estante de insignias (bloqueadas en gris, desbloqueadas a color), y las 9-10 pantallas del portal como "misiones" — tarjetas grandes con ícono y color propio, con un contador pulsante si hay algo pendiente ahí.
- **Metas** (`/portal/metas`), ahora "Mis misiones": cada meta es una tarjeta con anillo de progreso (según cuántos avances se han contado) y hasta 3 estrellas; al guardar un avance nuevo, estalla un confeti (`canvas-confetti` por CDN, sin paso de build — respeta `prefers-reduced-motion` para quien lo necesite).
- El resto del portal (8 pantallas) siguió funcionando igual en este piloto, sin tocar — solo cambió el fondo/navegación levemente para que se sienta parte del mismo "mundo" (gradiente morado-turquesa, navegación en píldoras). El usuario aprobó el estilo y pidió extenderlo a todo el portal — ver la sección de arriba.

Verificado con la suite completa (217 pruebas, todas en verde) y un recorrido manual real contra el servidor: inicio y metas renderizan las clases e insignias esperadas para el paciente demo (todo bloqueado, sin actividad previa) y para el cuidador demo (insignias desbloqueadas y XP > 0, reflejando su actividad real ya sembrada).

## Notificación al staff cuando llega una solicitud (2026-09-13)

Otra brecha real: cuando un paciente o cuidador reportaba una dificultad o duda desde "Necesito ayuda", nadie del staff se enteraba — solo lo descubrían si entraban manualmente a revisar la lista de "Solicitudes y dificultades". Una dificultad marcada como urgente podía quedar sin respuesta simplemente porque nadie miró esa pantalla. El proyecto ya tenía un patrón probado de notificaciones de Filament (campanita del panel + correo, `MAIL_MAILER=log` en desarrollo) usado en Sepsis y ACV para asignaciones de caso y comentarios — PICS no tenía ninguna.

Ahora, al crear una `SupportRequest`, se notifica automáticamente al auditor asignado al caso; si el caso todavía no tiene auditor asignado, se notifica a quienes lideran el programa (líder, líder clínico o coordinador). Las solicitudes con prioridad alta se marcan como "Urgente" en el título y el ícono, tanto en la notificación del panel como en el correo.

Verificado con 3 pruebas automatizadas nuevas (217 en total, todas en verde) y un recorrido manual: el correo se renderiza sin errores de plantilla y muestra el aviso de urgencia cuando corresponde.

## Trazabilidad del portal ampliada (2026-09-13)

Mismo tipo de brecha que el inicio del portal: `PortalEngagementService` (`/pics/trazabilidad-portal`) se construyó antes de la Etapa 2/3 y nunca se actualizó — medía login, diario, metas, "Cómo me siento", pasaporte y solicitudes, pero nada sobre plan interdisciplinario, ruta del cuidador, preparación de alta, medicamentos conciliados, monitoreo en casa ni educación. Ahora `caseSnapshot()` y `aggregate()` incluyen las seis piezas nuevas, agrupadas visualmente en una sección aparte ("Preparación de egreso") tanto en el resumen institucional como en la pestaña "Trazabilidad del portal" de cada caso — sin inflar la tabla principal: las columnas nuevas quedan ocultas por defecto (`toggleable`) y se activan manualmente si se necesitan.

De paso se sumó al resumen institucional el porcentaje de pasaportes confirmados (`passport_confirmed_pct`), que ya se calculaba pero nunca se mostraba en la vista.

Verificado con 2 pruebas automatizadas nuevas (214 en total, todas en verde): el snapshot de un caso refleja correctamente las seis piezas nuevas, y el agregado calcula bien los porcentajes entre varios casos.

## Rediseño del inicio del portal (2026-09-13)

Auditoría de brechas fuera de la lista del prompt maestro: el inicio de `/portal` no se había tocado desde la Iteración 1 — solo mostraba 3 tarjetas y 2 accesos directos (diario, metas), sin ningún rastro de los 8 módulos agregados después (pasaporte, bienestar, ayuda, medicamentos, monitoreo, preparación de alta, ruta del cuidador, educación). Ahora `/portal` muestra: la **etapa clínica actual** (nueva tarjeta), **accesos directos a los 10 módulos** (la ruta del cuidador solo aparece si el cuidador tiene `can_access_journey`), y un resumen real de **pendientes** reutilizando datos que ya existen — sin tablas nuevas: contenido educativo sin leer, temas de preparación de alta sin revisar, solicitudes de ayuda esperando respuesta, y pasos de la ruta del cuidador sin completar (solo cuidador autorizado).

De paso encontré y corregí un bug real de permisos expuesto al escribir las pruebas: el menú de navegación (`portal/layout.blade.php`) mostraba el enlace "Mi ruta como cuidador" a **cualquier** cuidador autenticado, sin verificar `can_access_journey` — un cuidador sin esa autorización veía el enlace en el menú aunque al entrar recibiera 403. Ahora el menú solo lo muestra si `CaseAccess::caregiverCanAccessJourney()` lo confirma, igual que ya hacía la página misma.

Verificado con 4 pruebas automatizadas nuevas (212 en total, todas en verde): la etapa clínica y los accesos aparecen, el resumen de pendientes cuenta correctamente entre módulos, y el enlace de la ruta del cuidador aparece/desaparece según la autorización real.

## El paciente puede escribir en el diario (2026-09-13)

Hasta ahora el diario era de una sola vía: el cuidador escribía, el paciente solo leía (así lo pedía explícitamente el prompt maestro para la Iteración 1, "participar posteriormente"). Ahora el paciente también puede escribir sus propias entradas desde `/portal/diario` — mismo formulario, pero sin los campos "mensaje para el paciente" y "¿el paciente puede leerla?" (no aplican cuando el propio paciente escribe: su entrada siempre le es visible a él mismo). El cuidador sigue viendo todas las entradas del caso, incluidas las del paciente, sin ningún cambio en su flujo.

De paso corregí un bug que esto habría expuesto: la vista y la pestaña del caso en `/pics` mostraban el autor con `$entry->authorable->name`, pero `Patient` guarda el nombre en `full_name`, no en `name` — una entrada del paciente se habría visto con el autor en blanco. Se centralizó en `DiaryEntry::authorLabel()` para no repetir ese detalle en cada vista.

Verificado con 1 prueba nueva (208 en total, todas en verde): el paciente escribe, la entrada queda con `visible_to_patient = true` automáticamente, se ve con su nombre correcto, y el cuidador también la ve.

## Etapa 3 — educación personalizada (2026-09-13)

Última pieza de la Etapa 3. Hasta ahora la "educación" solo existía como texto libre repetido por caso (el campo `medications_review` del seguimiento, o las instrucciones de `DischargeReadinessItem`). Ahora hay un **catálogo reutilizable** (`/pics → Educación personalizada`, `EducationResource`): el staff redacta un contenido una sola vez (título, categoría — respiratorio/movilidad/cognitivo/emocional/nutrición/cuidador —, dirigido a paciente/cuidador/ambos, cuerpo de texto) y luego lo **asigna** a los casos donde aplica desde la pestaña "Educación personalizada" de cada caso, con una nota opcional de por qué aplica a ese paciente en particular — eso es lo "personalizado": la biblioteca es compartida, la selección es por paciente.

El portal (`/portal/educacion`) muestra solo el contenido asignado y activo, agrupado por categoría, con un botón "Marcar como leído". A diferencia de "Preparación para el alta", aquí no hay verificación profesional de comprensión — es material de consulta continua, no un requisito de egreso.

Verificado con 6 pruebas automatizadas nuevas (207 en total, todas en verde): creación del catálogo, asignación con atribución server-side, el portal solo muestra contenido activo (uno inactivo asignado no aparece), marcar como leído, aislamiento entre casos, y render real de las páginas del recurso y la pestaña del caso en `/pics`.

**Con esto se cierra la Etapa 3 completa**: medicamentos conciliados, monitoreo en casa (registro manual) y educación personalizada.

## Etapa 3 — monitoreo en casa (2026-09-11)

El usuario pidió avanzar con "integraciones externas" (dispositivos de monitoreo), pero confirmó que **no hay hoy ninguna API ni credencial real** de ningún proveedor. Construir una integración contra un sistema que no existe habría sido fabricar una conexión falsa, en contra de la convención del proyecto de no inventar funcionalidad ni datos. Se construyó en cambio lo que sí es real hoy: un **registro manual de lecturas** (`/portal/monitoreo`, pestaña "Monitoreo en casa" de solo lectura en `/pics → Casos → [caso]`) — saturación, frecuencia cardíaca, presión arterial, temperatura, frecuencia respiratoria, glucosa o peso, digitadas por el paciente o el cuidador. Sin reglas de "rango normal" ni alertas automáticas — eso sería inventar una regla clínica no pedida; el staff interpreta los números. Cuando exista una API real de algún proveedor, este modelo de datos (`HomeMonitoringReading`) es el punto de partida natural para poblarlo automáticamente en vez de a mano.

Verificado con 4 pruebas automatizadas nuevas (201 en total, todas en verde): paciente y cuidador autorizado registran lecturas con atribución server-side, la pestaña de solo lectura renderiza en el caso, y el aislamiento entre casos.

## Etapa 3 — medicamentos conciliados (2026-09-11)

Primera pieza de la Etapa 3 (elegida por el usuario entre medicamentos, educación personalizada e integraciones): **conciliación de medicamentos** (`/pics → Medicamentos conciliados`, `/portal/medicamentos`). Antes solo existía un campo de texto libre dentro del seguimiento (`PicsFollowup.medications_review`) y el tema genérico "medicamentos" en la preparación de alta (que solo mide si el paciente entendió el tema, no el detalle clínico). Ahora hay una lista real por medicamento: nombre, dosis, vía, frecuencia y la **decisión de conciliación** (continúa igual / nuevo / suspendido / dosis ajustada), documentada por el staff. El portal la muestra en modo lectura — **no es una herramienta de prescripción**, es trazabilidad para que el paciente/familia sepan qué tomar.

Si el paciente o el cuidador tiene una duda sobre un medicamento, no se agregó una tercera capa de "revisado/confirmado": se reutiliza el `SupportRequest` ya construido en la Etapa 1 con un tipo nuevo (`duda_medicamento`) — el enlace "Tengo una duda sobre este medicamento" lleva directo a "Necesito ayuda" con el tipo y la descripción ya prellenados con el nombre del medicamento. Es la primera aplicación concreta de la decisión ya tomada de extender `SupportRequest` con más tipos en vez de construir un flujo de solicitud paralelo por módulo.

Verificado con 5 pruebas automatizadas nuevas (197 en total, todas en verde) y un recorrido manual completo: conciliación creada desde `/pics`, vista en el portal, y el flujo "tengo una duda" confirmado de punta a punta contra el servidor real (el snapshot de Livewire mostró el tipo y la descripción prellenados correctamente).

## Etapa 2 (2026-09-10)

Cierra las 5 brechas pendientes de la Etapa 2 del prompt maestro:

- **Estados formales del episodio**: cada caso PICS ahora avanza por **UCI → Hospitalización → Egreso → Seguimiento** (`clinical_stage`, visible como badge en el caso). Es un concepto distinto del "Estado del caso" (`status`, el flujo de auditoría/revisión, ya existente). El avance es secuencial (no se pueden saltar etapas salvo el líder/administrador) y el **egreso nunca es automático**: solo lo puede confirmar el líder/administrador o un médico del equipo, desde la acción dedicada "Confirmar egreso" — nunca como efecto de otro cambio en el formulario. Cada etapa registra su propia fecha (incluida quién confirmó el egreso).
- **Plan interdisciplinario formal con versiones** (`/pics → Plan interdisciplinario`): objetivo general, criterios de egreso y el aporte de cada disciplina (medicina, enfermería, terapias, psicología, trabajo social, nutrición). Cada vez que se edita, la versión anterior queda archivada completa con su vigencia — igual que ya funcionaba para las fichas técnicas de indicadores — así un plan histórico nunca se reescribe en silencio.
- **Ruta propia del cuidador** (`/portal/ruta-cuidador`, exclusiva del cuidador — nueva pestaña "Ruta del cuidador" en el caso): pasos de orientación, autocuidado, red de apoyo y preparación para el manejo en casa que el cuidador completa a su ritmo y el profesional confirma. Requiere una autorización propia (`can_access_journey`), independiente de poder escribir en el diario.
- **Preparación para el alta con recorrido de comprensión** (`/pics → Preparación para el alta`, `/portal/preparacion-alta`): temas como medicamentos, signos de alarma, citas de control y cuidados en casa. Dos capas independientes: el paciente/cuidador marca "ya lo revisé" desde el portal, y el profesional verifica la comprensión real por teach-back — esta segunda capa no depende de que el portal se haya usado.
- **Agenda coordinada interna** (`/pics → Agenda coordinada`): lista cronológica (no un calendario — este entorno no tiene Node.js para cargar una librería de calendario) que combina remisiones y tareas/citas/recordatorios internos de todos los casos, agrupada por fecha, con acciones rápidas para completar o cancelar.

Verificado con 28 pruebas automatizadas nuevas (192 en total, todas en verde) y un recorrido manual completo contra el servidor real.

## Cierre de los 5 huecos del portal (2026-09-09)

Hasta ahora no existía ninguna forma de **crear o autorizar un cuidador desde la interfaz** (solo por seeder o pruebas), y las alertas de la trazabilidad del portal eran solo indicadores pasivos. Se cierran cinco huecos relacionados:

- **Alta y autorización real de cuidadores**: nueva pestaña "Familia y cuidadores autorizados" dentro de cada caso PICS (`/pics → Casos → [caso]`). El profesional busca o crea el cuidador, define el parentesco y si puede escribir en el diario; puede revocar el acceso en cualquier momento.
- **Invitación real por correo**: la acción "Enviar invitación" (para el cuidador) y "Configurar acceso del paciente" (en la cabecera del caso) generan una contraseña temporal, la guardan hasheada y envían un correo con las credenciales (`MAIL_MAILER=log` en este entorno: nada sale a un correo real, todo queda en `storage/logs/laravel.log`, coherente con "solo pacientes ficticios en desarrollo").
- **"Olvidé mi contraseña"** (`/portal/olvide-password`): funciona igual para paciente y cuidador, con brokers de recuperación propios (`patients`/`caregivers`) y su propia tabla de tokens — responde siempre el mismo mensaje exista o no la cuenta, para no revelar si un correo está registrado.
- **Cambio de contraseña obligatorio en el primer ingreso**: cualquier cuenta invitada queda marcada `must_change_password = true`; al iniciar sesión se le redirige a `/portal/cambiar-contrasena` antes de poder usar el resto del portal. Las cuentas de demostración documentadas abajo **no** tienen esta bandera activa, para que se pueda entrar directo con ellas.
- **Alertas de inactividad y tendencia semanal** en `/pics/trazabilidad-portal`: además del resumen agregado ya existente, ahora se listan los casos que necesitan atención (cuidador autorizado que nunca ha entrado tras 3 días, o caso sin ninguna actividad de portal en 15 días) y una gráfica de barras (HTML/CSS, sin librerías nuevas) con la actividad de las últimas 8 semanas.

Verificado con 164 pruebas automatizadas (11 nuevas: relation manager de cuidadores, invitación, recuperación de contraseña para ambos guards, cambio forzado de punta a punta, alertas y tendencia) y un recorrido manual completo contra el servidor real vía HTTP: invitación → correo confirmado en `storage/logs/laravel.log` → login con la contraseña temporal → redirección forzada a cambiar contraseña → cambio → acceso normal al portal, con la base de datos verificada en cada paso.

## Trazabilidad del portal (`/pics/trazabilidad-portal`, 2026-09-09)

Conecta el panel profesional con el uso real de `/portal`: los logins de paciente y cuidador ahora se registran de verdad (antes la columna existía pero nunca se llenaba). El módulo nuevo mide, por caso y en agregado institucional: si el cuidador está autorizado y ha entrado, último ingreso del paciente, entradas de diario, reportes de metas por autor (paciente vs. cuidador), autorreportes de "Cómo me siento", solicitudes de ayuda respondidas y tiempo de respuesta, y estado del pasaporte de recuperación. Visible también como pestaña dentro de cada caso y como enlace desde `/pics/indicadores`. Es un eje de medición de **uso de la plataforma**, distinto de los indicadores clínicos PICS.

## Cómo ejecutar la demo

```powershell
.\serve.ps1
```

Requiere PHP 8.4 portátil en `runtime\php\` (ya incluido en este equipo; no se sube a git) y la base de datos sqlite local en `database\database.sqlite`.

Para reiniciar la demo sin afectar otros datos:

```powershell
php artisan migrate:fresh
php artisan db:seed                          # admin + catálogo institucional
php artisan db:seed --class=PicsDemoSeeder    # caso PICS de ejemplo
php artisan db:seed --class=PosuciDemoSeeder  # paciente, cuidador, diario y metas de ejemplo
```

### Accesos de prueba (solo datos ficticios)

| Rol | URL | Usuario | Contraseña |
|---|---|---|---|
| Profesional (panel `/pics`) | `/pics/login` | `admin` | `Pics2026!` |
| Paciente (portal) | `/portal/login` | `paciente.demo@posuci360.test` | `Posuci2026!` |
| Cuidador (portal) | `/portal/login` | `familia.demo@posuci360.test` | `Posuci2026!` |

## Qué incluye la Iteración 1 (implementado y verificado)

- **Autenticación separada**: dos guards de sesión nuevos e independientes del staff (`patient`, `caregiver`), sin exponer ningún dato clínico a través del panel administrativo.
- **Diario de recuperación**: el cuidador autorizado escribe entradas de texto; el paciente las lee solo si están marcadas como visibles. El profesional las ve (solo lectura) desde el caso en `/pics`.
- **Metas de recuperación**: el profesional las crea desde `/pics → Metas de recuperación`; el paciente o el cuidador reportan avances desde el portal; el profesional valida cada reporte por separado (`/pics → Seguimiento POSUCI` o desde la propia meta). El reporte y la validación quedan siempre separados en la base de datos.
- **Autorización familiar revocable**: un cuidador solo entra al caso que un miembro del staff le autorizó explícitamente; revocar la autorización le quita el acceso de inmediato.
- **Aislamiento por caso**: cada actor solo ve su propio caso, verificado con peticiones HTTP directas (no solo ocultando botones).

Verificado con 8 pruebas automatizadas nuevas (`tests/Feature/Posuci/PosuciIteration1Test.php`) más las 124 pruebas existentes del proyecto (todas pasan). También se probó manualmente el flujo completo en navegador vía `curl` (login, diario, metas) contra el servidor local.

## Módulo clínico PICS (portado desde "Panel de control", 2026-09-09)

El seguimiento PICS (`/pics → Casos → Seguimientos`) ya no usa campos de texto libre: usa los mismos instrumentos validados que el usuario tenía construidos en `C:\xampp\htdocs\Panel de control` — Pfeiffer/AMT (cognición), MoCA (solo si Pfeiffer ≥ 3 errores), HADS-A (ansiedad), PHQ-9 (depresión), PC-PTSD-5 (estrés postraumático), PTG-SF (crecimiento postraumático, solo checkpoints 3m/6m/12m) y PICS-F (sobrecarga del cuidador). Los puntajes y las banderas de "tamizaje positivo" se calculan siempre desde las respuestas — nunca se diligencian a mano.

`PicsCase` ahora calcula un **riesgo PICS de 7 factores** (estancia UCI, ventilación mecánica, delirium, edad, Barthel, choque/sepsis, debilidad adquirida en UCI por MRC/handgrip) — mismo algoritmo y mismos puntos de corte que el original. Se dispara con la acción "Recalcular riesgo" en el caso (no es automático, porque estos datos se completan por etapas).

El portal ganó una pantalla nueva, **"Cómo me siento"** (`/portal/bienestar`): el paciente autoadministra PHQ-9, HADS-A, PC-PTSD-5 y PTG-SF; el cuidador autoadministra PICS-F. Pfeiffer/AMT, MoCA y el tamizaje de disfagia **no** están en el portal — por seguridad clínica, esos requieren un evaluador presencial calificando respuestas correctas/incorrectas. Lo que el paciente/familia envía queda pendiente de confirmación profesional (mismo patrón que ya existía para las metas de recuperación).

Verificado con 18 pruebas nuevas: fórmulas de puntaje con casos de borde exactos en cada corte, el algoritmo de riesgo completo, render del panel con el formulario nuevo, y el flujo de autorreporte del portal de punta a punta.

## Etapa 1 — brechas cerradas frente al prompt maestro más reciente (2026-09-09)

El nuevo prompt maestro pedía explícitamente, para la Etapa 1, un **pasaporte de recuperación** y el ciclo **"el paciente reporta una dificultad → el profesional responde → el paciente ve la respuesta"**. Ninguno existía todavía; ya están implementados:

- **Pasaporte de recuperación** (`/pics → Pasaporte de recuperación`, `/portal/pasaporte` "Antes y ahora"): estado previo al ingreso (movilidad, autonomía, actividades habituales, apoyos), situación actual, barreras (hogar/transporte/acompañamiento/acceso) y una lista de necesidades/objetivos en las propias palabras del paciente (ej. "quiero volver a cocinar"). Registra quién lo reportó y cuándo, con confirmación profesional separada. El portal muestra el "antes" junto al "ahora" (último seguimiento registrado) **sin calcular ningún porcentaje de recuperación** — la comparación la hace quien lo lee.
- **Solicitudes y dificultades** (`/pics → Solicitudes y dificultades`, `/portal/ayuda` "Necesito ayuda"): el paciente o el cuidador reportan una dificultad con la prioridad que ellos perciben; el profesional la asigna, reconoce recepción, responde, escala o resuelve; el paciente ve la respuesta en su propia sesión, sin ver a quién se asignó internamente.
- **Motivo estructurado de dificultad** al reportar un avance de meta (cansancio, dolor, falta de ayuda, dificultad para comprender, otra), en vez de solo un sí/no.

Verificado con 16 pruebas nuevas, incluyendo el ciclo completo de la solicitud (reporta → responde → el paciente consulta) y el aislamiento entre casos (un cuidador de un caso no ve el pasaporte ni las solicitudes de otro).

## Qué quedó simulado o pendiente (no construido todavía)

Para etapas posteriores (según el prompt maestro más reciente): configuración institucional/academia, integraciones reales con proveedores de dispositivos (hoy el monitoreo en casa es un registro manual, no una API conectada) y resúmenes asistidos por IA. Tampoco hay fotos/audio en el diario (solo texto, como pide explícitamente el prompt). La agenda coordinada es una lista cronológica, no un calendario visual — no hay Node.js en este entorno para cargar una librería de calendario.

## Restricción de entorno detectada

Este equipo no tiene Node.js instalado (solo se copió `node_modules`, sin el runtime). El portal usa Bootstrap 5 y Livewire, ambos ya funcionan sin recompilar assets — pero si en el futuro se necesita Tailwind o JS nuevo por fuera de esas dos librerías, hará falta instalar Node.js y correr `npm run build`.

## Modelo de datos nuevo (además de lo ya documentado para PICS)

`patients` (+ columnas de login), `caregivers`, `caregiver_authorizations`, `diary_entries`, `recovery_goals`, `goal_progress_reports` — todas ancladas a `pics_cases` (el "episodio" del paciente). Detalle completo de columnas en la migración `database/migrations/2026_09_08_100000_create_posuci_iteration1_tables.php`. Para la Etapa 2: `care_plans`/`care_plan_versions`, `caregiver_journey_steps`, `discharge_readiness_checks`/`discharge_readiness_items`, `pics_agenda_items`, más `clinical_stage` (y sus fechas) en `pics_cases` y `can_access_journey` en `caregiver_authorizations` — ver las migraciones fechadas `2026_09_10_*`. Para la Etapa 3: `medication_reconciliations`/`medication_reconciliation_items` (`2026_09_11_000000_create_medication_reconciliations_table.php`), `home_monitoring_readings` (`2026_09_12_000000_create_home_monitoring_readings_table.php`) y `education_resources`/`education_assignments` (`2026_09_13_000000_create_education_resources_table.php`).

## Decisión confirmada para la Etapa 2

El usuario confirmó (2026-09-08): el ciclo completo **UCI → Hospitalización → Egreso → Seguimiento**, con el egreso dependiendo siempre de confirmación profesional explícita; y que `SupportRequest` se extienda con más tipos en el futuro en vez de construir un flujo de solicitud paralelo por módulo (esta Etapa 2 no le agregó tipos nuevos todavía — ninguna de sus 5 piezas lo requería).

## Decisiones confirmadas para la Etapa 3

El usuario confirmó (2026-09-11): "Confirmar egreso" se queda como recomendación, no como bloqueo. Eligió, en orden: **medicamentos conciliados**, luego **integraciones externas** — pero al confirmar que no existe ninguna API/credencial real de ningún proveedor de dispositivos, se construyó el registro manual de monitoreo en su lugar — y por último **educación personalizada**, con lo que la Etapa 3 queda completa.

## Decisión pendiente para la próxima sesión

Con la Etapa 3 completa y el paciente ya escribiendo en su diario, falta decidir qué sigue del prompt maestro: **configuración institucional/academia** (panel para que la institución ajuste catálogos/plantillas/roles propios) o **resúmenes asistidos por IA**. También sigue abierto si alguna vez aparece una API real de un proveedor de dispositivos, conectarla para poblar `home_monitoring_readings` automáticamente en vez del registro manual.
