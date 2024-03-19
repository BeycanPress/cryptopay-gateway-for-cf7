<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\CF7\Gateways;

use BeycanPress\CryptoPayLite\Helpers;
use BeycanPress\CryptoPayLite\Payment;
use BeycanPress\CryptoPayLite\Types\Order\OrderType;
use BeycanPress\CryptoPayLite\Types\Transaction\ParamsType;

class GatewayLite extends AbstractGateway
{
    /**
     * @var string
     */
    public string $key = 'cryptopay_lite';

    /**
     * @var string
     */
    public string $name = 'CryptoPay Lite';

    /**
     * @return object
     */
    public function getModel(): object
    {
        return Helpers::getModelByAddon('cf7');
    }

    /**
     * @return string
     */
    public function getMainJsKey(): string
    {
        return Helpers::getProp('mainJsKey');
    }

    /**
     * @return Payment
     */
    public function getPayment(): Payment
    {
        return new Payment('cf7');
    }

    /**
     * @param array<string,mixed> $args
     * @return OrderType
     */
    public function createOrder(array $args): OrderType
    {
        return OrderType::fromArray($args);
    }

    /**
     * @param array<string,mixed> $args
     * @return ParamsType
     */
    public function createParams(array $args): ParamsType
    {
        return ParamsType::fromArray($args);
    }
}
