<?php

namespace MlabPs\ProductSync\Services;

use Configuration;

/**
 * Service per gestire la comunicazione con Google Merchant Center
 */
class GoogleMerchantService
{
    private $merchantId;
    private $client;

    public function __construct()
    {
        $this->merchantId = Configuration::get('MLAB_GMC_MERCHANT_ID');
        $this->initializeClient();
    }

    /**
     * Inizializza il client Google
     */
    private function initializeClient()
    {
        $credentialsJson = Configuration::get('MLAB_GMC_CREDENTIALS');
        
        if (empty($credentialsJson)) {
            return;
        }

        try {
            $credentials = json_decode($credentialsJson, true);
            
            // Verifica se Google Client Library è disponibile
            if (!class_exists('\Google_Client')) {
                error_log('Google Client Library non disponibile');
                return;
            }

            $this->client = new \Google_Client();
            $this->client->setAuthConfig($credentials);
            $this->client->addScope('https://www.googleapis.com/auth/content');
        } catch (\Exception $e) {
            error_log('Errore inizializzazione Google Client: ' . $e->getMessage());
        }
    }

    /**
     * Inserisce o aggiorna un prodotto su Google Merchant Center
     */
    public function upsertProduct($productData)
    {
        if (!$this->client || empty($this->merchantId)) {
            return [
                'success' => false,
                'error' => 'Client non inizializzato o Merchant ID mancante'
            ];
        }

        try {
            $service = new \Google_Service_ShoppingContent($this->client);
            
            $product = new \Google_Service_ShoppingContent_Product();
            $product->setOfferId($productData['reference'] ?: 'product-' . $productData['id']);
            $product->setTitle($productData['title']);
            $product->setDescription($productData['description']);
            $product->setLink($productData['link']);
            $product->setImageLink($productData['image_link']);
            $product->setContentLanguage('it');
            $product->setTargetCountry('IT');
            $product->setChannel('online');
            
            // Prezzo
            $price = new \Google_Service_ShoppingContent_Price();
            $price->setValue($productData['price']);
            $price->setCurrency('EUR');
            $product->setPrice($price);
            
            // Disponibilità
            $product->setAvailability($productData['availability']);
            
            // Brand
            if (!empty($productData['brand'])) {
                $product->setBrand($productData['brand']);
            }
            
            // GTIN
            if (!empty($productData['gtin'])) {
                $product->setGtin($productData['gtin']);
            }
            
            // MPN
            if (!empty($productData['mpn'])) {
                $product->setMpn($productData['mpn']);
            }
            
            // Condizione
            $product->setCondition($productData['condition']);
            
            // Immagini aggiuntive
            if (!empty($productData['additional_image_links'])) {
                $product->setAdditionalImageLinks($productData['additional_image_links']);
            }
            
            // Categoria Google
            if (!empty($productData['google_product_category'])) {
                $product->setGoogleProductCategory($productData['google_product_category']);
            }
            
            // Tipo di prodotto
            if (!empty($productData['product_type'])) {
                $product->setProductTypes([$productData['product_type']]);
            }

            // Inserisci o aggiorna il prodotto
            $result = $service->products->insert($this->merchantId, $product);
            
            return [
                'success' => true,
                'product_id' => $result->getId(),
                'data' => $result
            ];
        } catch (\Exception $e) {
            error_log('Errore sync prodotto a GMC: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Elimina un prodotto da Google Merchant Center
     */
    public function deleteProduct($offerId)
    {
        if (!$this->client || empty($this->merchantId)) {
            return [
                'success' => false,
                'error' => 'Client non inizializzato o Merchant ID mancante'
            ];
        }

        try {
            $service = new \Google_Service_ShoppingContent($this->client);
            $service->products->delete($this->merchantId, $offerId);
            
            return ['success' => true];
        } catch (\Exception $e) {
            error_log('Errore eliminazione prodotto da GMC: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene un prodotto da Google Merchant Center
     */
    public function getProduct($offerId)
    {
        if (!$this->client || empty($this->merchantId)) {
            return null;
        }

        try {
            $service = new \Google_Service_ShoppingContent($this->client);
            return $service->products->get($this->merchantId, $offerId);
        } catch (\Exception $e) {
            error_log('Errore recupero prodotto da GMC: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sincronizza tutti i prodotti
     */
    public function syncAllProducts($products)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($products as $productData) {
            $result = $this->upsertProduct($productData);
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'product_id' => $productData['id'],
                    'error' => $result['error']
                ];
            }
        }

        return $results;
    }

    /**
     * Verifica se il servizio è configurato correttamente
     */
    public function isConfigured()
    {
        return !empty($this->merchantId) && 
               !empty(Configuration::get('MLAB_GMC_CREDENTIALS')) &&
               $this->client !== null;
    }
}
