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

use PrestaShop\PrestaShop\Core\Action\ActionsBarButton;
use PrestaShop\PrestaShop\Core\Action\ActionsBarButtonsCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface;

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

        $installed = parent::install()
            && $this->registerHook([
                'actionGetAdminOrderButtons',
                'actionCustomerGridDefinitionModifier',
                'displayAdminCustomers',
            ]);

        // Prevent 'Unable to generate a URL for the named route [...]' error,
        // clear Symfony cache
        if ($installed) {
            Tools::clearSf2Cache();
        }

        return $installed;
    }

    /**
     * Add a "Login as customer" button to the action bar of the order page.
     *
     * @param array{id_order: int, actions_bar_buttons_collection: ActionsBarButtonsCollection} $params
     *
     * @return void
     */
    public function hookActionGetAdminOrderButtons(array $params): void
    {
        if (!$this->canLoginAsCustomer()) {
            return;
        }

        $order = new Order((int) $params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        $params['actions_bar_buttons_collection']->add(new ActionsBarButton(
            'btn-action',
            [
                'href' => $this->getLoginUrl((int) $order->id_customer),
                'target' => '_blank',
                'rel' => 'noopener',
            ],
            '<i class="material-icons">login</i> ' . htmlspecialchars($this->getButtonLabel(), ENT_QUOTES)
        ));
    }

    /**
     * Add a "Login as customer" row action to the back-office customer list.
     *
     * @param array{definition: GridDefinitionInterface} $params
     *
     * @return void
     */
    public function hookActionCustomerGridDefinitionModifier(array $params): void
    {
        if (!$this->canLoginAsCustomer()) {
            return;
        }

        $actionsColumn = null;
        foreach ($params['definition']->getColumns() as $column) {
            if ('actions' === $column->getId()) {
                $actionsColumn = $column;
                break;
            }
        }

        if (null === $actionsColumn) {
            return;
        }

        $actionsColumn->getOption('actions')->add(
            (new LinkRowAction('awloginascustomer'))
                ->setName($this->getButtonLabel())
                ->setIcon('login')
                ->setOptions([
                    'route' => 'awloginascustomer_login',
                    'route_param_name' => 'customerId',
                    'route_param_field' => 'id_customer',
                    'target' => '_blank',
                ])
        );
    }

    /**
     * Display a "Login as customer" card on the customer page.
     *
     * The customer page has no hook for its toolbar buttons: this block is
     * rendered by displayAdminCustomers, below the customer information.
     *
     * @param array{id_customer: int} $params
     *
     * @return string
     */
    public function hookDisplayAdminCustomers(array $params): string
    {
        if (!$this->canLoginAsCustomer()) {
            return '';
        }

        $customer = new Customer((int) $params['id_customer']);
        if (!Validate::isLoadedObject($customer)) {
            return '';
        }

        $this->context->smarty->assign([
            'awloginascustomer' => [
                'url' => $this->getLoginUrl((int) $customer->id),
                'label' => $this->getButtonLabel(),
                'is_guest' => $customer->isGuest(),
                'is_active' => (bool) $customer->active,
            ],
        ]);

        return $this->fetch('module:awloginascustomer/views/templates/hook/displayAdminCustomers.tpl');
    }

    /**
     * Same right as the controller: edit on the customers tab.
     *
     * @return bool
     */
    private function canLoginAsCustomer(): bool
    {
        $employee = $this->context->employee;

        return Validate::isLoadedObject($employee) && $employee->can('edit', 'AdminCustomers');
    }

    /**
     * Back-office URL that generates the front-office login link.
     *
     * @param int $customerId
     *
     * @return string
     */
    private function getLoginUrl(int $customerId): string
    {
        return $this->get('router')->generate('awloginascustomer_login', ['customerId' => $customerId]);
    }

    /**
     * @return string
     */
    private function getButtonLabel(): string
    {
        return $this->trans('Login as customer', [], 'Modules.Awloginascustomer.Admin');
    }
}
