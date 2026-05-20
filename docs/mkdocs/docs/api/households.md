# Llars

Tots els endpoints de llars requereixen `Authorization: Bearer <token>` i `ROLE_USER`.

---

## Llistar les meves llars

```http
GET /api/households
```

Retorna les llars on l'usuari és membre, ordenades per `sortOrder`. Si l'usuari és `ROLE_SUPER_ADMIN`, retorna totes les llars del sistema.

### Resposta `200`

```json
[
  {
    "id": 12,
    "name": "Pis Gràcia",
    "role": "owner",
    "inviteCode": "AB12CD",
    "avatar": "/uploads/households/casa.jpg",
    "avatarIcon": "pi-home",
    "sortOrder": 1
  },
  {
    "id": 15,
    "name": "Casa de la platja",
    "role": "member",
    "inviteCode": "XY99ZZ",
    "avatar": null,
    "avatarIcon": "pi-sun",
    "sortOrder": 2
  }
]
```

---

## Crear una nova llar

```http
POST /api/households/new
Content-Type: application/json
```

### Cos

```json
{
  "name": "Pis Gràcia",
  "avatarIcon": "pi-home",
  "avatarCropData": "data:image/jpeg;base64,..."
}
```

| Camp | Requerit | Descripció |
|---|---|---|
| `name` | Sí | Nom de la llar |
| `avatarIcon` | No | Icona PrimeIcons (ex: `pi-home`, `pi-sun`, `pi-star`) |
| `avatarCropData` | No | Imatge base64 retallada per a l'avatar |

### Resposta `201`

```json
{
  "message": "Casa creada correctamente.",
  "household": {
    "id": 12,
    "name": "Pis Gràcia",
    "role": "owner",
    "inviteCode": "AB12CD",
    "avatar": null,
    "avatarIcon": "pi-home",
    "sortOrder": 1
  }
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | Nom buit |
| `400` | Error processant la imatge |

---

## Unir-se a una llar

```http
POST /api/households/join
Content-Type: application/json
```

### Cos

```json
{
  "code": "AB12CD"
}
```

### Resposta `200`

```json
{
  "message": "Te has unido a Pis Gràcia correctamente."
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | Codi buit |
| `404` | Codi d'invitació invàlid |
| `409` | L'usuari ja és membre d'aquesta llar |

---

## Actualitzar l'ordre de les llars

```http
PUT /api/households/order
Content-Type: application/json
```

### Cos

```json
{
  "orderedIds": [15, 12, 18]
}
```

Array d'IDs de llars en el nou ordre desitjat.

### Resposta `200`

```json
{
  "message": "Orden actualizado correctamente.",
  "orderedIds": [15, 12, 18]
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | L'ordre enviat no és vàlid |
| `403` | Intentant ordenar una llar on no ets membre |

---

## Veure membres d'una llar

```http
GET /api/households/{id}/members
```

### Resposta `200`

```json
{
  "household": {
    "id": 12,
    "name": "Pis Gràcia",
    "role": "owner",
    "inviteCode": "AB12CD",
    "avatar": null,
    "avatarIcon": "pi-home",
    "sortOrder": 1
  },
  "inviteCode": "AB12CD",
  "members": [
    {
      "id": 5,
      "firstName": "Maria",
      "lastName": "López",
      "email": "maria@example.com",
      "role": "owner",
      "avatar": "/uploads/avatars/maria.jpg",
      "avatarIcon": "pi-user"
    }
  ]
}
```

### Errors

| Codi | Motiu |
|---|---|
| `403` | No ets membre d'aquesta llar |
| `404` | Llar no trobada |

---

## Editar una llar

```http
PUT /api/households/{id}
PATCH /api/households/{id}
Content-Type: application/json
```

Requereix ser `owner` o `admin` de la llar.

### Cos (tots opcionals)

```json
{
  "name": "Nou nom",
  "avatarIcon": "pi-star",
  "avatarCropData": "data:image/jpeg;base64,...",
  "removeAvatar": true
}
```

### Resposta `200`

```json
{
  "message": "Casa actualizada correctamente.",
  "household": { "id": 12, "name": "Nou nom", "role": "owner", ... }
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | Nom buit o error processant imatge |
| `403` | No tens permisos per editar aquesta llar |
| `404` | Llar no trobada |

---

## Expulsar un membre

```http
DELETE /api/households/{id}/members/{userId}
```

Requereix ser `owner` o `admin`.

### Resposta `200`

```json
{
  "message": "Miembro expulsado correctamente."
}
```

### Errors

| Codi | Motiu |
|---|---|
| `403` | No tens permisos / no pots expulsar el propietari |
| `404` | Usuari no és membre de la llar |

---

## Canviar el codi d'invitació

```http
PUT /api/households/{id}/invite-code
Content-Type: application/json
```

Requereix ser `owner`.

### Cos

```json
{
  "code": "MYCODE"
}
```

El codi es converteix a majúscules automàticament.

### Resposta `200`

```json
{
  "message": "Código actualizado correctamente.",
  "inviteCode": "MYCODE"
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | Codi buit |
| `403` | Només el propietari pot canviar el codi |
| `409` | El codi ja és en ús per una altra llar |

---

## Exemple complet: crear i compartir una llar

```bash
# 1. Crear la llar
curl -X POST http://localhost:8000/api/households/new \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"name": "Pis Gràcia", "avatarIcon": "pi-home"}'

# Resposta: { "household": { "id": 12, "inviteCode": "AB12CD" } }

# 2. El company es connecta amb el codi
curl -X POST http://localhost:8000/api/households/join \
  -H "Authorization: Bearer <token-company>" \
  -H "Content-Type: application/json" \
  -d '{"code": "AB12CD"}'
```
