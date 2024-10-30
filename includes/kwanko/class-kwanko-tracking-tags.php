<?php

/**
 * The kwanko related code.
 *
 * @link	https://www.kwanko.com
 * @since	1.0.0
 *
 * @package		Kwanko_Tracking
 * @subpackage	Kwanko_Tracking/includes/kwanko
 */

/**
 * This class can be used to generate the code of the Kwanko tags
 * that should be included in the website to enable the tracking.
 *
 * @package		Kwanko_Tracking
 * @subpackage	Kwanko_Tracking/includes/kwanko
 * @author	 	Kwanko <support@kwanko.com>
 */
class Kwanko_Tracking_Tags {

	/**
	 * @var Kwanko_Tracking_Settings
	 */
	protected $settings;

	/**
	 * @var bool
	 */
	protected $ptag_displayed = false;

	/**
	 * Create a new KwankoTags instance.
	 *
	 * @since 1.0.0
	 *
	 * @param   Kwanko_Tracking_Settings	 $settings
	 * @return  Kwanko_Tracking_Tags
	 */
	public function __construct($settings) {

		$this->settings = $settings;

	}

	/**
	 * Check if the plugin has been configured.
	 *
	 * @since 1.0.0
	 *
	 * @return  bool
	 */
	public function is_configured() {

		$woocommerce_defined = class_exists('woocommerce');
		$uni_js_defined = $this->settings->get('uniJsFileLastUpload') !== null || $this->settings->get('uniJsFileUrl') !== '';

		return $woocommerce_defined && $uni_js_defined && $this->settings->get('mclic') !== '';

	}

	/**
	 * Check if the UniJS tag can be included in the page.
	 *
	 * @since 1.0.0
	 *
	 * @return  bool
	 */
	public function is_unijs_enabled() {

		return $this->is_configured() && $this->settings->get('uniJsTracking');

	}

	/**
	 * Return the url of the UniJS file.
	 *
	 * @since 1.0.0
	 *
	 * @param   bool	$force_protocol	  ensure that the url starts with http:// or https://
	 * @return  string
	 */
	public function get_unijs_file_url($force_protocol = false) {

		$url = $this->settings->get('uniJsFileUrl') !== ''
			? $this->settings->get('uniJsFileUrl')
			: $this->get_default_unijs_file_url() . '?v=' . $this->settings->get('uniJsFileLastUpload')
		;

		if ( !$force_protocol || strpos($url, 'https://') === 0 || strpos($url, 'http://') === 0 ) {
			return $url;
		}

		$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != '' ? 'https' : 'http';
		$separator = strpos($url, '//') !== 0 ? '://' : ':';

		return  $protocol . $separator . $url;

	}

	/**
	 * Return the default url of the UniJS file.
	 * It is the url that belongs to the uploaded file.
	 *
	 * @since 1.0.0
	 *
	 * @param   bool	$start_with_double_slash   false to remove the leading //
	 * @return  bool
	 */
	public function get_default_unijs_file_url($start_with_double_slash = true) {

		$url = plugins_url( 'public/js/uni.js', dirname( dirname( __FILE__ ) ) );

		$num_slashes_to_remove = $start_with_double_slash ? 0 : 2;

		// Replace http:// scheme with //
		if (substr($url, 0, 7) === 'http://') {
			$url = substr($url, - (strlen($url) - 5 - $num_slashes_to_remove));
		}

		// Replace https:// scheme with //
		if (substr($url, 0, 8) === 'https://') {
			$url = substr($url, - (strlen($url) - 6 - $num_slashes_to_remove));
		}

		return $url;

	}

	/**
	 * Return a code describing the configuration.
	 *
	 * @return  string
	 */
	public function get_config_code() {

		$code = '';
		$code .= class_exists('woocommerce') ? '1-' : '0-';
		$code .= $this->settings->get('uniJsFileLastUpload') !== null ? '1' : '0';
		$code .= $this->settings->get('uniJsFileUrl') !== '' ? '1' : '0';
		$code .= $this->settings->get('mclic') !== '' ? '1' : '0';
		$code .= $this->settings->get('newCustomerMclic') !== '' ? '1' : '0';
		$code .= $this->settings->get('cpaRetargeting') ? '1' : '0';
		$code .= $this->settings->get('emailRetargeting') ? '1' : '0';
		$code .= $this->settings->get('uniJsTracking') ? '1' : '0';
		$code .= $this->settings->get('productIdType') === 'sku' ? '1' : '0';
		$code .= $this->settings->get('firstPartyHost') !== null ? '1' : '0';
		return $code;

	}

	/**
	 * pTag, UniJS or old pTag depending on configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param   array   parameters given to the pTag
	 * @param   bool    force the pTag even if one was displayed before
	 * @return  bool
	 */
	public function ptag($params, $force = false) {

		return $this->is_unijs_enabled() ? $this->unijs_ptag($params, $force) : $this->old_ptag($params, $force);

	}

	/**
	 * UniJS pTag.
	 *
	 * @since 1.0.0
	 *
	 * @param   array   parameters given to the pTag
	 * @param   bool    force the pTag even if one was displayed before
	 */
	public function unijs_ptag($params, $force = false) {

		if ( ! $this->is_configured() || ( ! $force && $this->ptag_displayed ) ) {
			return false;
		}

		$this->ptag_displayed = true;

		wp_register_script( 'kwanko-footer', '' );
		wp_enqueue_script( 'kwanko-footer' );
		wp_add_inline_script( 'kwanko-footer' , 'KWKUniJS.ptag(' . json_encode($params) . ');' );

		return true;

	}

	/**
	 * Old pTag.
	 *
	 * @since 1.0.0
	 *
	 * @param   array   parameters given to the pTag
	 * @param   bool    force the pTag even if one was displayed before
	 * @return  bool
	 */
	public function old_ptag($params, $force = false) {

		if ( ! $this->is_configured() || ( ! $force && $this->ptag_displayed ) ) {
			return false;
		}

		$this->ptag_displayed = true;

		wp_register_script( 'kwanko-footer-old', esc_url( $this->get_old_ptag_url() ) );
		wp_enqueue_script( 'kwanko-footer-old' );

		wp_register_script( 'kwanko-footer', '' );
		wp_enqueue_script( 'kwanko-footer' );
		wp_add_inline_script( 'kwanko-footer' , 'window.ptag_params = ' . json_encode($params) . ';' );

		return true;

	}

	/**
	 * Alternative old pTag with the parameters inside the url.
	 *
	 * @since 1.0.0
	 *
	 * @param   array   parameters given to the pTag
	 * @return  bool
	 */
	public function old_ptag_alt($params) {

		if ( ! $this->is_configured() || $this->ptag_displayed ) {
			return false;
		}

		$this->ptag_displayed = true;

		wp_register_script( 'kwanko-footer-old-alt', esc_url( $this->get_old_ptag_url() . '?' . $this->build_query_params($params) ) );
		wp_enqueue_script( 'kwanko-footer-old-alt' );

		return true;

	}

	/**
	 * Old pTag url.
	 *
	 * @since 1.0.0
	 *
	 * @return  string
	 */
	public function get_old_ptag_url() {

		if ( ! $this->is_configured() ) {
			return '';
		}

		$m = Kwanko_Tracking_Mclic_Decoder::decode($this->settings->get('mclic'));

		return 'https://img.metaffiliation.com/u/' . ($m['p'] % 41) . '/p' . $m['p'] . '.js';

	}

	/**
	 * Customer parameters used in nearly all the pTags.
	 *
	 * @since 1.0.0
	 *
	 * @return  array
	 */
	public function customer_params() {

		$user = wp_get_current_user();

		return array(
			'customerId' => $user->ID != 0 ? (string) $user->ID : '',
			'siteType' => wp_is_mobile() ? 'm' : 'd',
			'm_md5' => $user->user_email != '' ? md5((string) $user->user_email) : '',
		);

	}

	/**
	 * Encode url query parameters.
	 * The mclic is handled differently if the first party mode is enabled.
	 *
	 * @since 1.2.0
	 *
	 * @param   array
	 * @return  string
	 */
	public function build_query_params($params) {

		$is_first_party = $this->is_unijs_enabled() && $this->settings->get('firstPartyHost') !== null;
		$has_mclic = array_key_exists('mclic', $params);

		if ( !$is_first_party || !$has_mclic ) {
			return http_build_query($params);
		}

		$mclic = $params['mclic'];
		$params = array() + $params;
		unset($params['mclic']);

		if ( count($params) === 0 ) {
			return $mclic;
		}

		return $mclic . '&' . http_build_query($params);

	}


	/**
	 * Homepage pTag.
	 *
	 * @since 1.0.0
	 *
	 * @return  bool
	 */
	public function ptag_homepage() {

		return $this->ptag($this->homepage_params());

	}

	/**
	 * Homepage parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return  array
	 */
	public function homepage_params() {

		return array_merge(array(
			'zone' => 'homepage',
		), $this->customer_params());

	}

	/**
	 * Product page pTag.
	 *
	 * @since 1.0.0
	 *
	 * @return  bool
	 */
	public function ptag_product() {

		return $this->ptag($this->product_params());

	}

	/**
	 * Product page parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return  array
	 */
	public function product_params() {

		// Do not use wc_get_product() and $product->get_category_ids()
		// to be compatible with old woocommerce versions.
		$product_post = get_post();
		$category_terms = get_the_terms($product_post->ID, 'product_cat');

		$category_id = '';

		foreach ( $category_terms as $category_term ) {
			$category_id = (string) $category_term->term_id;
			break;
		}

		return array_merge(array(
			'zone' => 'product',
			'productId' => $this->format_product_id($product_post->ID),
			'categoryId' => $category_id,
		), $this->customer_params());

	}

	/**
	 * Return the product is or the sku depending on the configuration.
	 * The parameter is the product id.
	 *
	 * @since 1.1.0
	 *
	 * @param 	int 	$product_id
	 * @return 	string
	 */
	protected function format_product_id($product_id) {

		return $this->settings->get('productIdType') === 'sku'
			? (string) get_post_meta($product_id, '_sku', true)
			: (string) $product_id
		;

	}

	/**
	 * Category page pTag.
	 *
	 * @since 1.0.0
	 *
	 * @return  bool
	 */
	public function ptag_category() {

		return $this->ptag($this->category_params());

	}

	/**
	 * Category page parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return  array
	 */
	public function category_params() {

		$category = get_queried_object();

		$products = isset($GLOBALS['wp_query']->posts) ? $GLOBALS['wp_query']->posts : array();
		$product_ids = array();

		foreach ( $products as $product ) {
			if ( isset($product->ID) ) {
				$product_ids[] = $this->format_product_id($product->ID);
			}
		}

		return array_merge(array(
			'zone' => 'listing',
			'categoryId' => (string) $category->term_id,
			'products' => $product_ids,
		), $this->customer_params());

	}

	/**
	 * Basket page pTag.
	 *
	 * @since 1.0.0
	 *
	 * @return  bool
	 */
	public function ptag_basket() {

		return $this->ptag($this->basket_params());

	}

	/**
	 * Basket page parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return  array|false
	 */
	public function basket_params() {

		$products = wc()->cart->get_cart();

		$formatted_products = array();

		foreach ( $products as $product ) {
			$price = function_exists('wc_get_price_including_tax')
				? (string) wc_get_price_including_tax($product['data'])
				: (string) $product['data']->get_price_including_tax()
			;

			$formatted_products[] = array(
				'id' => $this->format_product_id($product['product_id']),
				'price' => $price,
				'quantity' => (string) $product['quantity'],
			);
		}

		return array_merge(array(
			'zone' => 'basket',
			'products' => $formatted_products,
			'currency' => (string) get_woocommerce_currency(),
		), $this->customer_params());

	}

	/**
	 * Transaction pTag.
	 *
	 * @since 1.0.0
	 *
	 * @return  bool
	 */
	public function ptag_transaction() {

		return $this->ptag($this->transaction_params(), true);

	}

	/**
	 * Transaction parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return  array
	 */
	public function transaction_params() {

		$order_id = isset($GLOBALS['wp_query']->query['order-received']) ? $GLOBALS['wp_query']->query['order-received'] : -1;

		$order = function_exists('wc_get_order')
			? wc_get_order($order_id)
			: new WC_Order($order_id)
		;

		$formatted_products = array();

		foreach ( $order->get_items() as $item ) {
			$product = method_exists($item, 'get_product')
				? $item->get_product()
				: $order->get_product_from_item($item)
			;

			$product_id = method_exists($product, 'get_id')
				? (string) $product->get_id()
				: (string) $product->id
			;

			$price = function_exists('wc_get_price_including_tax')
				? (string) wc_get_price_including_tax($product)
				: (string) $product->get_price_including_tax()
			;

			$quantity = method_exists($item, 'get_quantity')
				? (string) $item->get_quantity()
				: (string) $item['qty']
			;

			$formatted_products[] = array(
				'id' => $this->format_product_id($product_id),
				'price' => $price,
				'quantity' => $quantity,
			);
		}

		$currency = method_exists($order, 'get_currency')
			? (string) $order->get_currency()
			: (string) $order->order_currency
		;

		$customer_params = $this->customer_params();

		// Use the billing email if the user is not logged in
		if ( $customer_params['m_md5'] === '' && $order->billing_email ) {
			$email_md5 = md5($order->billing_email);
			$customer_params['m_md5'] = $email_md5 ? $email_md5 : '';
		}

		return array_merge(array(
			'zone' => 'transaction',
			'products' => $formatted_products,
			'transactionId' => (string) $order_id,
			'currency' => $currency,
		), $customer_params);

	}

	/**
	 * Conversion page tag.
	 *
	 * @since 1.0.0
	 *
	 * @return  bool
	 */
	public function conversion_tag() {

		if ( ! $this->is_configured() ) {
			return false;
		}

		$params = $this->conversion_params();
		$params_n_mclic = array_merge(array(), $params, array('mclic' => Kwanko_Tracking_Mclic_Decoder::to_n_mclic($params['mclic'])));

		$first_party_host = $this->settings->get('firstPartyHost');
		$is_first_party = $this->is_unijs_enabled() && $first_party_host !== null;

		$base_img_src = $is_first_party ? 'https://' . $first_party_host . '?' : 'https://action.metaffiliation.com/trk.php?';


		if ( $this->is_unijs_enabled() ) {
			wp_register_script( 'kwanko-footer', '' );
			wp_enqueue_script( 'kwanko-footer' );
			wp_add_inline_script( 'kwanko-footer' , 'KWKUniJS.conversion(' . json_encode($params) . ');' );

			echo '<noscript><img src="' . esc_url( $base_img_src . $this->build_query_params($params_n_mclic) ) . '" width="1" height="1" border="0" /></noscript>';

			return true;
		}

		echo '<img src="' . esc_url( $base_img_src . $this->build_query_params($params) ) . '" width="1" height="1" border="0" />';

		return true;

	}

	/**
	 * Conversion page parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return  array
	 */
	public function conversion_params() {

		$order_id = isset($GLOBALS['wp_query']->query['order-received']) ? $GLOBALS['wp_query']->query['order-received'] : -1;

		$order = function_exists('wc_get_order')
			? wc_get_order($order_id)
			: new WC_Order($order_id)
		;

		$coupons = $order->get_used_coupons();

		$argmon = 0;

		foreach ( $order->get_items() as $item ) {
			$argmon += $item['line_total'];
		}

		$argmon = $argmon >= 0 ? $argmon : 0; // ensure argmon is postive

		$argmodp = method_exists($order, 'get_payment_method')
			? (string) $order->get_payment_method()
			: (string) $order->payment_method
		;

		$currency = method_exists($order, 'get_currency')
			? (string) $order->get_currency()
			: (string) $order->order_currency
		;

		// find user email, or billing email if the user is not logged in
		$user = wp_get_current_user();
		$email = $user->user_email != '' ? md5((string) $user->user_email) : '';

		if ( $email === '' && $order->billing_email ) {
			$email_md5 = md5($order->billing_email);
			$email = $email_md5 ? $email_md5 : '';
		}

		return array(
			'mclic' => Kwanko_Tracking_Mclic_Decoder::to_g_mclic($this->get_customer_mclic()),
			'argmon' => (string) $argmon,
			'argann' => (string) $order_id,
			'argmodp' => $argmodp,
			'nacur' => $currency,
			'altid' => $email,
			'argbr' => count($coupons) > 0 ? (string) $coupons[0] : '',
		);

	}

	/**
	 * Return mclic or newCustomerMclic dependending on the settings and the customer.
	 *
	 * @since 1.0.0
	 *
	 * @return  string
	 */
	public function get_customer_mclic() {

		$mclic = $this->settings->get('mclic');
		$new_customer_mclic = $this->settings->get('newCustomerMclic');

		if ( $new_customer_mclic === '' || $new_customer_mclic === $mclic ) {
			return $mclic;
		}

		$orders = get_posts(array(
			'numberposts' => -1,
			'meta_key' => '_customer_user',
			'meta_value' => get_current_user_id(),
			'post_type' => wc_get_order_types(),
			'post_status' => array_keys(wc_get_order_statuses()),
		));

		return count($orders) <= 1 ? $new_customer_mclic : $mclic;

	}

	/**
	 * Lead inscription tag.
	 *
	 * @since 1.0.0
	 *
	 * @return  bool
	 */
	public function lead_inscription() {

		if ( ! $this->is_configured() ) {
			return '';
		}

		$params = $this->lead_inscription_params();

		if ( $this->is_unijs_enabled() ) {
			return $this->unijs_ptag($params);
		}

		return $this->old_ptag_alt($params);

	}

	/**
	 * Lead inscription parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return  array
	 */
	public function lead_inscription_params() {

		return array(
			'zone' => 'lead_inscription',
			'm_md5' => '',
		);

	}

	/**
	 * Lead confirmation tag.
	 *
	 * @since 1.0.0
	 *
	 * @param   string  $user_id
	 * @return  bool
	 */
	public function lead_confirmation($user_id) {

		if ( ! $this->is_configured() ) {
			return false;
		}

		$params = $this->lead_confirmation_params($user_id);

		if ( $this->is_unijs_enabled() ) {
			return $this->unijs_ptag($params);
		}

		return $this->old_ptag_alt($params);

	}

	/**
	 * Lead confirmation parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param   string  $user_id
	 * @return  array
	 */
	public function lead_confirmation_params($user_id) {

		$user = wp_get_current_user();

		return array(
			'zone' => 'lead_confirmation',
			'id_lead' => (string) $user_id,
			'm_md5' => $user->user_email != '' ? md5((string) $user->user_email) : ''
		);

	}

}
