<?php
/**
 * Plugin Name: YourCommentOnAShirt
 * Description: Adds a textbox to product pages, saves the text through cart/checkout, and attaches it to the order.
 * Version: 1.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * GLOBAL CONFIGURATION CONSTANTS
 */
define('YCOS_MIN_FONT_SIZE', 20);
define('YCOS_MAX_FONT_SIZE', 300);
define('YCOS_DEFAULT_FONT_SIZE', 120);

/**
 * INCLUDE REQUIRED FILES
 */

// Load admin settings and catalog management functionality
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';

/**
 * DISPLAY CUSTOM FIELDS ON PRODUCT PAGE
 * 
 * Hook: woocommerce_before_add_to_cart_form
 * Purpose: Automatically injects custom fields (comment text and text color) before the add-to-cart form
 */
add_action('woocommerce_before_add_to_cart_form', 'ycos_create_custom_fields');
function ycos_create_custom_fields() {
    ?>
    <!-- Comment Text Field -->
    <div class="comment-field">
        <label class="variation-label" for="comment_text">Comment</label>
        <div class="ycos-textarea-wrapper">
            <div class="ycos-alignment-buttons-container">
                <div class="ycos-control-group">
                    <label class="ycos-control-label">Color</label>
                    <div class="ycos-color-display-wrapper">
                        <button type="button" class="ycos-color-display-button" id="ycos-color-display" title="Change Text Color">
                            <div class="ycos-color-swatch" style="background-color: #000000;"></div>
                        </button>
                        <input type="color" id="text_color_picker" class="ycos-color-input" value="#000000">
                    </div>
                </div>
                <div class="ycos-control-group">
                    <label class="ycos-control-label">Size</label>
                    <div class="ycos-font-size-control">
                        <button type="button" class="ycos-font-size-button" id="ycos-font-decrease" title="Decrease Font Size">
                            <span>−</span>
                        </button>
                        <input type="number" id="font_size_display" class="ycos-font-size-input" value="<?php echo YCOS_DEFAULT_FONT_SIZE; ?>" min="<?php echo YCOS_MIN_FONT_SIZE; ?>" max="<?php echo YCOS_MAX_FONT_SIZE; ?>" title="Font Size">
                        <button type="button" class="ycos-font-size-button" id="ycos-font-increase" title="Increase Font Size">
                            <span>+</span>
                        </button>
                    </div>
                </div>
                <div class="ycos-control-group">
                    <label class="ycos-control-label">Font</label>
                    <div class="ycos-font-dropdown">
                        <button type="button" class="ycos-font-display-button selected" id="ycos-font-display" title="Select Font">
                            <span class="ycos-font-name">Font</span>
                        </button>
                        <div class="ycos-font-options" id="ycos-font-options"></div>
                    </div>
                </div>
                <div class="ycos-control-group">
                    <label class="ycos-control-label">H. Align</label>
                    <div class="ycos-alignment-dropdown ycos-alignment-horizontal">
                        <button type="button" class="ycos-align-button" data-align="left" title="Align Left">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/left.png" alt="Left Align">
                        </button>
                        <button type="button" class="ycos-align-button selected" data-align="center" title="Align Center">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/center.png" alt="Center Align">
                        </button>
                        <button type="button" class="ycos-align-button" data-align="right" title="Align Right">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/right.png" alt="Right Align">
                        </button>
                    </div>
                </div>
                <div class="ycos-control-group">
                    <label class="ycos-control-label">V. Align</label>
                    <div class="ycos-alignment-dropdown ycos-alignment-vertical">
                        <button type="button" class="ycos-valign-button" data-valign="top" title="Align Top">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/top.png" alt="Top Align">
                        </button>
                        <button type="button" class="ycos-valign-button selected" data-valign="center" title="Align Middle">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/middle.png" alt="Middle Align">
                        </button>
                        <button type="button" class="ycos-valign-button" data-valign="bottom" title="Align Bottom">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/bottom.png" alt="Bottom Align">
                        </button>
                    </div>
                </div>
            </div>
            <textarea id="comment_text" name="comment_text">Hello, World!</textarea>
        </div>
        <input type="hidden" id="text_align" name="text_align" value="center">
        <input type="hidden" id="text_valign" name="text_valign" value="center">
        <input type="hidden" id="text_color" name="text_color" value="#000000">
        <input type="hidden" id="font_size" name="font_size" value="<?php echo YCOS_DEFAULT_FONT_SIZE; ?>">
        <input type="hidden" id="font_family" name="font_family" value="12">
    </div>
    <?php
}

/**
 * ADD "GO TO CART" BUTTON AFTER THE FORM
 * 
 * Hook: woocommerce_after_add_to_cart_form
 * Purpose: Adds a "Go To Cart" button after the closing </form> tag
 */
add_action('woocommerce_after_add_to_cart_form', 'ycos_add_go_to_cart_button');
function ycos_add_go_to_cart_button() {
    $cart_url = wc_get_cart_url();
    ?>
    <div class="ycos-go-to-cart-wrapper">
        <a href="<?php echo esc_url($cart_url); ?>" class="button ycos-go-to-cart-button" id="ycos-go-to-cart-btn">
            Buy Now
        </a>
    </div>
    <?php
}

/**
 * GENERATE CONTENT HASH FROM IMAGE PARAMETERS
 * 
 * Purpose: Creates deterministic hash for image deduplication
 * Uses MD5 algorithm (same as Node.js) for consistency
 * 
 * @param array $params Array of image generation parameters
 * @param string $prefix 'product' or 'design' to determine which params to include
 * @return string 32-character MD5 hash (not used for filenames yet, just logged)
 */
function ycos_generate_image_hash_preview($params, $prefix = 'product') {
    // Extract parameters with defaults (matching Node.js defaults)
    $comment_text = isset($params['comment_text']) && $params['comment_text'] !== '' ? $params['comment_text'] : '';
    $text_color = isset($params['text_color']) && $params['text_color'] !== '' ? $params['text_color'] : '#000000';
    $text_align = isset($params['text_align']) && $params['text_align'] !== '' ? $params['text_align'] : 'center';
    $text_valign = isset($params['text_valign']) && $params['text_valign'] !== '' ? $params['text_valign'] : 'center';
    $font_size = isset($params['font_size']) && $params['font_size'] !== '' ? $params['font_size'] : '120';
    $font_family = isset($params['font_family']) && $params['font_family'] !== '' ? $params['font_family'] : '12';
    $shirt_color = isset($params['shirt_color']) && $params['shirt_color'] !== '' ? $params['shirt_color'] : '';
    
    // Build hash input string (pipe-separated)
    // For product images, include shirtColor; for design masks, exclude it
    if ($prefix === 'product') {
        $hash_input = "{$comment_text}|{$text_color}|{$text_align}|{$text_valign}|{$font_size}|{$font_family}|{$shirt_color}";
    } else {
        // Design masks don't include shirt color (they're transparent)
        $hash_input = "{$comment_text}|{$text_color}|{$text_align}|{$text_valign}|{$font_size}|{$font_family}";
    }
    
    // Generate MD5 hash (32 characters)
    $hash = md5($hash_input);
    
    return $hash;
}

/**
 * AJAX HANDLER TO GET CART COUNT
 * 
 * Hooks: wp_ajax_get_cart_count (logged-in users)
 *        wp_ajax_nopriv_get_cart_count (guest users)
 * Purpose: Returns the number of items in the cart for logging purposes
 */
add_action('wp_ajax_get_cart_count', 'ycos_get_cart_count');
add_action('wp_ajax_nopriv_get_cart_count', 'ycos_get_cart_count');
function ycos_get_cart_count() {
    $cart_count = WC()->cart->get_cart_contents_count();
    $is_empty = WC()->cart->is_empty();
    
    wp_send_json_success([
        'cart_count' => $cart_count,
        'is_empty' => $is_empty
    ]);
}

/**
 * AJAX HANDLER FOR SAVING COMMENT TEXT
 * 
 * Hooks: wp_ajax_save_comment_text (logged-in users)
 *        wp_ajax_nopriv_save_comment_text (guest users)
 * Purpose: Handles AJAX requests to save comment text to WooCommerce session
 * This is called when users type in the comment field or when URL parameters populate the field
 * The session storage ensures the comment persists for Apple Pay, Google Pay, and other payment methods
 */
add_action('wp_ajax_save_comment_text', 'my_save_comment_text');
add_action('wp_ajax_nopriv_save_comment_text', 'my_save_comment_text');
function my_save_comment_text() {
    if (isset($_POST['product_id'], $_POST['comment_text'])) {
        $product_id   = absint($_POST['product_id']);
        $comment_text = sanitize_text_field($_POST['comment_text']);

        // Store in Woo session with product-specific key
        WC()->session->set('comment_text_' . $product_id, $comment_text);

        wp_send_json_success(['message' => 'Saved']);
    }
    wp_send_json_error(['message' => 'Missing data']);
}

/**
 * AJAX HANDLER FOR SAVING TEXT ALIGNMENT
 * 
 * Hooks: wp_ajax_save_text_alignment (logged-in users)
 *        wp_ajax_nopriv_save_text_alignment (guest users)
 * Purpose: Handles AJAX requests to save text alignment to WooCommerce session
 * This ensures alignment persists through cart and checkout for all payment methods
 */
add_action('wp_ajax_save_text_alignment', 'ycos_save_text_alignment');
add_action('wp_ajax_nopriv_save_text_alignment', 'ycos_save_text_alignment');
function ycos_save_text_alignment() {
    if (isset($_POST['product_id'], $_POST['text_align'])) {
        $product_id = absint($_POST['product_id']);
        $text_align = sanitize_text_field($_POST['text_align']);
        
        // Validate alignment value
        if (!in_array($text_align, ['left', 'center', 'right'])) {
            wp_send_json_error(['message' => 'Invalid alignment value']);
        }

        // Store in Woo session with product-specific key
        WC()->session->set('text_align_' . $product_id, $text_align);

        wp_send_json_success(['message' => 'Alignment saved']);
    }
    wp_send_json_error(['message' => 'Missing data']);
}

/**
 * AJAX HANDLER FOR SAVING VERTICAL TEXT ALIGNMENT
 * 
 * Hooks: wp_ajax_save_text_valignment (logged-in users)
 *        wp_ajax_nopriv_save_text_valignment (guest users)
 * Purpose: Handles AJAX requests to save vertical text alignment to WooCommerce session
 * This ensures vertical alignment persists through cart and checkout for all payment methods
 */
add_action('wp_ajax_save_text_valignment', 'ycos_save_text_valignment');
add_action('wp_ajax_nopriv_save_text_valignment', 'ycos_save_text_valignment');
function ycos_save_text_valignment() {
    if (isset($_POST['product_id'], $_POST['text_valign'])) {
        $product_id = absint($_POST['product_id']);
        $text_valign = sanitize_text_field($_POST['text_valign']);
        
        // Validate vertical alignment value
        if (!in_array($text_valign, ['flex-start', 'center', 'flex-end'])) {
            wp_send_json_error(['message' => 'Invalid vertical alignment value']);
        }

        // Store in Woo session with product-specific key
        WC()->session->set('text_valign_' . $product_id, $text_valign);

        wp_send_json_success(['message' => 'Vertical alignment saved']);
    }
    wp_send_json_error(['message' => 'Missing data']);
}

/**
 * AJAX HANDLER FOR SAVING FONT SIZE
 * 
 * Hooks: wp_ajax_save_font_size (logged-in users)
 *        wp_ajax_nopriv_save_font_size (guest users)
 * Purpose: Handles AJAX requests to save font size to WooCommerce session
 * This ensures font size persists through cart and checkout for all payment methods
 */
add_action('wp_ajax_save_font_size', 'ycos_save_font_size');
add_action('wp_ajax_nopriv_save_font_size', 'ycos_save_font_size');
function ycos_save_font_size() {
    if (isset($_POST['product_id'], $_POST['font_size'])) {
        $product_id = absint($_POST['product_id']);
        // Check if font_size can be parsed as an integer
        $font_size_raw = $_POST['font_size'];
        $font_size = filter_var($font_size_raw, FILTER_VALIDATE_INT);
        
        // If not a valid integer, use default
        if ($font_size === false) {
            $font_size = YCOS_DEFAULT_FONT_SIZE;
        } elseif ($font_size < YCOS_MIN_FONT_SIZE) {
            // Clamp to minimum if below min
            $font_size = YCOS_MIN_FONT_SIZE;
        } elseif ($font_size > YCOS_MAX_FONT_SIZE) {
            // Clamp to maximum if above max
            $font_size = YCOS_MAX_FONT_SIZE;
        }

        // Store in Woo session with product-specific key
        WC()->session->set('font_size_' . $product_id, $font_size);

        wp_send_json_success(['message' => 'Font size saved']);
    }
    wp_send_json_error(['message' => 'Missing data']);
}

/**
 * AJAX HANDLER FOR SAVING FONT FAMILY
 * 
 * Hooks: wp_ajax_save_font_family (logged-in users)
 *        wp_ajax_nopriv_save_font_family (guest users)
 * Purpose: Handles AJAX requests to save font family (as index) to WooCommerce session
 * This ensures font family persists through cart and checkout for all payment methods
 */
add_action('wp_ajax_save_font_family', 'ycos_save_font_family');
add_action('wp_ajax_nopriv_save_font_family', 'ycos_save_font_family');
function ycos_save_font_family() {
    if (isset($_POST['product_id'], $_POST['font_family'])) {
        $product_id = absint($_POST['product_id']);
        $font_family = absint($_POST['font_family']);
        
        // Validate font family index (0-20 for 21 fonts)
        if ($font_family < 0 || $font_family > 20) {
            wp_send_json_error(['message' => 'Invalid font family index']);
        }

        // Store in Woo session with product-specific key
        WC()->session->set('font_family_' . $product_id, $font_family);

        wp_send_json_success(['message' => 'Font family saved']);
    }
    wp_send_json_error(['message' => 'Missing data']);
}

/**
 * AJAX HANDLER FOR SAVING TEXT COLOR
 * 
 * Hooks: wp_ajax_save_text_color (logged-in users)
 *        wp_ajax_nopriv_save_text_color (guest users)
 * Purpose: Handles AJAX requests to save text color to WooCommerce session
 * This ensures text color persists through cart and checkout for all payment methods
 */
add_action('wp_ajax_save_text_color', 'ycos_save_text_color');
add_action('wp_ajax_nopriv_save_text_color', 'ycos_save_text_color');
function ycos_save_text_color() {
    if (isset($_POST['product_id'], $_POST['text_color'])) {
        $product_id = absint($_POST['product_id']);
        $text_color = sanitize_text_field($_POST['text_color']);
        
        // Validate color format (hex color)
        if (!preg_match('/^#[a-f0-9]{6}$/i', $text_color)) {
            wp_send_json_error(['message' => 'Invalid color format']);
        }

        // Store in Woo session with product-specific key
        WC()->session->set('text_color_' . $product_id, $text_color);

        wp_send_json_success(['message' => 'Text color saved']);
    }
    wp_send_json_error(['message' => 'Missing data']);
}

/**
 * JAVASCRIPT FOR COMMENT FIELD FUNCTIONALITY
 * 
 * Hook: wp_footer
 * Purpose: Adds JavaScript to handle comment field interactions and URL parameter support
 * Features:
 * - Populates comment field from URL parameter (?comment=text) on page load
 * - Saves comment text to session when user types or when populated from URL
 * - Handles URL decoding for special characters
 */
// Enqueue html2canvas script for image generation
add_action('wp_enqueue_scripts', 'ycos_enqueue_html2canvas');
function ycos_enqueue_html2canvas() {
    if (is_product() || is_page() || is_front_page() || is_home() || is_cart()) {
        wp_enqueue_script('html2canvas', 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js', ['jquery'], '1.4.1', true);
    }
}

// Enqueue plugin styles
add_action('wp_enqueue_scripts', 'ycos_enqueue_styles');
function ycos_enqueue_styles() {
    if (is_product() || is_page() || is_front_page() || is_home() || is_cart()) {
        // Variation swatches styles
        wp_enqueue_style(
            'ycos-variation-swatches',
            plugin_dir_url(__FILE__) . 'assets/css/variation-swatches.css',
            array(),
            '1.0.0'
        );
        
        // Additional custom styles
        wp_enqueue_style(
            'ycos-additional-styles',
            plugin_dir_url(__FILE__) . 'assets/css/additional-styles.css',
            array(),
            '1.0.0'
        );
    }
}

/**
 * ADD COMMENT MASK OVERLAYS TO PRODUCT PAGE
 * 
 * Creates HTML elements that display the user's text over:
 * 1. The main product image (large display)
 * 2. The first thumbnail in the gallery carousel
 * Both are positioned absolutely and styled via CSS.
 * Only for product page display - cart images use pre-generated images.
 */
add_action('wp_footer', 'ycos_add_comment_mask_overlay');
function ycos_add_comment_mask_overlay() {
    if (!is_product()) {
        return; // Only add on product pages
    }
    ?>
    <p id="comment-mask-product"></p>
    <p id="comment-mask-thumbnail"></p>
    <?php
}

add_action('wp_footer', 'my_comment_textbox_js');
function my_comment_textbox_js() {
    // Load on product pages and any page that has the comment field
    // This ensures the JavaScript works wherever the comment field is placed
    if (!is_product() && !is_page() && !is_front_page() && !is_home() && !is_cart()) {
        return; // only load on product pages, regular pages, home page, and cart page
    }
    ?>
    <script type="text/javascript">
    (function($){
        // Global font size configuration
        var YCOS_MIN_FONT_SIZE = <?php echo YCOS_MIN_FONT_SIZE; ?>;
        var YCOS_MAX_FONT_SIZE = <?php echo YCOS_MAX_FONT_SIZE; ?>;
        var YCOS_DEFAULT_FONT_SIZE = <?php echo YCOS_DEFAULT_FONT_SIZE; ?>;
        
        // Available fonts array (alphabetically sorted)
        var availableFonts = [
            'Annie Use Your Telescope',
            'Asset',
            'BBH Sans Bartle',
            'BBH Sans Bogle',
            'BBH Sans Hegarty',
            'Butcherman',
            'Creepster',
            'Domine',
            'Inter',
            'Jolly Lodger',
            'Kablammo',
            'Nosifer',
            'NotoSans',
            'Oooh Baby',
            'Open Sans',
            'Orbitron',
            'Playwrite AU TAS',
            'Press Start 2P',
            'Roboto',
            'Rubik Puddles',
            'Trade Winds'
        ];
        
        // Function to safely extract URL parameters
        function getUrlParameter(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }
        
        // Function to position comment masks on main image and thumbnail
        function moveCommentMask() {
            // Position on main product image
            var $mainImage = $('[data-block-name="woocommerce/product-image"]').first(); 
            var $commentMask = $('#comment-mask-product');
            $mainImage.append($commentMask);
            
            // Position on first thumbnail in carousel
            var $firstThumbnail = $('.wc-block-product-gallery-thumbnails__thumbnail').first();
            var $thumbnailMask = $('#comment-mask-thumbnail');
            if ($firstThumbnail.length > 0) {
                $firstThumbnail.css('position', 'relative'); // Ensure thumbnail is positioned
                $firstThumbnail.append($thumbnailMask);
            }
        }
        
        // Color mapping data
        var colorMapping = {
            "black": "#141313",
            "gold": "#ffb22d", 
            "irish-green": "#00ba69",
            "orange": "#ff5f2e",
            "red": "#d80019",
            "royal": "#175ac7",
            "white": "#fffefa"
        };
        
        // Function to apply color to product images
        function applyColorToImages(selectedColor, selectedHex) {
            if (selectedHex) {                
                // Set background color for thumbnail images
                $('.wc-block-product-gallery-thumbnails__thumbnail__image').css('background-color', selectedHex);
                
                // Set background color for main product image
                $('[data-testid="product-image"]').css('background-color', selectedHex);
            }
        }
        
        // Function to check for initially selected color
        function checkInitialColorSelection() {
            // Check URL parameter first (most reliable)
            var selectedColor = getUrlParameter('attribute_pa_color');
            
            // Fallback to select dropdown value if no URL param
            if (!selectedColor) {
                var $selectElement = $('select[name="attribute_pa_color"]');
                if ($selectElement.length > 0 && $selectElement.val()) {
                    selectedColor = $selectElement.val();
                }
            }
            
            // Apply the color if found
            if (selectedColor && colorMapping[selectedColor]) {
                applyColorToImages(selectedColor, colorMapping[selectedColor]);
                updateUrlParameter('attribute_pa_color', selectedColor);
            }
        }
        
        // Function to handle color attribute changes
        function handleColorAttributeChange() {
            // Apply initial color from URL or select value
            checkInitialColorSelection();
            
            // Listen for select dropdown changes
            $('select[name="attribute_pa_color"]').on('change', function() {
                var selectedColor = $(this).val();
                if (selectedColor && colorMapping[selectedColor]) {
                    applyColorToImages(selectedColor, colorMapping[selectedColor]);
                    updateUrlParameter('attribute_pa_color', selectedColor);
                }
            });
        }
        
        // Function to handle size attribute changes
        function handleSizeAttributeChange() {
            // Check URL parameter first
            var selectedSize = getUrlParameter('attribute_pa_size');
            
            // Apply initial size from URL if present
            if (selectedSize) {
                var $selectElement = $('select[name="attribute_pa_size"]');
                if ($selectElement.length > 0) {
                    $selectElement.val(selectedSize).trigger('change');
                }
                updateUrlParameter('attribute_pa_size', selectedSize);
            }
            
            // Listen for select dropdown changes
            $('select[name="attribute_pa_size"]').on('change', function() {
                var selectedSize = $(this).val();
                if (selectedSize) {
                    updateUrlParameter('attribute_pa_size', selectedSize);
                }
            });
        }
        
        // Function to create variation buttons from select elements
        function createVariationButtons() {
            $('.variations select').each(function() {
                var $select = $(this);
                var $td = $select.closest('td');
                var attributeName = $select.attr('name');
                var isColorAttribute = attributeName && attributeName.indexOf('color') !== -1;
                
                // Check if buttons already exist
                if ($td.find('.ycos-variation-buttons').length > 0) {
                    return;
                }
                
                // Create container for buttons
                var $buttonsContainer = $('<div class="ycos-variation-buttons"></div>');
                
                // Create a button for each option (skip the first "Choose an option")
                $select.find('option').each(function(index) {
                    if (index === 0 && $(this).val() === '') {
                        return; // Skip the placeholder option
                    }
                    
                    var $option = $(this);
                    var value = $option.val();
                    var label = $option.text();
                    var isSelected = $option.is(':selected');
                    
                    var $button = $('<button type="button" class="ycos-variation-button"></button>')
                        .attr('data-value', value)
                        .attr('data-attribute', attributeName);
                    
                    // Special handling for color attributes
                    if (isColorAttribute) {
                        $button.addClass('color-button');
                        var hexColor = colorMapping[value] || '#ccc';
                        $button.html('<div class="color-swatch" style="background-color: ' + hexColor + ';"></div>');
                        $button.attr('title', label);
                        $button.attr('aria-label', label);
                    } else {
                        $button.addClass('ycos-size-variation');
                        $button.text(label);
                    }
                    
                    if (isSelected) {
                        $button.addClass('selected');
                    }
                    
                    // Click handler
                    $button.on('click', function(e) {
                        e.preventDefault();
                        
                        var $btn = $(this);
                        var btnValue = $btn.attr('data-value');
                        var btnAttribute = $btn.attr('data-attribute');
                        
                        // Remove selected class from siblings
                        $btn.siblings('.ycos-variation-button').removeClass('selected');
                        
                        // Add selected class to this button
                        $btn.addClass('selected');
                        
                        // Update the hidden select
                        var $targetSelect = $('select[name="' + btnAttribute + '"]');
                        $targetSelect.val(btnValue).trigger('change');
                        
                        // If this is a color change, apply the color to images and update URL
                        if (isColorAttribute) {
                            var selectedHex = colorMapping[btnValue];
                            applyColorToImages(btnValue, selectedHex);
                            updateUrlParameter('attribute_pa_color', btnValue);
                        } else if (btnAttribute === 'attribute_pa_size') {
                            // If this is a size change, update URL parameter
                            updateUrlParameter('attribute_pa_size', btnValue);
                        }
                    });
                    
                    $buttonsContainer.append($button);
                });
                
                // Insert buttons after the select
                $select.after($buttonsContainer);
            });
        }
        
        // Function to update button states when variations change
        function updateVariationButtons() {
            $('.variations select').each(function() {
                var $select = $(this);
                var selectedValue = $select.val();
                var attributeName = $select.attr('name');
                var $buttons = $('.ycos-variation-button[data-attribute="' + attributeName + '"]');
                
                $buttons.each(function() {
                    var $btn = $(this);
                    var btnValue = $btn.attr('data-value');
                    
                    if (btnValue === selectedValue) {
                        $btn.addClass('selected');
                    } else {
                        $btn.removeClass('selected');
                    }
                });
            });
        }
        
        // Helper function to get product ID with fallbacks
        function getProductId() {
            // Try multiple methods to get product ID
            var productId = $('form.cart').find('input[name=add-to-cart]').val();
            if (productId) return productId;
            
            productId = $('form.cart').find('input[name=product_id]').val();
            if (productId) return productId;
            
            productId = $('form.cart').data('product_id');
            if (productId) return productId;
            
            productId = $('form.variations_form').data('product_id');
            if (productId) return productId;
            
            console.error('YourCommentOnAShirt: Could not find product ID');
            return null;
        }
        
        // Helper function to get currently selected shirt color
        function getSelectedShirtColor() {
            // Try URL parameter first
            var colorSlug = getUrlParameter('attribute_pa_color');
            
            // Fallback to select dropdown value if no URL param
            if (!colorSlug) {
                var $selectElement = $('select[name="attribute_pa_color"]');
                if ($selectElement.length > 0) {
                    colorSlug = $selectElement.val();
                }
            }
            
            // Convert color slug to hex value using colorMapping
            if (colorSlug && colorMapping[colorSlug]) {
                return colorMapping[colorSlug];
            }
            
            // Default to empty string if no color selected
            return '';
        }
        
        // Function to update URL parameter
        function updateUrlParameter(param, value) {
            var url = new URL(window.location.href);
            
            // Convert value to string for checking
            var stringValue = (value !== null && value !== undefined) ? String(value) : '';
            
            if (stringValue && stringValue.trim() !== '') {
                url.searchParams.set(param, stringValue);
            } else {
                url.searchParams.delete(param);
            }
            
            // Update URL without reloading the page
            window.history.replaceState({}, '', url.toString());
        }
        
        // Shared function to save and update comment text
        function saveAndUpdateComment(text) {
            var productId = getProductId();
            
            // Update both comment masks (main image and thumbnail) with the same text
            $('#comment-mask-product, #comment-mask-thumbnail').text(text);
            
            // Update URL parameter so users can share the link
            updateUrlParameter('comment', text);
            
            if (productId && text) {
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'save_comment_text',
                    product_id: productId,
                    comment_text: text
                });
            }
            
            // Capture BOTH images whenever the comment changes
            captureCommentMaskForPrintful(function(printfulImageData) {
                // After Printful image is generated, generate the cart display image
                captureFullProductImageForOrder();
            });
        }
        
        // Function to update text color
        function updateTextColor(color) {
            var commentMasks = $('#comment-mask-product, #comment-mask-thumbnail');
            commentMasks.css('color', color);
            $('#text_color').val(color);
            
            // Update URL parameter so users can share the link with the color
            updateUrlParameter('color', color);
            
            // Save text color to session
            var productId = getProductId();
            if (productId) {
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'save_text_color',
                    product_id: productId,
                    text_color: color
                });
            }
        }
        
        // Function to update text alignment
        function updateTextAlignment(alignment) {
            var commentMasks = $('#comment-mask-product, #comment-mask-thumbnail');
            
            // Map alignment values to justify-content values for flex containers
            var justifyValue = alignment;
            if (alignment === 'left') {
                justifyValue = 'flex-start';
            } else if (alignment === 'center') {
                justifyValue = 'center';
            } else if (alignment === 'right') {
                justifyValue = 'flex-end';
            }
            
            commentMasks.css('justify-content', justifyValue);
            commentMasks.css('text-align', alignment);
            $('#text_align').val(alignment);
            
            // Update URL parameter so users can share the link with the alignment
            updateUrlParameter('align', alignment);
            
            // Save alignment to session
            var productId = getProductId();
            if (productId) {
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'save_text_alignment',
                    product_id: productId,
                    text_align: alignment
                });
            }
        }
        
        // Function to update vertical alignment
        function updateVerticalAlignment(valignment) {
            var commentMasks = $('#comment-mask-product, #comment-mask-thumbnail');
            
            // Map button values to valid CSS align-content values
            var cssValue = valignment;
            if (valignment === 'top') {
                cssValue = 'flex-start';
            } else if (valignment === 'center') {
                cssValue = 'center';
            } else if (valignment === 'bottom') {
                cssValue = 'flex-end';
            }
            
            // Set both align-content and align-items for proper vertical alignment
            commentMasks.css({
                'align-content': cssValue,
                'align-items': cssValue
            });
            $('#text_valign').val(cssValue);
            
            // Update URL parameter so users can share the link with the vertical alignment
            updateUrlParameter('valign', cssValue);
            
            // Save vertical alignment to session
            var productId = getProductId();
            if (productId) {
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'save_text_valignment',
                    product_id: productId,
                    text_valign: cssValue
                });
            }
        }
        
        // Function to calculate and apply scaled font size
        function applyScaledFontSize(realFontSize) {
            var commentMasks = $('#comment-mask-product, #comment-mask-thumbnail');
            if (commentMasks.length === 0) {
                return;
            }
            
            // Apply scaled font size to each mask individually (they may have different widths)
            commentMasks.each(function() {
                var $mask = $(this);
                // Get the actual width of this specific comment mask
                var trueWidth = $mask[0].offsetWidth;
                
                // Image generation dimensions
                var imageWidth = 1800;
                
                // Calculate scale factor
                var scaleFactor = trueWidth / imageWidth;
                
                // Calculate and apply scaled font size
                var scaledFontSize = realFontSize * scaleFactor;
                $mask.css('font-size', scaledFontSize + 'px');
            });
        }
        
        // Function to update font size
        // Function to update font size visually only (no image regeneration)
        function updateFontSizeVisual(fontSize) {
            // Don't validate the text just try to apply the font size
            
            // Update the display input
            $('#font_size_display').val(fontSize);
            
            // Apply scaled font size to comment mask
            applyScaledFontSize(fontSize);
        }
        
        function updateFontSize(fontSize) {
            // Validate font size
            fontSize = parseInt(fontSize);
            if (isNaN(fontSize)) {
                fontSize = YCOS_DEFAULT_FONT_SIZE; // Default if not a number
            } else if (fontSize < YCOS_MIN_FONT_SIZE) {
                fontSize = YCOS_MIN_FONT_SIZE; // Clamp to minimum
            } else if (fontSize > YCOS_MAX_FONT_SIZE) {
                fontSize = YCOS_MAX_FONT_SIZE; // Clamp to maximum
            }
            
            // Update the display input
            $('#font_size_display').val(fontSize);
            
            // Store the real font size
            $('#font_size').val(fontSize);
            
            // Apply scaled font size to comment mask
            applyScaledFontSize(fontSize);
            
            // Update URL parameter
            updateUrlParameter('fontsize', fontSize);
            
            // Save font size to session
            var productId = getProductId();
            if (productId) {
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'save_font_size',
                    product_id: productId,
                    font_size: fontSize
                });
            }
            
            // Regenerate and save both images with the new font size
            captureCommentMaskForPrintful(function(printfulImageData) {
                captureFullProductImageForOrder();
            });
        }
        
        // Function to initialize text color button
        function initializeTextColorButton() {
            // Handle color display button click - open color picker
            $('#ycos-color-display').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                // For iOS Safari, we need to trigger the input directly
                // The input is positioned over the button, so clicking the button
                // should also trigger the input, but we'll programmatically trigger it too
                var colorPicker = $('#text_color_picker')[0];
                if (colorPicker) {
                    // Use native click for better iOS Safari support
                    colorPicker.click();
                }
            });
            
            // Also allow direct clicks on the color input (for iOS Safari)
            $('#text_color_picker').on('click', function(e) {
                e.stopPropagation();
            });
            
            // Handle color picker input (live preview while dragging)
            $('#text_color_picker').on('input', function() {
                var selectedColor = $(this).val();
                // Update the display swatch
                $('.ycos-color-swatch').css('background-color', selectedColor);
                // Update the comment mask color
                updateTextColor(selectedColor);
            });

            // Handle color picker change (final selection)
            $('#text_color_picker').on('change', function() {
                var selectedColor = $(this).val();
                // Update the display swatch
                $('.ycos-color-swatch').css('background-color', selectedColor);
                // Update the comment mask color
                updateTextColor(selectedColor);
                // Regenerate and save both images with the new text color
                captureCommentMaskForPrintful(function(printfulImageData) {
                    captureFullProductImageForOrder();
                });
            });
        }
        
        // Function to initialize text alignment buttons
        function initializeAlignmentButtons() {
            // Handle horizontal alignment button clicks
            $('.ycos-align-button').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $dropdown = $button.closest('.ycos-alignment-dropdown');
                var alignment = $button.attr('data-align');
                
                // If clicking the selected button, toggle dropdown
                if ($button.hasClass('selected')) {
                    $dropdown.toggleClass('expanded');
                    return;
                }
                
                // Otherwise, it's a selection change
                // Remove selected class from all buttons in this dropdown
                $dropdown.find('.ycos-align-button').removeClass('selected');
                
                // Add selected class to clicked button
                $button.addClass('selected');
                
                // Collapse the dropdown
                $dropdown.removeClass('expanded');
                
                // Update the comment mask alignment
                updateTextAlignment(alignment);
                
                // Regenerate and save both images with the new alignment
                captureCommentMaskForPrintful(function(printfulImageData) {
                    captureFullProductImageForOrder();
                });
            });
            
            // Handle vertical alignment button clicks
            $('.ycos-valign-button').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $dropdown = $button.closest('.ycos-alignment-dropdown');
                var valignment = $button.attr('data-valign');
                
                // If clicking the selected button, toggle dropdown
                if ($button.hasClass('selected')) {
                    $dropdown.toggleClass('expanded');
                    return;
                }
                
                // Otherwise, it's a selection change
                // Remove selected class from all buttons in this dropdown
                $dropdown.find('.ycos-valign-button').removeClass('selected');
                
                // Add selected class to clicked button
                $button.addClass('selected');
                
                // Collapse the dropdown
                $dropdown.removeClass('expanded');
                
                // Update the comment mask vertical alignment
                updateVerticalAlignment(valignment);
                
                // Regenerate and save both images with the new vertical alignment
                captureCommentMaskForPrintful(function(printfulImageData) {
                    captureFullProductImageForOrder();
                });
            });
            
            // Close dropdowns when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.ycos-alignment-dropdown').length) {
                    $('.ycos-alignment-dropdown').removeClass('expanded');
                }
            });
        }
        
        // Timeout variable for debouncing font size button clicks
        var fontSizeUpdateTimeout = null;
        
        // Function to initialize font size control
        function initializeFontSizeControl() {
            // Handle decrease button
            $('#ycos-font-decrease').on('click', function(e) {
                e.preventDefault();
                
                // Clear any pending timeout
                if (fontSizeUpdateTimeout) {
                    clearTimeout(fontSizeUpdateTimeout);
                    fontSizeUpdateTimeout = null;
                }
                
                var currentSize = parseInt($('#font_size_display').val());
                if (isNaN(currentSize)) currentSize = YCOS_DEFAULT_FONT_SIZE;
                var newSize = Math.max(YCOS_MIN_FONT_SIZE, currentSize - 1);
                
                // Update visual display immediately for responsive feedback
                updateFontSizeVisual(newSize);
                
                // Debounce the full update with image regeneration - wait 500ms before applying
                fontSizeUpdateTimeout = setTimeout(function() {
                    updateFontSize(newSize);
                    fontSizeUpdateTimeout = null;
                }, 500);
            });
            
            // Handle increase button
            $('#ycos-font-increase').on('click', function(e) {
                e.preventDefault();
                
                // Clear any pending timeout
                if (fontSizeUpdateTimeout) {
                    clearTimeout(fontSizeUpdateTimeout);
                    fontSizeUpdateTimeout = null;
                }
                
                var currentSize = parseInt($('#font_size_display').val());
                if (isNaN(currentSize)) currentSize = YCOS_DEFAULT_FONT_SIZE;
                var newSize = Math.min(YCOS_MAX_FONT_SIZE, currentSize + 1);
                
                // Update visual display immediately for responsive feedback
                updateFontSizeVisual(newSize);
                
                // Debounce the full update with image regeneration - wait 500ms before applying
                fontSizeUpdateTimeout = setTimeout(function() {
                    updateFontSize(newSize);
                    fontSizeUpdateTimeout = null;
                }, 500);
            });
            
            // Handle direct input (live preview while typing)
            $('#font_size_display').on('input', function() {
                var newSize = parseInt($(this).val());
                updateFontSizeVisual(newSize);
            });
            
            // Handle direct input change (final value - regenerate images)
            $('#font_size_display').on('change', function() {
                var newSize = parseInt($(this).val());
                updateFontSize(newSize);
            });
            
            // Handle input validation on keypress
            $('#font_size_display').on('keypress', function(e) {
                // Allow only numbers
                if (e.which < 48 || e.which > 57) {
                    e.preventDefault();
                }
            });
        }
        
        // Function to update font family
        function updateFontFamily(fontIndex) {
            // Validate font index
            fontIndex = parseInt(fontIndex);
            if (isNaN(fontIndex) || fontIndex < 0 || fontIndex >= availableFonts.length) {
                fontIndex = 12; // Default to NotoSans
            }
            
            var fontFamily = availableFonts[fontIndex];
            
            // Update both comment masks font
            var commentMasks = $('#comment-mask-product, #comment-mask-thumbnail');
            commentMasks.css('font-family', "'" + fontFamily + "', sans-serif");
            
            // Update the display button
            $('.ycos-font-name').text(fontFamily).css('font-family', "'" + fontFamily + "', sans-serif");
            
            // Store the font index
            $('#font_family').val(fontIndex);
            
            // Update URL parameter with font index
            updateUrlParameter('font', fontIndex);
            
            // Save font family to session
            var productId = getProductId();
            if (productId) {
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'save_font_family',
                    product_id: productId,
                    font_family: fontIndex
                });
            }
            
            // Regenerate and save both images with the new font
            captureCommentMaskForPrintful(function(printfulImageData) {
                captureFullProductImageForOrder();
            });
        }
        
        // Function to preload all fonts for instant dropdown rendering
        function preloadAllFonts() {
            // Use the Font Loading API if available (modern browsers)
            // This is more efficient than creating DOM elements
            if (document.fonts && document.fonts.load) {
                var fontPromises = availableFonts.map(function(font) {
                    // Load the font with a specific size to trigger download
                    return document.fonts.load('16px "' + font + '"');
                });
                
                // Wait for all fonts to load (non-blocking)
                Promise.all(fontPromises).catch(function(error) {
                    console.warn('Some fonts failed to preload:', error);
                });
            }
            
            // Create the dropdown options now to pre-render them
            // This ensures they're ready when the dropdown opens
            var $fontOptions = $('#ycos-font-options');
            availableFonts.forEach(function(font, index) {
                var $option = $('<div class="ycos-font-option"></div>')
                    .text(font)
                    .css('font-family', "'" + font + "', sans-serif")
                    .attr('data-font-index', index);
                $fontOptions.append($option);
            });
            
            // Force layout on dropdown options to trigger font loading
            // This ensures fonts load even if Font Loading API isn't available
            $fontOptions[0] && ($fontOptions[0].offsetHeight);
        }
        
        // Function to initialize font dropdown
        function initializeFontDropdown() {
            var $fontOptions = $('#ycos-font-options');
            var $fontDisplayButton = $('#ycos-font-display');
            
            // Attach click handlers to pre-populated options
            $fontOptions.find('.ycos-font-option').on('click', function() {
                var selectedIndex = $(this).attr('data-font-index');
                
                // Remove selected class from all options
                $fontOptions.find('.ycos-font-option').removeClass('selected');
                
                // Add selected class to this option
                $(this).addClass('selected');
                
                // Update the font
                updateFontFamily(selectedIndex);
                
                // Hide the dropdown
                $fontOptions.removeClass('show');
            });
            
            // Handle display button click - toggle dropdown
            $fontDisplayButton.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $fontOptions.toggleClass('show');
            });
            
            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.ycos-font-dropdown').length) {
                    $fontOptions.removeClass('show');
                }
            });
            
            // Set default font name immediately if no URL parameter exists
            var fontParam = getUrlParameter('font');
            if (!fontParam) {
                var defaultFontIndex = parseInt($('#font_family').val()) || 12; // Default to NotoSans (index 12)
                if (!isNaN(defaultFontIndex) && defaultFontIndex >= 0 && defaultFontIndex < availableFonts.length) {
                    var defaultFontFamily = availableFonts[defaultFontIndex];
                    $('.ycos-font-name').text(defaultFontFamily).css('font-family', "'" + defaultFontFamily + "', sans-serif");
                    // Mark the default option as selected
                    $fontOptions.find('.ycos-font-option[data-font-index="' + defaultFontIndex + '"]').addClass('selected');
                }
            }
        }
        
        // Function to handle "Buy Now" button click
        function initializeGoToCartButton() {
            $('#ycos-go-to-cart-btn').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var cartUrl = $button.attr('href');
                                
                // Get form data from the add to cart form
                var $form = $('form.cart');
                if ($form.length === 0) {
                    console.error('YourCommentOnAShirt: Could not find product form');
                    window.location.href = cartUrl;
                    return;
                }
                
                // Serialize form data (includes all variations and custom fields)
                var formData = $form.serialize();
                
                // Get the form action URL (usually the current page)
                var formAction = $form.attr('action') || window.location.href;
                
                console.log('YourCommentOnAShirt: Submitting form data via AJAX...');
                
                // Always add product to cart via AJAX, then navigate to cart
                $.post(formAction + '?add-to-cart=' + getProductId(), formData, function(response) {
                    console.log('YourCommentOnAShirt: Product successfully added to cart');
                    // Navigate to cart page
                    window.location.href = cartUrl;
                }).fail(function(xhr, status, error) {
                    console.error('YourCommentOnAShirt: Failed to add product to cart:', status, error);
                    // Try navigating to cart anyway
                    window.location.href = cartUrl;
                });
            });
        }
        
        // Populate comment field from URL parameter on page load
        $(document).ready(function() {
            var commentParam = getUrlParameter('comment');
            if (commentParam) {
                $('#comment_text').val(commentParam);
            }
            
            // Populate text color from URL parameter on page load
            var colorParam = getUrlParameter('color');
            if (colorParam) {
                updateTextColor(colorParam);
                $('#text_color_picker').val(colorParam);
                $('.ycos-color-swatch').css('background-color', colorParam);
            } else {
                // If no URL param, save the default color value to session
                var defaultColor = $('#text_color').val();
                if (defaultColor) {
                    var productId = getProductId();
                    if (productId) {
                        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                            action: 'save_text_color',
                            product_id: productId,
                            text_color: defaultColor
                        });
                    }
                }
            }
            
            // Populate text alignment from URL parameter on page load
            var alignParam = getUrlParameter('align');
            if (alignParam) {
                // Map to justify-content value
                var justifyValue = 'center';
                if (alignParam === 'left') {
                    justifyValue = 'flex-start';
                } else if (alignParam === 'right') {
                    justifyValue = 'flex-end';
                }
                
                var commentMasks = $('#comment-mask-product, #comment-mask-thumbnail');
                commentMasks.css('justify-content', justifyValue);
                $('#text_align').val(alignParam);
                
                // Update selected button in dropdown
                var $horizontalDropdown = $('.ycos-alignment-horizontal');
                $horizontalDropdown.find('.ycos-align-button').removeClass('selected');
                $horizontalDropdown.find('.ycos-align-button[data-align="' + alignParam + '"]').addClass('selected');
                $horizontalDropdown.removeClass('expanded');
            }
            
            // Populate vertical alignment from URL parameter on page load
            var valignParam = getUrlParameter('valign');
            if (valignParam) {
                // Map CSS values back to button values for selection
                var buttonValue = valignParam;
                if (valignParam === 'flex-start') {
                    buttonValue = 'top';
                } else if (valignParam === 'flex-end') {
                    buttonValue = 'bottom';
                }
                
                var commentMasks = $('#comment-mask-product, #comment-mask-thumbnail');
                // Set both align-content and align-items for proper vertical alignment
                commentMasks.css({
                    'align-content': valignParam,
                    'align-items': valignParam
                });
                $('#text_valign').val(valignParam);
                
                // Update selected button in dropdown
                var $verticalDropdown = $('.ycos-alignment-vertical');
                $verticalDropdown.find('.ycos-valign-button').removeClass('selected');
                $verticalDropdown.find('.ycos-valign-button[data-valign="' + buttonValue + '"]').addClass('selected');
                $verticalDropdown.removeClass('expanded');
            }
            
            // Populate font size from URL parameter on page load
            var fontSizeParam = getUrlParameter('fontsize');
            if (fontSizeParam) {
                var fontSize = parseInt(fontSizeParam);
                if (!isNaN(fontSize)) {
                    // Clamp to valid range
                    if (fontSize < YCOS_MIN_FONT_SIZE) {
                        fontSize = YCOS_MIN_FONT_SIZE;
                    } else if (fontSize > YCOS_MAX_FONT_SIZE) {
                        fontSize = YCOS_MAX_FONT_SIZE;
                    }
                    $('#font_size_display').val(fontSize);
                    $('#font_size').val(fontSize);
                }
            }
            
            // Preload all fonts first for instant dropdown rendering
            preloadAllFonts();
            
            // Initialize font dropdown (options are already pre-populated)
            initializeFontDropdown();
            
            // Populate font family from URL parameter on page load
            var fontParam = getUrlParameter('font');
            if (fontParam) {
                var fontIndex = parseInt(fontParam);
                if (!isNaN(fontIndex) && fontIndex >= 0 && fontIndex < availableFonts.length) {
                    var fontFamily = availableFonts[fontIndex];
                    
                    // Update both comment masks font
                    var commentMasks = $('#comment-mask-product, #comment-mask-thumbnail');
                    commentMasks.css('font-family', "'" + fontFamily + "', sans-serif");
                    
                    // Update the display button
                    $('.ycos-font-name').text(fontFamily).css('font-family', "'" + fontFamily + "', sans-serif");
                    
                    // Store the font index
                    $('#font_family').val(fontIndex);
                    
                    // Mark the option as selected
                    $('.ycos-font-option[data-font-index="' + fontIndex + '"]').addClass('selected');
                }
            } else {
                // If no URL param, ensure default font is applied to comment masks
                var defaultFontIndex = parseInt($('#font_family').val()) || 12; // Default to NotoSans (index 12)
                if (!isNaN(defaultFontIndex) && defaultFontIndex >= 0 && defaultFontIndex < availableFonts.length) {
                    var defaultFontFamily = availableFonts[defaultFontIndex];
                    var commentMasks = $('#comment-mask-product, #comment-mask-thumbnail');
                    commentMasks.css('font-family', "'" + defaultFontFamily + "', sans-serif");
                }
            }
            
            // Always save comment text on page load (whether from URL or existing value)
            var currentComment = $('#comment_text').val();
            if (currentComment) {
                saveAndUpdateComment(currentComment);
            }
            
            // Move comment mask to main product image
            moveCommentMask();
            
            // Initialize shirt color attribute handling
            handleColorAttributeChange();
            
            // Initialize size attribute handling
            handleSizeAttributeChange();
            
            // Initialize text color button
            initializeTextColorButton();
            
            // Initialize text alignment buttons
            initializeAlignmentButtons();
            
            // Initialize font size control
            initializeFontSizeControl();
            
            // Initialize "Go To Cart" button
            initializeGoToCartButton();
            
            // Create variation buttons
            setTimeout(function() {
                createVariationButtons();
            }, 100);
            
            // Listen for WooCommerce variation changes
            $('form.variations_form').on('woocommerce_update_variation_values', function() {
                updateVariationButtons();
            });
            
            // Apply initial scaled font size based on stored value
            var initialFontSize = parseInt($('#font_size').val());
            if (!isNaN(initialFontSize)) {
                applyScaledFontSize(initialFontSize);
            }
            
            // Handle window resize to recalculate scaled font size
            var resizeTimer;
            $(window).on('resize', function() {
                // Debounce the resize event to avoid too many calculations
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    var currentFontSize = parseInt($('#font_size').val());
                    if (!isNaN(currentFontSize)) {
                        applyScaledFontSize(currentFontSize);
                    }
                }, 250); // Wait 250ms after resize stops
            });
        });
        
        // Handle manual changes to the comment field
        $('#comment_text').on('input', function() {
            var text = $(this).val();
            // Update both comment masks with the same text
            $('#comment-mask-product, #comment-mask-thumbnail').text(text);
        });
        $('#comment_text').on('change', function() {
            var text = $(this).val();
            saveAndUpdateComment(text);
        });
        
        
        /**
         * CAPTURE TRANSPARENT COMMENT MASK FOR PRINTFUL PRINTING
         * 
         * Generates a 1800x2400px transparent PNG with ONLY the text overlay.
         * This is sent to Printful for printing on blank shirts.
         * Stored in session as 'image_data_' (base64) for later conversion to file.
         */
        function captureCommentMaskForPrintful(callback) {
            var commentText = $('#comment_text').val();

            var $commentMask = $('#comment-mask-product');
            if ($commentMask.length === 0) {
                console.error('YourCommentOnAShirt: Comment mask element not found');
                if (callback) callback(null);
                return;
            }
            
            if (typeof html2canvas !== 'undefined') {
                // Get current styling values
                var currentTextColor = $('#text_color').val();
                var currentTextAlign = $('#text_align').val();
                var currentTextValign = $('#text_valign').val();
                var currentFontSize = parseInt($('#font_size').val());
                var currentFontIndex = parseInt($('#font_family').val());
                
                // Validate font size
                if (isNaN(currentFontSize)) {
                    currentFontSize = YCOS_DEFAULT_FONT_SIZE;
                } else if (currentFontSize < YCOS_MIN_FONT_SIZE) {
                    currentFontSize = YCOS_MIN_FONT_SIZE; // Clamp to minimum
                } else if (currentFontSize > YCOS_MAX_FONT_SIZE) {
                    currentFontSize = YCOS_MAX_FONT_SIZE; // Clamp to maximum
                }
                
                // Validate font index
                if (isNaN(currentFontIndex) || currentFontIndex < 0 || currentFontIndex >= availableFonts.length) {
                    currentFontIndex = 12;
                }
                var currentFontFamily = availableFonts[currentFontIndex];
                
                // Map horizontal alignment values to justify-content
                var justifyValue = 'center';
                if (currentTextAlign === 'left') {
                    justifyValue = 'flex-start';
                } else if (currentTextAlign === 'right') {
                    justifyValue = 'flex-end';
                }
                
                // Create a hidden clone for canvas generation at 1800x2400 (Printful size)
                var $clone = $commentMask.clone();
                $clone.attr('id', 'comment-mask-printful-clone');
                
                // Explicitly set text content to ensure reliable capture (cloning can miss dynamic content)
                $clone.text(commentText);
                
                $clone.css({
                    'position': 'absolute',
                    'left': '-9999px',
                    'top': '-9999px',
                    'z-index': '-9999',
                    'font-family': "'" + currentFontFamily + "', sans-serif",
                    'font-size': currentFontSize + 'px',
                    'width': '1800px',
                    'height': '2400px',
                    'display': 'flex',
                    'flex-wrap': 'wrap',
                    'align-items': currentTextValign || 'center',
                    'align-content': currentTextValign || 'center',
                    'justify-content': justifyValue,
                    'text-align': currentTextAlign || 'center',
                    'color': currentTextColor,
                    'white-space': 'pre-wrap',
                    'overflow-wrap': 'break-word',
                    'word-wrap': 'break-word',
                    'word-break': 'break-word',
                    'overflow': 'hidden',
                    'line-height': '1',
                });
                
                // Add clone to body temporarily
                $('body').append($clone);
                
                html2canvas($clone[0], {
                    backgroundColor: null, // Transparent for overlay
                    scale: 1,
                    useCORS: true,
                    allowTaint: true,
                    width: 1800,
                    height: 2400
                }).then(function(canvas) {
                    $clone.remove();
                    var imageData = canvas.toDataURL('image/png');
                    
                    // Store the Printful image data (transparent mask) in session via AJAX
                    var productId = getProductId();
                    if (productId) {
                        var textAlign = $('#text_align').val();
                        var textValign = $('#text_valign').val();
                        var fontSize = $('#font_size').val();
                        var fontFamily = $('#font_family').val();
                        var textColor = $('#text_color').val();
                        
                        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                            action: 'store_printful_image_data',
                            product_id: productId,
                            image_data: imageData,
                            comment_text: commentText,
                            text_align: textAlign,
                            text_valign: textValign,
                            font_size: fontSize,
                            font_family: fontFamily,
                            text_color: textColor
                        }, 'json');
                    }
                    
                    if (callback) callback(imageData);
                }).catch(function(error) {
                    $clone.remove();
                    console.error('YourCommentOnAShirt: html2canvas failed for Printful mask:', error);
                    if (callback) callback(null);
                });
            } else {
                console.error('html2canvas library not loaded');
                if (callback) callback(null);
            }
        }
        
        /**
         * CAPTURE FULL PRODUCT IMAGE FOR CART/WOOPAY DISPLAY
         * 
         * Generates a 1000x1000px image with the shirt + text overlay.
         * This is displayed in cart, mini-cart, WooPay, and checkout.
         * Stored in session as 'image_url_' (file URL) for immediate display.
         * Uses separate session key to prevent overwriting the Printful mask.
         */
        function captureFullProductImageForOrder() {
            var commentText = $('#comment_text').val();
            
            // Find the main product image container
            var $productContainer = $('[data-block-name="woocommerce/product-image"]').first();
            if ($productContainer.length === 0) {
                console.error('YourCommentOnAShirt: Product image container not found');
                return;
            }
            
            if (typeof html2canvas !== 'undefined') {
                // Get current styling values
                var currentTextColor = $('#text_color').val();
                var currentTextAlign = $('#text_align').val();
                var currentTextValign = $('#text_valign').val();
                var currentFontSize = parseInt($('#font_size').val());
                var currentFontIndex = parseInt($('#font_family').val());
                
                // Validate font size
                if (isNaN(currentFontSize)) {
                    currentFontSize = YCOS_DEFAULT_FONT_SIZE;
                } else if (currentFontSize < YCOS_MIN_FONT_SIZE) {
                    currentFontSize = YCOS_MIN_FONT_SIZE; // Clamp to minimum
                } else if (currentFontSize > YCOS_MAX_FONT_SIZE) {
                    currentFontSize = YCOS_MAX_FONT_SIZE; // Clamp to maximum
                }
                
                // Validate font index
                if (isNaN(currentFontIndex) || currentFontIndex < 0 || currentFontIndex >= availableFonts.length) {
                    currentFontIndex = 12;
                }
                var currentFontFamily = availableFonts[currentFontIndex];
                
                // Clone the entire product container (includes shirt image + comment mask)
                var $clone = $productContainer.clone();
                $clone.attr('id', 'product-image-clone');
                
                // Position clone off-screen but ensure it renders properly
                $clone.css({
                    'position': 'absolute',
                    'left': '-9999px',
                    'top': '0',
                    'z-index': '-9999',
                    'width': '1000px',
                    'height': '1000px'
                });
                
                // Add clone to body temporarily (must be in DOM to measure and style)
                $('body').append($clone);
                
                // Preserve the shirt background color from the original image
                // Must be done after appending to DOM for proper CSS application
                var $originalImage = $('[data-testid="product-image"]').first();
                var $clonedImage = $clone.find('[data-testid="product-image"]').first();
                if ($originalImage.length > 0 && $clonedImage.length > 0) {
                    var bgColor = $originalImage.css('background-color');
                    // Apply background color to cloned image to preserve shirt color
                    $clonedImage[0].style.setProperty('background-color', bgColor, 'important');
                }
                
                // Update the comment mask in the clone with proper styling
                var $clonedMask = $clone.find('#comment-mask-product');
                if ($clonedMask.length > 0) {
                    // Map alignment values
                    var justifyValue = currentTextAlign === 'left' ? 'flex-start' : 
                                      currentTextAlign === 'right' ? 'flex-end' : 'center';
                    
                    // Calculate scaled font size based on actual mask width
                    // The mask has margins (20% 30% 27% 30%), so its actual width is less than container
                    // Get the actual rendered width of the cloned mask
                    var actualMaskWidth = $clonedMask[0].offsetWidth;
                    
                    // The real font size is meant for 1800px image generation width
                    var imageGenerationWidth = 1800;
                    var scaleFactor = actualMaskWidth / imageGenerationWidth;
                    var scaledFontSize = currentFontSize * scaleFactor;

                    
                    $clonedMask.css({
                        'font-family': "'" + currentFontFamily + "', sans-serif",
                        'font-size': scaledFontSize + 'px',
                        'color': currentTextColor,
                        'text-align': currentTextAlign || 'center',
                        'justify-content': justifyValue,
                        'align-items': currentTextValign || 'center',
                        'align-content': currentTextValign || 'center',
                        'white-space': 'pre-wrap',
                        'overflow-wrap': 'break-word',
                        'word-wrap': 'break-word',
                        'display': 'flex',
                        'flex-wrap': 'wrap',
                        'word-break': 'break-word',
                        'overflow': 'hidden',
                        'line-height': '1',
                    });
                }
                
                // Capture with html2canvas
                html2canvas($clone[0], {
                    backgroundColor: null,
                    scale: 1,
                    useCORS: true,
                    allowTaint: true,
                    width: 1000,
                    height: 1000,
                    logging: false
                }).then(function(canvas) {
                    // Remove the clone
                    $clone.remove();
                    
                    // Convert canvas to base64 image
                    var imageData = canvas.toDataURL('image/png');
                    
                    // Store the image data in session for the order
                    var productId = getProductId();                    
                    if (productId) {
                        var textAlign = $('#text_align').val();
                        var textValign = $('#text_valign').val();
                        var fontSize = $('#font_size').val();
                        var fontFamily = $('#font_family').val();
                        var textColor = $('#text_color').val();
                        var shirtColor = getSelectedShirtColor();
                        
                        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                            action: 'store_image_data',
                            product_id: productId,
                            image_data: imageData,
                            comment_text: commentText,
                            text_align: textAlign,
                            text_valign: textValign,
                            font_size: fontSize,
                            font_family: fontFamily,
                            text_color: textColor,
                            shirt_color: shirtColor
                        }, 'json');
                    }
                }).catch(function(error) {
                    // Remove the clone
                    $clone.remove();
                    console.error('YourCommentOnAShirt: html2canvas failed for full product image:', error);
                });
            } else {
                console.error('html2canvas library not loaded');
            }
        }
    })(jQuery);
    </script>
    <?php
}

/**
 * ADD COMMENT DATA TO CART ITEM
 * 
 * Hook: woocommerce_add_cart_item_data (filter)
 * Purpose: Attaches comment text to cart items when products are added to cart
 * Priority System:
 * 1. First checks for posted comment_text (normal form submission)
 * 2. Falls back to session data (for Apple Pay, Google Pay, WooPay, etc.)
 * This ensures comments work with all payment methods and cart interactions
 */
add_filter('woocommerce_add_cart_item_data', 'my_add_cart_item_data', 10, 2);
function my_add_cart_item_data($cart_item_data, $product_id) {
    // First priority: posted value (normal add to cart form submission)
    if (isset($_POST['comment_text']) && !empty($_POST['comment_text'])) {
        $cart_item_data['comment_text'] = sanitize_text_field($_POST['comment_text']);
    } else {
        // Fallback: session data (for ApplePay / WooPay / GooglePay / AJAX cart)
        $session_value = WC()->session->get('comment_text_' . $product_id);
        if (!empty($session_value)) {
            $cart_item_data['comment_text'] = $session_value;
        }
    }
    
    // Add text alignment
    if (isset($_POST['text_align']) && !empty($_POST['text_align'])) {
        $cart_item_data['text_align'] = sanitize_text_field($_POST['text_align']);
    } else {
        // Fallback: session data
        $session_align = WC()->session->get('text_align_' . $product_id);
        if (!empty($session_align)) {
            $cart_item_data['text_align'] = $session_align;
        }
    }
    
    // Add vertical text alignment
    if (isset($_POST['text_valign']) && !empty($_POST['text_valign'])) {
        $cart_item_data['text_valign'] = sanitize_text_field($_POST['text_valign']);
    } else {
        // Fallback: session data
        $session_valign = WC()->session->get('text_valign_' . $product_id);
        if (!empty($session_valign)) {
            $cart_item_data['text_valign'] = $session_valign;
        }
    }
    
    // Add font size
    if (isset($_POST['font_size']) && !empty($_POST['font_size'])) {
        $cart_item_data['font_size'] = absint($_POST['font_size']);
    } else {
        // Fallback: session data
        $session_font_size = WC()->session->get('font_size_' . $product_id);
        if (!empty($session_font_size)) {
            $cart_item_data['font_size'] = $session_font_size;
        }
    }
    
    // Add font family
    if (isset($_POST['font_family']) && !empty($_POST['font_family'])) {
        $cart_item_data['font_family'] = absint($_POST['font_family']);
    } else {
        // Fallback: session data
        $session_font_family = WC()->session->get('font_family_' . $product_id);
        if (!empty($session_font_family)) {
            $cart_item_data['font_family'] = $session_font_family;
        }
    }
    
    // Add text color
    if (isset($_POST['text_color']) && !empty($_POST['text_color'])) {
        $cart_item_data['text_color'] = sanitize_text_field($_POST['text_color']);
    } else {
        // Fallback: session data
        $session_text_color = WC()->session->get('text_color_' . $product_id);
        if (!empty($session_text_color)) {
            $cart_item_data['text_color'] = $session_text_color;
        }
    }
    
    // Include Printful image data from session (transparent mask for printing)
    $image_data = WC()->session->get('image_data_' . $product_id);
    if (!empty($image_data)) {
        $cart_item_data['image_data'] = $image_data;
    }
    
    // Include display image URL from session (full product image for cart/WooPay)
    $image_url = WC()->session->get('image_url_' . $product_id);
    if (!empty($image_url)) {
        $cart_item_data['image_url'] = $image_url;
    }
    
    return $cart_item_data;
}

/**
 * DISPLAY COMMENT IN CART AND CHECKOUT
 * 
 * Hook: woocommerce_get_item_data (filter)
 * Purpose: Shows the comment text as additional item data in cart and checkout pages
 * This allows customers to see their custom comment before completing the order
 * The comment appears as a key-value pair (Comment: [text]) below the product details
 */
add_filter('woocommerce_get_item_data', 'my_display_cart_item_data', 10, 2);
function my_display_cart_item_data($item_data, $cart_item) {
    if (!empty($cart_item['comment_text'])) {
        $item_data[] = array(
            'key'   => 'Comment',
            'value' => sanitize_text_field($cart_item['comment_text'])
        );
    }
    
    if (!empty($cart_item['text_color'])) {
        $item_data[] = array(
            'key'   => 'Text Color',
            'value' => sanitize_text_field($cart_item['text_color'])
        );
    }
    
    if (!empty($cart_item['text_align'])) {
        $item_data[] = array(
            'key'   => 'Text Alignment',
            'value' => sanitize_text_field($cart_item['text_align'])
        );
    }
    
    if (!empty($cart_item['text_valign'])) {
        $item_data[] = array(
            'key'   => 'Vertical Alignment',
            'value' => sanitize_text_field($cart_item['text_valign'])
        );
    }
    
    if (!empty($cart_item['font_size'])) {
        $item_data[] = array(
            'key'   => 'Font Size',
            'value' => absint($cart_item['font_size']) . 'px'
        );
    }
    
    if (!empty($cart_item['font_family'])) {
        // Map font index to font name for display
        $font_names = array(
            'Annie Use Your Telescope',
            'Asset',
            'BBH Sans Bartle',
            'BBH Sans Bogle',
            'BBH Sans Hegarty',
            'Butcherman',
            'Creepster',
            'Domine',
            'Inter',
            'Jolly Lodger',
            'Kablammo',
            'Nosifer',
            'NotoSans',
            'Oooh Baby',
            'Open Sans',
            'Orbitron',
            'Playwrite AU TAS',
            'Press Start 2P',
            'Roboto',
            'Rubik Puddles',
            'Trade Winds'
        );
        
        $font_index = absint($cart_item['font_family']);
        $font_name = isset($font_names[$font_index]) ? $font_names[$font_index] : 'NotoSans';
        
        $item_data[] = array(
            'key'   => 'Font',
            'value' => $font_name
        );
    }
    
    return $item_data;
}

/**
 * SAVE COMMENT TO ORDER ITEMS
 * 
 * Hook: woocommerce_checkout_create_order_line_item (action)
 * Purpose: Permanently saves the comment text as metadata on the order line item
 * This ensures the comment is preserved in the order and can be accessed by:
 * - Order management systems
 * - Printful integration
 * - Order emails
 * - Admin order details
 * The comment becomes part of the permanent order record
 */
add_action('woocommerce_checkout_create_order_line_item', 'my_add_order_item_meta', 10, 4);
function my_add_order_item_meta($item, $cart_item_key, $values, $order) {
    if (!empty($values['comment_text'])) {
        $item->add_meta_data('Comment', $values['comment_text']);
    }
    
    if (!empty($values['text_color'])) {
        $item->add_meta_data('Text Color', $values['text_color']);
    }
    
    if (!empty($values['text_align'])) {
        $item->add_meta_data('Text Alignment', $values['text_align']);
    }
    
    if (!empty($values['text_valign'])) {
        $item->add_meta_data('Vertical Alignment', $values['text_valign']);
    }
    
    if (!empty($values['font_size'])) {
        $item->add_meta_data('Font Size', $values['font_size']);
    }
    
    if (!empty($values['font_family'])) {
        $item->add_meta_data('Font Family', $values['font_family']);
    }
    
    if (!empty($values['image_data'])) {
        $item->add_meta_data('Image Data', $values['image_data']);
    }
    
    if (!empty($values['image_url'])) {
        $item->add_meta_data('Custom Design Image', $values['image_url']);
    }
}

/**
 * REPLACE CART ITEM THUMBNAIL WITH CUSTOM DESIGN IMAGE
 * 
 * Hook: woocommerce_cart_item_thumbnail (filter)
 * Purpose: Replaces the standard product thumbnail with the generated custom design image
 * This ensures the customized image appears in:
 * - WooCommerce cart page
 * - Mini cart
 * - Checkout page (including WooPay at pay.woo.com)
 * - Order confirmation emails
 * - Any other place WooCommerce displays cart item images
 */
add_filter('woocommerce_cart_item_thumbnail', 'ycos_replace_cart_item_thumbnail', 10, 3);
function ycos_replace_cart_item_thumbnail($product_image, $cart_item, $cart_item_key) {
    // Check if this cart item has a custom design image URL
    if (!empty($cart_item['image_url'])) {
        $image_url = $cart_item['image_url'];
        $product_name = $cart_item['data']->get_name();
        
        // Generate a new image tag with the custom design
        $product_image = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product_name) . '" class="ycos-custom-design-thumbnail">';
    }
    
    return $product_image;
}

/**
 * REPLACE ORDER ITEM THUMBNAIL WITH CUSTOM DESIGN IMAGE
 * 
 * Hook: woocommerce_order_item_thumbnail (filter)
 * Purpose: Replaces the standard product thumbnail with the generated custom design image in orders
 * This ensures the customized image appears in:
 * - Order confirmation page
 * - Order details in admin
 * - Order confirmation emails
 */
add_filter('woocommerce_order_item_thumbnail', 'ycos_replace_order_item_thumbnail', 10, 2);
function ycos_replace_order_item_thumbnail($image, $item) {
    // Get the custom design image URL from order item metadata
    $image_url = $item->get_meta('Custom Design Image');
    
    if (!empty($image_url)) {
        $product_name = $item->get_name();
        
        // Generate a new image tag with the custom design
        $image = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product_name) . '" class="ycos-custom-design-thumbnail">';
    }
    
    return $image;
}

/**
 * REPLACE STORE API CART ITEM IMAGES (FOR WOOPAY)
 * 
 * Hook: woocommerce_store_api_cart_item_images (filter)
 * Purpose: Replaces product images in the Store API cart response with custom design images
 * This is THE KEY HOOK for WooPay integration!
 * 
 * WooPay uses the WooCommerce Store API to get cart data, so this filter ensures
 * the custom design images are sent to WooPay instead of the standard product images.
 * 
 * @param array  $product_images Array of image objects from the Store API
 * @param array  $cart_item      Cart item array
 * @param string $cart_item_key  Cart item key
 * @return array Modified array of image objects
 */
add_filter('woocommerce_store_api_cart_item_images', 'ycos_replace_store_api_cart_images', 10, 3);
function ycos_replace_store_api_cart_images($product_images, $cart_item, $cart_item_key) {
    // Check if this cart item has a custom design image URL (full product image for display)
    if (empty($cart_item['image_url'])) {
        return $product_images;
    }
    
    $custom_image_url = $cart_item['image_url'];
    
    // Parse the URL to get the attachment ID (if it's in our uploads directory)
    // This creates a proper image object that the Store API expects
    $image_id = attachment_url_to_postid($custom_image_url);
    
    // If we can't find an attachment ID, create a custom image object
    if (!$image_id) {
        $image_id = 0; // Use 0 for dynamically generated images
    }
    
    // Get product name for alt text
    $product = $cart_item['data'];
    $product_name = $product->get_name();
    
    // Create a properly formatted image object for the Store API
    // This matches the structure expected by WooPay and WooCommerce Blocks
    $custom_image = (object) [
        'id' => $image_id,
        'src' => $custom_image_url,
        'thumbnail' => $custom_image_url, // Use same URL for thumbnail
        'srcset' => '',
        'sizes' => '',
        'name' => basename($custom_image_url),
        'alt' => $product_name . ' - Custom Design'
    ];
    
    // Replace the entire images array with our custom image
    return [$custom_image];
}

/**
 * APPEND URL PARAMETERS TO CART ITEM PERMALINK
 * 
 * Hook: woocommerce_cart_item_permalink (filter)
 * Purpose: Modifies the product link in cart/mini-cart to include URL parameters
 * This ensures when users click cart items, they're taken to the product page
 * with all their customization settings preserved (comment, color, alignment, etc.)
 * 
 * @param string $permalink The original product permalink
 * @param array  $cart_item Cart item data containing customization settings
 * @param string $cart_item_key Unique key for this cart item
 * @return string Modified permalink with URL parameters
 */
add_filter('woocommerce_cart_item_permalink', 'ycos_add_url_params_to_cart_link', 10, 3);
function ycos_add_url_params_to_cart_link($permalink, $cart_item, $cart_item_key) {
    // Only modify permalink if it's not empty
    if (empty($permalink)) {
        return $permalink;
    }
    
    // Build URL parameters from cart item data
    // Note: http_build_query() handles URL encoding, so we don't manually encode
    $params = array();
    
    // Add comment text
    if (!empty($cart_item['comment_text'])) {
        $params['comment'] = $cart_item['comment_text'];
    }
    
    // Add text color
    if (!empty($cart_item['text_color'])) {
        $params['color'] = $cart_item['text_color'];
    }
    
    // Add text alignment
    if (!empty($cart_item['text_align'])) {
        $params['align'] = $cart_item['text_align'];
    }
    
    // Add vertical alignment
    if (!empty($cart_item['text_valign'])) {
        $params['valign'] = $cart_item['text_valign'];
    }
    
    // Add font size
    if (!empty($cart_item['font_size'])) {
        $params['fontsize'] = $cart_item['font_size'];
    }
    
    // Add font family
    if (!empty($cart_item['font_family'])) {
        $params['font'] = $cart_item['font_family'];
    }
    
    // If we have any parameters, append them to the permalink
    if (!empty($params)) {
        $query_string = http_build_query($params);
        $separator = (strpos($permalink, '?') === false) ? '?' : '&';
        $permalink = $permalink . $separator . $query_string;
    }
    
    return $permalink;
}

/**
 * ADD CUSTOM URL PARAMETERS TO ADD-TO-CART REDIRECT
 * 
 * Hook: woocommerce_add_to_cart_redirect (filter)
 * Purpose: When clicking "Add to Cart" button, preserve custom parameters in redirect URL
 * This ensures users see their customization when redirected back to the product page
 * 
 * @param string $url The redirect URL after adding to cart
 * @return string Modified URL with custom parameters preserved
 */
add_filter('woocommerce_add_to_cart_redirect', 'ycos_add_params_to_cart_redirect');
function ycos_add_params_to_cart_redirect($url) {    
    // Get product ID from POST data
    $product_id = null;
    if (!empty($_POST['product_id'])) {
        $product_id = absint($_POST['product_id']);
    } elseif (!empty($_POST['add-to-cart'])) {
        $product_id = absint($_POST['add-to-cart']);
    }
    
    if (!$product_id) {
        error_log('YourCommentOnAShirt: No product ID found, returning original URL');
        return $url;
    }
    
    // Get the product permalink to redirect back to the product page
    $product = wc_get_product($product_id);
    if (!$product) {
        error_log('YourCommentOnAShirt: Product not found, returning original URL');
        return $url;
    }
    
    $product_url = get_permalink($product_id);
    
    // Get custom field values from SESSION (they're already saved via AJAX)
    $params = array();
    
    $comment_text = WC()->session->get('comment_text_' . $product_id);
    if (!empty($comment_text)) {
        $params['comment'] = $comment_text;
    }
    
    $text_color = WC()->session->get('text_color_' . $product_id);
    if (!empty($text_color)) {
        $params['color'] = $text_color;
    }
    
    $text_align = WC()->session->get('text_align_' . $product_id);
    if (!empty($text_align)) {
        $params['align'] = $text_align;
    }
    
    $text_valign = WC()->session->get('text_valign_' . $product_id);
    if (!empty($text_valign)) {
        $params['valign'] = $text_valign;
    }
    
    $font_size = WC()->session->get('font_size_' . $product_id);
    if (!empty($font_size)) {
        $params['fontsize'] = $font_size;
    }
    
    $font_family = WC()->session->get('font_family_' . $product_id);
    if (!empty($font_family)) {
        $params['font'] = $font_family;
    }
    
    // Also add the variation attributes back to the URL
    if (!empty($_POST['attribute_pa_size'])) {
        $params['attribute_pa_size'] = sanitize_text_field($_POST['attribute_pa_size']);
    }
    
    if (!empty($_POST['attribute_pa_color'])) {
        $params['attribute_pa_color'] = sanitize_text_field($_POST['attribute_pa_color']);
    }
        
    // Build the redirect URL to the product page with all parameters
    if (!empty($params)) {
        $query_string = http_build_query($params);
        $redirect_url = $product_url . '?' . $query_string;
        error_log('YourCommentOnAShirt: Redirect URL: ' . $redirect_url);
        return $redirect_url;
    } else {
        error_log('YourCommentOnAShirt: No params to add, redirecting to product page');
        return $product_url;
    }
}

/**
 * AJAX HANDLER FOR STORING FULL PRODUCT IMAGE (DISPLAY ONLY)
 * 
 * This stores the full product image (shirt + text overlay) for display in cart/WooPay.
 * CRITICAL: This does NOT store to 'image_data_' key - that's reserved for Printful mask.
 * Separation prevents the display image from overwriting the Printful printing image.
 */
add_action('wp_ajax_store_image_data', 'ycos_store_image_data');
add_action('wp_ajax_nopriv_store_image_data', 'ycos_store_image_data');
function ycos_store_image_data() {
    if (!isset($_POST['product_id']) || !isset($_POST['image_data']) || !isset($_POST['comment_text'])) {
        wp_send_json_error('Missing required parameters');
    }
    
    $product_id = absint($_POST['product_id']);
    $image_data = $_POST['image_data']; // Base64 image data for the full product image
    $comment_text = sanitize_text_field($_POST['comment_text']);
    
    // Collect parameters for hash generation
    $params_for_hash = array(
        'comment_text' => $comment_text,
        'text_color' => isset($_POST['text_color']) ? sanitize_text_field($_POST['text_color']) : null,
        'text_align' => isset($_POST['text_align']) ? sanitize_text_field($_POST['text_align']) : null,
        'text_valign' => isset($_POST['text_valign']) ? sanitize_text_field($_POST['text_valign']) : null,
        'font_size' => isset($_POST['font_size']) ? absint($_POST['font_size']) : null,
        'font_family' => isset($_POST['font_family']) ? absint($_POST['font_family']) : null,
        'shirt_color' => isset($_POST['shirt_color']) ? sanitize_text_field($_POST['shirt_color']) : null
    );
    
    // Generate and save the actual image file with hash-based filename
    $image_url = ycos_save_image_from_data($image_data, $comment_text, 'product', $params_for_hash);
    
    // Store ONLY the URL (not the base64 data) - this is for display purposes only
    WC()->session->set('image_url_' . $product_id, $image_url);
    WC()->session->set('comment_text_' . $product_id, $comment_text);
    
    // Also store text alignment if provided
    if (isset($_POST['text_align'])) {
        $text_align = sanitize_text_field($_POST['text_align']);
        WC()->session->set('text_align_' . $product_id, $text_align);
    }
    
    // Also store vertical text alignment if provided
    if (isset($_POST['text_valign'])) {
        $text_valign = sanitize_text_field($_POST['text_valign']);
        WC()->session->set('text_valign_' . $product_id, $text_valign);
    }
    
    // Also store font size if provided
    if (isset($_POST['font_size'])) {
        $font_size = absint($_POST['font_size']);
        WC()->session->set('font_size_' . $product_id, $font_size);
    }
    
    // Also store font family if provided
    if (isset($_POST['font_family'])) {
        $font_family = absint($_POST['font_family']);
        WC()->session->set('font_family_' . $product_id, $font_family);
    }
    
    // Also store text color if provided
    if (isset($_POST['text_color'])) {
        $text_color = sanitize_text_field($_POST['text_color']);
        WC()->session->set('text_color_' . $product_id, $text_color);
    }
    
    // Also store shirt color if provided
    if (isset($_POST['shirt_color'])) {
        $shirt_color = sanitize_text_field($_POST['shirt_color']);
        WC()->session->set('shirt_color_' . $product_id, $shirt_color);
    }
    
    wp_send_json_success([
        'message' => 'Image data stored successfully',
        'image_url' => $image_url
    ]);
}

/**
 * AJAX HANDLER FOR STORING PRINTFUL IMAGE DATA (TRANSPARENT MASK)
 * 
 * This stores the transparent comment mask (1800x2400px) for Printful printing.
 * CRITICAL: This stores to 'image_data_' key which is used ONLY for Printful.
 * The display image handler uses 'image_url_' to prevent overwriting this data.
 */
add_action('wp_ajax_store_printful_image_data', 'ycos_store_printful_image_data');
add_action('wp_ajax_nopriv_store_printful_image_data', 'ycos_store_printful_image_data');
function ycos_store_printful_image_data() {
    if (!isset($_POST['product_id']) || !isset($_POST['image_data']) || !isset($_POST['comment_text'])) {
        wp_send_json_error('Missing required parameters');
    }
    
    $product_id = absint($_POST['product_id']);
    $image_data = $_POST['image_data']; // Base64 image data - transparent mask for Printful
    $comment_text = sanitize_text_field($_POST['comment_text']);
    
    // Store the Printful image data (transparent mask) in session
    // This will be converted to a file and sent to Printful when order is created
    WC()->session->set('image_data_' . $product_id, $image_data);
    WC()->session->set('comment_text_' . $product_id, $comment_text);
    
    // Also store text alignment if provided
    if (isset($_POST['text_align'])) {
        $text_align = sanitize_text_field($_POST['text_align']);
        WC()->session->set('text_align_' . $product_id, $text_align);
    }
    
    // Also store vertical text alignment if provided
    if (isset($_POST['text_valign'])) {
        $text_valign = sanitize_text_field($_POST['text_valign']);
        WC()->session->set('text_valign_' . $product_id, $text_valign);
    }
    
    // Also store font size if provided
    if (isset($_POST['font_size'])) {
        $font_size = absint($_POST['font_size']);
        WC()->session->set('font_size_' . $product_id, $font_size);
    }
    
    // Also store font family if provided
    if (isset($_POST['font_family'])) {
        $font_family = absint($_POST['font_family']);
        WC()->session->set('font_family_' . $product_id, $font_family);
    }
    
    // Also store text color if provided
    if (isset($_POST['text_color'])) {
        $text_color = sanitize_text_field($_POST['text_color']);
        WC()->session->set('text_color_' . $product_id, $text_color);
    }
    
    wp_send_json_success(['message' => 'Printful image data stored successfully']);
}

/**
 * AJAX HANDLER FOR SAVING CANVAS IMAGES
 */
add_action('wp_ajax_save_canvas_image', 'ycos_save_canvas_image');
add_action('wp_ajax_nopriv_save_canvas_image', 'ycos_save_canvas_image');
function ycos_save_canvas_image() {
    if (!isset($_POST['image']) || !isset($_POST['comment_text'])) {
        wp_send_json_error('Missing image data or comment text');
    }
    
    $image_data = $_POST['image'];
    $comment_text = sanitize_text_field($_POST['comment_text']);
    $prefix = isset($_POST['prefix']) ? sanitize_text_field($_POST['prefix']) : 'design_';
    
    // Remove data URL prefix
    $image_data = str_replace('data:image/png;base64,', '', $image_data);
    $image_data = base64_decode(str_replace(' ', '+', $image_data));
    
    if ($image_data === false) {
        wp_send_json_error('Invalid image data');
    }
    
    // Define upload path
    $upload_dir = wp_upload_dir();
    $subdir = '/custom-designs/';
    $dir = $upload_dir['basedir'] . $subdir;
    $url = $upload_dir['baseurl'] . $subdir;
    
    // Ensure directory exists
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }
    
    // Generate unique filename with configurable prefix
    $timestamp = time();
    $random = wp_rand(1000, 9999);
    $filename = $prefix . $timestamp . '_' . $random . '.png';
    $filepath = $dir . $filename;
    $fileurl = $url . $filename;
    
    // Save the image
    $success = file_put_contents($filepath, $image_data);
    
    if ($success !== false) {
        wp_send_json_success(['url' => $fileurl, 'filename' => $filename]);
    } else {
        error_log('YourCommentOnAShirt: Failed to save canvas image');
        wp_send_json_error('Failed to save image');
    }
}

/**
 * GENERATE IMAGES FOR ORDER ITEMS
 * 
 * Hook: woocommerce_new_order (action)
 * Purpose: Generates custom design images for each order item with comment text
 * This creates PNG images that can be used by Printful or other print-on-demand services
 * The image URL is stored as order item metadata for easy access
 * 
 * Note: This hook fires reliably when orders are created and is the optimal timing
 * for image generation before payment processing begins.
 */
add_action('woocommerce_new_order', 'ycos_generate_images_for_order', 10, 1);
function ycos_generate_images_for_order($order_id) {
    $order = wc_get_order($order_id);
    // ycos_log_printful_order_data($order);
    if (!$order) {
        return;
    }
    
    $printful_order_items = array();
    $has_custom_items = false;
    
    // First pass: Generate Printful images and collect order items
    foreach ($order->get_items() as $item_id => $item) {
        // Get comment text from item metadata
        $comment_text = $item->get_meta('Comment');
        
        // CRITICAL: Two separate image types are stored in cart metadata:
        // 1. 'Image Data' = transparent mask (1800x2400px) for Printful printing
        // 2. 'Custom Design Image' = full product image (1000x1000px) for cart/WooPay display
        // Always use 'Image Data' for Printful to get the transparent mask
        $printful_image_url = null;
        
        if (!empty($comment_text)) {
            $image_data = $item->get_meta('Image Data');
            if (!empty($image_data)) {
                // Collect parameters for hash generation (design masks don't include shirt color)
                $params_for_hash = array(
                    'comment_text' => $comment_text,
                    'text_color' => $item->get_meta('Text Color'),
                    'text_align' => $item->get_meta('Text Alignment'),
                    'text_valign' => $item->get_meta('Vertical Alignment'),
                    'font_size' => $item->get_meta('Font Size'),
                    'font_family' => $item->get_meta('Font Family'),
                    'shirt_color' => null  // Design masks don't include shirt color (transparent)
                );
                
                // Generate Printful image from stored image data with hash-based filename
                // Uses 'design' prefix (underscore added by function)
                $printful_image_url = ycos_save_image_from_data($image_data, $comment_text, 'design', $params_for_hash);
                
                if ($printful_image_url) {
                    // Store as separate metadata for reference
                    $item->add_meta_data('Printful Design Image', $printful_image_url, true);
                    $item->save();
                }
            }
        }
        
        // If we have a Printful image URL, prepare Printful item
        if (!empty($comment_text) && !empty($printful_image_url)) {
            // Prepare Printful order item data with the transparent mask image
            $printful_item = ycos_prepare_printful_order_item($printful_image_url, $item);
            if ($printful_item) {
                $printful_order_items[] = $printful_item;
                $has_custom_items = true;
            }
        }
    }
    
    // Second pass: Create single Printful order with all items
    if ($has_custom_items && !empty($printful_order_items)) {
        $printful_response = ycos_create_printful_order_with_items($order, $printful_order_items);
        if ($printful_response) {
            $printful_order_id = $printful_response['data']['id'] ?? 'Unknown';
            
            // Add Printful order ID to all custom items
            foreach ($order->get_items() as $item_id => $item) {
                $comment_text = $item->get_meta('Comment');
                if (!empty($comment_text)) {
                    $item->add_meta_data('Printful Order ID', $printful_order_id, true);
                    $item->save();
                }
            }
        } else {
            error_log('YourCommentOnAShirt: Failed to create Printful order');
        }
    }
    
    // Save the order to persist any metadata changes
    $order->save();
}

/**
 * SAVE IMAGE FROM STORED DATA
 * 
 * Purpose: Converts stored base64 image data to actual image file
 * Uses hash-based filenames for automatic deduplication
 * 
 * @param string $image_data Base64 encoded image data
 * @param string $comment_text The comment text for logging
 * @param string $prefix Filename prefix ('product' or 'design')
 * @param array $params Image generation parameters for hash calculation
 * @return string|false Image URL on success, false on failure
 */
function ycos_save_image_from_data($image_data, $comment_text, $prefix = 'design', $params = array()) {
    // Define upload path
    $upload_dir = wp_upload_dir();
    $subdir = '/custom-designs/';
    $dir = $upload_dir['basedir'] . $subdir;
    $url = $upload_dir['baseurl'] . $subdir;
    
    // Ensure directory exists
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }
    
    // Generate hash-based filename for deduplication
    $hash = ycos_generate_image_hash_preview($params, $prefix);
    $filename = $prefix . '_' . $hash . '.png';
    $filepath = $dir . $filename;
    $fileurl = $url . $filename;
    
    // Check if file already exists (reuse cached version)
    if (file_exists($filepath)) {
        return $fileurl;
    }
    
    // File doesn't exist, need to generate it
    // Remove data URL prefix and decode
    $image_data = str_replace('data:image/png;base64,', '', $image_data);
    $image_data = base64_decode(str_replace(' ', '+', $image_data));
    
    if ($image_data === false) {
        error_log('YourCommentOnAShirt: Invalid image data');
        return false;
    }
    
    // Save the image
    $success = file_put_contents($filepath, $image_data);
    
    if ($success !== false) {
        return $fileurl;
    } else {
        error_log('YourCommentOnAShirt: Failed to save image');
        return false;
    }
}

/**
 * LOG PRINTFUL ORDER DATA
 * 
 * Purpose: Logs all order information that would be sent to Printful API
 * This helps debug and understand the data structure before making actual API calls
 */
function ycos_log_printful_order_data($order) {
    // Get all order data using WooCommerce's get_data() method
    $order_data = $order->get_data();
    
    foreach ($order_data as $key => $value) {
        if (is_array($value)) {
            error_log('YourCommentOnAShirt: {' . $key . '} => {Array with ' . count($value) . ' items}');
            // Log array contents if it's not too large
            if (count($value) <= 5) {
                foreach ($value as $sub_key => $sub_value) {
                    error_log('YourCommentOnAShirt:   {' . $sub_key . '} => {' . $sub_value . '}');
                }
            }
        } else {
            error_log('YourCommentOnAShirt: {' . $key . '} => {' . $value . '}');
        }
    }
}

/**
 * GET CATALOG VARIANTS FROM SAVED FILE
 *
 * Purpose: Retrieves catalog variants from the saved JSON file
 * This provides easy access to variant data for other functions
 *
 * @return array|false Array of variants on success, false on failure
 */
function ycos_get_catalog_variants() {
    $upload_dir = wp_upload_dir();
    $json_file = $upload_dir['basedir'] . '/catalog-variants/catalog-variants.json';
    
    if (!file_exists($json_file)) {
        return false;
    }
    
    $json_data = file_get_contents($json_file);
    if ($json_data === false) {
        return false;
    }
    
    $variants = json_decode($json_data, true);
    return $variants ? $variants : false;
}

/**
 * FIND CATALOG VARIANT ID BY SIZE AND COLOR
 *
 * Purpose: Searches the saved catalog variants to find the matching variant ID
 * based on the size and color from the WooCommerce order
 *
 * @param string $size The size from the order (e.g., "S", "M", "L")
 * @param string $color The color slug from the order (e.g., "irish-green", "black")
 * @return int|false Catalog variant ID on success, false if not found
 */
function ycos_find_catalog_variant_id($size, $color) {
    $variants = ycos_get_catalog_variants();
    
    if (!$variants) {
        error_log('YourCommentOnAShirt: No catalog variants found. Please refresh catalog variants first.');
        return false;
    }
    
    // Map WooCommerce color slugs to Printful color names
    $color_mapping = array(
        'ash' => 'Ash',
        'azalea' => 'Azalea',
        'black' => 'Black',
        'brown-savana' => 'Brown Savana',
        'cardinal' => 'Cardinal',
        'carolina-blue' => 'Carolina Blue',
        'charcoal' => 'Charcoal',
        'daisy' => 'Daisy',
        'dark-chocolate' => 'Dark Chocolate',
        'dark-heather' => 'Dark Heather',
        'forest-green' => 'Forest Green',
        'gold' => 'Gold',
        'graphite-heather' => 'Graphite Heather',
        'heliconia' => 'Heliconia',
        'ice-grey' => 'Ice Grey',
        'irish-green' => 'Irish Green',
        'light-blue' => 'Light Blue',
        'light-pink' => 'Light Pink',
        'lime' => 'Lime',
        'maroon' => 'Maroon',
        'military-green' => 'Military Green',
        'natural' => 'Natural',
        'navy' => 'Navy',
        'orange' => 'Orange',
        'purple' => 'Purple',
        'red' => 'Red',
        'royal' => 'Royal',
        'sand' => 'Sand',
        'sapphire' => 'Sapphire',
        'sky' => 'Sky',
        'sport-grey' => 'Sport Grey',
        'tropical-blue' => 'Tropical Blue',
        'turf-green' => 'Turf Green',
        'white' => 'White',
        'yellow-haze' => 'Yellow Haze'
    );
    
    // Normalize the inputs for comparison
    $normalized_size = strtoupper(trim($size));
    $normalized_color_slug = strtolower(trim($color));
    
    // Convert WooCommerce color slug to Printful color name
    $printful_color_name = isset($color_mapping[$normalized_color_slug]) ? $color_mapping[$normalized_color_slug] : null;
    
    if (!$printful_color_name) {
        error_log('YourCommentOnAShirt: Unknown color slug: ' . $color);
        return false;
    }
    
    // Search through variants
    foreach ($variants as $variant) {
        $variant_size = strtoupper(trim($variant['size']));
        $variant_color = trim($variant['color']); // Keep original case for exact match
        
        // Check for exact match
        if ($variant_size === $normalized_size && $variant_color === $printful_color_name) {
            error_log('YourCommentOnAShirt: Found matching variant - Size: ' . $size . ', Color: ' . $color . ' -> ' . $printful_color_name . ', Variant ID: ' . $variant['catalog_variant_id']);
            return $variant['catalog_variant_id'];
        }
    }
    
    // Log available variants for debugging
    error_log('YourCommentOnAShirt: No matching variant found for Size: ' . $size . ', Color: ' . $color . ' -> ' . $printful_color_name);
    error_log('YourCommentOnAShirt: Available variants: ' . json_encode($variants));
    
    return false;
}

/**
 * EXTRACT SIZE AND COLOR FROM ORDER ITEM
 *
 * Purpose: Extracts size and color attributes from WooCommerce order item metadata
 * These attributes are stored in the order item's meta_data array
 *
 * @param WC_Order_Item_Product $item The order item to extract attributes from
 * @return array Array with 'size' and 'color' keys, or false if not found
 */
function ycos_extract_item_attributes($item) {
    $size = null;
    $color = null;
    
    // Get all meta data for the item
    $meta_data = $item->get_meta_data();
    
    foreach ($meta_data as $meta) {
        $key = $meta->get_data()['key'];
        $value = $meta->get_data()['value'];
        
        // Check for size attribute (pa_size is the WooCommerce attribute key)
        if ($key === 'pa_size') {
            $size = $value;
        }
        // Check for color attribute (pa_color is the WooCommerce attribute key)
        elseif ($key === 'pa_color') {
            $color = $value;
        }
    }
    
    if ($size && $color) {
        return [
            'size' => $size,
            'color' => $color
        ];
    }
    
    error_log('YourCommentOnAShirt: Could not extract size and color from order item');
    return false;
}

/**
 * PREPARE PRINTFUL ORDER ITEM
 *
 * Purpose: Prepares order item data for Printful API from WooCommerce order item
 * Extracts size, color, quantity, and image data for a single item
 *
 * @param string $image_url The URL of the custom design image
 * @param WC_Order_Item_Product $item The order item containing size, color, and order data
 * @return array|false Order item data on success, false on failure
 */
function ycos_prepare_printful_order_item($image_url, $item) {
    // Extract size and color from order item
    $attributes = ycos_extract_item_attributes($item);
    if (!$attributes) {
        error_log('YourCommentOnAShirt: Could not extract size and color from order item');
        return false;
    }

    // Find the correct catalog variant ID based on size and color
    $catalog_variant_id = ycos_find_catalog_variant_id($attributes['size'], $attributes['color']);
    if (!$catalog_variant_id) {
        error_log('YourCommentOnAShirt: Could not find catalog variant ID for size: ' . $attributes['size'] . ', color: ' . $attributes['color']);
        return false;
    }

    // Get quantity from the order item
    $quantity = $item->get_quantity();

    // Return prepared order item data
    return array(
        'catalog_variant_id' => $catalog_variant_id,
        'source' => 'catalog',
        'quantity' => $quantity,
        'placements' => array(
            array(
                'placement' => 'front',
                'technique' => 'dtg',
                'layers' => array(
                    array(
                        'type' => 'file',
                        'url' => $image_url
                    )
                )
            )
        )
    );
}

/**
 * CREATE PRINTFUL ORDER WITH MULTIPLE ITEMS
 *
 * Purpose: Creates a single Printful order containing multiple items
 * Uses the WooCommerce order ID as external_id to avoid duplicates
 *
 * @param WC_Order $order The WooCommerce order object
 * @param array $printful_order_items Array of prepared Printful order items
 * @return array|false API response on success, false on failure
 */
function ycos_create_printful_order_with_items($order, $printful_order_items) {
    // Get API key from WordPress options
    $api_key = get_option('ycos_printful_api_key');
    if (empty($api_key)) {
        error_log('YourCommentOnAShirt: Printful API key not configured. Please set it in Settings > YourCommentOnAShirt');
        return false;
    }

    // Printful API endpoint
    $api_url = 'https://api.printful.com/v2/orders?store_id=16875297';

    // Use order ID as external_id
    $external_id = (string) $order->get_id();

    // Get shipping address from order
    $shipping_address = $order->get_address('shipping');

    // Map WooCommerce order data to Printful recipient format
    $recipient = array(
        'name' => trim($shipping_address['first_name'] . ' ' . $shipping_address['last_name']),
        'address1' => $shipping_address['address_1'],
        'address2' => $shipping_address['address_2'],
        'city' => $shipping_address['city'],
        'state_code' => $shipping_address['state'],
        'state_name' => $shipping_address['state'], // Printful expects state_name as well
        'country_code' => $shipping_address['country'],
        'country_name' => $shipping_address['country'], // Printful expects country_name as well
        'zip' => $shipping_address['postcode']
    );

    // Build order data with multiple items
    $order_data = array(
        'external_id' => $external_id,
        'recipient' => $recipient,
        'order_items' => $printful_order_items
    );
    
    // Convert to JSON
    $json_data = json_encode($order_data);
    
    // Set up cURL request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data),
        'Authorization: Bearer ' . $api_key
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log the request and response
    error_log('YourCommentOnAShirt: Printful API Request - ' . $json_data);
    error_log('YourCommentOnAShirt: Printful API Response Code - ' . $http_code);
    error_log('YourCommentOnAShirt: Printful API Response - ' . $response);
    
    // Check for cURL errors
    if ($curl_error) {
        error_log('YourCommentOnAShirt: cURL Error - ' . $curl_error);
        return false;
    }
    
    // Check HTTP response code
    if ($http_code >= 200 && $http_code < 300) {
        $decoded_response = json_decode($response, true);
        return $decoded_response;
    } else {
        error_log('YourCommentOnAShirt: Printful API Error - HTTP ' . $http_code . ': ' . $response);
        return false;
    }
}
