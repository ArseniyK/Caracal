<?php

/**
 * Delivery method base class
 */

abstract class DeliveryMethod {
	protected $name;
	protected $parent;	
	
	protected function __construct($parent) {
		$this->parent = $parent;
	}

	/**
	 * Register delivery method with main shop module.
	 */
	protected function registerDeliveryMethod() {
		if (class_exists('shop')) {
			$shop = shop::getInstance();
			$shop->registerDeliveryMethod($this->name, $this);
		}
	}

	/**
	 * Get the name of the delivery method.
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get localized name of delivery method.
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->parent->getLanguageConstant('delivery_method_title');
	}

	/**
	 * Get URL of icon for delivery method. This icon is used in menus and
	 * needs to be 16x16 pixels.
	 *
	 * @return string
	 */
	public function getIcon() {
		return url_GetFromFilePath($this->parent->path.'images/icon.png');
	}

	/**
	 * Get URL of 200x55 pixel image for delivery method. This image is a
	 * logo of delivery method and is used in forms for selection.
	 *
	 * @return string
	 */
	public function getImage() {
		return url_GetFromFilePath($this->parent->path.'images/image.png');
	}

	/**
	 * Get status of specified delivery. If available multiple statuses
	 * should be provided last item being the current status of delivery.
	 *
	 * Example of result array:
	 *		$result = array(
	 *					array('Prosessing', 1362040000),
	 *					array('Departure', 1362240000),
	 *					array('In transit', 1362440000),
	 *					array('Delivered', 1363440000)
	 *				);                       
	 *                                       
	 * @param string $delivery_id            
	 * @return array
	 */
	abstract public function getDeliveryStatus($delivery_id);

	/**
	 * Get available delivery types for selected items. Each type needs
	 * to return estimated delivery time, cost and name of service.
	 *
	 * Example of items array:
	 * 		$items = array(
	 * 					array(
	 * 						'properties'	=> array(),
	 * 						'package_type'	=> 0,
	 * 						'width'			=> 0.2,
	 * 						'height'		=> 0.5,
	 * 						'length'		=> 1,
	 * 						'units'			=> 1,
	 * 						'count'			=> 1
	 * 					)
	 * 				);
	 *
	 * Example of result array:
	 *		$result = array(
	 *					array('Normal', 19.95, 1364040000, 1365040000),
	 *					array('Express', 33.23, 1363040000, 1364040000)
	 *				);
	 *
	 * @param array $items
	 * @return array
	 */
	abstract public function getDeliveryTypes($items);

	/**
	 * Get list of supported package types. Items in resulting array must
	 * corespond to constants in PackageType class.
	 *
	 * Example of resulting array:
	 * 		$result = array(
	 * 					PackageType::BOX_10,
	 * 					PackageType::ENVELOPE,
	 * 					PackageType::PAK
	 * 				);
	 *
	 * @return array
	 */
	abstract public function getSupportedPackageTypes();

	/**
	 * Get special params supported by delivery method which should be
	 * assigned with items in shop. Resulting array needs to contain
	 * array which will contain key-value pairs of localized group names
	 * and values and a second array with available params.
	 *
	 * Example of result array:
	 *		$result = array(
	 *					array(
	 *						'package_types'		=> 'Packaging params',
	 *						'special_services'	=> 'Special services'
	 *					),
	 *					array(
	 *						'package_types'	=> array(
	 *							array('package_box', 'Box'),
	 *							array('package_tube', 'Tube shaped box'),
	 *							array('package_envelope', 'Envelope'),
	 *						),
	 *						'special_services' => array(
	 *							array('keep_on_ice', 'Keep package cool'),
	 *							array('keep_hot', 'Keep package hot'),
	 *							array('fragile', 'Fragile')
	 *						)
	 *					)
	 *				);
	 *
	 * @return array
	 */
	abstract public function getAvailableParams();

	/**
	 * Whether delivery method can be used for international deliveries.
	 *
	 * @return boolean
	 */
	abstract public function isInternational();
}