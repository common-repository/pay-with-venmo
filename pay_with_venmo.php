<?php
/*
Plugin Name: Pay with Venmo
Description: Add simple form to collect payment with Venmo (https://venmo.com) to your page/post.
Author: Alex L.
Version: 1.2.5
Text Domain: pay-with-venmo
Author URI: https://qatsys.com

Pay with Venmo plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Pay with Venmo plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Pay with Venmo plugin. If not, see email us: info@qatsys.com.
*/
global $wpdb;

define('PAY_WITH_VENMO_PLUGIN_DIR',str_replace('\\','/',dirname(__FILE__)));

register_activation_hook( __FILE__, 'pay_with_venmo_activate');

register_deactivation_hook( __FILE__, 'pay_with_venmo_deactivate');

register_uninstall_hook(__FILE__, 'pay_with_venmo_uninstall');

global $pay_with_venmo_version;

$pay_with_venmo_version = '1.2.3';

function pay_with_venmo_deactivate(){
	//Maybe strip all shortcodes ? Crazy!
}

function pay_with_venmo_activate(){
	global $wpdb;
	global $pay_with_venmo_version;
	
	$sql = 'CREATE TABLE '.$wpdb->prefix."pay_with_venmo".' ( 
		id int(11) NOT NULL AUTO_INCREMENT,
		venmoUrl text NOT NULL,
		settings text NOT NULL,
		dateUpdated datetime NOT NULL, 
		PRIMARY KEY  (id) 
		);';

	require_once( ABSPATH.'/wp-admin/includes/upgrade.php' );
  	
	dbDelta($sql);

	add_option( 'pay_with_venmo_version', $pay_with_venmo_version );
}

function pay_with_venmo_uninstall(){
	global $wpdb;

	//drop table
	$table_name = $wpdb->prefix."pay_with_venmo";

    $sql = 'DROP TABLE IF EXISTS '.$wpdb->prefix."pay_with_venmo";
    
    require_once( ABSPATH.'/wp-admin/includes/upgrade.php' );
    
    $wpdb->query($sql);
}

add_action('admin_menu', 'pay_with_venmo_pages');
function pay_with_venmo_pages(){
    add_menu_page('Pay with Venmo', 'Pay with Venmo', 'manage_options', 'pay-with-venmo', 'pay_with_venmo_main_page' );
}

function pay_with_venmo_getUrls(){
	global $wpdb;
	return $wpdb->get_results( 'SELECT * FROM '.$wpdb->prefix."pay_with_venmo");
}

function pay_with_venmo_one_form($preview){
	if(pay_with_venmo_getUrls()){
		$id = pay_with_venmo_getUrls()[0]->id;
		$venmoUrl = pay_with_venmo_getUrls()[0]->venmoUrl;
	}else{
		$id = "undefined";
		$venmoUrl = "https://venmo.com";
	}
	$one_form = '
	<script>
		function SendVenmo(){
			var amount = document.getElementById("pay_with_venmo_amount_field").value;
			var description = document.getElementById("pay_with_venmo_description_field").value;
			if (isNaN(amount) || amount=="" || amount==null || description=="" || description==null){
				document.getElementById("error").style.display = "block";
			}else{
				var venmo_url = '.$id.';
				window.open("'.$venmoUrl.'"+"?amount="+amount+"&note="+description, "_blank");
				document.getElementById("error").style.display = "none";
			}
		}
	</script>
	<div id="pay_with_venmo_container">
		<div class="sample_form">
			<input type="text" style="margin-bottom:5px; width:50%;" id="pay_with_venmo_amount_field" placeholder="Enter amount" name="amount_field"></br>
			<textarea name="description_field" rows="4" style="width:50%;" id="pay_with_venmo_description_field" placeholder="Enter description"></textarea></br>
			<button type="button" class="button button-primary btn btn-primary" onclick="SendVenmo()" '.$preview.'>Send Venmo</button>
			<label id="error" style="display:none; color:red;">Both fields are required</label>
		</div>
	</div>';
	return $one_form;
}

function pay_with_venmo_dropdown_form($preview){
	if(pay_with_venmo_getUrls()){
		$id = pay_with_venmo_getUrls()[0]->id;
		$venmoUrl = pay_with_venmo_getUrls()[0]->venmoUrl;
		$amounts = explode(",",pay_with_venmo_getUrls()[0]->settings);
	}else{
		$id = "undefined";
		$venmoUrl = "https://venmo.com";
		$amounts = "";
	}
	$dropdown_form = '
	<script>
		function SendVenmoDropdown(){
			var amount = document.getElementById("pay_with_venmo_amount_field_dropdown").value;
			var description = document.getElementById("pay_with_venmo_description_field_dropdown").value;
			if (description=="" || description==null){
				document.getElementById("error").style.display = "block";
			}else{
				var venmo_url = '.$id.';
				window.open("'.$venmoUrl.'"+"?amount="+amount+"&note="+description, "_blank");
				document.getElementById("error").style.display = "none";
			}
		}
	</script>
	<div id="pay_with_venmo_dropdown_container">
		<div class="sample_form" style="float:right; width:50%;">
			<select id="pay_with_venmo_amount_field_dropdown" style="width:50%;">
				<option selected disabled>Select amount</option>';
	if($amounts != ""){
		foreach ($amounts as $amount){
			$dropdown_form .= '<option value='.$amount.'>$'.$amount.'</option>';
		}
	}
	$dropdown_form .= '</select>
			</br>
			<textarea name="description_field" rows="4" style="width:50%;" id="pay_with_venmo_description_field_dropdown" placeholder="Enter description"></textarea></br>
			<button type="button" class="button button-primary btn btn-primary" onclick="SendVenmoDropdown()" '.$preview.'>Send Venmo</button>
			<label id="error" style="display:none; color:red;">Both fields are required</label>
		</div>
	</div>';
	return $dropdown_form;
}

function pay_with_venmo_admin_notice__success() {
	echo $output ='
    <div class="notice notice-success is-dismissible">
        <p>Changes successfully saved!</p>
    </div>';
}

function pay_with_venmo_admin_notice__error() {
	echo $output ='
    <div class="notice notice-error is-dismissible">
        <p>Oups, An error has occurred...</p>
    </div>';
}

add_action( 'plugins_loaded', 'pay_with_venmo_save_url_form_submit' );
function pay_with_venmo_save_url_form_submit(){
	global $wpdb;
	global $pay_with_venmo_version;
	
	$installed_ver = get_option("pay_with_venmo_version");

	if ( $installed_ver != $pay_with_venmo_version ) {
		pay_with_venmo_activate();
	}

	if( isset($_POST['_wpnonce']) && isset($_POST['venmo_url']) && check_admin_referer('pay_with_venmo_nonce') && current_user_can('administrator') ){

		$venmo_url = $_POST['venmo_url'];
		
		if(substr($venmo_url, -1) == "/"){
			$venmo_url = substr($venmo_url, 0, -1);
		}
		
		$venmo_master_id = (int)$_POST['pay_with_venmo_id'];

		require_once(ABSPATH.'wp-admin/includes/upgrade.php');

		if ( count(pay_with_venmo_getUrls() ) > 0){
			$sql = 'UPDATE '.$wpdb->prefix."pay_with_venmo".' SET venmoUrl="'.$venmo_url.'" WHERE id="'.$venmo_master_id.'"';
			$result = $wpdb->query($sql);
		}else{
			$sql = 'INSERT INTO '.$wpdb->prefix."pay_with_venmo".' (venmoUrl, dateUpdated) VALUES ("'.$venmo_url.'","'.date("Y-m-d h:i:sa").'")';
			$result = $wpdb->query($sql);
			//dbDelta($sql);
		}
		if ($result === false){
			add_action( 'admin_notices', 'pay_with_venmo_admin_notice__error' );
		}else{
			add_action( 'admin_notices', 'pay_with_venmo_admin_notice__success' );
		}
	}
	//Save amounts for the dropdown
	if( isset($_POST['_wpnonce']) && isset($_POST['dropdown_amounts']) && check_admin_referer('pay_with_venmo_nonce') && current_user_can('administrator') ){

		$amounts = $_POST['dropdown_amounts'];

		$venmo_master_id = (int)$_POST['pay_with_venmo_id'];

		require_once(ABSPATH.'wp-admin/includes/upgrade.php');

		if ( count(pay_with_venmo_getUrls() ) > 0){
			$sql = 'UPDATE '.$wpdb->prefix."pay_with_venmo".' SET settings="'.$amounts.'" WHERE id="'.$venmo_master_id.'"';
			$result = $wpdb->query($sql);
		}else{
			$sql = 'INSERT INTO '.$wpdb->prefix."pay_with_venmo".' (settings, dateUpdated) VALUES ("'.$amounts.'","'.date("Y-m-d h:i:sa").'")';
			$result = $wpdb->query($sql);
			//dbDelta($sql);
		}
		if ($result === false){
			add_action( 'admin_notices', 'pay_with_venmo_admin_notice__error' );
		}else{
			add_action( 'admin_notices', 'pay_with_venmo_admin_notice__success' );
		}
	}
}

function pay_with_venmo_main_page(){
	if(pay_with_venmo_getUrls()){
		$id = pay_with_venmo_getUrls()[0]->id;
		$venmoUrl = pay_with_venmo_getUrls()[0]->venmoUrl;
		$static_amounts = pay_with_venmo_getUrls()[0]->settings;
	}else{
		$id = "undefined";
		$venmoUrl = "";
		$static_amounts = "";
	}

	// URL format: https://venmo.com/xxx-xxx
	$output = '
	<script>
		function validateVenmoUrl(){
			var url = document.forms["pay_with_venmo_form"]["pay_with_venmo_url"].value.trim();
		    if ( (url == "") || (!url.startsWith("https://venmo.com/")) ) {
		        document.getElementById("venmo_url_error").style.display = "block";
		        return false;
		    }
		}
		function validateDropdownValues(){
			var amounts = document.forms["pay_with_venmo_dropdown_config"]["dropdown_amounts"].value.trim().split(",");
			if( (amounts == "") || (amounts.every(validateAmounts) == false) ){
				document.getElementById("venmo_dropdown_amounts_error").style.display = "block";
				return false;
			}
		}
		function validateAmounts(item){
			return !isNaN(item);
		}
	</script>
	<h3 style="margin-top:10px;">Your VENMO account url:</h3>
	<form name="pay_with_venmo_form" method="post" onsubmit="return validateVenmoUrl()">';
	$output .= wp_nonce_field('pay_with_venmo_nonce');
	$output .='
		<input type="hidden" name="pay_with_venmo_id" value="'.$id.'">
		<label id="venmo_url_error" style="color:red; display:none;">Supported format: https://venmo.com/xxxxx</label>
		<input style="width:50%;" type="text" id="pay_with_venmo_url" name="venmo_url" value="'.sanitize_text_field($venmoUrl).'">
		<input type="submit" class="button button-primary btn btn-primary" name="test_button" value="Submit">
	</form>';

	//$output .= '<p>To begin collecting payments with venmo just add this shortcode <strong>[pay_with_venmo]</strong> to the post or page.</p>';
	$output .= '<p>Enter url to your venmo profile in to the field above in format: "https://venmo.com/xxx-xxx"</p>';

	$output .= '<hr><div id="pay_with_venmo_layout_container" style="width:100%;"><div id="left_container" style="width:50%; float:left;">
	To embed the form on the page use this shortcode: <strong>[pay_with_venmo]</strong></br><strong>Form example:</strong>'.pay_with_venmo_one_form("disabled").'</div>
	<div id="right container" style="">';

	$output .='To embed the form as a dropdown with static amounts use <strong>[pay_with_venmo_dropdown]</strong>';
	$output .='<form name="pay_with_venmo_dropdown_config" method="post" onsubmit="return validateDropdownValues()">';
	$output .= wp_nonce_field('pay_with_venmo_nonce');
	$output .='Amounts: <input type="text" name="dropdown_amounts" id="dropdown_amounts" value="'.sanitize_text_field($static_amounts).'">
			<input type="hidden" name="pay_with_venmo_id" value="'.$id.'">
			<input type="submit" class="button button-primary btn btn-primary" name="dropdown_amounts_submit" value="Save">
			<label id="venmo_dropdown_amounts_error" style="color:red; display:none;">Please, enter amounts separated by commas. Ex. 10,20,30</label>
			</form>';
	$output .= '<strong>Form example:</strong>'.pay_with_venmo_dropdown_form("disabled");
	
	$output .='</div>
	</div>'; //setup disabled submit for the demo form

	$output .= '<div class="clear"></div>
	<div style="padding-top:30px;"><hr>
		<h4>Liked the plugin?</h4>
		<p>If you like the plugin and would like to make small donation: </br>
		<div>
			<input style="margin-bottom:10px;" type="button" class="button button-primary btn btn-primary" value="Send Venmo to Alex" onclick=(location.href="https://venmo.com/Aleksei-Lyskovich")>
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" onclick=(location.href="https://www.paypal.me/Lyskovich/25")>
		</div>
		</p>
		<p>
			Please, provide your feedback and we can improve the plugin! Email to <a href="mailto:info@qatsys.com" target="_top">info@qatsys.com</a>
		</p>
	</div>';

	echo $output;
}

function pay_with_venmo_dropdown_to_embed(){
	return pay_with_venmo_dropdown_form("");
}
add_shortcode('pay_with_venmo_dropdown', 'pay_with_venmo_dropdown_to_embed');

function pay_with_venmo_form_to_embed(){
	return pay_with_venmo_one_form("");
}
add_shortcode( 'pay_with_venmo', 'pay_with_venmo_form_to_embed' );
