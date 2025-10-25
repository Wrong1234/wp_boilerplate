<?php

if(!defined('ABSPATH')) exit;


// ✅ SMTP Configuration - Use constants from wp-config.php for security
add_action('phpmailer_init', function($phpmailer) {
    // Only configure if constants are defined in wp-config.php
    if (defined('PERRYSTOWN_SMTP_HOST') && defined('PERRYSTOWN_SMTP_USER') && defined('PERRYSTOWN_SMTP_PASS')) {
        error_log('PHPMailer Init Hook Triggered');
        
        $phpmailer->isSMTP();
        $phpmailer->Host = PERRYSTOWN_SMTP_HOST;
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = PERRYSTOWN_SMTP_PORT ?? 587;
        $phpmailer->Username = PERRYSTOWN_SMTP_USER;
        $phpmailer->Password = PERRYSTOWN_SMTP_PASS;
        $phpmailer->SMTPSecure = PERRYSTOWN_SMTP_SECURE ?? 'tls';
        $phpmailer->From = PERRYSTOWN_SMTP_FROM ?? PERRYSTOWN_SMTP_USER;
        $phpmailer->FromName = PERRYSTOWN_SMTP_FROM_NAME ?? 'Perrystown';
        
        // Enable debug in development only
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $phpmailer->SMTPDebug = 2;
            $phpmailer->Debugoutput = function($str, $level) {
                error_log("SMTP Debug level $level: $str");
            };
        }
        
        error_log('SMTP configured with host: ' . $phpmailer->Host);
    }
});