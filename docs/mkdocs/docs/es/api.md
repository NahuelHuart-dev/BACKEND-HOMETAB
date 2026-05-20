# Documentación API

## Base URL

Desarrollo local:

```text
http://localhost:8000/api
```

El frontend Vue apunta automáticamente a esta URL cuando se ejecuta en `localhost`.

## Autenticación

La mayoría de endpoints requieren:

```http
Authorization: Bearer <token>
```

Endpoints públicos:

- `POST /api/login_check`
- `POST /api/login/verify`
- `POST /api/register`

## Recursos principales

| Recurso | Prefijo |
| --- | --- |
| Auth | `/api/login_check`, `/api/login/verify`, `/api/2fa/*` |
| Perfil | `/api/profile` |
| Hogares | `/api/households` |
| Dashboard | `/api/households/{homeId}/dashboard` |
| Tareas | `/api/households/{homeId}/tasks` |
| Gastos | `/api/households/{homeId}/expenses` |
| Eventos | `/api/households/{homeId}/events` |
| Chat de hogar | `/api/households/{homeId}/chat` |
| Asistente IA | `/api/assistant/chat` |

## Asistente IA

```http
POST /api/assistant/chat
```

Request:

```json
{
  "message": "¿Qué pagos tengo pendientes?"
}
```

Response:

```json
{
  "answer": "Tienes 2 pagos pendientes...",
  "sources": ["payments", "expenses"]
}
```

El asistente es de solo lectura y está limitado a los hogares del usuario autenticado.
