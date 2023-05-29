<?php

class BitPayEndpoint
{
    public static function getType(int $testMode): string
    {
        if ($testMode == 1) {
            return 'test';
        } else {
            return 'prod';
        }

    }

    public static function getUrl(int $testMode, string $invoiceId): string
    {
        if ($testMode == 1) {
            return '//test.bitpay.com/dashboard/payments/' . $invoiceId;
        } else {
            return '//bitpay.com/dashboard/payments/' . $invoiceId;
        }
    }
}
