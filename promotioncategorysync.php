<?php
/**
 * Dolce&Zampa - Promotion Category Sync Module
 * 
 * Modulo proprietario di Dolce&Zampa per la gestione automatica
 * dell'assegnazione della categoria "promozione" ai prodotti in promozione
 * 
 * @author Dolce&Zampa
 * @copyright 2024 Dolce&Zampa
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PromotionCategorySync extends Module
{
    const PROMOTION_CATEGORY_NAME = 'Promozioni';
    
    public function __construct()
    {
        $this->name = 'promotioncategorysync';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Dolce&Zampa';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Dolce&Zampa - Sincronizzazione Categoria Promozioni');
        $this->description = $this->l('Modulo Dolce&Zampa per sincronizzare automaticamente i prodotti in promozione con la categoria Promozioni');
        $this->confirmUninstall = $this->l('Sei sicuro di voler disinstallare il modulo Dolce&Zampa Promotion Sync?');
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('actionCronJob')) {
            return false;
        }

        // Crea la categoria "Promozioni" se non esiste
        $this->createPromotionCategory();

        return true;
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Crea la categoria "Promozioni" se non esiste
     */
    private function createPromotionCategory()
    {
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $idShop = (int) Context::getContext()->shop->id;
        
        // Verifica se la categoria esiste già
        $categoryId = $this->getPromotionCategoryId();
        
        if (!$categoryId) {
            $category = new Category();
            $category->name = array_fill_keys(Language::getIDs(false), self::PROMOTION_CATEGORY_NAME);
            $category->link_rewrite = array_fill_keys(Language::getIDs(false), Tools::str2url(self::PROMOTION_CATEGORY_NAME));
            $category->id_parent = (int) Configuration::get('PS_HOME_CATEGORY');
            $category->active = 1;
            $category->add();
            
            Configuration::updateValue('PROMOTION_CATEGORY_ID', $category->id);
        }
    }

    /**
     * Ottiene l'ID della categoria promozioni
     */
    public function getPromotionCategoryId()
    {
        $categoryId = Configuration::get('PROMOTION_CATEGORY_ID');
        
        if ($categoryId && Validate::isLoadedObject(new Category($categoryId))) {
            return (int) $categoryId;
        }
        
        // Cerca per nome
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $sql = 'SELECT c.id_category 
                FROM ' . _DB_PREFIX_ . 'category c
                INNER JOIN ' . _DB_PREFIX_ . 'category_lang cl ON (c.id_category = cl.id_category)
                WHERE cl.name = "' . pSQL(self::PROMOTION_CATEGORY_NAME) . '"
                AND cl.id_lang = ' . $idLang ;
        
        $result = Db::getInstance()->getValue($sql);
        
        if ($result) {
            Configuration::updateValue('PROMOTION_CATEGORY_ID', $result);
            return (int) $result;
        }
        
        return false;
    }

    /**
     * Sincronizza i prodotti in promozione con la categoria
     */
    public function syncPromotionProducts()
    {
        $categoryId = $this->getPromotionCategoryId();
        
        if (!$categoryId) {
            return [
                'success' => false,
                'message' => 'Categoria promozioni non trovata'
            ];
        }

        $currentDate = date('Y-m-d H:i:s');
        $stats = [
            'added' => 0,
            'removed' => 0,
            'errors' => 0
        ];

        // Trova tutti i prodotti con promozioni attive
        // Imposta la modalità SQL per gestire le date zero
        Db::getInstance()->execute('SET sql_mode = REPLACE(@@sql_mode, "NO_ZERO_DATE,", "")');
        Db::getInstance()->execute('SET sql_mode = REPLACE(@@sql_mode, "NO_ZERO_IN_DATE,", "")');
        
        $sql = 'SELECT DISTINCT sp.id_product
                FROM ' . _DB_PREFIX_ . 'specific_price sp
                WHERE sp.reduction > 0
                AND (sp.from = "0000-00-00 00:00:00" OR sp.from <= "' . pSQL($currentDate) . '")
                AND (sp.to = "0000-00-00 00:00:00" OR sp.to >= "' . pSQL($currentDate) . '")';
        
        $productsInPromotion = Db::getInstance()->executeS($sql);
        $promotionProductIds = [];
        
        if ($productsInPromotion) {
            foreach ($productsInPromotion as $row) {
                $promotionProductIds[] = (int) $row['id_product'];
            }
        }

        // Ottieni tutti i prodotti attualmente nella categoria promozioni
        $category = new Category($categoryId);
        $currentProducts = $category->getProducts(
            (int) Configuration::get('PS_LANG_DEFAULT'),
            1,
            10000,
            null,
            null,
            false
        );
        
        $currentProductIds = [];
        foreach ($currentProducts as $product) {
            $currentProductIds[] = (int) $product['id_product'];
        }

        // Aggiungi prodotti in promozione che non sono nella categoria
        foreach ($promotionProductIds as $productId) {
            if (!in_array($productId, $currentProductIds)) {
                $product = new Product($productId);
                if (Validate::isLoadedObject($product)) {
                    if ($product->addToCategories([$categoryId])) {
                        $stats['added']++;
                    } else {
                        $stats['errors']++;
                    }
                }
            }
        }

        // Rimuovi prodotti dalla categoria se non sono più in promozione
        foreach ($currentProductIds as $productId) {
            if (!in_array($productId, $promotionProductIds)) {
                $product = new Product($productId);
                if (Validate::isLoadedObject($product)) {
                    // Rimuovi solo la categoria promozioni, mantieni le altre
                    $categories = $product->getCategories();
                    $key = array_search($categoryId, $categories);
                    if ($key !== false) {
                        unset($categories[$key]);
                        if ($product->updateCategories($categories)) {
                            $stats['removed']++;
                        } else {
                            $stats['errors']++;
                        }
                    }
                }
            }
        }

        return [
            'success' => true,
            'stats' => $stats,
            'message' => sprintf(
                'Sincronizzazione completata: %d prodotti aggiunti, %d rimossi, %d errori',
                $stats['added'],
                $stats['removed'],
                $stats['errors']
            )
        ];
    }

    /**
     * Hook per cron job
     */
    public function hookActionCronJob()
    {
        return $this->syncPromotionProducts();
    }

    /**
     * Configurazione del modulo
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitSyncNow')) {
            $result = $this->syncPromotionProducts();
            
            if ($result['success']) {
                $output .= $this->displayConfirmation($result['message']);
            } else {
                $output .= $this->displayError($result['message']);
            }
        }

        return $output . $this->renderForm();
    }

    /**
     * Form di configurazione
     */
    public function renderForm()
    {
        $categoryId = $this->getPromotionCategoryId();
        $category = new Category($categoryId);
        
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Dolce&Zampa - Sincronizzazione Categoria Promozioni'),
                    'icon' => 'icon-refresh'
                ],
                'description' => $this->l('Modulo Dolce&Zampa che sincronizza automaticamente i prodotti in promozione con la categoria "Promozioni".'),
                'input' => [
                    [
                        'type' => 'html',
                        'name' => 'info',
                        'html_content' => '<div class="alert alert-info">' .
                            '<h4>' . $this->l('Informazioni') . '</h4>' .
                            '<p><strong>' . $this->l('Categoria Promozioni:') . '</strong> ' . 
                            ($categoryId ? $category->name[(int) Configuration::get('PS_LANG_DEFAULT')] . ' (ID: ' . $categoryId . ')' : $this->l('Non trovata')) . '</p>' .
                            '<p><strong>' . $this->l('URL Cron:') . '</strong> ' . 
                            $this->context->link->getModuleLink($this->name, 'cron', ['token' => substr(Tools::hash($this->name . '/cron'), 0, 10)]) . '</p>' .
                            '<p>' . $this->l('Usa questo URL per configurare un cron job che esegua la sincronizzazione automaticamente.') . '</p>' .
                            '<p>' . $this->l('Esempio crontab: ') . '<code>0 */4 * * * wget -q -O /dev/null "' . $this->context->link->getModuleLink($this->name, 'cron', ['token' => substr(Tools::hash($this->name . '/cron'), 0, 10)]) . '"</code></p>' .
                            '</div>'
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Sincronizza Ora'),
                    'name' => 'submitSyncNow',
                    'icon' => 'process-icon-refresh'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitSyncNow';
        $helper->title = $this->displayName;

        return $helper->generateForm([$fieldsForm]);
    }
}
