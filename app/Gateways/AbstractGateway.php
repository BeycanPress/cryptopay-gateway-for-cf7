<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\CF7\Gateways;

use BeycanPress\CryptoPay\Constants;
use BeycanPress\CryptoPay\Integrator\Session;
use BeycanPress\CryptoPayLite\Constants as LiteConstants;

abstract class AbstractGateway
{
    /**
     * @var string
     */
    public string $key;

    /**
     * @var string
     */
    public string $name;

    /**
     * AbstractGateway constructor.
     */
    public function __construct()
    {
        Session::start();
        add_action('wpcf7_init', [$this, 'init']);
        add_action('wpcf7_after_save', [$this, 'save']);
        add_filter('wpcf7_editor_panels', [$this, 'addPanel']);
        add_filter('wpcf7_posted_data', [$this, 'addSessionForPayment']);
        add_action('wpcf7_before_send_mail', [$this, 'checkPayment'], 10, 3);
    }

    /**
     * @return object
     */
    abstract public function getModel(): object;

    /**
     * @return string
     */
    abstract public function getMainJsKey(): string;

    /**
     * @return object
     */
    abstract public function getPayment(): object;

    /**
     * @param array<string,mixed> $args
     * @return object
     */
    abstract public function createOrder(array $args): object;

    /**
     * @param array<string,mixed> $args
     * @return object
     */
    abstract public function createParams(array $args): object;

    /**
     * @return void
     */
    public function init(): void
    {
        wpcf7_add_form_tag($this->key, [$this, 'tagHandler']);
    }

    /**
     * @param \WPCF7_FormTag $tag
     * @return string
     */
    public function tagHandler(\WPCF7_FormTag $tag): string
    {
        $form = \WPCF7_ContactForm::get_current();
        $itemId = get_post_meta($form->id(), "cf7_cp_item_id", true);
        $activate = get_post_meta($form->id(), "cf7_cp_activate", true);
        $itemPrice = get_post_meta($form->id(), "cf7_cp_item_price", true);
        $itemCurrency = get_post_meta($form->id(), "cf7_cp_item_currency", true);

        if (!$activate) {
            /* translators: %s: plugin name */
            return sprintf(esc_html__('%s is not activated.', 'cryptopay-gateway-for-cf7'), $this->name);
        }

        if (!$itemPrice || !$itemCurrency) {
            return esc_html__('Item price or currency is not set.', 'cryptopay-gateway-for-cf7');
        }

        $order = [
            'amount' => $itemPrice,
            'currency' => $itemCurrency,
        ];

        $params = [
            'formId' => strval($form->id()),
        ];

        if ($itemId) {
            $params['itemId'] = strval($itemId);
        }

        if (Session::has('cf7_transaction_hash')) {
            $transaction = $this->getModel()->findOneBy([
                'hash' => Session::get('cf7_transaction_hash'),
                'params' => wp_json_encode($params),
            ]);
            if ($transaction) {
                return $this->alreadyPaid($transaction);
            }
        }

        $html = $this->getPayment()
            ->setOrder($this->createOrder($order))
            ->setParams($this->createParams($params))
            ->html(loading: true);

        $this->enqueueScripts([
            $this->getMainJsKey()
        ]);

        return $html;
    }

    /**
     * @param array<string> $deps
     * @return void
     */
    protected function enqueueScripts(array $deps = []): void
    {
        wp_enqueue_script(
            'cf7-' . $this->key,
            CRYPTOPAY_GATEWAY_FOR_CF7_URL . 'assets/js/main.js',
            array_merge($deps, ['jquery', 'wp-i18n']),
            CRYPTOPAY_GATEWAY_FOR_CF7_VERSION,
            true
        );
    }

    /**
     * @param object $transaction
     * @return string
     */
    private function alreadyPaid(object $transaction): string
    {
        $this->enqueueScripts();
        $msg = __('The final payment for this form has been completed but not submitted. Therefore, you only need to send the form.', 'cryptopay-gateway-for-cf7'); // phpcs:ignore
        return '<p>' . esc_html($msg) . '</p><p><input type="hidden" name="transaction-hash" value="' . esc_attr($transaction->getHash()) . '" /><input class="wpcf7-form-control wpcf7-submit has-spinner" type="submit" value="' . esc_attr__('Send') . '"><p>'; // phpcs:ignore
    }

    /**
     * @param \WPCF7_ContactForm $form
     * @return void
     */
    public function save(\WPCF7_ContactForm $form): void
    {
        $nonce = isset($_POST['cf7_cp_nonce']) ? sanitize_text_field(wp_unslash($_POST['cf7_cp_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'cf7_cp_nonce')) {
            return;
        }

        $activate = isset($_POST['cf7_cp_activate']) ? 1 : 0;
        $itemId = isset($_POST['cf7_cp_item_id']) ? absint($_POST['cf7_cp_item_id']) : 0;
        $itemPrice = isset($_POST['cf7_cp_item_price']) ? absint($_POST['cf7_cp_item_price']) : 0;
        $itemCurrency = isset($_POST['cf7_cp_item_currency'])
            ? sanitize_text_field(wp_unslash($_POST['cf7_cp_item_currency']))
            : 'USD';

        update_post_meta($form->id(), "cf7_cp_activate", $activate);
        update_post_meta($form->id(), "cf7_cp_item_id", $itemId);
        update_post_meta($form->id(), "cf7_cp_item_price", $itemPrice);
        update_post_meta($form->id(), "cf7_cp_item_currency", $itemCurrency);
    }

    /**
     * @param array<string,mixed> $panels
     * @return array<string,mixed>
     */
    public function addPanel(array $panels): array
    {
        $panels[$this->key] = [
            'title' => $this->name,
            'callback' => [$this, 'panelContent'],
        ];

        return $panels;
    }

    /**
     * @return void
     */
    public function panelContent(): void
    {
        // here is fe side and adding nonce field below
        /* phpcs:disable WordPress.Security.NonceVerification.Recommended */

        if (class_exists(Constants::class)) {
            $currencies = Constants::getCountryCurrencies();
        } else {
            $currencies = LiteConstants::getCountryCurrencies();
        }

        $formId = isset($_GET['post']) ? absint($_GET['post']) : 0;
        $itemId = get_post_meta($formId, "cf7_cp_item_id", true);
        $activate = get_post_meta($formId, "cf7_cp_activate", true);
        $itemPrice = get_post_meta($formId, "cf7_cp_item_price", true);
        $itemCurrency = get_post_meta($formId, "cf7_cp_item_currency", true);
        $activationStatus = $activate ? 'checked' : '';

        $options = '';
        foreach ($currencies as $code => $name) {
            $selectedCurrency = $itemCurrency == $code ? 'selected' : '';
            $options .= '<option value="' . $code . '" ' . esc_attr($selectedCurrency) . '>' . $name . '</option>';
        }

        wp_nonce_field('cf7_cp_nonce', 'cf7_cp_nonce', false, true);

        echo '<h2>' . esc_html($this->name) . '</h2>';
        echo '<p>' . esc_html__(
            'Add cryptocurrency payment gateway to your form.',
            'cryptopay-gateway-for-cf7'
        ) . '</p>';
        echo '<p>' . sprintf(
            /* translators: %s: tag name */
            esc_html__(
                'You need add "%1$s" tag to form for start %2$s and need delete submit button.',
                'cryptopay-gateway-for-cf7'
            ),
            '<strong>[' . esc_html($this->key) . ']</strong>',
            '<strong>' . esc_html($this->name) . '</strong>'
        ) . '</p>';
        echo '
            <table>
                <tr>
                    <td width="195px">
                        <label>'
            . sprintf(
                /* translators: %s: plugin name */
                esc_html__('Activate %s', 'cryptopay-gateway-for-cf7'),
                esc_html($this->name)
            )
            . ': </label>
                    </td>
                    <td width="250px">
                        <input name="cf7_cp_activate" value="1" type="checkbox" ' . esc_attr($activationStatus) . '>
                    </td>
                </tr>
                <tr><td>&nbsp;</td></tr>
                <tr>
                    <td>Item ID: </td>
                    <td>
                        <input type="number" min="1" name="cf7_cp_item_id" value="' . esc_attr($itemId) . '">
                    </td>
                    <td> [ Optional ]</td>
                </tr>
                <tr><td>&nbsp;</td></tr>
                <tr>
                    <td>Item Price: </td>
                    <td>
                        <input
                            type="number"
                            min="0"
                            name="cf7_cp_item_price"
                            value="' . esc_attr($itemPrice) . '"
                            required
                        >
                    </td>
                    <td> [ Required ]</td>
                </tr>
                <tr><td>&nbsp;</td></tr>
                <tr>
                    <td>Item Currency: </td>
                    <td>
                        <select name="cf7_cp_item_currency" required>
                            <option value="">Select Currency</option>
                            ' .
            wp_kses(
                $options,
                [
                    'option' => [
                        'value' => [],
                        'selected' => [],
                    ],
                ]
            )
            . '
                        </select>
                    </td>
                    <td> [ Required ]</td>
                </tr>
            </table>
        ';

        /* phpcs:enable WordPress.Security.NonceVerification.Recommended */
    }

    /**
     * @param array<string,mixed> $postedData
     * @return array<string,mixed>
     */
    public function addSessionForPayment(array $postedData): array
    {
        $transactionHash = isset($postedData['transaction-hash'])
            ? sanitize_text_field($postedData['transaction-hash'])
            : null;

        if ($transactionHash) {
            Session::set('cf7_transaction_hash', $transactionHash);
        }

        return $postedData;
    }

    /**
     * @param \WPCF7_ContactForm $form
     * @param bool $abort
     * @param \WPCF7_Submission $submission
     * @return void
     */
    public function checkPayment(\WPCF7_ContactForm $form, bool &$abort, \WPCF7_Submission $submission): void
    {
        $abort = true;
        $activate = get_post_meta($form->id(), "cf7_cp_activate", true);

        if (!$activate) {
            return;
        }

        $postedData = $submission->get_posted_data();
        $transactionHash = isset($postedData['transaction-hash'])
            ? sanitize_text_field($postedData['transaction-hash'])
            : null;

        if ($transactionHash) {
            $transaction = $this->getModel()->findOneBy([
                'hash' => $transactionHash,
            ]);
            if ($transaction) {
                $abort = false;
                Session::remove('cf7_transaction_hash');
            }
        }

        if ($abort) {
            $submission->set_response($form->filter_message(
                esc_html__('Payment is not verified. Sending mail has been aborted.', 'cryptopay-gateway-for-cf7')
            ));
        }
    }
}
