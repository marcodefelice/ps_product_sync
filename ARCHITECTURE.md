# Architettura del Modulo - MLab Product Sync

## Panoramica

Il modulo `mlab_product_sync` implementa un'architettura **MVC (Model-View-Controller)** completa per sincronizzare i prodotti di PrestaShop con Google Merchant Center.

## Struttura Progetto

```
mlab_product_sync/
│
├── mlab_product_sync.php          # Entry point del modulo PrestaShop
├── composer.json                  # Dipendenze (Google API Client)
├── config.json                    # Metadata del modulo
│
├── src/
│   ├── Models/                    # Layer Model (dati)
│   │   └── ProductModel.php
│   │
│   ├── Services/                  # Layer Service (logica business)
│   │   ├── GoogleMerchantService.php
│   │   └── SyncService.php
│   │
│   └── Controllers/               # Layer Controller (orchestrazione)
│       └── ProductSyncController.php
│
├── README.md                      # Documentazione utente
├── INSTALL.md                     # Guida installazione
└── ARCHITECTURE.md               # Questo file
```

## Pattern Architetturale: MVC

### 1. Model Layer

**File**: `src/Models/ProductModel.php`

**Responsabilità**:
- Accesso e gestione dei dati dei prodotti PrestaShop
- Trasformazione dei dati da formato PrestaShop a formato Google Merchant
- Nessuna logica business o di presentazione

**Metodi principali**:
- `getProductData($productId)`: Recupera tutti i dati di un prodotto
- `getActiveProducts()`: Ottiene lista prodotti attivi
- `getProductImages($productId)`: Gestisce le immagini
- `getAvailability($productId)`: Determina disponibilità

**Dipendenze**:
- PrestaShop Core: `Product`, `StockAvailable`, `Image`, `Link`, `Category`

### 2. Service Layer

Il Service Layer implementa la **logica business** e orchestra le operazioni complesse.

#### SyncService (`src/Services/SyncService.php`)

**Responsabilità**:
- Orchestra la sincronizzazione tra PrestaShop e Google
- Applica le regole business (es. sincronizza solo prodotti attivi)
- Gestisce il flusso di sincronizzazione

**Metodi principali**:
- `syncProduct($productId)`: Sincronizza un singolo prodotto
- `deleteProduct($productId)`: Elimina da GMC
- `syncAllProducts()`: Sincronizzazione di massa
- `updateProductQuantity($productId)`: Aggiorna disponibilità

**Dipendenze**:
- `ProductModel`: Per ottenere i dati
- `GoogleMerchantService`: Per comunicare con Google

#### GoogleMerchantService (`src/Services/GoogleMerchantService.php`)

**Responsabilità**:
- Comunicazione con Google Merchant Center API
- Autenticazione e gestione credenziali
- Conversione dati al formato richiesto da Google

**Metodi principali**:
- `upsertProduct($productData)`: Inserisce o aggiorna un prodotto
- `deleteProduct($offerId)`: Elimina un prodotto
- `getProduct($offerId)`: Recupera un prodotto da GMC
- `syncAllProducts($products)`: Sincronizzazione batch
- `isConfigured()`: Verifica configurazione

**Dipendenze**:
- Google API Client Library
- Configuration (PrestaShop)

### 3. Controller Layer

**File**: `src/Controllers/ProductSyncController.php`

**Responsabilità**:
- Gestisce gli hook di PrestaShop
- Processa le richieste dell'utente dal back office
- Genera l'interfaccia di configurazione (View)
- Delega la logica ai Services

**Metodi principali**:
- `handleProductAdd($params)`: Hook nuovo prodotto
- `handleProductUpdate($params)`: Hook aggiornamento
- `handleProductDelete($params)`: Hook eliminazione
- `handleQuantityUpdate($params)`: Hook quantità
- `handleConfiguration()`: Pagina configurazione
- `displayConfigurationForm()`: Genera form (View)

**Dipendenze**:
- `SyncService`: Per eseguire sincronizzazioni
- PrestaShop Core: `Tools`, `Configuration`, `HelperForm`

### 4. Entry Point

**File**: `mlab_product_sync.php`

**Responsabilità**:
- Registrazione modulo PrestaShop
- Registrazione hook
- Inizializzazione controller
- Gestione install/uninstall

**Hook registrati**:
- `actionProductAdd`: Quando viene creato un prodotto
- `actionProductUpdate`: Quando viene aggiornato un prodotto
- `actionProductDelete`: Quando viene eliminato un prodotto
- `actionUpdateQuantity`: Quando cambia la quantità

## Flusso Dati

### Sincronizzazione Prodotto (esempio)

```
1. PrestaShop Event
   └─> actionProductUpdate hook triggered
       │
2. Entry Point (mlab_product_sync.php)
   └─> hookActionProductUpdate($params)
       │
3. Controller (ProductSyncController)
   └─> handleProductUpdate($params)
       │
4. Service (SyncService)
   └─> syncProduct($productId)
       │
       ├─> Model (ProductModel)
       │   └─> getProductData($productId)
       │       └─> Returns: product data array
       │
       └─> Service (GoogleMerchantService)
           └─> upsertProduct($productData)
               │
               └─> Google API Call
                   └─> Product synced to GMC
```

### Configurazione Modulo

```
1. User Action
   └─> Click "Configure" in Module Manager
       │
2. Entry Point
   └─> getContent()
       │
3. Controller
   └─> handleConfiguration()
       │
       ├─> processConfigurationForm() (if form submitted)
       │   └─> Save to Configuration
       │
       └─> displayConfigurationForm()
           └─> Generate HTML form (View)
               └─> Return to PrestaShop
```

## Principi di Design

### 1. Separation of Concerns (SoC)
Ogni componente ha una responsabilità specifica:
- **Model**: Solo dati
- **Service**: Solo logica business
- **Controller**: Solo orchestrazione e UI

### 2. Single Responsibility Principle (SRP)
Ogni classe ha un solo motivo per cambiare:
- `ProductModel`: Cambio struttura dati PrestaShop
- `GoogleMerchantService`: Cambio API Google
- `SyncService`: Cambio logica sincronizzazione
- `ProductSyncController`: Cambio interfaccia o hook

### 3. Dependency Injection
Le dipendenze vengono iniettate, non create internamente:
```php
// Nel Controller
$this->syncService = new SyncService();

// Nel SyncService
$this->productModel = new ProductModel();
$this->googleMerchantService = new GoogleMerchantService();
```

### 4. Open/Closed Principle
Il codice è aperto all'estensione ma chiuso alla modifica:
- Nuovi service possono essere aggiunti senza modificare esistenti
- Nuovi hook possono essere gestiti aggiungendo metodi al controller

## Configurazione e Sicurezza

### Gestione Credenziali

**Storage**:
- Merchant ID: `Configuration::get('MLAB_GMC_MERCHANT_ID')`
- Credenziali JSON: `Configuration::get('MLAB_GMC_CREDENTIALS')`
- Auto Sync Flag: `Configuration::get('MLAB_GMC_AUTO_SYNC')`

**Sicurezza**:
- Le credenziali non vengono mai esposte nel frontend
- Il file JSON viene letto e salvato nel database
- Nessuna credenziale nei file di codice
- Solo il back office può accedere alla configurazione

### Error Handling

Il modulo implementa un robusto error handling:

```php
try {
    $result = $this->googleMerchantService->upsertProduct($data);
    if (!$result['success']) {
        error_log("Errore sync: " . $result['error']);
    }
} catch (Exception $e) {
    error_log("Eccezione: " . $e->getMessage());
}
```

Tutti gli errori vengono:
1. Loggati nel sistema PrestaShop
2. Restituiti come risultato strutturato
3. Mai esposti all'utente finale

## Estensibilità

### Aggiungere un Nuovo Campo

1. **Model**: Aggiungere campo in `getProductData()`
```php
return [
    // ... campi esistenti
    'custom_field' => $product->custom_attribute,
];
```

2. **Service**: Mappare in `GoogleMerchantService::upsertProduct()`
```php
if (!empty($productData['custom_field'])) {
    $product->setCustomField($productData['custom_field']);
}
```

### Aggiungere un Nuovo Hook

1. **Entry Point**: Registrare in `install()`
```php
$this->registerHook('actionNewHook')
```

2. **Entry Point**: Aggiungere metodo hook
```php
public function hookActionNewHook($params) {
    return $this->getSyncController()->handleNewHook($params);
}
```

3. **Controller**: Implementare handler
```php
public function handleNewHook($params) {
    // logica
}
```

### Aggiungere un Nuovo Service

1. Creare nuovo file in `src/Services/`
2. Implementare la logica
3. Iniettare dove necessario

```php
// src/Services/NewService.php
namespace MlabPs\ProductSync\Services;

class NewService {
    public function doSomething() {
        // implementazione
    }
}

// Uso nel SyncService
$this->newService = new NewService();
```

## Testing

### Unit Testing (consigliato)

```php
// Test ProductModel
$model = new ProductModel();
$data = $model->getProductData(1);
$this->assertArrayHasKey('title', $data);

// Test SyncService con mock
$mockGoogleService = $this->createMock(GoogleMerchantService::class);
$syncService = new SyncService();
// ... test logic
```

### Integration Testing

1. Installare il modulo in PrestaShop di test
2. Configurare con credenziali di test
3. Creare prodotto di test
4. Verificare sincronizzazione su GMC test

### Manual Testing

1. Creare/modificare/eliminare prodotti
2. Verificare log PrestaShop
3. Verificare Google Merchant Center
4. Testare sincronizzazione manuale

## Performance

### Ottimizzazioni Implementate

1. **Lazy Loading**: Controller e Service inizializzati solo se necessari
2. **Error Handling**: Errori non bloccano il flusso PrestaShop
3. **Batch Operations**: `syncAllProducts()` per sincronizzazioni di massa
4. **Conditional Sync**: Solo prodotti attivi vengono sincronizzati

### Considerazioni

- Google API ha limiti di quota
- Per cataloghi grandi (>10000 prodotti), considerare:
  - Cron job per sincronizzazione graduale
  - Queue system per operazioni asincrone
  - Caching per ridurre chiamate API

## Manutenzione

### Log Monitoring

Controllare regolarmente:
```
Back Office > Strumenti avanzati > Log
```

Cercare:
- "Errore sync prodotto"
- "Errore inizializzazione Google Client"
- "GMC"

### Update Dependencies

```bash
cd modules/mlab_product_sync
composer update
```

### Database Cleanup

Il modulo non crea tabelle custom, usa solo `Configuration`.
Cleanup automatico in `uninstall()`.

## Supporto

Per domande o supporto: tech@mlabfactory.it

## Licenza

Copyright © 2024 MLab Factory - Tutti i diritti riservati
