<?php
/**
 * Settings Page & Fields
 */

if (!defined('ABSPATH')) exit;

class MyCred_Polar_Settings {
    
    public function __construct() {
        add_action('admin_init', array($this, 'settings_init'));
    }
    
    /**
     * Initialize settings
     */
    public function settings_init() {
        register_setting('mycred_polar_settings_group', 'mycred_polar_options', array($this, 'sanitize_options'));
        
        add_settings_section('mycred_polar_main_section', 'Main Configuration', null, 'mycred_polar_settings');
        
        $fields = array(
            'mode' => array('Payment Mode', array($this, 'field_mode_html')),
            'access_token_live' => array('Live Access Token', array($this, 'field_access_token_live_html')),
            'access_token_sandbox' => array('Sandbox Access Token', array($this, 'field_access_token_sandbox_html')),
            'product_id_live' => array('Live One‑Time Product ID (PWYW or fixed)', array($this, 'field_product_id_live_html')),
            'product_id_sandbox' => array('Sandbox One‑Time Product ID (PWYW or fixed)', array($this, 'field_product_id_sandbox_html')),
            'exchange_rate' => array('Exchange Rate ($ per Point)', array($this, 'field_exchange_rate_html')),
            'min_points' => array('Minimum Points', array($this, 'field_min_points_html')),
            'default_points' => array('Default Points', array($this, 'field_default_points_html')),
            'point_type' => array('myCred Point Type', array($this, 'field_point_type_html')),
            'webhook_secret' => array('Webhook Secret (whsec_ / Svix)', array($this, 'field_webhook_secret_html')),
            'webhook_verify_mode' => array('Webhook Verification Mode', array($this, 'field_webhook_verify_mode_html')),
            'subscription_plans' => array('Subscription Plans', array($this, 'field_subscription_plans_html')),
            'log_entry' => array('myCred Log Entry Template', array($this, 'field_log_entry_html')),
        );
        
        foreach ($fields as $key => $field) {
            add_settings_field('mycred_polar_' . $key, $field[0], $field[1], 'mycred_polar_settings', 'mycred_polar_main_section');
        }
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $san = array();
        $san['mode'] = (!empty($input['mode']) && $input['mode'] === 'live') ? 'live' : 'sandbox';
        $san['access_token_live'] = sanitize_text_field($input['access_token_live'] ?? '');
        $san['access_token_sandbox'] = sanitize_text_field($input['access_token_sandbox'] ?? '');
        $san['product_id_live'] = sanitize_text_field($input['product_id_live'] ?? '');
        $san['product_id_sandbox'] = sanitize_text_field($input['product_id_sandbox'] ?? '');
        $san['exchange_rate'] = floatval($input['exchange_rate'] ?? 0.10);
        $san['min_points'] = intval($input['min_points'] ?? 50);
        $san['default_points'] = intval($input['default_points'] ?? 100);
        $san['point_type'] = sanitize_text_field($input['point_type'] ?? 'mycred_default');
        $san['webhook_secret'] = sanitize_text_field($input['webhook_secret'] ?? '');
        $m = strtolower(sanitize_text_field($input['webhook_verify_mode'] ?? 'strict'));
        $san['webhook_verify_mode'] = in_array($m, array('strict','api_fallback','disabled'), true) ? $m : 'strict';
        $san['log_entry'] = sanitize_text_field($input['log_entry'] ?? 'Points purchased via Polar.sh (Order: %order_id%)');
        
        $plans = array();
        if (!empty($input['subscription_plans_json'])) {
            $dec = json_decode(wp_unslash($input['subscription_plans_json']), true);
            if (is_array($dec)) {
                foreach ($dec as $p) {
                    $plans[] = array(
                        'name' => sanitize_text_field($p['name'] ?? ''),
                        'product_id' => sanitize_text_field($p['product_id'] ?? ''),
                        'points_per_cycle' => intval($p['points_per_cycle'] ?? 0),
                        'use_custom_amount' => !empty($p['use_custom_amount']) ? 1 : 0,
                    );
                }
            }
        }
        $san['subscription_plans'] = $plans;
        
        return $san;
    }
    
    // Field rendering methods
    public function field_mode_html() {
        $o = mycred_polar_get_options();
        ?>
        <select name="mycred_polar_options[mode]">
            <option value="sandbox" <?php selected($o['mode'], 'sandbox'); ?>>Sandbox (Test)</option>
            <option value="live" <?php selected($o['mode'], 'live'); ?>>Live (Production)</option>
        </select>
        <?php
    }
    
    public function field_access_token_live_html() {
        $o = mycred_polar_get_options();
        ?>
        <input type="password" name="mycred_polar_options[access_token_live]" value="<?php echo esc_attr($o['access_token_live']); ?>" class="regular-text">
        <p class="description">Live token (polar_at_…) scopes: <code>products:read</code>, <code>checkouts:write</code>, <code>orders:read</code>, <code>subscriptions:read</code>, <strong><code>customer_sessions:write</code></strong>, <strong><code>subscriptions:write</code></strong>.</p>
        <?php
    }
    
    public function field_access_token_sandbox_html() {
        $o = mycred_polar_get_options();
        ?>
        <input type="password" name="mycred_polar_options[access_token_sandbox]" value="<?php echo esc_attr($o['access_token_sandbox']); ?>" class="regular-text">
        <p class="description">Sandbox token (polar_at_…)</p>
        <?php
    }
    
    public function field_product_id_live_html() {
        $o = mycred_polar_get_options();
        ?>
        <input type="text" name="mycred_polar_options[product_id_live]" value="<?php echo esc_attr($o['product_id_live']); ?>" class="regular-text">
        <?php
    }
    
    public function field_product_id_sandbox_html() {
        $o = mycred_polar_get_options();
        ?>
        <input type="text" name="mycred_polar_options[product_id_sandbox]" value="<?php echo esc_attr($o['product_id_sandbox']); ?>" class="regular-text">
        <?php
    }
    
    public function field_exchange_rate_html() {
        $o = mycred_polar_get_options();
        ?>
        <input type="number" step="0.001" min="0.001" name="mycred_polar_options[exchange_rate]" value="<?php echo esc_attr($o['exchange_rate']); ?>" class="small-text"> USD/point
        <?php
    }
    
    public function field_min_points_html() {
        $o = mycred_polar_get_options();
        ?>
        <input type="number" step="1" min="1" name="mycred_polar_options[min_points]" value="<?php echo esc_attr($o['min_points']); ?>" class="small-text">
        <?php
    }
    
    public function field_default_points_html() {
        $o = mycred_polar_get_options();
        ?>
        <input type="number" step="1" min="1" name="mycred_polar_options[default_points]" value="<?php echo esc_attr($o['default_points']); ?>" class="small-text">
        <?php
    }
    
    public function field_point_type_html() {
        $o = mycred_polar_get_options();
        $pt = $o['point_type'];
        $types = array('mycred_default' => 'Default Points');
        
        if (function_exists('mycred_get_types')) {
            $mts = mycred_get_types();
            if (!empty($mts)) {
                $types = array();
                foreach ($mts as $k => $lbl) $types[$k] = $lbl;
            }
        }
        ?>
        <select name="mycred_polar_options[point_type]">
            <?php foreach ($types as $k => $lbl): ?>
                <option value="<?php echo esc_attr($k); ?>" <?php selected($pt, $k); ?>><?php echo esc_html($lbl); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public function field_webhook_secret_html() {
        $o = mycred_polar_get_options();
        $url = esc_html(rest_url('mycred-polar/v1/webhook'));
        ?>
        <input type="text" name="mycred_polar_options[webhook_secret]" value="<?php echo esc_attr($o['webhook_secret']); ?>" class="regular-text">
        <p class="description">Webhook: Event <code>order.paid</code>, Secret here, Endpoint <code><?php echo $url; ?></code>.</p>
        <?php
    }
    
    public function field_webhook_verify_mode_html() {
        $o = mycred_polar_get_options();
        ?>
        <select name="mycred_polar_options[webhook_verify_mode]">
            <option value="strict" <?php selected($o['webhook_verify_mode'], 'strict'); ?>>Strict</option>
            <option value="api_fallback" <?php selected($o['webhook_verify_mode'], 'api_fallback'); ?>>API fallback</option>
            <option value="disabled" <?php selected($o['webhook_verify_mode'], 'disabled'); ?>>Disabled (dev)</option>
        </select>
        <?php
    }
    
    public function field_subscription_plans_html() {
        require MYCRED_POLAR_PATH . 'admin/views/subscription-plans-field.php';
    }
    
    public function field_log_entry_html() {
        $o = mycred_polar_get_options();
        ?>
        <input type="text" name="mycred_polar_options[log_entry]" value="<?php echo esc_attr($o['log_entry']); ?>" class="regular-text">
        <p class="description">Placeholders: %points%, %order_id%, %amount%</p>
        <?php
    }
    
    /**
     * Render settings page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) return;
        require MYCRED_POLAR_PATH . 'admin/views/settings-page.php';
    }
}

// Initialize settings
new MyCred_Polar_Settings();