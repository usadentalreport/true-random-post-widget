<?php
/**
 * Plugin Name: True Random Post Widget
 * Description: Display a truly random post from your entire database on page refresh. Properly randomizes across all posts, not just recent ones.
 * Version: 2.2.0
 * Author: Your Name
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Text Domain: true-random-post-widget
 * Domain Path: /languages
 * License: GPL3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package TrueRandomPostWidget
 */

if ( ! class_exists( 'TrueRandomPostWidget' ) ) {

	class TrueRandomPostWidget {

		const SHORTCODE    = 'true_random_post';
		const OPTION_GROUP = 'trpw_settings';
		const MENU_SLUG    = 'true-random-post-widget';

		/** Shortcode defaults (post-level query params only; display is driven by admin settings). */
		const DEFAULT_ATTRIBUTES = array(
			'post_type'      => 'post',
			'image_required' => false,
			'class'          => '',
		);

		/* ---------------------------------------------------------------
		 * Option keys
		 * ------------------------------------------------------------- */
		const OPT_IMAGE_SOURCE      = 'trpw_image_source';      // 'featured' | 'global'
		const OPT_GLOBAL_IMAGE_ID   = 'trpw_global_image_id';   // attachment ID
		const OPT_TAXONOMY          = 'trpw_taxonomy';           // taxonomy slug
		const OPT_TERM_LOGO_FIELD   = 'trpw_term_logo_field';   // SCF/ACF field key for term logo
		const OPT_BUTTON_TEXT       = 'trpw_button_text';        // e.g. 'Listen'
		const OPT_BUTTON_BG_COLOR   = 'trpw_button_bg_color';   // hex
		const OPT_BUTTON_TEXT_COLOR = 'trpw_button_text_color'; // hex

		/* ---------------------------------------------------------------
		 * Bootstrap
		 * ------------------------------------------------------------- */

		/**
		 * Initialize the plugin.
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
			add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );

			// Admin-only hooks
			add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
			add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );

			// Term logo images are now managed via Secure Custom Fields (SCF) or ACF.
			// No custom term meta hooks needed here.
		}

		/**
		 * Load plugin textdomain.
		 */
		public static function load_textdomain() {
			load_plugin_textdomain(
				'true-random-post-widget',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages/'
			);
		}

		/* ---------------------------------------------------------------
		 * Front-end assets
		 * ------------------------------------------------------------- */

		/**
		 * Enqueue plugin styles.
		 */
		public static function enqueue_styles() {
			wp_register_style(
				self::SHORTCODE,
				plugins_url( '/assets/style.css', __FILE__ ),
				array(),
				'2.0.0'
			);
		}

		/* ---------------------------------------------------------------
		 * Admin: menu + settings page
		 * ------------------------------------------------------------- */

		/**
		 * Add plugin settings page under Settings menu.
		 */
		public static function add_admin_menu() {
			add_options_page(
				__( 'True Random Post Widget', 'true-random-post-widget' ),
				__( 'True Random Post', 'true-random-post-widget' ),
				'manage_options',
				self::MENU_SLUG,
				array( __CLASS__, 'render_settings_page' )
			);
		}

		/**
		 * Register plugin settings with the Settings API.
		 */
		public static function register_settings() {
			register_setting( self::OPTION_GROUP, self::OPT_IMAGE_SOURCE, array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'featured',
			) );
			register_setting( self::OPTION_GROUP, self::OPT_GLOBAL_IMAGE_ID, array(
				'sanitize_callback' => 'absint',
				'default'           => 0,
			) );
			register_setting( self::OPTION_GROUP, self::OPT_TAXONOMY, array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			) );
			register_setting( self::OPTION_GROUP, self::OPT_TERM_LOGO_FIELD, array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			) );
			register_setting( self::OPTION_GROUP, self::OPT_BUTTON_TEXT, array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'Listen',
			) );
			register_setting( self::OPTION_GROUP, self::OPT_BUTTON_BG_COLOR, array(
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#1a1a1a',
			) );
			register_setting( self::OPTION_GROUP, self::OPT_BUTTON_TEXT_COLOR, array(
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#ffffff',
			) );
		}

		/**
		 * Enqueue admin JS/CSS on the settings page and on taxonomy term edit pages.
		 *
		 * @param string $hook Current admin page hook.
		 */
		public static function enqueue_admin_scripts( $hook ) {
			if ( 'settings_page_' . self::MENU_SLUG !== $hook ) {
				return;
			}

			// Media library + color picker (settings page only)
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );

			wp_enqueue_script(
				'trpw-admin',
				plugins_url( '/assets/admin.js', __FILE__ ),
				array( 'jquery', 'wp-color-picker' ),
				'2.2.0',
				true
			);

			wp_localize_script( 'trpw-admin', 'trpwAdmin', array(
				'mediaTitle'     => __( 'Select Image', 'true-random-post-widget' ),
				'mediaButton'    => __( 'Use this image', 'true-random-post-widget' ),
				'changeText'     => __( 'Change Image', 'true-random-post-widget' ),
				'removeText'     => __( 'Remove', 'true-random-post-widget' ),
				'selectText'     => __( 'Select Image', 'true-random-post-widget' ),
				'onSettingsPage' => '1',
			) );
		}

		/**
		 * Render the plugin settings page.
		 */
		public static function render_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$image_source     = get_option( self::OPT_IMAGE_SOURCE, 'featured' );
			$global_image_id  = (int) get_option( self::OPT_GLOBAL_IMAGE_ID, 0 );
			$taxonomy         = get_option( self::OPT_TAXONOMY, '' );
			$term_logo_field  = get_option( self::OPT_TERM_LOGO_FIELD, '' );
			$button_text      = get_option( self::OPT_BUTTON_TEXT, 'Listen' );
			$button_bg        = get_option( self::OPT_BUTTON_BG_COLOR, '#1a1a1a' );
			$button_color     = get_option( self::OPT_BUTTON_TEXT_COLOR, '#ffffff' );
			$scf_active       = function_exists( 'get_field' );

			$global_image_url = $global_image_id ? wp_get_attachment_image_url( $global_image_id, 'thumbnail' ) : '';

			// All public taxonomies
			$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'True Random Post Widget Settings', 'true-random-post-widget' ); ?></h1>

				<form method="post" action="options.php">
					<?php settings_fields( self::OPTION_GROUP ); ?>

					<!-- ================================================
					     IMAGE SETTINGS
					     ============================================== -->
					<h2 class="title"><?php esc_html_e( 'Image Settings', 'true-random-post-widget' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Image Source', 'true-random-post-widget' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="radio"
											name="<?php echo esc_attr( self::OPT_IMAGE_SOURCE ); ?>"
											value="featured"
											<?php checked( $image_source, 'featured' ); ?> />
										<?php esc_html_e( 'Use post featured image', 'true-random-post-widget' ); ?>
									</label>
									<br><br>
									<label>
										<input type="radio"
											name="<?php echo esc_attr( self::OPT_IMAGE_SOURCE ); ?>"
											value="global"
											<?php checked( $image_source, 'global' ); ?> />
										<?php esc_html_e( 'Use a global image', 'true-random-post-widget' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr id="trpw-global-image-row"<?php echo 'global' !== $image_source ? ' style="display:none;"' : ''; ?>>
							<th scope="row"><?php esc_html_e( 'Global Image', 'true-random-post-widget' ); ?></th>
							<td>
								<input type="hidden"
									id="trpw-global-image-id"
									name="<?php echo esc_attr( self::OPT_GLOBAL_IMAGE_ID ); ?>"
									value="<?php echo esc_attr( $global_image_id ); ?>" />

								<div id="trpw-global-image-preview">
									<?php if ( $global_image_url ) : ?>
										<img src="<?php echo esc_url( $global_image_url ); ?>"
											style="max-width:150px;height:auto;display:block;margin-bottom:8px;border:1px solid #ddd;border-radius:4px;" />
									<?php endif; ?>
								</div>

								<button type="button" id="trpw-select-global-image" class="button button-secondary">
									<?php echo $global_image_id
										? esc_html__( 'Change Image', 'true-random-post-widget' )
										: esc_html__( 'Select Image', 'true-random-post-widget' ); ?>
								</button>

								<button type="button" id="trpw-remove-global-image" class="button button-link-delete"
									style="margin-left:6px;<?php echo ! $global_image_id ? 'display:none;' : ''; ?>">
									<?php esc_html_e( 'Remove', 'true-random-post-widget' ); ?>
								</button>
							</td>
						</tr>
					</table>

					<!-- ================================================
					     DISPLAY SETTINGS
					     ============================================== -->
					<h2 class="title"><?php esc_html_e( 'Display Settings', 'true-random-post-widget' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( self::OPT_TAXONOMY ); ?>">
									<?php esc_html_e( 'Footer Taxonomy', 'true-random-post-widget' ); ?>
								</label>
							</th>
							<td>
								<select id="<?php echo esc_attr( self::OPT_TAXONOMY ); ?>"
									name="<?php echo esc_attr( self::OPT_TAXONOMY ); ?>">
									<option value=""><?php esc_html_e( '— None —', 'true-random-post-widget' ); ?></option>
									<?php foreach ( $taxonomies as $tax_slug => $tax_obj ) : ?>
										<option value="<?php echo esc_attr( $tax_slug ); ?>"
											<?php selected( $taxonomy, $tax_slug ); ?>>
											<?php echo esc_html( $tax_obj->label ); ?> (<?php echo esc_html( $tax_slug ); ?>)
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php esc_html_e( 'The first term from this taxonomy will appear in the card footer. Set a field key below to also show a term logo.', 'true-random-post-widget' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( self::OPT_TERM_LOGO_FIELD ); ?>">
									<?php esc_html_e( 'Term Logo Field Key', 'true-random-post-widget' ); ?>
								</label>
							</th>
							<td>
								<input type="text"
									id="<?php echo esc_attr( self::OPT_TERM_LOGO_FIELD ); ?>"
									name="<?php echo esc_attr( self::OPT_TERM_LOGO_FIELD ); ?>"
									value="<?php echo esc_attr( $term_logo_field ); ?>"
									class="regular-text"
									placeholder="e.g. term_logo" />
								<p class="description">
									<?php if ( $scf_active ) : ?>
										<?php esc_html_e( 'Secure Custom Fields (SCF) / ACF is active. Enter the field name of the Image field you created for this taxonomy — the plugin will call get_field() with this key on each term.', 'true-random-post-widget' ); ?>
									<?php else : ?>
										<strong style="color:#d63638;"><?php esc_html_e( 'SCF / ACF is not active.', 'true-random-post-widget' ); ?></strong>
										<?php esc_html_e( ' Install and activate Secure Custom Fields (or ACF), create an Image field group for your taxonomy, then enter the field name here. Without SCF/ACF the field key will be read as plain term meta.', 'true-random-post-widget' ); ?>
									<?php endif; ?>
								</p>
							</td>
						</tr>
					</table>

					<!-- ================================================
					     BUTTON SETTINGS
					     ============================================== -->
					<h2 class="title"><?php esc_html_e( 'Button Settings', 'true-random-post-widget' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( self::OPT_BUTTON_TEXT ); ?>">
									<?php esc_html_e( 'Button Text', 'true-random-post-widget' ); ?>
								</label>
							</th>
							<td>
								<input type="text"
									id="<?php echo esc_attr( self::OPT_BUTTON_TEXT ); ?>"
									name="<?php echo esc_attr( self::OPT_BUTTON_TEXT ); ?>"
									value="<?php echo esc_attr( $button_text ); ?>"
									class="regular-text"
									placeholder="Listen" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( self::OPT_BUTTON_BG_COLOR ); ?>">
									<?php esc_html_e( 'Button Background Color', 'true-random-post-widget' ); ?>
								</label>
							</th>
							<td>
								<input type="text"
									id="<?php echo esc_attr( self::OPT_BUTTON_BG_COLOR ); ?>"
									name="<?php echo esc_attr( self::OPT_BUTTON_BG_COLOR ); ?>"
									value="<?php echo esc_attr( $button_bg ); ?>"
									class="trpw-color-picker"
									data-default-color="#1a1a1a" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( self::OPT_BUTTON_TEXT_COLOR ); ?>">
									<?php esc_html_e( 'Button Text Color', 'true-random-post-widget' ); ?>
								</label>
							</th>
							<td>
								<input type="text"
									id="<?php echo esc_attr( self::OPT_BUTTON_TEXT_COLOR ); ?>"
									name="<?php echo esc_attr( self::OPT_BUTTON_TEXT_COLOR ); ?>"
									value="<?php echo esc_attr( $button_color ); ?>"
									class="trpw-color-picker"
									data-default-color="#ffffff" />
							</td>
						</tr>
					</table>

					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}

		/* ---------------------------------------------------------------
		 * Core query: truly random post
		 * ------------------------------------------------------------- */

		/**
		 * Get a truly random post from the entire database.
		 *
		 * @param array $args Query arguments.
		 * @return WP_Post|false Random post or false if none found.
		 */
		public static function get_random_post( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'image_required' => false,
			);

			$args = wp_parse_args( $args, $defaults );

			// Build IN clause for post types
			$post_types     = is_array( $args['post_type'] ) ? $args['post_type'] : array( $args['post_type'] );
			$post_types     = array_map( 'esc_sql', $post_types );
			$post_type_list = "'" . implode( "','", $post_types ) . "'";

			// Count query
			if ( $args['image_required'] ) {
				$count_query = $wpdb->prepare(
					"SELECT COUNT(DISTINCT p.ID) AS total
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
					WHERE p.post_type IN ({$post_type_list})
					  AND p.post_status = %s
					  AND pm.meta_key = '_thumbnail_id'",
					$args['post_status']
				);
			} else {
				$count_query = $wpdb->prepare(
					"SELECT COUNT(*) AS total
					FROM {$wpdb->posts}
					WHERE post_type IN ({$post_type_list})
					  AND post_status = %s",
					$args['post_status']
				);
			}

			$total = (int) $wpdb->get_var( $count_query );

			if ( ! $total ) {
				return false;
			}

			$offset = wp_rand( 0, $total - 1 );

			// Fetch query
			if ( $args['image_required'] ) {
				$row_query = $wpdb->prepare(
					"SELECT p.*
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
					WHERE p.post_type IN ({$post_type_list})
					  AND p.post_status = %s
					  AND pm.meta_key = '_thumbnail_id'
					ORDER BY p.ID DESC
					LIMIT %d, 1",
					$args['post_status'],
					$offset
				);
			} else {
				$row_query = $wpdb->prepare(
					"SELECT *
					FROM {$wpdb->posts}
					WHERE post_type IN ({$post_type_list})
					  AND post_status = %s
					ORDER BY ID DESC
					LIMIT %d, 1",
					$args['post_status'],
					$offset
				);
			}

			$result = $wpdb->get_row( $row_query );

			return $result ? get_post( $result->ID ) : false;
		}

		/* ---------------------------------------------------------------
		 * Shortcode
		 * ------------------------------------------------------------- */

		/**
		 * Render the [true_random_post] shortcode.
		 *
		 * @param array $atts Shortcode attributes.
		 * @return string HTML output.
		 */
		public static function render_shortcode( $atts ) {
			wp_enqueue_style( self::SHORTCODE );

			$atts = shortcode_atts( self::DEFAULT_ATTRIBUTES, $atts, self::SHORTCODE );

			$image_required = wp_validate_boolean( $atts['image_required'] );

			// ── Plugin settings ──────────────────────────────────────────
			$image_source     = get_option( self::OPT_IMAGE_SOURCE, 'featured' );
			$global_image_id  = (int) get_option( self::OPT_GLOBAL_IMAGE_ID, 0 );
			$taxonomy         = get_option( self::OPT_TAXONOMY, '' );
			$term_logo_field  = get_option( self::OPT_TERM_LOGO_FIELD, '' );
			$button_text      = get_option( self::OPT_BUTTON_TEXT, '' );
			$button_bg        = sanitize_hex_color( get_option( self::OPT_BUTTON_BG_COLOR, '#1a1a1a' ) ) ?: '#1a1a1a';
			$button_color     = sanitize_hex_color( get_option( self::OPT_BUTTON_TEXT_COLOR, '#ffffff' ) ) ?: '#ffffff';

			if ( ! $button_text ) {
				$button_text = __( 'Listen', 'true-random-post-widget' );
			}

			// ── Fetch random post ─────────────────────────────────────────
			$post = self::get_random_post( array(
				'post_type'      => sanitize_text_field( $atts['post_type'] ),
				'image_required' => $image_required,
			) );

			if ( ! $post ) {
				return self::error_message( __( 'No posts found in the database.', 'true-random-post-widget' ) );
			}

			// ── Image ─────────────────────────────────────────────────────
			$image_html = '';
			if ( 'global' === $image_source && $global_image_id ) {
				$image_html = wp_get_attachment_image(
					$global_image_id,
					'large',
					false,
					array( 'class' => 'true-random-post-widget__img' )
				);
			} elseif ( has_post_thumbnail( $post ) ) {
				$image_html = get_the_post_thumbnail(
					$post,
					'large',
					array( 'class' => 'true-random-post-widget__img' )
				);
			}

			// ── Excerpt (fall back to trimmed content) ────────────────────
			$excerpt = '';
			if ( ! empty( $post->post_excerpt ) ) {
				$excerpt = wp_kses_post( $post->post_excerpt );
			} elseif ( ! empty( $post->post_content ) ) {
				$excerpt = esc_html( wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 ) );
			}

			// ── Taxonomy term + logo ───────────────────────────────────────
			$term_name = '';
			$term_logo = '';
			if ( $taxonomy ) {
				$terms = get_the_terms( $post->ID, $taxonomy );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$term      = reset( $terms );
					$term_name = $term->name;

					if ( $term_logo_field ) {
						$logo_id = 0;

						if ( function_exists( 'get_field' ) ) {
							// SCF / ACF is active — call get_field() with the configured key.
							$field_value = get_field( $term_logo_field, $term );

							if ( is_array( $field_value ) && ! empty( $field_value['id'] ) ) {
								// Image Array return format → extract the attachment ID.
								$logo_id = (int) $field_value['id'];
							} elseif ( is_numeric( $field_value ) && $field_value ) {
								// Image ID return format.
								$logo_id = (int) $field_value;
							} elseif ( is_string( $field_value ) && $field_value ) {
								// Image URL return format → render directly.
								$term_logo = '<img src="' . esc_url( $field_value ) . '" '
									. 'class="true-random-post-widget__term-img" '
									. 'width="48" height="48" alt="" />';
							}
						} else {
							// SCF / ACF not active — fall back to plain term meta
							// using the field key as the meta key.
							$logo_id = (int) get_term_meta( $term->term_id, $term_logo_field, true );
						}

						if ( $logo_id && ! $term_logo ) {
							$term_logo = wp_get_attachment_image(
								$logo_id,
								array( 48, 48 ),
								false,
								array( 'class' => 'true-random-post-widget__term-img' )
							);
						}
					}
				}
			}

			// ── Button inline style ───────────────────────────────────────
			$button_style = 'background-color:' . esc_attr( $button_bg ) . ';color:' . esc_attr( $button_color ) . ';';

			// ── Build HTML ────────────────────────────────────────────────
			$wrapper_class = trim( 'true-random-post-widget ' . sanitize_html_class( $atts['class'] ) );

			ob_start();
			?>
			<div class="<?php echo esc_attr( $wrapper_class ); ?>">

				<?php if ( $image_html ) : ?>
				<div class="true-random-post-widget__image">
					<?php echo $image_html; // already escaped by WP functions ?>
				</div>
				<?php endif; ?>

				<div class="true-random-post-widget__body">
					<h3 class="true-random-post-widget__title">
						<?php echo esc_html( get_the_title( $post ) ); ?>
					</h3>
					<?php if ( $excerpt ) : ?>
					<p class="true-random-post-widget__excerpt">
						<?php echo $excerpt; // wp_kses_post / esc_html applied above ?>
					</p>
					<?php endif; ?>
				</div>

				<div class="true-random-post-widget__footer">
					<div class="true-random-post-widget__term">
						<?php if ( $term_logo ) : ?>
						<span class="true-random-post-widget__term-logo">
							<?php echo $term_logo; // wp_get_attachment_image output ?>
						</span>
						<?php endif; ?>
						<?php if ( $term_name ) : ?>
						<span class="true-random-post-widget__term-name">
							<?php echo esc_html( $term_name ); ?>
						</span>
						<?php endif; ?>
					</div>

					<a href="<?php echo esc_url( get_permalink( $post ) ); ?>"
						class="true-random-post-widget__button"
						style="<?php echo esc_attr( $button_style ); ?>">
						<?php echo esc_html( $button_text ); ?>
					</a>
				</div>

			</div>
			<?php
			return ob_get_clean();
		}

		/* ---------------------------------------------------------------
		 * Helpers
		 * ------------------------------------------------------------- */

		/**
		 * Return an error message visible only to editors/admins.
		 *
		 * @param string $message Error message text.
		 * @return string HTML or empty string.
		 */
		public static function error_message( $message ) {
			if ( current_user_can( 'edit_posts' ) ) {
				return '<div class="notice notice-error"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
			}
			return '';
		}
	}

	add_action( 'plugins_loaded', array( 'TrueRandomPostWidget', 'init' ) );

}
