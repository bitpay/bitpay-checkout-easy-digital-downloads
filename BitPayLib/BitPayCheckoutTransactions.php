<?php

class BitPayCheckoutTransactions
{
    private string $tableName = '_bitpay_checkout_transactions';

    public function createTable(): void
    {
        $charset_collate = $this->getWpdb()->get_charset_collate();
        $query = "CREATE TABLE IF NOT EXISTS $this->tableName(
		            `id` int(11) NOT NULL AUTO_INCREMENT,
		            `order_id` int(11) NOT NULL,
		            `transaction_id` varchar(255) NOT NULL,
		            `transaction_status` varchar(50) NOT NULL DEFAULT 'new',
		            `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		            PRIMARY KEY (`id`)
		            ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($query);
    }

    public function updateStatus(
        string $eventName,
        int $orderId,
        string $transactionId
    ): void {
        $query = $this->getWpdb()->prepare(
            "UPDATE " . $this->tableName . " SET  transaction_status = %s WHERE order_id = %s AND transaction_id = %s",
            $eventName,
            $orderId,
            $transactionId
        );
        $this->getWpdb()->query($query);
    }

    public function updateLastPendingStatus(
        string $eventName,
        int $orderId
    ): void {
        $query = $this->getWpdb()->prepare(
            "UPDATE " . $this->tableName . " SET  transaction_status = %s WHERE order_id = %s AND transaction_status = %s order by date_added desc limit 1",
            $eventName,
            $orderId,
            'pending'
        );
        $this->getWpdb()->query($query);
    }

    public function createTransaction(
        int $orderId,
        string $transactionId,
        string $status
    ): void {
        $query = $this->getWpdb()->prepare(
            "INSERT INTO " . $this->tableName . " (order_id,transaction_id,transaction_status) VALUES(%s,%s,%s)",
            $orderId,
            $transactionId,
            $status
        );
        $this->getWpdb()->query($query);
    }

    private function getWpdb(): wpdb
    {
        global $wpdb;
        return $wpdb;
    }
}
