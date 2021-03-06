<?php

/**
 * Shop Transactions Manager
 *
 * Author: Mladen Mijatov
 */

class ShopTransactionsManager extends ItemManager {
	private static $_instance;

	/**
	 * Constructor
	 */
	protected function __construct() {
		parent::__construct('shop_transactions');

		$this->addProperty('id', 'int');
		$this->addProperty('buyer', 'int');
		$this->addProperty('address', 'int');
		$this->addProperty('uid', 'varchar');
		$this->addProperty('type', 'smallint');
		$this->addProperty('status', 'smallint');
		$this->addProperty('currency', 'int');
		$this->addProperty('handling', 'decimal');
		$this->addProperty('shipping', 'decimal');
		$this->addProperty('weight', 'decimal');
		$this->addProperty('payment_method', 'varchar');
		$this->addProperty('payment_token', 'int');
		$this->addProperty('delivery_method', 'varchar');
		$this->addProperty('delivery_type', 'varchar');
		$this->addProperty('remark', 'text');
		$this->addProperty('remote_id', 'varchar');
		$this->addProperty('total', 'decimal');
		$this->addProperty('timestamp', 'timestamp');
	}

	/**
	 * Public function that creates a single instance
	 */
	public static function getInstance() {
		if (!isset(self::$_instance))
		self::$_instance = new self();

		return self::$_instance;
	}
}

?>
