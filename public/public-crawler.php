<?php

/**
 * The public-facing functionality of the plugin.
 * 
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 */
class Nguon_avdbapi_crawler
{
    private $plugin_name;
    private $version;

    public $CRAWL_IMAGE = 1;

    /**
     * Initialize the class and set its properties.
     *
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name . 'mainjs', plugin_dir_url(__FILE__) . 'js/main.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->plugin_name . 'bootstrapjs', plugin_dir_url(__FILE__) . 'js/bootstrap.bundle.min.js', array(), $this->version, false);
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/nguonc.css', array(), $this->version, 'all');
    }

    /**
     * Make CURL
     *
     * @param  string      $url       Url string
     * @return string|bool $response  Response
     */
    private function curl($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * wp_ajax_avdbapi_crawler_api action Callback function
     *
     * @param  string $api url
     * @return json $page_array
     */
    public function avdbapi_crawler_api()
    {
        // Check if required POST data exists
        if (!isset($_POST['api'])) {
            echo json_encode(['code' => 999, 'message' => 'Missing API parameter']);
            wp_die();
        }
        
        $url = $_POST['api'];
        $url = strpos($url, '?') === false ? $url . '?' : $url . '&';
        $latest_url = $url . http_build_query(['pg' => 1]);

        $latest_response = $this->curl($latest_url);

        $latest_data = json_decode($latest_response);

        // Check if json_decode was successful
        if (!$latest_data) {
            echo json_encode(['code' => 999, 'message' => 'Failed to decode API response']);
            wp_die();
        }

        $page_array = array(
            'code' => 1,
            'last_page' => $latest_data->pagecount ?? 0,
            'per_page' => $latest_data->limit ?? 0,
            'total' => $latest_data->pagecount ?? 0,
            'full_list_page' => range(1, $latest_data->pagecount ?? 1),
            'latest_list_page' => range(1, $latest_data->pagecount ?? 1),
        );
        echo json_encode($page_array);

        wp_die();
    }

    /**
     * wp_ajax_avdbapi_get_movies_page action Callback function
     *
     * @param  string $api        url
     * @param  string $param      query params
     * @return json   $page_array List movies in page
     */
    public function avdbapi_get_movies_page()
    {
        try {
            // Check if required POST data exists
            if (!isset($_POST['api']) || !isset($_POST['param'])) {
                echo json_encode(['code' => 999, 'message' => 'Missing required parameters']);
                wp_die();
            }
            
            $url = $_POST['api'];
            $params = $_POST['param'];
            $url = strpos($url, '?') === false ? $url . '?' : $url . '&';
            $response = $this->curl($url . $params);

            // Check if response is empty or invalid
            if (empty($response)) {
                echo json_encode(['code' => 999, 'message' => 'Empty response from API']);
                wp_die();
            }

            $data = json_decode($response);
            if (!$data) {
                $json_error = json_last_error_msg();
                echo json_encode(['code' => 999, 'message' => 'Invalid JSON response: ' . $json_error, 'response' => substr($response, 0, 200)]);
                wp_die();
            }

            // Validate required fields
            if (!isset($data->list)) {
                echo json_encode(['code' => 999, 'message' => 'Missing required field: list']);
                wp_die();
            }

            $page_array = array(
                'code' => 1,
                'movies' => $data->list,
            );
            echo json_encode($page_array);

            wp_die();
        } catch (\Throwable $th) {
            //throw $th;
            echo json_encode(['code' => 999, 'message' => $th->getMessage()]);
            wp_die();
        }
    }

    /**
     * wp_ajax_avdbapi_crawl_by_id action Callback function
     *
     * @param  string $api        url
     * @param  string $param      movie id
     */
    public function avdbapi_crawl_by_id()
    {
        try {
            // Check if required POST data exists
            if (!isset($_POST['av']) || !isset($_POST['crawl_image'])) {
                echo json_encode(['code' => 999, 'message' => 'Missing required parameters']);
                wp_die();
            }
            
            wp_cache_flush();
            $av = $_POST['av'];
            $this->CRAWL_IMAGE = $_POST['crawl_image'];

            // Clean up the JSON string and try multiple parsing approaches
            $av_clean = $av;
            
            // Remove any potential double escaping
            $av_clean = str_replace('\"', '"', $av_clean);
            $av_clean = stripslashes($av_clean);
            
            // Try to decode the JSON
            $data = json_decode($av_clean, true);
            
            // If that fails, try without the second parameter (as object)
            if (!$data) {
                $data_obj = json_decode($av_clean, false);
                if ($data_obj) {
                    $data = json_decode(json_encode($data_obj), true);
                }
            }
            
            // If still no data, try with different encoding
            if (!$data) {
                // Use mb_convert_encoding instead of deprecated utf8_encode
                $av_clean = mb_convert_encoding($av_clean, 'UTF-8', 'ISO-8859-1');
                $data = json_decode($av_clean, true);
            }
            
            if (!$data || !isset($data['id']) || !isset($data['type_name'])) {
                echo json_encode([
                    'code' => 999, 
                    'message' => 'The JSON model is not right, does not support collection', 
                    'data' => $data, 
                    'av' => $_POST['av'],
                    'av_clean' => $av_clean,
                    'json_error' => json_last_error_msg()
                ]);
                die();
            }
            
            // Additional validation for required fields
            $required_fields = ['name', 'slug', 'description', 'category', 'actor'];
            $missing_fields = [];
            foreach ($required_fields as $field) {
                if (!isset($data[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                echo json_encode([
                    'code' => 999,
                    'message' => 'Missing required fields: ' . implode(', ', $missing_fields),
                    'data' => $data
                ]);
                die();
            }
            $movie_data = $data;

            $args = array(
                'name' => $data["slug"],
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 1
            );
            $my_posts = get_posts($args);

            $existing_post_id = null;
            if ($my_posts) { // Coincide the movie name
                $existing_post_id = $my_posts[0]->ID; // Get the ID of the first post found
            }

            // Check if episodes data exists and is valid
            if (isset($movie_data["episodes"]) && isset($movie_data["episodes"]["server_data"])) {
                foreach ($movie_data["episodes"]["server_data"] as $key => $val) {
                    $this->insert_movie($movie_data, $key, $val, $existing_post_id);
                }
            } else {
                echo json_encode([
                    'code' => 999,
                    'message' => 'Missing episodes data in movie structure',
                    'data' => $movie_data
                ]);
                wp_die();
            }

            $action_type = $existing_post_id ? 'updated' : 'created';
            $result = array(
                'code' => 1,
                'message' => $movie_data['slug'] . ' : Successfully ' . $action_type . '.',
                'data' => $movie_data,
            );

            echo json_encode($result);
            wp_die();
        } catch (\Throwable $th) {
            echo json_encode([
                'code' => 999,
                'message' => $th->getMessage(),
                'data' => isset($movie_data) ? $movie_data : null
            ]);
            wp_die();
        }

    }

    /**
     * Cronjob version of crawl_by_id - doesn't require AJAX parameters
     *
     * @param  array  $movie_data  Movie data array
     * @param  bool   $force_update Force update existing posts
     * @return bool   Success status
     */
    public function avdbapi_crawl_by_id_cron($movie_data, $force_update = false)
    {
        try {
            if (!$movie_data || !isset($movie_data['id']) || !isset($movie_data['type_name'])) {
                return false;
            }
            
            wp_cache_flush();
            
            // Clean up the data and try multiple parsing approaches
            $data = $movie_data;

            // --- Normalize fields to arrays if needed (like JS does) ---
            $fields_to_array = ['category', 'actor', 'tag', 'director'];
            foreach ($fields_to_array as $field) {
                if (isset($data[$field]) && !is_array($data[$field])) {
                    if (is_string($data[$field]) && strlen(trim($data[$field])) > 0) {
                        $data[$field] = array_map('trim', preg_split('/[,|\/]/', $data[$field]));
                    } else {
                        $data[$field] = [];
                    }
                }
            }
            // --- End normalization ---

            // --- Extract link_embed from nested structure if missing ---
            if (empty($data['link_embed'])) {
                if (isset($data['episodes']['server_data']) && is_array($data['episodes']['server_data'])) {
                    // Try to get the first available link_embed
                    foreach ($data['episodes']['server_data'] as $ep) {
                        if (isset($ep['link_embed']) && !empty($ep['link_embed'])) {
                            $data['link_embed'] = $ep['link_embed'];
                            break;
                        }
                    }
                } elseif (isset($data['episodes']['server_data']['Full']['link_embed'])) {
                    $data['link_embed'] = $data['episodes']['server_data']['Full']['link_embed'];
                }
            }
            // --- End extract link_embed ---
            
            // Validate required fields before processing
            $required_fields = ['id', 'type_name', 'name', 'slug', 'description'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Adult API Crawler: Skipped movie in cron due to missing field: ' . $field . ' | Data: ' . print_r($data, true));
                    }
                    return false;
                }
            }
            
            // Additional validation for required fields
            $required_fields = ['name', 'slug', 'description', 'category', 'actor'];
            $missing_fields = [];
            foreach ($required_fields as $field) {
                if (!isset($data[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                echo json_encode([
                    'code' => 999,
                    'message' => 'Missing required fields: ' . implode(', ', $missing_fields),
                    'data' => $data
                ]);
                die();
            }
            $movie_data = $data;

            $args = array(
                'name' => $data["slug"],
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 1
            );
            $my_posts = get_posts($args);
            $existing_post_id = null;
            if($my_posts){
                $existing_post_id = $my_posts[0]->ID;
            }
            // If not forcing update and post exists, skip
            if (!$force_update && $existing_post_id) {
                return false;
            }
            
            // Process episodes
            $episodes = array();
            if (isset($data['episode_list']) && is_array($data['episode_list'])) {
                $episodes = $data['episode_list'];
            } elseif (isset($data['episodes']['server_data']) && is_array($data['episodes']['server_data'])) {
                // Convert server_data to episode list
                foreach ($data['episodes']['server_data'] as $ep_name => $ep) {
                    $episodes[] = array(
                        'name' => $ep_name,
                        'link_embed' => isset($ep['link_embed']) ? $ep['link_embed'] : ''
                    );
                }
            } else {
                // Single episode
                $episodes = array(array(
                    'name' => 'Full',
                    'link_embed' => $data['link_embed'] ?? ''
                ));
            }
            
            $processed_count = 0;
            foreach ($episodes as $episode) {
                // Validate required episode fields
                if (empty($episode['link_embed'])) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Adult API Crawler: Skipped episode in cron due to missing link_embed. Data: ' . print_r($episode, true));
                    }
                    continue;
                }
                $episode_name = $episode['name'] ?? 'Full';
                $post_id = $this->insert_movie($data, $episode_name, $episode, $existing_post_id);
                if ($post_id) {
                    $processed_count++;
                }
            }
            
            return $processed_count > 0;
            
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Insert or update movie to WP posts, save images
     *
     * @param  array  $data   movie data
     * @param  string $name   episode name
     * @param  array  $episode episode data
     * @param  int    $existing_post_id existing post ID to update (optional)
     */
    private function insert_movie($data, $name, $episode, $existing_post_id = null)
    {
        // Emergency global stop: abort if flag is set
        if (get_transient('avdbapi_cronjob_hard_stop')) {
            return null;
        }
        // Validate required fields before creating post
        $required_fields = ['id', 'type_name', 'name', 'slug', 'description'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Adult API Crawler: Skipped insert_movie due to missing field: ' . $field . ' | Data: ' . print_r($data, true));
                }
                return null;
            }
        }
        if (empty($episode['link_embed'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Adult API Crawler: Skipped insert_movie due to missing link_embed. Data: ' . print_r($episode, true));
            }
            return null;
        }
        $addition_title = "";
        $addition_slug = "";
        if ($name != "Full"){
            $addition_title = " - EP " . $name;
            $addition_slug = "-" . $name;
        }
        $post_id = null;
        
        // If we have an existing post ID to update, use it
        if ($existing_post_id) {
            $post_id = $existing_post_id;
        } else {
            // Check if post already exists
            $args = array(
                'name' => $data['slug'].$addition_slug,
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 1
            );
            $my_posts = get_posts($args);
            if($my_posts){
                $post_id = $my_posts[0]->ID; // Get the ID of the first post found
            }
        }

        $post_data = array(
            'post_title' => $this->decode_html_entities($data['name']) . $addition_title,
            'post_content' => $this->decode_html_entities($data['description']),
            'post_name' => $data['slug'].$addition_slug,
            'post_status' => 'publish',
            'comment_status' => 'open',
            'ping_status' => 'open',
            'post_author' => get_current_user_id(),
            'post_type' => "post",
        );
        
        // If updating existing post, only update fields that are present in API
        if ($post_id) {
            $existing_post = get_post($post_id);
            if ($existing_post) {
                // Only update title if it's different
                if ($existing_post->post_title !== $post_data['post_title']) {
                    $post_data['ID'] = $post_id;
                } else {
                    unset($post_data['post_title']); // Don't update if same
                }
                
                // Only update content if it's different
                if ($existing_post->post_content !== $post_data['post_content']) {
                    $post_data['ID'] = $post_id;
                } else {
                    unset($post_data['post_content']); // Don't update if same
                }
                
                // Only update slug if it's different
                if ($existing_post->post_name !== $post_data['post_name']) {
                    $post_data['ID'] = $post_id;
                } else {
                    unset($post_data['post_name']); // Don't update if same
                }
            }
        }
        
        // If we have an existing post ID, update it; otherwise create new
        if ($post_id) {
            $post_data['ID'] = $post_id;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        // Set the post format to video
        set_post_format($post_id, 'video');

        // --- ALWAYS OVERWRITE TAXONOMIES AND THUMBNAIL FROM API ---
        // Categories
        $categories_id = [];
        if (!category_exists($this->decode_html_entities($data['type_name'])) && $data['type_name'] !== '') {
            wp_create_category($this->decode_html_entities($data['type_name']));
        }
        $categories_id[] = get_cat_ID($this->decode_html_entities($data['type_name']));
        if (isset($data['category']) && is_array($data['category'])) {
            foreach ($data['category'] as $category) {
                $decoded_category = $this->decode_html_entities($category);
                if (!category_exists($decoded_category) && $decoded_category !== '') {
                    wp_create_category($decoded_category);
                }
                $categories_id[] = get_cat_ID($decoded_category);
            }
        }
        wp_set_post_categories($post_id, $categories_id);

        // Actors - Map to toro_pornstar taxonomy for Eroz theme
        if (isset($data['actor']) && is_array($data['actor'])) {
            $decoded_actors = [];
            foreach ($data['actor'] as $actor) {
                $decoded_actor = $this->decode_html_entities($actor);
                if (!term_exists($decoded_actor, 'actors') && $decoded_actor != '') {
                    wp_insert_term($decoded_actor, 'actors');
                }
                $decoded_actors[] = $decoded_actor;
            }
            wp_set_post_terms($post_id, $decoded_actors, 'actors', false);
        }

        // Thumbnail (featured image) - Map to eroz_meta_src for Eroz theme
        if (isset($data['poster_url']) && $this->CRAWL_IMAGE != 0) {
            $results = $this->save_images($data['poster_url']);
            if ($results !== false) {
                $attachment = array(
                    'guid' => $results['url'],
                    'post_mime_type' => $results['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($results['file'])),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $results['file'], $post_id);
                set_post_thumbnail($post_id, $attach_id);
                $data['poster_url'] = $results['url'];
                // Update eroz_meta_src for Eroz theme
                update_post_meta($post_id, 'eroz_meta_src', $results['url']);
            }
        } else if (isset($data['poster_url'])) {
            // If not downloading, still update eroz_meta_src for Eroz theme
            update_post_meta($post_id, 'eroz_meta_src', $data['poster_url']);
        }
        // --- END ALWAYS OVERWRITE TAXONOMIES AND THUMBNAIL ---

        // --- AUTO-GENERATE AND ASSIGN VIDEO TAGS ---
        $auto_tags = array();
        // API tag field (can be string or array)
        if (isset($data['tag'])) {
            if (is_array($data['tag'])) {
                foreach ($data['tag'] as $tag) {
                    $auto_tags[] = $this->decode_html_entities($tag);
                }
            } elseif (!empty($data['tag'])) {
                // Split comma-separated string
                $tag_array = array_map('trim', explode(',', $data['tag']));
                foreach ($tag_array as $tag) {
                    $auto_tags[] = $this->decode_html_entities($tag);
                }
            }
        }
        // Categories
        if (isset($data['category']) && is_array($data['category'])) {
            foreach ($data['category'] as $category) {
                $auto_tags[] = $this->decode_html_entities($category);
            }
        }
        // Director
        if (isset($data['director']) && is_array($data['director'])) {
            foreach ($data['director'] as $director) {
                $auto_tags[] = $this->decode_html_entities($director);
            }
        }
        // Year
        if (isset($data['year']) && !empty($data['year'])) {
            $auto_tags[] = $data['year'];
        }
        // Remove duplicates and empty values
        $auto_tags = array_unique(array_filter(array_map('trim', $auto_tags)));
        if (!empty($auto_tags)) {
            wp_set_post_terms($post_id, $auto_tags, 'post_tag', false);
        }
        // --- END AUTO-GENERATE AND ASSIGN VIDEO TAGS ---

        // --- EROZ THEME SPECIFIC META FIELDS MAPPING ---
        // Map embed and link_embed for Eroz theme
        if (isset($episode['link_embed'])) {
            $iframe = '<iframe src="' . $episode['link_embed'] . '" frameborder="0" scrolling="no" width="960" height="720" allowfullscreen></iframe>';
            update_post_meta($post_id, 'embed', $iframe);
            update_post_meta($post_id, 'link_embed', $episode['link_embed']);
        }

        // Map original name to original_name_1 for Eroz theme
        if (isset($data['original_name']) && !empty($data['original_name'])) {
            update_post_meta($post_id, 'original_name_1', $this->decode_html_entities($data['original_name']));
        } elseif (isset($data['origin_name']) && !empty($data['origin_name'])) {
            // Some APIs use origin_name instead of original_name
            update_post_meta($post_id, 'original_name_1', $this->decode_html_entities($data['origin_name']));
        }

        // Map movie code to movie_code fields for Eroz theme
        if (isset($data['movie_code']) && !empty($data['movie_code'])) {
            update_post_meta($post_id, 'movie_code_1', $data['movie_code']);
        }

        // Map duration for Eroz theme
        if (isset($data['duration']) && !empty($data['duration'])) {
            // Only save duration if it's numeric (seconds) and not iframe
            if (is_numeric($data['duration']) && strpos($data['duration'], '<iframe') === false) {
                update_post_meta($post_id, 'duration', $data['duration']);
            }
        }

        // Map description to eroz_post_desc for Eroz theme
        if (isset($data['description']) && !empty($data['description'])) {
            update_post_meta($post_id, 'eroz_post_desc', $this->decode_html_entities($data['description']));
        }

        // Map trailer URL for Eroz theme
        if (isset($data['trailer_url']) && !empty($data['trailer_url'])) {
            update_post_meta($post_id, 'trailer_url', $data['trailer_url']);
        }
        // --- END EROZ THEME SPECIFIC META FIELDS MAPPING ---

        return $post_id;
    }

    /**
     * Save movie thumbail to WP
     *
     * @param  string   $image_url   thumbail url
     */
    public function save_images($image_url)
    {
        require_once (ABSPATH . "wp-admin/includes/file.php");

        $temp_file = download_url($image_url, 300);
        if (!is_wp_error($temp_file)) {

            $mime_extensions = array(
                'jpg' => 'image/jpg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'png' => 'image/png',
                'webp' => 'image/webp',
            );

            // Array based on $_FILE as seen in PHP file uploads.
            $file = array(
                'name' => basename($image_url), // ex: wp-header-logo.png
                'type' => $mime_extensions[pathinfo($image_url, PATHINFO_EXTENSION)],
                'tmp_name' => $temp_file,
                'error' => 0,
                'size' => filesize($temp_file),
            );

            $overrides = array(
                'test_form' => false,
                'test_size' => true,
            );

            // Move the temporary file into the uploads directory.
            $results = wp_handle_sideload($file, $overrides);
            unlink($temp_file);

            if (!empty($results['error'])) {
                return false;
            } else {
                return $results;
            }
        }
    }

    /**
     * Uppercase the first character of each word in a string
     *
     * @param  string   $string     format string
     * @param  array    $arr        string array
     */
    private function format_text($string)
    {
        $string = str_replace(array('/', '，', '|', '、', ',,,'), ',', $string);
        $arr = explode(',', sanitize_text_field($string));
        foreach ($arr as &$item) {
            $item = ucwords(trim($item));
        }
        return $arr;
    }

    /**
     * Decode HTML entities to fix special characters in titles
     *
     * @param  string   $string     string to decode
     * @return string   decoded string
     */
    private function decode_html_entities($string)
    {
        if (empty($string)) {
            return $string;
        }
        
        // Decode common HTML entities
        $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Fix specific entities that might not be decoded properly
        $string = str_replace(
            ['&quot;', '&amp;', '&lt;', '&gt;', '&apos;', '&#39;', '&#34;'],
            ['"', '&', '<', '>', "'", "'", '"'],
            $string
        );
        
        // Clean up any remaining HTML tags
        $string = strip_tags($string);
        
        // Trim whitespace
        $string = trim($string);
        
        return $string;
    }

    /**
     * Filter html tags in api response
     *
     * @param  string   $rs     response
     * @param  array    $rs     response
     */
    private function filter_tags($rs)
    {
        $rex = array('{:', '<script', '<iframe', '<frameset', '<object', 'onerror');
        if (is_array($rs)) {
            foreach ($rs as $k2 => $v2) {
                if (!is_numeric($v2)) {
                    $rs[$k2] = str_ireplace($rex, '*', $rs[$k2]);
                }
            }
        } else {
            if (!is_numeric($rs)) {
                $rs = str_ireplace($rex, '*', $rs);
            }
        }
        return $rs;
    }


}
