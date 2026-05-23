<?php
/**
 * Plugin Name: True Random Post Widget
 * Description: Display a truly random post from your entire database on page refresh. Properly randomizes across all posts, not just recent ones.
 * Version: 1.0.0
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

		const SHORTCODE = 'true_random_post';

		const DEFAULT_ATTRIBUTES = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'show_title'     => true,
			'show_image'     => true,
			'show_excerpt'   => true,
			'image_size'     => 'large',
			'image_required' => false,
			'class'          => '',
		);

		/**
		 * Initialize the plugin.
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
			add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );
		}

		/**
		 * Load plugin textdomain.
		 */
		public static function load_textdomain() {
			load_plugin_textdomain( 'true-random-post-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Enqueue plugin styles.
		 */
		public static function enqueue_styles() {
			wp_register_style( self::SHORTCODE, plugins_url( '/assets/style.css', __FILE__ ), array(), '1.0.0' );
		}

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

			// Build WHERE clause for post types
			$post_types = is_array( $args['post_type'] ) ? $args['post_type'] : array( $args['post_type'] );
			$post_types = array_map( 'esc_sql', $post_types );
			$post_type_list = "'" . implode( "','", $post_types ) . "'";

			// Build the query to get total count of posts
			$count_query = $wpdb->prepare(
				"SELECT COUNT(*) as total FROM {$wpdb->posts} 
				WHERE post_type IN ({$post_type_list}) 
				AND post_status = %s",
				$args['post_status']
			);

			// Add featured image requirement to count if needed
			if ( $args['image_required'] ) {
				$count_query = $wpdb->prepare(
					"SELECT COUNT(DISTINCT p.ID) as total 
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
					WHERE p.post_type IN ({$post_type_list}) 
					AND p.post_status = %s 
					AND pm.meta_key = '_thumbnail_id'",
					$args['post_status']
				);
			}

			// Get total post count
			$total = $wpdb->get_var( $count_query );

			if ( ! $total ) {
				return false;
			}

			// Get a random offset
			$random_offset = wp_rand( 0, $total - 1 );

			// Query for the random post
			$random_post_query = $wpdb->prepare(
				"SELECT * FROM {$wpdb->posts} 
				WHERE post_type IN ({$post_type_list}) 
				AND post_status = %s 
				ORDER BY ID DESC 
				LIMIT %d, 1",
				$args['post_status'],
				$random_offset
			);

			// Modify query if featured image required
			if ( $args['image_required'] ) {
				$random_post_query = $wpdb->prepare(
					"SELECT p.* FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
					WHERE p.post_type IN ({$post_type_list}) 
					AND p.post_status = %s 
					AND pm.meta_key = '_thumbnail_id'
					ORDER BY p.ID DESC 
					LIMIT %d, 1",
					$args['post_status'],
					$random_offset
				);
			}

			$result = $wpdb->get_row( $random_post_query );

			if ( ! $result ) {
				return false;
			}

			return get_post( $result->ID );
		}

		/**
		 * Render the shortcode.
		 *
		 * @param array $atts Shortcode attributes.
		 * @return string HTML output or error message.
		 */
		public static function render_shortcode( $atts ) {
			wp_enqueue_style( self::SHORTCODE );

			$atts = shortcode_atts( self::DEFAULT_ATTRIBUTES, $atts, self::SHORTCODE );

			// Sanitize boolean values
			$show_title     = wp_validate_boolean( $atts['show_title'] );
			$show_image     = wp_validate_boolean( $atts['show_image'] );
			$show_excerpt   = wp_validate_boolean( $atts['show_excerpt'] );
			$image_required = wp_validate_boolean( $atts['image_required'] );

			// Check theme support for featured images
			if ( $show_image && ! current_theme_supports( 'post-thumbnails' ) ) {
				return self::error_message(
					__( 'Your theme does not support featured images. Set show_image="false" to disable.', 'true-random-post-widget' )
				);
			}

			// Get the random post
			$random_post = self::get_random_post( array(
				'post_type'      => sanitize_text_field( $atts['post_type'] ),
				'image_required' => $image_required,
			) );

			if ( ! $random_post ) {
				return self::error_message(
					__( 'No posts found in the database.', 'true-random-post-widget' )
				);
			}

			// Check if image is required and post has featured image
			if ( $show_image && $image_required && ! has_post_thumbnail( $random_post ) ) {
				return self::error_message(
					__( 'The selected random post does not have a featured image.', 'true-random-post-widget' )
				);
			}

			// Build the output
			$output = '<div class="true-random-post-widget ' . esc_attr( $atts['class'] ) . '">';
			$output .= '<a href="' . esc_url( get_permalink( $random_post ) ) . '" class="true-random-post-widget__link">';

			// Featured image
			if ( $show_image && has_post_thumbnail( $random_post ) ) {
				$output .= '<div class="true-random-post-widget__image">';
				$output .= get_the_post_thumbnail( $random_post, sanitize_text_field( $atts['image_size'] ) );
				$output .= '</div>';
			}

			// Title
			if ( $show_title ) {
				$output .= '<h3 class="true-random-post-widget__title">' . esc_html( get_the_title( $random_post ) ) . '</h3>';
			}

			// Excerpt
			if ( $show_excerpt ) {
				$excerpt = has_excerpt( $random_post ) ? $random_post->post_excerpt : wp_trim_words( $random_post->post_content, 20 );
				$output .= '<p class="true-random-post-widget__excerpt">' . wp_kses_post( $excerpt ) . '</p>';
			}

			$output .= '</a>';
			$output .= '</div>';

			return $output;
		}

		/**
		 * Display error message (only to admins).
		 *
		 * @param string $message The error message.
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
