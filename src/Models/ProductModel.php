<?php

namespace MlabPs\ProductSync\Models;

use Product;
use StockAvailable;
use Image;
use Link;
use Category;

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
     * Ottiene la categoria Google per il prodotto
     */
    private function getGoogleCategory($productId)
    {
        // Qui puoi implementare la logica per mappare le categorie PrestaShop
        // alle categorie Google Merchant Center
        // Per ora restituiamo una categoria generica
        return '';
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
