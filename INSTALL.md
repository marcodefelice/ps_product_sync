# Guida Rapida - Installazione e Configurazione

## 1. Installazione Dipendenze

```bash
cd modules/mlab_product_sync
composer install
```

## 2. Verifica File Installati

Assicurati che la struttura sia:
```
mlab_product_sync/
├── src/
│   ├── Controllers/
│   │   └── ProductSyncController.php
│   ├── Services/
│   │   ├── GoogleMerchantService.php
│   │   └── SyncService.php
│   └── Models/
│       └── ProductModel.php
├── vendor/
├── mlab_product_sync.php
├── composer.json
└── config.json
```

## 3. Installa il Modulo in PrestaShop

1. Nel back office vai su: **Moduli > Module Manager**
2. Cerca **"MLab Product Sync"**
3. Clicca **"Installa"**

## 4. Configura Google Cloud

### 4.1 Crea un Progetto Google Cloud

1. Vai su [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuovo progetto o selezionane uno esistente
3. Annota il **Project ID** 1009834426689

### 4.2 Abilita l'API Content for Shopping

1. Nel progetto Google Cloud, vai su **APIs & Services > Library**
2. Cerca **"Content API for Shopping"**
3. Clicca su **"Enable"**

### 4.3 Crea un Service Account

1. Vai su **IAM & Admin > Service Accounts**
2. Clicca **"Create Service Account"**
3. Nome: `prestashop-gmc-sync`
4. Descrizione: `Service Account per sincronizzazione PrestaShop con Google Merchant Center`
5. Clicca **"Create and Continue"**
6. Salta l'assegnazione dei ruoli (opzionale per questo step)
7. Clicca **"Done"**

### 4.4 Crea le Credenziali JSON

1. Nella lista dei Service Accounts, clicca sul Service Account appena creato
2. Vai su tab **"Keys"**
3. Clicca **"Add Key" > "Create new key"**
4. Seleziona formato **"JSON"**
5. Clicca **"Create"**
6. Il file JSON verrà scaricato automaticamente
7. **IMPORTANTE**: Conserva questo file in modo sicuro, non condividerlo mai!

### 4.5 Annota l'Email del Service Account

Nel file JSON scaricato, troverai qualcosa come:
```json
{
  "client_email": "prestashop-gmc-sync@your-project.iam.gserviceaccount.com",
  ...
}
```
Annota questa email, ti servirà nel prossimo step.

## 5. Configura Google Merchant Center

### 5.1 Crea o Accedi al Merchant Center

1. Vai su [Google Merchant Center](https://merchants.google.com/)
2. Se non hai un account, creane uno
3. Completa la configurazione base del negozio:
   - Informazioni azienda
   - URL del sito web
   - Verifica del sito
   - Shipping e Tax (se richiesti)

### 5.2 Annota il Merchant ID

In alto a destra vedrai un numero (es. `123456789`)
Questo è il tuo **Merchant ID** - annotalo. 5675955989

### 5.3 Aggiungi il Service Account come Utente

1. In Merchant Center, vai su **Impostazioni** (icona ingranaggio) > **Accesso all'account**
2. Nella sezione **Utenti**, clicca su **"Aggiungi utente"**
3. Email: Inserisci l'email del Service Account (dal passo 4.5)
4. Ruolo: Seleziona **"Amministratore"**
5. Clicca **"Invita"**
6. Il Service Account ora ha accesso al tuo Merchant Center

## 6. Configura il Modulo in PrestaShop

1. Nel back office vai su: **Moduli > Module Manager**
2. Cerca **"MLab Product Sync"**
3. Clicca **"Configura"**
4. Compila i campi:
   - **Enable automatic sync**: Attiva (On)
   - **Merchant ID**: Il tuo Merchant ID (dal passo 5.2)
   - **Service Account Credentials**: Carica il file JSON (dal passo 4.4)
5. Clicca **"Salva"**
6. Verifica che tutti gli indicatori di stato siano verdi

## 7. Test

### Test Automatico
Quando salvi la configurazione, verifica lo stato nella sezione "Configuration Status":
- ✅ Auto Sync: Enabled
- ✅ Merchant ID: Set
- ✅ Credentials: Uploaded
- ✅ Service Status: Configured

### Test con un Singolo Prodotto

1. Vai su **Catalogo > Prodotti**
2. Crea o modifica un prodotto
3. Assicurati che abbia:
   - Nome
   - Descrizione
   - Prezzo
   - Almeno un'immagine
   - EAN13 (opzionale ma consigliato)
4. Salva il prodotto
5. Vai su Google Merchant Center
6. Vai su **Products > All products**
7. Dovresti vedere il tuo prodotto (può richiedere qualche minuto)

### Test Sincronizzazione di Massa

1. Nella configurazione del modulo, scorri fino a "Manual Synchronization"
2. Clicca **"Sync All Products Now"**
3. Attendi il completamento
4. Verifica il messaggio di conferma con il numero di prodotti sincronizzati
5. Controlla su Google Merchant Center che i prodotti siano presenti

## 8. Verifica Prodotti su GMC

1. Vai su [Google Merchant Center](https://merchants.google.com/)
2. Menu laterale: **Products > All products**
3. Dovresti vedere i tuoi prodotti PrestaShop
4. Se ci sono errori o avvisi, cliccali per vedere i dettagli
5. Correggi eventuali problemi (es. campi mancanti, categorie non valide)

## Troubleshooting

### Errore: "Service Status: Not Configured"
- Verifica che il file JSON sia stato caricato correttamente
- Controlla che l'API Content for Shopping sia abilitata
- Verifica che il Merchant ID sia corretto

### Errore: "Access denied" o "Permission denied"
- Verifica che il Service Account sia stato aggiunto come utente in GMC
- Controlla che il ruolo assegnato sia "Amministratore"
- Attendi qualche minuto dopo aver aggiunto l'utente (la propagazione può richiedere tempo)

### I prodotti non appaiono in GMC
- Verifica che i prodotti siano attivi in PrestaShop
- Controlla che abbiano tutti i campi obbligatori:
  - Titolo
  - Descrizione
  - Prezzo
  - Immagine
  - Link
- Guarda la sezione "Diagnostics" in GMC per eventuali errori

### Errore API Quota
- Google ha limiti sulle API gratuite
- Vai su Google Cloud Console > APIs & Services > Dashboard
- Verifica le quote utilizzate
- Se necessario, abilita la fatturazione per aumentare i limiti

### Prodotti con errori su GMC
Common issues:
- **Missing GTIN**: Aggiungi il codice EAN13 ai prodotti
- **Missing brand**: Aggiungi il produttore ai prodotti
- **Invalid image**: Verifica che le immagini siano accessibili pubblicamente
- **Invalid price**: Verifica che il prezzo sia maggiore di 0

## Log

I log del modulo si trovano in:
**Back Office > Strumenti avanzati > Log**

Cerca messaggi contenenti:
- "Errore sync prodotto"
- "Errore inizializzazione Google Client"
- "GMC"

## Sincronizzazione Continua

Una volta configurato, il modulo sincronizzerà automaticamente:
- ✅ Nuovi prodotti quando vengono creati
- ✅ Modifiche quando i prodotti vengono aggiornati
- ✅ Eliminazioni quando i prodotti vengono cancellati
- ✅ Disponibilità quando cambia la quantità

## Note Importanti

⚠️ **Sicurezza**
- Non condividere mai il file JSON delle credenziali
- Non committare il file JSON in repository pubblici
- Usa ruoli con minimi privilegi necessari

⚠️ **Performance**
- La prima sincronizzazione di molti prodotti può richiedere tempo
- Google ha limiti di API quota
- Considera di sincronizzare in batch per cataloghi molto grandi

⚠️ **Requisiti Prodotti**
- I prodotti devono avere almeno: titolo, descrizione, prezzo, immagine
- È consigliato avere: GTIN (EAN13), marca, categoria
- Verifica le policy di Google Merchant Center per il tuo paese

## Supporto

Per assistenza: tech@mlabfactory.it
