# Complementary Material

## Final delivery checklist

- Backend branch: `feat-home-chat`.
- Frontend branch: `develop`.
- Code in the official repository.
- Sensitive variables outside the repository.
- Database migrations available.
- Fixtures documented.
- MkDocs source available at `backend-grup-6-gensync/docs/mkdocs`.
- Protected backend route: `/admin/documentacio`.

## Test users

Test users are loaded from:

```text
src/DataFixtures/AppFixtures.php
```

The detailed Catalan table is available in:

```text
docs/test-users.md
```

## Documentation placement decision

For this project, MkDocs is integrated directly into the backend because:

- It belongs to the official backend repository.
- It can be protected with `ROLE_SUPER_ADMIN`.
- It does not require a separate deployment.
- It versions next to the API it documents.

A separate documentation project would only make sense for a public or cross-service documentation portal.
