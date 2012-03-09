<?php
/**
 * PayPal Standard Gateway
 *
 * DISCLAIMER
 *
 * Do not edit or add directly to this file if you wish to upgrade Jigoshop to newer
 * versions in the future. If you wish to customise Jigoshop core for your needs,
 * please use our GitHub repository to publish essential changes for consideration.
 *
 * @package		Jigoshop
 * @category	Checkout
 * @author		Jigowatt
 * @copyright	Copyright (c) 2011-2012 Jigowatt Ltd.
 * @license		http://jigoshop.com/license/commercial-edition
 */

/**
 * Add the gateway to JigoShop
 **/
function add_paypal_gateway( $methods ) {
	$methods[] = 'paypal';
	return $methods;
}
add_filter( 'jigoshop_payment_gateways', 'add_paypal_gateway', 10 );


class paypal extends jigoshop_payment_gateway {

	public function __construct() {
		
		
// 		Jigoshop_Options::add_option('jigoshop_paypal_enabled', 'yes');
// 		Jigoshop_Options::add_option('jigoshop_paypal_email', '');
// 		Jigoshop_Options::add_option('jigoshop_paypal_title', __('PayPal', 'jigoshop') );
// 		Jigoshop_Options::add_option('jigoshop_paypal_description', __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'jigoshop') );
// 		Jigoshop_Options::add_option('jigoshop_paypal_testmode', 'no');
// 		Jigoshop_Options::add_option('jigoshop_paypal_send_shipping', 'no');

		// NOTE: The above add_options are used for now.  When the gateway is converted to using Jigoshop_Options class
		// sometime post Jigoshop 1.2, they won't be needed and only the following commented out line will be used
		
		Jigoshop_Options::install_external_options( __( 'Payment Gateways', 'jigoshop' ), $this->get_default_options() );
		
        $this->id			= 'paypal';
        $this->icon 		= jigoshop::assets_url() . '/assets/images/icons/paypal.png';
        $this->has_fields 	= false;
      	$this->enabled		= Jigoshop_Options::get_option('jigoshop_paypal_enabled');
		$this->title 		= Jigoshop_Options::get_option('jigoshop_paypal_title');
		$this->email 		= Jigoshop_Options::get_option('jigoshop_paypal_email');
		$this->description  = Jigoshop_Options::get_option('jigoshop_paypal_description');

		$this->liveurl 		= 'https://www.paypal.com/webscr';
		$this->testurl 		= 'https://www.sandbox.paypal.com/webscr';
		$this->testmode		= Jigoshop_Options::get_option('jigoshop_paypal_testmode');

		$this->send_shipping = Jigoshop_Options::get_option('jigoshop_paypal_send_shipping');

		add_action( 'init', array(&$this, 'check_ipn_response') );
		add_action('valid-paypal-standard-ipn-request', array(&$this, 'successful_request') );
		add_action('receipt_paypal', array(&$this, 'receipt_page'));
		
		// remove this hook 'jigoshop_update_options' for post Jigoshop 1.2 use
//		add_action('jigoshop_update_options', array(&$this, 'process_admin_options'));

    }


	/**
	 * Default Option settings for WordPress Settings API using the Jigoshop_Options class
	 *
	 * These should be installed on the Jigoshop_Options 'Payment Gateways' tab
	 *
	 * NOTE: these are currently not used in Jigoshop 1.2 or less.  They will be implemented when all Gateways are
	 * converted for full Jigoshop_Options use post Jigoshop 1.2.
	 *
	 */	
	public function get_default_options() {
	
		$defaults = array();
		
		// Define the Section name for the Jigoshop_Options
		$defaults[] = array( 'name' => __('PayPal Standard', 'jigoshop'), 'type' => 'title', 'desc' => __('<p>PayPal Standard works by sending the user to <a href="https://www.paypal.com/uk/mrb/pal=JFC9L8JJUZZK2">PayPal</a> to enter their payment information.</p>', 'jigoshop') );
		
		// List each option in order of appearance with details
		$defaults[] = array(
			'name'		=> __('Enable PayPal Standard','jigoshop'),
			'desc' 		=> '',
			'tip' 		=> '',
			'id' 		=> 'jigoshop_paypal_enabled',
			'std' 		=> 'yes',
			'type' 		=> 'radio',
			'choices'	=> array(
				'no'			=> __('No', 'jigoshop'),
				'yes'			=> __('Yes', 'jigoshop')
			)
		);
		
		$defaults[] = array(
			'name'		=> __('Method Title','jigoshop'),
			'desc' 		=> '',
			'tip' 		=> __('This controls the title which the user sees during checkout.','jigoshop'),
			'id' 		=> 'jigoshop_paypal_title',
			'std' 		=> __('PayPal','jigoshop'),
			'type' 		=> 'text'
		);
		
		$defaults[] = array(
			'name'		=> __('Description','jigoshop'),
			'desc' 		=> '',
			'tip' 		=> __('This controls the description which the user sees during checkout.','jigoshop'),
			'id' 		=> 'jigoshop_paypal_description',
			'std' 		=> __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'jigoshop'),
			'type' 		=> 'longtext'
		);

		$defaults[] = array(
			'name'		=> __('PayPal email address','jigoshop'),
			'desc' 		=> '',
			'tip' 		=> __('Please enter your PayPal email address; this is needed in order to take payment!','jigoshop'),
			'id' 		=> 'jigoshop_paypal_email',
			'std' 		=> '',
			'type' 		=> 'email'
		);

		$defaults[] = array(
			'name'		=> __('Send shipping details to PayPal','jigoshop'),
			'desc' 		=> '',
			'tip' 		=> __('If your checkout page does not ask for shipping details, or if you do not want to send shipping information to PayPal, set this option to no. If you enable this option PayPal may restrict where things can be sent, and will prevent some orders going through for your protection.','jigoshop'),
			'id' 		=> 'jigoshop_paypal_send_shipping',
			'std' 		=> 'no',
			'type' 		=> 'radio',
			'choices'	=> array(
				'no'			=> __('No', 'jigoshop'),
				'yes'			=> __('Yes', 'jigoshop')
			)
		);

		$defaults[] = array(
			'name'		=> __('Enable PayPal sandbox','jigoshop'),
			'desc' 		=> '',
			'tip' 		=> '',
			'id' 		=> 'jigoshop_paypal_testmode',
			'std' 		=> 'no',
			'type' 		=> 'radio',
			'choices'	=> array(
				'no'			=> __('No', 'jigoshop'),
				'yes'			=> __('Yes', 'jigoshop')
			)
		);

		return $defaults;
	}


	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * NOTE: this will be deprecated post Jigoshop 1.2 and no longer required for Admin options display
	 *
	 **/
	public function admin_options() {
    	?>
    	<thead><tr><th scope="col" width="200px"><?php _e('PayPal Standard', 'jigoshop'); ?></th><th scope="col" class="desc"><?php _e('PayPal Standard works by sending the user to <a href="https://www.paypal.com/uk/mrb/pal=JFC9L8JJUZZK2">PayPal</a> to enter their payment information.', 'jigoshop'); ?></th></tr></thead>
    	<tr>
	        <td class="titledesc"><?php _e('Enable PayPal Standard', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <select name="jigoshop_paypal_enabled" id="jigoshop_paypal_enabled" style="min-width:100px;">
		            <option value="yes" <?php if (Jigoshop_Options::get_option('jigoshop_paypal_enabled') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'jigoshop'); ?></option>
		            <option value="no" <?php if (Jigoshop_Options::get_option('jigoshop_paypal_enabled') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'jigoshop'); ?></option>
		        </select>
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('This controls the title which the user sees during checkout.','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Method Title', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input class="input-text" type="text" name="jigoshop_paypal_title" id="jigoshop_paypal_title" style="min-width:50px;" value="<?php if ($value = Jigoshop_Options::get_option('jigoshop_paypal_title')) echo $value; else echo 'PayPal'; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('This controls the description which the user sees during checkout.','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Description', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input class="input-text wide-input" type="text" name="jigoshop_paypal_description" id="jigoshop_paypal_description" style="min-width:50px;" value="<?php if ($value = Jigoshop_Options::get_option('jigoshop_paypal_description')) echo $value; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('Please enter your PayPal email address; this is needed in order to take payment!','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('PayPal email address', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input class="input-text" type="text" name="jigoshop_paypal_email" id="jigoshop_paypal_email" style="min-width:50px;" value="<?php if ($value = Jigoshop_Options::get_option('jigoshop_paypal_email')) echo $value; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('If your checkout page does not ask for shipping details, or if you do not want to send shipping information to PayPal, set this option to no. If you enable this option PayPal may restrict where things can be sent, and will prevent some orders going through for your protection.','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Send shipping details to PayPal', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <select name="jigoshop_paypal_send_shipping" id="jigoshop_paypal_send_shipping" style="min-width:100px;">
		            <option value="yes" <?php if (Jigoshop_Options::get_option('jigoshop_paypal_send_shipping') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'jigoshop'); ?></option>
		            <option value="no" <?php if (Jigoshop_Options::get_option('jigoshop_paypal_send_shipping') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'jigoshop'); ?></option>
		        </select>
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><?php _e('Enable PayPal sandbox', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <select name="jigoshop_paypal_testmode" id="jigoshop_paypal_testmode" style="min-width:100px;">
		            <option value="yes" <?php if (Jigoshop_Options::get_option('jigoshop_paypal_testmode') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'jigoshop'); ?></option>
		            <option value="no" <?php if (Jigoshop_Options::get_option('jigoshop_paypal_testmode') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'jigoshop'); ?></option>
		        </select>
	        </td>
	    </tr>
    	<?php
    }

    /**
	 * There are no payment fields for paypal, but we want to show the description if set.
	 **/
    function payment_fields() {
    	if ($jigoshop_paypal_description = Jigoshop_Options::get_option('jigoshop_paypal_description')) echo wpautop(wptexturize($jigoshop_paypal_description));
    }

	/**
	 * Admin Panel Options Processing
	 * - Saves the options to the DB
	 *
	 * NOTE: this will be deprecated post Jigoshop 1.2 and no longer required for Admin options saving
	 *
	 **/
    public function process_admin_options() {
   		if(isset($_POST['jigoshop_paypal_enabled'])) Jigoshop_Options::set_option('jigoshop_paypal_enabled', jigowatt_clean($_POST['jigoshop_paypal_enabled']));
   		if(isset($_POST['jigoshop_paypal_title'])) Jigoshop_Options::set_option('jigoshop_paypal_title', jigowatt_clean($_POST['jigoshop_paypal_title']));
   		if(isset($_POST['jigoshop_paypal_email'])) Jigoshop_Options::set_option('jigoshop_paypal_email', jigowatt_clean($_POST['jigoshop_paypal_email']));
   		if(isset($_POST['jigoshop_paypal_description'])) Jigoshop_Options::set_option('jigoshop_paypal_description', jigowatt_clean($_POST['jigoshop_paypal_description']));
   		if(isset($_POST['jigoshop_paypal_testmode'])) Jigoshop_Options::set_option('jigoshop_paypal_testmode', jigowatt_clean($_POST['jigoshop_paypal_testmode']));
   		if(isset($_POST['jigoshop_paypal_send_shipping'])) Jigoshop_Options::set_option('jigoshop_paypal_send_shipping', jigowatt_clean($_POST['jigoshop_paypal_send_shipping']));
    }

	/**
	 * Generate the paypal button link
	 **/
    public function generate_paypal_form( $order_id ) {

		$order = new jigoshop_order( $order_id );

		if ( $this->testmode == 'yes' ):
			$paypal_adr = $this->testurl . '?test_ipn=1&';
		else :
			$paypal_adr = $this->liveurl . '?';
		endif;

		$shipping_name = explode(' ', $order->shipping_method);

		if (in_array($order->billing_country, array('US','CA'))) :
			$order->billing_phone = str_replace(array('(', '-', ' ', ')'), '', $order->billing_phone);
			$phone_args = array(
				'night_phone_a' => substr($order->billing_phone,0,3),
				'night_phone_b' => substr($order->billing_phone,3,3),
				'night_phone_c' => substr($order->billing_phone,6,4),
				'day_phone_a' 	=> substr($order->billing_phone,0,3),
				'day_phone_b' 	=> substr($order->billing_phone,3,3),
				'day_phone_c' 	=> substr($order->billing_phone,6,4)
			);
		else :
			$phone_args = array(
				'night_phone_b' => $order->billing_phone,
				'day_phone_b' 	=> $order->billing_phone
			);
		endif;

		// filter redirect page
		$checkout_redirect = apply_filters( 'jigoshop_get_checkout_redirect_page_id', jigoshop_get_page_id('thanks') );

		$paypal_args = array_merge(
			array(
				'cmd' 					=> '_cart',
				'business' 				=> $this->email,
				'no_note' 				=> 1,
				'currency_code' 		=> Jigoshop_Options::get_option('jigoshop_currency'),
				'charset' 				=> 'UTF-8',
				'rm' 					=> 2,
				'upload' 				=> 1,
				'return' 				=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink( $checkout_redirect ))),
				'cancel_return'			=> $order->get_cancel_order_url(),
				//'cancel_return'			=> home_url(),

				// Order key
				'custom'				=> $order_id,

				// IPN
				'notify_url'			=> trailingslashit(get_bloginfo('wpurl')).'?paypalListener=paypal_standard_IPN',

				// Address info
				'first_name'			=> $order->billing_first_name,
				'last_name'				=> $order->billing_last_name,
				'company'				=> $order->billing_company,
				'address1'				=> $order->billing_address_1,
				'address2'				=> $order->billing_address_2,
				'city'					=> $order->billing_city,
				'state'					=> $order->billing_state,
				'zip'					=> $order->billing_postcode,
				'country'				=> $order->billing_country,
				'email'					=> $order->billing_email,

				// Payment Info
				'invoice' 				=> $order->order_key,
				'amount' 				=> $order->order_total,
				'discount_amount_cart' 	=> $order->order_discount
			),
			$phone_args
		);

        // only include tax if prices don't include tax
        if (Jigoshop_Options::get_option('jigoshop_prices_include_tax') != 'yes') :
            $paypal_args['tax']					= $order->get_total_tax();
            $paypal_args['tax_cart']			= $order->get_total_tax();
        endif;


		if ($this->send_shipping=='yes') :
			$paypal_args['no_shipping'] = 0;
			$paypal_args['address_override'] = 1;
		else :
			$paypal_args['no_shipping'] = 1;
		endif;

		// Cart Contents
		$item_loop = 0;
		if (sizeof($order->items)>0) : foreach ($order->items as $item) :

            if(!empty($item['variation_id'])) {
                $_product = new jigoshop_product_variation($item['variation_id']);
            } else {
                $_product = new jigoshop_product($item['id']);
            }

			if ($_product->exists() && $item['qty']) :

				$item_loop++;

                $title = $_product->get_title();

                //if variation, insert variation details into product title
                if ($_product instanceof jigoshop_product_variation) {
                    $variation_details = array();

                    foreach ($_product->get_variation_attributes() as $name => $value) {
                        $variation_details[] = ucfirst(str_replace('tax_', '', $name)) . ': ' . ucfirst($value);
                    }

                    if (count($variation_details) > 0) {
                        $title .= ' (' . implode(', ', $variation_details) . ')';
                    }
                }

				$paypal_args['item_name_'.$item_loop] = $title;
				$paypal_args['quantity_'.$item_loop] = $item['qty'];
				// use product price since we want the base price if it's including tax or if it's not including tax
                $paypal_args['amount_'.$item_loop] = number_format($_product->get_price(), 2); //Apparently, Paypal did not like "28.4525" as the amount. Changing that to "28.45" fixed the issue.
			endif;
		endforeach; endif;

		// Shipping Cost
		$item_loop++;
		$paypal_args['item_name_'.$item_loop] = __('Shipping cost', 'jigoshop');
		$paypal_args['quantity_'.$item_loop] = '1';

        $shipping_tax = (float)($order->order_shipping_tax ? $order->order_shipping_tax : 0);

		$paypal_args['amount_'.$item_loop] = (Jigoshop_Options::get_option('jigoshop_prices_include_tax') == 'yes' ? number_format((float)$order->order_shipping + $shipping_tax, 2) : number_format((float)$order->order_shipping, 2));

		$paypal_args_array = array();

		foreach ($paypal_args as $key => $value) {
			$paypal_args_array[] = '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'" />';
		}

		return '<form action="'.$paypal_adr.'" method="post" id="paypal_payment_form">
				' . implode('', $paypal_args_array) . '
				<input type="submit" class="button-alt" id="submit_paypal_payment_form" value="'.__('Pay via PayPal', 'jigoshop').'" /> <a class="button cancel" href="'.esc_url($order->get_cancel_order_url()).'">'.__('Cancel order &amp; restore cart', 'jigoshop').'</a>
				<script type="text/javascript">
					jQuery(function(){
						jQuery("body").block(
							{
								message: "<img src=\"'.jigoshop::assets_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />'.__('Thank you for your order. We are now redirecting you to PayPal to make payment.', 'jigoshop').'",
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
						jQuery("#submit_paypal_payment_form").click();
					});
				</script>
			</form>';

	}

	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {

		$order = new jigoshop_order( $order_id );

		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('pay'))))
		);

	}

	/**
	 * receipt_page
	 **/
	function receipt_page( $order ) {

		echo '<p>'.__('Thank you for your order, please click the button below to pay with PayPal.', 'jigoshop').'</p>';

		echo $this->generate_paypal_form( $order );

	}

	/**
	 * Check PayPal IPN validity
	 **/
	function check_ipn_request_is_valid() {

    	 // Add cmd to the post array
        $_POST['cmd'] = '_notify-validate';

        // Send back post vars to paypal
        $params = array( 'body' => $_POST, 'sslverify' => apply_filters('https_local_ssl_verify', false));

        // Get url
       	if ( $this->testmode == 'yes' ):
			$paypal_adr = $this->testurl;
		else :
			$paypal_adr = $this->liveurl;
		endif;

		// Post back to get a response
        $response = wp_remote_post( $paypal_adr, $params );

		 // Clean
        unset($_POST['cmd']);

        // check to see if the request was valid
        if ( !is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && (strcmp( $response['body'], "VERIFIED") == 0)) {
            return true;
        }

        return false;
    }

	/**
	 * Check for PayPal IPN Response
	 **/
	function check_ipn_response() {

		if (isset($_GET['paypalListener']) && $_GET['paypalListener'] == 'paypal_standard_IPN'):

        	$_POST = stripslashes_deep($_POST);

        	if (self::check_ipn_request_is_valid()) :

            	do_action("valid-paypal-standard-ipn-request", $_POST);

       		endif;

       	endif;

	}

	/**
	 * Successful Payment!
	 **/
	function successful_request( $posted ) {

		// Custom holds post ID
	    if ( !empty($posted['txn_type']) && !empty($posted['invoice']) ) {

	        $accepted_types = array('cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money');

	        if (!in_array(strtolower($posted['txn_type']), $accepted_types)) exit;

			$order = new jigoshop_order( (int) $posted['custom'] );

	        if ($order->order_key!==$posted['invoice']) exit;

	        // Sandbox fix
	        if ($posted['test_ipn']==1 && $posted['payment_status']=='Pending') $posted['payment_status'] = 'completed';


			if ($order->status !== 'completed') :
		        // We are here so lets check status and do actions
		        switch (strtolower($posted['payment_status'])) :
		            case 'completed' :
		            	// Payment completed
		                $order->add_order_note( __('IPN payment completed', 'jigoshop') );
		                $order->payment_complete();
		            break;
		            case 'denied' :
		            case 'expired' :
		            case 'failed' :
		            case 'voided' :
		                // Hold order
		                $order->update_status('on-hold', sprintf(__('Payment %s via IPN.', 'jigoshop'), strtolower($posted['payment_status']) ) );
		            break;
		            default:
		            	// No action
		            break;
		        endswitch;
			endif;

			exit;

	    }

	}

}
