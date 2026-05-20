# Documentacio de presentacio

## Identitat del projecte

**HomeTab** es una eina digital per centralitzar la gestio diaria d'una llar compartida. El producte neix per resoldre problemes habituals de convivencia: repartiment de tasques, calendari comu, control de despeses, comunicacio entre membres i consulta rapida de l'estat de la casa.

## Problema que resol

En una llar compartida sovint hi ha informacio dispersa en missatges, notes, fulls de calcul o converses informals. Aixo provoca oblits, despeses poc clares, tasques sense responsable i poca visibilitat sobre el que esta pendent. HomeTab concentra aquests fluxos en una unica aplicacio.

## Solucio proposada

HomeTab ofereix:

- Gestio de llars amb codis d'invitacio.
- Tasques assignables a membres.
- Calendari d'esdeveniments compartits.
- Despeses amb repartiment entre participants.
- Calcul de balances i compensacions.
- Chat intern per llar.
- Perfil d'usuari amb avatar o fotografia.
- Assistent IA per consultar dades de la llar en llenguatge natural.
- Backoffice privat per a superadministradors.

## Que s'ha desenvolupat dins del projecte

Durant el projecte s'ha desenvolupat una aplicacio completa amb dues superficies principals:

| Superficie | Descripcio |
| --- | --- |
| Frontend Vue | Experiencia principal d'usuari, amb landing, login, registre, panell de llars i pestanyes funcionals. |
| Backend Symfony | API JSON, autenticacio, persistencia, panell d'administracio, eines internes i control de permisos. |

També s'ha desenvolupat una landing page complementaria per explicar el producte i facilitar la presentacio.

## Modul Digitalitzacio

El modul de digitalitzacio correspon a l'assistent IA implementat al backend i exposat al frontend mitjancant l'endpoint:

```text
POST /api/assistant/chat
```

Aquest modul permet que l'usuari faci consultes en llenguatge natural sobre les seves dades de HomeTab. L'assistent pot respondre sobre:

- Tasques pendents.
- Esdeveniments propers.
- Pagaments compartits pendents.
- Balances entre membres.
- Resum general de la llar.

La implementacio actual no modifica dades. Es una primera versio segura, de consulta, orientada a digitalitzar l'acces a la informacio de la llar. En comptes d'obligar l'usuari a navegar manualment per cada pestanya, l'assistent sintetitza la informacio rellevant.

## Actors principals

| Actor | Capacitats |
| --- | --- |
| Usuari registrat | Gestiona les seves llars, tasques, esdeveniments, despeses, perfil i chat. |
| Propietari de llar | Pot administrar membres i codi d'invitacio de la seva llar. |
| Superadministrador | Accedeix al backend privat, administra dades globals, audita chats i consulta la documentacio. |

## Valor diferencial

El valor de HomeTab no es limita a tenir formularis CRUD. El producte integra:

- Una experiencia frontend usable i visual.
- Una API separada i reutilitzable.
- Control de seguretat per JWT i rols.
- Funcionalitats reals de convivencia.
- IA aplicada a dades internes del projecte.
- Documentacio tecnica professional integrada al backend.

## Estat final del lliurament

El codi ha d'estar al repositori oficial. La documentacio tecnica i d'API queda ubicada a:

```text
backend-grup-6-gensync/docs/mkdocs
```

I accessible, un cop generada, a:

```text
/admin/documentacio
```
