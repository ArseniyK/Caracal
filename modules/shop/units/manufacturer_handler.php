<?php

/**
 * Handler class for shop manufacturers
 * Copyright (c) 2012. by Way2CU
 *
 * Author: Mladen Mijatov
 */

require_once('manufacturer_manager.php');


class ShopManufacturerHandler {
	private static $_instance;
	private $_parent;
	private $name;
	private $path;

	/**
	 * Constructor
	 */
	protected function __construct($parent) {
		$this->_parent = $parent;
		$this->name = $this->_parent->name;
		$this->path = $this->_parent->path;
	}

	/**
	 * Public function that creates a single instance
	 */
	public static function getInstance($parent) {
		if (!isset(self::$_instance))
		self::$_instance = new self($parent);

		return self::$_instance;
	}

	/**
	 * Transfer control to group
	 *
	 * @param array $params
	 * @param array $children
	 */
	public function transferControl($params = array(), $children = array()) {
		$action = isset($params['sub_action']) ? $params['sub_action'] : null;

		switch ($action) {
			case 'add':
				$this->addManufacturer();
				break;

			case 'change':
				$this->changeManufacturer();
				break;

			case 'save':
				$this->saveManufacturer();
				break;

			case 'delete':
				$this->deleteManufacturer();
				break;

			case 'delete_commit':
				$this->deleteManufacturer_Commit();
				break;

			default:
				$this->showManufacturers();
				break;
		}
	}

	/**
	 * Show list of manufacturers
	 */
	private function showManufacturers() {
		$template = new TemplateHandler('manufacturer_list.xml', $this->path.'templates/');

		$params = array(
					'link_new' => url_MakeHyperlink(
										$this->_parent->getLanguageConstant('add_manufacturer'),
										window_Open( // on click open window
											'shop_manufacturer_add',
											360,
											$this->_parent->getLanguageConstant('title_manufacturer_add'),
											true, true,
											backend_UrlMake($this->name, 'manufacturers', 'add')
										)
									),
					);

		// register tag handler
		$template->registerTagHandler('cms:manufacturer_list', $this, 'tag_ManufacturerList');

		$template->restoreXML();
		$template->setLocalParams($params);
		$template->parse();
	}

	/**
	 * Shows form for adding new manufacturer to shop
	 */
	private function addManufacturer() {
		$template = new TemplateHandler('manufacturer_add.xml', $this->path.'templates/');
		$template->setMappedModule($this->name);

		$params = array(
					'form_action'	=> backend_UrlMake($this->name, 'manufacturers', 'save'),
					'cancel_action'	=> window_Close('shop_manufacturer_add')
				);

		$template->restoreXML();
		$template->setLocalParams($params);
		$template->parse();
	}

	/**
	 * Show form for changing manufacturer data
	 */
	private function changeManufacturer() {
		$id = fix_id($_REQUEST['id']);
		$manager = ShopManufacturerManager::getInstance();

		$item = $manager->getSingleItem($manager->getFieldNames(), array('id' => $id));

		if (!is_object($item))
			return;

		$template = new TemplateHandler('manufacturer_change.xml', $this->path.'templates/');
		$template->setMappedModule($this->name);

		if (class_exists('gallery')) {
			$gallery = gallery::getInstance();
			$template->registerTagHandler('cms:image_list', $gallery, 'tag_ImageList');
		}

		$params = array(
					'id'			=> $item->id,
					'name'			=> $item->name,
					'web_site'		=> $item->web_site,
					'logo'			=> $item->logo,
					'form_action'	=> backend_UrlMake($this->name, 'manufacturers', 'save'),
					'cancel_action'	=> window_Close('shop_manufacturer_change')
				);

		$template->restoreXML();
		$template->setLocalParams($params);
		$template->parse();
	}

	/**
	 * Save new or changed manufacturer data
	 */
	private function saveManufacturer() {
		$id = null;
		$manager = ShopManufacturerManager::getInstance();
		$gallery_addon = '';

		if (isset($_REQUEST['id']))
			$id = fix_id($_REQUEST['id']);

		// get data from request
		$data = array(
				'name'		=> $this->_parent->getMultilanguageField('name'),
				'web_site'	=> escape_chars($_REQUEST['web_site']),
			);

		// store or update data in database
		if (is_null($id)) {
			// get new image inserted
			if (class_exists('gallery') && isset($_FILES['logo'])) {
				$gallery = gallery::getInstance();
				$gallery_manager = GalleryManager::getInstance();

				$result = $gallery->createImage('logo');

				if (!$result['error']) {
					$image_data = array(
								'title'			=> $data['name'],
								'visible'		=> 0,
								'protected'		=> 1
							);

					$gallery_manager->updateData($image_data, array('id' => $result['id']));

					$data['logo'] = $result['id'];
					$gallery_addon = ';'.window_ReloadContent('gallery_images');
				}
			}

			// insert new manufacturer data
			$manager->insertData($data);
			$window = 'shop_manufacturer_add';

		} else {
			// get the logo
			$data['logo'] = isset($_REQUEST['logo']) && !empty($_REQUEST['logo']) ? fix_id($_REQUEST['logo']) : 0;

			// update existing data
			$manager->updateData($data, array('id' => $id));
			$window = 'shop_manufacturer_change';
		}

		// show message
		$template = new TemplateHandler('message.xml', $this->path.'templates/');
		$template->setMappedModule($this->name);

		$params = array(
					'message'	=> $this->_parent->getLanguageConstant('message_manufacturer_saved'),
					'button'	=> $this->_parent->getLanguageConstant('close'),
					'action'	=> window_Close($window).";".window_ReloadContent('shop_manufacturers').$gallery_addon
				);

		$template->restoreXML();
		$template->setLocalParams($params);
		$template->parse();
	}

	/**
	 * Show confirmation form before removing manufacturer
	 */
	private function deleteManufacturer() {
		global $language;

		$id = fix_id($_REQUEST['id']);
		$manager = ShopManufacturerManager::getInstance();

		$item = $manager->getSingleItem(array('name'), array('id' => $id));

		$template = new TemplateHandler('confirmation.xml', $this->path.'templates/');
		$template->setMappedModule($this->name);

		$params = array(
					'message'		=> $this->_parent->getLanguageConstant("message_manufacturer_delete"),
					'name'			=> $item->name[$language],
					'yes_text'		=> $this->_parent->getLanguageConstant("delete"),
					'no_text'		=> $this->_parent->getLanguageConstant("cancel"),
					'yes_action'	=> window_LoadContent(
											'shop_manufacturer_delete',
											url_Make(
												'transfer_control',
												'backend_module',
												array('module', $this->name),
												array('backend_action', 'manufacturers'),
												array('sub_action', 'delete_commit'),
												array('id', $id)
											)
										),
					'no_action'		=> window_Close('shop_manufacturer_delete')
				);

		$template->restoreXML();
		$template->setLocalParams($params);
		$template->parse();
	}

	/**
	 * Perform manufacturer removal
	 */
	private function deleteManufacturer_Commit() {
		$id = fix_id($_REQUEST['id']);
		$manager = ShopManufacturerManager::getInstance();
		$item_manager = ShopItemManager::getInstance();

		$manager->deleteData(array('id' => $id));
		$item_manager->updateData(array('manufacturer' => 0), array('manufacturer' => $id));

		$template = new TemplateHandler('message.xml', $this->path.'templates/');
		$template->setMappedModule($this->name);

		$params = array(
					'message'	=> $this->_parent->getLanguageConstant("message_manufacturer_deleted"),
					'button'	=> $this->_parent->getLanguageConstant("close"),
					'action'	=> window_Close('shop_manufacturer_delete').";".window_ReloadContent('shop_manufacturers')
				);

		$template->restoreXML();
		$template->setLocalParams($params);
		$template->parse();
	}

	/**
	 * Handle drawing manufacturer tag
	 *
	 * @param array $tag_params
	 * @param array $children
	 */
	public function tag_Manufacturer($tag_params, $children) {
		$manager = ShopManufacturerManager::getInstance();
		$conditions = array();

		// collect params
		if (isset($tag_params['id']))
			$conditions['id'] = fix_id($tag_params['id']);

		// get single item from database
		$item = $manager->getSingleItem($manager->getFieldNames(), $conditions);

		// load template
		$template = $this->_parent->loadTemplate($tag_params, 'manufacturer_list_item.xml');
		$template->setTemplateParamsFromArray($children);

		if (is_object($item)) {
			// prepare parameters
			$params = array(
					'id'		=> $item->id,
					'name'		=> $item->name,
					'web_site'	=> $item->web_site,
					'logo'		=> $item->logo
				);

			// parse template
			$template->setLocalParams($params);
			$template->restoreXML();
			$template->parse();
		}
	}

	/**
	 * Handle drawing list of manufacturers tag
	 *
	 * @param array $tag_params
	 * @param array $children
	 */
	public function tag_ManufacturerList($tag_params, $children) {
		$manager = ShopManufacturerManager::getInstance();
		$conditions = array();
		$selected = -1;

		if (class_exists('gallery')) {
			$use_images = true;
			$gallery = gallery::getInstance();
			$gallery_manager = GalleryManager::getInstance();
		} else {
			$use_images = false;
		}

		if (isset($tag_params['selected']))
			$selected = fix_id($tag_params['selected']);

		$items = $manager->getItems($manager->getFieldNames(), $conditions);
		$template = $this->_parent->loadTemplate($tag_params, 'manufacturer_list_item.xml');
		$template->setTemplateParamsFromArray($children);

		if (count($items) > 0)
			foreach ($items as $item) {
				// get image
				$image = '';

				if ($use_images && !empty($item->logo)) {
					$image_item = $gallery_manager->getSingleItem(
											$gallery_manager->getFieldNames(),
											array('id' => $item->logo)
										);

					if (is_object($image_item))
						$image = $gallery->getImageURL($image_item);
				}

				// prepare parameters
				$params = array(
						'id'		=> $item->id,
						'name'		=> $item->name,
						'web_site'	=> $item->web_site,
						'logo'		=> $image,
						'selected'	=> $selected == $item->id ? 1 : 0,
						'item_change'	=> url_MakeHyperlink(
												$this->_parent->getLanguageConstant('change'),
												window_Open(
													'shop_manufacturer_change', 	// window id
													360,				// width
													$this->_parent->getLanguageConstant('title_manufacturer_change'), // title
													true, true,
													url_Make(
														'transfer_control',
														'backend_module',
														array('module', $this->name),
														array('backend_action', 'manufacturers'),
														array('sub_action', 'change'),
														array('id', $item->id)
													)
												)
											),
						'item_delete'	=> url_MakeHyperlink(
												$this->_parent->getLanguageConstant('delete'),
												window_Open(
													'shop_manufacturer_delete', 	// window id
													400,				// width
													$this->_parent->getLanguageConstant('title_manufacturer_delete'), // title
													false, false,
													url_Make(
														'transfer_control',
														'backend_module',
														array('module', $this->name),
														array('backend_action', 'manufacturers'),
														array('sub_action', 'delete'),
														array('id', $item->id)
													)
												)
											)
					);

				// parse template
				$template->setLocalParams($params);
				$template->restoreXML();
				$template->parse();
			}
	}
}

?>
