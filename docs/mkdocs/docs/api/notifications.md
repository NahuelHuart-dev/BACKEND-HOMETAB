# Notificacions

## Endpoint: llistar notificacions

```http
GET /api/notifications
Authorization: Bearer <token>
```

Retorna les notificacions de l'usuari autenticat agrupades per llar, sincronitzades automàticament.

### Resposta d'èxit `200`

```json
{
  "totalUnread": 3,
  "households": [
    {
      "id": 12,
      "name": "Pis Gràcia",
      "avatar": null,
      "avatarIcon": "pi-home",
      "unreadCount": 2,
      "items": [
        {
          "id": 45,
          "type": "task_due",
          "priority": "high",
          "title": "Tasca pendent",
          "message": "La tasca 'Netejar cuina' venç avui.",
          "targetAt": "2026-05-19T00:00:00+02:00",
          "route": "/households/12/tasks",
          "targetType": "task",
          "targetId": 88,
          "read": false,
          "createdAt": "2026-05-19T08:00:00+02:00"
        }
      ]
    }
  ]
}
```

### Notes

- `NotificationService::syncForUser()` genera notificacions noves basades en l'estat actual de tasques, despeses i events.
- Es filtren les notificacions de llars on l'usuari ja no és membre.
- El polling recomanat és cada 30-60 segons.

---

## Endpoint: marcar notificació com a llegida

```http
POST /api/notifications/{id}/read
Authorization: Bearer <token>
```

### Resposta d'èxit `200`

```json
{
  "message": "Notificació llegida."
}
```

### Errors

| Codi | Motiu |
|---|---|
| `404` | La notificació no existeix o no pertany a l'usuari autenticat |

---

## Endpoint: marcar totes com a llegides

```http
POST /api/notifications/read-all
Authorization: Bearer <token>
```

Marca com a llegides totes les notificacions actives de l'usuari.

### Resposta d'èxit `200`

```json
{
  "message": "Notificacions llegides."
}
```

---

## Tipus de notificació (`type`)

| Tipus | Descripció |
|---|---|
| `task_due` | Tasca propera al venciment o vencuda |
| `expense_pending` | Despesa pendent de pagament |
| `event_upcoming` | Esdeveniment pròxim |
| `chat_message` | Missatge nou al xat de llar |
| `member_joined` | Nou membre s'ha unit a la llar |

## Prioritats (`priority`)

| Prioritat | Quan s'usa |
|---|---|
| `high` | Venciment avui o passat, import alt |
| `medium` | Venciment proper, event properament |
| `low` | Informació general |

---

## Exemple cURL

```bash
# Llistar notificacions
curl -X GET http://localhost:8000/api/notifications \
  -H "Authorization: Bearer <token>"

# Marcar notificació 45 com a llegida
curl -X POST http://localhost:8000/api/notifications/45/read \
  -H "Authorization: Bearer <token>"

# Marcar totes com a llegides
curl -X POST http://localhost:8000/api/notifications/read-all \
  -H "Authorization: Bearer <token>"
```

---

## Relació amb el frontend

| Component/Composable | Ús |
|---|---|
| `useNotifications.js` | Fetch, polling, mark read, comptador |
| `HouseNotificationsWidget.vue` | Renderitzat del widget de campana |
| `appNavbar.vue` | Badge numèric de no llegides |
