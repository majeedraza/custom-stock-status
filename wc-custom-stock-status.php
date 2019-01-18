<?php
/**!
 * Plugin Name: WooCommerce Custom Stock Status
 * Plugin URI: https://wordpress.org/plugins/wc-custom-stock-status/
 * Description: Write the custom stock status with different colors for each WooCommerce product, to show in product details and listing pages.
 * Version: 1.0.0
 * Author: Stackonet Services Private Limited
 * Author URI: www.stackonet.com
 * Requires at least: 4.4
 * Tested up to: 5.0
 * WC requires at least: 3.0
 * WC tested up to: 3.5
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-custom-stock-status
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Custom_Stock_Status' ) ) {

	/**
	 * Main WooCommerce_Custom_Stock_Status Class.
	 *
	 * @class WooCommerce_Custom_Stock_Status
	 */
	class WC_Custom_Stock_Status {

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

				// Add setting link on plugin page
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ self::$instance, 'action_links' ] );

				// Load plugin admin scripts
				add_action( 'admin_enqueue_scripts', [ self::$instance, 'admin_scripts' ] );

				// Add "Stock statuses" settings under "WooCommerce -> Settings -> Products"
				add_filter( 'woocommerce_get_sections_products', [ self::$instance, 'add_section' ] );
				add_filter( 'woocommerce_get_settings_products', [ self::$instance, 'add_settings' ], 10, 2 );

				// Add our custom field
				add_action( 'woocommerce_admin_field_wc_custom_stock_status_control', [ self::$instance, 'admin_field' ] );

				// Save our custom settings values
				add_action( 'woocommerce_admin_settings_sanitize_option', [ self::$instance, 'update_settings' ] );

				// product status tab on product
				add_action( 'woocommerce_product_options_stock_status', [ self::$instance, 'stock_status' ], 999 );

				// Save product status
				add_action( 'woocommerce_process_product_meta', [ self::$instance, 'save_stock_status' ], 99, 1 );

				// Replace product availability text
				add_filter( 'woocommerce_get_availability', [ self::$instance, 'product_availability' ], 10, 2 );
			}

			return self::$instance;
		}

		/**
		 * Add custom links on plugins page.
		 *
		 * @param array $links
		 *
		 * @return array
		 */
		public function action_links( $links ) {
			$setting_url  = add_query_arg( array(
				'page'    => 'wc-settings',
				'tab'     => 'products',
				'section' => 'stock_statuses',
			), admin_url( 'admin.php' ) );
			$plugin_links = array(
				'<a href="' . $setting_url . '">' . __( 'Settings', 'wc-custom-stock-status' ) . '</a>'
			);

			return array_merge( $plugin_links, $links );
		}

		/**
		 * Load admin scripts
		 */
		public function admin_scripts() {
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );
		}

		/**
		 * Create the section beneath the products tab
		 *
		 * @param array $sections
		 *
		 * @return array
		 */
		function add_section( $sections ) {
			$sections['stock_statuses'] = __( 'Stock statuses', 'wc-custom-stock-status' );

			return $sections;
		}

		/**
		 * Add settings to the specific section we created before
		 *
		 * @param array $settings
		 * @param string $current_section
		 *
		 * @return array
		 */
		function add_settings( $settings, $current_section ) {
			/**
			 * Check the current section is what we want
			 **/
			if ( $current_section == 'stock_statuses' ) {
				$settings = array();
				// Add Title to the Settings
				$settings[] = array(
					'id'   => 'stock_statuses',
					'type' => 'title',
					'name' => __( 'Stock statuses', 'wc-custom-stock-status' ),
					'desc' => __( 'The following options are used to configure stock statuses.', 'wc-custom-stock-status' ),
				);
				$settings[] = array(
					'id'   => 'wc_custom_stock_status_control',
					'type' => 'wc_custom_stock_status_control',
				);
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
                table.wc_custom_stock_statuses.wc_input_table.sortable.widefat {
                    max-width: 800px;
                }

                .wp-picker-holder {
                    position: absolute;
                }
            </style>
            <table class="wc_custom_stock_statuses wc_input_table sortable widefat">
                <thead>
                <tr>
                    <th width="20px"><?php _e( 'Use it', 'wc-custom-stock-status' ); ?></th>
                    <th width="300px"><?php _e( 'Name', 'wc-custom-stock-status' ); ?></th>
                    <th width="280px"><?php _e( 'Color', 'wc-custom-stock-status' ); ?></th>
                </tr>
                </thead>
                <tbody id="rates">
				<?php
				$wc_custom_stock_statuses = get_option( 'wc_custom_stock_statuses', array() );
				foreach ( $wc_custom_stock_statuses as $key => $data ) {
					?>
                    <tr>
                        <td align="center">
                            <input type="checkbox" class="wc_custom_stock_statuses_default_radios"
                                   name="wc_custom_stock_statuses[default][<?php echo $key ?>]"
                                   value="yes" <?php echo ( isset( $data['default'] ) && $data['default'] == 'yes' ) ? 'checked="checked"' : ''; ?> />
                        </td>
                        <td>
                            <input type="text" value="<?php echo esc_attr( $data['name'] ) ?>"
                                   name="wc_custom_stock_statuses[name][]"/>
                        </td>
                        <td>
                            <input class="colorpicker" type="text" value="<?php echo esc_attr( $data['id'] ) ?>"
                                   name="wc_custom_stock_statuses[id][]"/>
                        </td>
                    </tr>
					<?php
				}
				?>
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="10">
                        <a href="#" class="button plus insert">
							<?php _e( 'Add status', 'wc-custom-stock-status' ); ?>
                        </a>
                        <a href="#" class="button minus remove_item">
							<?php _e( 'Remove selected status(es)', 'wc-custom-stock-status' ); ?>
                        </a>
                    </th>
                </tr>
                </tfoot>
            </table>
            <script type="text/javascript">
                jQuery(function () {
                    jQuery('input[name*="wc_custom_stock_statuses[id][]"]').wpColorPicker();

                    jQuery('.wc_custom_stock_statuses .remove_item').click(function () {

                        var $tbody = jQuery('.wc_custom_stock_statuses').find('tbody');
                        if ($tbody.find('tr.current').size() > 0) {
                            $current = $tbody.find('tr.current');
                            $current.remove();
                        } else {
                            alert('<?php echo esc_js( __( 'No row(s) selected', 'wc-custom-stock-status' ) ); ?>');
                        }
                        return false;
                    });
                    jQuery('.wc_custom_stock_statuses .insert').click(function () {
                        var $tbody = jQuery('.wc_custom_stock_statuses').find('tbody');
                        var size = $tbody.find('tr').size();
                        var code = '<tr class="new">\
							<td width="20px" align="center">\
								<input type="checkbox" class="wc_custom_stock_statuses_default_radio" value="yes" name="wc_custom_stock_statuses[default][' + size + ']" />\
								\
							</td>\
							<td><input type="text"  name="wc_custom_stock_statuses[name][]" /></td>\
							<td><input type="text" class="color"  name="wc_custom_stock_statuses[id][]" /></td>\
						</tr>';
                        if ($tbody.find('tr.current').size() > 0) {
                            $tbody.find('tr.current').after(code);
                        } else {
                            $tbody.append(code);
                        }
                        jQuery('.color').wpColorPicker();
                        return false;
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
			$new_statuses = isset( $_POST['wc_custom_stock_statuses'] ) ? $_POST['wc_custom_stock_statuses'] : [];
			$new_statuses = static::sanitize( $new_statuses );
			$statuses     = array();

			foreach ( $new_statuses as $fields => $stock_statuses ) {
				foreach ( $stock_statuses as $key => $settings ) {
					$statuses[ $key ][ $fields ] = $settings;
				}
			}

			update_option( 'wc_custom_stock_statuses', $statuses );
		}


		/**
		 * Add custom stock type
		 */
		public function stock_status() {
			foreach ( get_option( 'wc_custom_stock_statuses' ) as $option ) {
				if ( ! ( isset( $option['default'] ) && $option['default'] == 'yes' ) ) {
					continue;
				}

				$options[] = $option['name'];
			}

			$options['instock']    = __( 'In stock', 'wc-custom-stock-status' );
			$options['outofstock'] = __( 'Out of stock', 'wc-custom-stock-status' );
			?>
            <script type="text/javascript">
                jQuery(function () {
                    jQuery('._stock_status_field').not('.custom-stock-status').remove();
                });
            </script>
			<?php
			woocommerce_wp_select( array(
				'id'            => '_stock_status',
				'wrapper_class' => 'hide_if_variable custom-stock-status',
				'label'         => __( 'Stock status', 'wc-custom-stock-status' ),
				'options'       => $options, // The new option
				'desc_tip'      => true,
				'description'   => __( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.', 'wc-custom-stock-status' )
			) );
		}

		/**
		 * Save custom stock status
		 *
		 * @param $product_id
		 */
		function save_stock_status( $product_id ) {
			if ( ! empty( $_POST['_stock_status'] ) ) {
				update_post_meta( $product_id, '_stock_status', static::sanitize( $_POST['_stock_status'] ) );
			} else {
				delete_post_meta( $product_id, '_stock_status' );
			}
		}

		/**
		 * Returns the availability of the product.
		 *
		 * @param array $availability
		 * @param \WC_Product $product
		 *
		 * @return string[]
		 */
		function product_availability( $availability, $product ) {
			$statuses = get_option( 'wc_custom_stock_statuses' );
			$status   = get_post_meta( $product->get_id(), '_stock_status', true );
			$color    = esc_attr( $statuses[ $status ]['id'] );
			$label    = esc_attr( $statuses[ $status ]['name'] );


			// Change In Stock Text
			if ( $product->is_in_stock() ) {
				$availability['availability'] = __( 'Available!', 'wc-custom-stock-status' );
			} elseif ( ! $product->is_in_stock() ) {
				$availability['availability'] = __( 'Sold Out', 'wc-custom-stock-status' );
			}

			if ( ! empty( $label ) ) {
				$availability['availability'] = '<span style="color:' . $color . '">' . $label . '</span>';
			}

			return $availability;
		}

		/**
		 * Sanitize user submitted data
		 *
		 * @param mixed $data
		 *
		 * @return array|string
		 */
		private static function sanitize( $data ) {
			if ( is_array( $data ) ) {
				$_data = array();
				foreach ( $data as $key => $value ) {
					$_data[ $key ] = self::sanitize( $value );
				}

				return $_data;
			}

			return is_scalar( $data ) ? sanitize_text_field( $data ) : '';
		}
	}
}

WC_Custom_Stock_Status::init();
