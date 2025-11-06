<?php
/**
 * Transaction Logs Page Template
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'mycred_polar_logs';
$logs = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");
?>

<div class="wrap">
    <h1>Transaction Logs</h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Points</th>
                <th>Amount</th>
                <th>Order/Checkout ID</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6">No transactions yet.</td></tr>
            <?php else: foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo esc_html($log->created_at); ?></td>
                    <td><?php echo esc_html(get_userdata($log->user_id)->user_login ?? 'Unknown'); ?></td>
                    <td><?php echo esc_html($log->points); ?></td>
                    <td><?php echo esc_html(number_format($log->amount / 100, 2)); ?></td>
                    <td><?php echo esc_html($log->order_id); ?></td>
                    <td><?php echo esc_html($log->status); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>