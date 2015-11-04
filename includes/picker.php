<?php
/**
 * Menu editor handler
 *
 * @package Menu_Icons
 * @author  Dzikri Aziz <kvcrvt@gmail.com>
 */


/**
 * Nav menu admin
 */
final class Menu_Icons_Picker {

	/**
	 * Initialize class
	 *
	 * @since   0.1.0
	 * @wp_hook action load-nav-menus.php
	 */
	public static function init() {
		add_action( 'load-nav-menus.php', array( __CLASS__, '_load_nav_menus' ) );
		add_filter( 'wp_edit_nav_menu_walker', array( __CLASS__, '_filter_wp_edit_nav_menu_walker' ), 99 );
		add_filter( 'wp_nav_menu_item_custom_fields', array( __CLASS__, '_fields' ), 10, 4 );
		add_filter( 'manage_nav-menus_columns', array( __CLASS__, '_columns' ), 99 );
		add_action( 'wp_update_nav_menu_item', array( __CLASS__, '_save' ), 10, 3 );
	}


	/**
	 * Load Icon Picker
	 *
	 * @since   0.9.0
	 * @wp_hook action load-nav-menus.php/10
	 */
	public static function _load_nav_menus() {
		Icon_Picker::instance()->load();

		add_action( 'print_media_templates', array( __CLASS__, '_media_templates' ) );
	}



	/**
	 * Custom walker
	 *
	 * @since   0.3.0
	 * @access  protected
	 * @wp_hook filter    wp_edit_nav_menu_walker/10/1
	 */
	public static function _filter_wp_edit_nav_menu_walker( $walker ) {
		// Load menu item custom fields plugin
		if ( ! class_exists( 'Menu_Item_Custom_Fields_Walker' ) ) {
			require_once Menu_Icons::get( 'dir' ) . 'includes/walker-nav-menu-edit.php';
		}
		$walker = 'Menu_Item_Custom_Fields_Walker';

		return $walker;
	}


	/**
	 * Print fields
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @uses    add_action() Calls 'menu_icons_before_fields' hook
	 * @uses    add_action() Calls 'menu_icons_after_fields' hook
	 * @wp_hook action       menu_item_custom_fields/10/3
	 *
	 * @param object $item  Menu item data object.
	 * @param int    $depth Nav menu depth.
	 * @param array  $args  Menu item args.
	 * @param int    $id    Nav menu ID.
	 *
	 * @return string Form fields
	 */
	public static function _fields( $id, $item, $depth, $args ) {
		if ( ! class_exists( 'Kucrut_Form_Field' ) ) {
			require_once Menu_Icons::get( 'dir' ) . 'includes/library/form-fields.php';
		}

		$input_id   = sprintf( 'menu-icons-%d', $item->ID );
		$input_name = sprintf( 'menu-icons[%d]', $item->ID );
		$current    = wp_parse_args(
			Menu_Icons_Meta::get( $item->ID ),
			Menu_Icons_Settings::get_menu_settings( Menu_Icons_Settings::get_current_menu_id() )
		);
		$fields     = array_merge(
			array(
				array(
					'id'    => 'type',
					'label' => __( 'Type' ),
					'value' => $current['type'],
				),
				array(
					'id'    => 'icon',
					'label' => __( 'Icon' ),
					'value' => $current['icon'],
				),
			),
			Menu_Icons_Settings::get_settings_fields( $current )
		);
		?>
			<div class="field-icon description-wide menu-icons-wrap" data-id="<?php echo json_encode( $item->ID ); ?>">
				<?php
					/**
					 * Allow plugins/themes to inject HTML before menu icons' fields
					 *
					 * @param object $item  Menu item data object.
					 * @param int    $depth Nav menu depth.
					 * @param array  $args  Menu item args.
					 * @param int    $id    Nav menu ID.
					 *
					 */
					do_action( 'menu_icons_before_fields', $item, $depth, $args, $id );
				?>
				<p class="description submitbox">
					<label><?php esc_html_e( 'Icon:', 'menu-icons' ) ?></label>
					<?php printf( '<a class="_select">%s</a>', esc_html__( 'Select', 'menu-icons' ) ); ?>
					<?php printf( '<a class="_remove submitdelete">%s</a>', esc_html__( 'Remove', 'menu-icons' ) ); ?>
				</p>
				<div class="_settings hidden">
					<?php
					foreach ( $fields as $field ) {
						printf(
							'<label>%1$s: <input type="text" name="%2$s" class="_mi-%3$s" value="%4$s" /></label><br />',
							esc_html( $field['label'] ),
							esc_attr( "{$input_name}[{$field['id']}]" ),
							esc_attr( $field['id'] ),
							esc_attr( $field['value'] )
						);
					}
					printf(
						'<input type="hidden" class="_mi-url" value="%s" />',
						esc_attr( $current['url'] )
					);
					?>
				</div>
				<?php
					/**
					 * Allow plugins/themes to inject HTML after menu icons' fields
					 *
					 * @param object $item  Menu item data object.
					 * @param int    $depth Nav menu depth.
					 * @param array  $args  Menu item args.
					 * @param int    $id    Nav menu ID.
					 *
					 */
					do_action( 'menu_icons_after_fields', $item, $depth, $args, $id );
				?>
			</div>
		<?php
	}


	/**
	 * Add our field to the screen options toggle
	 *
	 * @since   0.1.0
	 * @access  private
	 * @wp_hook action manage_nav-menus_columns
	 * @link    http://codex.wordpress.org/Plugin_API/Filter_Reference/manage_posts_columns Action: manage_nav-menus_columns/99
	 *
	 * @param array $columns Menu item columns
	 * @return array
	 */
	public static function _columns( $columns ) {
		$columns['icon'] = __( 'Icon', 'menu-icons' );

		return $columns;
	}


	/**
	 * Save menu item metadata
	 *
	 * @since 0.8.0
	 *
	 * @param int   $id    Menu item ID.
	 * @param mixed $value Metadata value.
	 *
	 * @return void
	 */
	public static function save_menu_item_meta( $id, $value ) {
		/**
		 * Allow plugins/themes to filter the values
		 *
		 * Deprecated.
		 *
		 * @since 0.1.0
		 * @param array $value Metadata value.
		 * @param int   $id    Menu item ID.
		 */
		$_value = apply_filters( 'menu_icons_values', $value, $id );

		if ( $_value !== $value ) {
			_deprecated_function( 'menu_icons_values', '0.8.0', 'menu_icons_item_meta_values' );
		}

		/**
		 * Allow plugins/themes to filter the values
		 *
		 * Deprecated.
		 *
		 * @since 0.8.0
		 * @param array $value Metadata value.
		 * @param int   $id    Menu item ID.
		 */
		$value = apply_filters( 'menu_icons_item_meta_values', $_value, $id );

		// Update
		if ( ! empty( $value ) ) {
			update_post_meta( $id, 'menu-icons', $value );
		} else {
			delete_post_meta( $id, 'menu-icons' );
		}
	}


	/**
	 * Save menu item's icons values
	 *
	 * @since  0.1.0
	 * @access protected
	 * @uses   apply_filters() Calls 'menu_icons_values' on returned array.
	 * @link   http://codex.wordpress.org/Plugin_API/Action_Reference/wp_update_nav_menu_item Action: wp_update_nav_menu_item/10/2
	 *
	 * @param int   $menu_id         Nav menu ID
	 * @param int   $menu_item_db_id Menu item ID
	 * @param array $menu_item_args  Menu item data
	 */
	public static function _save( $menu_id, $menu_item_db_id, $menu_item_args ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen instanceof WP_Screen || 'nav-menus' !== $screen->id ) {
			return;
		}

		check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );

		// Sanitize
		if ( ! empty( $_POST['menu-icons'][ $menu_item_db_id ] ) ) {
			$value = array_map(
				'sanitize_text_field',
				wp_unslash( (array) $_POST['menu-icons'][ $menu_item_db_id ] )
			);
		} else {
			$value = array();
		}

		self::save_menu_item_meta( $menu_item_db_id, $value );
	}


	/**
	 * Get and print media templates from all types
	 *
	 * @since   0.2.0
	 * @since   0.9.0 Deprecate menu_icons_media_templates filter.
	 * @wp_hook action print_media_templates
	 */
	public static function _media_templates() {
		$id_prefix = 'tmpl-menu-icons';

		// Deprecated
		$templates = apply_filters( 'menu_icons_media_templates', array() );

		if ( ! empty( $templates ) ) {
			if ( WP_DEBUG ) {
				_deprecated_function( 'menu_icons_media_templates', '0.9.0', 'menu_icons_js_templates' );
			}

			foreach ( $templates as $key => $template ) {
				$id = sprintf( '%s-%s', $id_prefix, $key );
				self::_print_tempate( $id, $template );
			}
		}

		require_once dirname( __FILE__ ) . '/media-template.php';
	}


	/**
	 * Print media template
	 *
	 * @since 0.2.0
	 * @param string $id       Template ID
	 * @param string $template Media template HTML
	 */
	protected static function _print_tempate( $id, $template ) {
		?>
			<script type="text/html" id="<?php echo esc_attr( $id ) ?>">
				<?php echo $template; // xss ok ?>
			</script>
		<?php
	}
}
