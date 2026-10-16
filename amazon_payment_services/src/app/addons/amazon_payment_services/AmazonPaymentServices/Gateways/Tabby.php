<?php

namespace AmazonPaymentServices\Gateways;

class Tabby extends Gateway
{
    public $type = 'tabby';
    public $integration_type = 'redirection';
    public $supportedCurriences = ['SAR', 'AED'];

    public function isActive()
    {
        if (!$this->active) {
            return false;
        }

        if ($this->disableCurrencyCheck) {
            return true;
        }

        return in_array($this->getCurrency(), $this->supportedCurriences);
    }

    public function verifyParams($params)
    {
        $params['command'] = 'PURCHASE';
        $params['payment_option'] = 'TABBY';

        if (!empty($params['phone_number'])) {
            $params['phone_number'] = trim(preg_replace('/[^0-9+]/', '', $params['phone_number']));
        }

        return $params;
    }
}
