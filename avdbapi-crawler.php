<?php

/*
* @wordpress-plugin
* Plugin Name: Adult API Crawler For Naught Theme
* Plugin URI: https://avdbapi.com
* Description: Collect videos from multiple adult API providers (AVDBAPI, XVIDAPI, etc.) - Eroz Theme Compatibility
* Version: 2.0.0
* Requires PHP: 7.4^
* Author: Adult API Crawler For Eroz Theme
* Author URI: https://avdbapi.com
*/

// Protect plugins from direct access. If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die('The action has not been authenticated!');
}

/**
 * Currently plugin version.
 * Start at version 2.0.0
 */
define( 'PLUGIN_NAME_VERSION', '2.0.0' );

/**
 * The unique identifier of this plugin.
 */
set_time_limit(0);
if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
    $version = PLUGIN_NAME_VERSION;
} else {
    $version = '2.0.0';
}

define('PLUGIN_NAME', 'adult-api-crawler-for-wp-script');
define('VERSION', $version);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_plugin_name() {
    // Clear any existing cron events
    wp_clear_scheduled_hook('avdbapi_cron_crawl');
    
    // Set default cronjob settings
    $default_settings = array(
        'enabled' => false,
        'api_url' => 'https://avdbapi.com/api.php/provide/vod/?ac=detail',
        'crawling_method' => 'recent', // recent, selected, all
        'selected_pages_from' => 1,
        'selected_pages_to' => 5,
        'schedule' => 'twicedaily', // hourly, twicedaily, daily
        'download_images' => false,
        'force_update' => false,
        'last_run' => null,
        'next_run' => null,
        'total_runs' => 0,
        'last_status' => null
    );
    
    update_option('avdbapi_cronjob_settings', $default_settings);
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_plugin_name() {
    // Clear the cron event when plugin is deactivated
    wp_clear_scheduled_hook('avdbapi_cron_crawl');
}

register_activation_hook( __FILE__, 'activate_plugin_name' );
register_deactivation_hook( __FILE__, 'deactivate_plugin_name' );

/**
 * Provide a public-facing view for the plugin
 */
function avdbapi_crawler_add_menu() {
    add_menu_page(
        __('Adult API Crawler For Eroz Theme Tools', 'textdomain'),
        'Adult API Crawler For Eroz Theme',
        'manage_options',
        'adult-api-crawler-for-wp-script',
        'avdbapi_crawler_page_menu',
        'dashicons-video-alt3',
        2
    );
}

/**
 * Include the following files that make up the plugin
 */
function avdbapi_crawler_page_menu() {
    require_once plugin_dir_path(__FILE__) . 'public/partials/avdbapi_crawler_view.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 * 
 */
require_once plugin_dir_path( __FILE__ ) . 'public/public-crawler.php';
function run_plugin_name() {
    add_action('admin_menu', 'avdbapi_crawler_add_menu');

    $plugin_admin = new Nguon_avdbapi_crawler( PLUGIN_NAME, VERSION );
    add_action('in_admin_header', array($plugin_admin, 'enqueue_scripts'));
    add_action('in_admin_header', array($plugin_admin, 'enqueue_styles'));

    add_action('wp_ajax_avdbapi_crawler_api', array($plugin_admin, 'avdbapi_crawler_api'));
    add_action('wp_ajax_avdbapi_get_movies_page', array($plugin_admin, 'avdbapi_get_movies_page'));
    add_action('wp_ajax_avdbapi_crawl_by_id', array($plugin_admin, 'avdbapi_crawl_by_id'));
    // Add AJAX handler for multi-link import
    add_action('wp_ajax_avdbapi_multi_link_import', function() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        $ids = isset($_POST['ids']) ? sanitize_text_field($_POST['ids']) : '';
        if (empty($ids)) {
            wp_send_json_error(['message' => 'No IDs provided.']);
        }
        $api_url = 'https://avdbapi.com/api.php/provide/vod?ac=detail&ids=' . urlencode($ids);
        $response = wp_remote_get($api_url);
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'API request failed.']);
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (empty($data['list'])) {
            wp_send_json_error(['message' => 'No videos found for these IDs.']);
        }
        // Return the list to the frontend for further processing (so JS can use the same crawling/progress logic)
        wp_send_json_success(['message' => 'Found ' . count($data['list']) . ' videos.', 'movies' => $data['list']]);
    });

    // Add AJAX handlers for cronjob settings
    add_action('wp_ajax_avdbapi_save_cronjob_settings', 'avdbapi_save_cronjob_settings');
    add_action('wp_ajax_avdbapi_get_cronjob_settings', 'avdbapi_get_cronjob_settings');
    add_action('wp_ajax_avdbapi_test_cronjob', 'avdbapi_test_cronjob');
    add_action('wp_ajax_avdbapi_stop_cronjob', 'avdbapi_stop_cronjob');
    add_action('wp_ajax_avdbapi_clear_cronjob_lock', 'avdbapi_clear_cronjob_lock');
    
    // Register the cron event
    add_action('avdbapi_cron_crawl', 'avdbapi_execute_cronjob_crawl');
    
    // Add manual test hook (for debugging)
    if (isset($_GET['avdbapi_test']) && $_GET['avdbapi_test'] === 'manual') {
        add_action('init', 'avdbapi_manual_test');
    }
    
    // Add debug hook (for debugging)
    if (isset($_GET['avdbapi_debug']) && $_GET['avdbapi_debug'] === 'status') {
        add_action('init', 'avdbapi_debug_cron_status');
    }
    
    // Add custom cron schedules
    add_filter('cron_schedules', 'avdbapi_add_cron_schedules');
    
    // Enqueue Bootstrap 5 for admin area (for modal support)
    add_action('admin_enqueue_scripts', function() {
        wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
        wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array('jquery'), null, true);
    });

    // Enqueue Bootstrap 5 for admin area (for modal support)
    add_action('admin_enqueue_scripts', function() {
        wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
        wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array('jquery'), null, true);
    });
}

/**
 * Add custom cron schedules
 */
function avdbapi_add_cron_schedules($schedules) {
    $schedules['every_30_minutes'] = array(
        'interval' => 1800,
        'display'  => 'Every 30 Minutes'
    );
    $schedules['every_2_hours'] = array(
        'interval' => 7200,
        'display'  => 'Every 2 Hours'
    );
    $schedules['every_6_hours'] = array(
        'interval' => 21600,
        'display'  => 'Every 6 Hours'
    );
    return $schedules;
}

/**
 * Save cronjob settings via AJAX
 */
function avdbapi_save_cronjob_settings() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }
    // Clear hard stop flag so user can resume crawling after saving
    delete_transient('avdbapi_cronjob_hard_stop');
    $settings = array(
        'enabled' => isset($_POST['enabled']) ? (bool)$_POST['enabled'] : false,
        'api_url' => sanitize_text_field($_POST['api_url']),
        'crawling_method' => sanitize_text_field($_POST['crawling_method']),
        'selected_pages_from' => intval($_POST['selected_pages_from']),
        'selected_pages_to' => intval($_POST['selected_pages_to']),
        'schedule' => sanitize_text_field($_POST['schedule']),
        // Force to false unless explicitly checked
        'download_images' => (isset($_POST['download_images']) && $_POST['download_images'] == 'true') ? true : false,
        'force_update' => (isset($_POST['force_update']) && $_POST['force_update'] == 'true') ? true : false
    );
    // Always clear existing cron event
    wp_clear_scheduled_hook('avdbapi_cron_crawl');
    // Schedule new cron event if enabled (do NOT run crawl now)
    if ($settings['enabled']) {
        $interval = 0;
        switch ($settings['schedule']) {
            case 'every_30_minutes': $interval = 1800; break;
            case 'hourly': $interval = 3600; break;
            case 'every_2_hours': $interval = 7200; break;
            case 'every_6_hours': $interval = 21600; break;
            case 'twicedaily': $interval = 43200; break;
            case 'daily': $interval = 86400; break;
            default: $interval = 43200; // fallback to twice daily
        }
        $next_run_time = time() + $interval;
        if (!wp_next_scheduled('avdbapi_cron_crawl')) {
            wp_schedule_event($next_run_time, $settings['schedule'], 'avdbapi_cron_crawl');
        }
        $settings['next_run'] = wp_next_scheduled('avdbapi_cron_crawl');
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Adult API Crawler: Scheduled next cron for ' . date('Y-m-d H:i:s', $settings['next_run']) . ' (interval: ' . $interval . ' seconds)');
        }
    } else {
        $settings['next_run'] = null;
    }
    update_option('avdbapi_cronjob_settings', $settings);
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Adult API Crawler: Settings saved. Next run: ' . ($settings['next_run'] ? date('Y-m-d H:i:s', $settings['next_run']) : 'Disabled'));
    }
    wp_send_json_success([
        'message' => 'Cronjob settings saved successfully!',
        'next_run' => $settings['next_run'] ? date('Y-m-d H:i:s', $settings['next_run']) : 'Disabled'
    ]);
}

/**
 * Stop cronjob immediately via AJAX (hard stop)
 */
function avdbapi_stop_cronjob() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }
    wp_clear_scheduled_hook('avdbapi_cron_crawl');
    set_transient('avdbapi_cronjob_hard_stop', 1, 60 * 30); // 30 min
    $settings = get_option('avdbapi_cronjob_settings', array());
    $settings['enabled'] = false;
    $settings['next_run'] = null;
    update_option('avdbapi_cronjob_settings', $settings);
    wp_send_json_success(['message' => 'All scheduled cronjobs stopped. Hard stop activated.']);
}

/**
 * Get cronjob settings via AJAX
 */
function avdbapi_get_cronjob_settings() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }
    
    $settings = get_option('avdbapi_cronjob_settings', array());
    // Ensure download_images and force_update are always set (default false)
    if (!isset($settings['download_images'])) {
        $settings['download_images'] = false;
    }
    if (!isset($settings['force_update'])) {
        $settings['force_update'] = false;
    }
    $settings['next_run_formatted'] = $settings['next_run'] ? date('Y-m-d H:i:s', $settings['next_run']) : 'Disabled';
    $settings['last_run_formatted'] = $settings['last_run'] ? date('Y-m-d H:i:s', $settings['last_run']) : 'Never';
    
    wp_send_json_success($settings);
}

/**
 * Test cronjob via AJAX (run once, do not schedule)
 */
function avdbapi_test_cronjob() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }
    // Clear hard stop flag so user can test again
    delete_transient('avdbapi_cronjob_hard_stop');
    $settings = get_option('avdbapi_cronjob_settings', array());
    if (!$settings['enabled']) {
        wp_send_json_error(['message' => 'Cronjob is not enabled.']);
    }
    // Run the crawl once, using current settings (including force_update)
    $result = avdbapi_execute_cronjob_crawl();
    wp_send_json_success(['message' => 'Test completed: ' . $result]);
}

/**
 * Clear cronjob lock via AJAX (for debugging stuck locks)
 */
function avdbapi_clear_cronjob_lock() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }
    delete_transient('avdbapi_cronjob_lock');
    delete_transient('avdbapi_cronjob_hard_stop');
    wp_send_json_success(['message' => 'All locks cleared successfully.']);
}

/**
 * Manual test function for debugging (can be called via URL)
 */
function avdbapi_manual_test() {
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied.');
    }
    
    echo "<h2>Manual Cron Test</h2>";
    echo "<p>Testing cron execution...</p>";
    
    // Check if cron is scheduled
    $next_run = wp_next_scheduled('avdbapi_cron_crawl');
    echo "<p>Next scheduled run: " . ($next_run ? date('Y-m-d H:i:s', $next_run) : 'Not scheduled') . "</p>";
    
    // Check settings
    $settings = get_option('avdbapi_cronjob_settings', array());
    echo "<p>Settings: " . print_r($settings, true) . "</p>";
    
    // Check locks
    $lock = get_transient('avdbapi_cronjob_lock');
    $hard_stop = get_transient('avdbapi_cronjob_hard_stop');
    echo "<p>Lock: " . ($lock ? 'Yes' : 'No') . "</p>";
    echo "<p>Hard Stop: " . ($hard_stop ? 'Yes' : 'No') . "</p>";
    
    // Run test
    echo "<p>Running test...</p>";
    $result = avdbapi_execute_cronjob_crawl();
    echo "<p>Result: " . $result . "</p>";
    
    echo "<p><a href='admin.php?page=adult-api-crawler-for-wp-script'>Back to Plugin</a></p>";
    wp_die();
}

/**
 * Debug function to check cron status
 */
function avdbapi_debug_cron_status() {
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied.');
    }
    
    $settings = get_option('avdbapi_cronjob_settings', array());
    $lock_status = get_transient('avdbapi_cronjob_lock') ? 'LOCKED' : 'UNLOCKED';
    $hard_stop = get_transient('avdbapi_cronjob_hard_stop') ? 'STOPPED' : 'RUNNING';
    
    echo '<h2>Cron Debug Status:</h2>';
    echo '<pre>';
    echo "Settings: " . print_r($settings, true) . "\n";
    echo "Lock Status: $lock_status\n";
    echo "Hard Stop: $hard_stop\n";
    echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
    echo "Last Run: " . (!empty($settings['last_run']) ? date('Y-m-d H:i:s', $settings['last_run']) : 'Never') . "\n";
    echo '</pre>';
    echo '<p><a href="' . admin_url('admin.php?page=avdbapi-crawler') . '">← Back to Plugin</a></p>';
    exit;
}

/**
 * Execute the cronjob crawl
 */
function avdbapi_execute_cronjob_crawl() {
    // Hard stop: abort if flag is set
    if (get_transient('avdbapi_cronjob_hard_stop')) {
        delete_transient('avdbapi_cronjob_lock');
        error_log('Adult API Crawler: Aborted crawl due to hard stop.');
        return 'Aborted: Hard stop requested.';
    }
    
    // Lock: prevent overlapping jobs
    if (get_transient('avdbapi_cronjob_lock')) {
        error_log('Adult API Crawler: Skipped crawl because another crawl is already running.');
        return 'Skipped: Another crawl is already running.';
    }
    
    // PREVENT BULK EXECUTION: Skip if last run was too recent
    $settings = get_option('avdbapi_cronjob_settings', array());
    $now = time();
    if (!empty($settings['last_run']) && ($now - $settings['last_run']) < 1200) { // 20 minutes minimum
        error_log('Adult API Crawler: Skipped cron, last run was too recent (' . ($now - $settings['last_run']) . ' seconds ago).');
        return 'Skipped: Last run was too recent (' . round(($now - $settings['last_run']) / 60, 1) . ' minutes ago).';
    }
    
    set_transient('avdbapi_cronjob_lock', 1, 60 * 30); // 30 min lock
    if (!$settings['enabled']) {
        delete_transient('avdbapi_cronjob_lock');
        error_log('Adult API Crawler: Cronjob is disabled, aborting crawl.');
        return 'Cronjob is disabled';
    }
    // Update last run time
    $settings['last_run'] = time();
    $settings['total_runs']++;
    error_log('Adult API Crawler: Starting crawl. Method: ' . $settings['crawling_method'] . ', API: ' . $settings['api_url']);
    try {
        $crawler = new Nguon_avdbapi_crawler(PLUGIN_NAME, VERSION);
        $crawler->CRAWL_IMAGE = $settings['download_images'] ? 1 : 0;
        $api_url = $settings['api_url'];
        $url = strpos($api_url, '?') === false ? $api_url . '?' : $api_url . '&';
        $latest_url = $url . http_build_query(['pg' => 1]);
        $response = wp_remote_get($latest_url);
        if (is_wp_error($response)) {
            throw new Exception('Failed to connect to API');
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        if (!$data || !isset($data->pagecount)) {
            throw new Exception('Invalid API response');
        }
        $total_pages = $data->pagecount;
        $videos_processed = 0;
        $pages_to_crawl = array();
        switch ($settings['crawling_method']) {
            case 'recent':
                $pages_to_crawl = range(1, min(5, $total_pages));
                break;
            case 'selected':
                $from = max(1, $settings['selected_pages_from']);
                $to = min($total_pages, $settings['selected_pages_to']);
                $pages_to_crawl = range($from, $to);
                break;
            case 'all':
                $pages_to_crawl = range(1, $total_pages);
                break;
        }
        
        // LOCALHOST SAFETY: Limit pages on localhost/dev environments
        if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
            strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
            strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false) {
            $pages_to_crawl = array_slice($pages_to_crawl, 0, 2); // Max 2 pages on localhost
            error_log('Adult API Crawler: Limited to 2 pages on localhost environment.');
        }
        foreach ($pages_to_crawl as $page) {
            if (get_transient('avdbapi_cronjob_hard_stop')) {
                delete_transient('avdbapi_cronjob_lock');
                return 'Aborted: Hard stop requested.';
            }
            $page_url = $url . http_build_query(['pg' => $page]);
            $max_retries = 3;
            $retry = 0;
            $page_data = null;
            do {
                $page_response = wp_remote_get($page_url);
                $page_body = wp_remote_retrieve_body($page_response);
                $page_data = json_decode($page_body);
                $retry++;
                if ((!$page_data || !isset($page_data->list)) && $retry < $max_retries) {
                    error_log("Adult API Crawler: Empty or invalid response for page $page. Retrying ($retry/$max_retries)...");
                    sleep(2); // Wait before retry
                }
            } while ((!$page_data || !isset($page_data->list)) && $retry < $max_retries);
            if (!$page_data || !isset($page_data->list)) {
                error_log("Adult API Crawler: Skipped page $page due to invalid response after $max_retries retries.");
                continue;
            }
            foreach ($page_data->list as $movie) {
                if (get_transient('avdbapi_cronjob_hard_stop')) {
                    delete_transient('avdbapi_cronjob_lock');
                    return 'Aborted: Hard stop requested.';
                }
                try {
                    $movie_data = json_decode(json_encode($movie), true);
                    // Validate required fields before processing
                    $required_fields = ['id', 'type_name', 'name', 'slug', 'description'];
                    $missing = false;
                    foreach ($required_fields as $field) {
                        if (empty($movie_data[$field])) {
                            $missing = true;
                            break;
                        }
                    }
                    if ($missing) {
                        error_log('Adult API Crawler: Skipped movie due to missing fields: ' . print_r($movie_data, true));
                        continue;
                    }
                    $force_update = isset($settings['force_update']) && $settings['force_update'] ? true : false;
                    $result = $crawler->avdbapi_crawl_by_id_cron($movie_data, $force_update);
                    if ($result) {
                        $videos_processed++;
                    }
                } catch (Exception $e) {
                    error_log('Adult API Crawler: Exception processing movie: ' . $e->getMessage());
                    continue;
                }
            }
            // Add a random delay to avoid API rate limits
            sleep(rand(2, 4));
        }
        $settings['last_status'] = "Success: Processed $videos_processed videos from " . count($pages_to_crawl) . " pages";
        update_option('avdbapi_cronjob_settings', $settings);
        delete_transient('avdbapi_cronjob_lock');
        delete_transient('avdbapi_cronjob_hard_stop');
        error_log('Adult API Crawler: Crawl finished. Status: ' . $settings['last_status']);
        return "Success: Processed $videos_processed videos from " . count($pages_to_crawl) . " pages";
    } catch (Exception $e) {
        $settings['last_status'] = "Error: " . $e->getMessage();
        update_option('avdbapi_cronjob_settings', $settings);
        delete_transient('avdbapi_cronjob_lock');
        delete_transient('avdbapi_cronjob_hard_stop');
        error_log('Adult API Crawler: Crawl failed. Error: ' . $e->getMessage());
        return "Error: " . $e->getMessage();
    }
}

run_plugin_name();