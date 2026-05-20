# Documentacio MkDocs de HomeTab

Aquest directori conte el projecte MkDocs oficial del backend.

## Build local

```bash
cd backend-grup-6-gensync
python -m pip install -r docs/mkdocs/requirements.txt
python -m mkdocs build -f docs/mkdocs/mkdocs.yml
```

La sortida es genera a:

```text
docs/mkdocs/site
```

El backend Symfony la serveix a:

```text
/admin/documentacio
```

L'acces esta protegit per `ROLE_SUPER_ADMIN` mitjancant `AdminDocumentationController`.
