# Guía de administración backend e IA

Esta guía resume el trabajo realizado en el backend Symfony para estudiar la arquitectura sin tener que saltar entre muchos archivos.

## Objetivo general

El backend cubre:

- Administración global para `ROLE_SUPER_ADMIN`.
- Gestión completa de hogares, usuarios, tareas, eventos, calendario, configuración e invitaciones mediante vistas Twig.
- Gastos compartidos con participantes, estado de pago por usuario y periodicidad.
- Chat de hogar con auditoría administrativa.
- Asistente IA funcional para el módulo de digitalización.
- IA SQL con OpenRouter para soporte de base de datos del superadministrador.
- Documentación MkDocs servida desde una ruta privada del backend.

## Seguridad y acceso

Archivo principal:

```text
config/packages/security.yaml
```

Puntos clave:

- `/api/login_check` realiza login manual para poder controlar el 2FA antes de emitir JWT.
- `/api` exige JWT y `ROLE_USER`.
- `/admin` exige `ROLE_SUPER_ADMIN`.
- El firewall `main` utiliza `App\Security\LoginFormAuthenticator` para el login web de Symfony.
- `ROLE_SUPER_ADMIN` hereda `ROLE_ADMIN` y `ROLE_USER`.

El frontend Vue entra por API/JWT. El backend Twig entra por sesión web y solo el superadministrador puede acceder.

## Controlador de administración

Archivo principal:

```text
src/Controller/twig/AdminController.php
```

Rutas importantes:

- `GET /admin/dashboard`: lista todos los hogares.
- `POST /admin/households/create`: crea un hogar y asigna el superadministrador como propietario.
- `GET /admin/households/{id}/{section}`: vista principal del hogar.
- `POST /admin/households/{id}/invite-code`: cambia el código de invitación.
- `POST /admin/households/{id}/expenses/save/{expenseId?}`: crea o edita gastos.
- `POST /admin/households/{id}/tasks/save/{taskId?}`: crea o edita tareas.
- `POST /admin/households/{id}/events/save/{eventId?}`: crea o edita eventos.
- `GET /admin/users`: lista usuarios.

Métodos internos importantes:

- `userBelongsToHousehold()`
- `selectedHouseholdUsers()`
- `replaceExpenseShares()`
- `isValidRecurrence()`
- `buildCalendarWeeks()`
- `generateInviteCode()`
- `storeAvatarUpload()`
- `storeAvatarCrop()`

## Vistas Twig

Directorio principal:

```text
templates/admin
```

Plantillas importantes:

- `dashboard.html.twig`
- `household.html.twig`
- `users.html.twig`
- `records.html.twig`
- `chat.html.twig`
- `sql.html.twig`
- `documentation_missing.html.twig`

`household.html.twig` usa macros de gastos, tareas y eventos para evitar repetir formularios modales.

## Gastos compartidos

Entidades principales:

```text
src/Entity/Expense.php
src/Entity/ExpenseShare.php
```

`Expense` guarda el gasto global. `ExpenseShare` guarda la parte individual de cada participante, el importe debido, el estado de pago y la fecha de pago.

Flujo de guardado:

1. El administrador selecciona pagador, tipo de pago y participantes.
2. Los gastos individuales solo incluyen al pagador.
3. Los gastos compartidos incluyen a los participantes seleccionados.
4. `replaceExpenseShares()` reconstruye las cuotas.
5. `paidShares[]` marca quién ya ha pagado.

## Calendario

El calendario vive en:

```text
templates/admin/household.html.twig
```

Se construye con `AdminController::buildCalendarWeeks()` y soporta navegación mensual, creación de eventos desde un día seleccionado y edición de eventos.

## Perfil y avatares

El perfil permite fotografía o icono. Las imágenes se recortan en el navegador con `canvas`, se envían en base64 y el backend las valida antes de guardarlas en:

```text
public/uploads/avatars
```

## IA SQL y asistente de digitalización

Hay dos funcionalidades relacionadas con IA:

| Funcionalidad | Usuario | Finalidad |
| --- | --- | --- |
| Asistente HomeTab | Usuario autenticado | Consultar datos del hogar en lenguaje natural. |
| IA SQL | Admin/superadmin | Generar SQL de lectura para soporte interno. |

Endpoint del asistente de digitalización:

```text
POST /api/assistant/chat
```

Puede responder sobre tareas, eventos, pagos pendientes, balances y resúmenes del hogar.
