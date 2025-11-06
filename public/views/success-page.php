<?php
/**
 * Success Page Template
 */

if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful</title>
    <meta name="robots" content="noindex,nofollow">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f0f0f0; }
        .success-box { background: white; border: 3px solid #00a32a; border-radius: 10px; padding: 40px; max-width: 520px; margin: 0 auto; }
        .checkmark { font-size: 80px; color: #00a32a; }
        h1 { color: #00a32a; }
        .button { display: inline-block; padding: 15px 30px; background: #0073aa; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="checkmark">✓</div>
        <h1>Payment Successful!</h1>
        <p>Thanks! Your points will appear shortly.</p>
        <a href="<?php echo esc_url(home_url()); ?>" class="button">Return to Home</a>
    </div>
</body>
</html>