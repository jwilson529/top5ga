<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Top5ga
 * @subpackage Top5ga/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Top5ga
 * @subpackage Top5ga/public
 * @author     James Wilson <james@oneclickcontent.com>
 */
class Top5ga_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $top5ga    The ID of this plugin.
	 */
	private $top5ga;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $top5ga       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $top5ga, $version ) {

		$this->top5ga  = $top5ga;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Top5ga_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Top5ga_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->top5ga, plugin_dir_url( __FILE__ ) . 'css/top5ga-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Top5ga_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Top5ga_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->top5ga, plugin_dir_url( __FILE__ ) . 'js/top5ga-public.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Register shortcodes.
	 */
	public function register_shortcodes() {
		add_shortcode( 'top_ga_posts', array( $this, 'top_ga_posts_shortcode' ) );
	}

	/**
	 * Shortcode callback for displaying top posts based on GA views.
	 *
	 * Usage: [top_ga_posts limit="5" post_type="post"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function top_ga_posts_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'     => 5,
				'post_type' => 'post',
			),
			$atts,
			'top_ga_posts'
		);

		$query_args = array(
			'post_type'      => $atts['post_type'],
			'meta_key'       => '_ga_page_views',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'posts_per_page' => intval( $atts['limit'] ),
		);

		$query = new WP_Query( $query_args );
		if ( $query->have_posts() ) {
			$output = '<ul>';
			while ( $query->have_posts() ) {
				$query->the_post();
				$views   = get_post_meta( get_the_ID(), '_ga_page_views', true );
				$output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a> (' . esc_html( $views ) . ' views)</li>';
			}
			$output .= '</ul>';
			wp_reset_postdata();
		} else {
			$output = '<p>No posts found.</p>';
		}

		return $output;
	}
}
