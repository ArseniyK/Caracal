<?php

/**
 * Handler class for item properties.
 *
 * Author: Mladen Mijatov
 */
namespace Modules\Shop\Property;

require_once('property_manager.php');

use \TemplateHandler as TemplateHandler;


class Handler {
	private static $instance;
	private $parent;
	private $name;
	private $path;

	/**
	 * Constructor
	 */
	protected function __construct($parent) {
		$this->parent = $parent;
		$this->name = $this->parent->name;
		$this->path = $this->parent->path;
	}

	/**
	 * Public function that creates a single instance
	 */
	public static function getInstance($parent) {
		if (!isset(self::$instance))
			self::$instance = new self($parent);

		return self::$instance;
	}

	/**
	 * Save properties for specified item.
	 *
	 * @param integer $item_id
	 * @return integer
	 */
	public function save_properties($item_id) {
		$manager = Manager::getInstance();

		// remove existing properties
		$manager->deleteData(array('item' => $item_id));

		// insert new data
		$count = 0;
		foreach ($_REQUEST as $key => $value) {
			if (substr($key, 0, 14) != 'property_data_')
				continue;

			// decode data prepared by user interface
			$decoded = json_decode($value, true);

			$data = array(
					'item'    => $item_id,
					'text_id' => $decoded['text_id'],
					'name'    => $decoded['name'],
					'type'    => $decoded['type'],
					'value'   => serialize($decoded['value'])
				);

			$manager->insertData($data);
			$count++;
		}

		return $count;
	}

	/**
	 * Get specified item property.
	 */
	public function json_GetProperty() {
		$manager = Manager::getInstance();
		$item_manager = \ShopItemManager::getInstance();
		$conditions = array();
		$result = false;

		// prepare conditions for manager
		if (isset($_REQUEST['item']))
			$conditions['item'] = fix_id($_REQUEST['item']);

		if (isset($_REQUEST['item_uid'])) {
			$item = $item_manager->getSingleItem(
					array('id'),
					array('uid' => fix_chars($_REQUEST['item_uid']))
				);

			if (is_object($item))
				$conditions['item'] = $item->id; else
				$conditions['item'] = -1;
		}

		if (isset($_REQUEST['id']))
			$conditions['id'] = fix_id($_REQUEST['id']);

		if (isset($_REQUEST['text_id']))
			$conditions['text_id'] = fix_chars($_REQUEST['text_id']);

		// get property from the database
		$property = $manager->getSingleItem($manager->getFieldNames(), $conditions);

		// bail if no property was found
		if (!is_object($property)) {
			print json_encode($result);
			return;
		}

		// prepare output
		$result = array(
				'id'      => $property->id,
				'text_id' => $property->text_id,
				'name'    => $property->name,
				'type'    => $property->type,
				'value'   => unserialize($property->value)
			);

		print json_encode($result);
	}

	/**
	 * Get property list for specified item as JSON object.
	 */
	private function json_GetPropertyList() {
		$manager = Manager::getInstance();
		$item_manager = \ShopItemManager::getInstance();
		$conditions = array();
		$result = false;

		print json_encode($result);
	}

	/**
	 * Tag handler for list of item properties.
	 *
	 * @param array $tag_params
	 * @param array $children
	 */
	public function tag_PropertyList($tag_params, $children) {
		$manager = Manager::getInstance();
		$conditions = array();
		$sort_by = array('id');
		$sort_asc = true;

		// prepare conditions
		if (isset($tag_params['item']))
			$conditions['item'] = fix_id($tag_params['item']);

		if (isset($tag_params['starts_with']))
			$conditions['text_id'] = array(
					'operator' => 'LIKE',
					'value'    => escape_chars($tag_params['starts_with']).'%'
				);

		if (isset($tag_params['sort_by']))
			$sort_by = fix_chars(explode(',', $tag_params['sort_by']));

		if (isset($tag_params['sort_asc']))
			$sort_asc = $tag_params['sort_asc'] == '1';

		// get item properties from database
		$items = $manager->getItems($manager->getFieldNames(), $conditions, $sort_by, $sort_asc);

		// we need items to display
		if (count($items) == 0)
			return;

		// create template
		$template = $this->parent->loadTemplate($tag_params, 'item_property_list_item.xml');

		$index = 0;
		foreach ($items as $item) {
			// prepare data
			$data = array(
					'text_id' => $item->text_id,
					'name'    => $item->name,
					'type'    => $item->type,
					'value'   => unserialize($item->value)
				);

			// prepare parameters
			$params = array(
					'id'        => $item->id,
					'index'     => $index,
					'text_id'   => $item->text_id,
					'name'      => $item->name,
					'type'      => $item->type,
					'value'     => unserialize($item->value),
					'raw_value' => $item->value,
					'data'      => json_encode($data)
				);
			$index++;

			// parse template
			$template->restoreXML();
			$template->setLocalParams($params);
			$template->parse();
		}
	}
}

?>
