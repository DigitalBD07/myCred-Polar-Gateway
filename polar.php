<?php
/**
 * Plugin Name: myCred Polar.sh Points Purchase nov4
 * Plugin URI: https://your-website.com
 * Description: Purchase myCred points via Polar.sh. Includes webhook verification (Svix / Standard Webhooks), server-side fallback credit on success, and transaction logging.
 * Version: 2.4.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 * Text Domain: mycred-polar
 * Requires Plugins: mycred
 */

if (!defined('ABSPATH')) exit;

/* -----------------------------------------------------------
   myCred dependency check
----------------------------------------------------------- */
function mycred_polar_check_mycred() {
    if (!function_exists('mycred')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p><strong>myCred Polar.sh Points Purchase</strong> requires the myCred plugin to be installed and activated.</p></div>';
        });
        return false;
    }
    return true;
}
add_action('plugins_loaded', 'mycred_polar_check_mycred');

/* -----------------------------------------------------------
   Admin menu
----------------------------------------------------------- */
function mycred_polar_add_admin_menu() {
    add_menu_page('myCred Polar.sh Settings', 'myCred Polar.sh', 'manage_options', 'mycred_polar_settings', 'mycred_polar_settings_page_html', 'dashicons-money-alt', 80);
    add_submenu_page('mycred_polar_settings', 'Transaction Logs', 'Transaction Logs', 'manage_options', 'mycred_polar_logs', 'mycred_polar_logs_page_html');
}
add_action('admin_menu', 'mycred_polar_add_admin_menu');

/* -----------------------------------------------------------
   Settings
----------------------------------------------------------- */
function mycred_polar_settings_init() {
    register_setting('mycred_polar_settings_group', 'mycred_polar_options', 'mycred_polar_sanitize_options');

    add_settings_section('mycred_polar_main_section', 'Main Configuration', null, 'mycred_polar_settings');

    $fields = array(
        'mode' => array('Payment Mode', 'mycred_polar_field_mode_html'),
        'access_token_live' => array('Live Access Token', 'mycred_polar_field_access_token_live_html'),
        'access_token_sandbox' => array('Sandbox Access Token', 'mycred_polar_field_access_token_sandbox_html'),
        'product_id_live' => array('Live Product ID', 'mycred_polar_field_product_id_live_html'),
        'product_id_sandbox' => array('Sandbox Product ID', 'mycred_polar_field_product_id_sandbox_html'),
        'exchange_rate' => array('Exchange Rate ($ per Point)', 'mycred_polar_field_exchange_rate_html'),
        'min_points' => array('Minimum Points', 'mycred_polar_field_min_points_html'),
        'default_points' => array('Default Points', 'mycred_polar_field_default_points_html'),
        'point_type' => array('myCred Point Type', 'mycred_polar_field_point_type_html'),
        'webhook_secret' => array('Polar.sh Webhook Secret', 'mycred_polar_field_webhook_secret_html'),
        'log_entry' => array('Log Entry Template', 'mycred_polar_field_log_entry_html'),
    );
    foreach ($fields as $key => $field) {
        add_settings_field('mycred_polar_' . $key, $field[0], $field[1], 'mycred_polar_settings', 'mycred_polar_main_section');
    }
}
add_action('admin_init', 'mycred_polar_settings_init');

function mycred_polar_get_options() {
    $defaults = array(
        'mode' => 'sandbox',
        'access_token_live' => '',
        'access_token_sandbox' => '',
        'product_id_live' => '',
        'product_id_sandbox' => '',
        'exchange_rate' => 0.10,
        'min_points' => 50,
        'default_points' => 100,
        'point_type' => 'mycred_default',
        'webhook_secret' => '',
        'log_entry' => 'Points purchased via Polar.sh (Order: %order_id%)',
    );
    $stored = get_option('mycred_polar_options', array());
    if (!is_array($stored)) $stored = array();
    return wp_parse_args($stored, $defaults);
}

/* Settings fields */
function mycred_polar_field_mode_html() { $o = mycred_polar_get_options(); $mode = $o['mode'] ?? 'sandbox'; ?>
    <select id="mycred_polar_mode" name="mycred_polar_options[mode]">
        <option value="sandbox" <?php selected($mode, 'sandbox'); ?>>Sandbox (Test Mode)</option>
        <option value="live" <?php selected($mode, 'live'); ?>>Live (Production Mode)</option>
    </select>
    <p class="description">Use Sandbox to test payments without charging real cards.</p>
<?php }
function mycred_polar_field_access_token_live_html() { $o = mycred_polar_get_options(); ?>
    <input type="password" name="mycred_polar_options[access_token_live]" value="<?php echo esc_attr($o['access_token_live'] ?? ''); ?>" class="regular-text">
    <p class="description">Production token (starts with polar_at_)</p>
<?php }
function mycred_polar_field_access_token_sandbox_html() { $o = mycred_polar_get_options(); ?>
    <input type="password" name="mycred_polar_options[access_token_sandbox]" value="<?php echo esc_attr($o['access_token_sandbox'] ?? ''); ?>" class="regular-text">
    <p class="description">Sandbox token (starts with polar_at_)</p>
<?php }
function mycred_polar_field_product_id_live_html() { $o = mycred_polar_get_options(); ?>
    <input type="text" name="mycred_polar_options[product_id_live]" value="<?php echo esc_attr($o['product_id_live'] ?? ''); ?>" class="regular-text">
    <p class="description">PWYW Product ID (format: prod_xxxxx or UUID).</p>
<?php }
function mycred_polar_field_product_id_sandbox_html() { $o = mycred_polar_get_options(); ?>
    <input type="text" name="mycred_polar_options[product_id_sandbox]" value="<?php echo esc_attr($o['product_id_sandbox'] ?? ''); ?>" class="regular-text">
    <p class="description">Sandbox PWYW Product ID.</p>
<?php }
function mycred_polar_field_exchange_rate_html() { $o = mycred_polar_get_options(); ?>
    <input type="number" step="0.001" min="0.001" name="mycred_polar_options[exchange_rate]" value="<?php echo esc_attr($o['exchange_rate'] ?? 0.10); ?>" class="small-text">
    <p class="description">Price per point in USD (e.g. 0.10 = $0.10).</p>
<?php }
function mycred_polar_field_min_points_html() { $o = mycred_polar_get_options(); ?>
    <input type="number" step="1" min="1" name="mycred_polar_options[min_points]" value="<?php echo esc_attr($o['min_points'] ?? 50); ?>" class="small-text">
<?php }
function mycred_polar_field_default_points_html() { $o = mycred_polar_get_options(); ?>
    <input type="number" step="1" min="1" name="mycred_polar_options[default_points]" value="<?php echo esc_attr($o['default_points'] ?? 100); ?>" class="small-text">
<?php }
function mycred_polar_field_point_type_html() {
    $o = mycred_polar_get_options();
    $point_type = $o['point_type'] ?? 'mycred_default';
    $types = array('mycred_default' => 'Default Points');
    if (function_exists('mycred_get_types')) {
        $tmp = mycred_get_types();
        if (!empty($tmp)) { $types = array(); foreach ($tmp as $k => $lbl) $types[$k] = $lbl; }
    } ?>
    <select name="mycred_polar_options[point_type]">
        <?php foreach ($types as $k => $lbl): ?>
            <option value="<?php echo esc_attr($k); ?>" <?php selected($point_type, $k); ?>><?php echo esc_html($lbl); ?></option>
        <?php endforeach; ?>
    </select>
    <p class="description">Which myCred point type to award.</p>
<?php }
function mycred_polar_field_webhook_secret_html() { $o = mycred_polar_get_options(); ?>
    <input type="text" name="mycred_polar_options[webhook_secret]" value="<?php echo esc_attr($o['webhook_secret'] ?? ''); ?>" class="regular-text">
    <p class="description">Webhook secret from Polar (starts with whsec_). In Polar → Webhooks use Format: Raw and enable event <code>order.paid</code> (recommended). </p>
<?php }
function mycred_polar_field_log_entry_html() { $o = mycred_polar_get_options(); ?>
    <input type="text" name="mycred_polar_options[log_entry]" value="<?php echo esc_attr($o['log_entry'] ?? 'Points purchased via Polar.sh (Order: %order_id%)'); ?>" class="regular-text">
    <p class="description">Vars: %points%, %order_id%, %amount%</p>
<?php }

function mycred_polar_sanitize_options($input) {
    return array(
        'mode' => (!empty($input['mode']) && $input['mode'] === 'live') ? 'live' : 'sandbox',
        'access_token_live' => sanitize_text_field($input['access_token_live'] ?? ''),
        'access_token_sandbox' => sanitize_text_field($input['access_token_sandbox'] ?? ''),
        'product_id_live' => sanitize_text_field($input['product_id_live'] ?? ''),
        'product_id_sandbox' => sanitize_text_field($input['product_id_sandbox'] ?? ''),
        'exchange_rate' => floatval($input['exchange_rate'] ?? 0.10),
        'min_points' => intval($input['min_points'] ?? 50),
        'default_points' => intval($input['default_points'] ?? 100),
        'point_type' => sanitize_text_field($input['point_type'] ?? 'mycred_default'),
        'webhook_secret' => sanitize_text_field($input['webhook_secret'] ?? ''),
        'log_entry' => sanitize_text_field($input['log_entry'] ?? 'Points purchased via Polar.sh (Order: %order_id%)'),
    );
}

/* -----------------------------------------------------------
   Settings page
----------------------------------------------------------- */
function mycred_polar_settings_page_html() {
    if (!current_user_can('manage_options')) return;
    $webhook_url = esc_html(rest_url('mycred-polar/v1/webhook'));
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="notice notice-info" style="padding: 15px;">
            <h3>📋 Setup</h3>
            <ol>
                <li>Create a Polar PWYW product (copy Product ID)</li>
                <li>Create an Access Token with scopes: <code>products:read</code>, <code>checkouts:write</code>, <code>orders:read</code></li>
                <li>Webhook (Format: Raw):
                    <ul>
                        <li>URL: <code><?php echo $webhook_url; ?></code></li>
                        <li>Events: <code>order.paid</code> (recommended), optional <code>checkout.updated</code></li>
                        <li>Secret: <code>whsec_…</code> (paste below)</li>
                    </ul>
                </li>
                <li>Add shortcode <code>[mycred_polar_form]</code> to a page.</li>
            </ol>
        </div>

        <form action="options.php" method="post">
            <?php settings_fields('mycred_polar_settings_group'); ?>
            <?php do_settings_sections('mycred_polar_settings'); ?>
            <?php submit_button('Save Settings'); ?>
        </form>

        <hr>
        <h2>🧪 Test Connection</h2>
        <button type="button" id="test-polar-connection" class="button button-primary">Test Connection</button>
        <div id="test-result" style="margin-top: 10px;"></div>

        <script>
        document.getElementById('test-polar-connection').addEventListener('click', function() {
            const btn = this, result = document.getElementById('test-result');
            btn.disabled = true; btn.textContent = 'Testing...'; result.innerHTML = '';
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mycred_polar_test_connection&nonce=<?php echo wp_create_nonce('mycred_polar_test'); ?>'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.innerHTML = '<div class="notice notice-success inline" style="padding: 10px; margin: 0;"><p style="margin:0;">✅ ' + data.data.message + '</p></div>';
                } else {
                    result.innerHTML = '<div class="notice notice-error inline" style="padding: 10px; margin: 0;"><p style="margin:0;">❌ ' + (data.data?.message || 'Failed') + '</p></div>';
                }
            })
            .catch(err => {
                result.innerHTML = '<div class="notice notice-error inline" style="padding: 10px; margin: 0;"><p style="margin:0;">❌ Error: ' + err.message + '</p></div>';
            })
            .finally(() => { btn.disabled = false; btn.textContent = 'Test Connection'; });
        });
        </script>
    </div>
    <?php
}

/* -----------------------------------------------------------
   Logs page
----------------------------------------------------------- */
function mycred_polar_logs_page_html() {
    global $wpdb;
    $table = $wpdb->prefix . 'mycred_polar_logs';
    $logs = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");
    ?>
    <div class="wrap">
        <h1>Transaction Logs</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Date</th><th>User</th><th>Points</th><th>Amount</th><th>Order/Checkout ID</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6">No transactions yet.</td></tr>
            <?php else: foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo esc_html($log->created_at); ?></td>
                    <td><?php echo esc_html(get_userdata($log->user_id)->user_login ?? 'Unknown'); ?></td>
                    <td><?php echo esc_html($log->points); ?></td>
                    <td>$<?php echo esc_html(number_format($log->amount / 100, 2)); ?></td>
                    <td><?php echo esc_html($log->order_id); ?></td>
                    <td><?php echo esc_html($log->status); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/* -----------------------------------------------------------
   Activation (logs table + rewrites)
----------------------------------------------------------- */
function mycred_polar_activate() {
    global $wpdb;
    $table = $wpdb->prefix . 'mycred_polar_logs';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        order_id varchar(255) NOT NULL,
        points int(11) NOT NULL,
        amount int(11) NOT NULL,
        status varchar(50) NOT NULL,
        webhook_data longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY order_id (order_id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    mycred_polar_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'mycred_polar_activate');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

/* -----------------------------------------------------------
   Shortcode - purchase form
----------------------------------------------------------- */
function mycred_polar_render_form_shortcode() {
    if (!mycred_polar_check_mycred()) return '<p style="color:red;">myCred plugin is required.</p>';
    if (!is_user_logged_in()) return '<p>You must be logged in to purchase points. <a href="' . esc_url(wp_login_url(get_permalink())) . '">Login here</a>.</p>';

    $o = mycred_polar_get_options();
    $exchange_rate = floatval($o['exchange_rate']);
    $min_points = intval($o['min_points']);
    $default_points = intval($o['default_points']);
    $user = wp_get_current_user();
    $mycred = mycred($o['point_type']);
    $current_balance = $mycred->get_users_balance($user->ID);

    ob_start(); ?>
    <div id="mycred-polar-form-wrapper" style="max-width: 500px; padding: 20px; border: 2px solid #0073aa; border-radius: 8px; background: #f9f9f9;">
        <h3 style="margin-top: 0;">💎 Purchase myCred Points</h3>
        <div style="background: white; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
            <p style="margin: 0;"><strong>Current Balance:</strong> <?php echo $mycred->format_creds($current_balance); ?></p>
        </div>
        <p><strong>Exchange Rate:</strong> $<?php echo esc_html(number_format($exchange_rate, 3)); ?> per point</p>
        <label for="mycred-points-input"><strong>Enter number of points:</strong></label>
        <input type="number" id="mycred-points-input" value="<?php echo esc_attr($default_points); ?>" min="<?php echo esc_attr($min_points); ?>" step="1" style="padding: 10px; font-size: 16px; width: 100%; margin: 10px 0; border: 2px solid #ddd; border-radius: 4px;">
        <div id="mycred-cost-display" style="background: #0073aa; color: white; padding: 15px; margin: 15px 0; border-radius: 5px; text-align: center;">
            <strong style="font-size: 24px;">$<span id="mycred-cost-amount">0.00</span></strong>
        </div>
        <button id="mycred-polar-pay-button" style="padding: 15px 30px; font-size: 18px; cursor: pointer; width: 100%; background: #00a32a; color: white; border: none; border-radius: 5px; font-weight: bold;">🛒 Purchase Now</button>
        <p id="mycred-error-msg" style="color: red; display: none; margin-top: 10px; font-weight: bold;"></p>
        <p id="mycred-loading-msg" style="display: none; margin-top: 10px; text-align: center;">⏳ Creating checkout session...</p>
    </div>
    <script>
    (function() {
        const exchangeRate = <?php echo floatval($exchange_rate); ?>;
        const minPoints = <?php echo intval($min_points); ?>;
        const payButton = document.getElementById("mycred-polar-pay-button");
        const pointsInput = document.getElementById("mycred-points-input");
        const costDisplay = document.getElementById("mycred-cost-amount");
        const errorText = document.getElementById("mycred-error-msg");
        const loadingText = document.getElementById("mycred-loading-msg");
        function updateCost() {
            const points = parseInt(pointsInput.value) || 0;
            const cost = points * exchangeRate;
            costDisplay.textContent = cost.toFixed(2);
        }
        pointsInput.addEventListener("input", updateCost);
        updateCost();
        payButton.addEventListener("click", function() {
            const points = parseInt(pointsInput.value);
            if (isNaN(points) || points < minPoints) {
                errorText.textContent = "Please enter at least " + minPoints + " points.";
                errorText.style.display = "block";
                return;
            }
            errorText.style.display = "none";
            loadingText.style.display = "block";
            payButton.disabled = true;
            const costCents = Math.round((points * exchangeRate) * 100);
            const formData = new URLSearchParams();
            formData.append('action', 'mycred_polar_create_checkout');
            formData.append('points', points);
            formData.append('amount', costCents);
            formData.append('nonce', '<?php echo wp_create_nonce('mycred_polar_checkout'); ?>');
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData.toString()
            })
            .then(async (r) => {
                const text = await r.text();
                try { return JSON.parse(text); } catch (e) { throw new Error('AJAX returned non‑JSON: ' + text.slice(0, 300)); }
            })
            .then(data => {
                if (data.success && data.data.url) {
                    window.location.href = data.data.url;
                } else {
                    errorText.textContent = "Error: " + (data.data?.message || "Failed to create checkout");
                    errorText.style.display = "block";
                    loadingText.style.display = "none";
                    payButton.disabled = false;
                    console.error('Checkout error:', data);
                }
            })
            .catch(error => {
                errorText.textContent = "Error: " + error.message;
                errorText.style.display = "block";
                loadingText.style.display = "none";
                payButton.disabled = false;
                console.error('Checkout error:', error);
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('mycred_polar_form', 'mycred_polar_render_form_shortcode');

/* -----------------------------------------------------------
   AJAX - create checkout (POST /v1/checkouts, fallback to /custom)
----------------------------------------------------------- */
function mycred_polar_create_checkout() {
    check_ajax_referer('mycred_polar_checkout', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(array('message' => 'User not logged in'));

    $points = intval($_POST['points'] ?? 0);
    $amount = intval($_POST['amount'] ?? 0); // cents
    $user = wp_get_current_user();
    $o = mycred_polar_get_options();

    $mode = $o['mode'] ?? 'sandbox';
    $access_token = ($mode === 'live') ? ($o['access_token_live'] ?? '') : ($o['access_token_sandbox'] ?? '');
    $product_id  = ($mode === 'live') ? ($o['product_id_live'] ?? '')     : ($o['product_id_sandbox'] ?? '');
    $api_base    = ($mode === 'live') ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';

    if (empty($access_token) || empty($product_id)) {
        wp_send_json_error(array('message' => 'Polar.sh not configured. Please contact administrator.'));
    }

    $meta = array(
        'user_id' => (string) $user->ID,
        'points'  => (string) $points,
        'amount_cents' => (string) $amount,
        'wp_user_email' => $user->user_email,
    );

    $payload = array(
        'products'        => array($product_id),
        'amount'          => $amount,
        'customer_email'  => $user->user_email,
        'metadata'        => $meta,
        'success_url'     => get_site_url() . '/mycred-success?checkout_id={CHECKOUT_ID}',
    );

    error_log('Polar Checkout Request (main): ' . wp_json_encode($payload));
    $resp = wp_remote_post($api_base . '/v1/checkouts', array(
        'headers' => array('Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json'),
        'body'    => wp_json_encode($payload),
        'timeout' => 30,
    ));

    if (is_wp_error($resp)) {
        error_log('Polar API Error: ' . $resp->get_error_message());
        wp_send_json_error(array('message' => 'API Error: ' . $resp->get_error_message()));
    }

    $code = wp_remote_retrieve_response_code($resp);
    $raw  = wp_remote_retrieve_body($resp);
    $body = json_decode($raw, true);
    error_log('Polar Response Code (main): ' . $code);
    error_log('Polar Response (main): ' . (is_array($body) ? print_r($body, true) : $raw));

    if (($code === 200 || $code === 201) && is_array($body) && isset($body['url'])) {
        wp_send_json_success(array('url' => $body['url'], 'checkout_id' => $body['id'] ?? ''));
    }

    // Fallback to /v1/checkouts/custom for older orgs
    if (in_array($code, array(404, 405, 422), true)) {
        $payload_fb = array(
            'product_id'      => $product_id,
            'amount'          => $amount,
            'customer_email'  => $user->user_email,
            'metadata'        => $meta,
            'success_url'     => get_site_url() . '/mycred-success?checkout_id={CHECKOUT_ID}',
        );
        error_log('Polar Checkout Request (fallback): ' . wp_json_encode($payload_fb));
        $resp2 = wp_remote_post($api_base . '/v1/checkouts/custom', array(
            'headers' => array('Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json'),
            'body'    => wp_json_encode($payload_fb),
            'timeout' => 30,
        ));
        $code2 = wp_remote_retrieve_response_code($resp2);
        $raw2  = wp_remote_retrieve_body($resp2);
        $body2 = json_decode($raw2, true);
        error_log('Polar Response Code (fallback): ' . $code2);
        error_log('Polar Response (fallback): ' . (is_array($body2) ? print_r($body2, true) : $raw2));
        if (($code2 === 200 || $code2 === 201) && is_array($body2) && isset($body2['url'])) {
            wp_send_json_success(array('url' => $body2['url'], 'checkout_id' => $body2['id'] ?? ''));
        }
        $err = is_array($body2) ? ($body2['detail'] ?? ($body2['message'] ?? 'Unknown error')) : 'Unexpected API response';
        wp_send_json_error(array('message' => 'Checkout failed: ' . $err, 'status_code' => $code2, 'response' => $body2));
    }

    $err = is_array($body) ? ($body['detail'] ?? ($body['message'] ?? 'Unknown error')) : ('Unexpected API response: ' . substr($raw, 0, 300));
    wp_send_json_error(array('message' => 'Checkout failed: ' . $err, 'status_code' => $code, 'response' => $body));
}
add_action('wp_ajax_mycred_polar_create_checkout', 'mycred_polar_create_checkout');

/* -----------------------------------------------------------
   AJAX - test connection
----------------------------------------------------------- */
function mycred_polar_test_connection() {
    check_ajax_referer('mycred_polar_test', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Permission denied'));

    $o = mycred_polar_get_options();
    $mode = $o['mode'] ?? 'sandbox';
    $access_token = ($mode === 'live') ? ($o['access_token_live'] ?? '') : ($o['access_token_sandbox'] ?? '');
    $api = ($mode === 'live') ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';

    if (empty($access_token)) wp_send_json_error(array('message' => 'Access token not set.'));

    $resp = wp_remote_get($api . '/v1/products', array(
        'headers' => array('Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json'),
        'timeout' => 15
    ));
    if (is_wp_error($resp)) wp_send_json_error(array('message' => 'Connection error: ' . $resp->get_error_message()));

    $code = wp_remote_retrieve_response_code($resp);
    $body = json_decode(wp_remote_retrieve_body($resp), true);
    error_log('Polar Test Response Code: ' . $code);
    error_log('Polar Test Response: ' . print_r($body, true));

    if ($code == 200) {
        wp_send_json_success(array('message' => '✅ Connection successful! (' . ucfirst($mode) . ' mode)', 'products_found' => isset($body['items']) ? count($body['items']) : 'N/A'));
    } elseif ($code == 401) {
        wp_send_json_error(array('message' => 'Invalid token. Ensure polar_at_ and scopes products:read, checkouts:write, orders:read.'));
    } else {
        $msg = is_array($body) ? ($body['detail'] ?? ($body['message'] ?? 'Unknown error')) : 'Unknown error';
        wp_send_json_error(array('message' => 'Failed (Status ' . $code . '): ' . $msg));
    }
}
add_action('wp_ajax_mycred_polar_test_connection', 'mycred_polar_test_connection');

/* -----------------------------------------------------------
   REST: Webhook endpoint
----------------------------------------------------------- */
function mycred_polar_register_webhook_endpoint() {
    register_rest_route('mycred-polar/v1', '/webhook', array(
        'methods' => 'POST',
        'callback' => 'mycred_polar_handle_webhook',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'mycred_polar_register_webhook_endpoint');

/* Svix/Standard Webhooks verification */
function mycred_polar_verify_webhook_signature_std(WP_REST_Request $request, string $payload, string $secret): bool {
    $id = $request->get_header('webhook-id') ?: $request->get_header('svix-id');
    $ts = $request->get_header('webhook-timestamp') ?: $request->get_header('svix-timestamp');
    $sig = $request->get_header('webhook-signature') ?: $request->get_header('svix-signature');

    if (empty($id) || empty($ts) || empty($sig)) { error_log('Polar Webhook: missing required headers'); return false; }
    if (!ctype_digit((string)$ts) || abs(time() - (int)$ts) > 300) { error_log('Polar Webhook: timestamp outside tolerance'); return false; }

    // Normalize whsec_ secret (base64 or base64url)
    $raw = preg_match('/^whsec_(.+)$/', trim($secret), $m) ? $m[1] : trim($secret);
    $key = base64_decode($raw, true);
    if ($key === false) {
        $b64 = strtr($raw, '-_', '+/');
        $b64 .= str_repeat('=', (4 - strlen($b64) % 4) % 4);
        $key = base64_decode($b64, true);
    }
    if ($key === false) { error_log('Polar Webhook: invalid secret format after normalization'); return false; }

    $signed = $id . '.' . $ts . '.' . $payload;
    $expected = base64_encode(hash_hmac('sha256', $signed, $key, true));

    // Accept "v1,..." or "v1=..."
    $candidates = preg_split('/\s+/', trim($sig));
    foreach ($candidates as $entry) {
        $entry = trim($entry);
        $provided = null;
        if (stripos($entry, 'v1,') === 0) $provided = substr($entry, 3);
        elseif (stripos($entry, 'v1=') === 0) $provided = substr($entry, 3);
        if ($provided && hash_equals($expected, $provided)) {
            error_log('Polar Webhook: signature verified');
            return true;
        }
    }
    error_log('Polar Webhook: signature verification failed');
    return false;
}

/* Award points helper (idempotent) */
function mycred_polar_award_points($user_id, $points, $order_id, $amount_cents, $raw_payload = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'mycred_polar_logs';

    // Idempotency: already have this order?
    $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table WHERE order_id = %s", $order_id));
    if ($existing) {
        error_log('Polar Award: already processed ' . $order_id);
        return true;
    }

    $o = mycred_polar_get_options();
    $point_type = $o['point_type'] ?? 'mycred_default';
    $log_entry = $o['log_entry'] ?? 'Points purchased via Polar.sh (Order: %order_id%)';
    $log_entry = str_replace(
        array('%points%', '%order_id%', '%amount%'),
        array($points, $order_id, '$' . number_format($amount_cents / 100, 2)),
        $log_entry
    );

    if (!function_exists('mycred_add')) return false;

    $ok = mycred_add('polar_purchase', $user_id, $points, $log_entry, 0, '', $point_type);
    if ($ok === false) {
        error_log('Polar Award: mycred_add failed for user ' . $user_id);
        $wpdb->insert($table, array(
            'user_id' => $user_id, 'order_id' => $order_id, 'points' => $points,
            'amount' => $amount_cents, 'status' => 'failed', 'webhook_data' => $raw_payload
        ));
        return false;
    }

    $wpdb->insert($table, array(
        'user_id' => $user_id, 'order_id' => $order_id, 'points' => $points,
        'amount' => $amount_cents, 'status' => 'success', 'webhook_data' => $raw_payload
    ));

    error_log("Polar Award: awarded {$points} points to user {$user_id} (order {$order_id})");
    return true;
}

/* -----------------------------------------------------------
   Webhook handler (order.paid preferred, checkout.updated fallback)
----------------------------------------------------------- */
function mycred_polar_handle_webhook($request) {
    if (!mycred_polar_check_mycred()) return new WP_REST_Response(array('error' => 'myCred not active'), 500);

    $o = mycred_polar_get_options();
    $webhook_secret = $o['webhook_secret'] ?? '';

    $body = $request->get_body();
    error_log('Polar Webhook: received ' . strlen($body) . ' bytes');

    if (!empty($webhook_secret)) {
        if (!mycred_polar_verify_webhook_signature_std($request, $body, $webhook_secret)) {
            return new WP_REST_Response(array('error' => 'Invalid signature'), 403);
        }
    } else {
        error_log('Polar Webhook: verification disabled');
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        error_log('Polar Webhook: invalid JSON');
        return new WP_REST_Response(array('error' => 'Invalid payload'), 400);
    }

    $event_type = $data['type'] ?? '';
    error_log('Polar Webhook: event ' . $event_type);

    $user_id = 0; $points = 0; $order_id = ''; $amount = 0;

    if ($event_type === 'order.paid' || $event_type === 'order.updated') {
        $order = $data['data'] ?? array();
        if ($event_type === 'order.updated' && (($order['status'] ?? '') !== 'paid')) {
            return new WP_REST_Response(array('success' => true, 'message' => 'Order not paid yet'), 200);
        }
        $meta = $order['metadata'] ?? array();
        $user_id  = intval($meta['user_id'] ?? 0);
        $points   = intval($meta['points'] ?? 0);
        $order_id = $order['id'] ?? '';
        $amount   = intval($order['net_amount'] ?? ($order['amount'] ?? 0));
        if ($user_id > 0 && $points > 0 && $order_id !== '') {
            $ok = mycred_polar_award_points($user_id, $points, $order_id, $amount, $body);
            return new WP_REST_Response(array('success' => $ok), $ok ? 202 : 500);
        }
        return new WP_REST_Response(array('error' => 'Invalid data'), 400);
    }

    if ($event_type === 'checkout.updated') {
        $checkout = $data['data'] ?? array();
        if (($checkout['status'] ?? '') !== 'succeeded') {
            return new WP_REST_Response(array('success' => true, 'message' => 'Checkout not succeeded'), 200);
        }
        $meta = $checkout['metadata'] ?? array();
        $user_id  = intval($meta['user_id'] ?? 0);
        $points   = intval($meta['points'] ?? 0);
        $order_id = $checkout['id'] ?? '';
        $amount   = intval($checkout['net_amount'] ?? ($checkout['amount'] ?? ($meta['amount_cents'] ?? 0)));
        if ($user_id > 0 && $points > 0 && $order_id !== '') {
            $ok = mycred_polar_award_points($user_id, $points, $order_id, $amount, $body);
            return new WP_REST_Response(array('success' => $ok), $ok ? 202 : 500);
        }
        return new WP_REST_Response(array('error' => 'Invalid data'), 400);
    }

    return new WP_REST_Response(array('success' => true, 'message' => 'Event ignored'), 200);
}

/* -----------------------------------------------------------
   Success page: server-side fallback to credit points
   1) Try GET /v1/orders?checkout_id=... (preferred)
   2) Fallback to GET /v1/checkouts/{id} if needed
----------------------------------------------------------- */
function mycred_polar_success_page() {
    if (!isset($_GET['checkout_id'])) return;

    $checkout_id = sanitize_text_field($_GET['checkout_id']);
    $o = mycred_polar_get_options();
    $mode = $o['mode'] ?? 'sandbox';
    $access_token = ($mode === 'live') ? ($o['access_token_live'] ?? '') : ($o['access_token_sandbox'] ?? '');
    $api_base = ($mode === 'live') ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';

    if (!empty($access_token) && !empty($checkout_id) && mycred_polar_check_mycred()) {
        // Small polling: try to see the order that matches this checkout
        for ($i = 0; $i < 3; $i++) {
            $url = add_query_arg(array('checkout_id' => $checkout_id), $api_base . '/v1/orders');
            $resp = wp_remote_get($url, array('headers' => array('Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json'), 'timeout' => 15));
            if (!is_wp_error($resp)) {
                $code = wp_remote_retrieve_response_code($resp);
                $body = json_decode(wp_remote_retrieve_body($resp), true);
                if ($code === 200 && is_array($body) && !empty($body['items'])) {
                    $order = $body['items'][0];
                    if (($order['status'] ?? '') === 'paid') {
                        $meta     = $order['metadata'] ?? array();
                        $user_id  = intval($meta['user_id'] ?? 0);
                        $points   = intval($meta['points'] ?? 0);
                        $order_id = $order['id'] ?? $checkout_id;
                        $amount   = intval($order['net_amount'] ?? ($order['amount'] ?? 0));
                        if ($user_id > 0 && $points > 0) {
                            mycred_polar_award_points($user_id, $points, $order_id, $amount, 'success-page');
                        }
                        break;
                    }
                }
            }
            usleep(500000); // 0.5s
        }

        // Fallback: check checkout directly for succeeded status if no paid order was found yet
        $resp2 = wp_remote_get($api_base . '/v1/checkouts/' . rawurlencode($checkout_id), array('headers' => array('Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json'), 'timeout' => 15));
        if (!is_wp_error($resp2)) {
            $code2 = wp_remote_retrieve_response_code($resp2);
            $checkout = json_decode(wp_remote_retrieve_body($resp2), true);
            if ($code2 === 200 && is_array($checkout) && (($checkout['status'] ?? '') === 'succeeded')) {
                $meta     = $checkout['metadata'] ?? array();
                $user_id  = intval($meta['user_id'] ?? 0);
                $points   = intval($meta['points'] ?? 0);
                $order_id = $checkout['id'] ?? $checkout_id;
                $amount   = intval($checkout['net_amount'] ?? ($checkout['amount'] ?? ($meta['amount_cents'] ?? 0)));
                if ($user_id > 0 && $points > 0) {
                    mycred_polar_award_points($user_id, $points, $order_id, $amount, 'success-page-checkout');
                }
            }
        }
    }

    // Render a simple success page
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Successful</title>
        <meta name="robots" content="noindex,nofollow">
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f0f0f0; }
            .success-box { background: white; border: 3px solid #00a32a; border-radius: 10px; padding: 40px; max-width: 500px; margin: 0 auto; }
            .checkmark { font-size: 80px; color: #00a32a; }
            h1 { color: #00a32a; }
            .button { display: inline-block; padding: 15px 30px; background: #0073aa; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="success-box">
            <div class="checkmark">✓</div>
            <h1>Payment Successful!</h1>
            <p>Thank you for your purchase. Your points will appear shortly (usually within a few seconds).</p>
            <a href="<?php echo esc_url(home_url()); ?>" class="button">Return to Home</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function mycred_polar_rewrite_rules() { add_rewrite_rule('^mycred-success/?', 'index.php?mycred_polar_success=1', 'top'); }
add_action('init', 'mycred_polar_rewrite_rules');
function mycred_polar_query_vars($vars) { $vars[] = 'mycred_polar_success'; return $vars; }
add_filter('query_vars', 'mycred_polar_query_vars');
function mycred_polar_template_redirect() { if (get_query_var('mycred_polar_success')) mycred_polar_success_page(); }
add_action('template_redirect', 'mycred_polar_template_redirect');

/* -----------------------------------------------------------
   Admin row settings link
----------------------------------------------------------- */
function mycred_polar_settings_link($links) {
    $settings_link = '<a href="admin.php?page=mycred_polar_settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'mycred_polar_settings_link');