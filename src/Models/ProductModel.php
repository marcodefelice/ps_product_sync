<?php

namespace MlabPs\ProductSync\Models;

use Product;
use StockAvailable;
use Image;
use Link;
use Category;
use MlabPs\ProductSync\Services\GoogleCategoryMappingService;

/**
 * Model per gestire i dati dei prodotti
 */
class ProductModel
{
    private $context;

    public function __construct($context = null)
    {
        $this->context = $context ?: \Context::getContext();
    }

    /**
     * Ottiene i dati di un prodotto per Google Merchant Center
     */
    public function getProductData($productId, $langId = null)
    {
        if ($langId === null) {
            $langId = $this->context->language->id;
        }

        $product = new Product($productId, true, $langId);
        
        if (!$product->id) {
            return null;
        }

        $link = new Link();
        $images = $this->getProductImages($productId);
        $category = new Category($product->id_category_default, $langId);
        
        return [
            'id' => $product->id,
            'reference' => $product->reference,
            'title' => $product->name,
            'description' => strip_tags($product->description),
            'short_description' => strip_tags($product->description_short),
            'link' => $link->getProductLink($product),
            'image_link' => !empty($images) ? $images[0] : '',
            'additional_image_links' => array_slice($images, 1),
            'price' => $product->getPrice(true),
            'availability' => $this->getAvailability($productId),
            'brand' => $product->manufacturer_name,
            'condition' => 'new',
            'gtin' => $product->ean13,
            'mpn' => $product->reference,
            'product_type' => $category->name,
            'google_product_category' => $this->getGoogleCategory($productId),
            'active' => (bool)$product->active,
            'quantity' => StockAvailable::getQuantityAvailableByProduct($productId),
        ];
    }

    /**
     * Ottiene le immagini di un prodotto
     */
    private function getProductImages($productId)
    {
        $images = Image::getImages($this->context->language->id, $productId);
        $link = new Link();
        $imageLinks = [];

        foreach ($images as $image) {
            $imageLinks[] = $link->getImageLink(
                Product::getProductName($productId),
                $image['id_image'],
                'large_default'
            );
        }

        return $imageLinks;
    }

    /**
     * Determina la disponibilità del prodotto
     */
    private function getAvailability($productId)
    {
        $quantity = StockAvailable::getQuantityAvailableByProduct($productId);
        
        if ($quantity > 0) {
            return 'in_stock';
        } elseif ($quantity == 0) {
            return 'out_of_stock';
        } else {
            return 'preorder';
        }
    }

    /**
     * Ottiene la categoria Google Shopping per il prodotto
     */
    private function getGoogleCategory($productId)
    {
        try {
            $product = new Product($productId, false, $this->context->language->id);
            
            if (!$product->id) {
                return GoogleCategoryMappingService::mapCategoryToGoogle('', [], []);
            }

            $categoryId = $product->id_category_default;
            
            // Se non ha categoria default, prova con le categorie associate
            if (!$categoryId || $categoryId <= 2) {
                $categories = Product::getProductCategoriesFull($productId);
                if (!empty($categories)) {
                    foreach ($categories as $cat) {
                        if ($cat['id_category'] > 2) { // Evita root e home
                            $categoryId = $cat['id_category'];
                            break;
                        }
                    }
                }
            }
            
            // Ottieni il percorso delle categorie
            $categoryPath = $this->getCategoryPath($categoryId);
            
            // Usa l'ultima categoria (più specifica) come principale
            $mainCategory = !empty($categoryPath) ? end($categoryPath) : '';
            
            // Prepara i dati del prodotto per l'analisi
            $productAnalysisData = [
                'title' => $product->name,
                'description' => $product->description,
                'short_description' => $product->description_short,
                'reference' => $product->reference,
                'brand' => $product->manufacturer_name
            ];
            
            // Mappa alla categoria Google Shopping con analisi del prodotto
            return GoogleCategoryMappingService::mapCategoryToGoogle(
                $mainCategory, 
                $categoryPath, 
                $productAnalysisData
            );
            
        } catch (\Exception $e) {
            // In caso di errore, restituisci categoria di default
            return GoogleCategoryMappingService::mapCategoryToGoogle('', [], []);
        }
    }

    /**
     * Ottiene il percorso delle categorie (per aiutare la mappatura)
     */
    private function getCategoryPath($categoryId, $langId = null)
    {
        if ($langId === null) {
            $langId = $this->context->language->id;
        }

        $path = [];
        $currentCategoryId = $categoryId;
        $maxLevels = 10; // Evita loop infiniti
        $level = 0;

        // Risali la gerarchia delle categorie
        while ($currentCategoryId && $currentCategoryId > 2 && $level < $maxLevels) {
            $category = new Category($currentCategoryId, $langId);
            
            // Se la categoria non esiste o non è valida, fermati
            if (!$category->id || !$category->active) {
                break;
            }
            
            // Se il nome è vuoto, fermati
            if (empty(trim($category->name))) {
                break;
            }
            
            // Aggiungi la categoria al percorso
            array_unshift($path, trim($category->name));
            
            // Vai al parent
            $currentCategoryId = $category->id_parent;
            $level++;
            
            // Se arriva alla categoria root o home, fermati
            if ($currentCategoryId <= 2) {
                break;
            }
        }

        return $path;
    }

    /**
     * Ottiene tutti i prodotti attivi
     */
    public function getActiveProducts($langId = null)
    {
        if ($langId === null) {
            $langId = $this->context->language->id;
        }

        $products = Product::getProducts(
            $langId,
            0,
            0,
            'id_product',
            'ASC',
            false,
            true
        );

        return $products;
    }
}
