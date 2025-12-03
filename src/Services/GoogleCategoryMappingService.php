<?php

namespace MlabPs\ProductSync\Services;

/**
 * Servizio avanzato per mappare le categorie PrestaShop alle categorie Google Shopping
 * con analisi del prodotto per categorie più specifiche
 */
class GoogleCategoryMappingService
{
    private static $categoryMapping = [

    // 🐶 CATEGORIE GENERALI PET
    'animali' => 'Animals & Pet Supplies',
    'pet' => 'Animals & Pet Supplies',
    'cani' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies',
    'gatti' => 'Animals & Pet Supplies > Pet Supplies > Cat Supplies',

    // 🐕‍🦺 PETTORINE
    'pettorina' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Harnesses',
    'pettorine' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Harnesses',
    'harness' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Harnesses',

    // 🐕 GUINZAGLI
    'guinzaglio' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Leashes',
    'guinzagli' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Leashes',
    'lead' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Leashes',

    // 🦴 COLLARI
    'collare' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Collars',
    'collari' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Collars',

    // 👗 ABBIGLIAMENTO CANI
    'abbigliamento' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
    'cappotto' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
    'cappotti' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
    'piumino' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
    'maglione' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
    'maglioni' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
    'felpa' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
    'felpe' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',

    // 👜 BORSE / TRASPORTINI PER CANI
    'borsa' => 'Animals & Pet Supplies > Pet Supplies > Pet Carriers & Totes',
    'borse' => 'Animals & Pet Supplies > Pet Supplies > Pet Carriers & Totes',
    'trasportino' => 'Animals & Pet Supplies > Pet Supplies > Pet Carriers & Totes',
    'carrier' => 'Animals & Pet Supplies > Pet Supplies > Pet Carriers & Totes',

    // 🛏️ CUCCE / LETTI / BORSE CUCCIA
    'cuccia' => 'Animals & Pet Supplies > Pet Supplies > Pet Beds',
    'cucce' => 'Animals & Pet Supplies > Pet Supplies > Pet Beds',
    'bed bag' => 'Animals & Pet Supplies > Pet Supplies > Pet Beds',

    // 🎁 ACCESSORI VARI
    'porta sacchetti' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies',
    'medagliette' => 'Animals & Pet Supplies > Pet Supplies > Pet ID Tags',
    'id tag' => 'Animals & Pet Supplies > Pet Supplies > Pet ID Tags',

];


    /**
     * Mappa una categoria PrestaShop a una categoria Google Shopping
     * con analisi avanzata del prodotto
     */
    public static function mapCategoryToGoogle($categoryName, $categoryPath = [], $productData = [])
    {
        // Carica mappature personalizzate
        self::loadCustomMappings();
        
        if (empty($categoryName)) {
            return self::analyzeProductForCategory($productData);
        }

        // Normalizza il nome della categoria
        $normalizedName = strtolower(trim($categoryName));
        $normalizedName = preg_replace('/[^a-z0-9\s]/', '', $normalizedName);
        
        // Prova mappatura diretta
        if (isset(self::$categoryMapping[$normalizedName])) {
            return self::refineCategory(self::$categoryMapping[$normalizedName], $productData);
        }
        
        // Prova con parole chiave contenute nel nome
        foreach (self::$categoryMapping as $keyword => $googleCategory) {
            if (strpos($normalizedName, $keyword) !== false) {
                return self::refineCategory($googleCategory, $productData);
            }
        }
        
        // Se ha un path di categorie, prova con quelle
        if (!empty($categoryPath)) {
            foreach ($categoryPath as $pathCategory) {
                $normalizedPath = strtolower(trim($pathCategory));
                $normalizedPath = preg_replace('/[^a-z0-9\s]/', '', $normalizedPath);
                
                if (isset(self::$categoryMapping[$normalizedPath])) {
                    return self::refineCategory(self::$categoryMapping[$normalizedPath], $productData);
                }
                
                foreach (self::$categoryMapping as $keyword => $googleCategory) {
                    if (strpos($normalizedPath, $keyword) !== false) {
                        return self::refineCategory($googleCategory, $productData);
                    }
                }
            }
        }
        
        // Analizza il prodotto per determinare la categoria
        $analyzedCategory = self::analyzeProductForCategory($productData);
        if ($analyzedCategory !== 'Apparel & Accessories') {
            return $analyzedCategory;
        }
        
        // Fallback alla categoria generica
        return 'Apparel & Accessories';
    }
    
    /**
     * Analizza i dati del prodotto per determinare una categoria più specifica
     */
    private static function analyzeProductForCategory($productData)
    {
        if (empty($productData)) {
            return 'Apparel & Accessories';
        }
        
        // Combina nome e descrizione per l'analisi
        $text = strtolower(
            ($productData['title'] ?? '') . ' ' . 
            ($productData['description'] ?? '') . ' ' . 
            ($productData['short_description'] ?? '')
        );
        
        // Rimuovi caratteri speciali
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Patterns specifici per rilevare settori principali PRIMA dell'analisi dettagliata
        $sectorPatterns = [
            // Elettronica
            '/\b(smartphone|iphone|android|samsung|telefon|cellulare|mobile)\b/' => 'Electronics > Communications > Telephony > Mobile Phones',
            '/\b(tablet|ipad)\b/' => 'Electronics > Computers > Tablets',
            '/\b(laptop|computer|pc|notebook)\b/' => 'Electronics > Computers > Laptops',
            '/\b(tv|televisore|televisione)\b/' => 'Electronics > Audio & Video > Televisions',
            '/\b(cuffie|auricolari|headphones)\b/' => 'Electronics > Audio > Audio Components > Headphones',
            
            // Casa e cucina
            '/\b(cucina|pentola|padella|cookware)\b/' => 'Home & Garden > Kitchen & Dining > Cookware',
            '/\b(bagno|doccia|bathroom)\b/' => 'Home & Garden > Bathroom Accessories',
            '/\b(giardino|piante|garden)\b/' => 'Home & Garden > Yard, Garden & Outdoor Living',
            '/\b(mobili|furniture|tavolo|sedia)\b/' => 'Home & Garden > Furniture',
            
            // Sport
            '/\b(fitness|palestra|gym|training|workout)\b/' => 'Sporting Goods > Exercise & Fitness',
            '/\b(calcio|soccer|football)\b/' => 'Sporting Goods > Team Sports > Soccer',
            '/\b(tennis|racchetta)\b/' => 'Sporting Goods > Individual Sports > Tennis & Racquet Sports > Tennis',
            '/\b(basket|basketball)\b/' => 'Sporting Goods > Team Sports > Basketball',
            '/\b(nuoto|swimming|piscina)\b/' => 'Sporting Goods > Water Sports > Swimming',
            
            // Libri e Media
            '/\b(libro|book|romanzo|manuale)\b/' => 'Media > Books',
            '/\b(dvd|film|movie|cinema)\b/' => 'Media > DVDs & Videos',
            '/\b(cd|musica|music|disco)\b/' => 'Media > Music',
            
            // Giocattoli
            '/\b(giocattolo|toy|bambola|peluche|puzzle)\b/' => 'Toys & Games',
            '/\b(lego|costruzioni|building)\b/' => 'Toys & Games > Toys > Building Sets',
            
            // Bellezza e cura
            '/\b(cosmetici|makeup|trucco|beauty)\b/' => 'Health & Beauty > Personal Care > Cosmetics',
            '/\b(profumo|fragrance|eau)\b/' => 'Health & Beauty > Personal Care > Fragrance',
            '/\b(shampoo|bagno|soap|sapone)\b/' => 'Health & Beauty > Personal Care > Bath & Body',
            
            // Cibo e bevande
            '/\b(cibo|food|alimentari|snack)\b/' => 'Food, Beverages & Tobacco > Food Items',
            '/\b(bevande|drinks|vino|wine|birra)\b/' => 'Food, Beverages & Tobacco > Beverages',
            
            // Animali
            '/\b(cane|dog|gatto|cat|pet|animale)\b/' => 'Animals & Pet Supplies > Pet Supplies',
        ];
        
        // Verifica settori principali prima
        foreach ($sectorPatterns as $pattern => $category) {
            if (preg_match($pattern, $text)) {
                return $category;
            }
        }
        
        // Patterns specifici per abbigliamento per cani
        $apparelPatterns = [
            // Abbigliamento per cani
            '/\b(cappotto|coat).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
            '/\b(giacca|jacket).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
            '/\b(felpa|hoodie|sweatshirt).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
            '/\b(maglietta|t-shirt|tshirt).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
            '/\b(maglione|pullover|sweater).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
            '/\b(impermeabile|raincoat).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
            '/\b(costume|vestito).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
            
            // Accessori per cani
            '/\b(collare|collar).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Collars',
            '/\b(guinzaglio|leash|lead).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Leashes',
            '/\b(pettorina|harness).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Harnesses',
            '/\b(bandana).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
            '/\b(cappello|hat|cap).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
            '/\b(scarpette|shoes|boots|scarpe).*\b(cane|dog|cani|dogs)\b/' => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Apparel',
        ];
        
        // Controlla pattern abbigliamento dettagliati
        foreach ($apparelPatterns as $pattern => $category) {
            if (preg_match($pattern, $text)) {
                return $category;
            }
        }
        
        // Se contiene parole generiche di abbigliamento, ritorna la categoria base
        if (preg_match('/\b(abbigliamento|clothing|vestiti|apparel|fashion|moda)\b/', $text)) {
            return 'Apparel & Accessories';
        }
        
        // Fallback finale basato su parole chiave generiche
        if (preg_match('/\b(scarpe|shoes|calzature)\b/', $text)) {
            return 'Apparel & Accessories > Shoes';
        }
        
        if (preg_match('/\b(maglietta|shirt|t-shirt|tshirt|top)\b/', $text)) {
            return 'Apparel & Accessories > Clothing > Shirts & Tops';
        }
        
        if (preg_match('/\b(pantaloni|pants|jeans)\b/', $text)) {
            return 'Apparel & Accessories > Clothing > Pants';
        }
        
        // Fallback finale: categoria più generica
        return 'Apparel & Accessories';
    }
    
    /**
     * Raffina una categoria base con informazioni aggiuntive dal prodotto
     */
    private static function refineCategory($baseCategory, $productData)
    {
        if (empty($productData)) {
            return $baseCategory;
        }
        
        $text = strtolower(
            ($productData['title'] ?? '') . ' ' . 
            ($productData['description'] ?? '')
        );
        
        // Se è una categoria generica di abbigliamento, prova a renderla più specifica
        if ($baseCategory === 'Apparel & Accessories > Clothing') {
            // Cerca indicatori più specifici
            if (strpos($text, 'maglietta') !== false || strpos($text, 't-shirt') !== false) {
                return 'Apparel & Accessories > Clothing > Shirts & Tops > T-shirts';
            }
            if (strpos($text, 'felpa') !== false || strpos($text, 'hoodie') !== false) {
                return 'Apparel & Accessories > Clothing > Outerwear > Hoodies & Sweatshirts';
            }
            if (strpos($text, 'pantalon') !== false || strpos($text, 'jeans') !== false) {
                return 'Apparel & Accessories > Clothing > Pants';
            }
        }
        
        // Se è scarpe generiche, prova a specificare
        if ($baseCategory === 'Apparel & Accessories > Shoes') {
            if (strpos($text, 'running') !== false || strpos($text, 'corsa') !== false) {
                return 'Apparel & Accessories > Shoes > Athletic Shoes > Running Shoes';
            }
            if (strpos($text, 'sneaker') !== false || strpos($text, 'ginnastica') !== false) {
                return 'Apparel & Accessories > Shoes > Athletic Shoes';
            }
            if (strpos($text, 'elegante') !== false || strpos($text, 'formal') !== false) {
                return 'Apparel & Accessories > Shoes > Dress Shoes';
            }
        }
        
        return $baseCategory;
    }
    
    /**
     * Carica mappature personalizzate dal file JSON
     */
    private static function loadCustomMappings()
    {
        static $loaded = false;
        
        if ($loaded) {
            return;
        }
        
        $configFile = __DIR__ . '/../../category_mappings.json';
        
        if (file_exists($configFile)) {
            try {
                $json = file_get_contents($configFile);
                $config = json_decode($json, true);
                
                if (isset($config['category_mappings']['mappings'])) {
                    foreach ($config['category_mappings']['mappings'] as $prestashop => $google) {
                        $normalized = strtolower(trim($prestashop));
                        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);
                        self::$categoryMapping[$normalized] = $google;
                    }
                }
            } catch (Exception $e) {
                // Se c'è un errore nel JSON, usa solo le mappature di default
            }
        }
        
        $loaded = true;
    }
    
    /**
     * Aggiunge una nuova mappatura
     */
    public static function addCategoryMapping($prestashopCategory, $googleCategory)
    {
        $normalized = strtolower(trim($prestashopCategory));
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);
        self::$categoryMapping[$normalized] = $googleCategory;
    }
    
    /**
     * Ottiene tutte le mappature disponibili
     */
    public static function getAllMappings()
    {
        self::loadCustomMappings();
        return self::$categoryMapping;
    }
    
}