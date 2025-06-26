/**
 * Bridge MLS Admin JavaScript
 * Version 3.0.2 - Fixed
 */

(function($) {
    'use strict';

    /**
     * Bridge MLS Admin Class
     */
    class BridgeMLSAdminApp {
        constructor() {
            this.init();
        }

        /**
         * Initialize admin functionality
         */
        init() {
            this.bindEvents();
            this.initTooltips();
            this.validateSettings();
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            // API URL validation
            $('#api_url').on('blur', this.validateAPIUrl.bind(this));
            
            // Token validation
            $('#server_token').on('blur', this.validateToken.bind(this));
            
            // Email validation
            $('#agent_email').on('blur', this.validateEmail.bind(this));
            
            // Phone number formatting
            $('#agent_phone').on('input', this.formatPhoneNumber.bind(this));
            
            // Clear cache button
            $('#bridge-admin-clear-cache').on('click', this.clearCache.bind(this));
            
            // Test credentials button
            $('#bridge-admin-test-api').on('click', this.testCredentials.bind(this));
            
            // Export settings
            $('#export-settings').on('click', this.exportSettings.bind(this));
            
            // Import settings
            $('#import-settings-file').on('change', this.enableImport.bind(this));
            $('#import-settings').on('click', this.importSettings.bind(this));
            
            // Copy system info
            $('#copy-system-info').on('click', this.copySystemInfo.bind(this));
            
            // Tab navigation
            $('.nav-tab').on('click', this.handleTabClick.bind(this));
            
            // Clear all cache button (tools page)
            $('#clear-all-cache').on('click', this.clearCache.bind(this));
        }

        /**
         * Initialize tooltips
         */
        initTooltips() {
            $('.bridge-tooltip').each(function() {
                const tooltip = $(this).data('tooltip');
                if (tooltip) {
                    $(this).attr('title', tooltip);
                }
            });
        }

        /**
         * Validate settings on page load
         */
        validateSettings() {
            if ($('#api_url').length) {
                this.validateAPIUrl();
            }
            if ($('#server_token').length) {
                this.validateToken();
            }
            if ($('#agent_email').length) {
                this.validateEmail();
            }
        }

        /**
         * Validate API URL format
         */
        validateAPIUrl() {
            const $input = $('#api_url');
            if (!$input.length) return true;
            
            const url = $input.val();
            
            if (!url) {
                this.showFieldError($input, bridgeMLSAdmin.i18n.api_url_required || 'API URL is required');
                return false;
            }
            
            // Check if URL is valid
            try {
                const urlObj = new URL(url);
                
                // Must be HTTPS
                if (urlObj.protocol !== 'https:') {
                    this.showFieldError($input, bridgeMLSAdmin.i18n.api_url_https || 'API URL must use HTTPS');
                    return false;
                }
                
                // Must contain expected path
                if (!url.includes('/api/') || !url.includes('/OData/')) {
                    this.showFieldError($input, bridgeMLSAdmin.i18n.api_url_format || 'API URL must contain /api/ and /OData/ paths');
                    return false;
                }
                
                this.clearFieldError($input);
                return true;
                
            } catch (e) {
                this.showFieldError($input, bridgeMLSAdmin.i18n.api_url_invalid || 'Invalid URL format');
                return false;
            }
        }

        /**
         * Validate token format
         */
        validateToken() {
            const $input = $('#server_token');
            if (!$input.length) return true;
            
            const token = $input.val();
            
            if (!token) {
                this.showFieldError($input, bridgeMLSAdmin.i18n.token_required || 'Server token is required');
                return false;
            }
            
            // Token should be alphanumeric (allow hyphens and underscores)
            if (!/^[a-zA-Z0-9_-]+$/.test(token)) {
                this.showFieldError($input, bridgeMLSAdmin.i18n.token_invalid || 'Token contains invalid characters');
                return false;
            }
            
            this.clearFieldError($input);
            return true;
        }

        /**
         * Validate email format
         */
        validateEmail() {
            const $input = $('#agent_email');
            if (!$input.length) return true;
            
            const email = $input.val();
            
            if (!email) {
                this.clearFieldError($input);
                return true; // Email is optional
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                this.showFieldError($input, bridgeMLSAdmin.i18n.email_invalid || 'Invalid email address');
                return false;
            }
            
            this.clearFieldError($input);
            return true;
        }

        /**
         * Format phone number as user types
         */
        formatPhoneNumber(e) {
            const $input = $(e.target);
            let value = $input.val().replace(/\D/g, '');
            
            if (value.length >= 10) {
                value = value.slice(0, 10);
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 6) {
                value = value.replace(/(\d{3})(\d{3})/, '($1) $2-');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{3})/, '($1) ');
            }
            
            $input.val(value);
        }

        /**
         * Show field error
         */
        showFieldError($field, message) {
            // Remove existing error
            this.clearFieldError($field);
            
            // Add error class
            $field.addClass('error');
            
            // Add error message
            const $error = $('<span class="field-error" style="color: #dc3232; font-size: 13px; display: block; margin-top: 5px;">' + message + '</span>');
            $field.after($error);
        }

        /**
         * Clear field error
         */
        clearFieldError($field) {
            $field.removeClass('error');
            $field.next('.field-error').remove();
        }

        /**
         * Clear cache
         */
        clearCache(e) {
            e.preventDefault();
            
            if (!confirm(bridgeMLSAdmin.i18n.confirm_clear_cache || 'Are you sure you want to clear all cached data?')) {
                return;
            }
            
            const $button = $(e.currentTarget);
            const originalText = $button.text();
            
            $button.prop('disabled', true).text(bridgeMLSAdmin.i18n.clearing_cache || 'Clearing cache...');
            
            $.post(bridgeMLSAdmin.ajax_url, {
                action: 'bridge_mls_clear_cache',
                nonce: bridgeMLSAdmin.nonce
            })
            .done((response) => {
                if (response.success) {
                    this.showNotice('success', response.data.message || 'Cache cleared successfully!');
                } else {
                    this.showNotice('error', response.data || bridgeMLSAdmin.i18n.error || 'An error occurred');
                }
            })
            .fail(() => {
                this.showNotice('error', bridgeMLSAdmin.i18n.error || 'Connection error');
            })
            .always(() => {
                $button.prop('disabled', false).text(originalText);
            });
        }

        /**
         * Test API credentials
         */
        testCredentials(e) {
            e.preventDefault();
            
            // Validate fields first
            if (!this.validateAPIUrl() || !this.validateToken()) {
                this.showNotice('error', bridgeMLSAdmin.i18n.fix_errors || 'Please fix the errors above');
                return;
            }
            
            const $button = $(e.currentTarget);
            const originalText = $button.text();
            const $statusDiv = $('#bridge-admin-api-status');
            
            $button.prop('disabled', true).text(bridgeMLSAdmin.i18n.testing || 'Testing...');
            $statusDiv.html('<em>Testing API connection...</em>');
            
            $.post(bridgeMLSAdmin.ajax_url, {
                action: 'bridge_mls_validate_credentials',
                nonce: bridgeMLSAdmin.nonce,
                api_url: $('#api_url').val(),
                server_token: $('#server_token').val()
            })
            .done((response) => {
                if (response.success) {
                    this.showNotice('success', response.data.message || 'API connection successful!');
                    this.displayTestResults(response.data.tests);
                } else {
                    this.showNotice('error', response.data || bridgeMLSAdmin.i18n.test_failed || 'API test failed');
                    $statusDiv.html(`<div class="notice notice-error inline"><p>${response.data || 'Test failed'}</p></div>`);
                }
            })
            .fail((xhr) => {
                const errorMsg = bridgeMLSAdmin.i18n.connection_error || 'Connection error';
                this.showNotice('error', errorMsg);
                $statusDiv.html(`<div class="notice notice-error inline"><p>${errorMsg}</p></div>`);
            })
            .always(() => {
                $button.prop('disabled', false).text(originalText);
            });
        }

        /**
         * Display test results
         */
        displayTestResults(tests) {
            const $statusDiv = $('#bridge-admin-api-status');
            
            if (!tests) {
                $statusDiv.html('<div class="notice notice-success inline"><p>Connection successful!</p></div>');
                return;
            }
            
            let html = '<div class="test-results"><h4>Test Results:</h4><ul style="margin: 10px 0 0 20px;">';
            
            for (let key in tests) {
                const test = tests[key];
                const icon = test.success ? '✅' : '❌';
                const className = test.success ? 'bridge-test-success' : 'bridge-test-error';
                html += `<li class="${className}">${icon} ${test.name}: ${test.message}</li>`;
            }
            
            html += '</ul></div>';
            
            $statusDiv.html(html);
        }

        /**
         * Export settings
         */
        exportSettings(e) {
            e.preventDefault();
            
            // Create export data
            const settings = {
                version: bridgeMLSAdmin.plugin_version || '3.0.2',
                exported: new Date().toISOString(),
                settings: {}
            };
            
            // Collect form data
            $('#bridge_mls_settings input, #bridge_mls_settings select').each(function() {
                const name = $(this).attr('name');
                if (name && name.includes('bridge_mls_options')) {
                    const matches = name.match(/\[(.*?)\]/);
                    if (matches && matches[1]) {
                        const key = matches[1];
                        settings.settings[key] = $(this).val();
                    }
                }
            });
            
            // Create download
            const dataStr = JSON.stringify(settings, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
            
            const exportFileDefaultName = 'bridge-mls-settings-' + new Date().toISOString().slice(0, 10) + '.json';
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
            
            this.showNotice('success', 'Settings exported successfully!');
        }

        /**
         * Enable import button
         */
        enableImport(e) {
            const file = e.target.files[0];
            $('#import-settings').prop('disabled', !file);
        }

        /**
         * Import settings
         */
        importSettings(e) {
            e.preventDefault();
            
            const fileInput = $('#import-settings-file')[0];
            const file = fileInput.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const settings = JSON.parse(e.target.result);
                    
                    // Validate settings format
                    if (!settings.version || !settings.settings) {
                        throw new Error('Invalid settings format');
                    }
                    
                    // Apply settings
                    let appliedCount = 0;
                    for (let key in settings.settings) {
                        const $field = $(`[name="bridge_mls_options[${key}]"]`);
                        if ($field.length) {
                            $field.val(settings.settings[key]);
                            appliedCount++;
                        }
                    }
                    
                    this.showNotice('success', bridgeMLSAdmin.i18n.import_success || `Import successful! ${appliedCount} settings applied.`);
                    
                    // Re-validate fields
                    this.validateSettings();
                    
                    // Clear file input
                    fileInput.value = '';
                    $('#import-settings').prop('disabled', true);
                    
                } catch (error) {
                    console.error('Import error:', error);
                    this.showNotice('error', bridgeMLSAdmin.i18n.import_error || 'Invalid settings file');
                }
            };
            
            reader.readAsText(file);
        }

        /**
         * Copy system info
         */
        copySystemInfo(e) {
            e.preventDefault();
            
            const $textarea = $('.system-info');
            if (!$textarea.length) return;
            
            const textarea = $textarea[0];
            textarea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    const $button = $(e.target);
                    const originalText = $button.text();
                    $button.text(bridgeMLSAdmin.i18n.copied || 'Copied!');
                    
                    setTimeout(() => {
                        $button.text(originalText);
                    }, 2000);
                }
            } catch (err) {
                console.error('Copy failed:', err);
                // Fallback for modern browsers
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(textarea.value).then(() => {
                        const $button = $(e.target);
                        const originalText = $button.text();
                        $button.text(bridgeMLSAdmin.i18n.copied || 'Copied!');
                        
                        setTimeout(() => {
                            $button.text(originalText);
                        }, 2000);
                    });
                }
            }
        }

        /**
         * Handle tab navigation
         */
        handleTabClick(e) {
            e.preventDefault();
            
            const $tab = $(e.target);
            const target = $tab.attr('href');
            
            if (!target || target === '#') return;
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $tab.addClass('nav-tab-active');
            
            // Show target content
            $('.tab-content').removeClass('active').hide();
            $(target).addClass('active').show();
            
            // Update URL without reload
            if (history.pushState) {
                const url = new URL(window.location);
                url.searchParams.set('tab', target.substring(1));
                history.pushState(null, '', url);
            }
        }

        /**
         * Show admin notice
         */
        showNotice(type, message) {
            // Remove existing notices
            $('.bridge-admin-notice').remove();
            
            const $notice = $(`
                <div class="notice notice-${type} bridge-admin-notice is-dismissible">
                    <p>${message}</p>
                </div>
            `);
            
            // Find the best place to insert the notice
            const $heading = $('.wrap h1').first();
            if ($heading.length) {
                $heading.after($notice);
            } else {
                $('.wrap').prepend($notice);
            }
            
            // Trigger WordPress notice dismiss handler
            if (typeof wp !== 'undefined' && wp.notices) {
                $(document).trigger('wp-updates-notice-added');
            }
            
            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    $notice.fadeOut(() => $notice.remove());
                }, 5000);
            }
        }

        /**
         * Initialize color picker (if needed)
         */
        initColorPicker() {
            if ($.fn.wpColorPicker) {
                $('.color-picker').wpColorPicker();
            }
        }

        /**
         * Monitor form changes
         */
        monitorFormChanges() {
            let formChanged = false;
            
            $('form').on('change', 'input, select, textarea', function() {
                formChanged = true;
            });
            
            $(window).on('beforeunload', function() {
                if (formChanged) {
                    return bridgeMLSAdmin.i18n.unsaved_changes || 'You have unsaved changes. Are you sure you want to leave?';
                }
            });
            
            $('form').on('submit', function() {
                formChanged = false;
            });
        }
    }

    /**
     * Initialize on DOM ready
     */
    $(document).ready(function() {
        // Check if we're on a Bridge MLS admin page
        if ($('.bridge-mls-admin, .bridge-mls-docs, .bridge-mls-tools').length) {
            window.bridgeMLSAdmin = new BridgeMLSAdminApp();
            
            // Initialize form change monitoring
            if ($('form#bridge_mls_settings').length) {
                window.bridgeMLSAdmin.monitorFormChanges();
            }
        }
    });

})(jQuery);