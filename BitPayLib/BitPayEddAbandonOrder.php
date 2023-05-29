<?php

class BitPayEddAbandonOrder
{
    private BitPayCheckoutTransactions $bitPayCheckoutTransactions;

    public function __construct(BitPayCheckoutTransactions $bitPayCheckoutTransactions)
    {
        $this->bitPayCheckoutTransactions = $bitPayCheckoutTransactions;
    }

    public function execute(WP_REST_Request $request): void
    {
        $data = $request->get_params();
        $orderId = $data['orderid'];

        $this->bitPayCheckoutTransactions->updateLastPendingStatus('invoice_expired', $orderId);
        edd_update_order_status($orderId, "abandoned");

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }
}
