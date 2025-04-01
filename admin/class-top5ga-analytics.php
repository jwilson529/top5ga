<?php
/**
 * The Google Analytics API functionality of the plugin (GA4 version).
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Top5ga
 * @subpackage Top5ga/admin
 */
class Top5ga_Analytics {

	private $top5ga;
	private $version;
	private $option_name = 'top5ga_settings';

	public function __construct( $top5ga, $version ) {
		$this->top5ga  = $top5ga;
		$this->version = $version;
	}

	/**
	 * Get GA4 properties and data streams.
	 *
	 * @return array|null Array of accounts with properties and data streams, or null on failure.
	 */
	public function get_analytics_options() {
		$options = get_option( $this->option_name );
		if ( empty( $options['access_token'] ) ) {
			// error_log( 'Top5ga: No access token available for GA4 options fetch.' );
			return null;
		}

		// error_log( 'Top5ga: Using access token: ' . substr( $options['access_token'], 0, 10 ) . '... (expires at ' . $options['expires_at'] . ')' );

		// Step 1: Fetch GA4 accounts
		$accounts_response = wp_remote_get(
			'https://analyticsadmin.googleapis.com/v1beta/accounts',
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $options['access_token'] ),
			)
		);

		if ( is_wp_error( $accounts_response ) ) {
			// error_log( 'Top5ga: Failed to fetch GA4 accounts: ' . $accounts_response->get_error_message() );
			return null;
		}

		$accounts_data = json_decode( wp_remote_retrieve_body( $accounts_response ), true );
		// error_log( 'Top5ga: GA4 Accounts API response: ' . print_r( $accounts_data, true ) );

		if ( empty( $accounts_data['accounts'] ) ) {
			// error_log( 'Top5ga: No GA4 accounts found in API response.' );
			return null;
		}

		$analytics_options = array();

		// Step 2: For each account, fetch properties
		foreach ( $accounts_data['accounts'] as $account ) {
			$account_name         = $account['name']; // e.g., "accounts/1234567"
			$account_id           = str_replace( 'accounts/', '', $account_name );
			$account_display_name = $account['displayName'];

			$analytics_options[ $account_id ] = array(
				'name'       => $account_display_name,
				'properties' => array(),
			);

			$properties_response = wp_remote_get(
				'https://analyticsadmin.googleapis.com/v1beta/properties?filter=parent:accounts/' . $account_id,
				array(
					'headers' => array( 'Authorization' => 'Bearer ' . $options['access_token'] ),
				)
			);

			if ( is_wp_error( $properties_response ) ) {
				// error_log( 'Top5ga: Failed to fetch properties for account ' . $account_id . ': ' . $properties_response->get_error_message() );
				continue;
			}

			$properties_data = json_decode( wp_remote_retrieve_body( $properties_response ), true );
			// error_log( 'Top5ga: Properties API response for account ' . $account_id . ': ' . print_r( $properties_data, true ) );

			if ( empty( $properties_data['properties'] ) ) {
				// error_log( 'Top5ga: No properties found for account ' . $account_id );
				continue;
			}

			// Step 3: Process properties and optionally fetch data streams
			foreach ( $properties_data['properties'] as $property ) {
				$property_id   = str_replace( 'properties/', '', $property['name'] );
				$property_name = $property['displayName'];

				$analytics_options[ $account_id ]['properties'][ $property_id ] = array(
					'name'  => $property_name,
					'views' => array(), // Data streams will go here
				);

				// Fetch data streams for this property
				$streams_response = wp_remote_get(
					"https://analyticsadmin.googleapis.com/v1beta/properties/{$property_id}/dataStreams",
					array(
						'headers' => array( 'Authorization' => 'Bearer ' . $options['access_token'] ),
					)
				);

				if ( is_wp_error( $streams_response ) ) {
					// error_log( 'Top5ga: Failed to fetch data streams for property ' . $property_id . ': ' . $streams_response->get_error_message() );
					continue;
				}

				$streams_data = json_decode( wp_remote_retrieve_body( $streams_response ), true );
				// error_log( 'Top5ga: Data Streams API response for property ' . $property_id . ': ' . print_r( $streams_data, true ) );

				if ( ! empty( $streams_data['dataStreams'] ) ) {
					foreach ( $streams_data['dataStreams'] as $stream ) {
						$stream_id   = str_replace( "properties/{$property_id}/dataStreams/", '', $stream['name'] );
						$stream_name = $stream['displayName'];
						$analytics_options[ $account_id ]['properties'][ $property_id ]['views'][ $stream_id ] = $stream_name;
					}
				}
			}
		}

		// error_log( 'Top5ga: Final GA4 analytics options: ' . print_r( $analytics_options, true ) );
		return $analytics_options;
	}

	/**
	 * Get top pages from GA4 property.
	 *
	 * @param string $property_id The GA4 property ID.
	 * @param int    $limit       Number of results to fetch.
	 * @return array|null Array of top pages or null on failure.
	 */
	public function get_top_pages( $property_id, $limit = 10 ) {
		$options = get_option( $this->option_name );
		if ( empty( $options['access_token'] ) ) {
			// error_log( 'Top5ga: No access token available for GA4 report.' );
			return null;
		}

		$response = wp_remote_post(
			"https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport",
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $options['access_token'],
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode(
					array(
						'dateRanges' => array(
							array(
								'startDate' => '30daysAgo',
								'endDate'   => 'today',
							),
						),
						'dimensions' => array(
							array( 'name' => 'pagePath' ),
						),
						'metrics'    => array(
							array( 'name' => 'screenPageViews' ),
						),
						'orderBys'   => array(
							array(
								'metric' => array( 'metricName' => 'screenPageViews' ),
								'desc'   => true,
							),
						),
						'limit'      => $limit,
					)
				),
			)
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			// error_log( 'Top5ga: Failed to fetch top pages: ' . wp_remote_retrieve_body( $response ) );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['rows'] ) ) {
			return array();
		}

		$top_pages = array();
		foreach ( $body['rows'] as $row ) {
			$top_pages[] = array(
				'path'      => $row['dimensionValues'][0]['value'],
				'pageviews' => $row['metricValues'][0]['value'],
			);
		}
		return $top_pages;
	}
}
