# Quick Start Guide - MLab Product Sync

Guida rapida per iniziare in 10 minuti.

## 📋 Prerequisiti

- [ ] Account Google (Gmail)
- [ ] PrestaShop 8.0+ installato
- [ ] Accesso al back office PrestaShop
- [ ] Composer installato sul server

## 🚀 Step 1: Installazione Modulo (2 min)

```bash
cd modules/mlab_product_sync
composer install
```

Nel back office PrestaShop:
1. Vai su **Moduli > Module Manager**
2. Cerca "MLab Product Sync"
3. Clicca **Installa**

✅ Modulo installato!

## ☁️ Step 2: Setup Google Cloud (3 min)

### A. Crea Progetto
1. Vai su https://console.cloud.google.com/
2. Clicca su "Select a project" → "New Project"
3. Nome: `prestashop-sync`
4. Clicca "Create"

### B. Abilita API
1. Nel menu laterale: **APIs & Services > Library**
2. Cerca: "Content API for Shopping"
3. Clicca **Enable**

### C. Crea Service Account
1. Menu: **IAM & Admin > Service Accounts**
2. **Create Service Account**
3. Nome: `prestashop-sync`
4. **Create and Continue** → **Done**
5. Clicca sul service account appena creato
6. Tab **Keys** → **Add Key** → **Create new key**
7. Formato: **JSON** → **Create**
8. Salva il file scaricato

✅ Google Cloud configurato!

## 🛒 Step 3: Setup Google Merchant Center (3 min)

### A. Crea Account
1. Vai su https://merchants.google.com/
2. Se non hai account, creane uno
3. Completa setup base negozio

### B. Aggiungi Service Account
1. In GMC: **Settings** (⚙️) → **Account access**
2. Tab **Users** → **Add user**
3. Email: copia da file JSON scaricato (`client_email`)
4. Ruolo: **Admin**
5. **Invite**

### C. Prendi Merchant ID
Guarda in alto a destra: è il numero tipo `123456789`

✅ Google Merchant Center configurato!

## ⚙️ Step 4: Configura Modulo (2 min)

Nel back office PrestaShop:

1. **Moduli > Module Manager**
2. Cerca "MLab Product Sync"
3. **Configure**
4. Compila:
   - **Enable automatic sync**: ON
   - **Merchant ID**: (da Step 3C)
   - **Credentials**: Upload file JSON (da Step 2C)
5. **Save**

Verifica che tutti gli indicatori siano verdi ✅

✅ Modulo configurato!

## 🧪 Step 5: Test (1 min)

### Opzione A: Test con prodotto esistente
1. Vai su **Catalog > Products**
2. Modifica un prodotto
3. Cambia qualcosa (es. descrizione)
4. Salva
5. Vai su GMC: dovrebbe apparire in "Products"

### Opzione B: Sincronizza tutto
1. Nella configurazione modulo
2. Scorri giù fino a "Manual Synchronization"
3. Clicca **Sync All Products Now**
4. Verifica messaggio successo

✅ Test completato!

## ✅ Checklist Finale

Verifica che:
- [ ] Status in configurazione: tutto verde
- [ ] Prodotto di test appare in GMC (può richiedere qualche minuto)
- [ ] Nessun errore nei log PrestaShop

## 🎉 Fatto!

Il tuo negozio ora sincronizza automaticamente i prodotti con Google Merchant Center!

### Cosa succede ora automaticamente:

- ✅ Nuovo prodotto creato → Sync a GMC
- ✅ Prodotto modificato → Update su GMC
- ✅ Prodotto eliminato → Rimosso da GMC
- ✅ Quantità cambiata → Disponibilità aggiornata

## 🔧 Troubleshooting Veloce

### "Service Status: Not Configured"
➡️ Ricarica il file JSON delle credenziali

### "Permission denied"
➡️ Verifica che il Service Account sia stato aggiunto come Admin in GMC

### I prodotti non appaiono in GMC
➡️ Aspetta 2-3 minuti, poi ricontrolla
➡️ Verifica che il prodotto sia attivo
➡️ Controlla che abbia: titolo, descrizione, prezzo, immagine

### Errori nei prodotti su GMC
➡️ Clicca sull'errore in GMC per dettagli
➡️ Aggiungi campi mancanti (EAN13, marca, ecc.)

## 📚 Documentazione Completa

Per maggiori dettagli:
- **INSTALL.md** - Guida installazione dettagliata
- **README.md** - Documentazione completa
- **ARCHITECTURE.md** - Architettura tecnica
- **MIGRATION_SUMMARY.md** - Riepilogo modifiche

## 💬 Supporto

Problemi? Contatta: tech@mlabfactory.it

---

**Buon lavoro! 🚀**
