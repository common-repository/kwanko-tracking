<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link	https://www.kwanko.com
 * @since	1.0.0
 *
 * @package		Kwanko_Tracking
 * @subpackage	Kwanko_Tracking/admin
 */

/**
 * Configuration page.
 *
 * @package		Kwanko_Tracking
 * @subpackage	Kwanko_Tracking/admin
 * @author		Kwanko <support@kwanko.com>
 */
class Kwanko_Tracking_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		string	$plugin_name	The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		string	$version	The current version of this plugin.
	 */
	private $version;

	/**
	 * @var Kwanko_Tracking_Settings
	 */
	protected $settings;

	/**
	 * @var Kwanko_Tracking_Tags
	 */
	protected $tags;

	/**
	 * @var string
	 */
	protected $unijs_file_path;

	/**
	 * @var array
	 */
	protected $form_values;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since	1.0.0
	 *
	 * @param	string	$plugin_name	The name of this plugin.
	 * @param	string	$version		The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		try {
			// set dependencies
			$this->settings = new Kwanko_Tracking_Settings();
			$this->settings->load();

			$this->tags = new Kwanko_Tracking_Tags($this->settings);

			$this->unijs_file_path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'uni.js';

			// sync the uniJsFileLastUpload setting if the uniJs file does not exist
			if ( ! file_exists($this->unijs_file_path) && $this->settings->get('uniJsFileLastUpload') !== null) {
				$this->settings->set('uniJsFileLastUpload', null);
				$this->settings->save();
				$this->settings->load();
			}

		} catch (\Exception $e) {}

	}

	/**
	 * Add settings link in the plugin page.
	 *
	 * @since	1.0.0
	 */
	public function add_action_links($links) {

		$link = '<a href="'. admin_url('options-general.php?page=kwanko-tracking') .'">'.__('Settings', 'kwanko-tracking').'</a>';

		array_unshift($links, $link);

		return $links;

	}

	/**
	 * Register the settings page.
	 *
	 * @since	1.0.0
	 */
	public function add_option_page() {

		add_options_page(
			__('Kwanko - Tracking Tags for Advertisers', 'kwanko-tracking'),
			__('Kwanko - Tracking Tags for Advertisers', 'kwanko-tracking'),
			'manage_options',
			'kwanko-tracking',
   			array($this, 'settings_page')
		);

	}

	/**
	 * Show the settings page.
	 *
	 * @since	1.0.0
	 */
	public function settings_page() {

		$this->register_settings();

		if ( strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' ) {
			$this->form_values = array(
				'uniJsFile' => isset($_FILES['uniJsFile']['name']) ? sanitize_file_name($_FILES['uniJsFile']['name']) : '',
				'uniJsFileUrl' => isset($_POST['uniJsFileUrl']) ? sanitize_url($_POST['uniJsFileUrl']) : '',
				'mclic' => isset($_POST['mclic']) ? sanitize_text_field($_POST['mclic']) : '',
				'newCustomerMclic' => isset($_POST['newCustomerMclic']) ? sanitize_text_field($_POST['newCustomerMclic']) : '',
				'cpaRetargeting' => isset($_POST['cpaRetargeting']) ? (bool) $_POST['cpaRetargeting'] : false,
				'emailRetargeting' => isset($_POST['emailRetargeting']) ? (bool) $_POST['emailRetargeting'] : false,
				'uniJsTracking' => isset($_POST['uniJsTracking']) ? (bool) $_POST['uniJsTracking'] : true,
				'productIdType' => isset($_POST['productIdType']) ? sanitize_text_field($_POST['productIdType']) : 'id',
			);

			if ( $this->validate_form() ) {
				$this->save_form_values();
			}
		} else {
			$this->form_values = array(
				'uniJsFileUrl' => $this->settings->get('uniJsFileUrl'),
				'mclic' => $this->settings->get('mclic'),
				'newCustomerMclic' => $this->settings->get('newCustomerMclic'),
				'cpaRetargeting' => $this->settings->get('cpaRetargeting'),
				'emailRetargeting' => $this->settings->get('emailRetargeting'),
				'uniJsTracking' => $this->settings->get('uniJsTracking'),
				'productIdType' => $this->settings->get('productIdType'),
			);
		}

		// check if woocommerce is activated
		if ( ! class_exists('woocommerce') ) {
			add_settings_error('kwanko-tracking-messages', 'kwanko-tracking-err-wc', __('This plugin can not work without WooCommerce and WooCommerce is not activated !', 'kwanko-tracking'), 'error');
		}

		require dirname(__FILE__).'/partials/kwanko-tracking-admin-display.php';

		// Add style.
		wp_register_style( 'kwanko', '' );
		wp_enqueue_style( 'kwanko' );
		wp_add_inline_style( 'kwanko' , '
table.form-table {
	margin-top: 30px;
	margin-bottom: 30px;
}
.kwanko-hidden-field {
	display: none;
}
		' );

		// Add javascript. Show hidden inputs by default if UniJsTracking is disabled.
		$show_hidden_inputs_code = isset($this->form_values['uniJsTracking']) && !$this->form_values['uniJsTracking']
			? 'window.kwanko_show_hidden_inputs();'
			: '';

		wp_register_script( 'kwanko', '', array(), false, true );
		wp_enqueue_script( 'kwanko' );
		wp_add_inline_script( 'kwanko' , '
(function() {
	var uniJsRow = document.querySelector(".unijs-tracking-field");
	var uniJsInput = document.getElementById("uniJsTracking");

	if (!uniJsRow
		&& uniJsInput
		&& uniJsInput.parentNode
		&& uniJsInput.parentNode.tagName === "TD"
		&& uniJsInput.parentNode.parentNode
		&& uniJsInput.parentNode.parentNode.tagName === "TR"
	) {
		// for old versions of wordpress
		uniJsRow = uniJsInput.parentNode.parentNode;
		uniJsRow.classList.add("kwanko-hidden-field");
	}

	window.kwanko_show_hidden_inputs = function() {
		if (uniJsRow) {
			uniJsRow.classList.remove("kwanko-hidden-field");
		}
	};

	' . $show_hidden_inputs_code . '
})();
		' );

	}

	/**
	 * Format and validate the form values.
	 * Add error messages with add_settings_error.
	 *
	 * @since	1.0.0
	 *
	 * @return  bool	true if the form values are valid
	 */
	protected function validate_form() {

		// ensure that the UniJS file is uploaded if it has not been uploaded before
		$this->form_values['uniJsFileUrl'] = trim($this->form_values['uniJsFileUrl']);

		if ( $this->settings->get('uniJsFileLastUpload') === null && $this->form_values['uniJsFile'] === '' && $this->form_values['uniJsFileUrl'] === '' ) {
			add_settings_error('kwanko-tracking-messages', 'kwanko-tracking-err-unijs', __('You need to upload the UniJS file.', 'kwanko-tracking'), 'error');
			return false;
		}

		// format and validate mclic
		$this->form_values['mclic'] = trim($this->form_values['mclic']);
		$this->form_values['newCustomerMclic'] = trim($this->form_values['newCustomerMclic']);

		if ( $this->form_values['mclic'] === '' ) {
			add_settings_error('kwanko-tracking-messages', 'kwanko-tracking-err-mclic-empty', __('The MCLIC can not be empty.', 'kwanko-tracking'), 'error');
			return false;
		}

		if ( Kwanko_Tracking_Mclic_Decoder::decode($this->form_values['mclic']) === false ) {
			add_settings_error('kwanko-tracking-messages', 'kwanko-tracking-err-mclic', __('The MCLIC is not valid.', 'kwanko-tracking'), 'error');
			return false;
		}

		if ( $this->form_values['newCustomerMclic'] !== '' && Kwanko_Tracking_Mclic_Decoder::decode($this->form_values['newCustomerMclic']) === false ) {
			add_settings_error('kwanko-tracking-messages', 'kwanko-tracking-err-mclic-new', __('The MCLIC for the new customers is not valid.', 'kwanko-tracking'), 'error');
			return false;
		}

		if ( !in_array($this->form_values['productIdType'], ['id', 'sku']) ) {
			add_settings_error('kwanko-tracking-messages', 'kwanko-tracking-err-product-id-type', __('The type of product ID is not valid.', 'kwanko-tracking'), 'error');
			return false;
		}

		return true;

	}

	/**
	 * Save the form values in the database.
	 * Add success or error messages with add_settings_error.
	 *
	 * @since	1.0.0
	 */
	protected function save_form_values() {

		if ( $this->form_values['uniJsFile'] ) {
			if ( ! isset($_FILES['uniJsFile']['tmp_name']) || empty($_FILES['uniJsFile']['tmp_name']) ) {
				add_settings_error('kwanko-tracking-messages', 'kwanko-tracking-err-unijs-up', __('Could not upload the UniJS file.', 'kwanko-tracking'), 'error');
				return;
			}

			if ( ! move_uploaded_file($_FILES['uniJsFile']['tmp_name'], $this->unijs_file_path) ) {
				add_settings_error('kwanko-tracking-messages', 'kwanko-tracking-err-unijs-up', __('Could not upload the UniJS file.', 'kwanko-tracking'), 'error');
				return;
			}

			$this->settings->set('uniJsFileLastUpload', time());
		}

		$this->settings->set('uniJsFileUrl', $this->form_values['uniJsFileUrl']);
		$this->settings->set('mclic', $this->form_values['mclic']);
		$this->settings->set('newCustomerMclic', $this->form_values['newCustomerMclic']);
		$this->settings->set('cpaRetargeting', $this->form_values['cpaRetargeting']);
		$this->settings->set('emailRetargeting', $this->form_values['emailRetargeting']);
		$this->settings->set('uniJsTracking', $this->form_values['uniJsTracking']);
		$this->settings->set('productIdType', $this->form_values['productIdType']);

		$err = $this->set_first_party_setting();
		if ( $err !== null ) {
			add_settings_error('kwanko-tracking-messages', 'kwanko-tracking-err-unijs-first-party', $err, 'error');
			return;
		}

		if ( ! $this->settings->save() ) {
			add_settings_error('kwanko-tracking-messages', 'kwanko-tracking-err-save', __('The settings could not be saved.', 'kwanko-tracking'), 'error');
			return;
		}

		add_settings_error('kwanko-tracking-messages', 'kwanko-tracking-updated', __('Settings updated', 'kwanko-tracking'), 'updated');

	}

	/**
	 * Set the firstPartyHost setting based on the public uniJS file.
	 *
	 * @since	1.2.0
	 *
	 * @return  mixed   The error string if there is one. null if there was no error.
	 */
	protected function set_first_party_setting() {

		if ( !$this->tags->is_unijs_enabled() ) {
			$this->settings->set('firstPartyHost', null);
			return null;
		}

		$content = $this->get_unijs_file_content();
		if ( $content === false ) {
			return __('Could not read the UniJS file from its public url', 'kwanko-tracking'). ' (' . $this->tags->get_unijs_file_url(true) . ').';
		}

		$enabled = null;
		$host = null;

		// Try to read TRK_FIRST_PARTY string with double quotes
		preg_match('/TRK_FIRST_PARTY\s*:\s*"([^"]*)"/', $content, $matches);

		if ( count($matches) === 2 ) {
			$enabled = $matches[1] === '1';
		}

		// Try to read TRK_FIRST_PARTY string with single quotes
		preg_match("/TRK_FIRST_PARTY\s*:\s*'([^']*)'/", $content, $matches);

		if ( count($matches) === 2 ) {
			$enabled = $matches[1] === '1';
		}

		// Try to read TRK_HOST string with double quotes
		preg_match('/TRK_HOST\s*:\s*"([^"]*)"/', $content, $matches);

		if ( count($matches) === 2 ) {
			$host = $matches[1];
		}

		// Try to read TRK_HOST string with single quotes
		preg_match("/TRK_HOST\s*:\s*'([^']*)'/", $content, $matches);

		if ( count($matches) === 2 ) {
			$host = $matches[1];
		}

		if ( $enabled === null || $host === null ) {
			return __('Could not parse UniJS file content', 'kwanko-tracking') . ' (' . $this->tags->get_unijs_file_url(true) . '). ' . __('Are you sure it is the right file ?', 'kwanko-tracking');
		}

		$this->settings->set('firstPartyHost', $enabled ? $host : null);

		return null;

	}

	/**
	 * Return the content of the UniJS file.
	 *
	 * @since	1.2.1
	 *
	 * @return string
	 */
	protected function get_unijs_file_content() {

		// The file was uploaded, check it on the file system.
		if ( $this->settings->get('uniJsFileUrl') === '' ) {
			$content = @file_get_contents($this->unijs_file_path);
			if ( $content !== false ) {
				return $content;
			}
		}

		$unijs_file_url = $this->tags->get_unijs_file_url(true);

		return wp_remote_retrieve_body( wp_remote_get( $unijs_file_url ) );

	}


	/**
	 * Register the plugin settings.
	 *
	 * @since	1.0.0
	 */
	protected function register_settings() {

		// unijs section
		add_settings_section(
			'kwanko-tracking-form-section-unijs',
			'',
			function() {},
			'kwanko-tracking'
		);

		add_settings_field(
			'kwanko-tracking-form-unijs-file',
			__('UniJS File Upload', 'kwanko-tracking'),
			array($this, 'settings_unijs_file'),
			'kwanko-tracking',
			'kwanko-tracking-form-section-unijs',
			array(
				'label_for' => 'uniJsFile',
				'class' => '',
			)
		);

		add_settings_field(
			'kwanko-tracking-form-unijs-url',
			'<strong style="margin-right: 10px; color: #0073aa;">'.__('OR', 'kwanko-tracking').'</strong>'.__('UniJS File URL', 'kwanko-tracking'),
			array($this, 'settings_unijs_url'),
			'kwanko-tracking',
			'kwanko-tracking-form-section-unijs',
			array(
				'label_for' => 'uniJsFileUrl',
				'class' => '',
			)
		);

		// mclic section
		add_settings_section(
			'kwanko-tracking-form-section-mclic',
			'',
			function () {},
			'kwanko-tracking'
		);

		add_settings_field(
			'kwanko-tracking-form-mclic',
			__('MCLIC', 'kwanko-tracking'),
			array($this, 'settings_mclic'),
			'kwanko-tracking',
			'kwanko-tracking-form-section-mclic',
			array(
				'label_for' => 'mclic',
				'class' => '',
			)
		);

		add_settings_field(
			'kwanko-tracking-form-new-cutomer-mclic',
			__('MCLIC for new customers', 'kwanko-tracking'),
			array($this, 'settings_new_customer_mclic'),
			'kwanko-tracking',
			'kwanko-tracking-form-section-mclic',
			array(
				'label_for' => 'newCustomerMclic',
				'class' => '',
			)
		);

		// enabling section
		add_settings_section(
			'kwanko-tracking-form-section-enabling',
			'',
			function () {},
			'kwanko-tracking'
		);

		add_settings_field(
			'kwanko-tracking-form-cpa-retargeting',
			__('Enable CPA retargeting', 'kwanko-tracking'),
			array($this, 'settings_cpa_retargeting'),
			'kwanko-tracking',
			'kwanko-tracking-form-section-enabling',
			array(
				'label_for' => 'cpaRetargeting',
				'class' => '',
			)
		);

		add_settings_field(
			'kwanko-tracking-form-email-retargeting',
			__('Enable email retargeting', 'kwanko-tracking'),
			array($this, 'settings_email_retargeting'),
			'kwanko-tracking',
			'kwanko-tracking-form-section-enabling',
			array(
				'label_for' => 'emailRetargeting',
				'class' => '',
			)
		);

		add_settings_field(
			'kwanko-tracking-form-unijs-tracking',
			__('Enable latest version of the trackers', 'kwanko-tracking'),
			array($this, 'settings_unijs_tracking'),
			'kwanko-tracking',
			'kwanko-tracking-form-section-enabling',
			array(
				'label_for' => 'uniJsTracking',
				'class' => 'unijs-tracking-field kwanko-hidden-field',
			)
		);

		// woocommerce section
		add_settings_section(
			'kwanko-tracking-form-section-woocommerce',
			'',
			function () {},
			'kwanko-tracking'
		);

		add_settings_field(
			'kwanko-tracking-form-product-id-type',
			__('Type of product ID', 'kwanko-tracking'),
			array($this, 'settings_product_id_type'),
			'kwanko-tracking',
			'kwanko-tracking-form-section-woocommerce',
			array(
				'label_for' => 'productIdType',
				'class' => '',
			)
		);

	}

	/**
	 * uniJsFile input.
	 *
	 * @since	1.0.0
	 */
	public function settings_unijs_file() {

		echo '<input name="uniJsFile" type="file" id="uniJsFile" aria-describedby="uniJsFile-description" />
<p class="description" id="uniJsFile-description">';

		if ( $this->settings->get('uniJsFileLastUpload') === null ) {
			echo esc_html__('You need to upload the UniJS file provided by kwanko.', 'kwanko-tracking');
		} else {
			echo esc_html__('You have already uploaded the UniJS file to', 'kwanko-tracking') . ' ';
			echo esc_url($this->tags->get_default_unijs_file_url(false)) . '<br>';
			echo esc_html__('You can update it if you want.', 'kwanko-tracking');
		}

		echo '</p>';

}

	/**
	 * uniJsFileUrl input.
	 *
	 * @since	1.0.0
	 */
	public function settings_unijs_url() {

		$value = isset($this->form_values['uniJsFileUrl']) ? $this->form_values['uniJsFileUrl'] : '';

		echo '<input name="uniJsFileUrl" type="text" id="uniJsFileUrl" aria-describedby="uniJsFileUrl-description" value="'.esc_url($value).'" placeholder="https://" class="regular-text" />
<p class="description" id="uniJsFileUrl-description">';
		echo esc_html__('Set this URL to use a UniJS file you have manually uploaded on your server or cdn.', 'kwanko-tracking'). '<br>';
		echo esc_html__('Leave this field empty to use the UniJS file uploaded with this form.', 'kwanko-tracking');
		echo '</p>';

	}

	/**
	 * mclic input.
	 *
	 * @since	1.0.0
	 */
	public function settings_mclic() {

		$ph = __('Example: G51869F51869F11', 'kwanko-tracking');
		$descr = __('The advertising campaign identifier. You can find it in the tracking url as the mclic query parameter. Example: G51869F51869F11', 'kwanko-tracking');

		$value = isset($this->form_values['mclic']) ? $this->form_values['mclic'] : '';

		echo '<input name="mclic" type="text" id="mclic" aria-describedby="mclic-description" value="'.esc_attr($value).'" placeholder="'.esc_attr($ph).'" class="regular-text" required="required" />
<p class="description" id="mclic-description">'.esc_html($descr).'</p>';

	}

	/**
	 * newCustomerMclic input.
	 *
	 * @since	1.0.0
	 */
	public function settings_new_customer_mclic() {

		$ph = __('Example: G51869F51869F11', 'kwanko-tracking');
		$descr = __('The advertising campaign identifier for a customer\'s first purchase. If not defined, the other MCLIC will be used all the time.', 'kwanko-tracking');

		$value = isset($this->form_values['newCustomerMclic']) ? $this->form_values['newCustomerMclic'] : '';

		echo '<input name="newCustomerMclic" type="text" id="newCustomerMclic" aria-describedby="newCustomerMclic-description" value="'.esc_attr($value).'" placeholder="'.esc_attr($ph).'" class="regular-text" />
<p class="description" id="newCustomerMclic-description">'.esc_html($descr).'</p>';

	}

	/**
	 * cpaRetargeting input.
	 *
	 * @since	1.0.0
	 */
	public function settings_cpa_retargeting() {

		$descr = __('The CPA retargeting trackers are activated when a customer see the homepage, the category pages, the product page, the basket page and the confirmation page.', 'kwanko-tracking');

		$enabled = isset($this->form_values['cpaRetargeting']) && $this->form_values['cpaRetargeting'];

		echo '<select name="cpaRetargeting" id="cpaRetargeting" aria-describedby="cpaRetargeting-description">
	<option value="1" '.($enabled ? 'selected' : '').'>'.esc_html__('Enabled', 'kwanko-tracking').'</option>
	<option value="0" '.($enabled ? '' : 'selected').'>'.esc_html__('Disabled', 'kwanko-tracking').'</option>
</select>
<p class="description" id="cpaRetargeting-description">'.esc_html($descr).'</p>';

	}

	/**
	 * emailRetargeting input.
	 *
	 * @since	1.0.0
	 */
	public function settings_email_retargeting() {

		$descr = __('The email retargeting trackers are activated when a customer see or fill the registration form.', 'kwanko-tracking');

		$enabled = isset($this->form_values['emailRetargeting']) && $this->form_values['emailRetargeting'];

		echo '<select name="emailRetargeting" id="emailRetargeting" aria-describedby="emailRetargeting-description">
	<option value="1" '.($enabled ? 'selected' : '').'>'.esc_html__('Enabled', 'kwanko-tracking').'</option>
	<option value="0" '.($enabled ? '' : 'selected').'>'.esc_html__('Disabled', 'kwanko-tracking').'</option>
</select>
<p class="description" id="emailRetargeting-description">'.esc_html($descr).'</p>';

	}

	/**
	 * uniJsTracking input.
	 *
	 * @since	1.0.0
	 */
	public function settings_unijs_tracking() {

		$descr = __('Must be enabled to ensure the best tracking experience. Talk to your contact at Kwanko before disabling this option.', 'kwanko-tracking');

		$enabled = isset($this->form_values['uniJsTracking']) && $this->form_values['uniJsTracking'];

		echo '<select name="uniJsTracking" id="uniJsTracking" aria-describedby="uniJsTracking-description">
	<option value="1" '.($enabled ? 'selected' : '').'>'.esc_html__('Enabled', 'kwanko-tracking').'</option>
	<option value="0" '.($enabled ? '' : 'selected').'>'.esc_html__('Disabled', 'kwanko-tracking').'</option>
</select>
<p class="description" id="uniJsTracking-description">'.esc_html($descr).'</p>';

	}

	/**
	 * productIdType input.
	 *
	 * @since	1.1.0
	 */
	public function settings_product_id_type() {

		$descr = __('Type of product ID used in the tracking. If using a SKU, ensure that all your products have one. If you are not sure, use the product ID.', 'kwanko-tracking');

		$skuSelected = isset($this->form_values['productIdType']) && $this->form_values['productIdType'] === 'sku';

		echo '<select name="productIdType" id="productIdType" aria-describedby="productIdType-description">
	<option value="id" '.($skuSelected ? '' : 'selected').'>'.esc_html__('Product ID', 'kwanko-tracking').'</option>
	<option value="sku" '.($skuSelected ? 'selected' : '').'>'.esc_html__('SKU', 'kwanko-tracking').'</option>
</select>
<p class="description" id="productIdType-description">'.esc_html($descr).'</p>';

	}

}
