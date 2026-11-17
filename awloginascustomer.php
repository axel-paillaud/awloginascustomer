<?php
/**
 * @author    Axelweb <contact@axelweb.fr>
 * @copyright 2026 Axelweb
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Axelweb
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class AwLoginAsCustomer extends Module
{
    public function __construct()
    {
        $this->name = 'awloginascustomer';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Axelweb';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Login as customer', [], 'Modules.Awloginascustomer.Admin');
        $this->description = $this->trans('Allows employees to log in to the shop as a customer from the back-office.', [], 'Modules.Awloginascustomer.Admin');

        $this->ps_versions_compliancy = [
            'min' => '9.0.0',
            'max' => _PS_VERSION_,
        ];
    }

    /**
     * Use the Symfony translation system (domain Modules.Awloginascustomer.*).
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    /**
     * Install the module.
     *
     * @return bool
     */
    public function install(): bool
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install();
    }
}
