# MLab Product Sync - Google Merchant Center Integration

Modulo PrestaShop per sincronizzare automaticamente i prodotti con Google Merchant Center.

## Descrizione

Questo modulo sincronizza automaticamente i prodotti di PrestaShop con Google Merchant Center ogni volta che:
- Un prodotto viene creato
- Un prodotto viene aggiornato
- Un prodotto viene eliminato
- La quantità di un prodotto cambia

## Caratteristiche

- ✅ Sincronizzazione automatica con Google Merchant Center
- ✅ Architettura MVC (Model-View-Controller)
- ✅ Sincronizzazione manuale di tutti i prodotti
- ✅ Pannello di configurazione intuitivo
- ✅ Monitoraggio stato configurazione
- ✅ Log dettagliati per debugging
- ✅ Compatibile con PrestaShop 8.x e 9.x
- ✅ Architettura modulare con pattern PSR-4

## Requisiti

- PrestaShop 8.0.0 o superiore
- PHP 7.4 o superiore
- Composer
- Account Google Merchant Center
- Progetto Google Cloud con API Content for Shopping abilitata
- Service Account Google Cloud

## Installazione

### 1. Installazione Composer

```bash
cd modules/mlab_product_sync
composer install
```

### 2. Installazione del modulo

1. Carica la cartella del modulo in `modules/mlab_product_sync`
2. Vai nel back office di PrestaShop
3. Naviga in: Moduli > Module Manager
4. Cerca "MLab Product Sync"
5. Clicca su "Installa"

## Struttura del Modulo (MVC)

```
mlab_product_sync/
├── src/
│   ├── Controllers/
│   │   └── ProductSyncController.php   # Gestisce azioni e UI
│   ├── Services/
│   │   ├── GoogleMerchantService.php   # Comunicazione con Google API
│   │   └── SyncService.php             # Logica di sincronizzazione
│   └── Models/
│       └── ProductModel.php            # Gestione dati prodotti
├── mlab_product_sync.php               # File principale modulo
├── composer.json                       # Dipendenze (Google API Client)
└── config.json                         # Configurazione modulo
```

### Architettura MVC

**Model (ProductModel)**
- Gestisce i dati dei prodotti PrestaShop
- Prepara i dati per Google Merchant Center
- Ottiene immagini, prezzi, disponibilità

**View (generata da Controller)**
- Form di configurazione nel back office
- Pannelli di stato
- Interfaccia sincronizzazione manuale

**Controller (ProductSyncController)**
- Gestisce gli hook PrestaShop
- Processa le azioni dell'utente
- Genera l'interfaccia

**Services**
- **SyncService**: Orchestra la sincronizzazione
- **GoogleMerchantService**: Gestisce le API di Google

## Configurazione Google Cloud

### 1. Creare un Progetto Google Cloud

1. Vai su [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuovo progetto o selezionane uno esistente
3. Abilita l'API "Content API for Shopping":
   - Vai su API & Services > Library
   - Cerca "Content API for Shopping"
   - Clicca su "Abilita"

### 2. Creare un Service Account

1. Vai su IAM & Admin > Service Accounts
2. Clicca su "Create Service Account"
3. Inserisci un nome (es. "prestashop-sync")
4. Clicca su "Create and Continue"
5. Assegna il ruolo (opzionale per questo step)
6. Clicca su "Done"
7. Clicca sul Service Account appena creato
8. Vai su "Keys" > "Add Key" > "Create new key"
9. Seleziona "JSON" e scarica il file
10. Salva il file JSON in un posto sicuro

### 3. Configurare Google Merchant Center

1. Vai su [Google Merchant Center](https://merchants.google.com/)
2. Se non hai un account, creane uno
3. Completa la configurazione del tuo negozio
4. Vai su Impostazioni > Utenti
5. Clicca su "Aggiungi utente"
6. Inserisci l'email del Service Account (trovi l'email nel file JSON)
7. Assegna il ruolo "Amministratore"
8. Salva
9. Prendi nota del tuo Merchant ID (lo trovi in alto a destra in GMC)

## Configurazione Modulo

1. Vai nel back office di PrestaShop
2. Naviga in: Moduli > Module Manager
3. Cerca "MLab Product Sync"
4. Clicca su "Configura"
5. Inserisci i seguenti parametri:

   - **Enable automatic sync**: Attiva/disattiva la sincronizzazione automatica
   - **Merchant ID**: Il tuo ID di Google Merchant Center
   - **Service Account Credentials**: Carica il file JSON scaricato da Google Cloud

6. Clicca su "Salva"
7. Verifica che lo stato mostri tutto configurato correttamente

## Utilizzo

### Sincronizzazione Automatica

Una volta configurato e abilitato, il modulo sincronizzerà automaticamente:

- **Nuovi prodotti**: Quando un prodotto viene creato, viene subito aggiunto a GMC
- **Aggiornamenti**: Quando un prodotto viene modificato, le modifiche vengono sincronizzate
- **Eliminazioni**: Quando un prodotto viene eliminato, viene rimosso da GMC
- **Quantità**: Quando la quantità cambia, la disponibilità viene aggiornata

### Sincronizzazione Manuale

Per sincronizzare manualmente tutti i prodotti attivi:

1. Vai nella pagina di configurazione del modulo
2. Scorri fino alla sezione "Manual Synchronization"
3. Clicca su "Sync All Products Now"
4. Attendi il completamento e verifica il risultato

## Dati Sincronizzati

Per ogni prodotto vengono sincronizzati i seguenti dati:

- **ID e Riferimento**: Identificatori univoci del prodotto
- **Titolo**: Nome del prodotto
- **Descrizione**: Descrizione completa e breve
- **Link**: URL della pagina prodotto
- **Immagini**: Immagine principale e immagini aggiuntive
- **Prezzo**: Prezzo con IVA
- **Disponibilità**: in_stock, out_of_stock, preorder
- **Brand**: Marca/Produttore
- **GTIN**: Codice EAN13
- **MPN**: Codice produttore (reference)
- **Categoria**: Categoria del prodotto
- **Condizione**: new (nuovi prodotti)

## Funzionamento

### Hook PrestaShop Utilizzati

Il modulo si aggancia ai seguenti hook:

- `actionProductAdd`: Chiamato quando viene creato un nuovo prodotto
- `actionProductUpdate`: Chiamato quando un prodotto viene modificato
- `actionProductDelete`: Chiamato quando un prodotto viene eliminato
- `actionUpdateQuantity`: Chiamato quando cambia la quantità disponibile

### Flusso di Sincronizzazione

1. **Hook Trigger**: PrestaShop chiama uno degli hook sopra elencati
2. **Controller**: Riceve la richiesta e la passa al Service
3. **SyncService**: Orchestra la sincronizzazione
4. **ProductModel**: Recupera i dati del prodotto da PrestaShop
5. **GoogleMerchantService**: Invia i dati a Google Merchant Center
6. **Risultato**: Il prodotto viene sincronizzato o viene registrato un errore

## Troubleshooting

### Il modulo non sincronizza

- Verifica che la sincronizzazione automatica sia **abilitata**
- Controlla che le credenziali siano state caricate correttamente
- Verifica che il Service Account abbia accesso al Merchant Center
- Controlla i log di PrestaShop: Strumenti avanzati > Log

### Errori di autenticazione

- Verifica che il file JSON delle credenziali sia valido
- Controlla che l'API "Content API for Shopping" sia abilitata nel progetto Google Cloud
- Verifica che il Service Account sia stato aggiunto come utente in Merchant Center con ruolo "Amministratore"

### Prodotti non visualizzati in GMC

- Verifica che i prodotti siano attivi in PrestaShop
- Controlla che i prodotti abbiano tutti i campi obbligatori compilati (titolo, descrizione, prezzo, immagine)
- Verifica nel Merchant Center se ci sono errori o avvisi sui prodotti

### Errori API Google

- Controlla le quote API nel Google Cloud Console
- Verifica che non ci siano limitazioni sul Merchant Center
- Assicurati che il Merchant Center sia completamente configurato e verificato

## Log e Debugging

I log del modulo sono registrati nel sistema di log di PrestaShop:

**Accesso ai log:**
1. Back Office > Strumenti avanzati > Log
2. Cerca errori contenenti "GMC" o "sync prodotto"

**Tipi di errori registrati:**
- Errori di inizializzazione del client Google
- Errori di sincronizzazione prodotti
- Errori di eliminazione prodotti
- Errori di aggiornamento quantità

## Supporto

Email: tech@mlabfactory.it

## Autore

MLab Factory - tech@mlabfactory.it

## Licenza

Copyright © 2024 MLab Factory - Tutti i diritti riservati

## Changelog

### v1.0.0 (2024)
- Release iniziale
- Sincronizzazione automatica con Google Merchant Center
- Supporto hook PrestaShop per prodotti
- Pannello di configurazione con status
- Sincronizzazione manuale di tutti i prodotti
- Architettura MVC completa
