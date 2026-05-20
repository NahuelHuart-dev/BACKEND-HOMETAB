# Registre

## Endpoint: registrar un nou usuari

```http
POST /api/register
Content-Type: application/json
```

**No requereix autenticació.** Accés públic.

### Cos

```json
{
  "email": "nou.usuari@example.com",
  "password": "Contrasenya123!",
  "firstName": "Maria",
  "lastName": "García"
}
```

### Camps requerits

| Camp | Tipus | Restriccions |
|---|---|---|
| `email` | string | Email vàlid, únic al sistema |
| `password` | string | Mínim 8 caràcters |
| `firstName` | string | Mínim 2, màxim 100 caràcters |
| `lastName` | string | Mínim 2, màxim 100 caràcters |

### Resposta d'èxit `201`

```json
{
  "message": "Usuari registrat correctament.",
  "user": {
    "id": 42,
    "email": "nou.usuari@example.com",
    "firstName": "Maria",
    "lastName": "García",
    "fullName": "Maria García",
    "roles": ["ROLE_USER"]
  }
}
```

### Errors comuns

| Codi | Motiu |
|---|---|
| `400` | Camps buits, email invàlid o contrasenya massa curta |
| `409` | L'email ja existeix al sistema |
| `422` | Validació fallida (firstName/lastName massa curts) |

### Exemple cURL

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "nou.usuari@example.com",
    "password": "Contrasenya123!",
    "firstName": "Maria",
    "lastName": "García"
  }'
```

### Controlador

`ApiRegistrationController` a `src/Controller/Api/ApiRegistrationController.php`.

Utilitza el `UserPasswordHasherInterface` de Symfony per hashear la contrasenya amb bcrypt/argon2 automàticament.

### Relació amb el frontend

| Vista | Ús |
|---|---|
| `RegisterView.vue` | Formulari de registre amb validació i feedback d'errors |

### Notes

- El nou usuari rep el rol `ROLE_USER` automàticament.
- La data d'alta (`joinedAt`) es registra automàticament.
- El compte queda actiu (`isActive = true`) des del moment del registre.
- No hi ha verificació d'email (opcional, no implementada al MVP).
