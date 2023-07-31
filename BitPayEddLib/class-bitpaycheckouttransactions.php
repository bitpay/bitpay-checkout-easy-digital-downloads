<?php

namespace BitPayEddLib;

use wpdb;

class BitPayCheckoutTransactions {

	private string $table_name = '_bitpay_checkout_transactions';

	public function create_table(): void {
		$charset_collate = $this->get_wpdb()->get_charset_collate();
		$query           = "CREATE TABLE IF NOT EXISTS $this->table_name(
		            `id` int(11) NOT NULL AUTO_INCREMENT,
		            `order_id` int(11) NOT NULL,
		            `transaction_id` varchar(255) NOT NULL,
		            `transaction_status` varchar(50) NOT NULL DEFAULT 'new',
		            `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		            PRIMARY KEY (`id`)
		            ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $query );
	}

	public function update_status(
		string $event_name,
		int $order_id,
		string $transaction_id
	): void {
		$query = $this->get_wpdb()->prepare(
			'UPDATE ' . $this->table_name . ' SET  transaction_status = %s WHERE order_id = %s AND transaction_id = %s',
			$event_name,
			$order_id,
			$transaction_id
		);
		$this->get_wpdb()->query( $query );
	}

	public function update_last_pending_status(
		string $event_name,
		int $order_id
	): void {
		$query = $this->get_wpdb()->prepare(
			'UPDATE ' . $this->table_name . ' SET transaction_status = %s'
			. ' WHERE order_id = %s AND transaction_status = %s order by date_added desc limit 1',
			$event_name,
			$order_id,
			'pending'
		);
		$this->get_wpdb()->query( $query );
	}

	public function create_transaction(
		int $order_id,
		string $transaction_id,
		string $status
	): void {
		$query = $this->get_wpdb()->prepare(
			'INSERT INTO ' . $this->table_name . ' (order_id,transaction_id,transaction_status) VALUES(%s,%s,%s)',
			$order_id,
			$transaction_id,
			$status
		);
		$this->get_wpdb()->query( $query );
	}

	public function get_order_id_by_invoice_id( string $invoice_id ): ?int {
		$table_name = $this->table_name;
		$wp_db      = $this->get_wpdb();
		$sql        = $wp_db->prepare(
			"SELECT order_id FROM $table_name WHERE transaction_id = %s LIMIT 1",
			$invoice_id
		);
		$order_id   = $wp_db->get_var( $sql );
		if ( ! $order_id ) {
			return null;
		}
		return (int) $order_id;
	}

	private function get_wpdb(): wpdb {
		global $wpdb;
		return $wpdb;
	}
}
