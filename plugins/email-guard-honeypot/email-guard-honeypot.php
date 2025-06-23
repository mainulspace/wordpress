<?php
/**
 * Plugin Name: Email Guard & Honeypot Protection
 * Description: Blocks spam and prevents PHP mail() abuse. Sets safe FROM headers, injects honeypots into Contact Form 7 and comments, and includes test tools.
 */

defined('ABSPATH') || exit; // Prevent direct access

// === Get Safe Site Email (contact@domain.com fallback) === //
function get_site_safe_email() {
    $domain = parse_url(home_url(), PHP_URL_HOST);
    $domain = preg_replace('/^www\./', '', strtolower($domain));
    return 'contact@' . $domain;
}

// === Get Safe Admin Email (fallback) === //
function get_fallback_from_email() {
    $admin_email = get_option('admin_email');
    $domain = strtolower(substr(strrchr($admin_email, "@"), 1));
    $disallowed_domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com'];

    if (filter_var($admin_email, FILTER_VALIDATE_EMAIL) && !in_array($domain, $disallowed_domains)) {
        return $admin_email;
    }
    return get_site_safe_email();
}

// === Clean FROM email === //
add_filter('wp_mail_from', function($email) {
    $unsafe = ['wordpress@', 'admin@', 'localhost'];
    $isInvalid = empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL);

    foreach ($unsafe as $pattern) {
        if (stripos($email, $pattern) !== false) {
            $isInvalid = true;
            break;
        }
    }

    return $isInvalid ? get_fallback_from_email() : $email;
});

// === Clean FROM name === //
add_filter('wp_mail_from_name', function($name) {
    return (empty($name) || strtolower($name) === 'wordpress' || strtolower($name) === 'admin')
        ? get_bloginfo('name')
        : $name;
});

// === Block wp_mail() if honeypot is filled === //
add_filter('wp_mail', function($args) {
    if (!empty($_POST['honeypot_field'])) {
        error_log('❌ Blocked bot email attempt via honeypot.');
        return false;
    }
    return $args;
});

// === Inject Honeypot into CF7 === //
add_filter('wpcf7_form_elements', function($form) {
    $honeypot = '<span style="display:none;"><input type="text" name="honeypot_field" class="hp" tabindex="-1" autocomplete="off"></span>';
    return $form . $honeypot;
});

// === Block CF7 submission if honeypot is filled === //
add_filter('wpcf7_validate', function($result, $tags) {
    if (!empty($_POST['honeypot_field'])) {
        return new WPCF7_Validation(false, 'Bot submission blocked.');
    }
    return $result;
}, 10, 2);

// === Inject Honeypot into WordPress comments === //
function inject_comment_honeypot() {
    echo '<p style="display:none;"><label>Leave this field empty:<input type="text" name="honeypot_field" autocomplete="off"></label></p>';
}
add_action('comment_form_after_fields', 'inject_comment_honeypot');
add_action('comment_form_logged_in_after', 'inject_comment_honeypot');

// === Block comment if honeypot is filled === //
add_filter('preprocess_comment', function($commentdata) {
    if (!empty($_POST['honeypot_field'])) {
        wp_die('Bot comment blocked.');
    }
    return $commentdata;
});

// === Trigger Test Email via URL (admin only) https://yourdomain.com/?send_test_email=1 === //
add_action('init', function () {
    if (
        isset($_GET['send_test_email']) &&
        $_GET['send_test_email'] === '1' &&
        is_user_logged_in() &&
        current_user_can('manage_options')
    ) {
        $to = get_option('admin_email');
        $subject = '✅ Email Guard Plugin Test';
        $message = 'This is a test email sent by the Email Guard & Honeypot Protection plugin.';
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $success = wp_mail($to, $subject, $message, $headers);
        exit($success ? '✅ Test email sent to admin email.' : '❌ Failed to send test email.');
    }
});

// === Block native PHP mail() if override is possible === //
if (!function_exists('mail')) {
    function mail() {
        error_log('❌ mail() function blocked via plugin override.');
        return false;
    }
}