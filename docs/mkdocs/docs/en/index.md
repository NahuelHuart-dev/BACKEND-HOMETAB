# HomeTab - Technical Documentation

HomeTab is a web application for managing shared households. It centralizes daily coexistence workflows such as chores, shared events, expenses, balances, household chat and quick information retrieval through an AI assistant.

## Official repository path

The MkDocs project is integrated into the backend repository:

```text
backend-grup-6-gensync/docs/mkdocs
```

The protected backend route is:

```text
/admin/documentacio
```

Only users with `ROLE_SUPER_ADMIN` can access it. The generated site is not exposed directly through `public/`; Symfony serves it through a protected controller.

## Reviewed branches

| Project | Path | Branch |
| --- | --- | --- |
| Backend | `backend-grup-6-gensync` | `feat-home-chat` |
| Frontend | `frontend-grup-6-gensync` | `develop` |
| Landing | `landing-page-grup-6-gensync` | complementary presentation material |

## Documentation scope

- Final presentation documentation.
- Technical documentation.
- API documentation.
- Complementary delivery material.
- Backend administration and AI guide.
- Full backend technical worklog.

Use the language selector in the header to switch between Catalan, English and Spanish.
