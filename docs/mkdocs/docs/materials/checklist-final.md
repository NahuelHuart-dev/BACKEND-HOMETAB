# Checklist final

## Codi

- Backend a la branca `feat-home-chat`.
- Frontend a la branca `develop`.
- Codi al repositori oficial.
- Variables sensibles fora del repositori.
- Migracions disponibles.
- Fixtures documentades.

## Documentacio

- Documentacio de presentacio.
- Documentacio tecnica.
- Documentacio de l'API en MkDocs.
- Documentacio de testing i qualitat en MkDocs.
- Material complementari.
- Ruta indicada: `backend-grup-6-gensync/docs/mkdocs`.
- Acces backend protegit: `/admin/documentacio`.

## Testing i qualitat

- PHPUnit configurat i funcionant al backend.
- Tests unitaris per a logica critica.
- Tests funcionals per a login, llars, permisos, tasques, despeses i esdeveniments.
- Tests funcionals ampliats per a 2FA, perfil, chat, notificacions i multimedia.
- Vitest configurat al frontend per a serveis, composables i vistes principals.
- Playwright estabilitzat amb smoke tests E2E de login, Mis Tabs, llar, tasques, despeses, calendari, notificacions, chat i multimedia.
- GitHub Actions configurat com a porta de qualitat abans de PR/deploy.
- Document global disponible a `TESTING.md`.

## Presentacio

Cal explicar explicitament:

- Que es HomeTab.
- Quin problema resol.
- Arquitectura backend/frontend.
- Funcionalitats principals.
- Seguretat i rols.
- Modul Digitalitzacio: assistent IA de HomeTab.
- Demo del flux usuari: login, llar, tasques, despeses, calendari, chat i assistent.
- Demo del backoffice superadmin.
- Mostrar els tests passant en directe o ensenyar una execucio recent en verd: backend `composer test`, frontend `npm run test:unit`, build i E2E `3 passed`.

## Validacio tecnica recomanada

```bash
php bin/console lint:yaml config
php bin/console lint:twig templates
php bin/console doctrine:schema:validate
composer test
python -m mkdocs build -f docs/mkdocs/mkdocs.yml --strict
```

Frontend:

```bash
npm run build
npm run test:unit
npm run test:coverage
npm run test:e2e
```
