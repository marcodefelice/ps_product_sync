# Changelog

Tutte le modifiche rilevanti a questo progetto saranno documentate in questo file.

## [1.1.1] - 2024

### Aggiunto
- Campo obbligatorio `g:google_product_category` nel feed Facebook
- Campo obbligatorio `g:identifier_exists` nel feed Facebook
- Logica per utilizzare mappatura categorie Google esistente (modulo Facebook)
- Validazione GTIN (8/12/13/14 cifre numeriche)
- Validazione MPN (stringa non vuota)
- Categoria Google fallback su categoria prodotto PrestaShop
- Integrazione con GoogleCategoryProvider per mappature esistenti

### Migliorato
- Qualità dati feed Facebook per migliore approvazione
- Compatibilità con standard Google Shopping
- Utilizzo mappature categorie configurate in altri moduli

## [1.1.0] - 2024

### Aggiunto
- Generazione feed XML per Facebook Shop
- Service class `FacebookFeedService` per gestione feed
- Pannello di gestione feed nel back office
- Supporto formato RSS 2.0 con namespace Google Shopping
- URL pubblico per accesso feed
- Informazioni dettagliate sul feed (numero prodotti, dimensione, ultima modifica)
- Funzionalità eliminazione feed
- Documentazione feed Facebook in FACEBOOK_FEED.md
- Validazione prodotti prima dell'inclusione nel feed
- Gestione immagini multiple (fino a 10 immagini aggiuntive)
- Troncamento automatico testi per rispettare limiti Facebook

### Caratteristiche Feed
- Campi obbligatori: id, title, description, availability, condition, price, link, image_link
- Campi opzionali: brand, gtin, mpn, product_type, google_product_category, additional_image_link
- Solo prodotti attivi con dati validi
- Formato prezzo con valuta (es: 29.99 EUR)
- Disponibilità mappata (in stock, out of stock, preorder)

## [1.0.0] - 2024

### Aggiunto
- Sincronizzazione automatica con Google Merchant Center
- Hook PrestaShop per eventi prodotto (add, update, delete, quantity)
- Service class `GoogleMerchantService` per API Google
- Service class `SyncService` per logica sincronizzazione
- Model class `ProductModel` per gestione dati prodotti
- Controller class `ProductSyncController` per gestione UI e azioni
- Form di configurazione nel back office
- Pannello stato configurazione
- Sincronizzazione manuale di tutti i prodotti
- Logging dettagliato errori
- Upload credenziali Service Account JSON
- Test configurazione Google Cloud
- Documentazione completa in README.md

### Tecnico
- Architettura MVC completa
- Pattern PSR-4 con autoloading Composer
- Dependency Injection per Google API Client
- Compatibilità PrestaShop 8.x - 9.x
- Google API Client PHP
- Gestione sicura credenziali
- Validazione prodotti prima sincronizzazione

### Sicurezza
- Credenziali Google salvate in Configuration (database PrestaShop)
- Validazione input configurazione
- Gestione sicura errori API Google

## Note di Migrazione

### Da v1.0.0 a v1.1.0
- Nessuna migrazione richiesta
- Nuova funzionalità Facebook Feed disponibile immediatamente
- Configurazione esistente Google Merchant Center non influenzata

## Breaking Changes

- Nessuno

## Known Issues

- Nessuno noto

## Roadmap Futuro

Possibili funzionalità future:
- [ ] Rigenerazione automatica feed Facebook su schedule (cron)
- [ ] Supporto feed per altri marketplace (Amazon, eBay)
- [ ] Feed multilingua
- [ ] Mapping categorie personalizzato
- [ ] Filtri avanzati per inclusione/esclusione prodotti
- [ ] Dashboard statistiche sincronizzazione
- [ ] Notifiche email su errori sincronizzazione
- [ ] Batch processing per grandi cataloghi
- [ ] API REST per gestione feed
- [ ] Integrazione Google Analytics per tracking performance
