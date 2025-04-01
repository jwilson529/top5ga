=== Top 5 GA ===
Contributors: jwilson529
Donate link: https://oneclickcontent.com/donate/
Tags: google analytics, ga4, analytics, oauth, shortcode, post meta
Requires at least: 5.0
Tested up to: 6.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Top 5 GA is a headstart plugin that integrates Google Analytics (GA4) with WordPress. It retrieves GA data, maps view counts to posts by saving them as post meta, and provides a shortcode to display the top posts based on GA view counts. This plugin is not production ready but serves as a starting point for further development.

== Installation ==
1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > Top 5 GA** to configure the plugin.
4. Enter your Google OAuth credentials (Client ID and Client Secret) as described below.

== Google Cloud Setup ==
To use Top 5 GA, you need to obtain OAuth credentials from Google Cloud:
1. Visit [Google Cloud Console](https://console.cloud.google.com).
2. Create a new project or select an existing one.
3. Go to **APIs & Services > Dashboard** and click **Enable APIs and Services**.
4. Enable the following APIs:
   - **Google Analytics API**
   - **Google Analytics Data API**
5. Navigate to **APIs & Services > Credentials**.
6. Click **Create Credentials** > **OAuth client ID**.
7. Select **Web Application** as the application type.
8. Under **Authorized redirect URIs**, add:
https://yourdomain.com/wp-admin/options-general.php?page=top5ga-settings
Replace `yourdomain.com` with your actual domain.
9. Click **Create**. You will be provided with a **Client ID** and **Client Secret**.
10. Enter these credentials on the plugin’s settings page.

== Usage ==
1. After connecting your Google Analytics account via OAuth, the plugin will retrieve your GA data.
2. GA view counts are mapped to your posts by updating post meta (the meta key is `_ga_page_views`) using a scheduled cron job.
3. To display the top posts based on GA data on the front-end, use the shortcode:
[top_ga_posts limit=“5” post_type=“post”]
- The `limit` attribute determines how many posts are displayed.
- The `post_type` attribute lets you specify which post type to query.

== Frequently Asked Questions ==
= Why do I see a permission error? =
Ensure that the GA **property ID** is used in the plugin settings and that your OAuth credentials have the required scopes.

= How often is the GA data updated? =
GA data is updated hourly via a cron job. You can adjust the cron schedule in the code if needed.

== Changelog ==
= 1.0.0 =
* Initial release of Top 5 GA. Features include OAuth integration, GA data retrieval, post meta mapping, and a shortcode to display top posts.

== Upgrade Notice ==
= 1.0.0 =
Initial release.