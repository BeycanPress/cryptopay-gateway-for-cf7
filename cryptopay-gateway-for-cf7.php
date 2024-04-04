<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

// @phpcs:disable PSR1.Files.SideEffects
// @phpcs:disable PSR12.Files.FileHeader
// @phpcs:disable Generic.Files.InlineHTML
// @phpcs:disable Generic.Files.LineLength

/**
 * Plugin Name: CryptoPay Gateway for Contact Form 7
 * Version:     1.0.0
 * Plugin URI:  https://beycanpress.com/cryptopay/
 * Description: Adds Cryptocurrency payment gateway (CryptoPay) for Contact Form 7.
 * Author:      BeycanPress LLC
 * Author URI:  https://beycanpress.com
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: cf7-cryptopay
 * Tags: Cryptopay, Cryptocurrency, WooCommerce, WordPress, MetaMask, Trust, Binance, Wallet, Ethereum, Bitcoin, Binance smart chain, Payment, Plugin, Gateway, Moralis, Converter, API, coin market cap, CMC
 * Requires at least: 5.0
 * Tested up to: 6.5.0
 * Requires PHP: 8.1
*/

// Autoload
require_once __DIR__ . '/vendor/autoload.php';

define('CF7_CRYPTOPAY_FILE', __FILE__);
define('CF7_CRYPTOPAY_VERSION', '1.0.0');
define('CF7_CRYPTOPAY_KEY', basename(__DIR__));
define('CF7_CRYPTOPAY_URL', plugin_dir_url(__FILE__));
define('CF7_CRYPTOPAY_DIR', plugin_dir_path(__FILE__));
define('CF7_CRYPTOPAY_SLUG', plugin_basename(__FILE__));

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

load_plugin_textdomain('cf7-cryptopay', false, basename(__DIR__) . '/languages');

add_action('plugins_loaded', function (): void {

    cf7CryptoPayRegisterModels();

    if (!defined('WPCF7_VERSION')) {
        Helpers::requirePluginMessage('Contact Form 7', 'https://wordpress.org/plugins/contact-form-7/');
    } elseif (Helpers::bothExists()) {
        new BeycanPress\CryptoPay\CF7\Loader();
    } else {
        Helpers::requireCryptoPayMessage('Contact Form 7');
    }
});
