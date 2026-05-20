# Tasques

Tots els endpoints requereixen `Authorization: Bearer <token>` i que l'usuari sigui membre de la llar `{homeId}`.

**Ruta base**: `/api/households/{homeId}/tasks`

---

## Llistar tasques d'una llar

```http
GET /api/households/{homeId}/tasks
```

Retorna les tasques actives (`isActive = true`) de la llar.

### Resposta `200`

```json
[
  {
    "id": 88,
    "title": "Netejar cuina",
    "description": "Fregar el terra i netejar els fogons",
    "completed": false,
    "dueDate": "2026-05-20",
    "priority": "Alta",
    "periodicity": "Setmanal",
    "category": "Neteja",
    "assignedTo": {
      "id": 5,
      "firstName": "Maria",
      "lastName": "López"
    }
  }
]
```

---

## Crear una tasca

```http
POST /api/households/{homeId}/tasks
Content-Type: application/json
```

### Cos

```json
{
  "title": "Netejar cuina",
  "description": "Fregar el terra i netejar els fogons",
  "dueDate": "2026-05-20",
  "priority": "Alta",
  "periodicity": "Setmanal",
  "category": "Neteja",
  "assignedTo": 5
}
```

| Camp | Requerit | Descripció |
|---|---|---|
| `title` | Sí | Títol de la tasca |
| `description` | No | Descripció detallada |
| `dueDate` | No | Data de venciment (format `YYYY-MM-DD`) |
| `priority` | No | `Alta`, `Mitja`, `Baixa` (defecte: `Mitja`) |
| `periodicity` | No | `Única`, `Diària`, `Setmanal`, `Mensual` |
| `category` | No | Categoria lliure (ex: `Neteja`, `Cuina`, `Compres`) |
| `assignedTo` | No | ID d'un membre de la llar. Si no s'especifica, s'assigna al creador |

### Resposta `201`

```json
{
  "message": "Tasca creada correctament",
  "task": {
    "id": 88,
    "title": "Netejar cuina",
    "completed": false,
    "dueDate": "2026-05-20",
    "priority": "Alta",
    "periodicity": "Setmanal",
    "category": "Neteja",
    "assignedTo": { "id": 5, "firstName": "Maria", "lastName": "López" }
  }
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | Títol buit |
| `403` | L'usuari no és membre de la llar |

---

## Editar una tasca

```http
PUT /api/households/{homeId}/tasks/{id}
PATCH /api/households/{homeId}/tasks/{id}
Content-Type: application/json
```

Tots els camps del cos són opcionals. Només s'actualitza el que s'envia.

### Cos

```json
{
  "title": "Nou títol",
  "completed": true,
  "dueDate": "2026-05-25",
  "assignedTo": 7,
  "priority": "Baixa"
}
```

### Resposta `200`

```json
{
  "message": "Tasca actualitzada",
  "task": { ... }
}
```

### Errors

| Codi | Motiu |
|---|---|
| `403` | L'usuari no és membre de la llar |
| `404` | Tasca no trobada a la llar |

---

## Eliminar una tasca (borrat lògic)

```http
DELETE /api/households/{homeId}/tasks/{id}
```

Marca la tasca com a inactiva (`isActive = false`). No es destrueix físicament.

### Resposta `200`

```json
{
  "message": "Tasca donada de baixa"
}
```

### Errors

| Codi | Motiu |
|---|---|
| `403` | L'usuari no és membre de la llar |
| `404` | Tasca no trobada |



## Exemple cURL

```bash
# Llistar tasques de la llar 12
curl -X GET http://localhost:8000/api/households/12/tasks \
  -H "Authorization: Bearer <token>"

# Crear tasca
curl -X POST http://localhost:8000/api/households/12/tasks \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Netejar cuina",
    "priority": "Alta",
    "dueDate": "2026-05-20",
    "assignedTo": 5
  }'

# Marcar com a completada
curl -X PATCH http://localhost:8000/api/households/12/tasks/88 \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"completed": true}'

# Eliminar
curl -X DELETE http://localhost:8000/api/households/12/tasks/88 \
  -H "Authorization: Bearer <token>"
```

---

## Relació amb el frontend

| Vista | Ús |
|---|---|
| `TasksTab.vue` | Gestió completa de tasques: CRUD, filtres, marcatge |
| `DashboardTab.vue` | Mostra les últimes tasques pendents del resum |
