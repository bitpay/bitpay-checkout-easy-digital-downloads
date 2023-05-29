<?php

use BitPaySDK\Model\Invoice\Buyer;
use BitPaySDK\Model\Invoice\Invoice;

class BitPayInvoiceFactory
{
    public function create(
        float $price,
        string $orderId,
    ): Invoice {
        $invoice = new Invoice(
            $price,
            'USD'
        );

        $invoice->setExtendedNotifications(true);
        $invoice->setRedirectURL($this->getRedirectURL($orderId));
        $invoice->setNotificationURL($this->getNotificationURL());
        $invoice->setOrderId($orderId);
        $invoice->setBuyer($this->getBuyer());

        return $invoice;
    }

    private function getRedirectURL(string $orderId): string
    {
        return edd_get_success_page_uri() . '?order_id=' . $orderId;
    }

    private function getNotificationURL(): string
    {
        return get_home_url() . '/wp-json/bitpay-edd/ipn/status';
    }

    private function getBuyer(): Buyer
    {
        if ($this->getEddOption('bitpay_checkout_capture_email') != 1) {
            return new Buyer();
        }

        $current_user = wp_get_current_user();

        if (!$current_user->user_email) {
            return new Buyer();
        }

        $buyerInfo = new Buyer();
        $buyerInfo->setName($current_user->display_name);
        $buyerInfo->setEmail($current_user->user_email);

        return $buyerInfo;
    }

    private function getEddOption(string $optionName): string
    {
        global $edd_options;
        return $edd_options[$optionName];
    }
}
