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
     * If a specific property ID is selected in the settings, only fetch
     * that property's data streams. Otherwise, fetch all accounts and properties.
     *
     * @return array|null Array of analytics options, or null on failure.
     */
    public function get_analytics_options() {
        $options = get_option( 'top5ga_settings' );
        if ( empty( $options['access_token'] ) ) {
            error_log( 'Top5ga: No access token available for GA4 options fetch.' );
            return null;
        }

        // Ensure token is refreshed if expired.
        $admin = new Top5ga_Admin( $this->top5ga, $this->version );
        $admin->top5ga_refresh_token();

        // Reload settings after potential refresh.
        $options = get_option( 'top5ga_settings' );
        error_log( 'Top5ga: Using access token: ' . substr( $options['access_token'], 0, 10 ) . ' (expires at ' . $options['expires_at'] . ')' );

        // If a specific property is already selected, only fetch the details for that property.
        if ( ! empty( $options['property_id'] ) ) {
            $analytics_options = array();

            // Example: Fetching a specific property details. You might need to
            // adjust the endpoint or use stored account details if required.
            $property_id = $options['property_id'];
            $streams_response = wp_remote_get(
                "https://analyticsadmin.googleapis.com/v1beta/properties/{$property_id}/dataStreams",
                array(
                    'headers' => array( 'Authorization' => 'Bearer ' . $options['access_token'] ),
                )
            );

            if ( is_wp_error( $streams_response ) ) {
                error_log( 'Top5ga: Failed to fetch data streams for property ' . $property_id . ': ' . $streams_response->get_error_message() );
                return null;
            }

            $streams_data = json_decode( wp_remote_retrieve_body( $streams_response ), true );
            $analytics_options[ $property_id ] = array(
                'name'  => 'Selected Property', // You could store a display name in the options.
                'views' => array(), // Data streams.
            );

            if ( ! empty( $streams_data['dataStreams'] ) ) {
                foreach ( $streams_data['dataStreams'] as $stream ) {
                    $stream_id   = str_replace( "properties/{$property_id}/dataStreams/", '', $stream['name'] );
                    $stream_name = $stream['displayName'];
                    $analytics_options[ $property_id ]['views'][ $stream_id ] = $stream_name;
                }
            }
            return $analytics_options;
        }

        // If no specific property is selected, fallback to full account query.
        $accounts_response = wp_remote_get(
            'https://analyticsadmin.googleapis.com/v1beta/accounts',
            array(
                'headers' => array( 'Authorization' => 'Bearer ' . $options['access_token'] ),
            )
        );

        if ( is_wp_error( $accounts_response ) ) {
            error_log( 'Top5ga: Failed to fetch GA4 accounts: ' . $accounts_response->get_error_message() );
            return null;
        }

        $accounts_data = json_decode( wp_remote_retrieve_body( $accounts_response ), true );
        error_log( 'Top5ga: GA4 Accounts API response: ' . print_r( $accounts_data, true ) );

        if ( empty( $accounts_data['accounts'] ) ) {
            error_log( 'Top5ga: No GA4 accounts found in API response.' );
            return null;
        }

        $analytics_options = array();
        foreach ( $accounts_data['accounts'] as $account ) {
            $account_name         = $account['name'];
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
                error_log( 'Top5ga: Failed to fetch properties for account ' . $account_id . ': ' . $properties_response->get_error_message() );
                continue;
            }

            $properties_data = json_decode( wp_remote_retrieve_body( $properties_response ), true );
            error_log( 'Top5ga: Properties API response for account ' . $account_id . ': ' . print_r( $properties_data, true ) );

            if ( empty( $properties_data['properties'] ) ) {
                error_log( 'Top5ga: No properties found for account ' . $account_id );
                continue;
            }

            foreach ( $properties_data['properties'] as $property ) {
                $property_id   = str_replace( 'properties/', '', $property['name'] );
                $property_name = $property['displayName'];

                $analytics_options[ $account_id ]['properties'][ $property_id ] = array(
                    'name'  => $property_name,
                    'views' => array(),
                );

                $streams_response = wp_remote_get(
                    "https://analyticsadmin.googleapis.com/v1beta/properties/{$property_id}/dataStreams",
                    array(
                        'headers' => array( 'Authorization' => 'Bearer ' . $options['access_token'] ),
                    )
                );

                if ( is_wp_error( $streams_response ) ) {
                    error_log( 'Top5ga: Failed to fetch data streams for property ' . $property_id . ': ' . $streams_response->get_error_message() );
                    continue;
                }

                $streams_data = json_decode( wp_remote_retrieve_body( $streams_response ), true );
                error_log( 'Top5ga: Data Streams API response for property ' . $property_id . ': ' . print_r( $streams_data, true ) );

                if ( ! empty( $streams_data['dataStreams'] ) ) {
                    foreach ( $streams_data['dataStreams'] as $stream ) {
                        $stream_id   = str_replace( "properties/{$property_id}/dataStreams/", '', $stream['name'] );
                        $stream_name = $stream['displayName'];
                        $analytics_options[ $account_id ]['properties'][ $property_id ]['views'][ $stream_id ] = $stream_name;
                    }
                }
            }
        }

        error_log( 'Top5ga: Final GA4 analytics options: ' . print_r( $analytics_options, true ) );
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
            error_log( 'Top5ga: No access token available for GA4 report.' );
            return null;
        }
        
        // Optional: Ensure token is refreshed if expired before making the API request.
        $admin = new Top5ga_Admin($this->top5ga, $this->version);
        $admin->top5ga_refresh_token();
        
        // Reload settings after potential refresh.
        $options = get_option( $this->option_name );
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
            error_log( 'Top5ga: Failed to fetch top pages: ' . wp_remote_retrieve_body( $response ) );
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

    /**
     * Get worst performing pages from GA4 property (posts with lowest page views).
     *
     * @param string $property_id The GA4 property ID.
     * @param int    $limit       Number of results to fetch.
     * @return array|null Array of worst performing pages or null on failure.
     */
    public function get_worst_pages( $property_id, $limit = 10 ) {
        $options = get_option( $this->option_name );
        if ( empty( $options['access_token'] ) ) {
            error_log( 'Top5ga: No access token available for GA4 report.' );
            return null;
        }

        // Query GA4 API: ordering by page views in ascending order.
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
                        // Order in ascending order to fetch worst performing pages first.
                        'orderBys'   => array(
                            array(
                                'metric' => array( 'metricName' => 'screenPageViews' ),
                                'desc'   => false,
                            ),
                        ),
                        'limit'      => $limit,
                    )
                ),
            )
        );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            error_log( 'Top5ga: Failed to fetch worst pages: ' . wp_remote_retrieve_body( $response ) );
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['rows'] ) ) {
            return array();
        }

        $worst_pages = array();
        foreach ( $body['rows'] as $row ) {
            $worst_pages[] = array(
                'path'      => $row['dimensionValues'][0]['value'],
                'pageviews' => $row['metricValues'][0]['value'],
            );
        }
        return $worst_pages;
    }
}