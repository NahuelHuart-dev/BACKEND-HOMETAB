# Manual d'usuari — HomeTab

## Benvingut a HomeTab

**HomeTab** és l'aplicació per gestionar la convivència sense drames. Des d'una sola pantalla pots organitzar tasques de la llar, controlar despeses compartides, crear esdeveniments, xatejar amb els companys de pis i molt més.

---

## 1. Registre i inici de sessió

### Registrar-se

> **Captura recomanada**: Pàgina `/register` — formulari amb camps: Nom, Cognom, Email, Contrasenya. Fons fosc amb el logo HomeTab a la part superior.

1. Ves a la pàgina principal i fes clic a **"Registra't"**.
2. Omple el formulari:
   - **Nom** i **Cognom** (mínim 2 caràcters cadascun).
   - **Email** vàlid — serà el teu identificador d'accés.
   - **Contrasenya** — mínima 8 caràcters.
3. Fes clic a **"Crear compte"**.
4. Si tot és correcte, seràs redirigit automàticament a la pantalla de les teves llars (Mis Tabs).

### Iniciar sessió

> **Captura recomanada**: Pàgina `/login` — formulari Email + Contrasenya, botó "Entrar", opció "Recuperar contrasenya".

1. Entra el teu email i contrasenya.
2. Fes clic a **"Entrar"**.
3. Si tens la **verificació en dos passos** activada, rebràs un codi al teu correu. Introdueix-lo a la casella que apareix.

### Recuperar contrasenya

1. A la pantalla de login, fes clic a **"Has oblidat la contrasenya?"**.
2. Introdueix el teu email.
3. Rebràs un codi de verificació al correu.
4. Introdueix el codi i després la nova contrasenya.

---

## 2. Mis Tabs (TabHub)

> **Captura recomanada**: Pàgina `/tabhub` — targetes post-it de les llars de l'usuari sobre fons fosc. Botó "Nova llar" i botó "Unir-se a una llar".

En accedir a HomeTab, arribaràs a **Mis Tabs**: el centre de les teves llars.

### Crear una nova llar

1. Fes clic a **"Crear nova llar"** (targeta amb icona `+`).
2. Posa-li un nom.
3. (Opcional) Afegeix una imatge o tria un icona.
4. Fes clic a **"Crear"**.
5. La llar es crea amb tu com a **propietari** i genera un **codi d'invitació** de 6 caràcters.

### Unir-se a una llar existent

1. Fes clic a **"Unir-se a una llar"**.
2. Introdueix el **codi d'invitació** que t'ha donat el propietari.
3. Fes clic a **"Unir-me"**.
4. La llar apareixerà a les teves targetes.

### Reordenar les targetes

Arrossega les targetes per canviar l'ordre de les teves llars.

---

## 3. Dashboard de la llar

> **Captura recomanada**: Pàgina `/households/{id}/dashboard` — panell amb resum: tasques pendents, despeses, events pròxims. Indicadors de progrés. Llista de membres amb avatars.

En fer clic a una llar, accedeixes al seu **Dashboard**. Des d'aquí veus:

- **Resum de tasques**: quantes pendents, completades i vencudes.
- **Resum de despeses**: total del mes i estat de pagaments.
- **Pròxims esdeveniments**: els 3 events més propers.
- **Membres de la llar**: avatars i rols.

---

## 4. Tasques

> **Captura recomanada**: Pestanya `Tasques` — llista de tasques amb indicadors de prioritat, data de venciment, assignació i estat (pendent/completada). Botó "Nova tasca" a la cantonada.

### Crear una tasca

1. Ves a la pestanya **"Tasques"** de la llar.
2. Fes clic a **"Nova tasca"**.
3. Omple:
   - **Títol** (obligatori).
   - **Descripció** (opcional).
   - **Data de venciment** (opcional).
   - **Prioritat**: Alta, Mitja, Baixa.
   - **Periodicitat**: Única, Setmanal, Mensual.
   - **Categoria** (opcional).
   - **Assignada a**: tria un membre de la llar. Per defecte, t'assigna a tu.
4. Fes clic a **"Crear"**.

### Completar o editar una tasca

- **Marca-la com a completada** fent clic al cercle o switch de la tasca.
- **Edita-la** fent clic al llapis.
- **Elimina-la** fent clic a la paperera (borrat lògic, no desapareix de l'historial).

---

## 5. Despeses

> **Captura recomanada**: Pestanya `Despeses` — llista de despeses amb categoria, import, estat (pagat/pendent), qui va pagar. Resum de balance per membre. Botó "Nova despesa".

### Crear una despesa

1. Ves a la pestanya **"Despeses"**.
2. Fes clic a **"Nova despesa"**.
3. Omple:
   - **Títol** i **Import** (obligatoris).
   - **Categoria**: General, Alimentació, Llum, Aigua, Internet, Habitatge, etc.
   - **Tipus de pagament**:
     - *Compartida*: es divideix entre els membres seleccionats.
     - *Individual*: la paga una sola persona.
   - **Participants**: selecciona qui participa en la despesa.
   - **Periodicitat**: Única, Diària, Setmanal, Mensual.
   - **Data de venciment** (opcional).
4. Fes clic a **"Crear"**.

### Veure el balance

A la secció **"Balance"** veus quant deu o li deuen a cada membre. La secció **"Liquidacions"** mostra els pagaments recomanats per equilibrar els comptes.

---

## 6. Calendari (Esdeveniments)

> **Captura recomanada**: Pestanya `Calendari` — vista mensual amb events col·locats en el calendari, codificats per colors. Botó "Nou event" i panel lateral de detall de l'event seleccionat.

### Crear un event

1. Ves a la pestanya **"Calendari"**.
2. Fes clic a **"Nou event"** o fes clic a un dia del calendari.
3. Omple:
   - **Títol** i **Data d'inici** (obligatoris).
   - **Data de fi** (opcional).
   - **Ubicació** (opcional).
   - **Color** de l'event.
   - **Tot el dia**: activa si és un event que dura tot el dia.
   - **Participants**: membres de la llar.
4. Fes clic a **"Crear"**.

---

## 7. Xat de la llar

> **Captura recomanada**: Widget de xat obert — llista de missatges amb avatars, timestamps i camp d'input a la part inferior. En mòbil, apareix com a pantalla sencera.

El xat de la llar és accessible des de la **icona de xat** disponible a totes les pestanyes de la llar.

### Enviar un missatge

1. Fes clic a la icona de xat.
2. Escriu el missatge al camp d'input.
3. Prem **Enviar** o la tecla Enter.

### Editar o eliminar un missatge

- Fes clic als tres punts (⋯) al costat del teu missatge.
- Tria **"Editar"** o **"Eliminar"**.

> **Nota**: Només pots editar o eliminar els teus propis missatges.

---

## 8. Notificacions

> **Captura recomanada**: Widget de notificacions obert — llista agrupada per llar, amb icones de tipus (tasca, despesa, event, xat), títol, missatge i timestamp. Indicador numèric de no llegides.

Les notificacions apareixen a la **icona de campana** a la barra de navegació.

- Les notificacions s'agrupen per llar.
- Fes clic a **una notificació** per marcar-la com a llegida.
- Fes clic a **"Marcar totes com a llegides"** per netejar el comptador.

---

## 9. Multimèdia (Playlists de YouTube)

> **Captura recomanada**: Pestanya `Multimèdia` — llista de playlists amb miniatura del primer vídeo, nom i nombre de vídeos. Reproductors integrats de YouTube en fila. Botó "Nova playlist".

### Crear una playlist

1. Ves a la pestanya **"Multimèdia"**.
2. Fes clic a **"Nova playlist"**.
3. Posa un nom (màx. 120 caràcters).
4. Fes clic a **"Crear"**.

### Afegir vídeos

1. A dins d'una playlist, fes clic a **"Afegir vídeo"**.
2. Cerca per paraula clau o enganxa una URL de YouTube.
3. Selecciona el vídeo dels resultats.
4. El vídeo queda afegit i es pot reproduir directament a la pestanya.

---

## 10. Perfil d'usuari

> **Captura recomanada**: Pàgina `/profile` — formulari amb foto de perfil, nom, cognom, email, telèfon, bio. Secció de canvi de contrasenya i secció de 2FA.

### Editar el perfil

1. Fes clic al teu avatar a la barra de navegació.
2. Accedeix a **"El meu perfil"**.
3. Pots modificar:
   - **Foto de perfil**: puja una foto o tria un icona.
   - **Nom i cognom**.
   - **Email** (comprova que no estigui en ús).
   - **Telèfon** i **Bio**.
4. Fes clic a **"Guardar canvis"**.

### Canviar la contrasenya

1. A la secció **"Seguretat"** del perfil.
2. Introdueix la contrasenya actual.
3. Introdueix la nova contrasenya dues vegades.
4. Fes clic a **"Canviar contrasenya"**.

---

## 11. Verificació en dos passos (2FA)

> **Captura recomanada**: Secció 2FA dins del perfil — toggle activat/desactivat, descripció del procés, i estat actual (actiu en verd o inactiu en gris).

La verificació en dos passos afegeix una capa de seguretat extra al teu compte.

### Activar la 2FA

1. Ves a **"El meu perfil"** → secció **"Seguretat"**.
2. Activa el toggle de **"Verificació en dos passos"**.
3. Rebràs un codi al teu correu electrònic.
4. Introdueix el codi per confirmar.
5. A partir d'ara, en cada login rebràs un codi temporal.

### Desactivar la 2FA

1. Ves al perfil → **"Seguretat"**.
2. Desactiva el toggle de **"Verificació en dos passos"**.
3. Es desactiva immediatament.

---

## 12. Configuració de la llar

> **Captura recomanada**: Pestanya `Configuració` dins d'una llar — nom de la llar, imatge, codi d'invitació (amb opció de copiar), llista de membres amb rols, botó d'expulsar membres.

A la pestanya **"Configuració"** de cada llar (visible per a propietaris i admins):

- **Canviar el nom** de la llar.
- **Canviar la imatge** o l'icona.
- **Veure el codi d'invitació** i copiar-lo per compartir.
- **Canviar el codi d'invitació** (solo el propietari).
- **Veure els membres** i els seus rols.
- **Expulsar membres** (no pots expulsar el propietari).

---

## Rols dins d'una llar

| Rol | Pot crear/editar | Pot gestionar membres | Pot canviar codi |
|---|---|---|---|
| `owner` (propietari) | ✓ | ✓ | ✓ |
| `admin` | ✓ | ✓ | ✗ |
| `member` | ✓ | ✗ | ✗ |

---

## Suport i Feedback

Pots enviar comentaris o suggeriments a través de la pàgina **"Feedback"** accessible des del menú principal.
