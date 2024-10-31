<?php

defined('ABSPATH') or die('No script kiddies please!');

/*
Plugin Name: Set in the Future
Plugin URI: TBD
Description: Allows you to alter the display of all of your posts (and pages) so they appear to be set into the future. So, instead of "September 26, 2016", it could say "September 26, 2246" (but still be stored at 2016). This is a cosmetic alteration only.
Version: 1.0
Author: Richard J Brum
Author URI: http://richardbrum.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl.html
*/

$default_years_into_future = 180;

add_action('admin_menu', 'sitf_rjb_future_options_menu');

function sitf_rjb_future_options_menu()
{
	add_options_page('How Far Into the Future?', 'Future Options', 'manage_options', 'future-options', 'sitf_rjb_future_options_contents');
}

function sitf_rjb_future_options_contents()
{
	global $default_years_into_future;
	
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page. Go away.'));
	}
	
	if (isset($_POST['num_years'])) {
		// We already check that current_user_can manage_options above, so this
		// should be OK in that regard
		if (wp_verify_nonce($_POST['_wpnonce'], 'sitf-rjb-set-num-years') == false) {
			// Invalid nonce!
			wp_nonce_ays();
			return; // exit the function
		}
		update_option('sitf_rjb_num_years_into_future', (int)$_POST['num_years']);
	}
	$num_years = get_option('sitf_rjb_num_years_into_future', $default_years_into_future);
	$success_msg = (isset($_GET['success']) && $_GET['success'] == 'yes') ? '<div class="updated form-success"><p><strong>Value successfully saved!</strong></p></div>' : '';
	
	echo <<<HTML

<div class="wrap">
	<h1>How Far Into the Future?</h1>
	{$success_msg}
	<form method="post">
HTML;
	
	wp_nonce_field('sitf-rjb-set-num-years');
	
	echo <<<HTML
		<p>
			<label for="num_years">Number of years into the future:</label>
			<input type="number" name="num_years" id="num_years" maxlength="4" value="{$num_years}">
		</p>
		<p><input type="submit" value="Save" class="button button-primary button-large"></p>
	</form>
</div>

HTML;
}

function sitf_rjb_set_post_into_future($post_info)
{
	global $default_years_into_future;
	$num_years_into_future = get_option('sitf_rjb_num_years_into_future', $default_years_into_future);
	
	$fields_to_modify = [
		'post_date',
		'post_date_gmt',
		'post_modified',
		'post_modified_gmt',
	];
	
	foreach ($fields_to_modify as $field_name) {
		// Alter year in date field
		$date_str = $post_info->$field_name;
		$date_obj = new DateTime($date_str);
		$date_obj->modify('+' . $num_years_into_future . 'years');
		$new_date_str = $date_obj->format('Y-m-d H:i:s');
		$post_info->$field_name = $new_date_str;
		unset($date_obj);
	}
	
	// Finito!
	return $post_info;
}

add_action('the_post', 'sitf_rjb_set_post_into_future');
