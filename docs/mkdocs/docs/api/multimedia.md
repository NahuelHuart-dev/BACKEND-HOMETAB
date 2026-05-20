# Multimèdia

## Endpoint: llistar playlists

```http
GET /api/households/{homeId}/multimedia/playlists
Authorization: Bearer <token>
```

### Resposta d'èxit `200`

```json
{
  "playlists": [
    {
      "id": 1,
      "name": "Música de cuinar",
      "createdAt": "2026-05-01T18:30:00+02:00",
      "createdBy": {
        "id": 5,
        "fullName": "Maria López",
        "avatar": "/uploads/avatars/abc123.jpg",
        "avatarIcon": "pi-user"
      },
      "videos": [
        {
          "id": 3,
          "youtubeId": "dQw4w9WgXcW",
          "title": "Rick Astley - Never Gonna Give You Up",
          "thumbnailUrl": "https://i.ytimg.com/vi/dQw4w9WgXcW/hqdefault.jpg",
          "channelTitle": "Rick Astley",
          "url": "https://www.youtube.com/watch?v=dQw4w9WgXcW",
          "embedUrl": "https://www.youtube.com/embed/dQw4w9WgXcW",
          "createdAt": "2026-05-01T18:35:00+02:00",
          "addedBy": {
            "id": 5,
            "fullName": "Maria López",
            "avatar": null,
            "avatarIcon": "pi-user"
          }
        }
      ]
    }
  ]
}
```

### Errors

| Codi | Motiu |
|---|---|
| `403` | L'usuari no és membre de la llar |

---

## Endpoint: crear playlist

```http
POST /api/households/{homeId}/multimedia/playlists
Authorization: Bearer <token>
Content-Type: application/json
```

### Cos

```json
{
  "name": "Nom de la playlist"
}
```

**Restriccions**: nom no pot estar buit i no pot superar 120 caràcters.

### Resposta d'èxit `201`

```json
{
  "message": "Playlist creada correctamente.",
  "playlist": {
    "id": 2,
    "name": "Nom de la playlist",
    "createdAt": "2026-05-19T16:00:00+02:00",
    "createdBy": { "id": 5, "fullName": "Maria López", "avatar": null, "avatarIcon": "pi-user" },
    "videos": []
  }
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | Nom buit o supera 120 caràcters |
| `403` | L'usuari no és membre de la llar |

---

## Endpoint: cercar vídeos de YouTube

```http
GET /api/households/{homeId}/multimedia/search?q=paraula+clau
Authorization: Bearer <token>
```

### Paràmetres de query

| Paràmetre | Requerit | Descripció |
|---|---|---|
| `q` | Sí | Consulta de cerca (mínim 2 caràcters) |

### Resposta d'èxit `200`

```json
{
  "videos": [
    {
      "youtubeId": "dQw4w9WgXcW",
      "title": "Rick Astley - Never Gonna Give You Up",
      "thumbnailUrl": "https://i.ytimg.com/vi/dQw4w9WgXcW/hqdefault.jpg",
      "channelTitle": "Rick Astley"
    }
  ]
}
```

Retorna fins a 5 resultats per cerca. Requereix `YOUTUBE_API_KEY` configurat.

### Errors

| Codi | Motiu |
|---|---|
| `400` | Consulta massa curta (< 2 caràcters) |
| `403` | L'usuari no és membre de la llar |
| `503` | Error de l'API de YouTube (quota exhaurida o clau invàlida) |

---

## Endpoint: afegir vídeo a una playlist

```http
POST /api/households/{homeId}/multimedia/playlists/{playlistId}/videos
Authorization: Bearer <token>
Content-Type: application/json
```

### Cos (des dels resultats de cerca)

```json
{
  "youtubeId": "dQw4w9WgXcW",
  "title": "Rick Astley - Never Gonna Give You Up",
  "thumbnailUrl": "https://i.ytimg.com/vi/dQw4w9WgXcW/hqdefault.jpg",
  "channelTitle": "Rick Astley"
}
```

### Cos alternatiu (per URL directa)

```json
{
  "url": "https://www.youtube.com/watch?v=dQw4w9WgXcW"
}
```

### Resposta d'èxit `201`

```json
{
  "message": "Video añadido a la playlist.",
  "video": {
    "id": 10,
    "youtubeId": "dQw4w9WgXcW",
    "title": "Rick Astley - Never Gonna Give You Up",
    "thumbnailUrl": "https://i.ytimg.com/vi/dQw4w9WgXcW/hqdefault.jpg",
    "channelTitle": "Rick Astley",
    "url": "https://www.youtube.com/watch?v=dQw4w9WgXcW",
    "embedUrl": "https://www.youtube.com/embed/dQw4w9WgXcW",
    "createdAt": "2026-05-19T16:05:00+02:00",
    "addedBy": { "id": 5, "fullName": "Maria López", "avatar": null, "avatarIcon": "pi-user" }
  }
}
```

### Errors

| Codi | Motiu |
|---|---|
| `400` | No s'ha pogut reconèixer el vídeo de YouTube (ID invàlid) |
| `403` | L'usuari no és membre de la llar |
| `404` | Playlist no trobada o no pertany a la llar |

---

## Exemple cURL

```bash
# Llistar playlists de la llar 12
curl -X GET http://localhost:8000/api/households/12/multimedia/playlists \
  -H "Authorization: Bearer <token>"

# Crear playlist
curl -X POST http://localhost:8000/api/households/12/multimedia/playlists \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"name": "Playlist de divendres"}'

# Cercar a YouTube
curl -X GET "http://localhost:8000/api/households/12/multimedia/search?q=lo-fi+study" \
  -H "Authorization: Bearer <token>"

# Afegir vídeo per URL
curl -X POST http://localhost:8000/api/households/12/multimedia/playlists/1/videos \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://www.youtube.com/watch?v=dQw4w9WgXcW"}'
```

---

## Relació amb el frontend

| Component | Ús |
|---|---|
| `MultimediaTab.vue` | Renderitza playlists, creació, cerca i reproducció |
| `YouTubeVideoService` (backend) | Cerca via YouTube Data API v3 i extracció d'ID des d'URL |
