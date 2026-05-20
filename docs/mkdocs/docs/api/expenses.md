# Despeses

Tots els endpoints requereixen `Authorization: Bearer <token>` i que l'usuari sigui membre de la llar.

**Ruta base**: `/api/households/{homeId}/expenses`

---

## Llistar despeses

```http
GET /api/households/{homeId}/expenses
```

Retorna les despeses actives de la llar.

### Resposta `200`

```json
[
  {
    "id": 33,
    "title": "Factura electricitat",
    "description": "Mes de maig",
    "amount": "120.00",
    "category": "Llum",
    "paymentType": "shared",
    "isPaid": false,
    "dueDate": "2026-05-31",
    "periodicity": "monthly",
    "recurrenceDayOfMonth": 31,
    "recurrenceWeekday": null,
    "recurrenceTime": null,
    "paidAt": null,
    "paidBy": {
      "id": 5,
      "name": "Maria López",
      "email": "maria@example.com"
    },
    "shares": [
      {
        "userId": 5,
        "name": "Maria López",
        "email": "maria@example.com",
        "amountOwed": "60.00",
        "isPaid": false,
        "paidAt": null
      },
      {
        "userId": 7,
        "name": "Joan Puig",
        "email": "joan@example.com",
        "amountOwed": "60.00",
        "isPaid": false,
        "paidAt": null
      }
    ],
    "splitBetween": [
      { "id": 5, "name": "Maria López", "email": "maria@example.com" },
      { "id": 7, "name": "Joan Puig", "email": "joan@example.com" }
    ],
    "amountPerPerson": 60.0,
    "allParticipantsPaid": false
  }
]
```

---

## Crear una despesa

```http
POST /api/households/{homeId}/expenses
Content-Type: application/json
```

### Cos (pagament compartit)

```json
{
  "title": "Factura electricitat",
  "description": "Mes de maig",
  "amount": 120.00,
  "category": "Llum",
  "paymentType": "shared",
  "splitBetween": [5, 7],
  "isPaid": false,
  "dueDate": "2026-05-31",
  "periodicity": "monthly",
  "recurrenceDayOfMonth": 31
}
```

### Cos (pagament individual)

```json
{
  "title": "Compra setmanal",
  "amount": 85.50,
  "category": "Alimentació",
  "paymentType": "individual",
  "paidByUserId": 5,
  "isPaid": true,
  "paidAt": "2026-05-19T10:00:00"
}
```

### Camps

| Camp | Requerit | Descripció |
|---|---|---|
| `title` | Sí | Títol de la despesa |
| `amount` | Sí | Import total (decimal) |
| `category` | No | `General`, `Alimentació`, `Llum`, `Aigua`, `Internet`, `Habitatge`, etc. |
| `paymentType` | No | `shared` (defecte) o `individual` |
| `splitBetween` | Si `shared` | Array d'IDs dels participants (tots han de ser membres) |
| `paidByUserId` | Si `individual` | ID de l'usuari que ha pagat |
| `isPaid` | No | `true` o `false` |
| `dueDate` | No | Data de venciment (`YYYY-MM-DD`) |
| `periodicity` | No | `daily`, `weekly`, `monthly` o `null` |
| `recurrenceDayOfMonth` | Si `monthly` | Dia del mes (1-31) |
| `recurrenceWeekday` | Si `weekly` | Dia de la setmana (0=dilluns, 6=diumenge) |
| `recurrenceTime` | Si `daily` | Hora (`HH:MM`) |
| `sharePayments` | No | Array de `{userId, isPaid}` per marcar quotes individuals com a pagades |

### Resposta `201`

```json
{
  "message": "Despesa afegida correctament",
  "expense": { ... }
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | Títol o import buits |
| `400` | Pagador no membre de la llar |
| `400` | Participant de `splitBetween` no és membre |
| `403` | L'usuari no és membre de la llar |

---

## Editar una despesa

```http
PUT /api/households/{homeId}/expenses/{id}
PATCH /api/households/{homeId}/expenses/{id}
```

Mateixa estructura que la creació. Tots els camps opcionals. Actualitza `shares` si `splitBetween` canvia.

### Resposta `200`

```json
{
  "message": "Despesa actualitzada",
  "expense": { ... }
}
```

---

## Eliminar una despesa (borrat lògic)

```http
DELETE /api/households/{homeId}/expenses/{id}
```

Marca `isActive = false`.

### Resposta `200`

```json
{
  "message": "Despesa donada de baixa"
}
```

---

## Historial de despeses

```http
GET /api/households/{homeId}/expenses/history
```

Retorna tot l'historial de despeses de la llar, incloent-hi les inactives (esborrades lògicament).

### Resposta `200`

```json
[
  {
    "id": 33,
    "title": "Factura electricitat",
    "isActive": false,
    ...
  }
]
```

---

## Restaurar una despesa

```http
PATCH /api/households/{homeId}/expenses/{id}/restore
```

Restaura una despesa esborrada lògicament, tornant a posar `isActive = true`.

### Resposta `200`

```json
{
  "message": "Despesa restaurada"
}
```

---

## Balance per membre

```http
GET /api/households/{homeId}/expenses/balance
```

Calcula el saldo de cada membre: positiu = li deuen, negatiu = deu.

### Resposta `200`

```json
[
  {
    "userId": 5,
    "name": "Maria López",
    "email": "maria@example.com",
    "balance": 60.0
  },
  {
    "userId": 7,
    "name": "Joan Puig",
    "email": "joan@example.com",
    "balance": -60.0
  }
]
```

---

## Liquidacions recomanades

```http
GET /api/households/{homeId}/expenses/settlements
```

Algorisme greedy per calcular els pagaments mínims per equilibrar el balance.

### Resposta `200`

```json
[
  {
    "from": { "id": 7, "name": "Joan Puig", "email": "joan@example.com" },
    "to": { "id": 5, "name": "Maria López", "email": "maria@example.com" },
    "amount": 60.0
  }
]
```

---

## Exemple cURL

```bash
# Llistar despeses
curl -X GET http://localhost:8000/api/households/12/expenses \
  -H "Authorization: Bearer <token>"

# Crear despesa compartida
curl -X POST http://localhost:8000/api/households/12/expenses \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"title":"Llum","amount":120,"paymentType":"shared","splitBetween":[5,7]}'

# Balance
curl -X GET http://localhost:8000/api/households/12/expenses/balance \
  -H "Authorization: Bearer <token>"

# Liquidacions
curl -X GET http://localhost:8000/api/households/12/expenses/settlements \
  -H "Authorization: Bearer <token>"
```

---

## Relació amb el frontend

| Vista | Ús |
|---|---|
| `ExpensesTab.vue` | CRUD complet, balance, liquidació, estat de quotes |
| `DashboardTab.vue` | Total de despeses del mes al resum |
