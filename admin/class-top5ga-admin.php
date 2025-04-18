<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * This class defines methods to enqueue the admin-specific
 * stylesheet and JavaScript, handle OAuth token refresh and disconnect,
 * and update WordPress posts with GA view counts.
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 * @package    Top5ga
 * @subpackage Top5ga/admin
 * @author     James Wilson
 */
class Top5ga_Admin {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $top5ga;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $top5ga  The plugin slug or name.
	 * @param string $version Plugin version.
	 */
	public function __construct( $top5ga, $version ) {
		$this->top5ga  = $top5ga;
		$this->version = $version;
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->top5ga,
			plugin_dir_url( __FILE__ ) . 'css/top5ga-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->top5ga,
			plugin_dir_url( __FILE__ ) . 'js/top5ga-admin.js',
			array( 'jquery' ),
			$this->version,
			false
		);
	}

	/**
	 * Handle the disconnect action.
	 *
	 * Unsets OAuth data from the settings and sends a JSON success response.
	 *
	 * @return void
	 */
	public function top5ga_disconnect_callback() {
		$options = get_option( 'top5ga_settings' );
		unset( $options['access_token'], $options['refresh_token'], $options['expires_at'], $options['email'] );
		update_option( 'top5ga_settings', $options );
		wp_send_json_success();
	}

	/**
	 * Refresh the OAuth access token if expired.
	 *
	 * Retrieves a new access token using the refresh token stored in the settings.
	 *
	 * @return void
	 */
	public function top5ga_refresh_token() {
		$options = get_option( 'top5ga_settings' );
		if ( empty( $options['refresh_token'] ) ) {
			error_log( 'Top5ga: No refresh token available for token refresh.' );
			return;
		}

		// Refresh the token if it has expired.
		if ( time() >= $options['expires_at'] ) {
			$response = wp_remote_post(
				'https://oauth2.googleapis.com/token',
				array(
					'body' => array(
						'client_id'     => $options['client_id'],
						'client_secret' => $options['client_secret'],
						'refresh_token' => $options['refresh_token'],
						'grant_type'    => 'refresh_token',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				error_log( 'Top5ga: Refresh token request failed: ' . $response->get_error_message() );
				return;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $data['access_token'] ) ) {
				$options['access_token'] = $data['access_token'];
				$options['expires_at']   = time() + intval( $data['expires_in'] );
				update_option( 'top5ga_settings', $options );
				error_log( 'Top5ga: Access token refreshed successfully.' );
			} else {
				$error_message = ! empty( $data['error'] ) ? $data['error'] . ': ' . $data['error_description'] : 'Unknown error';
				error_log( 'Top5ga: Failed to refresh access token. Google response: ' . $error_message );
			}
		}
	}

	/**
	 * Cron callback to update post meta with GA view counts.
	 *
	 * Retrieves the top pages from GA and updates matching WordPress posts
	 * with the view counts.
	 *
	 * @return void
	 */
	public function top5ga_update_post_views_cron() {
		$options = get_option( 'top5ga_settings' );
		if ( empty( $options['access_token'] ) ) {
			error_log( 'Top5ga: No access token available for updating post views.' );
			return;
		}

		if ( empty( $options['property_id'] ) ) {
			error_log( 'Top5ga: No property ID available for updating post views.' );
			return;
		}

		$analytics   = new Top5ga_Analytics( $this->top5ga, $this->version );
		$property_id = $options['property_id'];

		$this->top5ga_update_post_views( $analytics, $property_id, 100, 'post' );
	}

	/**
	 * Update WordPress posts with GA view counts.
	 *
	 * This method fetches GA data (top pages), then maps each GA page
	 * path to a corresponding WordPress post (by slug) and updates its metadata.
	 *
	 * @param Top5ga_Analytics $analytics   The analytics instance.
	 * @param string           $property_id The GA4 property ID.
	 * @param int              $ga_limit    Number of GA pages to fetch. Default 100.
	 * @param string           $post_type   The post type to map. Default 'post'.
	 * @return void
	 */
	public function top5ga_update_post_views( $analytics, $property_id, $ga_limit = 100, $post_type = 'post' ) {
		$ga_data = $analytics->get_top_pages( $property_id, $ga_limit );
		if ( ! $ga_data || empty( $ga_data ) ) {
			error_log( 'Top5ga: No GA data found for updating post views.' );
			return;
		}

		foreach ( $ga_data as $page ) {
			$path  = trim( $page['path'], '/' );
			$parts = explode( '/', $path );
			$slug  = end( $parts );
			$post  = get_page_by_path( $slug, OBJECT, $post_type );
			if ( $post ) {
				update_post_meta( $post->ID, '_ga_page_views', $page['pageviews'] );
				error_log( "Top5ga: Updated post ID {$post->ID} with {$page['pageviews']} views." );
			} else {
				error_log( "Top5ga: No matching post found for slug '{$slug}'." );
			}
		}
	}
}