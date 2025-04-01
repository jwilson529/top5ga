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
	 * @param      string    $top5ga       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $top5ga, $version ) {

		$this->top5ga = $top5ga;
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

}
