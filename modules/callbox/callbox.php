<?php

/**
 * Callbox
 *
 * Support for Callbox service.
 *
 * Author: Mladen Mijatov
 */
use Core\Module;


class callbox extends Module {
	private static $_instance;

	const URL_API = 'ssl://api.calltrackingmetrics.com';
	const URL_FORM_REACTOR = '/api/v1/formreactor/{reactor_id}';

	/**
	 * Constructor
	 */
	protected function __construct() {
		global $section;

		parent::__construct(__FILE__);

		// register backend
		if (class_exists('backend')) {
			$backend = backend::getInstance();

			$callbox_menu = new backend_MenuItem(
					$this->getLanguageConstant('menu_callbox'),
					url_GetFromFilePath($this->path.'images/icon.svg'),
					'javascript:void(0);',
					$level=5
				);

			$callbox_menu->addChild('', new backend_MenuItem(
								$this->getLanguageConstant('menu_settings'),
								url_GetFromFilePath($this->path.'images/settings.svg'),

								window_Open( // on click open window
											'callbox_settings',
											400,
											$this->getLanguageConstant('title_settings'),
											true, true,
											backend_UrlMake($this->name, 'settings')
										),
								$level=5
							));

			$backend->addMenu($this->name, $callbox_menu);
		}

		if (class_exists('head_tag') && $section != 'backend' && $this->settings['include_code']) {
			$head_tag = head_tag::getInstance();

			$url = str_replace('{id}', $this->settings['account_id'], '//{id}.tctm.co/t.js');
			$head_tag->addTag(
						'script',
						array(
							'src'	=> $url,
							'type'	=> 'text/javascript',
							'async'	=> ''
						)
					);
		}
	}

	/**
	 * Public function that creates a single instance
	 */
	public static function getInstance() {
		if (!isset(self::$_instance))
			self::$_instance = new self();

		return self::$_instance;
	}

	/**
	 * Transfers control to module functions
	 *
	 * @param array $params
	 * @param array $children
	 */
	public function transferControl($params = array(), $children = array()) {
		// global control actions
		if (isset($params['action']))
			switch ($params['action']) {
				case 'json_formreactor':
					$this->json_FormReactor();
					break;

				default:
					break;
			}

		// global control actions
		if (isset($params['backend_action']))
			switch ($params['backend_action']) {
				case 'settings':
					$this->showSettings();
					break;

				case 'settings_save':
					$this->saveSettings();
					break;

				default:
					break;
			}
	}

	/**
	 * Event triggered upon module initialization
	 */
	public function onInit() {
		$this->saveSetting('account_id', '');
		$this->saveSetting('account_key', '');
		$this->saveSetting('account_secret', '');
		$this->saveSetting('include_code', 0);
	}

	/**
	 * Event triggered upon module deinitialization
	 */
	public function onDisable() {
	}

	/**
	 * Show settings form.
	 */
	private function showSettings() {
		$template = new TemplateHandler('settings.xml', $this->path.'templates/');
		$template->setMappedModule($this->name);

		$params = array(
						'form_action'	=> backend_UrlMake($this->name, 'settings_save'),
						'cancel_action'	=> window_Close('callbox_settings')
					);

		$template->restoreXML();
		$template->setLocalParams($params);
		$template->parse();
	}

	/**
	 * Save settings.
	 */
	private function saveSettings() {
		// grab parameters
		$account_id = fix_chars($_REQUEST['account_id']);
		$account_key = fix_chars($_REQUEST['account_key']);
		$account_secret = fix_chars($_REQUEST['account_secret']);
		$include_code = isset($_REQUEST['include_code']) && ($_REQUEST['include_code'] == 'on' || $_REQUEST['include_code'] == '1') ? 1 : 0;

		$this->saveSetting('account_id', $account_id);
		$this->saveSetting('account_key', $account_key);
		$this->saveSetting('account_secret', $account_secret);
		$this->saveSetting('include_code', $include_code);

		// show message
		$template = new TemplateHandler('message.xml', $this->path.'templates/');
		$template->setMappedModule($this->name);

		$params = array(
					'message'	=> $this->getLanguageConstant('message_saved'),
					'button'	=> $this->getLanguageConstant('close'),
					'action'	=> window_Close('callbox_settings')
				);

		$template->restoreXML();
		$template->setLocalParams($params);
		$template->parse();
	}

	/**
	 * Submit form reactor data to the server.
	 */
	private function json_FormReactor() {
		$result = false;
		$reactor_id = fix_chars($_REQUEST['reactor_id']);
		$visitor_sid = fix_chars($_REQUEST['visitor_sid']);
		$account_key = $this->settings['account_key'];
		$account_secret = $this->settings['account_secret'];

		// exit if we are missing data
		if (empty($account_key) || empty($account_secret)) {
			trigger_error('Account key and/or secret are not properly configured!', E_USER_ERROR);
			print json_encode($result);
			return;
		}

		// prepare content
		$params = array(
				'phone_number',
				'country_code',
				'caller_name'
			);
		$data = array();
		$strip_slashes = get_magic_quotes_gpc();

		foreach($params as $param) {
			$value = $_REQUEST[$param];

			if ($strip_slashes)
				$value = stripslashes($value);

			$data[] = $param.'='.urlencode($value);
		}

		// add visitor session id
		$data['visitor_sid'] = $visitor_sid;

		// make query string
		$content = implode('&', $data);

		// prepare headers
		$api_path = str_replace('{reactor_id}', $reactor_id, callbox::URL_FORM_REACTOR);
		$header = "POST ".$api_path." HTTP/1.0\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\n";
		$header .= "Content-Length: ".strlen($content)."\n";
		$header .= "Authorization: Basic ".base64_encode($account_key.':'.$account_secret)."\n";
		$header .= "Connection: close\n\n";

		// connect to server and send data
		$socket = fsockopen(callbox::URL_API, 443, $error_number, $error_string, 30);

		if ($socket) {
			fputs($socket, $header.$content);
			$response = fgets($socket);
			$result = strpos($response, '200 OK') != false;
		}

		fclose($socket);

		print json_encode($result);
	}
}
