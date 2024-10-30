<?php

/**
 * The kwanko related code.
 *
 * @link	 https://www.kwanko.com
 * @since	 1.0.0
 *
 * @package	 	Kwanko_Tracking
 * @subpackage  Kwanko_Tracking/includes/kwanko
 */

/**
 * This class handles the persistance of the plugin settings.
 *
 * @package	 	Kwanko_Tracking
 * @subpackage  Kwanko_Tracking/includes/kwanko
 * @author	  	Kwanko <support@kwanko.com>
 */
class Kwanko_Tracking_Settings {

	/**
	 * @var string  name used in the configuration table
	 */
	const SETTINGS_KEY = 'kwanko_tracking_settings';

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var array
	 */
	protected $last_loaded_value = '';

	/**
	 * Create a new KwankoSettings instance.
	 * The default settings are loaded. You need to call the "load" method to load the settings from the database.
	 *
	 * @since   1.0.0
	 *
	 * @return  Kwanko_Tracking_Settings
	 */
	public function __construct() {

		$this->settings = $this->default_settings();

	}

	/**
	 * Return all the available settings with their default value.
	 *
	 * @since   1.0.0
	 *
	 * @return  array
	 */
	public function default_settings() {

		return array(
			'uniJsFileLastUpload' => null,
			'uniJsFileUrl' => '',
			'mclic' => '',
			'newCustomerMclic' => '',
			'cpaRetargeting' => false,
			'emailRetargeting' => false,
			'uniJsTracking' => true,
			'productIdType' => 'id',
			'firstPartyHost' => null,
		);

	}

	/**
	 * Return the value of the given setting.
	 *
	 * @since   1.0.0
	 *
	 * @param   string	$key
	 * @return  mixed
	 * @throws  Exception   If the key is not a valid setting, an exception is thrown.
	 */
	public function get($key) {

		if ( ! array_key_exists($key, $this->settings) ) {
			throw new \Exception('The key `' . (string) $key . '` is not a valid Kwanko setting in ' . __METHOD__ . '.');
		}

		return $this->settings[$key];

	}

	/**
	 * Set the value of the given setting. It does not persist the value in the database if you don't call the "save" method.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  $key
	 * @param   mixed   $value
	 * @throws  Exception   If the key is not a valid setting, an exception is thrown.
	 */
	public function set($key, $value) {

		if ( ! array_key_exists($key, $this->settings) ) {
			throw new \Exception('The key `' . (string) $key . '` is not a valid Kwanko setting in ' . __METHOD__ . '.');
		}

		$this->settings[$key] = $value;

	}

	/**
	 * Load the settings from the database. Only the valid settings are loaded.
	 *
	 * @since   1.0.0
	 */
	public function load() {

		$db_raw_settings = get_option(self::SETTINGS_KEY);
		$db_settings = $db_raw_settings ? json_decode($db_raw_settings, JSON_OBJECT_AS_ARRAY) : array();

		$this->last_loaded_value = $db_raw_settings;
		$this->settings = $this->normalize_settings($db_settings);

	}

	/**
	 * Save the settings in the database.
	 *
	 * @since   1.0.0
	 *
	 * @return  bool	false is the settings could not be persisted
	 */
	public function save() {

		$value = json_encode($this->settings);

		$res = update_option(self::SETTINGS_KEY, $value);

		return $res || $value === $this->last_loaded_value;

	}

	/**
	 * Remove the settings from the database.
	 *
	 * @since   1.0.0
	 *
	 * @return  bool	false is the settings could not be deleted
	 */
	public function delete() {

		return delete_option(self::SETTINGS_KEY);

	}

	/**
	 * Normalize the given settings.
	 * It returns the default settings, updated with the given settings.
	 * It a setting is not valid, it will not be in the returned settings.
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $settings
	 * @return  array
	 */
	public function normalize_settings($settings) {

		$normalized = $this->default_settings();

		foreach ( $normalized as $key => $value ) {
			if ( array_key_exists($key, $settings) ) {
				$normalized[$key] = $settings[$key];
			}
		}

		return $normalized;

	}
}
