<?php

/**
 * Configuration form.
 *
 * @link	https://www.kwanko.com
 * @since	1.0.0
 *
 * @package		Kwanko_Tracking
 * @subpackage	Kwanko_Tracking/admin/partials
 */

// check user capabilities
if ( ! current_user_can('manage_options') ) {
	return;
}

// show error/update messages
settings_errors('kwanko-tracking-messages');
?>

<div class="wrap">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
	<form method="post" enctype="multipart/form-data">
		<?php
		settings_fields('kwanko-tracking');
		do_settings_sections('kwanko-tracking');
		submit_button(__('Save Settings', 'kwanko-tracking'));
		?>
	</form>
</div>
