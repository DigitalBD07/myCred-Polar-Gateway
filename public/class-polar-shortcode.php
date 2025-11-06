<?php
/**
 * [mycred_polar_form] Shortcode
 */

if (!defined('ABSPATH')) exit;

class MyCred_Polar_Shortcode {
    
    public function __construct() {
        add_shortcode('mycred_polar_form', array($this, 'render'));
    }
    
    /**
     * Render shortcode
     */
    public function render() {
        if (!mycred_polar_check_mycred()) {
            return '<p style="color:red;">myCred plugin is required.</p>';
        }
        
        if (!is_user_logged_in()) {
            return '<p>You must be logged in to purchase points. <a href="' . esc_url(wp_login_url(get_permalink())) . '">Login here</a>.</p>';
        }
        
        $o = mycred_polar_get_options();
        $ex = floatval($o['exchange_rate']);
        $minp = intval($o['min_points']);
        $defp = intval($o['default_points']);
        $plans = $o['subscription_plans'];
        $user = wp_get_current_user();
        $myc = mycred($o['point_type']);
        $bal = $myc->get_users_balance($user->ID);
        
        ob_start();
        require MYCRED_POLAR_PATH . 'public/views/shortcode-form.php';
        return ob_get_clean();
    }
}

// Initialize shortcode
new MyCred_Polar_Shortcode();