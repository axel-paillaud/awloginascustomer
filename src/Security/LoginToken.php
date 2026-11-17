<?php
/**
 * @author    Axelweb <contact@axelweb.fr>
 * @copyright 2026 Axelweb
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Axelweb
 */
declare(strict_types=1);

namespace Axelweb\AwLoginAsCustomer\Security;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Signed, short-lived token carried by the front-office login link.
 */
final class LoginToken
{
    /**
     * Validity of a login link, in seconds.
     */
    public const TTL = 60;

    /**
     * @param string $secret Shop secret key (_COOKIE_KEY_)
     */
    public function __construct(private readonly string $secret)
    {
    }

    /**
     * Build the query parameters of a login link.
     *
     * @param int $customerId Customer to log in as
     * @param int $employeeId Employee who asked for the link
     * @param int $now Current timestamp
     *
     * @return array{id_customer: int, id_employee: int, expires: int, token: string}
     */
    public function create(int $customerId, int $employeeId, int $now): array
    {
        $expires = $now + self::TTL;

        return [
            'id_customer' => $customerId,
            'id_employee' => $employeeId,
            'expires' => $expires,
            'token' => $this->sign($customerId, $employeeId, $expires),
        ];
    }

    /**
     * Check the signature and the expiry date of a login link.
     *
     * @param int $customerId Customer ID from the link
     * @param int $employeeId Employee ID from the link
     * @param int $expires Expiry timestamp from the link
     * @param string $token Signature from the link
     * @param int $now Current timestamp
     *
     * @return bool
     */
    public function isValid(int $customerId, int $employeeId, int $expires, string $token, int $now): bool
    {
        if ($customerId <= 0 || $employeeId <= 0 || $expires < $now || $expires > $now + self::TTL) {
            return false;
        }

        return hash_equals($this->sign($customerId, $employeeId, $expires), $token);
    }

    /**
     * @param int $customerId
     * @param int $employeeId
     * @param int $expires
     *
     * @return string
     */
    private function sign(int $customerId, int $employeeId, int $expires): string
    {
        return hash_hmac(
            'sha256',
            'awloginascustomer|' . $customerId . '|' . $employeeId . '|' . $expires,
            $this->secret
        );
    }
}
