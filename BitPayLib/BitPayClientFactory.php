<?php

use BitPaySDK\Client;
use BitPaySDK\Env;
use BitpaySDK\Exceptions\BitPayException;
use BitPaySDK\PosClient;

class BitPayClientFactory
{
    /**
     * @throws BitPayException
     */
    public function create(): Client
    {
        return new PosClient($this->getBitPayToken(), $this->getEnvironment());
    }

    private function getBitPayToken(): string
    {
        //dev or prod token
        switch ($this->getOption('test_mode')) {
            case 'true':
            case '1':
            default:
                return $this->getOption('bitpay_checkout_token_dev');
            case 'false':
            case '0':
                return $this->getOption('bitpay_checkout_token_prod');
        }
    }

    private function getEnvironment(): string
    {
        switch ($this->getOption('test_mode')) {
            case 'true':
            case '1':
            default:
                return Env::TEST;
            case 'false':
            case '0':
                return Env::PROD;
        }
    }

    private function getOption(string $optionName): string
    {
        global $edd_options;

        return $edd_options[$optionName];
    }
}
