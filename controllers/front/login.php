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

use Axelweb\AwLoginAsCustomer\Security\LoginToken;

/**
 * Front-office target of the back-office login link: checks the token and
 * logs the visitor in as the customer.
 */
class AwLoginAsCustomerLoginModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $ssl = true;

    /**
     * Log in as the customer, then redirect to their account.
     *
     * @return void
     */
    public function postProcess(): void
    {
        $customerId = (int) Tools::getValue('id_customer');
        $employeeId = (int) Tools::getValue('id_employee');

        $isValid = (new LoginToken(_COOKIE_KEY_))->isValid(
            $customerId,
            $employeeId,
            (int) Tools::getValue('expires'),
            (string) Tools::getValue('token'),
            time()
        );

        $customer = new Customer($customerId);
        $employee = new Employee($employeeId);

        if (!$isValid
            || !Validate::isLoadedObject($customer) || $customer->deleted || !$customer->active || $customer->isGuest()
            || !Validate::isLoadedObject($employee) || !$employee->active
        ) {
            $this->errors[] = $this->trans(
                'This login link is invalid or has expired. Click the button in the back-office again.',
                [],
                'Modules.Awloginascustomer.Shop'
            );
            $this->redirectWithNotifications($this->context->link->getPageLink('authentication'));
        }

        // Drop the current visitor (and their cart): a cart left by a previous
        // customer must never be handed over to this one.
        $this->context->cookie->mylogout();
        $this->context->cart = new Cart();

        $this->context->updateCustomer($customer);

        Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);

        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);

        Tools::redirect($this->context->link->getPageLink('my-account'));
    }
}
