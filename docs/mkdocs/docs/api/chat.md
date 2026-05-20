# Chat de llar

Prefix:

```text
/api/households/{homeId}/chat
```

## Llistar missatges

```http
GET /api/households/{homeId}/chat/messages
```

### Query params

| Parametre | Descripcio |
| --- | --- |
| `limit` | Nombre de missatges recents. Per defecte 50. |
| `afterId` | Retorna missatges posteriors a un id. |
| `beforeId` | Retorna missatges anteriors a un id. |

### Resposta

```json
[
  {
    "id": 10,
    "content": "He comprat pa.",
    "createdAt": "2026-05-14T12:30:00+02:00",
    "editedAt": null,
    "sender": {
      "id": 2,
      "firstName": "Maria",
      "lastName": "Garcia",
      "fullName": "Maria Garcia",
      "avatar": null,
      "avatarIcon": "pi-user"
    }
  }
]
```

## Enviar missatge

```http
POST /api/households/{homeId}/chat/messages
```

### Cos

```json
{
  "content": "He comprat pa."
}
```

El contingut no pot estar buit ni superar 1000 caracters.

## Marcar escrivint

```http
POST /api/households/{homeId}/chat/typing
```

### Resposta

```json
{
  "typing": true
}
```

## Consultar qui escriu

```http
GET /api/households/{homeId}/chat/typing
```

### Resposta

```json
{
  "typing": [
    {
      "id": 2,
      "fullName": "Maria Garcia"
    }
  ]
}
```

## Editar missatge

```http
PATCH /api/households/{homeId}/chat/messages/{id}
PUT /api/households/{homeId}/chat/messages/{id}
```

Nomes l'emissor pot editar el seu missatge.

## Esborrar missatge

```http
DELETE /api/households/{homeId}/chat/messages/{id}
```

Nomes l'emissor pot esborrar el seu missatge. El borrat es logic.

## Endpoints de chat Twig

El backend Twig tambe te endpoints sota `/chat-widget` per al widget de sessio web. La versio principal per al frontend Vue es la documentada sota `/api/households/{homeId}/chat`.
