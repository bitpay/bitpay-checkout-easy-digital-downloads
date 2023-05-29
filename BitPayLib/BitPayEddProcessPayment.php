<?php

use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\Model\Facade;
use BitPaySDK\Model\Invoice\Invoice;

class BitPayEddProcessPayment
{
    private BitPayCheckoutTransactions $bitPayCheckoutTransactions;
    private BitPayClientFactory $bitPayClientFactory;
    private BitPayInvoiceFactory $bitPayInvoiceFactory;

    public function __construct(
        BitPayCheckoutTransactions $bitPayCheckoutTransactions,
        BitPayClientFactory $bitPayClientFactory
    ) {
        $this->bitPayCheckoutTransactions = $bitPayCheckoutTransactions;
        $this->bitPayClientFactory = $bitPayClientFactory;
        $this->bitPayInvoiceFactory = new BitPayInvoiceFactory();
    }

    /**
     * @throws BitPayException
     */
    public function execute(array $purchaseData): void
    {
        // payment processing happens here
        $errors = edd_get_errors();
        if ($errors) {
            // if errors are present, send the user back to the purchase page, so they can be corrected
            edd_send_back_to_checkout('?payment-mode=' . $purchaseData['post_data']['edd-gateway']);
            return;
        }

        /**********************************
         * setup the payment details
         **********************************/
        $payment = $this->getPayment($purchaseData);
        // record the pending payment
        $orderId = edd_insert_payment($payment);

        if (!$orderId) {
            edd_send_back_to_checkout('?payment-mode=' . $purchaseData['post_data']['edd-gateway']);
            return;
        }

        //BitPay
        $bitPayInvoice = $this->createBitPayInvoice(floatval($purchaseData['price']), $orderId);

        //now we have to append the invoice transaction id for the callback verification
        $invoiceId = $bitPayInvoice->getId();
        $this->updateCookies($invoiceId);

        $this->addOrderNote($bitPayInvoice->getOrderId(), $invoiceId);

        //use the modal if '1', otherwise redirect
        if (intval($this->getEddOptions()['bitpay_checkout_flow']) == 2) {
            #add some order notes
            wp_redirect($bitPayInvoice->getUrl());
            return;
        }

        wp_redirect($bitPayInvoice->getRedirectURL() . '&bpedd=1&$invoiceID=' . $invoiceId);
    }

    private function getPayment(array $purchaseData): array
    {
        return array(
            'price' => $purchaseData['price'],
            'date' => $purchaseData['date'],
            'user_email' => $purchaseData['user_email'],
            'purchase_key' => $purchaseData['purchase_key'],
            'currency' => $this->getEddOptions()['currency'],
            'downloads' => $purchaseData['downloads'],
            'cart_details' => $purchaseData['cart_details'],
            'user_info' => $purchaseData['user_info'],
            'status' => 'pending',
        );
    }

    /**
     * @throws BitPayException
     */
    private function createBitPayInvoice(float $price, int $orderId): Invoice
    {
        $bitPayClient = $this->bitPayClientFactory->create();
        $bitPayInvoice = $this->bitPayInvoiceFactory->create($price, trim($orderId));

        return $bitPayClient->createInvoice($bitPayInvoice, Facade::POS, false);
    }

    private function updateCookies(string $invoiceId): void
    {
        //set a cookie for redirects and updating the order status
        setcookie("bitpay-edd-invoice-id", $invoiceId, time() + (86400 * 30), "/");
    }

    private function getEddOptions(): array
    {
        global $edd_options;
        return $edd_options;
    }

    private function addOrderNote(int $orderId, string $transactionId): void
    {
        $note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::getUrl($this->getEddOptions()['test_mode'],
                $transactionId) . '">' . $transactionId . '</a> is pending.';

        edd_insert_payment_note($orderId, $note);
        edd_update_order_status($orderId, 'pending');
        $this->bitPayCheckoutTransactions->createTransaction($orderId, $transactionId, 'pending');
    }
}
