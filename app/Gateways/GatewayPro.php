<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\CF7\Gateways;

use BeycanPress\CryptoPay\Helpers;
use BeycanPress\CryptoPay\Payment;
use BeycanPress\CryptoPay\Types\Order\OrderType;
use BeycanPress\CryptoPay\Types\Transaction\ParamsType;

class GatewayPro extends AbstractGateway
{
    /**
     * @var string
     */
    public string $key = 'cryptopay';

    /**
     * @var string
     */
    public string $name = 'CryptoPay';


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
