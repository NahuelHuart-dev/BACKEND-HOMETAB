# Decisions tecniques

## MkDocs dins del backend o projecte separat?

La recomanacio per a aquest lliurament es posar MkDocs directament dins del backend.

## Per que dins del backend

Avantatges:

- Queda dins del repositori oficial del backend.
- La ruta es pot protegir amb `ROLE_SUPER_ADMIN`.
- No cal desplegar un servei separat.
- La documentacio versiona al costat de l'API que descriu.
- Es mes facil indicar una ruta concreta al lliurament.

Inconvenients:

- El repositori backend conte tambe documentacio.
- Cal generar el build de MkDocs abans de servir-lo.

## Quan tindria sentit un projecte separat

Un repositori separat tindria sentit si:

- La documentacio fos publica.
- Hi hagues molts equips editant documentacio independentment del backend.
- Es desplegues a GitHub Pages, Read the Docs o un portal documental corporatiu.
- Hi hagues documentacio de molts serveis, no nomes HomeTab.

## Decisio final

Per HomeTab, la millor opcio es:

```text
backend-grup-6-gensync/docs/mkdocs
```

Servit a:

```text
/admin/documentacio
```

I protegit per:

```text
ROLE_SUPER_ADMIN
```

## Racional de seguretat

No s'ha col.locat el build de MkDocs dins de `public/`, perque aixo permetria acces directe sense passar pel sistema de rols. En canvi, Symfony serveix els fitxers generats i valida el rol abans de retornar-los.
