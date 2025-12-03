<?php

namespace MlabPs\ProductSync\Services;

use MlabPs\ProductSync\Models\ProductModel;
use Product;
use DOMDocument;
use DOMElement;

/**
 * Servizio per generare il feed XML per Facebook Shop
 */
class FacebookFeedService
{
    private $productModel;
    private $context;
    private $feedPath;

    public function __construct($context = null)
    {
        $this->context = $context ?: \Context::getContext();
        $this->productModel = new ProductModel($this->context);
        $this->feedPath = _PS_ROOT_DIR_ . '/facebook_product_feed.xml';
    }

    /**
     * Genera il feed XML completo di tutti i prodotti attivi
     * 
     * @return array ['success' => bool, 'file_path' => string, 'message' => string, 'products_count' => int]
     */
    public function generateFeed()
    {
        try {
            $products = Product::getProducts(
                $this->context->language->id,
                0,
                0,
                'id_product',
                'ASC',
                false,
                true
            );

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;

            // Root element
            $rss = $dom->createElement('rss');
            $rss->setAttribute('version', '2.0');
            $rss->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
            $dom->appendChild($rss);

            $channel = $dom->createElement('channel');
            $rss->appendChild($channel);

            // Channel info
            $this->addChannelInfo($dom, $channel);

            $productsCount = 0;

            foreach ($products as $productData) {
                $productDetails = $this->productModel->getProductData($productData['id_product']);
                
                if ($productDetails && $productDetails['active'] && $this->isValidProduct($productDetails)) {
                    $this->addProductItem($dom, $channel, $productDetails);
                    $productsCount++;
                }
            }

            $dom->save($this->feedPath);

            return [
                'success' => true,
                'file_path' => $this->feedPath,
                'url' => $this->getFeedUrl(),
                'message' => "Feed generato con successo: {$productsCount} prodotti",
                'products_count' => $productsCount
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Errore durante la generazione del feed: ' . $e->getMessage(),
                'products_count' => 0
            ];
        }
    }

    /**
     * Aggiunge le informazioni del canale
     */
    private function addChannelInfo(DOMDocument $dom, DOMElement $channel)
    {
        $shop = $this->context->shop;
        
        $title = $dom->createElement('title', htmlspecialchars($shop->name ?? 'Shop'));
        $channel->appendChild($title);

        $linkUrl = method_exists($shop, 'getBaseURL') ? $shop->getBaseURL(true) : 'https://yourshop.com/';
        $link = $dom->createElement('link', htmlspecialchars($linkUrl));
        $channel->appendChild($link);

        $description = $dom->createElement('description', htmlspecialchars('Feed prodotti per Facebook Shop'));
        $channel->appendChild($description);
    }

    /**
     * Aggiunge un prodotto al feed
     */
    private function addProductItem(DOMDocument $dom, DOMElement $channel, array $productDetails)
    {
        $item = $dom->createElement('item');
        $channel->appendChild($item);

        // Campi obbligatori Facebook
        $this->addElement($dom, $item, 'g:id', $productDetails['id']);
        $this->addElement($dom, $item, 'g:title', $this->truncate($productDetails['title'], 150));
        $this->addElement($dom, $item, 'g:description', $this->truncate($productDetails['description'] ?: $productDetails['short_description'], 5000));
        $this->addElement($dom, $item, 'g:availability', $this->mapAvailability($productDetails['availability']));
        $this->addElement($dom, $item, 'g:condition', $productDetails['condition']);
        $this->addElement($dom, $item, 'g:price', $this->formatPrice($productDetails['price']));
        $this->addElement($dom, $item, 'g:link', $productDetails['link']);
        $this->addElement($dom, $item, 'g:image_link', $productDetails['image_link']);

        // Campi opzionali ma consigliati
        if (!empty($productDetails['brand'])) {
            $this->addElement($dom, $item, 'g:brand', $productDetails['brand']);
        }

        if (!empty($productDetails['gtin'])) {
            $this->addElement($dom, $item, 'g:gtin', $productDetails['gtin']);
        }

        if (!empty($productDetails['mpn'])) {
            $this->addElement($dom, $item, 'g:mpn', $productDetails['mpn']);
        }

        if (!empty($productDetails['product_type'])) {
            $this->addElement($dom, $item, 'g:product_type', $productDetails['product_type']);
        }

        // Google Product Category - usa mappatura alle categorie ufficiali Google Shopping
        $googleCategory = $productDetails['google_product_category'];
        $this->addElement($dom, $item, 'g:google_product_category', $googleCategory);
        
        // Identifier exists - indica se il prodotto ha GTIN/EAN o MPN
        $identifierExists = (!empty($productDetails['gtin']) || !empty($productDetails['mpn'])) ? 'true' : 'false';
        $this->addElement($dom, $item, 'g:identifier_exists', $identifierExists);

        // Immagini aggiuntive
        if (!empty($productDetails['additional_image_links'])) {
            foreach (array_slice($productDetails['additional_image_links'], 0, 10) as $additionalImage) {
                $this->addElement($dom, $item, 'g:additional_image_link', $additionalImage);
            }
        }
    }

    /**
     * Aggiunge un elemento XML
     */
    private function addElement(DOMDocument $dom, DOMElement $parent, $name, $value)
    {
        if ($value !== null && $value !== '') {
            $element = $dom->createElement($name);
            $element->appendChild($dom->createTextNode(htmlspecialchars($value)));
            $parent->appendChild($element);
        }
    }

    /**
     * Mappa la disponibilità al formato Facebook
     */
    private function mapAvailability($availability)
    {
        $map = [
            'in_stock' => 'in stock',
            'out_of_stock' => 'out of stock',
            'preorder' => 'preorder'
        ];

        return $map[$availability] ?? 'out of stock';
    }

    /**
     * Formatta il prezzo nel formato richiesto (es: 19.99 EUR)
     */
    private function formatPrice($price)
    {
        $currency = $this->context->currency;
        return number_format($price, 2, '.', '') . ' ' . $currency->iso_code;
    }

    /**
     * Tronca una stringa alla lunghezza massima
     */
    private function truncate($text, $maxLength)
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (mb_strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength - 3) . '...';
        }
        
        return $text;
    }

    /**
     * Verifica se un prodotto è valido per il feed
     */
    private function isValidProduct(array $product)
    {
        return !empty($product['title']) &&
               !empty($product['link']) &&
               !empty($product['image_link']) &&
               !empty($product['price']) &&
               $product['price'] > 0;
    }

    /**
     * Ottiene l'URL pubblico del feed
     */
    public function getFeedUrl()
    {
        $shop = $this->context->shop;
        if (method_exists($shop, 'getBaseURL')) {
            return $shop->getBaseURL(true) . 'facebook_product_feed.xml';
        }
        // Fallback per testing
        return 'https://yourshop.com/facebook_product_feed.xml';
    }

    /**
     * Ottiene il percorso del file feed
     */
    public function getFeedPath()
    {
        return $this->feedPath;
    }

    /**
     * Verifica se il feed esiste
     */
    public function feedExists()
    {
        return file_exists($this->feedPath);
    }

    /**
     * Ottiene informazioni sul feed esistente
     */
    public function getFeedInfo()
    {
        if (!$this->feedExists()) {
            return [
                'exists' => false,
                'message' => 'Feed non ancora generato'
            ];
        }

        $fileTime = filemtime($this->feedPath);
        $fileSize = filesize($this->feedPath);

        // Conta prodotti nel feed
        $productsCount = 0;
        if ($fileSize > 0) {
            $xml = @simplexml_load_file($this->feedPath);
            if ($xml && isset($xml->channel->item)) {
                $productsCount = count($xml->channel->item);
            }
        }

        return [
            'exists' => true,
            'path' => $this->feedPath,
            'url' => $this->getFeedUrl(),
            'last_update' => date('Y-m-d H:i:s', $fileTime),
            'size' => $this->formatBytes($fileSize),
            'products_count' => $productsCount
        ];
    }

    /**
     * Formatta i byte in formato leggibile
     */
    private function formatBytes($bytes)
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Elimina il feed
     */
    public function deleteFeed()
    {
        if ($this->feedExists()) {
            return @unlink($this->feedPath);
        }
        return true;
    }
}
