# API - Guia general

## Base URL

En local:

```text
http://localhost:8000/api
```

El frontend Vue utilitza aquesta URL automaticament quan corre a `localhost`.

En entorns DEV o PROD, la URL final depen del domini configurat al desplegament. No s'ha de hardcodejar al codi: el frontend usa `VITE_API_URL` quan existeix.

## Swagger vs MkDocs

HomeTab te dues capes de documentacio d'API:

| Eina | Ruta | Finalitat |
| --- | --- | --- |
| Swagger / OpenAPI | `/api/doc` i `/api/doc.json` | Contracte viu generat des de les rutes Symfony reals. Serveix per inspeccionar metodes, paths i autenticacio. |
| MkDocs | `/admin/documentacio` | Explicacio humana: context, exemples JSON, fluxos, decisions, tests i relacio amb frontend. |

Swagger es public en local i DEV per facilitar proves. En produccio es recomana limitar-lo per entorn, per xarxa o per rol si exposa massa informacio interna. La UI es configura amb assets `bundle` perquè carregui CSS/JS des de `/bundles/nelmioapidoc` i no depengui del CDN de Swagger.

## Format

La majoria d'endpoints consumeixen i retornen JSON:

```http
Content-Type: application/json
Accept: application/json
```

## Autenticacio

Excepte login, verificacio 2FA, registre, feedback, recuperacio de contrasenya i Swagger, l'API requereix JWT:

```http
Authorization: Bearer <token>
```

## Codis d'estat habituals

| Codi | Significat |
| --- | --- |
| `200` | Operacio correcta. |
| `201` | Recurs creat. |
| `400` | Peticio invalida. |
| `401` | No autenticat. |
| `403` | Sense permis o fora de la llar. |
| `404` | Recurs no trobat. |
| `409` | Conflicte de dades. |
| `422` | Validacio semantica fallida. |
| `500` | Error intern del backend. |

## Recursos principals

| Recurs | Prefix |
| --- | --- |
| Auth | `/api/login_check`, `/api/login/verify`, `/api/2fa/*` |
| Registre | `/api/register` |
| Password reset | `/api/password-reset/*` |
| Perfil | `/api/profile` |
| Llars | `/api/households` |
| Dashboard | `/api/households/{homeId}/dashboard` |
| Tasques | `/api/households/{homeId}/tasks` |
| Despeses | `/api/households/{homeId}/expenses` |
| Esdeveniments | `/api/households/{homeId}/events` |
| Chat | `/api/households/{homeId}/chat` |
| Chat widget | `/api/chat/households` |
| Notificacions | `/api/notifications` |
| Multimedia | `/api/households/{homeId}/multimedia` |
| Assistent IA | `/api/assistant/chat` |
| Feedback | `/api/feedback` |
| Swagger | `/api/doc`, `/api/doc.json` |

## Regla d'acces per llar

Els endpoints amb `{homeId}` nomes retornen dades si l'usuari autenticat es membre de la llar o, en alguns casos, si es `ROLE_SUPER_ADMIN`.
