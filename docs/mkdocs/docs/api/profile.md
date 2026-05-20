# Perfil

Tots els endpoints requereixen `Authorization: Bearer <token>`.

**Ruta base**: `/api/profile`

---

## Obtenir el perfil

```http
GET /api/profile
```

Retorna les dades del perfil de l'usuari autenticat.

### Resposta `200`

```json
{
  "id": 5,
  "firstName": "Maria",
  "lastName": "López",
  "fullName": "Maria López",
  "email": "maria@example.com",
  "phoneNumber": "+34 612 345 678",
  "bio": "Estudiant de DAW i amant del cafè.",
  "avatar": "/uploads/avatars/maria-abc123.jpg",
  "avatarIcon": "pi-user",
  "roles": ["ROLE_USER"]
}
```

---

## Actualitzar el perfil

```http
POST /api/profile
Content-Type: application/json
```

Tots els camps són opcionals. Actualitza només el que s'envia.

### Cos

```json
{
  "firstName": "Maria",
  "lastName": "García",
  "email": "nou.email@example.com",
  "phoneNumber": "+34 612 345 678",
  "bio": "Nova bio.",
  "avatarIcon": "pi-star",
  "avatarCropData": "data:image/jpeg;base64,...",
  "removeAvatar": false
}
```

| Camp | Tipus | Descripció |
|---|---|---|
| `firstName` | string | Nom de l'usuari |
| `lastName` | string | Cognom de l'usuari |
| `email` | string | Nou email (no pot estar en ús per un altre usuari) |
| `phoneNumber` | string\|null | Telèfon (format lliure, o `null` per eliminar) |
| `bio` | string\|null | Biografia curta (màx. 500 caràcters, o `null` per eliminar) |
| `avatarIcon` | string | Icona PrimeIcons per a l'avatar: `pi-user`, `pi-star`, `pi-heart`, etc. |
| `avatarCropData` | string | Imatge en base64 (JPEG/PNG/WebP) per a la foto de perfil |
| `removeAvatar` | bool | Si `true`, elimina la foto i torna a l'icona |

### Canviar la contrasenya (en el mateix endpoint)

Afegeix al cos:

```json
{
  "currentPassword": "ContrasenyaActual123",
  "newPassword": "NovaContrasenya456",
  "confirmPassword": "NovaContrasenya456"
}
```

### Resposta `200`

```json
{
  "message": "Perfil actualizado.",
  "user": {
    "firstName": "Maria",
    "lastName": "García",
    "fullName": "Maria García",
    "email": "nou.email@example.com",
    "phoneNumber": "+34 612 345 678",
    "bio": "Nova bio.",
    "avatar": "/uploads/avatars/maria-abc123.jpg",
    "avatarIcon": "pi-user"
  }
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | El nou email ja és en ús per un altre usuari |
| `400` | La contrasenya actual és incorrecta |
| `400` | La nova contrasenya i la confirmació no coincideixen |
| `400` | Error processant la imatge de l'avatar |

---

## Icones d'avatar permeses (`avatarIcon`)

```
pi-user    pi-home     pi-star    pi-heart   pi-sparkles
pi-briefcase  pi-crown  pi-face-smile  pi-bolt  pi-sun
```

Qualsevol altre valor es normalitza a `pi-user`.

---

## Exemple cURL

```bash
# Llegir perfil
curl -X GET http://localhost:8000/api/profile \
  -H "Authorization: Bearer <token>"

# Actualitzar nom i bio
curl -X POST http://localhost:8000/api/profile \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"firstName": "Maria", "bio": "Nova bio."}'

# Canviar contrasenya
curl -X POST http://localhost:8000/api/profile \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "currentPassword": "ContrasenyaActual123",
    "newPassword": "NovaContrasenya456",
    "confirmPassword": "NovaContrasenya456"
  }'

# Eliminar avatar
curl -X POST http://localhost:8000/api/profile \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"removeAvatar": true, "avatarIcon": "pi-star"}'
```

---

## Relació amb el frontend

| Vista | Ús |
|---|---|
| `ProfileView.vue` | Formulari complet de perfil, avatar i seguretat |
| `appNavbar.vue` | Mostra l'avatar i el nom de l'usuari a la barra |
