<?php
/*
Plugin Name: Custom Woo Stock Status
Plugin URI:  www.stackonet.com
Description: Write the custom stock status with different colors for each woocommerce product, to show in product details and listing pages.
Version:     0.1 
Author:      Stackonet Services Pvt Ltd
Author URI:  www.stackonet.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: custom-stock-status
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


 

 
 /* Подключаем Iris Color Picker 
----------------------------------------------------------------- */
function add_admin_iris_scripts( $hook ){
	// подключаем IRIS
	wp_enqueue_script( 'wp-color-picker' );
	wp_enqueue_style( 'wp-color-picker' );

	// подключаем свой файл скрипта
//	wp_enqueue_script('plugin-script', plugins_url('js/plugin-script.js', __FILE__), array('wp-color-picker'), false, 1 );
}
add_action( 'admin_enqueue_scripts', 'add_admin_iris_scripts' );


add_filter( 'woocommerce_get_sections_products', 'woorei_mysettings_add_section' );
function woorei_mysettings_add_section( $sections ) {
	$sections['mysettings'] = __( 'Stock statuses', 'woorei' );
	return $sections;
}
/**
 * Add settings to the specific section we created before
 */
 
add_filter( 'woocommerce_get_settings_products', 'woorei_mysettings', 10, 2 );
function woorei_mysettings( $settings, $current_section ) {
	/**
	 * Check the current section is what we want
	 **/
	if ( $current_section == 'mysettings' ) {
		$settings = array();
		// Add Title to the Settings
		$settings[] = array( 'name' => __( 'Stock statuses', 'woorei' ), 'type' => 'title', 'desc' => __( 'The following options are used to configure my options.', 'woorei' ), 'id' => 'mysettings' );
		$settings[] = array( 'type' => 'woorei_dynamic_field_table', 'id' => 'woorei_dynamic_field_table' );
		$settings[] = array( 'type' => 'sectionend', 'id' => 'mysettings' );
	}
	
	return $settings;
}
add_action('woocommerce_admin_field_woorei_dynamic_field_table','woorei_admin_field_woorei_dynamic_field_table');
function woorei_admin_field_woorei_dynamic_field_table($value){
	
	?>
	<style>
	table.woorei_mysettings.wc_input_table.sortable.widefat {
	    max-width: 800px;
	}

		 .wp-picker-holder  {
			    position: absolute;
			}
	</style>
	<table class="woorei_mysettings wc_input_table sortable widefat">
			<thead>
				<tr>
					<th width="20px"><?php _e( 'Use it', 'woorei' ); ?></th>
					<th  width="300px"><?php _e( 'Name', 'woorei' ); ?></th>
					<th  width="280px"><?php _e( 'Color', 'woorei' ); ?></th>
				</tr>
			</thead>
			<tbody id="rates">
				<?php
					$woorei_mysettings = get_option('woorei_mysettings',array());
					//print_r($woorei_mysettings);
					foreach ( $woorei_mysettings as $key=>$data ) {
						?>
						<tr>
							<td align="center">
								<input type="checkbox" class="woorei_mysettings_default_radios" name="woorei_mysettings[default][<?php echo $key ?>]" value="yes" <?php if($data['default'] == 'yes') {echo 'checked="checked"';} ?> />
								<!--<input type="hidden" class="woorei_mysettings_default" name="woorei_mysettings[default][]" value="<?php echo esc_attr( $data['default'] ) ?>" />
							--></td>
							<td>
								<input type="text" value="<?php echo esc_attr( $data['name'] ) ?>"  name="woorei_mysettings[name][]" />
							</td>
							<td>
								<input class="colorpicker" type="text" value="<?php echo esc_attr( $data['id'] ) ?>"  name="woorei_mysettings[id][]" />
							</td>
						</tr>
						<?php
					}
				?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="10">
						<a href="#" class="button plus insert"><?php _e( 'Add status', 'woorei' ); ?></a>
						<a href="#" class="button minus remove_item"><?php _e( 'Remove selected status(es)', 'woorei' ); ?></a>
					</th>
				</tr>
			</tfoot>
		</table>
		<script type="text/javascript">
			jQuery( function() {
				jQuery('input[name*="woorei_mysettings[id][]"]').wpColorPicker();
				
				jQuery('.woorei_mysettings .remove_item').click(function() {
					
				
					var $tbody = jQuery('.woorei_mysettings').find('tbody');
					if ( $tbody.find('tr.current').size() > 0 ) {
						$current = $tbody.find('tr.current');
						$current.remove();
						
					} else {
						alert('<?php echo esc_js( __( 'No row(s) selected', 'woorei' ) ); ?>');
					}
					return false;
				});
				jQuery('.woorei_mysettings .insert').click(function() {
				
					var $tbody = jQuery('.woorei_mysettings').find('tbody');
					var size = $tbody.find('tr').size();
					var code = '<tr class="new">\
							<td width="20px" align="center">\
								<input type="checkbox" class="woorei_mysettings_default_radio" value="yes" name="woorei_mysettings[default]['+size+']" />\
								\
							</td>\
							<td><input type="text"  name="woorei_mysettings[name][]" /></td>\
							<td><input type="text" class="color"  name="woorei_mysettings[id][]" /></td>\
						</tr>';
					if ( $tbody.find('tr.current').size() > 0 ) {
						$tbody.find('tr.current').after( code );
					} else {
						$tbody.append( code );
					
				
					}
						jQuery('.color').wpColorPicker();
					return false;
				});
				jQuery('.woorei_mysettings').on('click','.woorei_mysettings_default_radio',function() {
					//jQuery('.woorei_mysettings_default').val('');
					//jQuery(this).siblings('.woorei_mysettings_default').val('yes');
				});
			});
		</script>
	<?php
	
}
add_action('woocommerce_update_option_woorei_dynamic_field_table','woorei_update_option_woorei_dynamic_field_table');
function woorei_update_option_woorei_dynamic_field_table($value){
	$woorei_mysettings_new = $_POST['woorei_mysettings'];
	$woorei_mysettings = array();
	
 
	foreach($woorei_mysettings_new as $fields => $mysettings ){
		foreach( $mysettings as $key => $settings ){
			$woorei_mysettings[$key][$fields] = $settings;
		}
	}
	update_option('woorei_mysettings',$woorei_mysettings);
}



/*adding stock option*/
 
function add_custom_stock_type() {
	
	//print_r(get_option('woorei_mysettings'));

	  
	  
	foreach (get_option('woorei_mysettings') as $option)  {
		if ($option['default'] != 'yes') continue;
		
		$options[ ] = $option['name'];
	}
	
	 $options['instock'] =  __( 'In stock', 'woocommerce' );
    $options['outofstock'] =  __( 'Out of stock', 'woocommerce');
    
    
    ?>
    <script type="text/javascript">
    jQuery(function(){
    
     //   jQuery('._stock_status_field').not('.custom-stock-status').remove();
    });
    </script>
    <?php   
    woocommerce_wp_select( array( 'id' => '_stock_status_custom', 'wrapper_class' => 'hide_if_variable custom-stock-status', 'label' => __( 'Custom inStock status', 'woocommerce' ), 
    'options' => 
        $options // The new option !!!
     , 'desc_tip' => true, 
     'description' => __( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.', 'woocommerce' ) ) );
}
add_action('woocommerce_product_options_stock_status', 'add_custom_stock_type', 999);
 
function save_custom_stock_status( $product_id ) {
    update_post_meta( $product_id, '_stock_status_custom', wc_clean( $_POST['_stock_status_custom'] ) );
}
add_action('woocommerce_process_product_meta', 'save_custom_stock_status',99,1);
 
function woocommerce_get_custom_availability( $data, $product ) {
    switch( $product->stock_status ) {
    	
    	
     case 1:
            $data = array( 'availability' => __( 'In stock', 'woocommerce' ), 'class' => 'in-stock' );
        break;
        case 'instock':
            $data = array( 'availability' => __( 'In stock', 'woocommerce' ), 'class' => 'in-stock' );
        break;
        case 'outofstock':
            $data = array( 'availability' => __( 'Out of stock', 'woocommerce' ), 'class' => 'out-of-stock' );
        break;
        case 'onrequest':
            $data = array( 'availability' => __( 'Available to Order', 'woocommerce' ), 'class' => 'on-request' );
        break;
    }
    return $data;
}
 add_action('woocommerce_get_availability', 'woocommerce_get_custom_availability', 10, 2);



 

function edit_avaliability($html, $availability, $product) {
 // Change In Stock Text
   /* if ( $product->is_in_stock() ) {
        $availability['availability'] = __('Available!', 'woocommerce');
    } elseif ( ! $product->is_in_stock() ) {
        $availability['availability'] = __('Sold Out', 'woocommerce');
    }
    */
    
    
    $statuses = get_option('woorei_mysettings');
    $status = get_post_meta( $product->id, '_stock_status', 0);
    
    $color = $statuses[$status[0]]['id'];
    $html = '<div class="stocks"><span style="color:'.$color.'">'.$statuses[$status[0]]['name'].'</span></div>';
    
    return $html ;
}

 add_filter('woocommerce_stock_html', 'edit_avaliability', 10, 3);


 //add_action('wp_footer', 'get_hooks');
 function get_hooks() {
 //global $wp_filter;
  //    print '<pre>';
 //   print_r( $wp_filter['woocommerce_single_product_summary'] );
    //print '</pre>';.
    ?>
    <script>
    jQuery(document).ready(function($){
    $('.product-stock').html(' ')
    	$('.stocks span').appendTo('.product-stock')
    })
    
     </script>
    <?php
    
    
    }
 

?>