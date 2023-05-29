<?php

use BitpaySDK\Exceptions\BitPayException;
use BitPaySDK\Exceptions\InvoiceQueryException;
use BitPaySDK\Model\Facade;

class BitPayIpnProcess
{
    private BitPayCheckoutTransactions $bitPayCheckoutTransactions;
    private BitPayClientFactory $bitPayClientFactory;

    public function __construct(
        BitPayCheckoutTransactions $bitPayCheckoutTransactions,
        BitPayClientFactory $bitPayClientFactory
    ) {
        $this->bitPayCheckoutTransactions = $bitPayCheckoutTransactions;
        $this->bitPayClientFactory = $bitPayClientFactory;
    }

    /**
     * @throws InvoiceQueryException
     * @throws BitPayException
     */
    public function execute(WP_REST_Request $request): void
    {
        $data = $request->get_body();

        $data = json_decode($data);
        $event = $data->event;
        $data = $data->data;

        $orderId = $data->orderId;
        $invoiceId = $data->id;

        $bitPayClient = $this->bitPayClientFactory->create();
        $bitPayInvoice = $bitPayClient->getInvoice($invoiceId, Facade::POS, false);

        switch ($event->name) {
            case 'invoice_completed':
            case 'invoice_confirmed':
                $this->processCompleted($bitPayInvoice->getStatus(), $invoiceId, $orderId, $event->name);
                break;
            case 'invoice_paidInFull':
                $this->processProcessing($bitPayInvoice->getStatus(), $invoiceId, $orderId, $event->name);
                break;
            case 'invoice_declined':
            case 'invoice_failedToConfirm':
                $this->processFailed($bitPayInvoice->getStatus(), $invoiceId, $orderId, $event->name);
                break;
            case 'invoice_expired':
                $this->processAbandoned($bitPayInvoice->getStatus(), $invoiceId, $orderId, $event->name);
                break;
            case 'invoice_refundComplete':
                $this->processRefunded($bitPayInvoice->getStatus(), $orderId, $event->name);
                break;
        }
    }

    private function getEddOptions(): array
    {
        global $edd_options;
        return $edd_options;
    }

    private function processCompleted(
        string $invoiceStatus,
        string $invoiceId,
        int $orderId,
        string $eventName
    ): void {
        if (!in_array($invoiceStatus, ['confirmed', 'completed'])) {
            return;
        }

        $note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::getUrl($this->getEddOptions()['test_mode'],
                $invoiceId) . '">' . $invoiceId . '</a> processing has been completed.';

        edd_insert_payment_note($orderId, $note);
        $this->bitPayCheckoutTransactions->updateStatus($eventName, $orderId, $invoiceId);
        edd_update_order_status($orderId, "completed");
    }

    private function processProcessing(
        string $invoiceStatus,
        string $invoiceId,
        int $orderId,
        string $eventName
    ): void {
        if ($invoiceStatus != 'paid') {
            return;
        }

        $note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::getUrl($this->getEddOptions()['test_mode'],
                $invoiceId) . '">' . $invoiceId . '</a> is processing.';

        edd_insert_payment_note($orderId, $note);
        $this->bitPayCheckoutTransactions->updateStatus($eventName, $orderId, $invoiceId);
        edd_update_order_status($orderId, "processing");
    }

    private function processFailed(
        string $invoiceStatus,
        string $invoiceId,
        int $orderId,
        string $eventName
    ): void {
        if (!in_array($invoiceStatus, ['invalid', 'declined'])) {
            return;
        }

        $note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::getUrl($this->getEddOptions()['test_mode'],
                $invoiceId) . '">' . $invoiceId . '</a> has become invalid because of network congestion.  Order will automatically update when the status changes.';

        edd_insert_payment_note($orderId, $note);
        $this->bitPayCheckoutTransactions->updateStatus($eventName, $orderId, $invoiceId);
        edd_update_order_status($orderId, "failed");
    }

    private function processAbandoned(
        string $invoiceStatus,
        string $invoice_id,
        int $orderId,
        string $eventName
    ): void {
        if ($invoiceStatus != 'expired') {
            return;
        }

        $this->bitPayCheckoutTransactions->updateStatus($eventName, $orderId, $invoice_id);
        edd_update_order_status($orderId, "abandoned");
    }

    private function processRefunded(
        string $invoiceId,
        int $orderId,
        string $eventName
    ): void {
        $note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::getUrl($this->getEddOptions()['test_mode'],
                $invoiceId) . '">' . $invoiceId . '</a> has been refunded.';

        edd_insert_payment_note($orderId, $note);
        $this->bitPayCheckoutTransactions->updateStatus($eventName, $orderId, $invoiceId);
        edd_update_order_status($orderId, "refunded");
    }
}
