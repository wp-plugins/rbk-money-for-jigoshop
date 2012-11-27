<?php
/*
  Plugin Name: RBK Money Payment Gateway
  Plugin URI: http://cardify.ru/
  Description: Adds RBK Money payment gateway for the Jigoshop e-commerce plugin.
  Version: 1.0
  Author: Cardify
  Author URI: http://cardify.ru/
 */


/*

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */


/* Add a custom payment class to Jigoshop
  ------------------------------------------------------------ */
add_action('plugins_loaded', 'jingoshop_rbkmoney', 0);
function jingoshop_rbkmoney()
{
	if (!class_exists('jigoshop_payment_gateway'))
		return; // if the Jigoshop payment gateway class is not available, do nothing

class rbkmoney extends jigoshop_payment_gateway {
		
	public function __construct() {
		$this->id = 'rbkmoney';
		$this->icon = '';
		$this->has_fields = false;
		$this->enabled = get_option('jigoshop_rbkmoney_enabled');
		$this->title = get_option('jigoshop_rbkmoney_title');
		$this->merchant = get_option('jigoshop_rbkmoney_merchant');
		$this->key1 = get_option('jigoshop_rbkmoney_key1');
		$this->key2 = get_option('jigoshop_rbkmoney_key2');
		
		add_action('init', array(&$this, 'check_callback') );
		add_action('valid-rbkmoney-callback', array(&$this, 'successful_request') );
		add_action('jigoshop_update_options', array(&$this, 'process_admin_options'));
		add_action('receipt_rbkmoney', array(&$this, 'receipt_page'));
		
		add_option('jigoshop_rbkmoney_enabled', 'yes');
		add_option('jigoshop_rbkmoney_title', 'RBK Money');
		add_option('jigoshop_rbkmoney_merchant', '');
		add_option('jigoshop_rbkmoney_key1', '');
		add_option('jigoshop_rbkmoney_key2', ''); 
		add_option('jigoshop_rbkmoney_title', __('RBK Money', 'jigoshop') );
	}
    
	/**
	* Admin Panel Options 
	* - Options for bits like 'title' and availability on a country-by-country basis
	**/
	public function admin_options() {
		?>
		<thead><tr><th scope="col" colspan="2"><h3 class="title"><?php _e('RBK Money', 'jigoshop'); ?></h3><p>RBK Money is one of the popular payment gateways in Russia.</p></th></tr></thead>
		<tr>
			<td class="titledesc"><?php _e('Enable RBK Money', 'jigoshop') ?>:</td>
			<td class="forminp">
				<select name="jigoshop_rbkmoney_enabled" id="jigoshop_rbkmoney_enabled" style="min-width:100px;">
					<option value="yes" <?php if (get_option('jigoshop_rbkmoney_enabled') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'jigoshop'); ?></option>
					<option value="no" <?php if (get_option('jigoshop_rbkmoney_enabled') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'jigoshop'); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="titledesc"><a href="#" tip="<?php _e('This controls the title which the user sees during checkout.','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Method Title', 'jigoshop') ?>:</td>
			<td class="forminp">
				<input class="input-text" type="text" name="jigoshop_rbkmoney_title" id="jigoshop_rbkmoney_title" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_rbkmoney_title')) echo $value; else echo 'RBK Money'; ?>" />
			</td>
		</tr>
		<tr>
			<td class="titledesc"><a href="#" tip="<?php _e('Please enter your RBK Money merchant ID','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('RBK Money Merchant login', 'jigoshop') ?>:</td>
			<td class="forminp">
				<input class="input-text" type="text" name="jigoshop_rbkmoney_merchant" id="jigoshop_rbkmoney_merchant" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_rbkmoney_merchant')) echo $value; ?>" />
			</td>
		</tr>
		<tr>
			<td class="titledesc"><a href="#" tip="<?php _e('URL of your website for notification about a successful payment','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('RBK Money key #1', 'jigoshop') ?>:</td>
			<td class="forminp">
				<input class="input-text" type="text" name="jigoshop_rbkmoney_key1" id="jigoshop_rbkmoney_key1" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_rbkmoney_key1')) echo $value; ?>" />
			</td>
		</tr>
		<tr>
			<td class="titledesc"><a href="#" tip="<?php _e('URL of your website for notification about a failed payment','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('RBK Money key #2', 'jigoshop') ?>:</td>
			<td class="forminp">
				<input class="input-text" type="text" name="jigoshop_rbkmoney_key2" id="jigoshop_rbkmoney_key2" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_rbkmoney_key2')) echo $value; ?>" />
			</td>
		</tr>

		<?php
	}

	/**
	* There are no payment fields for sprypay, but we want to show the description if set.
	**/
	function payment_fields() {
		if ($jigoshop_rbkmoney_description = get_option('jigoshop_rbkmoney_title')) echo wpautop(wptexturize($jigoshop_rbkmoney_title));
	}

	/**
	* Admin Panel Options Processing
	* - Saves the options to the DB
	**/
	public function process_admin_options() {
		if(isset($_POST['jigoshop_rbkmoney_enabled'])) update_option('jigoshop_rbkmoney_enabled', jigowatt_clean($_POST['jigoshop_rbkmoney_enabled'])); else @delete_option('jigoshop_rbkmoney_enabled');
		if(isset($_POST['jigoshop_rbkmoney_title'])) update_option('jigoshop_rbkmoney_title', jigowatt_clean($_POST['jigoshop_rbkmoney_title'])); else @delete_option('jigoshop_rbkmoney_title');
		if(isset($_POST['jigoshop_rbkmoney_merchant'])) update_option('jigoshop_rbkmoney_merchant', jigowatt_clean($_POST['jigoshop_rbkmoney_merchant'])); else @delete_option('jigoshop_rbkmoney_merchant');
		if(isset($_POST['jigoshop_rbkmoney_key1'])) update_option('jigoshop_rbkmoney_key1', jigowatt_clean($_POST['jigoshop_rbkmoney_key1'])); else @delete_option('jigoshop_rbkmoney_key1');
		if(isset($_POST['jigoshop_rbkmoney_key2'])) update_option('jigoshop_rbkmoney_key2', jigowatt_clean($_POST['jigoshop_rbkmoney_key2'])); else @delete_option('jigoshop_rbkmoney_key2');
	}
	
	/**
	* Generate RBK Money button
	**/
	public function generate_form( $order_id ) {
		
		$order = &new jigoshop_order( $order_id );

		$action_adr = 'https://rbkmoney.ru/acceptpurchase.aspx';
		
		//prepare description of the products in the order
		$description = '';
		$flaggy = true;
		foreach($order->items as $item) :
			$_product = $order->get_product_from_item( $item );
			if($flaggy == false)
				$description .= '; ';
			$description .= html_entity_decode(apply_filters('jigoshop_order_product_title', $item['name'], $_product), ENT_QUOTES, 'UTF-8');
			if($flaggy == true)
				$flaggy = false;
		endforeach;
		//description cannot be longer than 255 characters
		$description = substr($description, 0, 255);
		
		$args =
			array(
				// Shop ID
				'eshopId' => $this->merchant,
				'orderId' => $order_id,
				'serviceName' => rbkmoney::translitIt($description),
				'recipientAmount' => number_format($order->order_total, 2, '.', ''),
				'recipientCurrency' => 'RUR',
				'user-email' => $order->billing_email,
				'language' => 'ru',
				'direct' => 'false',
				'preference' => 'bankcard',
				'successUrl' => '/jigoshop/rbkmoneythanks.php',
				'failUrl' => '/jigoshop/rbkmoneycancel.php'
		);
		// Calculate keys
		foreach ($args as $key => $value) {
			$fields .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
		}

		return '<form action="'.$action_adr.'" method="post" id="rbkmoney_payment_form">
				' . $fields . '
				<input type="submit" class="button-alt" id="submit_rbkmoney_payment_form" value="'.__('Place order', 'jigoshop').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel', 'jigoshop').'</a>'.'</form>';
				/*<script type="text/javascript">
					jQuery(function(){
						jQuery("body").block(
							{ 
								message: "<img src=\"'.jigoshop::plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />'.__('Спасибо за заказ! Мы переправляем Вас на защищенную интернет-страницу для осуществления платежа.', 'jigoshop').'", 
								overlayCSS: 
								{ 
									background: "#fff", 
									opacity: 0.6 
								},
								css: { 
							        padding:        20, 
							        textAlign:      "center", 
							        color:          "#555", 
							        border:         "3px solid #aaa", 
							        backgroundColor:"#fff", 
							        cursor:         "wait" 
							    } 
							});
					});
					jQuery("#submit_rbkmoney_payment_form").click();
				</script>*/
			
		
	}
	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		
		$order = &new jigoshop_order( $order_id );
		
		return array(
			'result' => 'success',
			'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('jigoshop_pay_page_id'))))
		);
		
	}
	
	/**
	* receipt_page
	**/
	function receipt_page( $order ) {
		
		echo '<p>'.__("Спасибо за заказ! Для осуществления платежа Вам необходимо нажать кнопку '", 'jigoshop').__('Place order', 'jigoshop')."'".'</p>';
		echo $this->generate_form( $order );
		
	}
	
	/**
	* Check for RBK Money Response
	**/
	function check_callback() {
		if ( strpos($_SERVER["REQUEST_URI"], '/jigoshop/rbkmoneycallback.php')!==false ) {
			
			error_log('RBK Money callback!');
			
			$_POST = stripslashes_deep($_POST);
			
			do_action("valid-rbkmoney-callback", $_POST);
		}
		elseif(strpos($_SERVER["REQUEST_URI"], '/jigoshop/rbkmoneythanks.php')!==false)
		{
/*		$f=fopen(dirname(realpath(__FILE__))."/log2.txt","a+");
		fputs($f,$_SERVER["REQUEST_URI"]."\r\n");
		fclose($f);*/

			$inv_id = $_REQUEST["InvId"];
			$order = &new jigoshop_order( $inv_id );
			$order->update_status('on-hold', __('Awaiting cheque payment', 'jigoshop'));
			jigoshop_cart::empty_cart();
			wp_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $inv_id, get_permalink(get_option('jigoshop_thanks_page_id')))));
			exit;
		}
		elseif(strpos($_SERVER["REQUEST_URI"], '/jigoshop/rbkmoneycancel.php')!==false)
		{

			$inv_id = $_REQUEST["InvId"];
			$order = &new jigoshop_order( $inv_id );
			//$order->update_status('on-hold', __('Awaiting cheque payment', 'jigoshop'));
			jigoshop_cart::empty_cart();
			wp_redirect($order->get_cancel_order_url());
			exit;
		}

//echo add_query_arg('key', $order->order_key, add_query_arg('order', $inv_id, get_permalink(get_option('jigoshop_thanks_page_id'))));

	}

	/**
	* Successful Payment!
	**/
	function successful_request( $posted ) {
		$out_summ = $_REQUEST["OutSum"];
		$inv_id = $_REQUEST["InvId"];
		$shp_item = $_REQUEST["Shp_item"];
		$crc = $_REQUEST["SignatureValue"];
		$mcrc=strtoupper(md5("$out_summ:$inv_id:{$this->key2}" ));
		if($mcrc==$crc)
		{
			$order = &new jigoshop_order( $inv_id );
			$order->update_status('processing', __('Money is comming', 'jigoshop'));
			echo "OK".$posted['InvId'];
		}
		exit;		
	}
	
	private static function translitIt($str) 
	{
		$tr = array(
			"А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
			"Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
			"Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
			"О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
			"У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
			"Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
			"Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
			"в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
			"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
			"м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
			"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
			"ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
			"ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
		);
		return strtr($str,$tr);
	}

}

/**
 * Add the gateway to JigoShop
 **/
function add_rbkmoney_gateway( $methods ) {
	$methods[] = 'rbkmoney'; return $methods;
}

add_filter('jigoshop_payment_gateways', 'add_rbkmoney_gateway' );
}