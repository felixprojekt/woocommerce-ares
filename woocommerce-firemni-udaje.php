<?php
/*
 Plugin Name:       WooCommerce Firemní údaje
 Author:            Milan Švehla
 Author URI:        https://milansvehla.com
 Text Domain:       woocommerce-fu
 Domain Path:       /languages
 Description:       Adds custom fields for the company costumers to the WooCommerce checkout and allows Czech companies to fill company credentials from the API.
 Version:           1.0.1
 License:           GNU General Public License v3.0
 License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WOOCOMMERCE_FU_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOCOMMERCE_FU_VERSION', "1.0.1" );

// If Woocommerce is NOT active
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

    add_action( 'admin_init', 'woocommerce_fu_plugin_deactivate' );
    add_action( 'admin_notices', 'woocommerce_fu_plugin_admin_notice' );

    // Deactivate the Child Plugin
    function woocommerce_fu_plugin_deactivate() {
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }

    // Throw an Alert to tell the Admin why it didn't activate
    function woocommerce_fu_plugin_admin_notice() {
        $dpa_child_plugin = __( 'WooCommerce Firemní údaje', 'mswpec' );
        $dpa_parent_plugin = __( 'WooCommerce', 'mswpec' );

        echo '<div class="notice notice-warning is-dismissible"><p>'
            . sprintf( __( '%1$s vyžaduje %2$s. Nainstalujte/aktivujte prosím nejprve %2$s a poté %1$s. Tento plugin byl deaktivován.', 'woocommerce-fu' ), '<strong>' . esc_html( $dpa_child_plugin ) . '</strong>', '<strong>' . esc_html( $dpa_parent_plugin ) . '</strong>' )
            . '</p></div>';

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}

add_filter( 'woocommerce_billing_fields' , 'woocommerce_fu_override_checkout_fields' );

function woocommerce_fu_override_checkout_fields( $fields ) {
	$fields["ares_is_company"] = [
     	"type" => "checkbox",
     	"label" => __("Jsem firma", 'woocommerce-fu'),
     	"class" => ["ares-is-company-trigger"],
     	"clear" => true,
    ];

    $fields['ares_ico'] = [
        'label' => __('IČO', 'woocommerce-fu'),
    	'required' => false,
    	'class'	=> ["form-row-first"],
    	'clear'	=> true,
    ];

    $fields['ares_dic'] = [
        'label' => __('DIČ', 'woocommerce-fu'),
    	'required' => false,
    	'class'	=> ["form-row-wide"],
    	'clear'	=> true,
    ];

    $fields['billing_company'] = [
    	'label' => __('Název firmy', 'woocommerce-fu'),
    	'required' => false,
    	'class' => ["form-row-wide"],
    	'clear' => true,
    ];

     $fields = array('billing_company' => $fields['billing_company']) + $fields;
     $fields = array('ares_dic' => $fields['ares_dic']) + $fields;
     $fields = array('ares_ico' => $fields['ares_ico']) + $fields;
     $fields = array('ares_is_company' => $fields['ares_is_company']) + $fields;

     return $fields;
}

add_action('woocommerce_checkout_update_order_meta', 'woocommerce_fu_checkout_field_update_order_meta');


function woocommerce_fu_checkout_field_update_order_meta($order_id)
{

    if (!empty(filter_input(INPUT_POST, 'ares_is_company'))) {
        update_post_meta($order_id, "is_company", sanitize_text_field(filter_input(INPUT_POST, 'ares_is_company')));
    }

    if (!empty(filter_input(INPUT_POST, 'ares_ico'))) {
        update_post_meta($order_id, "ico", sanitize_text_field(filter_input(INPUT_POST, 'ares_ico')));
    }

    if (!empty(filter_input(INPUT_POST, 'ares_dic'))) {
        update_post_meta($order_id, "dic", sanitize_text_field(filter_input(INPUT_POST, 'ares_dic')));
    }
}

add_action( 'woocommerce_admin_order_data_after_shipping_address', 'woocommerce_fu_checkout_field_display_admin_order_meta', 10, 1 );

function woocommerce_fu_checkout_field_display_admin_order_meta($order){
    if(get_post_meta( $order->get_id(), 'is_company', true )) {
        echo '<p><strong>'.__('Je firma', 'woocommerce-fu').' &#10003;</strong></p>';
    }
    if(get_post_meta( $order->get_id(), 'ico', true )) {
        echo '<p><strong>' . __('IČO', 'woocommerce-fu') . ':</strong> ' . get_post_meta( $order->get_id(), 'ico', true ) . '</p>';
    }
    if(get_post_meta( $order->get_id(), 'dic', true )) {
        echo '<p><strong>' . __('DIČ', 'woocommerce-fu') . ':</strong> ' . get_post_meta( $order->get_id(), 'dic', true ) . '</p>';
    }
}

add_action( 'woocommerce_after_checkout_form', 'woocomerce_fu_checkout_form_modifications', 6);

function woocomerce_fu_checkout_form_modifications() {
?>
<script type="text/javascript">
	(function($) {
		$("#ares_ico_field").after("<p class='form-row form-row-last' id='ares_fetch_button'><label>&nbsp;</label><button class='button alt' style='width: 100%' disabled='disabled'><?= _e("Načíst data podle IČO", "woocommerce-fu"); ?></button></p>");

		if($("#ares_is_company").checked) {
			$('#ares_ico_field').show();
			$('#ares_dic_field').show();

			$('#billing_company_field').show();
			$('#ares_fetch_button').show();
		} else {
			$('#ares_ico_field').hide();
			$('#ares_dic_field').hide();
			$('#ares_ico').val('');   
			$('#ares_dic').val('');   

			$('#billing_company_field').hide();
			$('#ares_fetch_button').hide();
		}

		$('#ares_is_company').change(function(){
            if (this.checked) {
                $('#ares_ico_field').show();  
                $('#ares_dic_field').show(); 

                $('#billing_company_field').show(); 
                $('#ares_fetch_button').show(); 
            } else {
                $('#ares_ico_field').hide();
                $('#ares_dic_field').hide();
                $('#ares_ico').val(''); 
                $('#ares_dic').val(''); 

                $('#billing_company_field').hide();  
                $('#ares_fetch_button').hide();  
            }   
        });

        $("#ares_ico").on("change paste keyup", function() {
        	if($(this).val().length > 7) {
        		$("#ares_fetch_button button").removeAttr("disabled");
        	} else {
        		$("#ares_fetch_button button").attr("disabled", "disabled");
        	}
        });
   
	    $("#ares_fetch_button button").click(function(e) {
	    	e.preventDefault();

	    	$(this).addClass("loading");

	        var data = {
	        	action: 'ares_action',
	        	ico: $("#ares_ico").val()
	        }

	        var ajaxurl = "<?= admin_url('admin-ajax.php') ?>";

	    	$.post(ajaxurl, data, function(response) {
	    		console.log(JSON.parse(response));

	    		responseObj = JSON.parse(response);

	    		if(responseObj["error"]) {
	    			alert("Pro zadané IČO " + $("#ares_ico").val() + " jsme žádná data nenašli.");
	    		} else {
	    			$("#billing_company").val(responseObj["spolecnost"]);
	    			$("#billing_postcode").val(responseObj["psc"]);
	    			$("#billing_city").val(responseObj["mesto"]);
	    			$("#billing_address_1").val(responseObj["adresa"]);
	    			$("#ares_dic").val(responseObj["dic"]);
	    		}

	    		$("#ares_fetch_button button").removeClass("loading");
	    	});
	    	
	    });


	})( jQuery );
</script>
<?php 
}

add_action('wp_ajax_nopriv_ares_action', 'woocommerce_fu_fill_from_ares');
add_action('wp_ajax_ares_action', 'woocommerce_fu_fill_from_ares');

/**
 * MFCR INFO ARES API
 * http://wwwinfo.mfcr.cz/ares/ares_xml.html.cz#k3
 * 
 * ADDITIONAL CREDITS
 * https://github.com/svecon/web-utilities/blob/master/Ares/Ares.php
 * https://kybernaut.cz/pluginy/kybernaut-ic-dic
 */
function woocommerce_fu_fill_from_ares() {
	$ico = intval(str_replace(' ', '', filter_input(INPUT_POST, 'ico')));

	$url = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi?ico=' . $ico;
    $response = wp_remote_get( $url );

    if ( ! is_wp_error( $response ) ) {
        
        $body = wp_remote_retrieve_body($response);
        $xml  = simplexml_load_string($body);

        if ( $xml ) {

            $ns = $xml->getDocNamespaces(); 
            $data = $xml->children($ns['are']);
            $data = $data->children($ns['D'])->VBAS;

            if ( $data ) {

                $return = array( 'error' => false );
                $return['spolecnost'] = $data->OF->__toString();
                $return['ico'] = $data->ICO->__toString();
                $return['dic'] = $data->DIC->__toString();

                $cp_1 = $data->AA->CD->__toString();
                $cp_2 = $data->AA->CO->__toString();
                $cp = ( $cp_2 != "" ? $cp_1."/".$cp_2 : $cp_1 );
                $cp = (empty($cp)?$data->AA->CA->__toString():$cp);
                $return['adresa'] = $data->AA->NU->__toString() . ' ' . $cp;

                $return['psc'] = $data->AA->PSC->__toString();
                $return['mesto'] = $data->AA->N->__toString();

            } else {
                $return = array( 'error' => __('Entity doesn\'t exist in ARES.', 'woocommerce-fu') . $ico);
            }

        } else {
            $return = array( 'error' => __('ARES is not responding', 'woocommerce-fu'));
        }
        
    } else {
        $return = array( 'error' => __('WP ERROR, can\'t connect.', 'woocommerce-fu'));
    }

    print json_encode($return);

    exit();
}