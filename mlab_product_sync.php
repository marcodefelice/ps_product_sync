<?php

/**
 * Prestashop Module to sync products with Google Merchant Center
 * @author mlabfactory <tech@mlabfactory.com>
 * MlabPs - Product Sync Module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Carica l'autoloader di Composer PRIMA di qualsiasi use statement
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Autoloader alternativo manuale
    spl_autoload_register(function ($className) {
        $prefix = 'MlabPs\\ProductSync\\';
        $base_dir = __DIR__ . '/src/';
        
        $len = strlen($prefix);
        if (strncmp($prefix, $className, $len) !== 0) {
            return;
        }
        
        $relative_class = substr($className, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    });
}

// IMPORTANTE: use statement DOPO l'autoloader
use MlabPs\ProductSync\Controllers\ProductSyncController;


class mlab_product_sync extends Module
{
    private $syncController;

    public function __construct()
    {
        $this->name = 'mlab_product_sync';
        $this->tab = 'back_office_features';
        $this->version = '1.1.0';
        $this->author = 'mlabfactory';
        $this->need_instance = 0;
        $this->_path = dirname(__FILE__) . '/';
        
        // Compatibilità PS9
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '9.99.99'
        ];
        
        $this->bootstrap = true;

        // Chiamata SEMPRE sicura al parent constructor
        parent::__construct();

        $this->displayName = $this->l('MLab Product Sync Module');
        $this->description = $this->l('Sync products with Google Merchant Center');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        // Inizializza i controller solo dopo il parent constructor
        $this->initializeControllers();
    }

    /**
     * Inizializza i controller del modulo
     */
    private function initializeControllers()
    {
        try {
            $this->syncController = new ProductSyncController($this);
        } catch (Exception $e) {
            // Log dell'errore per debug
            error_log("Errore inizializzazione controller: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ottiene il controller, inizializzandolo se necessario
     */
    private function getSyncController(): ProductSyncController
    {
        if (!$this->syncController) {
            $this->initializeControllers();
        }
        return $this->syncController;
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionProductAdd') && 
            $this->registerHook('actionProductUpdate') && 
            $this->registerHook('actionProductDelete') &&
            $this->registerHook('actionUpdateQuantity') &&
            Configuration::updateValue('MLAB_GMC_AUTO_SYNC', 1) &&
            Configuration::updateValue('MLAB_GMC_MERCHANT_ID', '') &&
            Configuration::updateValue('MLAB_GMC_CREDENTIALS', '');
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            Configuration::deleteByName('MLAB_GMC_AUTO_SYNC') &&
            Configuration::deleteByName('MLAB_GMC_MERCHANT_ID') &&
            Configuration::deleteByName('MLAB_GMC_CREDENTIALS');
    }

    /**
     * Hook chiamato quando viene aggiunto un prodotto
     */
    public function hookActionProductAdd($params)
    {
        return $this->getSyncController()->handleProductAdd($params);
    }

    /**
     * Hook chiamato quando viene aggiornato un prodotto
     */
    public function hookActionProductUpdate($params)
    {
        return $this->getSyncController()->handleProductUpdate($params);
    }

    /**
     * Hook chiamato quando viene eliminato un prodotto
     */
    public function hookActionProductDelete($params)
    {
        return $this->getSyncController()->handleProductDelete($params);
    }

    /**
     * Hook chiamato quando cambia la quantità
     */
    public function hookActionUpdateQuantity($params)
    {
        return $this->getSyncController()->handleQuantityUpdate($params);
    }

    /**
     * Configurazione del modulo
     */
    public function getContent()
    {
        return $this->getSyncController()->handleConfiguration();
    }
}