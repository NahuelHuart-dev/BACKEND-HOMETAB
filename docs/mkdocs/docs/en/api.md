# API Documentation

## Base URL

Local development:

```text
http://localhost:8000/api
```

The Vue frontend automatically targets this URL when running on `localhost`.

## Authentication

Most endpoints require:

```http
Authorization: Bearer <token>
```

Public endpoints:

- `POST /api/login_check`
- `POST /api/login/verify`
- `POST /api/register`

## Main resources

| Resource | Prefix |
| --- | --- |
| Auth | `/api/login_check`, `/api/login/verify`, `/api/2fa/*` |
| Profile | `/api/profile` |
| Households | `/api/households` |
| Dashboard | `/api/households/{homeId}/dashboard` |
| Tasks | `/api/households/{homeId}/tasks` |
| Expenses | `/api/households/{homeId}/expenses` |
| Events | `/api/households/{homeId}/events` |
| Household chat | `/api/households/{homeId}/chat` |
| AI assistant | `/api/assistant/chat` |

## AI assistant

```http
POST /api/assistant/chat
```

Request:

```json
{
  "message": "Which payments are pending?"
}
```

Response:

```json
{
  "answer": "You have 2 pending payments...",
  "sources": ["payments", "expenses"]
}
```

The assistant is read-only and scoped to the authenticated user's households.
