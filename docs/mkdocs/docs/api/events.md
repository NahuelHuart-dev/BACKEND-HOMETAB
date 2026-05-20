# Esdeveniments

Tots els endpoints requereixen `Authorization: Bearer <token>` i ser membre de la llar.

**Ruta base**: `/api/households/{homeId}/events`

---

## Llistar esdeveniments

```http
GET /api/households/{homeId}/events
```

Retorna els events actius de la llar.

### Resposta `200`

```json
[
  {
    "id": 21,
    "title": "Sopar de festa major",
    "description": "Sopar a la terrassa de tots els veïns",
    "startDate": "2026-06-15T21:00:00+02:00",
    "endDate": "2026-06-16T01:00:00+02:00",
    "location": "Terrassa del pis",
    "color": "#f59e0b",
    "isAllDay": false,
    "createdBy": {
      "id": 5,
      "name": "Maria López",
      "email": "maria@example.com"
    },
    "participants": [
      { "id": 5, "name": "Maria López", "email": "maria@example.com" },
      { "id": 7, "name": "Joan Puig", "email": "joan@example.com" }
    ]
  }
]
```

---

## Crear un esdeveniment

```http
POST /api/households/{homeId}/events
Content-Type: application/json
```

### Cos

```json
{
  "title": "Sopar de festa major",
  "description": "Sopar a la terrassa",
  "startDate": "2026-06-15T21:00:00+02:00",
  "endDate": "2026-06-16T01:00:00+02:00",
  "location": "Terrassa del pis",
  "color": "#f59e0b",
  "isAllDay": false,
  "participants": [5, 7]
}
```

| Camp | Requerit | Descripció |
|---|---|---|
| `title` | Sí | Títol de l'event |
| `startDate` | Sí | Data i hora d'inici (ISO 8601) |
| `description` | No | Descripció de l'event |
| `endDate` | No | Data i hora de fi |
| `location` | No | Ubicació |
| `color` | No | Color hexadecimal (defecte: `#3b82f6`) |
| `isAllDay` | No | `true` si és un event de tot el dia |
| `participants` | No | Array d'IDs de membres de la llar |

### Resposta `201`

```json
{
  "message": "Esdeveniment creat correctament",
  "event": { ... }
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | Títol o data d'inici buits |
| `400` | Participant no membre de la llar |
| `403` | L'usuari no és membre de la llar |

---

## Editar un esdeveniment

```http
PUT /api/households/{homeId}/events/{id}
PATCH /api/households/{homeId}/events/{id}
Content-Type: application/json
```

Tots els camps opcionals. Si `participants` s'envia, reemplaça la llista completa.

### Resposta `200`

```json
{
  "message": "Esdeveniment actualitzat",
  "event": { ... }
}
```

---

## Eliminar un esdeveniment (borrat lògic)

```http
DELETE /api/households/{homeId}/events/{id}
```

Marca `isActive = false`.

### Resposta `200`

```json
{
  "message": "Esdeveniment donat de baixa"
}
```

---

## Exemple cURL

```bash
# Crear event
curl -X POST http://localhost:8000/api/households/12/events \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Reunió de convivència",
    "startDate": "2026-05-25T19:00:00+02:00",
    "color": "#10b981",
    "participants": [5, 7]
  }'

# Eliminar event
curl -X DELETE http://localhost:8000/api/households/12/events/21 \
  -H "Authorization: Bearer <token>"
```

---

## Relació amb el frontend

| Vista | Ús |
|---|---|
| `CalendarTab.vue` | Vista mensual, creació, edició i eliminació d'events |
| `DashboardTab.vue` | Pròxims 3 events al resum de la llar |
