# Top 5 GA

Top 5 GA is a WordPress plugin that integrates Google Analytics (GA4) data with your WordPress site. It retrieves GA data, maps page view counts to posts (stored as post meta), and provides a shortcode to display the top posts based on GA metrics.

> **Note:** This plugin is a headstart and is not production-ready. It is intended as a starting point for developers to build upon.

## Features

- **OAuth Integration:** Connect your Google Analytics account via OAuth.
- **GA Data Retrieval:** Fetch GA accounts, properties, and detailed report data using the GA4 Data API.
- **Post Meta Mapping:** Automatically update posts with GA view counts via a cron job.
- **Shortcode:** Display the top posts based on GA data using the `[top_ga_posts]` shortcode.
- **Cron Jobs:** Scheduled tasks to refresh OAuth tokens and update post metadata.

## Installation

1. Clone or download the repository.
2. Upload the plugin folder to your `/wp-content/plugins/` directory.
3. Activate the plugin via the WordPress Admin panel.
4. Go to **Settings > Top 5 GA** to configure the plugin.

## Google Cloud Setup

To get started, you need to obtain OAuth credentials from Google Cloud:

1. **Visit [Google Cloud Console](https://console.cloud.google.com):**
   - Create a new project or select an existing one.
2. **Enable APIs:**
   - Navigate to **APIs & Services > Dashboard**.
   - Click **Enable APIs and Services** and enable:
     - **Google Analytics API**
     - **Google Analytics Data API**
3. **Create OAuth Credentials:**
   - Go to **APIs & Services > Credentials**.
   - Click **Create Credentials** > **OAuth client ID**.
   - Choose **Web Application** as the application type.
4. **Authorized Redirect URI:**
     ```
     https://yourdomain.com/wp-admin/options-general.php?page=top5ga-settings
     ```
     Replace `yourdomain.com` with your actual domain.
5. **Obtain Your Credentials:**
   - Click **Create** to generate your **Client ID** and **Client Secret**.
6. **Configure the Plugin:**
   - Enter the **Client ID** and **Client Secret** on the plugin’s settings page.

## Usage

### Updating GA Data
- The plugin uses cron jobs to automatically refresh the OAuth token and update post metadata with GA view counts hourly.
- GA data is fetched and mapped to posts by matching the post slug with the GA page path.

### Shortcode
Display the top posts on your site by adding this shortcode to a page or widget:

```html
[top_ga_posts limit="5" post_type="post"]