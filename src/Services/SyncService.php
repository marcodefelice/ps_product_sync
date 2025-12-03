<?php

namespace MlabPs\ProductSync\Services;

use MlabPs\ProductSync\Models\ProductModel;
use Configuration;

/**
 * Service per orchestrare la sincronizzazione dei prodotti
 */
class SyncService
{
    private $productModel;
    private $googleMerchantService;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->googleMerchantService = new GoogleMerchantService();
    }

    /**
     * Sincronizza un singolo prodotto
     */
    public function syncProduct($productId)
    {
        // Verifica se la sincronizzazione automatica è abilitata
        if (!Configuration::get('MLAB_GMC_AUTO_SYNC')) {
            return [
                'success' => false,
                'message' => 'Sincronizzazione automatica disabilitata'
            ];
        }

        // Verifica se il servizio è configurato
        if (!$this->googleMerchantService->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Servizio Google Merchant Center non configurato'
            ];
        }

        // Ottieni i dati del prodotto
        $productData = $this->productModel->getProductData($productId);
        
        if (!$productData) {
            return [
                'success' => false,
                'message' => 'Prodotto non trovato'
            ];
        }

        // Sincronizza solo se il prodotto è attivo
        if (!$productData['active']) {
            return $this->deleteProduct($productId);
        }

        // Invia a Google Merchant Center
        return $this->googleMerchantService->upsertProduct($productData);
    }

    /**
     * Elimina un prodotto da Google Merchant Center
     */
    public function deleteProduct($productId)
    {
        $productData = $this->productModel->getProductData($productId);
        
        if (!$productData) {
            return [
                'success' => false,
                'message' => 'Prodotto non trovato'
            ];
        }

        $offerId = $productData['reference'] ?: 'product-' . $productData['id'];
        return $this->googleMerchantService->deleteProduct($offerId);
    }

    /**
     * Sincronizza tutti i prodotti attivi
     */
    public function syncAllProducts()
    {
        if (!$this->googleMerchantService->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Servizio Google Merchant Center non configurato'
            ];
        }

        $products = $this->productModel->getActiveProducts();
        $productsData = [];

        foreach ($products as $product) {
            $productData = $this->productModel->getProductData($product['id_product']);
            if ($productData && $productData['active']) {
                $productsData[] = $productData;
            }
        }

        return $this->googleMerchantService->syncAllProducts($productsData);
    }

    /**
     * Aggiorna la quantità di un prodotto
     */
    public function updateProductQuantity($productId)
    {
        // Per l'aggiornamento della quantità, possiamo semplicemente
        // risincronizzare tutto il prodotto
        return $this->syncProduct($productId);
    }

    /**
     * Verifica lo stato della configurazione
     */
    public function checkConfiguration()
    {
        return [
            'auto_sync' => (bool)Configuration::get('MLAB_GMC_AUTO_SYNC'),
            'merchant_id' => Configuration::get('MLAB_GMC_MERCHANT_ID'),
            'has_credentials' => !empty(Configuration::get('MLAB_GMC_CREDENTIALS')),
            'is_configured' => $this->googleMerchantService->isConfigured()
        ];
    }
}
