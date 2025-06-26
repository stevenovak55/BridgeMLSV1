<?php
/**
 * Bridge MLS Admin Interface
 * 
 * Handles all admin functionality including settings pages,
 * options management, and documentation.
 * 
 * @package Bridge_MLS
 * @since 3.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class BridgeMLSAdmin {
    
    /**
     * Plugin options
     */
    private $options;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // AJAX handlers
        add_action('wp_ajax_bridge_mls_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_bridge_mls_validate_credentials', array($this, 'ajax_validate_credentials'));
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Main settings page
        add_options_page(
            __('Bridge MLS Settings', 'bridge-mls'),
            __('Bridge MLS', 'bridge-mls'),
            'manage_options',
            'bridge-mls-settings',
            array($this, 'settings_page')
        );
        
        // Documentation page
        add_submenu_page(
            'options-general.php',
            __('Bridge MLS Documentation', 'bridge-mls'),
            __('MLS Documentation', 'bridge-mls'),
            'manage_options',
            'bridge-mls-docs',
            array($this, 'documentation_page')
        );
        
        // Tools page
        add_submenu_page(
            'options-general.php',
            __('Bridge MLS Tools', 'bridge-mls'),
            __('MLS Tools', 'bridge-mls'),
            'manage_options',
            'bridge-mls-tools',
            array($this, 'tools_page')
        );
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Check if API credentials are configured
        $options = get_option('bridge_mls_options', array());
        
        if (empty($options['api_url']) || empty($options['server_token'])) {
            $screen = get_current_screen();
            if ($screen && strpos($screen->id, 'bridge-mls') === false) {
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p><?php printf(
                        __('Bridge MLS: Please configure your API credentials. <a href="%s">Go to settings</a>', 'bridge-mls'),
                        admin_url('options-general.php?page=bridge-mls-settings')
                    ); ?></p>
                </div>
                <?php
            }
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our pages
        if (strpos($hook, 'bridge-mls') === false) {
            return;
        }
        
        // Admin styles
        wp_enqueue_style(
            'bridge-mls-admin', 
            BRIDGE_MLS_PLUGIN_URL . 'assets/bridge-mls-admin.css', 
            array(), 
            BRIDGE_MLS_VERSION
        );
        
        // Admin scripts
        wp_enqueue_script(
            'bridge-mls-admin',
            BRIDGE_MLS_PLUGIN_URL . 'assets/bridge-mls-admin.js',
            array('jquery'),
            BRIDGE_MLS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('bridge-mls-admin', 'bridgeMLSAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bridge_mls_admin_nonce'),
            'i18n' => array(
                'confirm_clear_cache' => __('Are you sure you want to clear all cached data?', 'bridge-mls'),
                'clearing_cache' => __('Clearing cache...', 'bridge-mls'),
                'cache_cleared' => __('Cache cleared successfully!', 'bridge-mls'),
                'error' => __('An error occurred. Please try again.', 'bridge-mls')
            )
        ));
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting(
            'bridge_mls_settings',
            'bridge_mls_options',
            array($this, 'sanitize_settings')
        );
        
        // API Configuration Section
        add_settings_section(
            'bridge_mls_api_section',
            __('API Configuration', 'bridge-mls'),
            array($this, 'api_section_callback'),
            'bridge-mls-settings'
        );
        
        add_settings_field(
            'api_url',
            __('API Base URL', 'bridge-mls'),
            array($this, 'api_url_callback'),
            'bridge-mls-settings',
            'bridge_mls_api_section'
        );
        
        add_settings_field(
            'server_token',
            __('Server Token', 'bridge-mls'),
            array($this, 'server_token_callback'),
            'bridge-mls-settings',
            'bridge_mls_api_section'
        );
        
        add_settings_field(
            'browser_token',
            __('Browser Token', 'bridge-mls'),
            array($this, 'browser_token_callback'),
            'bridge-mls-settings',
            'bridge_mls_api_section'
        );
        
        // Display Settings Section
        add_settings_section(
            'bridge_mls_display_section',
            __('Display Settings', 'bridge-mls'),
            array($this, 'display_section_callback'),
            'bridge-mls-settings'
        );
        
        add_settings_field(
            'default_columns',
            __('Default Grid Columns', 'bridge-mls'),
            array($this, 'default_columns_callback'),
            'bridge-mls-settings',
            'bridge_mls_display_section'
        );
        
        add_settings_field(
            'default_limit',
            __('Properties Per Page', 'bridge-mls'),
            array($this, 'default_limit_callback'),
            'bridge-mls-settings',
            'bridge_mls_display_section'
        );
        
        // Agent Information Section
        add_settings_section(
            'bridge_mls_agent_section',
            __('Agent Information', 'bridge-mls'),
            array($this, 'agent_section_callback'),
            'bridge-mls-settings'
        );
        
        add_settings_field(
            'agent_name',
            __('Agent Name', 'bridge-mls'),
            array($this, 'agent_name_callback'),
            'bridge-mls-settings',
            'bridge_mls_agent_section'
        );
        
        add_settings_field(
            'agent_phone',
            __('Agent Phone', 'bridge-mls'),
            array($this, 'agent_phone_callback'),
            'bridge-mls-settings',
            'bridge_mls_agent_section'
        );
        
        add_settings_field(
            'agent_email',
            __('Agent Email', 'bridge-mls'),
            array($this, 'agent_email_callback'),
            'bridge-mls-settings',
            'bridge_mls_agent_section'
        );
        
        add_settings_field(
            'agent_company',
            __('Agent Company', 'bridge-mls'),
            array($this, 'agent_company_callback'),
            'bridge-mls-settings',
            'bridge_mls_agent_section'
        );
        
        // Mortgage Calculator Section
        add_settings_section(
            'bridge_mls_mortgage_section',
            __('Mortgage Calculator Settings', 'bridge-mls'),
            array($this, 'mortgage_section_callback'),
            'bridge-mls-settings'
        );
        
        add_settings_field(
            'down_payment_percent',
            __('Default Down Payment %', 'bridge-mls'),
            array($this, 'down_payment_percent_callback'),
            'bridge-mls-settings',
            'bridge_mls_mortgage_section'
        );
        
        add_settings_field(
            'interest_rate',
            __('Default Interest Rate %', 'bridge-mls'),
            array($this, 'interest_rate_callback'),
            'bridge-mls-settings',
            'bridge_mls_mortgage_section'
        );
        
        add_settings_field(
            'loan_years',
            __('Default Loan Term (Years)', 'bridge-mls'),
            array($this, 'loan_years_callback'),
            'bridge-mls-settings',
            'bridge_mls_mortgage_section'
        );
    }
    
    /**
     * Section callbacks
     */
    public function api_section_callback() {
        echo '<p>' . __('Configure your Bridge MLS API credentials. These are required for the plugin to function.', 'bridge-mls') . '</p>';
        echo '<p>' . sprintf(
            __('Get your API credentials from the <a href="%s" target="_blank">Bridge Interactive Developer Portal</a>.', 'bridge-mls'),
            'https://developer.bridgeinteractive.com'
        ) . '</p>';
    }
    
    public function display_section_callback() {
        echo '<p>' . __('Configure how properties are displayed on your website.', 'bridge-mls') . '</p>';
    }
    
    public function agent_section_callback() {
        echo '<p>' . __('Enter your contact information to display on property detail pages.', 'bridge-mls') . '</p>';
    }
    
    public function mortgage_section_callback() {
        echo '<p>' . __('Configure default values for mortgage payment calculations.', 'bridge-mls') . '</p>';
    }
    
    /**
     * Field callbacks
     */
    public function api_url_callback() {
        $this->options = get_option('bridge_mls_options');
        $value = isset($this->options['api_url']) ? esc_attr($this->options['api_url']) : '';
        ?>
        <input type="url" id="api_url" name="bridge_mls_options[api_url]" value="<?php echo $value; ?>" class="regular-text code" />
        <p class="description"><?php _e('The base URL for the Bridge MLS API (e.g., https://api.bridgedataoutput.com/api/v2/OData/your_dataset)', 'bridge-mls'); ?></p>
        <?php
    }
    
    public function server_token_callback() {
        $value = isset($this->options['server_token']) ? esc_attr($this->options['server_token']) : '';
        ?>
        <input type="text" id="server_token" name="bridge_mls_options[server_token]" value="<?php echo $value; ?>" class="regular-text" />
        <p class="description"><?php _e('Server-side authentication token for API access', 'bridge-mls'); ?></p>
        <?php
    }
    
    public function browser_token_callback() {
        $value = isset($this->options['browser_token']) ? esc_attr($this->options['browser_token']) : '';
        ?>
        <input type="text" id="browser_token" name="bridge_mls_options[browser_token]" value="<?php echo $value; ?>" class="regular-text" />
        <p class="description"><?php _e('Browser-side authentication token (optional, for future use)', 'bridge-mls'); ?></p>
        <?php
    }
    
    public function default_columns_callback() {
        $value = isset($this->options['default_columns']) ? esc_attr($this->options['default_columns']) : '3';
        ?>
        <select id="default_columns" name="bridge_mls_options[default_columns]">
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <option value="<?php echo $i; ?>" <?php selected($value, $i); ?>>
                    <?php echo sprintf(_n('%d Column', '%d Columns', $i, 'bridge-mls'), $i); ?>
                </option>
            <?php endfor; ?>
        </select>
        <p class="description"><?php _e('Default number of columns for property grids', 'bridge-mls'); ?></p>
        <?php
    }
    
    public function default_limit_callback() {
        $value = isset($this->options['default_limit']) ? esc_attr($this->options['default_limit']) : '12';
        ?>
        <input type="number" id="default_limit" name="bridge_mls_options[default_limit]" value="<?php echo $value; ?>" min="1" max="100" />
        <p class="description"><?php _e('Default number of properties to display per page (1-100)', 'bridge-mls'); ?></p>
        <?php
    }
    
    public function agent_name_callback() {
        $value = isset($this->options['agent_name']) ? esc_attr($this->options['agent_name']) : '';
        ?>
        <input type="text" id="agent_name" name="bridge_mls_options[agent_name]" value="<?php echo $value; ?>" class="regular-text" />
        <p class="description"><?php _e('Agent name to display on property details', 'bridge-mls'); ?></p>
        <?php
    }
    
    public function agent_phone_callback() {
        $value = isset($this->options['agent_phone']) ? esc_attr($this->options['agent_phone']) : '';
        ?>
        <input type="tel" id="agent_phone" name="bridge_mls_options[agent_phone]" value="<?php echo $value; ?>" class="regular-text" />
        <p class="description"><?php _e('Agent phone number for contact', 'bridge-mls'); ?></p>
        <?php
    }
    
    public function agent_email_callback() {
        $value = isset($this->options['agent_email']) ? esc_attr($this->options['agent_email']) : '';
        ?>
        <input type="email" id="agent_email" name="bridge_mls_options[agent_email]" value="<?php echo $value; ?>" class="regular-text" />
        <p class="description"><?php _e('Agent email address for contact', 'bridge-mls'); ?></p>
        <?php
    }
    
    public function agent_company_callback() {
        $value = isset($this->options['agent_company']) ? esc_attr($this->options['agent_company']) : '';
        ?>
        <input type="text" id="agent_company" name="bridge_mls_options[agent_company]" value="<?php echo $value; ?>" class="regular-text" />
        <p class="description"><?php _e('Real estate company or brokerage name', 'bridge-mls'); ?></p>
        <?php
    }
    
    public function down_payment_percent_callback() {
        $value = isset($this->options['down_payment_percent']) ? esc_attr($this->options['down_payment_percent']) : '20';
        ?>
        <input type="number" id="down_payment_percent" name="bridge_mls_options[down_payment_percent]" value="<?php echo $value; ?>" min="0" max="100" step="0.1" />
        <span>%</span>
        <p class="description"><?php _e('Default down payment percentage for mortgage calculations', 'bridge-mls'); ?></p>
        <?php
    }
    
    public function interest_rate_callback() {
        $value = isset($this->options['interest_rate']) ? esc_attr($this->options['interest_rate']) : '7';
        ?>
        <input type="number" id="interest_rate" name="bridge_mls_options[interest_rate]" value="<?php echo $value; ?>" min="0" max="20" step="0.01" />
        <span>%</span>
        <p class="description"><?php _e('Default annual interest rate for mortgage calculations', 'bridge-mls'); ?></p>
        <?php
    }
    
    public function loan_years_callback() {
        $value = isset($this->options['loan_years']) ? esc_attr($this->options['loan_years']) : '30';
        ?>
        <select id="loan_years" name="bridge_mls_options[loan_years]">
            <option value="15" <?php selected($value, '15'); ?>>15 <?php _e('years', 'bridge-mls'); ?></option>
            <option value="20" <?php selected($value, '20'); ?>>20 <?php _e('years', 'bridge-mls'); ?></option>
            <option value="30" <?php selected($value, '30'); ?>>30 <?php _e('years', 'bridge-mls'); ?></option>
        </select>
        <p class="description"><?php _e('Default loan term for mortgage calculations', 'bridge-mls'); ?></p>
        <?php
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // API URL
        if (isset($input['api_url'])) {
            $url = esc_url_raw($input['api_url']);
            // Validate URL format
            if (filter_var($url, FILTER_VALIDATE_URL) && strpos($url, 'https://') === 0) {
                $sanitized['api_url'] = $url;
            } else {
                add_settings_error(
                    'bridge_mls_options',
                    'invalid_api_url',
                    __('Invalid API URL. Please enter a valid HTTPS URL.', 'bridge-mls')
                );
            }
        }
        
        // Tokens
        if (isset($input['server_token'])) {
            $sanitized['server_token'] = sanitize_text_field($input['server_token']);
        }
        
        if (isset($input['browser_token'])) {
            $sanitized['browser_token'] = sanitize_text_field($input['browser_token']);
        }
        
        // Display settings
        if (isset($input['default_columns'])) {
            $sanitized['default_columns'] = intval($input['default_columns']);
            if ($sanitized['default_columns'] < 1 || $sanitized['default_columns'] > 4) {
                $sanitized['default_columns'] = 3;
            }
        }
        
        if (isset($input['default_limit'])) {
            $sanitized['default_limit'] = intval($input['default_limit']);
            if ($sanitized['default_limit'] < 1 || $sanitized['default_limit'] > 100) {
                $sanitized['default_limit'] = 12;
            }
        }
        
        // Agent information
        if (isset($input['agent_name'])) {
            $sanitized['agent_name'] = sanitize_text_field($input['agent_name']);
        }
        
        if (isset($input['agent_phone'])) {
            $sanitized['agent_phone'] = sanitize_text_field($input['agent_phone']);
        }
        
        if (isset($input['agent_email'])) {
            $email = sanitize_email($input['agent_email']);
            if (!empty($email) && !is_email($email)) {
                add_settings_error(
                    'bridge_mls_options',
                    'invalid_email',
                    __('Invalid email address.', 'bridge-mls')
                );
            } else {
                $sanitized['agent_email'] = $email;
            }
        }
        
        if (isset($input['agent_company'])) {
            $sanitized['agent_company'] = sanitize_text_field($input['agent_company']);
        }
        
        // Mortgage settings
        if (isset($input['down_payment_percent'])) {
            $sanitized['down_payment_percent'] = floatval($input['down_payment_percent']);
            if ($sanitized['down_payment_percent'] < 0 || $sanitized['down_payment_percent'] > 100) {
                $sanitized['down_payment_percent'] = 20;
            }
        }
        
        if (isset($input['interest_rate'])) {
            $sanitized['interest_rate'] = floatval($input['interest_rate']);
            if ($sanitized['interest_rate'] < 0 || $sanitized['interest_rate'] > 20) {
                $sanitized['interest_rate'] = 7;
            }
        }
        
        if (isset($input['loan_years'])) {
            $sanitized['loan_years'] = intval($input['loan_years']);
            if (!in_array($sanitized['loan_years'], array(15, 20, 30))) {
                $sanitized['loan_years'] = 30;
            }
        }
        
        // Clear cache when settings change
        if ($sanitized !== get_option('bridge_mls_options')) {
            delete_transient('bridge_mls_api_cache');
            
            // Set flag to show success message
            set_transient('bridge_mls_settings_updated', true, 5);
        }
        
        return $sanitized;
    }
    
    /**
     * Settings page HTML
     */
    public function settings_page() {
        $this->options = get_option('bridge_mls_options');
        
        // Check for settings update
        if (get_transient('bridge_mls_settings_updated')) {
            delete_transient('bridge_mls_settings_updated');
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Settings saved and cache cleared successfully!', 'bridge-mls'); ?></p>
            </div>
            <?php
        }
        ?>
        <div class="wrap bridge-mls-admin">
            <h1><?php _e('Bridge MLS Settings', 'bridge-mls'); ?></h1>
            
            <div class="bridge-admin-container">
                <div class="bridge-admin-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('bridge_mls_settings');
                        do_settings_sections('bridge-mls-settings');
                        submit_button();
                        ?>
                    </form>
                    
                    <div class="bridge-debug-section">
                        <h2><?php _e('API Status', 'bridge-mls'); ?></h2>
                        <button type="button" id="bridge-admin-test-api" class="button button-secondary">
                            <?php _e('Test API Connection', 'bridge-mls'); ?>
                        </button>
                        <button type="button" id="bridge-admin-clear-cache" class="button button-secondary">
                            <?php _e('Clear Cache', 'bridge-mls'); ?>
                        </button>
                        <div id="bridge-admin-api-status" style="margin-top: 10px;"></div>
                    </div>
                </div>
                
                <div class="bridge-admin-sidebar">
                    <div class="bridge-sidebar-box">
                        <h3><?php _e('Quick Setup', 'bridge-mls'); ?></h3>
                        <ol>
                            <li><?php _e('Configure API credentials above', 'bridge-mls'); ?></li>
                            <li><?php _e('Test the API connection', 'bridge-mls'); ?></li>
                            <li><?php _e('Add agent information', 'bridge-mls'); ?></li>
                            <li><?php _e('Create a property search page', 'bridge-mls'); ?></li>
                            <li><?php _e('Use shortcode:', 'bridge-mls'); ?> <code>[bridge_property_search]</code></li>
                        </ol>
                        <a href="<?php echo admin_url('options-general.php?page=bridge-mls-docs'); ?>" class="button button-primary">
                            <?php _e('View Documentation', 'bridge-mls'); ?>
                        </a>
                    </div>
                    
                    <div class="bridge-sidebar-box">
                        <h3><?php _e('Shortcodes', 'bridge-mls'); ?></h3>
                        <p><strong><?php _e('Property Search:', 'bridge-mls'); ?></strong><br>
                        <code>[bridge_property_search]</code></p>
                        
                        <p><strong><?php _e('Featured Properties:', 'bridge-mls'); ?></strong><br>
                        <code>[bridge_featured_properties limit="6"]</code></p>
                        
                        <p><strong><?php _e('Property Details:', 'bridge-mls'); ?></strong><br>
                        <code>[bridge_property_details]</code></p>
                    </div>
                    
                    <div class="bridge-sidebar-box">
                        <h3><?php _e('Support', 'bridge-mls'); ?></h3>
                        <p><?php _e('Need help? Check the documentation page for detailed setup instructions and troubleshooting tips.', 'bridge-mls'); ?></p>
                        <p><a href="<?php echo admin_url('options-general.php?page=bridge-mls-tools'); ?>" class="button">
                            <?php _e('View Tools', 'bridge-mls'); ?>
                        </a></p>
                    </div>
                    
                    <div class="bridge-sidebar-box">
                        <h3><?php _e('Plugin Info', 'bridge-mls'); ?></h3>
                        <p><strong><?php _e('Version:', 'bridge-mls'); ?></strong> <?php echo BRIDGE_MLS_VERSION; ?></p>
                        <p><strong><?php _e('PHP Version:', 'bridge-mls'); ?></strong> <?php echo PHP_VERSION; ?></p>
                        <p><strong><?php _e('WordPress Version:', 'bridge-mls'); ?></strong> <?php echo get_bloginfo('version'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Test API connection
            $('#bridge-admin-test-api').on('click', function() {
                const button = $(this);
                const status = $('#bridge-admin-api-status');
                
                button.prop('disabled', true).text('<?php _e('Testing...', 'bridge-mls'); ?>');
                status.html('<em><?php _e('Testing API connection...', 'bridge-mls'); ?></em>');
                
                $.post(ajaxurl, {
                    action: 'bridge_test_api',
                    nonce: '<?php echo wp_create_nonce('bridge_mls_nonce'); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        status.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        if (response.data.tests) {
                            let testHtml = '<ul>';
                            $.each(response.data.tests, function(key, test) {
                                const icon = test.success ? '✅' : '❌';
                                testHtml += '<li>' + icon + ' ' + test.name + ': ' + test.message + '</li>';
                            });
                            testHtml += '</ul>';
                            status.append(testHtml);
                        }
                    } else {
                        status.html('<div class="notice notice-error inline"><p><?php _e('API Test Failed:', 'bridge-mls'); ?> ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    status.html('<div class="notice notice-error inline"><p><?php _e('Connection failed. Please check your settings.', 'bridge-mls'); ?></p></div>');
                })
                .always(function() {
                    button.prop('disabled', false).text('<?php _e('Test API Connection', 'bridge-mls'); ?>');
                });
            });
            
            // Clear cache
            $('#bridge-admin-clear-cache').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to clear all cached data?', 'bridge-mls'); ?>')) {
                    return;
                }
                
                const button = $(this);
                button.prop('disabled', true).text('<?php _e('Clearing...', 'bridge-mls'); ?>');
                
                $.post(ajaxurl, {
                    action: 'bridge_mls_clear_cache',
                    nonce: '<?php echo wp_create_nonce('bridge_mls_admin_nonce'); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert('<?php _e('Error clearing cache:', 'bridge-mls'); ?> ' + response.data);
                    }
                })
                .fail(function() {
                    alert('<?php _e('Failed to clear cache. Please try again.', 'bridge-mls'); ?>');
                })
                .always(function() {
                    button.prop('disabled', false).text('<?php _e('Clear Cache', 'bridge-mls'); ?>');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Documentation page HTML
     */
    public function documentation_page() {
        ?>
        <div class="wrap bridge-mls-docs">
            <h1><?php _e('Bridge MLS Documentation', 'bridge-mls'); ?></h1>
            
            <div class="bridge-docs-container">
                <div class="bridge-docs-toc">
                    <h3><?php _e('Table of Contents', 'bridge-mls'); ?></h3>
                    <ol>
                        <li><a href="#getting-started"><?php _e('Getting Started', 'bridge-mls'); ?></a></li>
                        <li><a href="#shortcodes"><?php _e('Shortcodes', 'bridge-mls'); ?></a></li>
                        <li><a href="#property-search"><?php _e('Property Search', 'bridge-mls'); ?></a></li>
                        <li><a href="#property-details"><?php _e('Property Details', 'bridge-mls'); ?></a></li>
                        <li><a href="#customization"><?php _e('Customization', 'bridge-mls'); ?></a></li>
                        <li><a href="#hooks-filters"><?php _e('Hooks & Filters', 'bridge-mls'); ?></a></li>
                        <li><a href="#troubleshooting"><?php _e('Troubleshooting', 'bridge-mls'); ?></a></li>
                        <li><a href="#api-reference"><?php _e('API Reference', 'bridge-mls'); ?></a></li>
                    </ol>
                </div>
                
                <div class="bridge-docs-content">
                    <?php include BRIDGE_MLS_PLUGIN_DIR . 'includes/documentation.php'; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Tools page
     */
    public function tools_page() {
        ?>
        <div class="wrap bridge-mls-tools">
            <h1><?php _e('Bridge MLS Tools', 'bridge-mls'); ?></h1>
            
            <div class="bridge-tools-container">
                <div class="tool-section">
                    <h2><?php _e('Cache Management', 'bridge-mls'); ?></h2>
                    <p><?php _e('Manage plugin cache and transients.', 'bridge-mls'); ?></p>
                    
                    <?php
                    // Get cache statistics
                    global $wpdb;
                    $cache_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                        '_transient_bridge_mls_%'
                    ));
                    ?>
                    
                    <p><?php printf(__('Current cached items: <strong>%d</strong>', 'bridge-mls'), $cache_count); ?></p>
                    
                    <button type="button" class="button button-primary" id="clear-all-cache">
                        <?php _e('Clear All Cache', 'bridge-mls'); ?>
                    </button>
                </div>
                
                <div class="tool-section">
                    <h2><?php _e('Export/Import Settings', 'bridge-mls'); ?></h2>
                    <p><?php _e('Backup or transfer your plugin settings.', 'bridge-mls'); ?></p>
                    
                    <h3><?php _e('Export Settings', 'bridge-mls'); ?></h3>
                    <button type="button" class="button" id="export-settings">
                        <?php _e('Download Settings', 'bridge-mls'); ?>
                    </button>
                    
                    <h3><?php _e('Import Settings', 'bridge-mls'); ?></h3>
                    <input type="file" id="import-settings-file" accept=".json">
                    <button type="button" class="button" id="import-settings" disabled>
                        <?php _e('Import Settings', 'bridge-mls'); ?>
                    </button>
                </div>
                
                <div class="tool-section">
                    <h2><?php _e('System Info', 'bridge-mls'); ?></h2>
                    <textarea class="system-info" readonly>
<?php echo $this->get_system_info(); ?>
                    </textarea>
                    <button type="button" class="button" id="copy-system-info">
                        <?php _e('Copy System Info', 'bridge-mls'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Clear cache
            $('#clear-all-cache').on('click', function() {
                // Implementation handled in main script
            });
            
            // Export settings
            $('#export-settings').on('click', function() {
                window.location.href = '<?php echo admin_url('admin-ajax.php?action=bridge_mls_export_settings&nonce=' . wp_create_nonce('bridge_mls_export')); ?>';
            });
            
            // Import settings
            $('#import-settings-file').on('change', function() {
                $('#import-settings').prop('disabled', !this.files.length);
            });
            
            $('#import-settings').on('click', function() {
                const file = $('#import-settings-file')[0].files[0];
                if (!file) return;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const settings = JSON.parse(e.target.result);
                        // Process import
                        console.log('Import settings:', settings);
                    } catch (error) {
                        alert('<?php _e('Invalid settings file', 'bridge-mls'); ?>');
                    }
                };
                reader.readAsText(file);
            });
            
            // Copy system info
            $('#copy-system-info').on('click', function() {
                const textarea = $('.system-info')[0];
                textarea.select();
                document.execCommand('copy');
                $(this).text('<?php _e('Copied!', 'bridge-mls'); ?>');
                setTimeout(() => {
                    $(this).text('<?php _e('Copy System Info', 'bridge-mls'); ?>');
                }, 2000);
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler to clear cache
     */
    public function ajax_clear_cache() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'bridge-mls'));
            return;
        }
        
        // Verify nonce
        if (!check_ajax_referer('bridge_mls_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'bridge-mls'));
            return;
        }
        
        // Clear all transients
        global $wpdb;
        $cleared = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bridge_mls_%'");
        $cleared += $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_bridge_mls_%'");
        
        wp_send_json_success(array(
            'message' => sprintf(__('Cache cleared successfully! %d items removed.', 'bridge-mls'), $cleared / 2)
        ));
    }
    
    /**
     * Get system info for debugging
     */
    private function get_system_info() {
        $info = array();
        
        $info[] = '=== Bridge MLS System Info ===';
        $info[] = '';
        
        // Plugin info
        $info[] = 'Plugin Version: ' . BRIDGE_MLS_VERSION;
        $info[] = 'WordPress Version: ' . get_bloginfo('version');
        $info[] = 'PHP Version: ' . PHP_VERSION;
        $info[] = 'MySQL Version: ' . $GLOBALS['wpdb']->db_version();
        $info[] = '';
        
        // Server info
        $info[] = 'Server Software: ' . $_SERVER['SERVER_SOFTWARE'];
        $info[] = 'Server Protocol: ' . $_SERVER['SERVER_PROTOCOL'];
        $info[] = 'HTTPS: ' . (is_ssl() ? 'Yes' : 'No');
        $info[] = '';
        
        // PHP settings
        $info[] = 'Memory Limit: ' . ini_get('memory_limit');
        $info[] = 'Max Execution Time: ' . ini_get('max_execution_time');
        $info[] = 'Max Input Vars: ' . ini_get('max_input_vars');
        $info[] = 'Post Max Size: ' . ini_get('post_max_size');
        $info[] = 'Upload Max Filesize: ' . ini_get('upload_max_filesize');
        $info[] = '';
        
        // WordPress settings
        $info[] = 'Site URL: ' . get_site_url();
        $info[] = 'Home URL: ' . get_home_url();
        $info[] = 'WP_DEBUG: ' . (defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled');
        $info[] = 'WP_DEBUG_LOG: ' . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'Enabled' : 'Disabled');
        $info[] = 'WP_DEBUG_DISPLAY: ' . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'Enabled' : 'Disabled');
        $info[] = '';
        
        // Active theme
        $theme = wp_get_theme();
        $info[] = 'Active Theme: ' . $theme->get('Name') . ' ' . $theme->get('Version');
        $info[] = '';
        
        // Active plugins
        $info[] = 'Active Plugins:';
        $active_plugins = get_option('active_plugins');
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $info[] = '- ' . $plugin_data['Name'] . ' ' . $plugin_data['Version'];
        }
        
        return implode("\n", $info);
    }
}