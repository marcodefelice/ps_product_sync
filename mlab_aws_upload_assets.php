<?php

/**
 * Prestashop Module to manage cookie policy banner
 * @author mlabfactory <tech@mlabfactory.com>
 * MlabPs - Cookie Policy Module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Carica l'autoloader di Composer PRIMA di qualsiasi use statement
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Autoloader alternativo manuale - CORRETTO IL NAMESPACE
    spl_autoload_register(function ($className) {
        $prefix = 'MlabPs\\CookiePolicyModule\\';
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
use MlabPs\CookiePolicyModule\Controllers\ModuleController;


class mlab_aws_upload_assets extends Module
{
    private $moduleController;

    public function __construct()
    {
        $this->name = 'mlab_aws_upload_assets';
        $this->tab = 'back_office_features';
        $this->version = '1.0.0';
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

        $this->displayName = $this->l('MLab AWS Upload Assets Module');
        $this->description = $this->l('Upload assets to AWS S3');
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
            $this->moduleController = new ModuleController($this, $this->name, $this->_path);
        } catch (Exception $e) {
            // Log dell'errore per debug
            error_log("Errore inizializzazione controller: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ottiene il widget controller, inizializzandolo se necessario
     */
    private function getModuleController(): ModuleController
    {
        if (!$this->moduleController) {
            $this->initializeControllers();
        }
        return $this->moduleController;
    }

    public function install()
    {
        // Check if compiled JavaScript exists
        $jsFile = $this->_path . 'assets/js/cookie-policy.js';
        if (!file_exists($jsFile) || filesize($jsFile) === 0) {
            // Try to compile TypeScript if possible
            if (!$this->compileTypeScript()) {
                // If compilation fails, show error
                $this->_errors[] = $this->l('Cookie policy JavaScript file is missing. Please compile TypeScript first: cd modules/mlab_cookie_policy && npx tsc');
                return false;
            }
        }
        
        return parent::install() &&
            $this->registerHook('displayHeader') && // Per includere CSS/JS
            $this->registerHook('displayFooter') && // Banner nel footer
            $this->registerHook('displayFooterAfter'); // Banner dopo il footer
    }

    /**
     * Try to compile TypeScript to JavaScript
     * @return bool True if compilation succeeded or was not needed, false if required but failed
     */
    private function compileTypeScript(): bool
    {
        $tsFile = $this->_path . 'assets/ts/cookie-policy.ts';
        $jsFile = $this->_path . 'assets/js/cookie-policy.js';
        
        // Check if TypeScript file exists
        if (!file_exists($tsFile)) {
            return false;
        }
        
        // Check if node and npx are available
        $nodeCheck = shell_exec('which node 2>/dev/null');
        $npxCheck = shell_exec('which npx 2>/dev/null');
        
        if (empty($nodeCheck) || empty($npxCheck)) {
            // Node.js not available, cannot compile
            return false;
        }
        
        // Try to compile
        $output = [];
        $returnCode = 0;
        
        chdir($this->_path);
        exec('npx tsc 2>&1', $output, $returnCode);
        
        // Check if compilation was successful
        if ($returnCode === 0 && file_exists($jsFile) && filesize($jsFile) > 0) {
            return true;
        }
        
        return false;
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Hook per aggiungere CSS e JS nel header
     */
    public function hookDisplayHeader($params)
    {
        return $this->getModuleController()->handleDisplayHeader();
    }

    /**
     * Hook per mostrare il banner nel footer
     */
    public function hookDisplayFooter($params)
    {
        return $this->getModuleController()->handleDisplayFooter();
    }

    /**
     * Hook per mostrare il banner dopo il footer
     */
    public function hookDisplayFooterAfter($params)
    {
        return $this->getModuleController()->handleDisplayFooter();
    }

    /**
     * Configurazione del modulo
     */
    public function getContent()
    {
        return $this->getModuleController()->handleConfiguration();
    }
}