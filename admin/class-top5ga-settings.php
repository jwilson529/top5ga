<?php
/**
 * The settings functionality of the plugin.
 *
 * This class defines the settings page where developers can
 * enter their Google OAuth credentials and start the OAuth flow.
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Top5ga
 * @subpackage Top5ga/admin
 */
class Top5ga_Settings {

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
	 * @var      string    $version    The current version of the plugin.
	 */
	private $version;

	/**
	 * The option name for storing settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $option_name    Option name.
	 */
	private $option_name = 'top5ga_settings';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string $top5ga     The name/ID of this plugin.
	 * @param    string $version    The version of this plugin.
	 */
	public function __construct( $top5ga, $version ) {
		$this->top5ga  = $top5ga;
		$this->version = $version;
	}

	/**
	 * Add settings page to the WordPress admin.
	 *
	 * @since    1.0.0
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Top 5 GA Settings', 'top5ga' ),
			__( 'Top 5 GA', 'top5ga' ),
			'manage_options',
			'top5ga-settings',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Register the plugin settings.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		register_setting(
			'top5ga_settings_group',  // Option group.
			$this->option_name,       // Option name.
			array( $this, 'sanitize_settings' ) // Sanitize callback.
		);

		add_settings_section(
			'top5ga_oauth_section',
			__( 'Google OAuth Settings', 'top5ga' ),
			array( $this, 'print_section_info' ),
			'top5ga-settings'
		);

		add_settings_field(
			'client_id',
			__( 'Client ID', 'top5ga' ),
			array( $this, 'client_id_callback' ),
			'top5ga-settings',
			'top5ga_oauth_section'
		);

		add_settings_field(
			'client_secret',
			__( 'Client Secret', 'top5ga' ),
			array( $this, 'client_secret_callback' ),
			'top5ga-settings',
			'top5ga_oauth_section'
		);
	}

	/**
	 * Sanitize and validate settings input.
	 *
	 * @since    1.0.0
	 * @param    array $input    The input data.
	 * @return   array              Sanitized data.
	 */
	public function sanitize_settings( $input ) {
		// Start with the current options to preserve fields not in the input
		$current_options = get_option( 'top5ga_settings', array() );
		$sanitized       = $current_options;

		// Update fields present in the input
		if ( isset( $input['client_id'] ) ) {
			$sanitized['client_id'] = sanitize_text_field( $input['client_id'] );
		}
		if ( isset( $input['client_secret'] ) ) {
			$sanitized['client_secret'] = sanitize_text_field( $input['client_secret'] );
		}
		if ( isset( $input['access_token'] ) ) {
			$sanitized['access_token'] = sanitize_text_field( $input['access_token'] );
		}
		if ( isset( $input['refresh_token'] ) ) {
			$sanitized['refresh_token'] = sanitize_text_field( $input['refresh_token'] );
		}
		if ( isset( $input['expires_at'] ) ) {
			$sanitized['expires_at'] = intval( $input['expires_at'] );
		}
		if ( isset( $input['email'] ) ) {
			$sanitized['email'] = sanitize_email( $input['email'] );
		}
		// Include view_id if your form submits it
		if ( isset( $input['view_id'] ) ) {
			$sanitized['view_id'] = sanitize_text_field( $input['view_id'] );
		}

		if ( isset( $input['property_id'] ) ) {
			$sanitized['property_id'] = sanitize_text_field( $input['property_id'] );
		}

		return $sanitized;
	}

	/**
	 * Print the section text.
	 *
	 * @since    1.0.0
	 */
	public function print_section_info() {
		echo '<p>' . esc_html__( 'Enter your Google OAuth credentials to connect your Google Analytics account.', 'top5ga' ) . '</p>';
	}

	/**
	 * Render the Client ID field.
	 *
	 * @since    1.0.0
	 */
	public function client_id_callback() {
		$options   = get_option( $this->option_name );
		$client_id = isset( $options['client_id'] ) ? esc_attr( $options['client_id'] ) : '';
		echo '<input type="text" id="client_id" name="' . esc_attr( $this->option_name ) . '[client_id]" value="' . $client_id . '" class="regular-text" />';
	}

	/**
	 * Render the Client Secret field.
	 *
	 * @since    1.0.0
	 */
	public function client_secret_callback() {
		$options       = get_option( $this->option_name );
		$client_secret = isset( $options['client_secret'] ) ? esc_attr( $options['client_secret'] ) : '';
		echo '<input type="text" id="client_secret" name="' . esc_attr( $this->option_name ) . '[client_secret]" value="' . $client_secret . '" class="regular-text" />';
	}

	/**
	 * Display the settings page.
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		$analytics = new Top5ga_Analytics( $this->top5ga, $this->version );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Top 5 GA Settings', 'top5ga' ); ?></h1>
			<?php
			if ( isset( $_GET['oauth'] ) && $_GET['oauth'] === 'success' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' .
					esc_html__( 'Successfully connected to Google Analytics!', 'top5ga' ) .
					'</p></div>';
			}
			?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'top5ga_settings_group' );
				do_settings_sections( 'top5ga-settings' );
				$this->display_analytics_selection( $analytics );
				submit_button();
				?>
			</form>
			<?php
			$this->display_oauth_section();
			$this->display_top_pages( $analytics );
			// Now show the worst performing posts table
			$this->display_worst_posts( $analytics );
			?>
		</div>
		<?php
	}

	/**
	 * Display dropdowns for selecting Analytics account, property, and view.
	 *
	 * @param Top5ga_Analytics $analytics The analytics handler instance.
	 */
	private function display_analytics_selection( $analytics ) {
		$options           = get_option( $this->option_name );
		$analytics_options = $analytics->get_analytics_options();

		if ( ! $analytics_options ) {
			if ( ! empty( $options['access_token'] ) ) {
				echo '<p>' . esc_html__( 'Failed to fetch Analytics options. Check logs for details.', 'top5ga' ) . '</p>';
			}
			return;
		}

		$selected_view_id = isset( $options['view_id'] ) ? $options['view_id'] : '';
		?>
			<h2><?php esc_html_e( 'Analytics Selection', 'top5ga' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="analytics_property"><?php esc_html_e( 'Analytics Property', 'top5ga' ); ?></label></th>
					<td>
					<?php
					$options              = get_option( $this->option_name );
					$analytics_options    = $analytics->get_analytics_options();
					$selected_property_id = isset( $options['property_id'] ) ? $options['property_id'] : '';
					?>
						<select name="<?php echo esc_attr( $this->option_name ); ?>[property_id]" id="analytics_property">
							<option value=""><?php esc_html_e( 'Select a property', 'top5ga' ); ?></option>
						<?php
						foreach ( $analytics_options as $account_id => $account ) {
							foreach ( $account['properties'] as $property_id => $property ) {
								$label = "{$account['name']} > {$property['name']}";
								echo '<option value="' . esc_attr( $property_id ) . '" ' . selected( $selected_property_id, $property_id, false ) . '>' . esc_html( $label ) . '</option>';
							}
						}
						?>
						</select>
						<p class="description"><?php esc_html_e( 'Select a GA4 property. The data stream will be fetched automatically.', 'top5ga' ); ?></p>
					</td>
				</tr>
			</table>
			<?php
	}

	/**
	 * Display the top 10 pages from Analytics.
	 *
	 * @param Top5ga_Analytics $analytics The analytics handler instance.
	 */
	private function display_top_pages( $analytics ) {
		$options = get_option( $this->option_name );
		if ( empty( $options['property_id'] ) ) {
			echo '<p>' . esc_html__( 'Please select an Analytics property and save settings to see top pages.', 'top5ga' ) . '</p>';
			return;
		}

		$top_pages = $analytics->get_top_pages( $options['property_id'] );
		if ( is_null( $top_pages ) ) {
			echo '<p>' . esc_html__( 'Failed to fetch top pages. Check logs for details.', 'top5ga' ) . '</p>';
			return;
		}

		if ( empty( $top_pages ) ) {
			echo '<p>' . esc_html__( 'No page data available for the selected property in the last 30 days.', 'top5ga' ) . '</p>';
			return;
		}
		?>
			<h2><?php esc_html_e( 'Top 10 Pages/Posts (Last 30 Days)', 'top5ga' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Page Path', 'top5ga' ); ?></th>
						<th><?php esc_html_e( 'Pageviews', 'top5ga' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $top_pages as $page ) : ?>
						<tr>
							<td><?php echo esc_html( $page['path'] ); ?></td>
							<td><?php echo esc_html( $page['pageviews'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
	}

	/**
	 * Display the worst performing posts in a table.
	 *
	 * This method fetches the worst pages (sorted in ascending order of pageviews),
	 * maps each GA page path to a WordPress post (using the slug), and displays a table
	 * with the post title, GA page path, pageviews, and an edit link if available.
	 *
	 * @param Top5ga_Analytics $analytics The analytics handler instance.
	 */
	private function display_worst_posts( $analytics ) {
		$options = get_option( $this->option_name );
		if ( empty( $options['property_id'] ) ) {
			echo '<p>' . esc_html__( 'Please select an Analytics property and save settings to see worst performing posts.', 'top5ga' ) . '</p>';
			return;
		}

		$worst_pages = $analytics->get_worst_pages( $options['property_id'], 10 );
		if ( empty( $worst_pages ) ) {
			echo '<p>' . esc_html__( 'No GA data available for worst performing posts.', 'top5ga' ) . '</p>';
			return;
		}

		echo '<h2>' . esc_html__( 'Worst Performing Posts (Last 30 Days)', 'top5ga' ) . '</h2>';
		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'WordPress Post', 'top5ga' ) . '</th>';
		echo '<th>' . esc_html__( 'GA Page Path', 'top5ga' ) . '</th>';
		echo '<th>' . esc_html__( 'Pageviews', 'top5ga' ) . '</th>';
		echo '<th>' . esc_html__( 'Edit Link', 'top5ga' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $worst_pages as $page ) {
			// Normalize the path and assume the last segment is the post slug.
			$path  = trim( $page['path'], '/' );
			$parts = explode( '/', $path );
			$slug  = end( $parts );
			$post  = get_page_by_path( $slug, OBJECT, 'post' );

			if ( $post ) {
				$post_title = get_the_title( $post->ID );
				$edit_link  = get_edit_post_link( $post->ID );
			} else {
				$post_title = esc_html__( 'No matching post', 'top5ga' );
				$edit_link  = '';
			}

			echo '<tr>';
			echo '<td>' . esc_html( $post_title ) . '</td>';
			echo '<td>' . esc_html( $page['path'] ) . '</td>';
			echo '<td>' . esc_html( $page['pageviews'] ) . '</td>';
			echo '<td>';
			if ( $edit_link ) {
				echo '<a href="' . esc_url( $edit_link ) . '" target="_blank">' . esc_html__( 'Edit', 'top5ga' ) . '</a>';
			} else {
				echo esc_html__( 'N/A', 'top5ga' );
			}
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
	}

	/**
	 * Display the OAuth connection section.
	 *
	 * This section shows a connect button if the credentials are present but no
	 * access token has been stored. If connected, it shows the connected account
	 * and a disconnect button.
	 *
	 * @since 1.0.0
	 */
	public function display_oauth_section() {
		// Clear cache and get fresh options
		wp_cache_delete( 'top5ga_settings', 'options' );
		$options = get_option( $this->option_name );
		error_log( 'Top5ga: Options in display_oauth_section: ' . print_r( $options, true ) ); // Debug

		if ( ! empty( $options['access_token'] ) ) {
			echo '<h2>' . esc_html__( 'Google Analytics Connected', 'top5ga' ) . '</h2>';
			echo '<p>' . esc_html__( 'Connected as ', 'top5ga' ) .
				'<strong>' . esc_html( $options['email'] ) . '</strong></p>';
			echo '<button class="button button-secondary" id="disconnect-oauth">' .
				esc_html__( 'Disconnect', 'top5ga' ) . '</button>';
			?>
			<script>
			jQuery(document).ready(function($) {
				$('#disconnect-oauth').on('click', function(e) {
					e.preventDefault();
					if (confirm('Are you sure you want to disconnect?')) {
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'top5ga_disconnect'
							},
							success: function(response) {
								if (response.success) {
									location.reload();
								} else {
									alert('Disconnect failed');
								}
							}
						});
					}
				});
			});
			</script>
			<?php
		} elseif ( ! empty( $options['client_id'] ) && ! empty( $options['client_secret'] ) ) {
			$auth_url = $this->get_oauth_url( $options['client_id'] );
			error_log( 'Top5ga: Generated OAuth URL: ' . $auth_url );
			echo '<h2>' . esc_html__( 'Connect to Google Analytics', 'top5ga' ) . '</h2>';
			echo '<p>' . esc_html__( 'Click the button below to authorize access to your Google Analytics account.', 'top5ga' ) . '</p>';
			echo '<a class="button button-primary" href="' . esc_url( $auth_url ) . '">' . esc_html__( 'Connect to Google Analytics', 'top5ga' ) . '</a>';
		} else {
			echo '<p>' . esc_html__( 'Please enter your Client ID and Client Secret and save the settings first.', 'top5ga' ) . '</p>';
		}
	}

	public function process_oauth_callback() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'top5ga-settings' || ! isset( $_GET['code'] ) ) {
			return;
		}

		$code    = sanitize_text_field( $_GET['code'] );
		$options = get_option( $this->option_name );
		$redirect_uri = admin_url( 'options-general.php?page=top5ga-settings' );

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'body' => array(
					'code'          => $code,
					'client_id'     => $options['client_id'],
					'client_secret' => $options['client_secret'],
					'redirect_uri'  => $redirect_uri,
					'grant_type'    => 'authorization_code',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			add_settings_error( 'top5ga_settings', 'oauth_error', __( 'OAuth failed: ' . $response->get_error_message(), 'top5ga' ), 'error' );
			return;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		error_log( 'Top5ga: Token exchange response: ' . print_r( $data, true ) );

		if ( ! empty( $data['access_token'] ) ) {
			$user_info = wp_remote_get(
			    'https://www.googleapis.com/oauth2/v3/userinfo',
			    array(
			        'headers' => array( 'Authorization' => 'Bearer ' . $data['access_token'] ),
			    )
			);

			if ( is_wp_error( $user_info ) ) {
			    error_log( 'Top5ga: User info request failed: ' . $user_info->get_error_message() );
			} else {
			    $user_data = json_decode( wp_remote_retrieve_body( $user_info ), true );
			    error_log( 'Top5ga: Raw user info response: ' . print_r( $user_data, true ) );
			}

			$email = 'Unknown';
			if ( ! is_wp_error( $user_info ) ) {
				$user_data = json_decode( wp_remote_retrieve_body( $user_info ), true );
				$email     = ! empty( $user_data['email'] ) ? $user_data['email'] : 'Unknown';
			}

			$google_oauth = array(
				'client_id'     => $options['client_id'],
				'client_secret' => $options['client_secret'],
				'access_token'  => $data['access_token'],
				'refresh_token' => ! empty( $data['refresh_token'] ) ? $data['refresh_token'] : '',
				'expires_at'    => time() + intval( $data['expires_in'] ),
				'email'         => $email,
			);

			// Force save by deleting and adding
			delete_option( $this->option_name ); // Clear existing option
			$added = add_option( $this->option_name, $google_oauth ); // Add fresh
			if ( ! $added ) {
				error_log( 'Top5ga: Failed to add OAuth options: ' . print_r( $google_oauth, true ) );
				update_option( $this->option_name, $google_oauth ); // Fallback to update
			}
			error_log( 'Top5ga: OAuth options saved attempt: ' . print_r( $google_oauth, true ) );

			// Verify save
			wp_cache_delete( 'top5ga_settings', 'options' );
			$saved_options = get_option( $this->option_name );
			error_log( 'Top5ga: Verified saved options: ' . print_r( $saved_options, true ) );

			if ( empty( $saved_options['access_token'] ) ) {
				error_log( 'Top5ga: Save verification failed - no access token found');
			}

			wp_redirect( admin_url( 'options-general.php?page=top5ga-settings&oauth=success&nocache=' . time() ) );
			exit;
		} else {
			$error_message = ! empty( $data['error'] ) ? $data['error'] . ': ' . $data['error_description'] : 'No access token returned';
			add_settings_error( 'top5ga_settings', 'oauth_error', __( 'Failed to retrieve access token. Google response: ' . $error_message, 'top5ga' ), 'error' );
		}
	}

	/**
	 * Generate the OAuth URL.
	 *
	 * @since    1.0.0
	 * @param    string $client_id   The Google Client ID.
	 * @return   string                OAuth URL.
	 */
	private function get_oauth_url( $client_id ) {
	    // Dynamically get the local admin URL.
	    $redirect_uri = admin_url( 'options-general.php?page=top5ga-settings' );
	    
	    $params = array(
	        'response_type' => 'code',
	        'client_id'     => $client_id,
	        'redirect_uri'  => $redirect_uri,
	        'scope'         => 'https://www.googleapis.com/auth/analytics.readonly',
	        'access_type'   => 'offline',
	        'prompt'        => 'consent',
	    );
	    return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query( $params );
	}
}