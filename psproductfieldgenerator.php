<?php

use PsProductFieldGenerator\Infrastructure\Hook\Listener\PfgActionProductAdded;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class psproductfieldgenerator extends Module
{
    const MODULE_NAME = 'psproductfieldgenerator';

    public function __construct()
    {
        $this->name = 'psproductfieldgenerator';
        $this->tab = 'administration';
        $this->version = '0.0.1';
        $this->author = '';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => '1.7.99',
        ];

        parent::__construct();

        $this->displayName = $this->l('Product field generator');
        $this->description = $this->l('Will generate some products field automatically.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionProductSave')
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('actionProductUpdate');
    }

    public function uninstall()
    {
        return (
            parent::uninstall()
            && Configuration::deleteByName('PFG_CONFIG_FIELD_REFERENCE')
            && Configuration::deleteByName('PFG_CONFIG_REFERENCE_PREFIX')
            && Configuration::deleteByName('PFG_CONFIG_FIELD_EAN13')
        );
    }

    public function hookActionProductSave(array $params)
    {
        $this->executeProductHook($params);
    }

    public function hookActionProductUpdate(array $params)
    {
        $this->executeProductHook($params);
    }

    public function hookActionProductAdd(array $params)
    {
        $this->executeProductHook($params);
    }

    private function executeProductHook(array $params)
    {
        /** @var \PrestaShop\PrestaShop\Core\Domain\Product\CommandHandler\UpdateProductDetailsHandlerInterface $handler */
        $handler = $this->get('prestashop.adapter.product.command_handler.update_product_details_handler');
        PfgActionProductAdded::create($handler)->setPrefix(Configuration::get('PFG_CONFIG_REFERENCE_PREFIX'))->execute($params['id_product'], $params['product']);
    }

    /**
     * This method handles the module's configuration page
     * @return string The page's HTML content
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $isFieldReferenceEnable = (int)Tools::getValue('PFG_FIELD_REFERENCE');
            $fieldReferencePrefix = (string)Tools::getValue('PFG_REFERENCE_PREFIX');
            $isEanEnable = (int)Tools::getValue('PFG_FIELD_EAN13');

            // value is ok, update it and display a confirmation message
            Configuration::updateValue('PFG_CONFIG_FIELD_REFERENCE', $isFieldReferenceEnable);
            Configuration::updateValue('PFG_CONFIG_REFERENCE_PREFIX', $fieldReferencePrefix);
            Configuration::updateValue('PFG_CONFIG_FIELD_EAN13', $isEanEnable);

            $output = $this->displayConfirmation($this->l('Settings updated'));
        }

        // display any message, then the form
        return $output . $this->displayForm();
    }

    /**
     * Builds the configuration form
     * @return string HTML code
     */
    public function displayForm()
    {
        // Init Fields form array
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Fields'),
                ],
                'input' => [
                    [
                        'type' => 'radio',
                        'label' => $this->l('Product Reference'),
                        'name' => 'PFG_FIELD_REFERENCE',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('ON')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('OFF')
                            )
                        ),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Product Reference prefix'),
                        'name' => 'PFG_FIELD_REFERENCE_PREFIX',
                    ],
                    [
                        'type' => 'radio',
                        'label' => $this->l('EAN13'),
                        'name' => 'PFG_FIELD_EAN13',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('ON')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('OFF')
                            )
                        ),

                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->title = $this->l('Configuration of the product fields generator');
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');

        // Load current value into the form
        $helper->fields_value['PFG_FIELD_REFERENCE'] = Tools::getValue('PFG_FIELD_REFERENCE',
            Configuration::get('PFG_CONFIG_FIELD_REFERENCE'));
        $helper->fields_value['PFG_FIELD_REFERENCE_PREFIX'] = Tools::getValue('PFG_FIELD_REFERENCE_PREFIX',
            Configuration::get('PFG_CONFIG_REFERENCE_PREFIX'));
        $helper->fields_value['PFG_FIELD_EAN13'] = Tools::getValue('PFG_FIELD_EAN13',
            Configuration::get('PFG_CONFIG_FIELD_EAN13'));

        return $helper->generateForm([$form]);
    }
}