<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\CF7\Models;

use BeycanPress\CryptoPayLite\Models\AbstractTransaction;

class TransactionsLite extends AbstractTransaction
{
    public string $addon = 'cf7';

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct('cf7_transaction');
    }
}
