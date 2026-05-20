# HomeTab - Documentación técnica

HomeTab es una aplicación web para gestionar hogares compartidos. Centraliza flujos diarios de convivencia como tareas, eventos compartidos, gastos, balances, chat de hogar y consulta rápida de información mediante un asistente de IA.

## Ruta oficial en el repositorio

El proyecto MkDocs está integrado dentro del repositorio del backend:

```text
backend-grup-6-gensync/docs/mkdocs
```

La ruta protegida del backend es:

```text
/admin/documentacio
```

Solo los usuarios con `ROLE_SUPER_ADMIN` pueden acceder. El sitio generado no se expone directamente a través de `public/`; Symfony lo sirve mediante un controlador protegido.

## Ramas revisadas

| Proyecto | Ruta | Rama |
| --- | --- | --- |
| Backend | `backend-grup-6-gensync` | `feat-home-chat` |
| Frontend | `frontend-grup-6-gensync` | `develop` |
| Landing | `landing-page-grup-6-gensync` | material complementario de presentación |

## Alcance de la documentación

- Documentación de presentación final.
- Documentación técnica.
- Documentación de la API.
- Material complementario de entrega.
- Guía de administración backend e IA.
- Worklog técnico completo del backend.

Usa el selector de idioma de la cabecera para cambiar entre catalán, inglés y español.
