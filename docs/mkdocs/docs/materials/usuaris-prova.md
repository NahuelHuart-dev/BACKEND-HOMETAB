# Usuaris de prova

Els usuaris de prova es carreguen amb fixtures. La font principal es:

```text
src/DataFixtures/AppFixtures.php
```

## Superadministrador

| Camp | Valor |
| --- | --- |
| Email | `superadmin@example.test` |
| Rol | `ROLE_SUPER_ADMIN` |
| Acces | Backend Twig, API i documentacio protegida. |

## Usuaris normals

Els usuaris normals tenen `ROLE_USER` i poden accedir al frontend Vue i a l'API de les seves llars.

La contrasenya de prova es documenta al fitxer:

```text
docs/test-users.md
```

## Notes

- Les fixtures poden esborrar dades existents quan es carreguen.
- No s'han d'utilitzar credencials de prova en produccio.
- Si es fan servir correus reals per provar 2FA, cal revisar que SMTP estigui correctament configurat.
