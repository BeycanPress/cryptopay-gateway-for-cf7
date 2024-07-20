<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\CF7;

use BeycanPress\CryptoPay\Integrator\Hook;
use BeycanPress\CryptoPay\Integrator\Helpers;

class Loader
{
    /**
     * Loader constructor.
     */
    public function __construct()
    {
        Helpers::registerIntegration('cf7');

        // add transaction page
        Helpers::createTransactionPage(
            esc_html__('Contact Form 7 Transactions', 'cf7-cryptopay'),
            'cf7',
            10,
            [],
            ['orderId']
        );

        Hook::addFilter('edit_config_data_cf7', [$this, 'disableReminderEmail']);
        Hook::addFilter('payment_redirect_urls_cf7', [$this, 'paymentRedirectUrls']);

        if (Helpers::exists()) {
            new Gateways\GatewayPro();
        } elseif (Helpers::liteExists()) {
            new Gateways\GatewayLite();
        }
    }

    /**
     * @param object $data
     * @return object
     */
    public function disableReminderEmail(object $data): object
    {
        return $data->disableReminderEmail();
    }

    /**
     * @param object $data
     * @return array<string>
     */
    public function paymentRedirectUrls(object $data): array
    {
        return [
            'success' => '#success',
            'failed' => '#failed'
        ];
    }
}
