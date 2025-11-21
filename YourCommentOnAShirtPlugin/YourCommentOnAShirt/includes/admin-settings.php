<?php
/**
 * Admin Settings and Catalog Management
 * 
 * This file handles all admin-related functionality including:
 * - Settings page UI for Printful API key configuration
 * - Catalog variant management (refresh from Printful API)
 * - Catalog image management (fetch from Printful API)
 * - AJAX handlers for admin operations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * ADMIN MENU AND SETTINGS PAGE
 * 
 * Purpose: Creates a settings page in the WordPress admin for managing the Printful API key
 * Uses WordPress Options API for secure storage in the database
 */

// Add admin menu
add_action('admin_menu', 'ycos_add_admin_menu');
function ycos_add_admin_menu() {
    add_options_page(
        'YourCommentOnAShirt Settings',           // Page title
        'YourCommentOnAShirt',                    // Menu title
        'manage_options',                         // Capability
        'your-comment-on-a-shirt',                // Menu slug
        'ycos_admin_page'                         // Callback function
    );
}

// Create the admin page
function ycos_admin_page() {
    // Handle form submission
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['ycos_nonce'], 'ycos_save_settings')) {
        $api_key = sanitize_text_field($_POST['printful_api_key']);
        update_option('ycos_printful_api_key', $api_key);
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    // Get current API key
    $current_api_key = get_option('ycos_printful_api_key', '');
    ?>
    <div class="wrap">
        <h1>YourCommentOnAShirt Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('ycos_save_settings', 'ycos_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="printful_api_key">Printful API Key</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="printful_api_key" 
                               name="printful_api_key" 
                               value="<?php echo esc_attr($current_api_key); ?>" 
                               class="regular-text" 
                               placeholder="Enter your Printful API key" />
                        <p class="description">
                            Enter your Printful API key. You can find this in your Printful dashboard under Settings > API.
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
        
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2>How to get your Printful API Key:</h2>
            <ol>
                <li>Log in to your <a href="https://www.printful.com/dashboard" target="_blank">Printful dashboard</a></li>
                <li>Go to Settings → API</li>
                <li>Copy your API key</li>
                <li>Paste it in the field above and click "Save Settings"</li>
            </ol>
        </div>
        
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2>Catalog Management:</h2>
            <p>Refresh the catalog variants from Printful to get the latest product information.</p>
            <button type="button" id="refresh-catalog-btn" class="button button-secondary">
                <span class="dashicons dashicons-update"></span> Refresh Catalog Variants
            </button>
            <div id="refresh-status" style="margin-top: 10px;"></div>
        </div>
        
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2>Catalog Images:</h2>
            <p>Fetch catalog images from Printful API. This will download all product images and save unique colors to a file.</p>
            <p>
                <label for="color-filter" style="display: block; margin-bottom: 5px; font-weight: bold;">Filter Colors (comma-separated):</label>
                <input type="text" id="color-filter" name="color-filter" placeholder="e.g., black, white, navy, red" style="width: 100%; padding: 8px; margin-bottom: 10px;" />
                <small style="color: #666;">Leave empty to fetch all colors. Enter color names separated by commas to filter specific colors.</small>
            </p>
            <button type="button" id="fetch-images-btn" class="button button-secondary">
                <span class="dashicons dashicons-images-alt2"></span> Fetch Catalog Images
            </button>
            <div id="fetch-images-status" style="margin-top: 10px;"></div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#refresh-catalog-btn').on('click', function() {
            var $btn = $(this);
            var $status = $('#refresh-status');
            
            $btn.prop('disabled', true);
            $status.html('<span class="spinner is-active"></span> Refreshing catalog variants...');
            
            $.post(ajaxurl, {
                action: 'ycos_refresh_catalog_variants',
                nonce: '<?php echo wp_create_nonce('ycos_refresh_catalog'); ?>'
            }, function(response) {
                $btn.prop('disabled', false);
                
                if (response.success) {
                    $status.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                } else {
                    $status.html('<div class="notice notice-error"><p>Error: ' + response.data.message + '</p></div>');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $status.html('<div class="notice notice-error"><p>Failed to refresh catalog variants. Please try again.</p></div>');
            });
        });
        
        $('#fetch-images-btn').on('click', function() {
            var $btn = $(this);
            var $status = $('#fetch-images-status');
            var colorFilter = $('#color-filter').val();
            
            $btn.prop('disabled', true);
            $status.html('<span class="spinner is-active"></span> Fetching catalog images...');
            
            $.post(ajaxurl, {
                action: 'ycos_fetch_catalog_images',
                nonce: '<?php echo wp_create_nonce('ycos_fetch_images'); ?>',
                color_filter: colorFilter
            }, function(response) {
                $btn.prop('disabled', false);
                
                if (response.success) {
                    $status.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                } else {
                    $status.html('<div class="notice notice-error"><p>Error: ' + response.data.message + '</p></div>');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $status.html('<div class="notice notice-error"><p>Failed to fetch catalog images. Please try again.</p></div>');
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX HANDLER FOR REFRESHING CATALOG VARIANTS
 * 
 * Hook: wp_ajax_ycos_refresh_catalog_variants
 * Purpose: Handles AJAX requests to refresh catalog variants from Printful API
 * This fetches the latest variant data and saves it as a JSON file for easy access
 */
add_action('wp_ajax_ycos_refresh_catalog_variants', 'ycos_ajax_refresh_catalog_variants');
function ycos_ajax_refresh_catalog_variants() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'ycos_refresh_catalog')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    // Call the refresh function
    $result = ycos_refresh_catalog_variants();
    
    if ($result['success']) {
        wp_send_json_success(['message' => $result['message']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}

/**
 * AJAX HANDLER FOR FETCHING CATALOG IMAGES
 * 
 * Hook: wp_ajax_ycos_fetch_catalog_images
 * Purpose: Handles AJAX requests to fetch catalog images from Printful API
 * This fetches all product images with pagination and saves unique colors to a JSON file
 */
add_action('wp_ajax_ycos_fetch_catalog_images', 'ycos_ajax_fetch_catalog_images');
function ycos_ajax_fetch_catalog_images() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'ycos_fetch_images')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    // Get color filter from POST data
    $color_filter = isset($_POST['color_filter']) ? sanitize_text_field($_POST['color_filter']) : '';
    
    // Call the fetch images function with color filter
    $result = ycos_fetch_catalog_images($color_filter);
    
    if ($result['success']) {
        wp_send_json_success(['message' => $result['message']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}

/**
 * PRINTFUL API HELPER FUNCTIONS
 */

/**
 * Helper function to make API requests with pagination parameters
 * 
 * @param string $api_key Printful API key
 * @param string $url API endpoint URL
 * @param int $limit Number of results per page
 * @param int $offset Pagination offset
 * @return array Result array with success status and data/error
 */
function ycos_make_printful_api_request($api_key, $url, $limit = 20, $offset = 0) {
    // Add pagination parameters to URL
    $url_with_params = $url . '?limit=' . $limit . '&offset=' . $offset;
    
    // Set up cURL request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_with_params);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $api_key
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Check for cURL errors
    if ($curl_error) {
        return [
            'success' => false,
            'error' => 'cURL Error: ' . $curl_error
        ];
    }

    // Check HTTP response code
    if ($http_code !== 200) {
        return [
            'success' => false,
            'error' => 'HTTP ' . $http_code . ': ' . $response
        ];
    }

    // Decode JSON response
    $data = json_decode($response, true);
    if (!$data) {
        return [
            'success' => false,
            'error' => 'Invalid JSON response'
        ];
    }

    return [
        'success' => true,
        'data' => $data
    ];
}

/**
 * REFRESH CATALOG VARIANTS FROM PRINTFUL
 *
 * Purpose: Fetches catalog variants from Printful API and saves them as JSON file
 * This function retrieves the latest variant data and maps it to the required format
 *
 * @return array Result array with success status and message
 */
function ycos_refresh_catalog_variants() {
    // Get API key from WordPress options
    $api_key = get_option('ycos_printful_api_key');
    if (empty($api_key)) {
        return [
            'success' => false,
            'message' => 'Printful API key not configured. Please set it in Settings > YourCommentOnAShirt'
        ];
    }

    // Printful API endpoint for catalog variants
    $api_url = 'https://api.printful.com/v2/catalog-products/438/catalog-variants';
    
    // Initialize variables for pagination
    $all_variants = [];
    $limit = 20;
    $offset = 0;
    $has_more_pages = true;
    $total_fetched = 0;
    
    // Fetch all pages of results
    while ($has_more_pages) {
        $result = ycos_make_printful_api_request($api_key, $api_url, $limit, $offset);
        
        if (!$result['success']) {
            error_log('YourCommentOnAShirt: API Error - ' . $result['error']);
            return [
                'success' => false,
                'message' => 'Failed to fetch catalog variants: ' . $result['error']
            ];
        }
        
        $data = $result['data'];
        
        // Check if we have data
        if (!isset($data['data']) || !is_array($data['data'])) {
            return [
                'success' => false,
                'message' => 'Invalid response from Printful API - no data field'
            ];
        }
        
        // Add variants from this page to our collection
        foreach ($data['data'] as $variant) {
            $all_variants[] = [
                'catalog_variant_id' => $variant['id'],
                'color' => $variant['color'],
                'color_code' => $variant['color_code'],
                'size' => $variant['size']
            ];
        }
        
        $total_fetched += count($data['data']);
        
        // Check pagination info
        if (isset($data['paging'])) {
            $paging = $data['paging'];
            $current_total = $paging['total'] ?? 0;
            $current_offset = $paging['offset'] ?? 0;
            $current_limit = $paging['limit'] ?? $limit;
            
            // Check if we have more pages
            if (($current_offset + $current_limit) >= $current_total) {
                $has_more_pages = false;
            } else {
                $offset += $limit;
            }
        } else {
            // If no paging info, check if we got fewer results than the limit
            if (count($data['data']) < $limit) {
                $has_more_pages = false;
            } else {
                $offset += $limit;
            }
        }
        
        // Safety check to prevent infinite loops
        if ($offset > 10000) { // Reasonable upper limit
            error_log('YourCommentOnAShirt: Pagination safety limit reached');
            break;
        }
    }

    // Save to JSON file in uploads directory
    $upload_dir = wp_upload_dir();
    $catalog_dir = $upload_dir['basedir'] . '/catalog-variants/';
    
    // Ensure directory exists
    if (!file_exists($catalog_dir)) {
        wp_mkdir_p($catalog_dir);
    }

    $json_file = $catalog_dir . 'catalog-variants.json';
    $json_data = json_encode($all_variants, JSON_PRETTY_PRINT);
    
    if (file_put_contents($json_file, $json_data) === false) {
        return [
            'success' => false,
            'message' => 'Failed to save catalog variants to file'
        ];
    }

    // Log success
    error_log('YourCommentOnAShirt: Successfully refreshed ' . count($all_variants) . ' catalog variants (fetched ' . $total_fetched . ' total)');

    return [
        'success' => true,
        'message' => 'Successfully refreshed ' . count($all_variants) . ' catalog variants from ' . ceil($total_fetched / $limit) . ' pages'
    ];
}

/**
 * FETCH CATALOG IMAGES FROM PRINTFUL
 *
 * Purpose: Fetches catalog images from Printful API with pagination and deduplication
 * This function retrieves all product images, paginates through all results (~260),
 * and saves unique colors to a JSON file (one entry per color, not per variant)
 *
 * @param string $color_filter Comma-separated list of colors to filter by (optional)
 * @return array Result array with success status and message
 */
function ycos_fetch_catalog_images($color_filter = '') {
    // Get API key from WordPress options
    $api_key = get_option('ycos_printful_api_key');
    if (empty($api_key)) {
        return [
            'success' => false,
            'message' => 'Printful API key not configured. Please set it in Settings > YourCommentOnAShirt'
        ];
    }

    // Printful API endpoint for catalog images
    $api_url = 'https://api.printful.com/v2/catalog-products/438/images';
    
    // Initialize variables for pagination
    $all_images = [];
    $unique_colors = []; // Track unique colors to avoid duplicates
    $limit = 20;
    $offset = 0;
    $has_more_pages = true;
    $total_fetched = 0;
    
    // Fetch all pages of results
    while ($has_more_pages) {
        $result = ycos_make_printful_api_request($api_key, $api_url, $limit, $offset);
        
        if (!$result['success']) {
            error_log('YourCommentOnAShirt: API Error - ' . $result['error']);
            return [
                'success' => false,
                'message' => 'Failed to fetch catalog images: ' . $result['error']
            ];
        }
        
        $data = $result['data'];
        
        // Check if we have data
        if (!isset($data['data']) || !is_array($data['data'])) {
            return [
                'success' => false,
                'message' => 'Invalid response from Printful API - no data field'
            ];
        }
        
        // Process images from this page
        foreach ($data['data'] as $image_data) {
            $color = $image_data['color'];
            $color_key = strtolower(trim($color)); // Normalize color for comparison
            
            // Apply color filter if specified
            if (!empty($color_filter)) {
                $filter_colors = array_map('trim', explode(',', $color_filter));
                $filter_colors = array_map('strtolower', $filter_colors);
                
                // Skip this color if it's not in the filter list
                if (!in_array($color_key, $filter_colors)) {
                    continue;
                }
            }
            
            // Only add if we haven't seen this color before
            if (!isset($unique_colors[$color_key])) {
                $unique_colors[$color_key] = true;
                
                // Process and simplify the images array - filter to only front, back, left, right images
                $simplified_images = [];
                $added_placements = []; // Track which placements we've already added
                foreach ($image_data['images'] as $image) {
                    $image_url = $image['image_url'];
                    $placement = $image['placement'];
                    
                    // Only include images that start with the specified URL patterns and have valid placements
                    // and we haven't already added this placement
                    if ((strpos($image_url, 'https://files.cdn.printful.com/m/Gildan5000/medium/ghost/') === 0 ||
                         strpos($image_url, 'https://files.cdn.printful.com/m/Gildan5000/medium/flat/') === 0) &&
                        in_array($placement, ['front', 'back', 'sleeve_left', 'sleeve_right']) &&
                        !in_array($placement, $added_placements)) {
                        
                        $simplified_images[] = [
                            'placement' => $image['placement'],
                            'image_url' => $image['image_url'],
                            'background_color' => $image['background_color']
                        ];
                        $added_placements[] = $placement; // Mark this placement as added
                    }
                }
                
                // Store only the required fields
                $all_images[] = [
                    'color' => $image_data['color'],
                    'primary_hex_color' => $image_data['primary_hex_color'],
                    'images' => $simplified_images
                ];
            }
        }
        
        $total_fetched += count($data['data']);
        
        // Check pagination info
        if (isset($data['paging'])) {
            $paging = $data['paging'];
            $current_total = $paging['total'] ?? 0;
            $current_offset = $paging['offset'] ?? 0;
            $current_limit = $paging['limit'] ?? $limit;
            
            // Check if we have more pages
            if (($current_offset + $current_limit) >= $current_total) {
                $has_more_pages = false;
            } else {
                $offset += $limit;
            }
        } else {
            // If no paging info, check if we got fewer results than the limit
            if (count($data['data']) < $limit) {
                $has_more_pages = false;
            } else {
                $offset += $limit;
            }
        }
        
        // Safety check to prevent infinite loops
        if ($offset > 10000) { // Reasonable upper limit
            error_log('YourCommentOnAShirt: Pagination safety limit reached');
            break;
        }
    }

    // Save to JSON file in uploads directory
    $upload_dir = wp_upload_dir();
    $catalog_dir = $upload_dir['basedir'] . '/catalog-images/';
    
    // Ensure directory exists
    if (!file_exists($catalog_dir)) {
        wp_mkdir_p($catalog_dir);
    }

    $json_file = $catalog_dir . 'catalog-images.json';
    $json_data = json_encode($all_images, JSON_PRETTY_PRINT);
    
    if (file_put_contents($json_file, $json_data) === false) {
        return [
            'success' => false,
            'message' => 'Failed to save catalog images to file'
        ];
    }

    // Download and save images - only one image per position
    $images_dir = $upload_dir['basedir'] . '/catalog-images/images/';
    
    // Ensure images directory exists
    if (!file_exists($images_dir)) {
        wp_mkdir_p($images_dir);
    }
    
    $downloaded_count = 0;
    $download_errors = [];
    $downloaded_positions = []; // Track which positions we've already downloaded
    
    foreach ($all_images as $color_data) {
        foreach ($color_data['images'] as $image) {
            $placement = $image['placement'];
            
            // Skip if we've already downloaded this position
            if (in_array($placement, $downloaded_positions)) {
                continue;
            }
            
            $image_url = $image['image_url'];
            
            // Get file extension from URL
            $path_info = pathinfo(parse_url($image_url, PHP_URL_PATH));
            $extension = isset($path_info['extension']) ? $path_info['extension'] : 'jpg';
            
            // Create filename: position.extension
            $filename = $placement . '.' . $extension;
            $file_path = $images_dir . $filename;
            
            // Download the image
            $image_data = file_get_contents($image_url);
            if ($image_data !== false) {
                if (file_put_contents($file_path, $image_data) !== false) {
                    $downloaded_count++;
                    $downloaded_positions[] = $placement; // Mark this position as downloaded
                } else {
                    $download_errors[] = "Failed to save: $filename";
                }
            } else {
                $download_errors[] = "Failed to download: $filename from $image_url";
            }
            
            // Stop if we've downloaded all 4 positions
            if (count($downloaded_positions) >= 4) {
                break 2; // Break out of both loops
            }
        }
    }

    // Log success
    $filter_info = !empty($color_filter) ? ' (filtered by colors: ' . $color_filter . ')' : '';
    error_log('YourCommentOnAShirt: Successfully fetched ' . count($all_images) . ' unique colors from ' . $total_fetched . ' total variants (fetched from ' . ceil($total_fetched / $limit) . ' pages)' . $filter_info);
    error_log('YourCommentOnAShirt: Downloaded ' . $downloaded_count . ' images to ' . $images_dir);

    $message = 'Successfully fetched ' . count($all_images) . ' unique colors from ' . $total_fetched . ' total variants across ' . ceil($total_fetched / $limit) . ' pages';
    if (!empty($color_filter)) {
        $message .= ' (filtered by colors: ' . $color_filter . ')';
    }
    
    $message .= '. Downloaded ' . $downloaded_count . ' images to: ' . $images_dir;
    
    if (!empty($download_errors)) {
        $message .= '. Download errors: ' . implode(', ', array_slice($download_errors, 0, 5));
        if (count($download_errors) > 5) {
            $message .= ' (and ' . (count($download_errors) - 5) . ' more)';
        }
    }

    return [
        'success' => true,
        'message' => $message
    ];
}

