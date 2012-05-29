<?php
/**
 * Jigoshop Payment Gateway class
 *
 * DISCLAIMER
 *
 * Do not edit or add directly to this file if you wish to upgrade Jigoshop to newer
 * versions in the future. If you wish to customise Jigoshop core for your needs,
 * please use our GitHub repository to publish essential changes for consideration.
 *
 * @package             Jigoshop
 * @category            Checkout
 * @author              Jigowatt
 * @copyright           Copyright © 2011-2012 Jigowatt Ltd.
 * @license             http://jigoshop.com/license/commercial-edition
 */
class jigoshop_payment_gateway {

	var $id;
	var $title;
	var $chosen;
	var $has_fields;
	var $countries;
	var $availability;
	var $enabled;
	var $icon;
	var $description;
    protected $jigoshop_options;
    
    public function __construct() {
        
        // allows for multiple constructors. Either one with an argument, or default which creates a new
        // instance of jigoshop_options
        $this->jigoshop_options = (func_num_args() == 1 ? func_get_arg(0) : new Jigoshop_Options());
        
        // default to Jigoshop_Options class if the arg passed in isn't implementing the interface or is null
        if (!$this->jigoshop_options instanceof jigoshop_options_interface) :
            
            $this->jigoshop_options = new Jigoshop_Options();
        endif;
        
        $this->jigoshop_options->install_external_options( __( 'Payment Gateways', 'jigoshop' ), $this->get_default_options() );
    }

	function is_available() {

		if ($this->enabled=="yes") :

			return true;

		endif;

		return false;
	}

	function set_current() {
		$this->chosen = true;
	}

	function icon() {
		if ($this->icon) :
			return '<img src="'. jigoshop::force_ssl($this->icon).'" alt="'.$this->title.'" />';
		endif;
	}

	function admin_options() {}

	function process_payment( $order_id ) {}

	function validate_fields() { return true; }
    
    /**
     * provides functionality to tell checkout if 
     * the gateway should be processed or not. If false, the gateway will not be 
     * processed, otherwise the gateway will be processed.
     * @return boolean defaults to needs_payment from cart class. If overridden, the gateway will provide
     * details as to when it should or shouldn't be processed.
     * @since 1.2
     */
    public function process_gateway($subtotal, $shipping_total, $discount = 0) { 
        // default to cart needs_payment() to keep the same functionality that jigoshop offers today
        // if overridden, the gateway will provide the details when to skip or not
        return jigoshop_cart::needs_payment();
    }
    
	/**
	 * Default Option settings for WordPress Settings API using the Jigoshop_Options class
	 *
	 * These should be installed on the Jigoshop_Options 'Payment Gateways' tab
	 *
	 */	
    public function get_default_options() {}
}