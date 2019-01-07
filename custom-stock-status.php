<?php
/**!
 * Plugin Name: Custom Woo Stock Status
 * Plugin URI:  www.stackonet.com
 * Description: Write the custom stock status with different colors for each woocommerce product, to show in product details and listing pages.
 * Version:     0.1
 * Author:      Stackonet Services Pvt Ltd
 * Author URI:  www.stackonet.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-stock-status
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Stackonet_Custom_Stock_Status' ) ) {
	class Stackonet_Custom_Stock_Status {

		/**
		 * The instance of the class
		 *
		 * @var self
		 */
		private static $instance;

		/**
		 * Ensures only one instance of the class is loaded or can be loaded.
		 *
		 * @return self - Main instance
		 */
		public static function init() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();

				add_action( 'admin_enqueue_scripts', [ self::$instance, 'admin_scripts' ] );
				add_filter( 'woocommerce_get_sections_products', [ self::$instance, 'add_section' ] );
				add_filter( 'woocommerce_get_settings_products', [ self::$instance, 'add_settings' ], 10, 2 );
				add_action( 'woocommerce_admin_field_woorei_dynamic_field_table', [ self::$instance, 'admin_field' ] );
				add_action( 'woocommerce_update_option_woorei_dynamic_field_table', [
					self::$instance,
					'update_settings'
				] );

				add_action( 'woocommerce_product_options_stock_status', 'add_custom_stock_type', 999 );
				add_action( 'woocommerce_process_product_meta', 'save_custom_stock_type', 99, 1 );
				add_action( 'woocommerce_get_availability', 'get_custom_availability', 10, 2 );
				add_filter( 'woocommerce_stock_html', 'edit_custom_availability', 10, 3 );
			}

			return self::$instance;
		}

		/**
		 * Load admin scripts
		 */
		public function admin_scripts() {
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );
		}

		/**
		 * Add WooCommerce section
		 *
		 * @param array $sections
		 *
		 * @return array
		 */
		function add_section( $sections ) {
			$sections['mysettings'] = __( 'Stock statuses', 'textdomain' );

			return $sections;
		}

		/**
		 * Add settings to the specific section we created before
		 */
		function add_settings( $settings, $current_section ) {
			/**
			 * Check the current section is what we want
			 **/
			if ( $current_section == 'mysettings' ) {
				$settings = array();
				// Add Title to the Settings
				$settings[] = array(
					'name' => __( 'Stock statuses', 'textdomain' ),
					'type' => 'title',
					'desc' => __( 'The following options are used to configure my options.', 'textdomain' ),
					'id'   => 'mysettings'
				);
				$settings[] = array( 'type' => 'woorei_dynamic_field_table', 'id' => 'woorei_dynamic_field_table' );
				$settings[] = array( 'type' => 'sectionend', 'id' => 'mysettings' );
			}

			return $settings;
		}

		/**
		 * Add WooCommerce settings field
		 *
		 * @param $value
		 */
		public function admin_field( $value ) {
			?>
            <style>
                table.woorei_mysettings.wc_input_table.sortable.widefat {
                    max-width: 800px;
                }

                .wp-picker-holder {
                    position: absolute;
                }
            </style>
            <table class="woorei_mysettings wc_input_table sortable widefat">
                <thead>
                <tr>
                    <th width="20px"><?php _e( 'Use it', 'woorei' ); ?></th>
                    <th width="300px"><?php _e( 'Name', 'woorei' ); ?></th>
                    <th width="280px"><?php _e( 'Color', 'woorei' ); ?></th>
                </tr>
                </thead>
                <tbody id="rates">
				<?php
				$woorei_mysettings = get_option( 'woorei_mysettings', array() );
				foreach ( $woorei_mysettings as $key => $data ) {
					?>
                    <tr>
                        <td align="center">
                            <input type="checkbox" class="woorei_mysettings_default_radios"
                                   name="woorei_mysettings[default][<?php echo $key ?>]"
                                   value="yes" <?php if ( $data['default'] == 'yes' ) {
								echo 'checked="checked"';
							} ?> />
                            <!--<input type="hidden" class="woorei_mysettings_default" name="woorei_mysettings[default][]" value="<?php echo esc_attr( $data['default'] ) ?>" />
							--></td>
                        <td>
                            <input type="text" value="<?php echo esc_attr( $data['name'] ) ?>"
                                   name="woorei_mysettings[name][]"/>
                        </td>
                        <td>
                            <input class="colorpicker" type="text" value="<?php echo esc_attr( $data['id'] ) ?>"
                                   name="woorei_mysettings[id][]"/>
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
                        <a href="#"
                           class="button minus remove_item"><?php _e( 'Remove selected status(es)', 'woorei' ); ?></a>
                    </th>
                </tr>
                </tfoot>
            </table>
            <script type="text/javascript">
                jQuery(function () {
                    jQuery('input[name*="woorei_mysettings[id][]"]').wpColorPicker();

                    jQuery('.woorei_mysettings .remove_item').click(function () {


                        var $tbody = jQuery('.woorei_mysettings').find('tbody');
                        if ($tbody.find('tr.current').size() > 0) {
                            $current = $tbody.find('tr.current');
                            $current.remove();

                        } else {
                            alert('<?php echo esc_js( __( 'No row(s) selected', 'woorei' ) ); ?>');
                        }
                        return false;
                    });
                    jQuery('.woorei_mysettings .insert').click(function () {

                        var $tbody = jQuery('.woorei_mysettings').find('tbody');
                        var size = $tbody.find('tr').size();
                        var code = '<tr class="new">\
							<td width="20px" align="center">\
								<input type="checkbox" class="woorei_mysettings_default_radio" value="yes" name="woorei_mysettings[default][' + size + ']" />\
								\
							</td>\
							<td><input type="text"  name="woorei_mysettings[name][]" /></td>\
							<td><input type="text" class="color"  name="woorei_mysettings[id][]" /></td>\
						</tr>';
                        if ($tbody.find('tr.current').size() > 0) {
                            $tbody.find('tr.current').after(code);
                        } else {
                            $tbody.append(code);


                        }
                        jQuery('.color').wpColorPicker();
                        return false;
                    });
                    jQuery('.woorei_mysettings').on('click', '.woorei_mysettings_default_radio', function () {
                        //jQuery('.woorei_mysettings_default').val('');
                        //jQuery(this).siblings('.woorei_mysettings_default').val('yes');
                    });
                });
            </script>
			<?php
		}

		/**
		 * Update settings value
		 *
		 * @param $value
		 */
		public function update_settings( $value ) {
			$woorei_mysettings_new = $_POST['woorei_mysettings'];
			$woorei_mysettings     = array();


			foreach ( $woorei_mysettings_new as $fields => $mysettings ) {
				foreach ( $mysettings as $key => $settings ) {
					$woorei_mysettings[ $key ][ $fields ] = $settings;
				}
			}
			update_option( 'woorei_mysettings', $woorei_mysettings );
		}


		/**
		 * Add custom stock type
		 */
		function add_custom_stock_type() {
			foreach ( get_option( 'woorei_mysettings' ) as $option ) {
				if ( $option['default'] != 'yes' ) {
					continue;
				}

				$options[] = $option['name'];
			}

			$options['instock']    = __( 'In stock', 'woocommerce' );
			$options['outofstock'] = __( 'Out of stock', 'woocommerce' );
			?>
            <script type="text/javascript">
                jQuery(function () {
                    //   jQuery('._stock_status_field').not('.custom-stock-status').remove();
                });
            </script>
			<?php
			woocommerce_wp_select( array(
				'id'            => '_stock_status_custom',
				'wrapper_class' => 'hide_if_variable custom-stock-status',
				'label'         => __( 'Custom inStock status', 'woocommerce' ),
				'options'       =>
					$options // The new option !!!
			,
				'desc_tip'      => true,
				'description'   => __( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.', 'woocommerce' )
			) );
		}

		/**
		 * Save custom stock type
		 *
		 * @param $product_id
		 */
		function save_custom_stock_type( $product_id ) {
			update_post_meta( $product_id, '_stock_status_custom', wc_clean( $_POST['_stock_status_custom'] ) );
		}

		/**
		 * Get custom availability
		 *
		 * @param $data
		 * @param $product
		 *
		 * @return array
		 */
		function get_custom_availability( $data, $product ) {
			switch ( $product->stock_status ) {

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
					$data = array(
						'availability' => __( 'Available to Order', 'woocommerce' ),
						'class'        => 'on-request'
					);
					break;
			}

			return $data;
		}

		/**
		 * Edit custom availability
		 *
		 * @param string $html
		 * @param $availability
		 * @param $product
		 *
		 * @return string
		 */
		function edit_custom_availability( $html, $availability, $product ) {
			$statuses = get_option( 'woorei_mysettings' );
			$status   = get_post_meta( $product->id, '_stock_status', 0 );

			$color = $statuses[ $status[0] ]['id'];
			$html  = '<div class="stocks"><span style="color:' . $color . '">' . $statuses[ $status[0] ]['name'] . '</span></div>';

			return $html;
		}
	}
}

Stackonet_Custom_Stock_Status::init();
