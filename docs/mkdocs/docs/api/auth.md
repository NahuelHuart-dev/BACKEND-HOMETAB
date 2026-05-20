# Autenticació i 2FA

## Login

```http
POST /api/login_check
Content-Type: application/json
```

**Accés públic** — no requereix JWT.

### Cos

```json
{
  "username": "usuari@example.com",
  "password": "Contrasenya123"
}
```

`username` i `email` s'accepten com a alias.

### Resposta sense 2FA

```json
{
  "token": "eyJhbGciOiJSUzI1NiJ9...",
  "twoFactorEnabled": false,
  "showTwoFactorPrompt": true,
  "user": {
    "id": 5,
    "email": "usuari@example.com",
    "firstName": "Maria",
    "lastName": "López",
    "fullName": "Maria López",
    "avatar": null,
    "avatarIcon": "pi-user",
    "roles": ["ROLE_USER"]
  }
}
```

### Resposta amb 2FA activat

```json
{
  "requiresTwoFactor": true,
  "challengeId": "a3f2b1c4-...",
  "message": "T'hem enviat un codi de verificació per correu."
}
```

### Errors

| Codi | Motiu |
|---|---|
| `401` | Credencials incorrectes o usuari inactiu |

---

## Verificar login amb 2FA

```http
POST /api/login/verify
Content-Type: application/json
```

**Accés públic.**

### Cos

```json
{
  "challengeId": "a3f2b1c4-...",
  "code": "123456"
}
```

### Resposta `200`

Retorna el mateix payload que el login sense 2FA (inclou el `token`).

### Errors

| Codi | Motiu |
|---|---|
| `400` | Codi invàlid, caducat o challengeId inexistent |

---

## Activar 2FA (inici)

```http
POST /api/2fa/enable/start
Authorization: Bearer <token>
```

Genera un repte 2FA i envia el codi per correu.

### Resposta `200`

```json
{
  "challengeId": "b5e1d2a3-...",
  "message": "T'hem enviat un codi per activar la verificació en dos passos."
}
```

Si la 2FA ja està activa:

```json
{
  "twoFactorEnabled": true,
  "message": "La verificació en dos passos ja està activa."
}
```

---

## Activar 2FA (verificació)

```http
POST /api/2fa/enable/verify
Authorization: Bearer <token>
Content-Type: application/json
```

### Cos

```json
{
  "challengeId": "b5e1d2a3-...",
  "code": "654321"
}
```

### Resposta `200`

```json
{
  "twoFactorEnabled": true,
  "showTwoFactorPrompt": false,
  "message": "Verificació en dos passos activada."
}
```

---

## Desactivar 2FA

```http
POST /api/2fa/disable
Authorization: Bearer <token>
```

### Resposta `200`

```json
{
  "twoFactorEnabled": false,
  "showTwoFactorPrompt": true,
  "message": "Verificació en dos passos desactivada."
}
```

---

## Recuperació de contrasenya — Pas 1: enviar codi

```http
POST /api/password-reset/start
Content-Type: application/json
```

**Accés públic.** Si el correu existeix i és actiu, envia un codi de verificació.

### Cos

```json
{
  "email": "usuari@example.com"
}
```

### Resposta `200`

Si el correu existeix:

```json
{
  "challengeId": "c7f8a2b1-...",
  "message": "T'hem enviat un codi per recuperar la contrasenya."
}
```

Si el correu no existeix (resposta deliberadament ambigua per seguretat):

```json
{
  "challengeId": null,
  "message": "Si el correu existeix, rebràs un codi de recuperació."
}
```

---

## Recuperació de contrasenya — Pas 2: verificar codi

```http
POST /api/password-reset/verify
Content-Type: application/json
```

### Cos

```json
{
  "challengeId": "c7f8a2b1-...",
  "code": "789012"
}
```

### Resposta `200`

```json
{
  "resetToken": "abc123def456...",
  "message": "Codi verificat. Ja pots canviar la contrasenya."
}
```

El `resetToken` és un token temporal vàlid per 15 minuts.

### Errors

| Codi | Motiu |
|---|---|
| `400` | Codi invàlid, caducat o usuari inactiu |

---

## Recuperació de contrasenya — Pas 3: nova contrasenya

```http
POST /api/password-reset/finish
Content-Type: application/json
```

### Cos

```json
{
  "resetToken": "abc123def456...",
  "password": "NovaContrasenya789!"
}
```

### Resposta `200`

```json
{
  "message": "Contrasenya actualitzada correctament."
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | Token invàlid, caducat o ja usat |
| `400` | La contrasenya té menys de 8 caràcters |

---

## Exemple cURL complet: login + 2FA

```bash
# Step 1: login
curl -X POST http://localhost:8000/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"username": "usuari@example.com", "password": "Contrasenya123"}'

# Si retorna requiresTwoFactor:
curl -X POST http://localhost:8000/api/login/verify \
  -H "Content-Type: application/json" \
  -d '{"challengeId": "a3f2b1c4-...", "code": "123456"}'

# Usar el token retornat:
curl -X GET http://localhost:8000/api/profile \
  -H "Authorization: Bearer eyJhbGciOiJSUzI1NiJ9..."
```
