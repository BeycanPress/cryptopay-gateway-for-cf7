<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

// @phpcs:disable PSR1.Files.SideEffects
// @phpcs:disable PSR12.Files.FileHeader
// @phpcs:disable Generic.Files.InlineHTML
// @phpcs:disable Generic.Files.LineLength

/**
 * Plugin Name: CryptoPay Gateway for Contact Form 7
 * Version:     1.0.2
 * Plugin URI:  https://beycanpress.com/cryptopay/
 * Description: Adds Cryptocurrency payment gateway (CryptoPay) for Contact Form 7.
 * Author:      BeycanPress LLC
 * Author URI:  https://beycanpress.com
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: cryptopay-gateway-for-cf7
 * Domain Path: /languages
 * Tags: Bitcoin, Ethereum, Crypto, Payment, Contact Form 7
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 8.1
*/

// Autoload
require_once __DIR__ . '/vendor/autoload.php';

define('CRYPTOPAY_GATEWAY_FOR_CF7_FILE', __FILE__);
define('CRYPTOPAY_GATEWAY_FOR_CF7_VERSION', '1.0.2');
define('CRYPTOPAY_GATEWAY_FOR_CF7_KEY', basename(__DIR__));
define('CRYPTOPAY_GATEWAY_FOR_CF7_URL', plugin_dir_url(__FILE__));
define('CRYPTOPAY_GATEWAY_FOR_CF7_DIR', plugin_dir_path(__FILE__));
define('CRYPTOPAY_GATEWAY_FOR_CF7_SLUG', plugin_basename(__FILE__));

use BeycanPress\CryptoPay\Integrator\Helpers;

/**
 * @return void
 */
function cf7CryptoPayRegisterModels(): void
{
    Helpers::registerModel(BeycanPress\CryptoPay\CF7\Models\TransactionsPro::class);
    Helpers::registerLiteModel(BeycanPress\CryptoPay\CF7\Models\TransactionsLite::class);
}

cf7CryptoPayRegisterModels();

add_action('plugins_loaded', function (): void {
    cf7CryptoPayRegisterModels();
    if (!defined('WPCF7_VERSION')) {
        Helpers::requirePluginMessage('Contact Form 7', admin_url('plugin-install.php?s=Contact%2520Form%25207&tab=search&type=term'));
    } elseif (Helpers::bothExists()) {
        new BeycanPress\CryptoPay\CF7\Loader();
    } else {
        Helpers::requireCryptoPayMessage('Contact Form 7');
    }
});
