# Material complementario

## Checklist de entrega final

- Backend en la rama `feat-home-chat`.
- Frontend en la rama `develop`.
- Código en el repositorio oficial.
- Variables sensibles fuera del repositorio.
- Migraciones disponibles.
- Fixtures documentadas.
- Fuente MkDocs disponible en `backend-grup-6-gensync/docs/mkdocs`.
- Ruta backend protegida: `/admin/documentacio`.

## Usuarios de prueba

Los usuarios de prueba se cargan desde:

```text
src/DataFixtures/AppFixtures.php
```

La tabla detallada en catalán está disponible en:

```text
docs/test-users.md
```

## Decisión sobre ubicación de la documentación

Para este proyecto, MkDocs está integrado directamente en el backend porque:

- Pertenece al repositorio oficial del backend.
- Se puede proteger con `ROLE_SUPER_ADMIN`.
- No requiere un despliegue separado.
- Versiona junto a la API que documenta.

Un proyecto de documentación separado solo tendría sentido para un portal público o documentación transversal de varios servicios.
