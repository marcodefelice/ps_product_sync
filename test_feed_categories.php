<?php
/**
 * Script di test per verificare la mappatura avanzata delle categorie Google Shopping
 * Mostra come il sistema analizza i prodotti per categorie ultra-specifiche
 */

// Questo script deve essere eseguito nel contesto di PrestaShop
if (!defined('_PS_ROOT_DIR_')) {
    // Prova a includere config se non siamo in un contesto PrestaShop
    $configPaths = [
        __DIR__ . '/../../config/config.inc.php',
        __DIR__ . '/../../../config/config.inc.php',
        __DIR__ . '/../../../../config/config.inc.php'
    ];
    
    foreach ($configPaths as $configPath) {
        if (file_exists($configPath)) {
            require_once $configPath;
            break;
        }
    }
}

// Verifica se siamo in PrestaShop
if (!defined('_PS_ROOT_DIR_')) {
    die("Errore: Questo script deve essere eseguito nel contesto di PrestaShop\n");
}

require_once __DIR__ . '/src/Models/ProductModel.php';
require_once __DIR__ . '/src/Services/GoogleCategoryMappingService.php';

use MlabPs\ProductSync\Models\ProductModel;
use MlabPs\ProductSync\Services\GoogleCategoryMappingService;

echo "=== TEST MAPPATURA AVANZATA CATEGORIE GOOGLE SHOPPING ===\n\n";

try {
    // Test delle mappature dirette
    echo "--- TEST MAPPATURE DIRETTE (Sistema Base) ---\n";
    $testCategories = [
        'felpe', 'magliette', 'jeans', 'scarpe running', 
        'sneakers', 'stivali', 'sandali', 'giacche'
    ];
    
    foreach ($testCategories as $category) {
        $googleCategory = GoogleCategoryMappingService::mapCategoryToGoogle($category, []);
        echo "'{$category}' -> '{$googleCategory}'\n";
    }
    
    // Test con analisi del prodotto simulata
    echo "\n--- TEST ANALISI AVANZATA PRODOTTI (Sistema Intelligente) ---\n";
    $testProducts = [
        [
            'category' => 'scarpe',
            'data' => [
                'title' => 'Nike Air Max Running Shoes',
                'description' => 'Scarpe da corsa professionali per atletica',
                'brand' => 'Nike'
            ]
        ],
        [
            'category' => 'magliette',
            'data' => [
                'title' => 'T-shirt Cotone Uomo',
                'description' => 'Maglietta casual in cotone 100%',
                'brand' => 'Adidas'
            ]
        ],
        [
            'category' => 'pantaloni',
            'data' => [
                'title' => 'Jeans Slim Fit Denim',
                'description' => 'Pantaloni jeans elasticizzati denim blu',
                'brand' => 'Levi\'s'
            ]
        ],
        [
            'category' => 'scarpe',
            'data' => [
                'title' => 'Scarpe Eleganti Business',
                'description' => 'Calzature formali in pelle per ufficio',
                'brand' => 'Clarks'
            ]
        ],
        [
            'category' => 'felpe',
            'data' => [
                'title' => 'Hoodie Training Sport',
                'description' => 'Felpa sportiva con cappuccio per palestra',
                'brand' => 'Under Armour'
            ]
        ]
    ];
    
    foreach ($testProducts as $test) {
        $googleCategory = GoogleCategoryMappingService::mapCategoryToGoogle(
            $test['category'], 
            [], 
            $test['data']
        );
        echo "Categoria: '{$test['category']}' + Prodotto: '{$test['data']['title']}'\n";
        echo "  -> Google Category: '{$googleCategory}'\n\n";
    }
    
    echo "--- TEST CON PRODOTTI REALI DEL NEGOZIO ---\n";
    
    // Crea istanza del model
    $productModel = new ProductModel();
    
    // Ottieni alcuni prodotti per test
    $products = Product::getProducts(Context::getContext()->language->id, 0, 5, 'id_product', 'ASC', false, true);
    
    if (empty($products)) {
        echo "Nessun prodotto trovato nel negozio.\n";
    } else {
        echo "Prodotti trovati: " . count($products) . "\n\n";
        
        foreach ($products as $product) {
            $productId = $product['id_product'];
            $productData = $productModel->getProductData($productId);
            
            if (!$productData) {
                echo "Prodotto ID $productId: Dati non disponibili\n\n";
                continue;
            }
            
            echo "--- PRODOTTO ID: $productId ---\n";
            echo "Nome: " . $productData['title'] . "\n";
            echo "Descrizione: " . substr(strip_tags($productData['description'] ?? ''), 0, 100) . "...\n";
            echo "Categoria Google (Avanzata): " . $productData['google_product_category'] . "\n";
            
            // Debug aggiuntivo
            $productObj = new Product($productId, false, Context::getContext()->language->id);
            echo "Categoria PrestaShop ID: " . $productObj->id_category_default . "\n";
            
            if ($productObj->id_category_default > 2) {
                $category = new Category($productObj->id_category_default, Context::getContext()->language->id);
                echo "Categoria PrestaShop Nome: " . $category->name . "\n";
            }
            
            echo "\n";
        }
    }
    
    echo "--- CONFRONTO: Sistema Base vs Sistema Avanzato ---\n";
    echo "Esempio con prodotto 'Nike Running Shoes' in categoria 'Scarpe':\n";
    echo "Sistema Base: " . GoogleCategoryMappingService::mapCategoryToGoogle('scarpe', []) . "\n";
    echo "Sistema Avanzato: " . GoogleCategoryMappingService::mapCategoryToGoogle('scarpe', [], [
        'title' => 'Nike Air Max Running Shoes',
        'description' => 'Scarpe da corsa professionali'
    ]) . "\n\n";
    
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}

echo "=== FINE TEST ===\n";
echo "\n💡 VANTAGGI DEL SISTEMA AVANZATO:\n";
echo "- Categorie ultra-specifiche basate sul prodotto reale\n";
echo "- Analisi intelligente di titolo, descrizione e brand\n";
echo "- Mappature più accurate per miglior posizionamento Google Shopping\n";
echo "- Personalizzazione tramite category_mappings.json\n";
?>