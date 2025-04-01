<?php

/**
 * Fired during plugin activation
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Top5ga
 * @subpackage Top5ga/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Top5ga
 * @subpackage Top5ga/includes
 * @author     James Wilson <james@oneclickcontent.com>
 */
class Top5ga_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
	    // Schedule the token refresh event if it's not already scheduled.
	    if ( ! wp_next_scheduled( 'top5ga_refresh_token_event' ) ) {
	        wp_schedule_event( time(), 'hourly', 'top5ga_refresh_token_event' );
	    }

	    // Schedule the GA post views update event if it's not already scheduled.
	    if ( ! wp_next_scheduled( 'top5ga_update_post_views_event' ) ) {
	        wp_schedule_event( time(), 'hourly', 'top5ga_update_post_views_event' );
	    }
	}

}
