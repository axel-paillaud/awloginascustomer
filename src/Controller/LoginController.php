<?php
/**
 * @author    Axelweb <contact@axelweb.fr>
 * @copyright 2026 Axelweb
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Axelweb
 */
declare(strict_types=1);

namespace Axelweb\AwLoginAsCustomer\Controller;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Axelweb\AwLoginAsCustomer\Security\LoginToken;
use Context;
use Customer;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use PrestaShopLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Validate;

/**
 * Back-office entry point of the "Login as customer" buttons.
 */
class LoginController extends PrestaShopAdminController
{
    /**
     * Check the customer, then redirect to the front-office login link.
     *
     * The link is generated here, at click time, so its short lifetime
     * starts when the employee actually opens it.
     *
     * @param Request $request
     * @param int $customerId
     *
     * @return RedirectResponse
     */
    #[AdminSecurity("is_granted('update', 'AdminCustomers')", redirectRoute: 'admin_customers_index')]
    public function login(Request $request, int $customerId): RedirectResponse
    {
        $customer = new Customer($customerId);

        $error = match (true) {
            !Validate::isLoadedObject($customer) || $customer->deleted => $this->trans('This customer does not exist.', [], 'Modules.Awloginascustomer.Admin'),
            $customer->isGuest() => $this->trans('This is a guest account: convert it to a customer account first.', [], 'Modules.Awloginascustomer.Admin'),
            !$customer->active => $this->trans('This customer account is disabled.', [], 'Modules.Awloginascustomer.Admin'),
            default => null,
        };

        if (null !== $error) {
            $this->addFlash('error', $error);

            return $this->redirect(
                $request->headers->get('referer') ?: $this->generateUrl('admin_customers_index')
            );
        }

        $employeeId = (int) $this->getEmployeeContext()->getEmployee()?->getId();

        PrestaShopLogger::addLog(
            sprintf('Login as customer #%d', $customerId),
            1,
            null,
            'Customer',
            $customerId,
            true,
            $employeeId
        );

        $params = (new LoginToken(_COOKIE_KEY_))->create($customerId, $employeeId, time());

        return $this->redirect(Context::getContext()->link->getModuleLink(
            'awloginascustomer',
            'login',
            $params,
            true,
            (int) $customer->id_lang,
            (int) $customer->id_shop
        ));
    }
}
