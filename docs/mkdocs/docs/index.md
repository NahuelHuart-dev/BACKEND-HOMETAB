# HomeTab - Documentacio tecnica

HomeTab es una aplicacio web per gestionar la convivencia en llars compartides. El projecte esta desenvolupat per l'equip GenSync i combina un backend Symfony amb una aplicacio frontend Vue. Aquesta documentacio reuneix la informacio necessaria per presentar, mantenir i avaluar el projecte final.

## Abast de la documentacio

Aquesta documentacio cobreix quatre blocs demanats per al lliurament final:

- Documentacio de presentacio del producte i del treball desenvolupat.
- Documentacio tecnica de l'arquitectura, el backend, el frontend, la seguretat i el model de dades.
- Documentacio de l'API en format MkDocs.
- Material complementari: checklist, usuaris de prova i decisions tecniques.

## Ruta oficial al repositori

La documentacio MkDocs esta integrada dins del repositori del backend:

```text
backend-grup-6-gensync/docs/mkdocs
```

La ruta protegida dins del backend es:

```text
/admin/documentacio
```

Nomes els usuaris amb `ROLE_SUPER_ADMIN` poden accedir-hi. Els fitxers generats de MkDocs no es publiquen directament a `public/`, sino que Symfony els serveix des d'un controlador protegit.

## Projectes revisats

| Projecte | Ruta | Branca |
| --- | --- | --- |
| Backend | `backend-grup-6-gensync` | `feat-home-chat` |
| Frontend | `frontend-grup-6-gensync` | `develop` |
| Landing | `landing-page-grup-6-gensync` | material complementari |

## Resum executiu

HomeTab permet crear llars, convidar membres, organitzar tasques, registrar esdeveniments, controlar despeses compartides, comunicar-se per chat de casa i consultar informacio mitjancant un assistent IA orientat al modul de digitalitzacio. El backend incorpora un panell privat per a superadministradors, autenticacio amb JWT per al frontend, verificacio en dos passos opcional per correu, control d'acces per llar i eines internes de suport.

## Com generar la documentacio

```bash
cd backend-grup-6-gensync
python -m pip install -r docs/mkdocs/requirements.txt
python -m mkdocs build -f docs/mkdocs/mkdocs.yml
```

Despres del build, obrir:

```text
http://localhost:8000/admin/documentacio
```

Cal iniciar sessio al backend amb un usuari `ROLE_SUPER_ADMIN`.
