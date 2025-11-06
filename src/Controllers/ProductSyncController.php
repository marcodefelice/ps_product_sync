<?php

namespace MlabPs\ProductSync\Controllers;

use MlabPs\ProductSync\Services\SyncService;
use Configuration;
use Tools;

/**
 * Controller per gestire le azioni di sincronizzazione
 */
class ProductSyncController
{
    private $module;
    private $syncService;

    public function __construct($module)
    {
        $this->module = $module;
        $this->syncService = new SyncService();
    }

    /**
     * Gestisce l'aggiunta di un prodotto
     */
    public function handleProductAdd($params)
    {
        if (!isset($params['id_product'])) {
            return false;
        }

        $result = $this->syncService->syncProduct($params['id_product']);
        
        if (!$result['success']) {
            error_log("Errore sync prodotto {$params['id_product']}: " . ($result['error'] ?? $result['message']));
        }

        return true;
    }

    /**
     * Gestisce l'aggiornamento di un prodotto
     */
    public function handleProductUpdate($params)
    {
        if (!isset($params['id_product'])) {
            return false;
        }

        $result = $this->syncService->syncProduct($params['id_product']);
        
        if (!$result['success']) {
            error_log("Errore sync prodotto {$params['id_product']}: " . ($result['error'] ?? $result['message']));
        }

        return true;
    }

    /**
     * Gestisce l'eliminazione di un prodotto
     */
    public function handleProductDelete($params)
    {
        if (!isset($params['id_product'])) {
            return false;
        }

        $result = $this->syncService->deleteProduct($params['id_product']);
        
        if (!$result['success']) {
            error_log("Errore eliminazione prodotto {$params['id_product']}: " . ($result['error'] ?? $result['message']));
        }

        return true;
    }

    /**
     * Gestisce l'aggiornamento della quantità
     */
    public function handleQuantityUpdate($params)
    {
        if (!isset($params['id_product'])) {
            return false;
        }

        $result = $this->syncService->updateProductQuantity($params['id_product']);
        
        if (!$result['success']) {
            error_log("Errore aggiornamento quantità prodotto {$params['id_product']}: " . ($result['error'] ?? $result['message']));
        }

        return true;
    }

    /**
     * Gestisce la pagina di configurazione del modulo
     */
    public function handleConfiguration()
    {
        $output = '';

        // Processa il form se è stato inviato
        if (Tools::isSubmit('submit' . $this->module->name)) {
            $output .= $this->processConfigurationForm();
        }

        // Sincronizzazione manuale di tutti i prodotti
        if (Tools::isSubmit('syncAllProducts')) {
            $output .= $this->processSyncAllProducts();
        }

        // Mostra il form di configurazione
        $output .= $this->displayConfigurationForm();

        return $output;
    }

    /**
     * Processa il form di configurazione
     */
    private function processConfigurationForm()
    {
        Configuration::updateValue('MLAB_GMC_AUTO_SYNC', Tools::getValue('MLAB_GMC_AUTO_SYNC'));
        Configuration::updateValue('MLAB_GMC_MERCHANT_ID', Tools::getValue('MLAB_GMC_MERCHANT_ID'));
        
        // Gestione file JSON per le credenziali
        if (isset($_FILES['MLAB_GMC_CREDENTIALS_FILE']) && $_FILES['MLAB_GMC_CREDENTIALS_FILE']['size'] > 0) {
            $credentialsJson = file_get_contents($_FILES['MLAB_GMC_CREDENTIALS_FILE']['tmp_name']);
            Configuration::updateValue('MLAB_GMC_CREDENTIALS', $credentialsJson);
        }

        return $this->module->displayConfirmation($this->module->l('Settings updated'));
    }

    /**
     * Processa la sincronizzazione di tutti i prodotti
     */
    private function processSyncAllProducts()
    {
        $result = $this->syncService->syncAllProducts();

        if ($result['success'] === false) {
            return $this->module->displayError($this->module->l('Error: ') . $result['message']);
        }

        $message = sprintf(
            $this->module->l('Sync completed: %d products synchronized, %d failed'),
            $result['success'],
            $result['failed']
        );

        return $this->module->displayConfirmation($message);
    }

    /**
     * Mostra il form di configurazione
     */
    private function displayConfigurationForm()
    {
        $configStatus = $this->syncService->checkConfiguration();

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Google Merchant Center Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Enable automatic sync'),
                        'name' => 'MLAB_GMC_AUTO_SYNC',
                        'is_bool' => true,
                        'desc' => $this->module->l('Automatically sync products when they are created or updated'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->module->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->module->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Merchant ID'),
                        'name' => 'MLAB_GMC_MERCHANT_ID',
                        'size' => 20,
                        'required' => true,
                        'desc' => $this->module->l('Your Google Merchant Center ID')
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->module->l('Service Account Credentials'),
                        'name' => 'MLAB_GMC_CREDENTIALS_FILE',
                        'desc' => $this->module->l('Upload the JSON credentials file from Google Cloud Console')
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ]
            ],
        ];

        $helper = new \HelperForm();
        $helper->module = $this->module;
        $helper->name_controller = $this->module->name;
        $helper->token = \Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = \AdminController::$currentIndex . '&configure=' . $this->module->name;
        $helper->submit_action = 'submit' . $this->module->name;
        $helper->default_form_language = (int)\Configuration::get('PS_LANG_DEFAULT');

        $helper->fields_value['MLAB_GMC_AUTO_SYNC'] = Configuration::get('MLAB_GMC_AUTO_SYNC');
        $helper->fields_value['MLAB_GMC_MERCHANT_ID'] = Configuration::get('MLAB_GMC_MERCHANT_ID');

        $output = $helper->generateForm([$fields_form]);

        // Status panel
        $statusHtml = '<div class="panel">';
        $statusHtml .= '<div class="panel-heading">' . $this->module->l('Configuration Status') . '</div>';
        $statusHtml .= '<div class="panel-body">';
        $statusHtml .= '<ul>';
        $statusHtml .= '<li>' . $this->module->l('Auto Sync: ') . ($configStatus['auto_sync'] ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-danger">Disabled</span>') . '</li>';
        $statusHtml .= '<li>' . $this->module->l('Merchant ID: ') . ($configStatus['merchant_id'] ? '<span class="badge badge-success">Set</span>' : '<span class="badge badge-danger">Not Set</span>') . '</li>';
        $statusHtml .= '<li>' . $this->module->l('Credentials: ') . ($configStatus['has_credentials'] ? '<span class="badge badge-success">Uploaded</span>' : '<span class="badge badge-danger">Missing</span>') . '</li>';
        $statusHtml .= '<li>' . $this->module->l('Service Status: ') . ($configStatus['is_configured'] ? '<span class="badge badge-success">Configured</span>' : '<span class="badge badge-danger">Not Configured</span>') . '</li>';
        $statusHtml .= '</ul>';
        $statusHtml .= '</div>';
        $statusHtml .= '</div>';

        // Manual sync button
        $syncHtml = '<div class="panel">';
        $syncHtml .= '<div class="panel-heading">' . $this->module->l('Manual Synchronization') . '</div>';
        $syncHtml .= '<div class="panel-body">';
        $syncHtml .= '<form method="post" action="' . \AdminController::$currentIndex . '&configure=' . $this->module->name . '&token=' . \Tools::getAdminTokenLite('AdminModules') . '">';
        $syncHtml .= '<button type="submit" name="syncAllProducts" class="btn btn-primary">';
        $syncHtml .= '<i class="icon-refresh"></i> ' . $this->module->l('Sync All Products Now');
        $syncHtml .= '</button>';
        $syncHtml .= '<p class="help-block">' . $this->module->l('Manually synchronize all active products with Google Merchant Center') . '</p>';
        $syncHtml .= '</form>';
        $syncHtml .= '</div>';
        $syncHtml .= '</div>';

        return $statusHtml . $output . $syncHtml;
    }
}
