<?php
/*
 Plugin Name:       WooCommerce ARES
 Author:            Milan Švehla
 Author URI:        https://milansvehla.com
 Text Domain:       woocommerce-ares
 Domain Path:       /languages
 Description:       Přidává do WooCommerce pokladny údaje pro firemní zákazníky a umožňuje natažení informací podle IČO.
 Version:           1.0.0
 License:           GPLv3
 License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 License:           GNU General Public License v3.0
 License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WOOCOMMERCE_ARES_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOCOMMERCE_ARES_VERSION', "1.0.0" );

add_filter( 'woocommerce_billing_fields' , 'woocommerce_ares_override_checkout_fields' );


function woocommerce_ares_override_checkout_fields( $fields ) {
	$fields["ares_is_company"] = [
     	"type" => "checkbox",
     	"label" => __("Jsem firma", 'woocommerce-ares'),
     	"class" => ["ares-is-company-trigger"],
     	"clear" => true,
    ];

    $fields['ares_ico'] = [
        'label' => __('IČO', 'woocommerce-ares'),
    	'required' => false,
    	'class'	=> ["form-row-first"],
    	'clear'	=> true,
    ];

    $fields['ares_dic'] = [
        'label' => __('DIČ', 'woocommerce-ares'),
    	'required' => false,
    	'class'	=> ["form-row-wide"],
    	'clear'	=> true,
    ];

    $fields['billing_company'] = [
    	'label' => __('Název firmy', 'woocommerce-ares'),
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

add_action('woocommerce_checkout_update_order_meta', 'woocommerce_ares_checkout_field_update_order_meta');


function woocommerce_ares_checkout_field_update_order_meta($order_id)
{
    if (!empty($_POST['ares_is_company'])) {
        update_post_meta($order_id, "is_company", sanitize_text_field($_POST['ares_is_company']));
    }

    if (!empty($_POST['ares_ico'])) {
        update_post_meta($order_id, "ico", sanitize_text_field($_POST['ares_ico']));
    }

    if (!empty($_POST['ares_dic'])) {
        update_post_meta($order_id, "dic", sanitize_text_field($_POST['ares_dic']));
    }
}

add_action( 'woocommerce_admin_order_data_after_shipping_address', 'woocommerce_ares_checkout_field_display_admin_order_meta', 10, 1 );

function woocommerce_ares_checkout_field_display_admin_order_meta($order){
    if(get_post_meta( $order->get_id(), 'is_company', true )) {
        echo '<p><strong>'.__('Je firma').' &#10003;</strong></p>';
    }
    if(get_post_meta( $order->get_id(), 'ico', true )) {
        echo '<p><strong>'.__('IČO').':</strong> ' . get_post_meta( $order->get_id(), 'ico', true ) . '</p>';
    }
    if(get_post_meta( $order->get_id(), 'dic', true )) {
        echo '<p><strong>'.__('DIČ').':</strong> ' . get_post_meta( $order->get_id(), 'dic', true ) . '</p>';
    }
}

add_action( 'woocommerce_after_checkout_form', 'woocomerce_ares_checkout_form_modifications', 6);

function woocomerce_ares_checkout_form_modifications() {
?>
<script type="text/javascript">
	(function($) {
		$("#ares_ico_field").after("<p class='form-row form-row-last' id='ares_fetch_button'><label>&nbsp;</label><button class='button alt' style='width: 100%' disabled='disabled'><?= _e("Načíst data podle IČO", "woocommerce-ares"); ?></button></p>");

		if($("#ares_is_company").checked) {
			$('#ares_ico_field').show();
			$('#ares_dic_field').show();

			$('#billing_company_field').show();
			$('#ares_fetch_button').show();
		} else {
			$('#ares_ico_field').hide();
			$('#ares_dic_field').hide();
			$('#ares_ico input').val('');   
			$('#ares_dic input').val('');   

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
                $('#ares_ico input').val(''); 
                $('#ares_dic input').val(''); 

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
	    			alert("Pro zadané IČO jsme žádná data nenašli.");
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

add_action('wp_ajax_nopriv_ares_action', 'woocommerce_ares_fill_from_ares');
add_action('wp_ajax_ares_action', 'woocommerce_ares_fill_from_ares');


function woocommerce_ares_fill_from_ares() {
	$ico = intval(str_replace(' ', '', $_POST['ico']));

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
                $return = array( 'error' => __('Entity doesn\'t exist in ARES.', 'woocommerce-ares') . $ico);
            }

        } else {
            $return = array( 'error' => __('ARES is not responding', 'woocommerce-ares'));
        }
        
    } else {
        $return = array( 'error' => __('WP ERROR, can\'t connect.', 'woocommerce-ares'));
    }

    print json_encode($return);

    exit();
}