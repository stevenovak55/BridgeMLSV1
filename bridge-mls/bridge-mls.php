<?php
/**
 * Plugin Name: Bridge MLS
 * Plugin URI: https://bridgemls.com
 * Description: Professional Bridge MLS integration with advanced property search, modern gallery lightbox, and comprehensive admin interface.
 * Version: 3.0.2
 * Author: Bridge MLS Development Team
 * License: GPL v2 or later
 * Text Domain: bridge-mls
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BRIDGE_MLS_VERSION', '3.0.2');
define('BRIDGE_MLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BRIDGE_MLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BRIDGE_MLS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load configuration from options
$bridge_options = get_option('bridge_mls_options', array());

// Define API constants with validation
define('BRIDGE_API_URL', isset($bridge_options['api_url']) && !empty($bridge_options['api_url']) ? 
    esc_url_raw($bridge_options['api_url']) : '');
define('BRIDGE_SERVER_TOKEN', isset($bridge_options['server_token']) ? 
    sanitize_text_field($bridge_options['server_token']) : '');
define('BRIDGE_BROWSER_TOKEN', isset($bridge_options['browser_token']) ? 
    sanitize_text_field($bridge_options['browser_token']) : '');

// Define no-image placeholder
define('BRIDGE_MLS_NO_IMAGE', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHRleHQtYW5jaG9yPSJtaWRkbGUiIHg9IjIwMCIgeT0iMTUwIiBmaWxsPSIjYWFhIiBmb250LXNpemU9IjIwIiBmb250LWZhbWlseT0iQXJpYWwiPk5vIEltYWdlIEF2YWlsYWJsZTwvdGV4dD48L3N2Zz4=');

// Include admin interface
if (is_admin()) {
    require_once BRIDGE_MLS_PLUGIN_DIR . 'bridge-mls-admin.php';
    new BridgeMLSAdmin();
}

/**
 * Main plugin class
 */
class BridgeMLSPlugin {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Cache expiration time (in seconds)
     */
    private $cache_expiration = 3600; // 1 hour
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Core hooks
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_bridge_search_properties', array($this, 'ajax_search_properties'));
        add_action('wp_ajax_nopriv_bridge_search_properties', array($this, 'ajax_search_properties'));
        add_action('wp_ajax_bridge_get_property_details', array($this, 'ajax_get_property_details'));
        add_action('wp_ajax_nopriv_bridge_get_property_details', array($this, 'ajax_get_property_details'));
        add_action('wp_ajax_bridge_test_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_bridge_mls_contact_agent', array($this, 'ajax_contact_agent'));
        add_action('wp_ajax_nopriv_bridge_mls_contact_agent', array($this, 'ajax_contact_agent'));
        add_action('wp_ajax_bridge_mls_validate_credentials', array($this, 'ajax_validate_credentials'));
        
        // Shortcodes
        add_shortcode('bridge_property_search', array($this, 'property_search_shortcode'));
        add_shortcode('bridge_featured_properties', array($this, 'featured_properties_shortcode'));
        add_shortcode('bridge_property_details', array($this, 'property_details_shortcode'));
        
        // Add query vars filter
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        load_plugin_textdomain('bridge-mls', false, dirname(BRIDGE_MLS_PLUGIN_BASENAME) . '/languages');
        
        // Add rewrite rules for pretty property URLs
        $this->add_rewrite_rules();
    }
    
    /**
     * Add rewrite rules for property URLs
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^property/([^/]+)/?$',
            'index.php?pagename=property-details&mls=$matches[1]',
            'top'
        );
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'mls';
        $vars[] = 'listing_key';
        return $vars;
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!$this->should_load_scripts()) {
            return;
        }
        
        // Select2
        wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', array(), '4.0.13');
        wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
        
        // Plugin assets
        wp_enqueue_style('bridge-mls-style', BRIDGE_MLS_PLUGIN_URL . 'assets/bridge-mls.css', array(), BRIDGE_MLS_VERSION);
        wp_enqueue_script('bridge-mls-script', BRIDGE_MLS_PLUGIN_URL . 'assets/bridge-mls.js', array('jquery', 'select2-js'), BRIDGE_MLS_VERSION, true);
        
        // Localize script
        wp_localize_script('bridge-mls-script', 'bridgeMLS', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bridge_mls_nonce'),
            'plugin_url' => BRIDGE_MLS_PLUGIN_URL,
            'home_url' => home_url(),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'no_image_url' => BRIDGE_MLS_NO_IMAGE
        ));
    }
    
    /**
     * Check if scripts should load
     */
    private function should_load_scripts() {
        if (is_admin()) {
            return false;
        }
        
        if (is_singular()) {
            $post = get_post();
            if ($post && (
                has_shortcode($post->post_content, 'bridge_property_search') ||
                has_shortcode($post->post_content, 'bridge_featured_properties') ||
                has_shortcode($post->post_content, 'bridge_property_details')
            )) {
                return true;
            }
        }
        
        if (isset($_GET['mls']) || isset($_GET['listing_key']) || strpos($_SERVER['REQUEST_URI'], 'property-details') !== false || strpos($_SERVER['REQUEST_URI'], '/property/') !== false) {
            return true;
        }
        
        return apply_filters('bridge_mls_load_scripts', false);
    }
    
    /**
     * Make API request with improved error handling
     */
    private function make_api_request($endpoint, $params = array()) {
        if (empty(BRIDGE_SERVER_TOKEN)) {
            return new WP_Error('no_token', __('API token not configured', 'bridge-mls'));
        }
        
        if (empty(BRIDGE_API_URL)) {
            return new WP_Error('no_url', __('API URL not configured', 'bridge-mls'));
        }
        
        // Validate API URL format
        if (!filter_var(BRIDGE_API_URL, FILTER_VALIDATE_URL) || strpos(BRIDGE_API_URL, 'https://') !== 0) {
            return new WP_Error('invalid_url', __('Invalid API URL configuration', 'bridge-mls'));
        }
        
        // Build cache key
        $cache_key = 'bridge_mls_' . md5($endpoint . serialize($params));
        
        // Check cache first
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false && apply_filters('bridge_mls_enable_cache', true)) {
            return $cached_data;
        }
        
        $params['access_token'] = BRIDGE_SERVER_TOKEN;
        $url = rtrim(BRIDGE_API_URL, '/') . '/' . ltrim($endpoint, '/') . '?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'Bridge MLS WordPress Plugin v' . BRIDGE_MLS_VERSION
            ),
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            error_log('Bridge MLS API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            $error_message = sprintf(__('API request failed with status %d', 'bridge-mls'), $status_code);
            
            // Try to get more specific error from response
            if (!empty($body)) {
                $error_data = json_decode($body, true);
                if (isset($error_data['error']['message'])) {
                    $error_message = $error_data['error']['message'];
                } elseif (isset($error_data['message'])) {
                    $error_message = $error_data['message'];
                }
            }
            
            error_log('Bridge MLS API Error (' . $status_code . '): ' . $error_message);
            return new WP_Error('api_error', $error_message);
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Bridge MLS JSON Error: ' . json_last_error_msg());
            return new WP_Error('json_error', __('Invalid JSON response from API', 'bridge-mls'));
        }
        
        // Cache successful response
        if (apply_filters('bridge_mls_enable_cache', true)) {
            set_transient($cache_key, $data, $this->cache_expiration);
        }
        
        return $data;
    }
    
    /**
     * Search properties with improved filtering
     */
    public function search_properties($params = array()) {
        $params = $this->sanitize_search_params($params);
        
        $api_params = array(
            '$select' => 'ListingKey,ListingId,ListPrice,BedroomsTotal,BathroomsTotalInteger,LivingArea,City,StateOrProvince,UnparsedAddress,PublicRemarks,PropertyType,Media,PhotosCount,StandardStatus',
            '$top' => isset($params['limit']) ? intval($params['limit']) : 12,
            '$orderby' => 'ModificationTimestamp desc'
        );
        
        $filters = array();
        $filters[] = "StandardStatus eq 'Active'";
        $filters[] = "PhotosCount gt 0";
        
        // Apply custom filters
        $filters = apply_filters('bridge_mls_search_filters', $filters, $params);
        
        if (!empty($params['city'])) {
            $cities = is_array($params['city']) ? $params['city'] : array($params['city']);
            $city_filters = array();
            foreach ($cities as $city) {
                $city = str_replace("'", "''", trim($city));
                if (!empty($city)) {
                    $city_filters[] = "City eq '" . $city . "'";
                }
            }
            if (!empty($city_filters)) {
                $filters[] = '(' . implode(' or ', $city_filters) . ')';
            }
        }
        
        if (!empty($params['min_price']) && is_numeric($params['min_price'])) {
            $filters[] = "ListPrice ge " . intval($params['min_price']);
        }
        
        if (!empty($params['max_price']) && is_numeric($params['max_price'])) {
            $filters[] = "ListPrice le " . intval($params['max_price']);
        }
        
        if (!empty($params['bedrooms']) && $params['bedrooms'] !== 'any' && is_numeric($params['bedrooms'])) {
            $filters[] = "BedroomsTotal ge " . intval($params['bedrooms']);
        }
        
        if (!empty($params['bathrooms']) && $params['bathrooms'] !== 'any' && is_numeric($params['bathrooms'])) {
            $filters[] = "BathroomsTotalInteger ge " . intval($params['bathrooms']);
        }
        
        if (!empty($params['property_type']) && $params['property_type'] !== 'any') {
            $type_map = apply_filters('bridge_mls_property_type_map', array(
                'for_sale' => "PropertyType eq 'Residential'",
                'for_rent' => "(PropertyType eq 'Residential Lease' or PropertyType eq 'Rental')",
                'commercial_sale' => "PropertyType eq 'Commercial Sale'",
                'commercial_rent' => "PropertyType eq 'Commercial Lease'",
                'land' => "PropertyType eq 'Land'"
            ));
            
            if (isset($type_map[$params['property_type']])) {
                $filters[] = $type_map[$params['property_type']];
            }
        }
        
        if (!empty($params['keywords'])) {
            $keywords = str_replace("'", "''", sanitize_text_field($params['keywords']));
            $filters[] = "contains(PublicRemarks, '" . $keywords . "')";
        }
        
        if (!empty($filters)) {
            $api_params['$filter'] = implode(' and ', $filters);
        }
        
        // Allow modification of API parameters
        $api_params = apply_filters('bridge_mls_api_params', $api_params, $params);
        
        do_action('bridge_mls_before_search', $params);
        
        $result = $this->make_api_request('Property', $api_params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        if (isset($result['value']) && is_array($result['value'])) {
            foreach ($result['value'] as &$property) {
                $property['Photos'] = $this->extract_property_photos($property);
                $property = apply_filters('bridge_mls_property_data', $property);
            }
        }
        
        return $result;
    }
    
    /**
     * Get single property with enhanced data
     */
    public function get_single_property($listing_key) {
        if (empty($listing_key)) {
            return new WP_Error('invalid_key', __('Invalid listing key', 'bridge-mls'));
        }
        
        $params = array(
            '$filter' => "ListingKey eq '" . str_replace("'", "''", $listing_key) . "'",
            '$select' => 'ListingKey,ListingId,ListPrice,BedroomsTotal,BathroomsTotalInteger,LivingArea,LotSizeArea,YearBuilt,City,StateOrProvince,PostalCode,UnparsedAddress,PublicRemarks,PropertyType,PropertySubType,Media,PhotosCount,StandardStatus,ListingContractDate,ModificationTimestamp'
        );
        
        $result = $this->make_api_request('Property', $params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        if (isset($result['value']) && !empty($result['value']) && is_array($result['value'])) {
            $property = $result['value'][0];
            $property['Photos'] = $this->extract_property_photos($property);
            return apply_filters('bridge_mls_single_property', $property);
        }
        
        return false;
    }
    
    /**
     * Get property by MLS ID
     */
    public function get_property_by_mls_id($mls_id) {
        if (empty($mls_id)) {
            return new WP_Error('invalid_id', __('Invalid MLS ID', 'bridge-mls'));
        }
        
        $params = array(
            '$filter' => "ListingId eq '" . str_replace("'", "''", $mls_id) . "'",
            '$select' => 'ListingKey,ListingId,ListPrice,BedroomsTotal,BathroomsTotalInteger,LivingArea,LotSizeArea,YearBuilt,City,StateOrProvince,PostalCode,UnparsedAddress,PublicRemarks,PropertyType,PropertySubType,Media,PhotosCount,StandardStatus,ListingContractDate,ModificationTimestamp'
        );
        
        $result = $this->make_api_request('Property', $params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        if (isset($result['value']) && !empty($result['value']) && is_array($result['value'])) {
            $property = $result['value'][0];
            $property['Photos'] = $this->extract_property_photos($property);
            return apply_filters('bridge_mls_single_property', $property);
        }
        
        return false;
    }
    
    /**
     * Extract property photos with validation
     */
    private function extract_property_photos($property) {
        $photos = array();
        
        if (isset($property['Media']) && is_array($property['Media'])) {
            $photo_media = array_filter($property['Media'], function($media) {
                return isset($media['MediaCategory']) && 
                       $media['MediaCategory'] === 'Photo' && 
                       !empty($media['MediaURL']) &&
                       filter_var($media['MediaURL'], FILTER_VALIDATE_URL);
            });
            
            usort($photo_media, function($a, $b) {
                $orderA = isset($a['Order']) ? intval($a['Order']) : 999;
                $orderB = isset($b['Order']) ? intval($b['Order']) : 999;
                return $orderA - $orderB;
            });
            
            foreach ($photo_media as $media) {
                if (!empty($media['MediaURL'])) {
                    $photos[] = esc_url($media['MediaURL']);
                }
            }
        }
        
        return $photos;
    }
    
    /**
     * AJAX search properties
     */
    public function ajax_search_properties() {
        if (!check_ajax_referer('bridge_mls_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'bridge-mls'));
            return;
        }
        
        $search_params = array();
        
        if (isset($_POST['city'])) {
            $search_params['city'] = is_array($_POST['city']) ? 
                array_map('sanitize_text_field', $_POST['city']) : 
                sanitize_text_field($_POST['city']);
        }
        
        $fields = array('min_price', 'max_price', 'bedrooms', 'bathrooms', 'keywords', 'property_type', 'limit');
        foreach ($fields as $field) {
            if (isset($_POST[$field]) && !empty($_POST[$field])) {
                $search_params[$field] = sanitize_text_field($_POST[$field]);
            }
        }
        
        $result = $this->search_properties($search_params);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        if (isset($result['value']) && is_array($result['value'])) {
            $html = '';
            foreach ($result['value'] as $property) {
                $html .= $this->render_property_card($property);
            }
            
            wp_send_json_success(array(
                'html' => $html,
                'count' => count($result['value'])
            ));
        } else {
            wp_send_json_success(array(
                'html' => '<p class="no-properties">' . __('No properties found matching your criteria.', 'bridge-mls') . '</p>',
                'count' => 0
            ));
        }
    }
    
    /**
     * AJAX get property details
     */
    public function ajax_get_property_details() {
        if (!check_ajax_referer('bridge_mls_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'bridge-mls'));
            return;
        }
        
        $listing_key = isset($_POST['listing_key']) ? sanitize_text_field($_POST['listing_key']) : '';
        
        if (empty($listing_key)) {
            wp_send_json_error(__('No property specified', 'bridge-mls'));
            return;
        }
        
        $property = $this->get_single_property($listing_key);
        
        if (is_wp_error($property)) {
            wp_send_json_error($property->get_error_message());
            return;
        }
        
        if ($property) {
            wp_send_json_success(array(
                'html' => $this->render_property_details($property),
                'property' => $property
            ));
        } else {
            wp_send_json_error(__('Property not found', 'bridge-mls'));
        }
    }
    
    /**
     * AJAX contact agent handler
     */
    public function ajax_contact_agent() {
        if (!check_ajax_referer('bridge_mls_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'bridge-mls'));
            return;
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $property = isset($_POST['property']) ? sanitize_text_field($_POST['property']) : '';
        
        // Validation
        if (empty($name) || empty($email)) {
            wp_send_json_error(__('Name and email are required', 'bridge-mls'));
            return;
        }
        
        if (!is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address', 'bridge-mls'));
            return;
        }
        
        // Get agent email from settings
        $options = get_option('bridge_mls_options', array());
        $agent_email = isset($options['agent_email']) ? $options['agent_email'] : get_option('admin_email');
        $agent_name = isset($options['agent_name']) ? $options['agent_name'] : get_bloginfo('name');
        
        // Prepare email
        $subject = sprintf(__('Property Inquiry: %s', 'bridge-mls'), $property);
        
        $email_body = sprintf(
            __("New property inquiry from your website:\n\n" .
               "Property: %s\n\n" .
               "From: %s\n" .
               "Email: %s\n" .
               "Phone: %s\n\n" .
               "Message:\n%s\n\n" .
               "---\n" .
               "This message was sent from the Bridge MLS plugin on %s", 'bridge-mls'),
            $property,
            $name,
            $email,
            $phone ?: 'Not provided',
            $message,
            get_bloginfo('name')
        );
        
        $headers = array(
            'From: ' . $name . ' <' . $email . '>',
            'Reply-To: ' . $email
        );
        
        // Send email
        $sent = wp_mail($agent_email, $subject, $email_body, $headers);
        
        if ($sent) {
            // Send confirmation to inquirer
            $confirm_subject = __('Your property inquiry has been received', 'bridge-mls');
            $confirm_body = sprintf(
                __("Hello %s,\n\n" .
                   "Thank you for your interest in %s.\n\n" .
                   "We have received your inquiry and will contact you soon.\n\n" .
                   "Best regards,\n%s", 'bridge-mls'),
                $name,
                $property,
                $agent_name
            );
            
            wp_mail($email, $confirm_subject, $confirm_body);
            
            wp_send_json_success(__('Your message has been sent successfully!', 'bridge-mls'));
        } else {
            wp_send_json_error(__('Failed to send message. Please try again later.', 'bridge-mls'));
        }
    }
    
    /**
     * AJAX test API
     */
    public function ajax_test_api() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'bridge-mls'));
            return;
        }
        
        check_ajax_referer('bridge_mls_nonce', 'nonce');
        
        $tests = array();
        
        // Test 1: Basic connection
        $test_params = array(
            '$top' => 1,
            '$select' => 'ListingKey,ListPrice'
        );
        
        $result = $this->make_api_request('Property', $test_params);
        
        if (is_wp_error($result)) {
            $tests['basic'] = array(
                'name' => __('Basic Connection', 'bridge-mls'),
                'success' => false,
                'message' => $result->get_error_message()
            );
        } else {
            $tests['basic'] = array(
                'name' => __('Basic Connection', 'bridge-mls'),
                'success' => true,
                'message' => __('Connected successfully', 'bridge-mls')
            );
            
            // Test 2: Check data structure
            if (isset($result['value']) && is_array($result['value'])) {
                $tests['data'] = array(
                    'name' => __('Data Structure', 'bridge-mls'),
                    'success' => true,
                    'message' => sprintf(__('Found %d properties', 'bridge-mls'), count($result['value']))
                );
            } else {
                $tests['data'] = array(
                    'name' => __('Data Structure', 'bridge-mls'),
                    'success' => false,
                    'message' => __('Unexpected data format', 'bridge-mls')
                );
            }
        }
        
        $overall_success = !empty($tests) && !in_array(false, array_column($tests, 'success'));
        
        if ($overall_success) {
            wp_send_json_success(array(
                'message' => __('API connection successful!', 'bridge-mls'),
                'tests' => $tests
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('API test failed', 'bridge-mls'),
                'tests' => $tests
            ));
        }
    }
    
    /**
     * AJAX validate credentials (admin)
     */
    public function ajax_validate_credentials() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'bridge-mls'));
            return;
        }
        
        check_ajax_referer('bridge_mls_admin_nonce', 'nonce');
        
        $api_url = isset($_POST['api_url']) ? esc_url_raw($_POST['api_url']) : '';
        $server_token = isset($_POST['server_token']) ? sanitize_text_field($_POST['server_token']) : '';
        
        if (empty($api_url) || empty($server_token)) {
            wp_send_json_error(__('API URL and Server Token are required', 'bridge-mls'));
            return;
        }
        
        // Test the credentials
        $test_url = rtrim($api_url, '/') . '/Property?$top=1&access_token=' . $server_token;
        
        $response = wp_remote_get($test_url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'Bridge MLS WordPress Plugin v' . BRIDGE_MLS_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(sprintf(__('Connection error: %s', 'bridge-mls'), $response->get_error_message()));
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['value'])) {
                wp_send_json_success(array(
                    'message' => __('Credentials validated successfully!', 'bridge-mls'),
                    'tests' => array(
                        'auth' => array(
                            'name' => __('Authentication', 'bridge-mls'),
                            'success' => true,
                            'message' => __('Valid credentials', 'bridge-mls')
                        ),
                        'api' => array(
                            'name' => __('API Response', 'bridge-mls'),
                            'success' => true,
                            'message' => __('Valid JSON response', 'bridge-mls')
                        )
                    )
                ));
            } else {
                wp_send_json_error(__('Invalid API response format', 'bridge-mls'));
            }
        } else {
            $error_msg = __('Invalid credentials', 'bridge-mls');
            if ($status_code === 401) {
                $error_msg = __('Authentication failed - check your token', 'bridge-mls');
            } elseif ($status_code === 404) {
                $error_msg = __('API endpoint not found - check your URL', 'bridge-mls');
            }
            
            wp_send_json_error($error_msg);
        }
    }
    
    /**
     * Property search shortcode
     */
    public function property_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Property Search', 'bridge-mls'),
            'show_search' => 'true',
            'columns' => '3',
            'limit' => '12',
            'city' => '',
            'min_price' => '',
            'max_price' => '',
            'bedrooms' => '',
            'bathrooms' => '',
            'property_type' => '',
            'show_title' => 'true'
        ), $atts);
        
        ob_start();
        
        $initial_params = array();
        foreach (array('city', 'min_price', 'max_price', 'bedrooms', 'bathrooms', 'property_type') as $param) {
            if (isset($_GET[$param])) {
                $initial_params[$param] = sanitize_text_field($_GET[$param]);
            } elseif (!empty($atts[$param])) {
                $initial_params[$param] = $atts[$param];
            }
        }
        
        $initial_params['limit'] = intval($atts['limit']);
        
        ?>
        <div class="bridge-mls-container">
            <?php if ($atts['show_title'] === 'true'): ?>
                <h2 class="search-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            
            <?php if ($atts['show_search'] === 'true'): ?>
                <div class="bridge-property-search">
                    <?php $this->render_search_form($initial_params); ?>
                </div>
            <?php endif; ?>
            
            <div id="bridge-search-results">
                <div class="property-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                    <?php
                    $properties = $this->search_properties($initial_params);
                    if (!is_wp_error($properties) && isset($properties['value']) && is_array($properties['value'])) {
                        foreach ($properties['value'] as $property) {
                            echo $this->render_property_card($property);
                        }
                    } elseif (is_wp_error($properties)) {
                        echo '<p class="error">' . esc_html($properties->get_error_message()) . '</p>';
                    } else {
                        echo '<p class="no-properties">' . __('No properties found.', 'bridge-mls') . '</p>';
                    }
                    ?>
                </div>
            </div>
            
            <div id="bridge-loading" style="display: none;">
                <div class="loading-spinner"><?php _e('Searching properties...', 'bridge-mls'); ?></div>
            </div>
        </div>
        
        <script type="text/javascript">
            window.bridgeInitialParams = <?php echo json_encode($initial_params); ?>;
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Featured properties shortcode
     */
    public function featured_properties_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '6',
            'columns' => '3',
            'title' => __('Featured Properties', 'bridge-mls'),
            'city' => '',
            'min_price' => '',
            'max_price' => '',
            'show_title' => 'true'
        ), $atts);
        
        $search_params = array('limit' => intval($atts['limit']));
        
        foreach (array('city', 'min_price', 'max_price') as $param) {
            if (!empty($atts[$param])) {
                $search_params[$param] = $atts[$param];
            }
        }
        
        $properties = $this->search_properties($search_params);
        
        ob_start();
        ?>
        <div class="bridge-featured-properties">
            <?php if ($atts['show_title'] === 'true'): ?>
                <h2 class="featured-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            
            <div class="property-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                <?php
                if (!is_wp_error($properties) && isset($properties['value']) && is_array($properties['value'])) {
                    foreach ($properties['value'] as $property) {
                        echo $this->render_property_card($property);
                    }
                } else {
                    echo '<p class="no-properties">' . __('No featured properties found.', 'bridge-mls') . '</p>';
                }
                ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Property details shortcode
     */
    public function property_details_shortcode($atts) {
        $atts = shortcode_atts(array(
            'mls_id' => '',
            'listing_key' => ''
        ), $atts);
        
        $mls_id = !empty($atts['mls_id']) ? $atts['mls_id'] : 
                  (isset($_GET['mls']) ? sanitize_text_field($_GET['mls']) : '');
        
        $listing_key = !empty($atts['listing_key']) ? $atts['listing_key'] : 
                       (isset($_GET['listing_key']) ? sanitize_text_field($_GET['listing_key']) : '');
        
        if (empty($mls_id) && empty($listing_key)) {
            return '<p class="error">' . __('Property not specified.', 'bridge-mls') . '</p>';
        }
        
        $property = null;
        if (!empty($listing_key)) {
            $property = $this->get_single_property($listing_key);
        } elseif (!empty($mls_id)) {
            $property = $this->get_property_by_mls_id($mls_id);
        }
        
        if (is_wp_error($property)) {
            return '<p class="error">' . esc_html($property->get_error_message()) . '</p>';
        }
        
        if (!$property) {
            return '<p class="error">' . __('Property not found.', 'bridge-mls') . '</p>';
        }
        
        return $this->render_property_details($property);
    }
    
    /**
     * Render search form
     */
    private function render_search_form($params = array()) {
        $cities = apply_filters('bridge_mls_cities_list', $this->get_ma_cities());
        $selected_cities = isset($params['city']) ? 
            (is_array($params['city']) ? $params['city'] : array($params['city'])) : 
            array();
        ?>
        <form id="bridge-property-search-form">
            <div class="search-row">
                <div class="search-field search-field-wide">
                    <label for="bridge-city"><?php _e('City', 'bridge-mls'); ?></label>
                    <select id="bridge-city" name="city[]" multiple="multiple" class="bridge-multiselect">
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo esc_attr($city); ?>" 
                                    <?php echo in_array($city, $selected_cities) ? 'selected' : ''; ?>>
                                <?php echo esc_html($city); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="search-field">
                    <label for="bridge-min-price"><?php _e('Min Price', 'bridge-mls'); ?></label>
                    <input type="number" id="bridge-min-price" name="min_price" 
                           value="<?php echo esc_attr(isset($params['min_price']) ? $params['min_price'] : ''); ?>" 
                           placeholder="<?php esc_attr_e('No Min', 'bridge-mls'); ?>" min="0" step="10000">
                </div>
                
                <div class="search-field">
                    <label for="bridge-max-price"><?php _e('Max Price', 'bridge-mls'); ?></label>
                    <input type="number" id="bridge-max-price" name="max_price" 
                           value="<?php echo esc_attr(isset($params['max_price']) ? $params['max_price'] : ''); ?>" 
                           placeholder="<?php esc_attr_e('No Max', 'bridge-mls'); ?>" min="0" step="10000">
                </div>
            </div>
            
            <div class="search-row">
                <div class="search-field">
                    <label for="bridge-bedrooms"><?php _e('Bedrooms', 'bridge-mls'); ?></label>
                    <select id="bridge-bedrooms" name="bedrooms">
                        <option value="any"><?php _e('Any', 'bridge-mls'); ?></option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" 
                                    <?php echo (isset($params['bedrooms']) && $params['bedrooms'] == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>+
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="search-field">
                    <label for="bridge-bathrooms"><?php _e('Bathrooms', 'bridge-mls'); ?></label>
                    <select id="bridge-bathrooms" name="bathrooms">
                        <option value="any"><?php _e('Any', 'bridge-mls'); ?></option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" 
                                    <?php echo (isset($params['bathrooms']) && $params['bathrooms'] == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>+
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="search-field">
                    <label for="bridge-property-type"><?php _e('Property Type', 'bridge-mls'); ?></label>
                    <select id="bridge-property-type" name="property_type">
                        <option value="any"><?php _e('All Types', 'bridge-mls'); ?></option>
                        <option value="for_sale" <?php echo (isset($params['property_type']) && $params['property_type'] == 'for_sale') ? 'selected' : ''; ?>><?php _e('For Sale', 'bridge-mls'); ?></option>
                        <option value="for_rent" <?php echo (isset($params['property_type']) && $params['property_type'] == 'for_rent') ? 'selected' : ''; ?>><?php _e('For Rent', 'bridge-mls'); ?></option>
                        <option value="commercial_sale" <?php echo (isset($params['property_type']) && $params['property_type'] == 'commercial_sale') ? 'selected' : ''; ?>><?php _e('Commercial For Sale', 'bridge-mls'); ?></option>
                        <option value="commercial_rent" <?php echo (isset($params['property_type']) && $params['property_type'] == 'commercial_rent') ? 'selected' : ''; ?>><?php _e('Commercial For Rent', 'bridge-mls'); ?></option>
                        <option value="land" <?php echo (isset($params['property_type']) && $params['property_type'] == 'land') ? 'selected' : ''; ?>><?php _e('Land', 'bridge-mls'); ?></option>
                    </select>
                </div>
                
                <div class="search-field search-field-wide">
                    <label for="bridge-keywords"><?php _e('Keywords', 'bridge-mls'); ?></label>
                    <input type="text" id="bridge-keywords" name="keywords" 
                           value="<?php echo esc_attr(isset($params['keywords']) ? $params['keywords'] : ''); ?>" 
                           placeholder="<?php esc_attr_e('Search property descriptions...', 'bridge-mls'); ?>">
                </div>
            </div>
            
            <div class="search-actions">
                <button type="button" id="bridge-search-button" class="button button-primary"><?php _e('Search Properties', 'bridge-mls'); ?></button>
                <button type="button" id="bridge-clear-button" class="button button-secondary"><?php _e('Clear Filters', 'bridge-mls'); ?></button>
            </div>
        </form>
        <?php
    }
    
    /**
     * Render property card with enhanced security
     */
    private function render_property_card($property) {
        $photos = isset($property['Photos']) ? $property['Photos'] : array();
        $primary_photo = !empty($photos) ? $photos[0] : BRIDGE_MLS_NO_IMAGE;
        $photo_count = isset($property['PhotosCount']) ? intval($property['PhotosCount']) : count($photos);
        
        $property_url = home_url('/property-details/?mls=' . urlencode($property['ListingId']));
        
        ob_start();
        ?>
        <div class="property-card" data-listing-key="<?php echo esc_attr($property['ListingKey']); ?>">
            <div class="property-image">
                <img src="<?php echo esc_url($primary_photo); ?>" 
                     alt="<?php echo esc_attr($property['UnparsedAddress']); ?>" 
                     loading="lazy"
                     onerror="if(this.src !== '<?php echo esc_js(BRIDGE_MLS_NO_IMAGE); ?>') this.src='<?php echo esc_js(BRIDGE_MLS_NO_IMAGE); ?>';">
                <?php if ($photo_count > 0): ?>
                    <span class="photo-count">📷 <?php echo intval($photo_count); ?></span>
                <?php endif; ?>
                <?php if (isset($property['StandardStatus']) && $property['StandardStatus'] === 'Active'): ?>
                    <span class="property-status status-active"><?php _e('Active', 'bridge-mls'); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="property-info">
                <div class="property-price">
                    $<?php echo number_format(floatval($property['ListPrice'])); ?>
                </div>
                
                <div class="property-address">
                    <?php echo esc_html($property['UnparsedAddress']); ?><br>
                    <?php echo esc_html($property['City'] . ', ' . $property['StateOrProvince']); ?>
                </div>
                
                <div class="property-stats">
                    <?php if (!empty($property['BedroomsTotal'])): ?>
                        <span class="property-stat">
                            🛏️ <?php echo intval($property['BedroomsTotal']); ?> <?php _e('beds', 'bridge-mls'); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['BathroomsTotalInteger'])): ?>
                        <span class="property-stat">
                            🚿 <?php echo intval($property['BathroomsTotalInteger']); ?> <?php _e('baths', 'bridge-mls'); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['LivingArea'])): ?>
                        <span class="property-stat">
                            📐 <?php echo number_format(floatval($property['LivingArea'])); ?> <?php _e('sqft', 'bridge-mls'); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="property-type-tag">
                    <span class="property-type">
                        <?php echo esc_html($property['PropertyType']); ?>
                    </span>
                </div>
                
                <div class="property-actions">
                    <a href="<?php echo esc_url($property_url); ?>" class="button button-primary view-details">
                        <?php _e('View Details', 'bridge-mls'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render property details with enhanced features
     */
    private function render_property_details($property) {
        $photos = isset($property['Photos']) ? $property['Photos'] : array();
        $options = get_option('bridge_mls_options', array());
        
        // Calculate monthly payment estimate
        $monthly_payment = $this->calculate_monthly_payment(
            floatval($property['ListPrice']), 
            $options
        );
        
        // Add structured data for SEO
        $this->add_property_structured_data($property);
        
        ob_start();
        ?>
        <div class="bridge-property-details-modern">
            <!-- Full-width image gallery -->
            <?php $this->render_image_gallery($photos); ?>
            
            <!-- Property details container -->
            <div class="property-details-container">
                <!-- Header with title and actions -->
                <div class="property-header">
                    <div class="property-header-left">
                        <h1 class="property-title"><?php echo esc_html($property['UnparsedAddress']); ?></h1>
                        <div class="property-address">
                            <?php echo esc_html($property['City'] . ', ' . $property['StateOrProvince'] . ' ' . $property['PostalCode']); ?>
                        </div>
                        <?php if (isset($property['StandardStatus']) && $property['StandardStatus'] === 'Active'): ?>
                            <span class="property-status-badge"><?php _e('FOR SALE - ACTIVE', 'bridge-mls'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="property-header-right">
                        <button class="button-outline" onclick="window.BridgeMLSUtils.shareProperty()">
                            <?php _e('Share', 'bridge-mls'); ?>
                        </button>
                        <button class="button-outline" onclick="window.print()">
                            <?php _e('Print', 'bridge-mls'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Main content area -->
                <div class="property-content">
                    <div class="property-main">
                        <!-- Key details section -->
                        <div class="key-details">
                            <div class="detail-item">
                                <span class="detail-value">$<?php echo number_format(floatval($property['ListPrice'])); ?></span>
                                <span class="detail-label"><?php _e('List Price', 'bridge-mls'); ?></span>
                            </div>
                            <?php if (!empty($property['BedroomsTotal'])): ?>
                                <div class="detail-item">
                                    <span class="detail-value"><?php echo intval($property['BedroomsTotal']); ?></span>
                                    <span class="detail-label"><?php _e('Beds', 'bridge-mls'); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($property['BathroomsTotalInteger'])): ?>
                                <div class="detail-item">
                                    <span class="detail-value"><?php echo intval($property['BathroomsTotalInteger']); ?></span>
                                    <span class="detail-label"><?php _e('Baths', 'bridge-mls'); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($property['LivingArea'])): ?>
                                <div class="detail-item">
                                    <span class="detail-value"><?php echo number_format(floatval($property['LivingArea'])); ?></span>
                                    <span class="detail-label"><?php _e('Sq Ft', 'bridge-mls'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Monthly payment estimate -->
                        <?php if ($monthly_payment > 0): ?>
                            <div class="property-section">
                                <p style="margin: 0;">
                                    <strong><?php _e('Est. Monthly Payment:', 'bridge-mls'); ?></strong> 
                                    $<?php echo number_format($monthly_payment); ?>/<?php _e('mo', 'bridge-mls'); ?>
                                    <span style="color: #666; font-size: 14px;">
                                        (<?php echo intval($options['down_payment_percent'] ?? 20); ?>% <?php _e('down', 'bridge-mls'); ?>, 
                                        <?php echo intval($options['loan_years'] ?? 30); ?>-<?php _e('year fixed', 'bridge-mls'); ?> @ 
                                        <?php echo number_format(floatval($options['interest_rate'] ?? 7), 1); ?>%)
                                    </span>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- About this home -->
                        <div class="property-section">
                            <h2><?php _e('About this home', 'bridge-mls'); ?></h2>
                            <p><?php echo nl2br(esc_html($property['PublicRemarks'])); ?></p>
                        </div>
                        
                        <!-- Home facts -->
                        <div class="property-section">
                            <h2><?php _e('Home facts', 'bridge-mls'); ?></h2>
                            <div class="home-facts-grid">
                                <div class="fact-item">
                                    <span class="fact-label"><?php _e('MLS #', 'bridge-mls'); ?></span>
                                    <span class="fact-value"><?php echo esc_html($property['ListingId']); ?></span>
                                </div>
                                <div class="fact-item">
                                    <span class="fact-label"><?php _e('Property Type', 'bridge-mls'); ?></span>
                                    <span class="fact-value"><?php echo esc_html($property['PropertyType']); ?></span>
                                </div>
                                <?php if (!empty($property['PropertySubType'])): ?>
                                    <div class="fact-item">
                                        <span class="fact-label"><?php _e('Style', 'bridge-mls'); ?></span>
                                        <span class="fact-value"><?php echo esc_html($property['PropertySubType']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($property['YearBuilt'])): ?>
                                    <div class="fact-item">
                                        <span class="fact-label"><?php _e('Year Built', 'bridge-mls'); ?></span>
                                        <span class="fact-value"><?php echo esc_html($property['YearBuilt']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($property['LotSizeArea'])): ?>
                                    <div class="fact-item">
                                        <span class="fact-label"><?php _e('Lot Size', 'bridge-mls'); ?></span>
                                        <span class="fact-value"><?php echo number_format(floatval($property['LotSizeArea'])); ?> <?php _e('sq ft', 'bridge-mls'); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($property['LivingArea'])): ?>
                                    <div class="fact-item">
                                        <span class="fact-label"><?php _e('Living Area', 'bridge-mls'); ?></span>
                                        <span class="fact-value"><?php echo number_format(floatval($property['LivingArea'])); ?> <?php _e('sq ft', 'bridge-mls'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="property-sidebar">
                        <div class="agent-contact-card" id="agent-contact-modal">
                            <div class="modal-header">
                                <h3><?php _e('Contact agent', 'bridge-mls'); ?></h3>
                                <button class="modal-close" onclick="window.bridgeMLSApp.closeContactModal()" aria-label="<?php esc_attr_e('Close modal', 'bridge-mls'); ?>">&times;</button>
                            </div>
                            
                            <div class="tour-buttons">
                                <button class="button button-primary large"><?php _e('Request a showing', 'bridge-mls'); ?></button>
                                <button class="button button-outline"><?php _e('Start an offer', 'bridge-mls'); ?></button>
                            </div>
                            
                            <div class="agent-info">
                                <?php if (!empty($options['agent_name'])): ?>
                                    <div class="agent-name"><?php echo esc_html($options['agent_name']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($options['agent_company'])): ?>
                                    <div class="agent-company"><?php echo esc_html($options['agent_company']); ?></div>
                                <?php endif; ?>
                                
                                <?php if (!empty($options['agent_phone'])): ?>
                                    <a href="tel:<?php echo esc_attr($options['agent_phone']); ?>" class="button button-outline" style="margin-top: 10px;">
                                        <?php echo esc_html($options['agent_phone']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <form class="contact-form agent-contact-form" data-property="<?php echo esc_attr($property['UnparsedAddress']); ?>">
                                <input type="text" name="name" placeholder="<?php esc_attr_e('Your Name', 'bridge-mls'); ?>" required>
                                <input type="email" name="email" placeholder="<?php esc_attr_e('Your Email', 'bridge-mls'); ?>" required>
                                <input type="tel" name="phone" placeholder="<?php esc_attr_e('Your Phone', 'bridge-mls'); ?>">
                                <textarea name="message" rows="3" placeholder="<?php echo esc_attr(sprintf(__("I'm interested in %s", 'bridge-mls'), $property['UnparsedAddress'])); ?>"></textarea>
                                <button type="submit" class="button button-primary"><?php _e('Send message', 'bridge-mls'); ?></button>
                            </form>
                        </div>
                        
                        <!-- Mobile contact button -->
                        <button class="mobile-contact-button" onclick="window.bridgeMLSApp.openContactModal()">
                            <?php _e('Contact Agent', 'bridge-mls'); ?>
                        </button>
                        
                        <!-- Modal overlay -->
                        <div class="contact-modal-overlay" onclick="window.bridgeMLSApp.closeContactModal()"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render image gallery
     */
    private function render_image_gallery($photos) {
        if (empty($photos)) {
            $photos = array(BRIDGE_MLS_NO_IMAGE);
        }
        ?>
        <div class="property-gallery">
            <div class="gallery-container">
                <div class="main-image-wrapper">
                    <div class="main-image-container">
                        <img id="main-property-image" 
                             src="<?php echo esc_url($photos[0]); ?>" 
                             alt="<?php esc_attr_e('Property photo', 'bridge-mls'); ?>" 
                             data-index="0"
                             class="clickable-image">
                    </div>
                </div>
                
                <?php if (count($photos) > 1): ?>
                    <div class="side-images">
                        <?php for ($i = 1; $i <= 2 && $i < count($photos); $i++): ?>
                            <div class="side-image-container" data-index="<?php echo $i; ?>">
                                <img src="<?php echo esc_url($photos[$i]); ?>" 
                                     alt="<?php echo esc_attr(sprintf(__('Property photo %d', 'bridge-mls'), $i + 1)); ?>"
                                     class="clickable-image">
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (count($photos) > 3): ?>
                    <div class="view-all-photos">
                        <?php echo sprintf(__('View all %d photos', 'bridge-mls'), count($photos)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <script type="text/javascript">
            window.propertyPhotos = <?php echo json_encode($photos); ?>;
        </script>
        <?php
    }
    
    /**
     * Calculate monthly payment
     */
    private function calculate_monthly_payment($price, $options = array()) {
        if (!is_numeric($price) || $price <= 0) {
            return 0;
        }
        
        // Get mortgage parameters from options or use defaults
        $down_payment_percent = isset($options['down_payment_percent']) ? 
            floatval($options['down_payment_percent']) / 100 : 0.2;
        $interest_rate = isset($options['interest_rate']) ? 
            floatval($options['interest_rate']) / 100 : 0.07;
        $loan_years = isset($options['loan_years']) ? 
            intval($options['loan_years']) : 30;
        
        $down_payment = $price * $down_payment_percent;
        $loan_amount = $price - $down_payment;
        $monthly_rate = $interest_rate / 12;
        $num_payments = $loan_years * 12;
        
        if ($monthly_rate > 0 && $loan_amount > 0) {
            $monthly_payment = $loan_amount * ($monthly_rate * pow(1 + $monthly_rate, $num_payments)) / (pow(1 + $monthly_rate, $num_payments) - 1);
            return round($monthly_payment);
        }
        
        return 0;
    }
    
    /**
     * Add structured data for SEO
     */
    private function add_property_structured_data($property) {
        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateListing',
            'name' => $property['UnparsedAddress'],
            'description' => $property['PublicRemarks'],
            'url' => get_permalink()
        );
        
        if (!empty($property['ListPrice'])) {
            $structured_data['offers'] = array(
                '@type' => 'Offer',
                'price' => $property['ListPrice'],
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock'
            );
        }
        
        if (!empty($property['Photos'])) {
            $structured_data['image'] = $property['Photos'];
        }
        
        $structured_data['address'] = array(
            '@type' => 'PostalAddress',
            'streetAddress' => $property['UnparsedAddress'],
            'addressLocality' => $property['City'],
            'addressRegion' => $property['StateOrProvince'],
            'postalCode' => $property['PostalCode']
        );
        
        if (!empty($property['BedroomsTotal'])) {
            $structured_data['numberOfRooms'] = $property['BedroomsTotal'];
        }
        
        if (!empty($property['LivingArea'])) {
            $structured_data['floorSize'] = array(
                '@type' => 'QuantitativeValue',
                'value' => $property['LivingArea'],
                'unitCode' => 'FTK'
            );
        }
        
        echo '<script type="application/ld+json">' . json_encode($structured_data) . '</script>';
    }
    
    /**
     * Sanitize search parameters
     */
    private function sanitize_search_params($params) {
        $sanitized = array();
        
        if (isset($params['city'])) {
            if (is_array($params['city'])) {
                $sanitized['city'] = array_map('sanitize_text_field', $params['city']);
            } else {
                $sanitized['city'] = sanitize_text_field($params['city']);
            }
        }
        
        $numeric_fields = array('min_price', 'max_price', 'limit');
        foreach ($numeric_fields as $field) {
            if (isset($params[$field]) && is_numeric($params[$field])) {
                $sanitized[$field] = intval($params[$field]);
            }
        }
        
        $text_fields = array('bedrooms', 'bathrooms', 'property_type', 'keywords');
        foreach ($text_fields as $field) {
            if (isset($params[$field]) && !empty($params[$field])) {
                $sanitized[$field] = sanitize_text_field($params[$field]);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get Massachusetts cities
     */
    private function get_ma_cities() {
        return array(
            'Abington', 'Acton', 'Acushnet', 'Adams', 'Agawam', 'Alford', 'Amesbury', 'Amherst', 'Andover', 'Aquinnah',
            'Arlington', 'Ashburnham', 'Ashby', 'Ashfield', 'Ashland', 'Athol', 'Attleboro', 'Auburn', 'Avon', 'Ayer',
            'Barnstable', 'Barre', 'Becket', 'Bedford', 'Belchertown', 'Bellingham', 'Belmont', 'Berkley', 'Berlin',
            'Bernardston', 'Beverly', 'Billerica', 'Blackstone', 'Blandford', 'Bolton', 'Boston', 'Bourne', 'Boxborough',
            'Boxford', 'Boylston', 'Braintree', 'Brewster', 'Bridgewater', 'Brimfield', 'Brockton', 'Brookfield',
            'Brookline', 'Buckland', 'Burlington', 'Cambridge', 'Canton', 'Carlisle', 'Carver', 'Charlemont', 'Charlton',
            'Chatham', 'Chelmsford', 'Chelsea', 'Cheshire', 'Chester', 'Chesterfield', 'Chicopee', 'Chilmark', 'Clarksburg',
            'Clinton', 'Cohasset', 'Colrain', 'Concord', 'Conway', 'Cummington', 'Dalton', 'Danvers', 'Dartmouth',
            'Dedham', 'Deerfield', 'Dennis', 'Dighton', 'Douglas', 'Dover', 'Dracut', 'Dudley', 'Dunstable', 'Duxbury',
            'East Bridgewater', 'East Brookfield', 'East Longmeadow', 'Eastham', 'Easthampton', 'Easton', 'Edgartown',
            'Egremont', 'Erving', 'Essex', 'Everett', 'Fairhaven', 'Fall River', 'Falmouth', 'Fitchburg', 'Florida',
            'Foxborough', 'Framingham', 'Franklin', 'Freetown', 'Gardner', 'Georgetown', 'Gill', 'Gloucester', 'Goshen',
            'Gosnold', 'Grafton', 'Granby', 'Granville', 'Great Barrington', 'Greenfield', 'Groton', 'Groveland',
            'Hadley', 'Halifax', 'Hamilton', 'Hampden', 'Hancock', 'Hanover', 'Hanson', 'Hardwick', 'Harvard', 'Harwich',
            'Hatfield', 'Haverhill', 'Hawley', 'Heath', 'Hingham', 'Hinsdale', 'Holbrook', 'Holden', 'Holland',
            'Holliston', 'Holyoke', 'Hopedale', 'Hopkinton', 'Hubbardston', 'Hudson', 'Hull', 'Huntington', 'Ipswich',
            'Kingston', 'Lakeville', 'Lancaster', 'Lanesborough', 'Lawrence', 'Lee', 'Leicester', 'Lenox', 'Leominster',
            'Leverett', 'Lexington', 'Leyden', 'Lincoln', 'Littleton', 'Longmeadow', 'Lowell', 'Ludlow', 'Lunenburg',
            'Lynn', 'Lynnfield', 'Malden', 'Manchester-by-the-Sea', 'Mansfield', 'Marblehead', 'Marion', 'Marlborough',
            'Marshfield', 'Mashpee', 'Mattapoisett', 'Maynard', 'Medfield', 'Medford', 'Medway', 'Melrose', 'Mendon',
            'Merrimac', 'Methuen', 'Middleborough', 'Middlefield', 'Middleton', 'Milford', 'Millbury', 'Millis',
            'Millville', 'Milton', 'Monroe', 'Monson', 'Montague', 'Monterey', 'Montgomery', 'Mount Washington', 'Nahant',
            'Nantucket', 'Natick', 'Needham', 'New Ashford', 'New Bedford', 'New Braintree', 'New Marlborough',
            'New Salem', 'Newbury', 'Newburyport', 'Newton', 'Norfolk', 'North Adams', 'North Andover',
            'North Attleborough', 'North Brookfield', 'North Reading', 'Northampton', 'Northborough', 'Northbridge',
            'Northfield', 'Norton', 'Norwell', 'Norwood', 'Oak Bluffs', 'Oakham', 'Orange', 'Orleans', 'Otis', 'Oxford',
            'Palmer', 'Paxton', 'Peabody', 'Pelham', 'Pembroke', 'Pepperell', 'Peru', 'Petersham', 'Phillipston',
            'Pittsfield', 'Plainfield', 'Plainville', 'Plymouth', 'Plympton', 'Princeton', 'Provincetown', 'Quincy',
            'Randolph', 'Raynham', 'Reading', 'Rehoboth', 'Revere', 'Richmond', 'Rochester', 'Rockland', 'Rockport',
            'Rowe', 'Rowley', 'Royalston', 'Russell', 'Rutland', 'Salem', 'Salisbury', 'Sandisfield', 'Sandwich',
            'Saugus', 'Savoy', 'Scituate', 'Seekonk', 'Sharon', 'Sheffield', 'Shelburne', 'Sherborn', 'Shirley',
            'Shrewsbury', 'Shutesbury', 'Somerset', 'Somerville', 'South Hadley', 'Southampton', 'Southborough',
            'Southbridge', 'Southwick', 'Spencer', 'Springfield', 'Sterling', 'Stockbridge', 'Stoneham', 'Stoughton',
            'Stow', 'Sturbridge', 'Sudbury', 'Sunderland', 'Sutton', 'Swampscott', 'Swansea', 'Taunton', 'Templeton',
            'Tewksbury', 'Tisbury', 'Tolland', 'Topsfield', 'Townsend', 'Truro', 'Tyngsborough', 'Tyringham', 'Upton',
            'Uxbridge', 'Wakefield', 'Wales', 'Walpole', 'Waltham', 'Ware', 'Wareham', 'Warren', 'Warwick', 'Washington',
            'Watertown', 'Wayland', 'Webster', 'Wellesley', 'Wellfleet', 'Wendell', 'Wenham', 'West Boylston',
            'West Bridgewater', 'West Brookfield', 'West Newbury', 'West Springfield', 'West Stockbridge', 'West Tisbury',
            'Westborough', 'Westfield', 'Westford', 'Weston', 'Westport', 'Westwood', 'Weymouth', 'Whately', 'Whitman',
            'Wilbraham', 'Williamsburg', 'Williamstown', 'Wilmington', 'Winchendon', 'Winchester', 'Windsor', 'Winthrop',
            'Woburn', 'Worcester', 'Worthington', 'Wrentham', 'Yarmouth'
        );
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    BridgeMLSPlugin::get_instance();
});

// Activation hook
register_activation_hook(__FILE__, 'bridge_mls_activate');
function bridge_mls_activate() {
    $default_options = array(
        'api_url' => '',
        'server_token' => '',
        'browser_token' => '',
        'default_columns' => '3',
        'default_limit' => '12',
        'agent_name' => '',
        'agent_phone' => '',
        'agent_email' => '',
        'agent_company' => '',
        'down_payment_percent' => '20',
        'interest_rate' => '7',
        'loan_years' => '30'
    );
    
    $existing_options = get_option('bridge_mls_options', array());
    $merged_options = wp_parse_args($existing_options, $default_options);
    update_option('bridge_mls_options', $merged_options);
    
    // Flush rewrite rules for pretty URLs
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'bridge_mls_deactivate');
function bridge_mls_deactivate() {
    // Clean up transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bridge_mls_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_bridge_mls_%'");
    
    flush_rewrite_rules();
}