# SPEC - Assistent IA HomeTab

## Objectiu

Crear un assistent IA petit per a HomeTab que permeti a l'usuari consultar informació ràpida sobre la seva llar.

## Funcionalitats inicials

L'assistent pot respondre preguntes sobre:

- Tasques pendents.
- Esdeveniments propers.
- Pagaments conjunts.
- Balanç entre membres de la llar.
- Resum general de la llar.

## Limitacions

- L'assistent només pot consultar dades de l'usuari autenticat.
- L'assistent no pot modificar dades en aquesta primera versió.
- L'assistent no pot inventar informació.
- Si no té dades suficients, ha de dir-ho clarament.
- Si la pregunta no és sobre HomeTab, ha de respondre que només pot ajudar amb informació de l'aplicació.

## Endpoint

POST /api/assistant/chat

### Request

{
"message": "Quins pagaments tinc pendents?"
}

### Response

{
"answer": "Tens 2 pagaments pendents...",
"sources": ["payments", "balances"]
}
