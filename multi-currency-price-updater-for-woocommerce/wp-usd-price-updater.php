<?php
/*
Plugin Name: Multi-Currency Price Updater for WooCommerce
Plugin URI: https://wordpress.org/plugins/multi-currency-price-updater-for-woocommerce/
Description: Automatically update WooCommerce product prices using real-time exchange rates for USD, EUR, GBP, JPY, CAD, and TRY currencies.
Version: 2.1.1
Author: Emre
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: multi-currency-price-updater-for-woocommerce
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
*/

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants with proper prefix
define('MCPUFW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MCPUFW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MCPUFW_VERSION', '2.1.1');

// Plugin activation
register_activation_hook(__FILE__, 'mcpufw_create_tables');

function mcpufw_create_tables() {
    global $wpdb;
    
    // Price history table
    $price_history_table = $wpdb->prefix . 'mcpufw_price_history';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $price_history_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        old_price decimal(10,2) NOT NULL,
        new_price decimal(10,2) NOT NULL,
        currency_code varchar(3) NOT NULL,
        currency_rate decimal(10,4) NOT NULL,
        date_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY product_id (product_id),
        KEY date_updated (date_updated)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Default options with proper prefix
    add_option('mcpufw_update_interval', 60);
    add_option('mcpufw_min_alert', 0);
    add_option('mcpufw_max_alert', 999);
    add_option('mcpufw_email_alerts', 0);
    add_option('mcpufw_default_currency', 'USD');
}

// Add menus
add_action('admin_menu', 'mcpufw_updater_menu');

function mcpufw_updater_menu(){
    add_menu_page(
        'Multi-Currency Updater', 
        'Currency Updater', 
        'manage_options', 
        'mcpufw-updater', 
        'mcpufw_updater_page', 
        'dashicons-money-alt'
    );
    add_submenu_page(
        'mcpufw-updater', 
        'Settings', 
        'Settings', 
        'manage_options', 
        'mcpufw-settings', 
        'mcpufw_settings_page'
    );
    add_submenu_page(
        'mcpufw-updater', 
        'Price History', 
        'Price History', 
        'manage_options', 
        'mcpufw-history', 
        'mcpufw_history_page'
    );
    add_submenu_page(
        'mcpufw-updater', 
        'Backups', 
        'Backups', 
        'manage_options', 
        'mcpufw-backups', 
        'mcpufw_backups_page'
    );
}

// Dashboard widget
add_action('wp_dashboard_setup', 'mcpufw_dashboard_widget');

function mcpufw_dashboard_widget() {
    wp_add_dashboard_widget(
        'mcpufw_widget',
        'Multi-Currency Price Updater',
        'mcpufw_dashboard_widget_content'
    );
}

function mcpufw_dashboard_widget_content() {
    global $wpdb;
    $active_products_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}postmeta WHERE meta_key = '_mcpufw_enabled' AND meta_value = '1'"
    );
    $last_update = get_option('mcpufw_last_update', 'Not updated yet');
    $current_rates = get_option('mcpufw_current_rates', array());
    
    echo '<div style="text-align: center; padding: 10px;">';
    echo '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 8px; margin-bottom: 15px;">';
    echo "<h3 style='margin: 0;'>Current Exchange Rates</h3>";
    
    if (!empty($current_rates)) {
        echo '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; font-size: 12px;">';
        foreach ($current_rates as $code => $rate) {
            echo '<div><strong>' . esc_html($code) . ':</strong> ' . esc_html($rate) . '</div>';
        }
        echo '</div>';
    }
    
    echo '</div>';
    echo "<p><strong>Active products:</strong> " . esc_html($active_products_count) . "</p>";
    echo "<p><strong>Last update:</strong> " . esc_html($last_update) . "</p>";
    echo '<a href="admin.php?page=mcpufw-updater" class="button button-primary">Management Panel</a>';
    echo '</div>';
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'mcpufw_admin_enqueue_scripts');

function mcpufw_admin_enqueue_scripts($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'mcpufw') === false) {
        return;
    }
    
    // Enqueue CSS
    wp_enqueue_style(
        'mcpufw-admin-style',
        MCPUFW_PLUGIN_URL . 'assets/admin-style.css',
        array(),
        MCPUFW_VERSION
    );
    
    // Enqueue JS
    wp_enqueue_script(
        'mcpufw-admin-script',
        MCPUFW_PLUGIN_URL . 'assets/admin-script.js',
        array('jquery'),
        MCPUFW_VERSION,
        true
    );
    
    // Localize script for AJAX
    wp_localize_script('mcpufw-admin-script', 'mcpufw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mcpufw_nonce')
    ));
}

// Main page
function mcpufw_updater_page() {
    $default_interval = get_option('mcpufw_update_interval', 60);
    $default_currency = get_option('mcpufw_default_currency', 'USD');
    ?>
    
    <div class="wrap">
        <h1>Multi-Currency Price Updater for WooCommerce</h1>
        
        <div class="mcpufw-container">
            <div id="mcpufw-app" class="mcpufw-rate-display">
                <h1>Current Exchange Rates: Loading...</h1>
                <div class="mcpufw-loading-spinner" id="mcpufwLoadingSpinner" style="display: none;">
                    <div class="mcpufw-spinner"></div>
                </div>
                <div class="mcpufw-currency-grid" id="mcpufwCurrencyGrid"></div>
                <div class="mcpufw-last-updated" id="mcpufwLastUpdated"></div>
            </div>

            <div class="mcpufw-controls-section">
                <label for="mcpufwUpdateInterval">Update interval (seconds):</label>
                <input type="number" id="mcpufwUpdateInterval" value="<?php echo esc_attr($default_interval); ?>" min="1">
                <button id="mcpufwSaveInterval" class="button button-secondary">Save Interval</button>
                
                <label for="mcpufwDefaultCurrency">Default Currency:</label>
                <select id="mcpufwDefaultCurrency">
                    <option value="USD" <?php selected($default_currency, 'USD'); ?>>USD - US Dollar</option>
                    <option value="EUR" <?php selected($default_currency, 'EUR'); ?>>EUR - Euro</option>
                    <option value="GBP" <?php selected($default_currency, 'GBP'); ?>>GBP - British Pound</option>
                    <option value="JPY" <?php selected($default_currency, 'JPY'); ?>>JPY - Japanese Yen</option>
                    <option value="CAD" <?php selected($default_currency, 'CAD'); ?>>CAD - Canadian Dollar</option>
                    <option value="TRY" <?php selected($default_currency, 'TRY'); ?>>TRY - Turkish Lira</option>
                </select>
                
                <label for="mcpufwCategoryFilter">Category Filter:</label>
                <select id="mcpufwCategoryFilter">
                    <option value="">All Categories</option>
                    <?php
                    $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
                    if (!is_wp_error($categories)) {
                        foreach ($categories as $category) {
                            echo "<option value='" . esc_attr($category->term_id) . "'>" . esc_html($category->name) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="mcpufw-button-group">
                <button id="mcpufwSelectAll" class="button">Select All</button>
                <button id="mcpufwDeselectAll" class="button">Deselect All</button>
                <button id="mcpufwBulkUpdate" class="button button-primary">Update Selected</button>
                <button id="mcpufwBackupPrices" class="button button-secondary">Backup Prices</button>
            </div>

            <div class="mcpufw-stats-section" id="mcpufwStatsSection">
                <div class="mcpufw-stat-card">
                    <span class="mcpufw-stat-number" id="mcpufwTotalProducts">0</span>
                    <span class="mcpufw-stat-label">Total Products</span>
                </div>
                <div class="mcpufw-stat-card">
                    <span class="mcpufw-stat-number" id="mcpufwActiveProducts">0</span>
                    <span class="mcpufw-stat-label">Active Products</span>
                </div>
                <div class="mcpufw-stat-card">
                    <span class="mcpufw-stat-number" id="mcpufwLastUpdateTime">-</span>
                    <span class="mcpufw-stat-label">Last Update</span>
                </div>
            </div>

            <table id="mcpufwProductTable" class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="mcpufwSelectAllCheckbox"></th>
                        <th>Product Name / Variation</th>
                        <th>Category</th>
                        <th>Base Price</th>
                        <th>Regular Price</th>
                        <th>Currency</th>
                        <th>Active</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Products will be loaded dynamically -->
                </tbody>
            </table>
        </div>
    </div>

    <div id="mcpufwNotificationContainer"></div>

    <?php
}

// Settings page
function mcpufw_settings_page() {
    // Nonce check
    if (isset($_POST['mcpufw_save_settings'])) {
        if (!isset($_POST['mcpufw_settings_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mcpufw_settings_nonce'])), 'mcpufw_save_settings')) {
            wp_die('Security check failed!');
        }
        
        update_option('mcpufw_min_alert', isset($_POST['min_alert']) ? floatval(wp_unslash($_POST['min_alert'])) : 0);
        update_option('mcpufw_max_alert', isset($_POST['max_alert']) ? floatval(wp_unslash($_POST['max_alert'])) : 999);
        update_option('mcpufw_email_alerts', isset($_POST['email_alerts']) ? 1 : 0);
        update_option('mcpufw_default_currency', isset($_POST['default_currency']) ? sanitize_text_field(wp_unslash($_POST['default_currency'])) : 'USD');
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }

    $min_alert = get_option('mcpufw_min_alert', 0);
    $max_alert = get_option('mcpufw_max_alert', 999);
    $email_alerts = get_option('mcpufw_email_alerts', 0);
    $default_currency = get_option('mcpufw_default_currency', 'USD');
    ?>
    
    <div class="wrap">
        <h1>Multi-Currency Updater Settings</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('mcpufw_save_settings', 'mcpufw_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Default Currency</th>
                    <td>
                        <select name="default_currency">
                            <option value="USD" <?php selected($default_currency, 'USD'); ?>>USD - US Dollar</option>
                            <option value="EUR" <?php selected($default_currency, 'EUR'); ?>>EUR - Euro</option>
                            <option value="GBP" <?php selected($default_currency, 'GBP'); ?>>GBP - British Pound</option>
                            <option value="JPY" <?php selected($default_currency, 'JPY'); ?>>JPY - Japanese Yen</option>
                            <option value="CAD" <?php selected($default_currency, 'CAD'); ?>>CAD - Canadian Dollar</option>
                            <option value="TRY" <?php selected($default_currency, 'TRY'); ?>>TRY - Turkish Lira</option>
                        </select>
                        <p class="description">Default currency for new products.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Minimum Rate Alert</th>
                    <td>
                        <input type="number" step="0.0001" name="min_alert" value="<?php echo esc_attr($min_alert); ?>" />
                        <p class="description">Alert when any rate drops below this value.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Maximum Rate Alert</th>
                    <td>
                        <input type="number" step="0.0001" name="max_alert" value="<?php echo esc_attr($max_alert); ?>" />
                        <p class="description">Alert when any rate exceeds this value.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Email Alerts</th>
                    <td>
                        <input type="checkbox" name="email_alerts" value="1" <?php checked($email_alerts, 1); ?> />
                        <label>Send email when rates reach alert values</label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Settings', 'primary', 'mcpufw_save_settings'); ?>
        </form>
    </div>
    <?php
}

// Price history page
function mcpufw_history_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mcpufw_price_history';
    
    $history = $wpdb->get_results($wpdb->prepare("
        SELECT h.*, p.post_title as product_name 
        FROM {$wpdb->prefix}mcpufw_price_history h 
        LEFT JOIN {$wpdb->prefix}posts p ON h.product_id = p.ID 
        ORDER BY h.date_updated DESC 
        LIMIT %d
    ", 100));
    ?>
    
    <div class="wrap">
        <h1>Price Change History</h1>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Old Price</th>
                    <th>New Price</th>
                    <th>Currency</th>
                    <th>Rate</th>
                    <th>Change</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="7">No price change records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $record): ?>
                        <tr>
                            <td><?php echo esc_html($record->product_name); ?></td>
                            <td><?php echo esc_html(number_format_i18n($record->old_price, 2)); ?> ₺</td>
                            <td><?php echo esc_html(number_format_i18n($record->new_price, 2)); ?> ₺</td>
                            <td><strong><?php echo esc_html($record->currency_code); ?></strong></td>
                            <td><?php echo esc_html(number_format_i18n($record->currency_rate, 4)); ?></td>
                            <td>
                                <?php 
                                $change = $record->new_price - $record->old_price;
                                $color = $change > 0 ? 'green' : ($change < 0 ? 'red' : 'gray');
                                $icon = $change > 0 ? '↗' : ($change < 0 ? '↘' : '→');
                                echo '<span style="color: ' . esc_attr($color) . ';">' . esc_html($icon) . ' ' . esc_html(number_format_i18n(abs($change), 2)) . ' ₺</span>';
                                ?>
                            </td>
                            <td><?php echo esc_html(gmdate('d.m.Y H:i', strtotime($record->date_updated))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Backups page
function mcpufw_backups_page() {
    global $wpdb;
    
    // Backup restoration
    if (isset($_POST['mcpufw_restore_backup']) && isset($_POST['backup_key'])) {
        if (!isset($_POST['mcpufw_backup_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mcpufw_backup_nonce'])), 'mcpufw_backup_action')) {
            wp_die('Security check failed!');
        }
        
        $backup_key = sanitize_text_field(wp_unslash($_POST['backup_key']));
        $backup_data = get_option($backup_key);
        
        if ($backup_data && is_array($backup_data)) {
            foreach ($backup_data as $product_data) {
                if (function_exists('wc_get_product')) {
                    $product = wc_get_product($product_data['id']);
                    if ($product) {
                        $product->set_regular_price($product_data['regular_price']);
                        $product->save();
                        
                        update_post_meta($product_data['id'], '_mcpufw_base_price', $product_data['base_price']);
                        update_post_meta($product_data['id'], '_mcpufw_enabled', $product_data['currency_enabled']);
                        update_post_meta($product_data['id'], '_mcpufw_currency_code', $product_data['currency_code']);
                    }
                }
            }
            echo '<div class="notice notice-success"><p>Backup restored successfully!</p></div>';
        }
    }
    
    // Backup deletion
    if (isset($_POST['mcpufw_delete_backup']) && isset($_POST['backup_key'])) {
        if (!isset($_POST['mcpufw_backup_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mcpufw_backup_nonce'])), 'mcpufw_backup_action')) {
            wp_die('Security check failed!');
        }
        
        $backup_key = sanitize_text_field(wp_unslash($_POST['backup_key']));
        delete_option($backup_key);
        echo '<div class="notice notice-success"><p>Backup deleted!</p></div>';
    }
    
    // List backups
    $backups = array();
    $options = $wpdb->get_results("SELECT option_name FROM {$wpdb->prefix}options WHERE option_name LIKE 'mcpufw_backup_%' ORDER BY option_name DESC");
    
    foreach ($options as $option) {
        $backup_data = get_option($option->option_name);
        if (is_array($backup_data) && !empty($backup_data)) {
            $date_parts = explode('_', str_replace('mcpufw_backup_', '', $option->option_name));
            if (count($date_parts) >= 4) {
                $backups[] = array(
                    'key' => $option->option_name,
                    'date' => $date_parts[0] . '-' . $date_parts[1] . '-' . $date_parts[2] . ' ' . $date_parts[3] . ':' . (isset($date_parts[4]) ? $date_parts[4] : '00'),
                    'count' => count($backup_data)
                );
            }
        }
    }
    ?>
    
    <div class="wrap">
        <h1>Price Backups</h1>
        
        <?php if (empty($backups)): ?>
            <p>No backups found.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Backup Date</th>
                        <th>Product Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><?php echo esc_html($backup['date']); ?></td>
                            <td><?php echo esc_html($backup['count']); ?> products</td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('mcpufw_backup_action', 'mcpufw_backup_nonce'); ?>
                                    <input type="hidden" name="backup_key" value="<?php echo esc_attr($backup['key']); ?>">
                                    <button type="submit" name="mcpufw_restore_backup" class="button button-primary" 
                                            onclick="return confirm('Are you sure you want to restore this backup?')">
                                        Restore
                                    </button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('mcpufw_backup_action', 'mcpufw_backup_nonce'); ?>
                                    <input type="hidden" name="backup_key" value="<?php echo esc_attr($backup['key']); ?>">
                                    <button type="submit" name="mcpufw_delete_backup" class="button button-secondary" 
                                            onclick="return confirm('Are you sure you want to delete this backup?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <h3>Create Manual Backup</h3>
            <p>Click the button below to create a backup of current prices.</p>
            <button id="mcpufwCreateManualBackup" class="button button-primary">Create Backup</button>
        </div>
    </div>
    <?php
}

// AJAX Handlers with proper prefixes

// Get products
add_action('wp_ajax_mcpufw_get_products', 'mcpufw_get_products');
function mcpufw_get_products() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access!');
    }
    
    if (!function_exists('wc_get_products')) {
        wp_send_json_error('WooCommerce not found!');
        return;
    }
    
    $products = wc_get_products(array('limit' => -1));
    $result = array();
    $default_currency = get_option('mcpufw_default_currency', 'USD');

    foreach ($products as $product) {
        // Main product info
        $currency_enabled = get_post_meta($product->get_id(), '_mcpufw_enabled', true) ? true : false;
        $currency_code = get_post_meta($product->get_id(), '_mcpufw_currency_code', true);
        if (empty($currency_code)) {
            $currency_code = $default_currency;
        }
        $base_price = get_post_meta($product->get_id(), '_mcpufw_base_price', true);
        
        // Get categories
        $categories = get_the_terms($product->get_id(), 'product_cat');
        $category_names = array();
        $category_ids = array();
        if ($categories && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $category_names[] = $category->name;
                $category_ids[] = $category->term_id;
            }
        }
        
        // Check for variations
        if ($product->is_type('variable')) {
            // Variable product - get variations
            $variations = $product->get_children();
            
            if (!empty($variations)) {
                // Add main variable product
                $result[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name() . ' (Main Product - ' . count($variations) . ' variations)',
                    'categories' => implode(', ', $category_names),
                    'category_ids' => implode(',', $category_ids),
                    'base_price' => $base_price ? $base_price : $product->get_regular_price(),
                    'regular_price' => $product->get_regular_price(),
                    'currency_enabled' => $currency_enabled,
                    'currency_code' => $currency_code,
                    'is_variation' => false,
                    'variations_count' => count($variations)
                );
                
                // Add each variation as separate row
                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        $attributes = $variation->get_variation_attributes();
                        $variation_name = implode(', ', array_filter($attributes));
                        if (empty($variation_name)) {
                            $variation_name = 'Variation #' . $variation_id;
                        }
                        
                        $var_currency_enabled = get_post_meta($variation_id, '_mcpufw_enabled', true) ? true : false;
                        $var_currency_code = get_post_meta($variation_id, '_mcpufw_currency_code', true);
                        if (empty($var_currency_code)) {
                            $var_currency_code = $currency_code;
                        }
                        $var_base_price = get_post_meta($variation_id, '_mcpufw_base_price', true);
                        
                        $result[] = array(
                            'id' => $variation_id,
                            'name' => $product->get_name() . ' - ' . $variation_name,
                            'categories' => implode(', ', $category_names),
                            'category_ids' => implode(',', $category_ids),
                            'base_price' => $var_base_price ? $var_base_price : $variation->get_regular_price(),
                            'regular_price' => $variation->get_regular_price(),
                            'currency_enabled' => $var_currency_enabled,
                            'currency_code' => $var_currency_code,
                            'is_variation' => true,
                            'parent_id' => $product->get_id()
                        );
                    }
                }
            } else {
                // Variable product without variations
                $result[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name() . ' (Variable - No variations)',
                    'categories' => implode(', ', $category_names),
                    'category_ids' => implode(',', $category_ids),
                    'base_price' => $base_price ? $base_price : $product->get_regular_price(),
                    'regular_price' => $product->get_regular_price(),
                    'currency_enabled' => $currency_enabled,
                    'currency_code' => $currency_code,
                    'is_variation' => false
                );
            }
        } else {
            // Simple product
            $result[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'categories' => implode(', ', $category_names),
                'category_ids' => implode(',', $category_ids),
                'base_price' => $base_price ? $base_price : $product->get_regular_price(),
                'regular_price' => $product->get_regular_price(),
                'currency_enabled' => $currency_enabled,
                'currency_code' => $currency_code,
                'is_variation' => false
            );
        }
    }

    wp_send_json($result);
}

// Save product price
add_action('wp_ajax_mcpufw_save_product_price', 'mcpufw_save_product_price');
function mcpufw_save_product_price() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access!');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['id'])) {
        wp_send_json_error('Invalid data!');
        return;
    }
    
    if (!function_exists('wc_get_product')) {
        wp_send_json_error('WooCommerce not found!');
        return;
    }
    
    global $wpdb;
    $product = wc_get_product($data['id']);
    
    if (!$product) {
        wp_send_json_error('Product not found!');
        return;
    }
    
    $old_price = $product->get_regular_price();
    
    // Save base price
    update_post_meta($data['id'], '_mcpufw_base_price', $data['base_price']);
    
    // Update regular price
    $product->set_regular_price($data['regular_price']);
    
    // Save currency info
    update_post_meta($data['id'], '_mcpufw_enabled', $data['currency_enabled']);
    update_post_meta($data['id'], '_mcpufw_currency_code', $data['currency_code']);
    
    $product->save();
    
    // Save to price history (only if price changed)
    if ($old_price != $data['regular_price']) {
        $table_name = $wpdb->prefix . 'mcpufw_price_history';
        $wpdb->insert(
            $table_name,
            array(
                'product_id' => $data['id'],
                'old_price' => $old_price,
                'new_price' => $data['regular_price'],
                'currency_code' => $data['currency_code'],
                'currency_rate' => isset($data['currency_rate']) ? $data['currency_rate'] : 1,
                'date_updated' => current_time('mysql')
            ),
            array('%d', '%f', '%f', '%s', '%f', '%s')
        );
    }

    wp_send_json_success();
}

// Backup prices
add_action('wp_ajax_mcpufw_backup_prices', 'mcpufw_backup_prices');
function mcpufw_backup_prices() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access!');
    }
    
    if (!function_exists('wc_get_products')) {
        wp_send_json_error('WooCommerce not found!');
        return;
    }
    
    $products = wc_get_products(array('limit' => -1));
    $backup_data = array();
    
    foreach ($products as $product) {
        // Main product
        $backup_data[] = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'regular_price' => $product->get_regular_price(),
            'base_price' => get_post_meta($product->get_id(), '_mcpufw_base_price', true),
            'currency_enabled' => get_post_meta($product->get_id(), '_mcpufw_enabled', true),
            'currency_code' => get_post_meta($product->get_id(), '_mcpufw_currency_code', true),
            'date' => current_time('mysql'),
            'type' => $product->get_type()
        );
        
        // Variable product variations
        if ($product->is_type('variable')) {
            $variations = $product->get_children();
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $backup_data[] = array(
                        'id' => $variation_id,
                        'name' => $product->get_name() . ' - Variation #' . $variation_id,
                        'regular_price' => $variation->get_regular_price(),
                        'base_price' => get_post_meta($variation_id, '_mcpufw_base_price', true),
                        'currency_enabled' => get_post_meta($variation_id, '_mcpufw_enabled', true),
                        'currency_code' => get_post_meta($variation_id, '_mcpufw_currency_code', true),
                        'date' => current_time('mysql'),
                        'type' => 'variation',
                        'parent_id' => $product->get_id()
                    );
                }
            }
        }
    }
    
    $backup_key = 'mcpufw_backup_' . gmdate('Y_m_d_H_i_s');
    update_option($backup_key, $backup_data);
    
    // Save last update time
    update_option('mcpufw_last_update', current_time('mysql'));
    
    wp_send_json_success(array('backup_key' => $backup_key));
}

// Save current rates
add_action('wp_ajax_mcpufw_save_current_rates', 'mcpufw_save_current_rates');
function mcpufw_save_current_rates() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access!');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data && isset($data['rates'])) {
        update_option('mcpufw_current_rates', $data['rates']);
        update_option('mcpufw_last_update', current_time('mysql'));
    }
    
    wp_send_json_success();
}

// Save default currency
add_action('wp_ajax_mcpufw_save_default_currency', 'mcpufw_save_default_currency');
function mcpufw_save_default_currency() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access!');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data && isset($data['currency'])) {
        update_option('mcpufw_default_currency', $data['currency']);
    }
    
    wp_send_json_success();
}

// Save update interval
add_action('wp_ajax_mcpufw_save_update_interval', 'mcpufw_save_update_interval');
function mcpufw_save_update_interval() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access!');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data && isset($data['interval'])) {
        update_option('mcpufw_update_interval', intval($data['interval']));
    }
    
    wp_send_json_success();
}

// Currency alerts check
add_action('wp_ajax_mcpufw_check_alerts', 'mcpufw_check_alerts');
function mcpufw_check_alerts() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access!');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['rates'])) {
        wp_send_json_error('Invalid data!');
        return;
    }
    
    $rates = $data['rates'];
    $min_rate = get_option('mcpufw_min_alert', 0);
    $max_rate = get_option('mcpufw_max_alert', 999);
    $email_alerts = get_option('mcpufw_email_alerts', 0);
    
    $alerts = array();
    
    foreach ($rates as $currency => $rate) {
        if ($rate < $min_rate || $rate > $max_rate) {
            $alerts[] = "$currency: $rate";
        }
    }
    
    if (!empty($alerts) && $email_alerts) {
        $admin_email = get_option('admin_email');
        $subject = 'Multi-Currency Rate Alert - ' . get_bloginfo('name');
        $message = "Hello,\n\n";
        $message .= "The following currencies have reached alert values:\n\n";
        $message .= implode("\n", $alerts) . "\n\n";
        $message .= "Current date: " . current_time('mysql') . "\n";
        $message .= "Management panel: " . admin_url('admin.php?page=mcpufw-updater') . "\n\n";
        $message .= "This is an automated message.";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    wp_send_json_success(array('alerts_sent' => !empty($alerts)));
}

// Cleanup functions
register_deactivation_hook(__FILE__, 'mcpufw_deactivation');
function mcpufw_deactivation() {
    wp_clear_scheduled_hook('mcpufw_auto_update');
}

register_uninstall_hook(__FILE__, 'mcpufw_uninstall');
function mcpufw_uninstall() {
    global $wpdb;
    
    // Delete options
    delete_option('mcpufw_update_interval');
    delete_option('mcpufw_min_alert');
    delete_option('mcpufw_max_alert');
    delete_option('mcpufw_email_alerts');
    delete_option('mcpufw_current_rates');
    delete_option('mcpufw_last_update');
    delete_option('mcpufw_default_currency');
    
    // Delete backups
    $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE 'mcpufw_backup_%'");
    
    // Drop price history table
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mcpufw_price_history");
    
    // Delete post meta
    $wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key IN ('_mcpufw_enabled', '_mcpufw_currency_code', '_mcpufw_base_price')");
}

?>