<?php
/**
 * Plugin Name: World Inspire Email Validation for Fluent Forms
 * Plugin URI: https://github.com/vecyang1/fluentform-email-guard
 * Description: Production-grade multi-layer real-time email defense for Fluent Forms and WordPress forms (WPForms, CF7, Gravity Forms, Forminator, Ninja Forms, WooCommerce, WP Registration). Blocks disposable/temporary domains (8,700+ domains), verifies live DNS MX records with 24h caching, auto-suggests typo corrections (e.g. gamil.com -> gmail.com), and prevents hard bounces.
 * Version: 1.2.0
 * Author: World Inspire LLC, Vec
 * Author URI: https://worldinspirelab.com/
 * License: GPL-2.0-or-later
 * Text Domain: world-inspire-email-validation-for-fluent-forms
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GM_FF_EMAIL_GUARD_VERSION', '1.2.0');
define('GM_FF_EMAIL_GUARD_FILE', __FILE__);
define('GM_FF_EMAIL_GUARD_BASENAME', plugin_basename(__FILE__));
define('GM_FF_EMAIL_GUARD_PATH', plugin_dir_path(__FILE__));
define('GM_FF_EMAIL_GUARD_GITHUB_REPO', 'vecyang1/fluentform-email-guard');

/**
 * Default configuration array (Single Source of Truth in wp_options).
 */
function gm_ff_email_guard_default_config() {
    return [
        'enabled' => true,
        'version' => GM_FF_EMAIL_GUARD_VERSION,
        'integrations' => [
            'fluentform'   => true,
            'wpforms'      => true,
            'wpcf7'        => true,
            'gravityforms' => true,
            'forminator'   => true,
            'ninja_forms'  => true,
            'woocommerce'  => true,
            'wp_register'  => true,
        ],
        'checks' => [
            'syntax' => true,
            'mx' => true,
            'disposable' => true,
            'typo' => true,
            'blocked_domains' => true,
            'role' => false,
            'external_api' => false,
        ],
        'blocked_domains' => [
            'search-glintmuse.com',
            'q2.com'
        ],
        'whitelist_domains' => [
            'glintmuse.com'
        ],
        'blocked_roles' => [
            'admin', 'support', 'info', 'sales', 'billing', 'abuse', 'postmaster', 'webmaster'
        ],
        'typo_domains' => [
            'gamil.com'     => 'gmail.com',
            'gmai.com'      => 'gmail.com',
            'gmial.com'     => 'gmail.com',
            'gmaill.com'    => 'gmail.com',
            'gmail.co'      => 'gmail.com',
            'hotmial.com'   => 'hotmail.com',
            'hotmaill.com'  => 'hotmail.com',
            'hotmali.com'   => 'hotmail.com',
            'outlok.com'    => 'outlook.com',
            'outllok.com'   => 'outlook.com',
            'yaho.com'      => 'yahoo.com',
            'yahooo.com'    => 'yahoo.com',
            'iclou.com'     => 'icloud.com',
            'iclud.com'     => 'icloud.com',
        ],
        'cache_ttl' => 86400,
        'target_forms' => [],
        'messages' => [
            'syntax' => 'Please enter a valid email address.',
            'typo' => 'Did you mean %s? Please verify your email.',
            'blocked_domain' => 'Email addresses from this domain are not accepted.',
            'disposable' => 'Temporary or disposable email addresses are not accepted.',
            'no_mx' => 'The domain of this email cannot receive mail. Please check for typos.',
            'role_account' => 'Generic role-based email addresses (e.g. admin, support) are not accepted.'
        ]
    ];
}

function gm_ff_email_guard_get_config() {
    $config = get_option('fluentform_email_guard_config');
    $default = gm_ff_email_guard_default_config();
    if (!is_array($config)) {
        $config = $default;
        update_option('fluentform_email_guard_config', $config);
        return $config;
    }
    $merged = wp_parse_args($config, $default);
    if (!isset($merged['integrations']) || !is_array($merged['integrations'])) {
        $merged['integrations'] = $default['integrations'];
    } else {
        $merged['integrations'] = wp_parse_args($merged['integrations'], $default['integrations']);
    }
    if (!isset($merged['checks']) || !is_array($merged['checks'])) {
        $merged['checks'] = $default['checks'];
    } else {
        $merged['checks'] = wp_parse_args($merged['checks'], $default['checks']);
    }
    return $merged;
}

function gm_ff_email_guard_update_config($new_config) {
    return update_option('fluentform_email_guard_config', $new_config);
}

/**
 * Supported integrations and runtime dynamic detection.
 * Derived at runtime to prevent state desynchronization (SSOT).
 */
function gm_ff_email_guard_get_supported_integrations() {
    return [
        'fluentform' => [
            'label'     => __('Fluent Forms', 'world-inspire-email-validation-for-fluent-forms'),
            'is_active' => defined('FLUENTFORM') || function_exists('wpFluentForm'),
            'desc'      => __('Protects Fluent Forms standard and custom email fields.', 'world-inspire-email-validation-for-fluent-forms'),
            'badge'     => 'Fluent Forms',
        ],
        'wpforms' => [
            'label'     => __('WPForms', 'world-inspire-email-validation-for-fluent-forms'),
            'is_active' => function_exists('wpforms') || class_exists('WPForms'),
            'desc'      => __('Protects WPForms standard and conversational email fields.', 'world-inspire-email-validation-for-fluent-forms'),
            'badge'     => 'WPForms',
        ],
        'wpcf7' => [
            'label'     => __('Contact Form 7', 'world-inspire-email-validation-for-fluent-forms'),
            'is_active' => defined('WPCF7_VERSION') || class_exists('WPCF7'),
            'desc'      => __('Protects Contact Form 7 [email] and [email*] tags.', 'world-inspire-email-validation-for-fluent-forms'),
            'badge'     => 'CF7',
        ],
        'gravityforms' => [
            'label'     => __('Gravity Forms', 'world-inspire-email-validation-for-fluent-forms'),
            'is_active' => class_exists('GFForms') || class_exists('RGForms'),
            'desc'      => __('Protects Gravity Forms email fields across all forms.', 'world-inspire-email-validation-for-fluent-forms'),
            'badge'     => 'Gravity Forms',
        ],
        'forminator' => [
            'label'     => __('Forminator', 'world-inspire-email-validation-for-fluent-forms'),
            'is_active' => class_exists('Forminator') || defined('FORMINATOR_VERSION'),
            'desc'      => __('Protects Forminator form submission email inputs.', 'world-inspire-email-validation-for-fluent-forms'),
            'badge'     => 'Forminator',
        ],
        'ninja_forms' => [
            'label'     => __('Ninja Forms', 'world-inspire-email-validation-for-fluent-forms'),
            'is_active' => class_exists('Ninja_Forms') || function_exists('Ninja_Forms'),
            'desc'      => __('Protects Ninja Forms email fields.', 'world-inspire-email-validation-for-fluent-forms'),
            'badge'     => 'Ninja Forms',
        ],
        'woocommerce' => [
            'label'     => __('WooCommerce', 'world-inspire-email-validation-for-fluent-forms'),
            'is_active' => class_exists('WooCommerce') || function_exists('WC'),
            'desc'      => __('Protects WooCommerce checkout billing email and customer registration.', 'world-inspire-email-validation-for-fluent-forms'),
            'badge'     => 'WooCommerce',
        ],
        'wp_register' => [
            'label'     => __('WordPress Core Registration', 'world-inspire-email-validation-for-fluent-forms'),
            'is_active' => (bool)get_option('users_can_register', 0),
            'desc'      => __('Intercepts disposable and malformed emails during native WordPress registration.', 'world-inspire-email-validation-for-fluent-forms'),
            'badge'     => 'WP Core',
        ],
    ];
}

function gm_ff_email_guard_builtin_disposable_domains() {
    return [
        'mailinator.com', 'tempmail.com', '10minutemail.com', 'guerrillamail.com',
        'sharklasers.com', 'yopmail.com', 'trashmail.com', 'dispostable.com',
        'getairmail.com', 'crazymailing.com', 'temp-mail.org', 'mohmal.com',
        'dropmail.me', 'inboxkitten.com', 'fakemailgenerator.com', 'throwawaymail.com',
        'burnermail.io', 'mytemp.email', 'tempinbox.com', 'nada.ltd',
        'emailondeck.com', 'maildrop.cc', 'generator.email', 'tempmailo.com'
    ];
}

function gm_ff_email_guard_get_disposable_domains() {
    static $domain_map = null;
    if ($domain_map !== null) {
        return $domain_map;
    }

    $bundled_file = GM_FF_EMAIL_GUARD_PATH . 'data/disposable_domains.json';
    $upload_dir = wp_upload_dir();
    $override_file = $upload_dir['basedir'] . '/fluentform-email-guard/disposable_domains.json';
    $domains = [];

    if (file_exists($bundled_file)) {
        $raw = file_get_contents($bundled_file);
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && !empty($decoded)) {
            $domains = $decoded;
        }
    } elseif (file_exists($override_file)) {
        $raw = file_get_contents($override_file);
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && !empty($decoded)) {
            $domains = $decoded;
        }
    }

    $all_domains = array_unique(array_merge(gm_ff_email_guard_builtin_disposable_domains(), (array)$domains));
    $domain_map = array_flip($all_domains);
    return $domain_map;
}

function gm_ff_email_guard_check_typo($domain) {
    $config = gm_ff_email_guard_get_config();
    $typos = $config['typo_domains'] ?? [];
    return $typos[strtolower($domain)] ?? null;
}

function gm_ff_email_guard_check_mx($domain, $cache_ttl = 86400) {
    $cache_key = 'ff_eg_mx_' . md5($domain);
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached === 'valid';
    }

    $has_mx = false;
    if (function_exists('checkdnsrr')) {
        $has_mx = checkdnsrr($domain, 'MX');
        if (!$has_mx) {
            $has_mx = checkdnsrr($domain, 'A');
        }
    } else {
        $has_mx = true;
    }

    set_transient($cache_key, $has_mx ? 'valid' : 'invalid', $cache_ttl);
    return $has_mx;
}

function gm_ff_email_guard_log_block($email, $reason, $form_id = 0) {
    $logs = get_option('fluentform_email_guard_logs', []);
    if (!is_array($logs)) {
        $logs = [];
    }

    $parts = explode('@', $email);
    $user = $parts[0] ?? '';
    $domain = $parts[1] ?? '';
    $masked_user = strlen($user) > 2 ? substr($user, 0, 1) . '***' . substr($user, -1) : '***';
    $masked_email = $masked_user . '@' . $domain;

    $client_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';

    $entry = [
        'time' => current_time('mysql'),
        'email' => $masked_email,
        'domain' => $domain,
        'reason' => $reason,
        'form_id' => (int)$form_id,
        'ip' => $client_ip,
    ];

    array_unshift($logs, $entry);
    if (count($logs) > 100) {
        $logs = array_slice($logs, 0, 100);
    }
    update_option('fluentform_email_guard_logs', $logs);
}

function gm_ff_email_guard_check($email, $form_id = 0) {
    $config = gm_ff_email_guard_get_config();

    if (!$config['enabled']) {
        return ['valid' => true, 'reason' => 'guard_disabled', 'error' => '', 'suggestion' => ''];
    }

    if (!empty($config['target_forms']) && !in_array($form_id, $config['target_forms'])) {
        return ['valid' => true, 'reason' => 'form_excluded', 'error' => '', 'suggestion' => ''];
    }

    $email = trim(strtolower((string)$email));

    // Layer 1: RFC Syntax Check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || substr_count($email, '@') !== 1) {
        gm_ff_email_guard_log_block($email, 'syntax_error', $form_id);
        return [
            'valid' => false,
            'reason' => 'syntax_error',
            'error' => $config['messages']['syntax'],
            'suggestion' => ''
        ];
    }

    list($username, $domain) = explode('@', $email, 2);

    // Whitelist Bypass
    if (!empty($config['whitelist_domains']) && in_array($domain, (array)$config['whitelist_domains'])) {
        return ['valid' => true, 'reason' => 'whitelisted', 'error' => '', 'suggestion' => ''];
    }

    // Layer 2: Typo Auto-Correction Check
    if ($config['checks']['typo']) {
        $suggested = gm_ff_email_guard_check_typo($domain);
        if ($suggested) {
            $suggested_email = $username . '@' . $suggested;
            gm_ff_email_guard_log_block($email, "typo_detected: {$domain}->{$suggested}", $form_id);
            return [
                'valid' => false,
                'reason' => 'typo',
                'error' => sprintf($config['messages']['typo'], $suggested),
                'suggestion' => $suggested_email
            ];
        }
    }

    // Layer 3: Blocked Domains
    if ($config['checks']['blocked_domains'] && !empty($config['blocked_domains'])) {
        if (in_array($domain, (array)$config['blocked_domains'])) {
            gm_ff_email_guard_log_block($email, 'blocked_domain', $form_id);
            return [
                'valid' => false,
                'reason' => 'blocked_domain',
                'error' => $config['messages']['blocked_domain'],
                'suggestion' => ''
            ];
        }
    }

    // Layer 4: Disposable Domains (O(1) Hash Map)
    if ($config['checks']['disposable']) {
        $disposable_map = gm_ff_email_guard_get_disposable_domains();
        if (isset($disposable_map[$domain])) {
            gm_ff_email_guard_log_block($email, 'disposable_domain', $form_id);
            return [
                'valid' => false,
                'reason' => 'disposable',
                'error' => $config['messages']['disposable'],
                'suggestion' => ''
            ];
        }
    }

    // Layer 5: Role Accounts
    if (!empty($config['checks']['role']) && !empty($config['blocked_roles'])) {
        $roles = array_map('strtolower', (array)$config['blocked_roles']);
        if (in_array($username, $roles)) {
            gm_ff_email_guard_log_block($email, 'role_account', $form_id);
            return [
                'valid' => false,
                'reason' => 'role_account',
                'error' => $config['messages']['role_account'],
                'suggestion' => ''
            ];
        }
    }

    // Layer 6: Real-Time DNS MX Check
    if ($config['checks']['mx']) {
        $mx_valid = gm_ff_email_guard_check_mx($domain, (int)$config['cache_ttl']);
        if (!$mx_valid) {
            gm_ff_email_guard_log_block($email, 'no_mx_records', $form_id);
            return [
                'valid' => false,
                'reason' => 'no_mx',
                'error' => $config['messages']['no_mx'],
                'suggestion' => ''
            ];
        }
    }

    return [
        'valid' => true,
        'reason' => 'passed',
        'error' => '',
        'suggestion' => ''
    ];
}

/**
 * -------------------------------------------------------------------------
 * Multi-Form & Registration Integrations Layer
 * -------------------------------------------------------------------------
 */

// 1. Fluent Forms
add_filter('fluentform/validate_input_item_input_email', function ($error, $field, $formData, $fields, $form) {
    if (!empty($error)) {
        return $error;
    }
    $config = gm_ff_email_guard_get_config();
    if (empty($config['enabled']) || empty($config['integrations']['fluentform'])) {
        return $error;
    }

    $inputName = '';
    if (isset($field['attributes']['name'])) {
        $inputName = $field['attributes']['name'];
    } elseif (isset($field['name'])) {
        $inputName = $field['name'];
    }

    if (!$inputName || !isset($formData[$inputName])) {
        return $error;
    }

    $email = trim((string)$formData[$inputName]);
    if ($email === '') {
        return $error;
    }

    $form_id = is_object($form) && isset($form->id) ? (int)$form->id : 0;
    $result = gm_ff_email_guard_check($email, $form_id);

    if (!$result['valid']) {
        return [$result['error']];
    }

    return $error;
}, 20, 5);

// 2. WPForms
add_action('wpforms_process_validate_email', function ($field_id, $field_submit, $form_data) {
    $config = gm_ff_email_guard_get_config();
    if (empty($config['enabled']) || empty($config['integrations']['wpforms'])) {
        return;
    }

    $email = is_array($field_submit) ? ($field_submit['primary'] ?? ($field_submit[0] ?? '')) : (string)$field_submit;
    $email = trim($email);
    if ($email === '') {
        return;
    }

    $form_id = is_array($form_data) && isset($form_data['id']) ? (int)$form_data['id'] : 0;
    $result = gm_ff_email_guard_check($email, $form_id);

    if (!$result['valid']) {
        if (function_exists('wpforms') && isset(wpforms()->process)) {
            wpforms()->process->errors[$form_id][$field_id] = $result['error'];
        }
    }
}, 10, 3);

// 3. Contact Form 7
$gm_ff_wpcf7_validator = function ($result, $tag) {
    $config = gm_ff_email_guard_get_config();
    if (empty($config['enabled']) || empty($config['integrations']['wpcf7'])) {
        return $result;
    }

    $name = is_object($tag) ? ($tag->name ?? '') : (is_array($tag) ? ($tag['name'] ?? '') : '');
    if (!$name) {
        return $result;
    }

    $email = isset($_POST[$name]) ? trim(sanitize_text_field(wp_unslash($_POST[$name]))) : '';
    if ($email === '') {
        return $result;
    }

    $check = gm_ff_email_guard_check($email, 'cf7');
    if (!$check['valid']) {
        if (is_object($result) && method_exists($result, 'invalidate')) {
            $result->invalidate($tag, $check['error']);
        }
    }
    return $result;
};
add_filter('wpcf7_validate_email', $gm_ff_wpcf7_validator, 20, 2);
add_filter('wpcf7_validate_email*', $gm_ff_wpcf7_validator, 20, 2);

// 4. Gravity Forms
add_filter('gform_field_validation', function ($result, $value, $form, $field) {
    if (!is_array($result) || empty($result['is_valid'])) {
        return $result;
    }
    $config = gm_ff_email_guard_get_config();
    if (empty($config['enabled']) || empty($config['integrations']['gravityforms'])) {
        return $result;
    }

    $field_type = is_object($field) ? ($field->type ?? '') : (is_array($field) ? ($field['type'] ?? '') : '');
    if ($field_type !== 'email') {
        return $result;
    }

    $email = is_array($value) ? ($value[0] ?? '') : (string)$value;
    $email = trim($email);
    if ($email === '') {
        return $result;
    }

    $form_id = is_array($form) ? (int)($form['id'] ?? 0) : (is_object($form) ? (int)($form->id ?? 0) : 0);
    $check = gm_ff_email_guard_check($email, $form_id);
    if (!$check['valid']) {
        $result['is_valid'] = false;
        $result['message'] = $check['error'];
    }
    return $result;
}, 10, 4);

// 5. Forminator
add_filter('forminator_custom_form_submit_errors', function ($submit_errors, $form_id, $field_data_array) {
    $config = gm_ff_email_guard_get_config();
    if (empty($config['enabled']) || empty($config['integrations']['forminator'])) {
        return $submit_errors;
    }
    if (!is_array($field_data_array)) {
        return $submit_errors;
    }

    foreach ($field_data_array as $field) {
        $name = is_array($field) ? ($field['name'] ?? '') : '';
        $val = is_array($field) ? trim((string)($field['value'] ?? '')) : '';
        if ($val === '') {
            continue;
        }

        if (strpos($name, 'email') !== false) {
            $check = gm_ff_email_guard_check($val, (int)$form_id);
            if (!$check['valid']) {
                if (!is_array($submit_errors)) {
                    $submit_errors = [];
                }
                $submit_errors[][$name] = $check['error'];
            }
        }
    }
    return $submit_errors;
}, 10, 3);

// 6. Ninja Forms
add_filter('ninja_forms_submit_data', function ($form_data) {
    $config = gm_ff_email_guard_get_config();
    if (empty($config['enabled']) || empty($config['integrations']['ninja_forms'])) {
        return $form_data;
    }
    if (!isset($form_data['fields']) || !is_array($form_data['fields'])) {
        return $form_data;
    }

    $form_id = (int)($form_data['id'] ?? 0);
    foreach ($form_data['fields'] as $key => $field) {
        $type = $field['type'] ?? ($field['settings']['type'] ?? '');
        if ($type === 'email') {
            $val = trim((string)($field['value'] ?? ''));
            if ($val === '') {
                continue;
            }
            $check = gm_ff_email_guard_check($val, $form_id);
            if (!$check['valid']) {
                $field_id = $field['id'] ?? $key;
                $form_data['errors']['fields'][$field_id] = $check['error'];
            }
        }
    }
    return $form_data;
}, 10, 1);

// 7. WooCommerce (Checkout & Registration)
add_action('woocommerce_checkout_process', function () {
    $config = gm_ff_email_guard_get_config();
    if (empty($config['enabled']) || empty($config['integrations']['woocommerce'])) {
        return;
    }

    $email = isset($_POST['billing_email']) ? trim(sanitize_email(wp_unslash($_POST['billing_email']))) : '';
    if ($email === '') {
        return;
    }

    $check = gm_ff_email_guard_check($email, 'wc_checkout');
    if (!$check['valid']) {
        if (function_exists('wc_add_notice')) {
            wc_add_notice($check['error'], 'error');
        }
    }
});

add_action('woocommerce_register_post', function ($username, $email, $validation_errors) {
    $config = gm_ff_email_guard_get_config();
    if (empty($config['enabled']) || empty($config['integrations']['woocommerce'])) {
        return;
    }

    $email = trim((string)$email);
    if ($email === '') {
        return;
    }

    $check = gm_ff_email_guard_check($email, 'wc_register');
    if (!$check['valid'] && is_object($validation_errors) && method_exists($validation_errors, 'add')) {
        $validation_errors->add('billing_email_error', $check['error']);
    }
}, 10, 3);

// 8. WordPress Core Registration
add_filter('registration_errors', function ($errors, $sanitized_user_login, $user_email) {
    $config = gm_ff_email_guard_get_config();
    if (empty($config['enabled']) || empty($config['integrations']['wp_register'])) {
        return $errors;
    }

    $email = trim((string)$user_email);
    if ($email === '') {
        return $errors;
    }

    $check = gm_ff_email_guard_check($email, 'wp_register');
    if (!$check['valid']) {
        if (is_object($errors) && method_exists($errors, 'add')) {
            $errors->add('invalid_email_guard', $check['error']);
        }
    }
    return $errors;
}, 10, 3);

// 9. Universal Developer API
add_filter('world_inspire_verify_email', function ($result, $email, $context = 0) {
    return gm_ff_email_guard_check($email, $context);
}, 10, 3);

if (!function_exists('world_inspire_is_valid_email')) {
    function world_inspire_is_valid_email($email, $context = 0) {
        $check = gm_ff_email_guard_check($email, $context);
        return !empty($check['valid']);
    }
}

/**
 * Settings Link on Plugins Page.
 */
add_filter('plugin_action_links_' . GM_FF_EMAIL_GUARD_BASENAME, function ($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=fluentform-email-guard')) . '">' . esc_html__('Settings', 'world-inspire-email-validation-for-fluent-forms') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

/**
 * Register Admin Menu under Fluent Forms with defensive fallback.
 * Running at priority 99 to ensure parent menu 'fluent_forms' is registered.
 */
add_action('admin_menu', function () {
    global $menu;
    $has_fluent = false;
    foreach ((array)$menu as $item) {
        if (isset($item[2]) && $item[2] === 'fluent_forms') {
            $has_fluent = true;
            break;
        }
    }
    $parent = $has_fluent ? 'fluent_forms' : 'options-general.php';

    add_submenu_page(
        $parent,
        esc_html__('World Inspire Email Validation for Fluent Forms', 'world-inspire-email-validation-for-fluent-forms'),
        esc_html__('Email Validation', 'world-inspire-email-validation-for-fluent-forms'),
        'manage_options',
        'fluentform-email-guard',
        'gm_ff_email_guard_render_admin_page'
    );
}, 99);

/**
 * Render Admin Settings Page.
 */
function gm_ff_email_guard_render_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'world-inspire-email-validation-for-fluent-forms'));
    }

    $message = '';
    $message_type = 'success';

    // Handle POST Actions
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gm_ff_eg_action'])) {
        if (!check_admin_referer('gm_ff_eg_action_nonce', 'gm_ff_eg_nonce')) {
            wp_die(esc_html__('Security check failed.', 'world-inspire-email-validation-for-fluent-forms'));
        }

        $action = sanitize_text_field(wp_unslash($_POST['gm_ff_eg_action']));

        if ($action === 'save_settings') {
            $current_config = gm_ff_email_guard_get_config();
            $current_config['enabled'] = !empty($_POST['gm_ff_eg_enabled']);
            $current_config['checks']['syntax'] = !empty($_POST['gm_ff_eg_check_syntax']);
            $current_config['checks']['mx'] = !empty($_POST['gm_ff_eg_check_mx']);
            $current_config['checks']['disposable'] = !empty($_POST['gm_ff_eg_check_disposable']);
            $current_config['checks']['typo'] = !empty($_POST['gm_ff_eg_check_typo']);
            $current_config['checks']['blocked_domains'] = !empty($_POST['gm_ff_eg_check_blocked_domains']);
            $current_config['checks']['role'] = !empty($_POST['gm_ff_eg_check_role']);

            // Save Form Integrations
            $supported_integrations = gm_ff_email_guard_get_supported_integrations();
            foreach (array_keys($supported_integrations) as $int_key) {
                $current_config['integrations'][$int_key] = !empty($_POST['gm_ff_eg_integration_' . $int_key]);
            }

            // Blocked domains
            $raw_blocked = isset($_POST['gm_ff_eg_blocked_domains']) ? sanitize_textarea_field(wp_unslash($_POST['gm_ff_eg_blocked_domains'])) : '';
            $blocked_lines = array_filter(array_map('trim', explode("\n", strtolower($raw_blocked))));
            $current_config['blocked_domains'] = array_values(array_unique($blocked_lines));

            // Whitelist
            $raw_white = isset($_POST['gm_ff_eg_whitelist_domains']) ? sanitize_textarea_field(wp_unslash($_POST['gm_ff_eg_whitelist_domains'])) : '';
            $white_lines = array_filter(array_map('trim', explode("\n", strtolower($raw_white))));
            $current_config['whitelist_domains'] = array_values(array_unique($white_lines));

            // TTL
            $current_config['cache_ttl'] = isset($_POST['gm_ff_eg_cache_ttl']) ? max(300, absint(wp_unslash($_POST['gm_ff_eg_cache_ttl']))) : 86400;

            gm_ff_email_guard_update_config($current_config);
            $message = esc_html__('World Inspire Email Validation configuration successfully updated.', 'world-inspire-email-validation-for-fluent-forms');
        } elseif ($action === 'clear_logs') {
            update_option('fluentform_email_guard_logs', []);
            $message = esc_html__('Security audit telemetry logs cleared.', 'world-inspire-email-validation-for-fluent-forms');
        }
    }

    $config = gm_ff_email_guard_get_config();
    $disposable_map = gm_ff_email_guard_get_disposable_domains();
    $supported_integrations = gm_ff_email_guard_get_supported_integrations();
    $detected_active = array_filter($supported_integrations, function($item) { return !empty($item['is_active']); });
    $logs = (array)get_option('fluentform_email_guard_logs', []);
    $bundled_file = GM_FF_EMAIL_GUARD_PATH . 'data/disposable_domains.json';
    $file_mtime = file_exists($bundled_file) ? gmdate('Y-m-d H:i:s T', filemtime($bundled_file)) : 'Bundled offline';
    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            <span class="dashicons dashicons-shield-alt" style="font-size:32px;width:32px;height:32px;color:#2271b1;"></span>
            World Inspire Email Validation for Fluent Forms
            <span style="font-size:12px;background:#2271b1;color:#fff;padding:2px 8px;border-radius:12px;">v<?php echo esc_html(GM_FF_EMAIL_GUARD_VERSION); ?></span>
        </h1>
        <p class="description">Production-grade defense against disposable emails, non-existent MX domains, domain typos, and bounce-inducing submissions. Zero Ghost Logic & Single Source of Truth.</p>

        <?php if ($message): ?>
            <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($detected_active)): 
            $active_labels = wp_list_pluck($detected_active, 'label');
        ?>
            <!-- Multi-Form Detection Notice Banner -->
            <div class="notice notice-info" style="border-left-color:#2271b1;padding:12px 16px;margin:16px 0;">
                <p style="font-size:13px;margin:0 0 4px 0;">
                    <span class="dashicons dashicons-shield-alt" style="color:#2271b1;vertical-align:text-bottom;margin-right:4px;"></span>
                    <strong><?php esc_html_e('Multi-Form Auto-Defense Active:', 'world-inspire-email-validation-for-fluent-forms'); ?></strong>
                    <?php printf(esc_html__('Detected %d active form / account engine(s) on this site: %s.', 'world-inspire-email-validation-for-fluent-forms'), count($detected_active), '<strong>' . esc_html(implode(', ', $active_labels)) . '</strong>'); ?>
                </p>
                <p style="font-size:12px;color:#50575e;margin:0;">
                    <?php esc_html_e('Submissions from all detected forms are automatically guarded against disposable mailboxes, syntax defects, and domain typos. You can toggle specific engines in the Integrations panel below.', 'world-inspire-email-validation-for-fluent-forms'); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Stat Badges -->
        <div style="display:flex;gap:16px;margin:20px 0;flex-wrap:wrap;">
            <div class="card" style="margin:0;flex:1;min-width:180px;padding:16px;border-left:4px solid <?php echo $config['enabled'] ? '#00a32a' : '#d63638'; ?>;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:600;">Engine Status</div>
                <div style="font-size:24px;font-weight:700;margin-top:6px;color:<?php echo $config['enabled'] ? '#00a32a' : '#d63638'; ?>;">
                    <?php echo $config['enabled'] ? esc_html__('● ACTIVE', 'world-inspire-email-validation-for-fluent-forms') : esc_html__('○ DISABLED', 'world-inspire-email-validation-for-fluent-forms'); ?>
                </div>
                <div style="font-size:12px;color:#646970;margin-top:4px;">Multi-layer email filter</div>
            </div>

            <div class="card" style="margin:0;flex:1;min-width:180px;padding:16px;border-left:4px solid #135e96;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:600;">Form Integrations</div>
                <div style="font-size:24px;font-weight:700;margin-top:6px;color:#1d2327;">
                    <?php echo esc_html(count($detected_active)); ?> <span style="font-size:14px;font-weight:400;color:#646970;">/ <?php echo esc_html(count($supported_integrations)); ?> Active</span>
                </div>
                <div style="font-size:12px;color:#646970;margin-top:4px;">Universal multi-form defense</div>
            </div>

            <div class="card" style="margin:0;flex:1;min-width:180px;padding:16px;border-left:4px solid #2271b1;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:600;">Disposable Blacklist</div>
                <div style="font-size:24px;font-weight:700;margin-top:6px;color:#1d2327;">
                    <?php echo esc_html(number_format(count($disposable_map))); ?> <span style="font-size:14px;font-weight:400;color:#646970;"><?php esc_html_e('domains', 'world-inspire-email-validation-for-fluent-forms'); ?></span>
                </div>
                <div style="font-size:12px;color:#646970;margin-top:4px;">Offline bundle: <?php echo esc_html($file_mtime); ?></div>
            </div>

            <div class="card" style="margin:0;flex:1;min-width:180px;padding:16px;border-left:4px solid #f0b849;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:600;">Blocked Attempts</div>
                <div style="font-size:24px;font-weight:700;margin-top:6px;color:#1d2327;">
                    <?php echo esc_html(number_format(count($logs))); ?> <span style="font-size:14px;font-weight:400;color:#646970;"><?php esc_html_e('events', 'world-inspire-email-validation-for-fluent-forms'); ?></span>
                </div>
                <div style="font-size:12px;color:#646970;margin-top:4px;">Prevented hard bounces</div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div style="display:flex;gap:24px;flex-wrap:wrap;">
            <!-- Left: Settings Form -->
            <div style="flex:2;min-width:350px;">
                <div class="card" style="max-width:none;padding:20px;">
                    <h2>Active Defense Settings</h2>
                    <form method="post" action="">
                        <?php wp_nonce_field('gm_ff_eg_action_nonce', 'gm_ff_eg_nonce'); ?>
                        <input type="hidden" name="gm_ff_eg_action" value="save_settings">

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">Master Switch</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="gm_ff_eg_enabled" value="1" <?php checked($config['enabled']); ?>>
                                        <strong>Enable Email Validation Protection</strong>
                                    </label>
                                    <p class="description">When disabled, submissions pass through without email domain verification.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Defense Layers</th>
                                <td>
                                    <fieldset>
                                        <label style="display:block;margin-bottom:8px;">
                                            <input type="checkbox" name="gm_ff_eg_check_syntax" value="1" <?php checked($config['checks']['syntax']); ?>>
                                            <strong>RFC 5322 Syntax Check</strong> (Rejects malformed emails)
                                        </label>
                                        <label style="display:block;margin-bottom:8px;">
                                            <input type="checkbox" name="gm_ff_eg_check_mx" value="1" <?php checked($config['checks']['mx']); ?>>
                                            <strong>DNS MX Records Check</strong> (Verifies domain can receive mail, cached 24h)
                                        </label>
                                        <label style="display:block;margin-bottom:8px;">
                                            <input type="checkbox" name="gm_ff_eg_check_disposable" value="1" <?php checked($config['checks']['disposable']); ?>>
                                            <strong>Disposable Email Blacklist</strong> (Blocks Mailinator, TempMail, 8,700+ domains)
                                        </label>
                                        <label style="display:block;margin-bottom:8px;">
                                            <input type="checkbox" name="gm_ff_eg_check_typo" value="1" <?php checked($config['checks']['typo']); ?>>
                                            <strong>Typo Auto-Correction</strong> (Detects gamil.com, hotmial.com, etc.)
                                        </label>
                                        <label style="display:block;margin-bottom:8px;">
                                            <input type="checkbox" name="gm_ff_eg_check_blocked_domains" value="1" <?php checked($config['checks']['blocked_domains']); ?>>
                                            <strong>Custom Blocked Domains</strong> (Filters custom denylist)
                                        </label>
                                        <label style="display:block;">
                                            <input type="checkbox" name="gm_ff_eg_check_role" value="1" <?php checked($config['checks']['role']); ?>>
                                            <strong>Role-Based Account Filter</strong> (Blocks admin@, support@, info@)
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Form Integrations</th>
                                <td>
                                    <p class="description" style="margin-top:0;margin-bottom:10px;">Toggle email validation protection per form plugin or WordPress core registration. Detected engines on your site are automatically safeguarded.</p>
                                    <div style="border:1px solid #c3c4c7;border-radius:4px;overflow:hidden;background:#fff;max-width:750px;">
                                        <table class="widefat striped" style="border:none;margin:0;">
                                            <thead>
                                                <tr>
                                                    <th style="width:40px;text-align:center;">Active</th>
                                                    <th style="width:160px;">Engine</th>
                                                    <th style="width:120px;">Detection</th>
                                                    <th>Protection Scope</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($supported_integrations as $int_key => $int_data): 
                                                    $is_enabled = !empty($config['integrations'][$int_key]);
                                                ?>
                                                <tr>
                                                    <td style="text-align:center;vertical-align:middle;">
                                                        <input type="checkbox" name="gm_ff_eg_integration_<?php echo esc_attr($int_key); ?>" value="1" <?php checked($is_enabled); ?>>
                                                    </td>
                                                    <td style="vertical-align:middle;"><strong><?php echo esc_html($int_data['label']); ?></strong></td>
                                                    <td style="vertical-align:middle;">
                                                        <?php if (!empty($int_data['is_active'])): ?>
                                                            <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#d4edda;color:#155724;">
                                                                ● DETECTED
                                                            </span>
                                                        <?php else: ?>
                                                            <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:500;background:#f0f0f1;color:#646970;">
                                                                ○ Standby
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="font-size:12px;color:#50575e;vertical-align:middle;"><?php echo esc_html($int_data['desc']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="gm_ff_eg_blocked_domains">Custom Blocked Domains</label></th>
                                <td>
                                    <textarea name="gm_ff_eg_blocked_domains" id="gm_ff_eg_blocked_domains" rows="4" class="large-text code"><?php echo esc_textarea(implode("\n", (array)$config['blocked_domains'])); ?></textarea>
                                    <p class="description">Enter one domain per line (e.g. <code>search-glintmuse.com</code>, <code>spamdomain.com</code>).</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="gm_ff_eg_whitelist_domains">Whitelisted Domains</label></th>
                                <td>
                                    <textarea name="gm_ff_eg_whitelist_domains" id="gm_ff_eg_whitelist_domains" rows="2" class="large-text code"><?php echo esc_textarea(implode("\n", (array)$config['whitelist_domains'])); ?></textarea>
                                    <p class="description">Domains that will always bypass checks (e.g. <code>glintmuse.com</code>).</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="gm_ff_eg_cache_ttl">DNS Cache TTL</label></th>
                                <td>
                                    <input type="number" name="gm_ff_eg_cache_ttl" id="gm_ff_eg_cache_ttl" value="<?php echo esc_attr($config['cache_ttl']); ?>" class="small-text"> seconds (default: 86400 = 24 hours)
                                    <p class="description">Caches DNS lookups in WordPress transients to eliminate form submission delay.</p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="submit" class="button button-primary" value="Save Settings">
                        </p>
                    </form>
                </div>
            </div>

            <!-- Right: Tools, Test Sandbox & Upstream Sync -->
            <div style="flex:1;min-width:300px;">
                <!-- Plugin Information Box -->
                <div class="card" style="max-width:none;padding:20px;margin-bottom:20px;">
                    <h3>Plugin Information</h3>
                    <p style="font-size:13px;color:#646970;">Developed by <a href="https://worldinspirelab.com/" target="_blank" rel="noopener">World Inspire LLC</a> for WordPress.org.</p>
                    <p style="font-size:12px;margin:8px 0 0 0;"><strong>Installed Version:</strong> v<?php echo esc_html(GM_FF_EMAIL_GUARD_VERSION); ?></p>
                    <p style="font-size:12px;margin:4px 0 0 0;"><strong>Updates:</strong> Managed automatically via WordPress Core</p>
                </div>

                <!-- Database Info Box -->
                <div class="card" style="max-width:none;padding:20px;margin-bottom:20px;">
                    <h3>Disposable Domain Database</h3>
                    <p style="font-size:13px;color:#646970;">Protected by 8,700+ bundled temporary disposable domains from the canonical <code>disposable-email-domains</code> repository.</p>
                    <p style="font-size:12px;margin:8px 0 0 0;"><strong>Operation Mode:</strong> 100% Offline &amp; In-Memory</p>
                    <p style="font-size:12px;margin:4px 0 0 0;"><strong>Dataset File:</strong> <code>data/disposable_domains.json</code></p>
                </div>

                <!-- Interactive Email Tester Sandbox -->
                <div class="card" style="max-width:none;padding:20px;">
                    <h3>Test an Email Address</h3>
                    <p style="font-size:13px;color:#646970;">Simulate real-time FluentForms email guard evaluation instantly.</p>
                    <div style="margin-bottom:12px;">
                        <input type="email" id="gm_ff_eg_test_email" placeholder="e.g. test@example.com" style="width:100%;margin-bottom:8px;">
                        <button type="button" id="gm_ff_eg_btn_test" class="button button-primary" style="width:100%;">
                            <span class="dashicons dashicons-search" style="vertical-align:middle;"></span> Run Validation Test
                        </button>
                    </div>
                    <div id="gm_ff_eg_test_result" style="display:none;padding:12px;border-radius:4px;font-size:13px;margin-top:10px;"></div>
                    <script>
                    document.getElementById('gm_ff_eg_btn_test').addEventListener('click', function() {
                        var email = document.getElementById('gm_ff_eg_test_email').value.trim();
                        var resBox = document.getElementById('gm_ff_eg_test_result');
                        if (!email) return;
                        resBox.style.display = 'block';
                        resBox.style.background = '#f0f0f1';
                        resBox.innerHTML = 'Testing...';

                        fetch('<?php echo esc_url_raw(rest_url('fluentform-email-guard/v1/test')); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>'
                            },
                            body: JSON.stringify({email: email})
                        })
                        .then(function(r){ return r.json(); })
                        .then(function(data){
                            if (data.valid) {
                                resBox.style.background = '#d4edda';
                                resBox.style.color = '#155724';
                                resBox.style.border = '1px solid #c3e6cb';
                                resBox.innerHTML = '<strong>[PASSED]</strong> Valid & Deliverable mailbox.';
                            } else {
                                resBox.style.background = '#f8d7da';
                                resBox.style.color = '#721c24';
                                resBox.style.border = '1px solid #f5c6cb';
                                var msg = '<strong>[BLOCKED]</strong> Reason: ' + data.reason;
                                if (data.error) msg += '<br>Error: ' + data.error;
                                if (data.suggestion) msg += '<br>Suggestion: <code>' + data.suggestion + '</code>';
                                resBox.innerHTML = msg;
                            }
                        })
                        .catch(function(err){
                            resBox.style.background = '#f8d7da';
                            resBox.innerHTML = 'Test error: ' + err;
                        });
                    });
                    </script>
                </div>
            </div>
        </div>

        <!-- Telemetry Audit Table -->
        <div class="card" style="max-width:none;padding:20px;margin-top:24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <h2 style="margin:0;">Recent Blocked Telemetry Audit (Last 50 Entries)</h2>
                <form method="post" action="" onsubmit="return confirm('<?php echo esc_js(esc_html__('Clear all audit logs?', 'world-inspire-email-validation-for-fluent-forms')); ?>');">
                    <?php wp_nonce_field('gm_ff_eg_action_nonce', 'gm_ff_eg_nonce'); ?>
                    <input type="hidden" name="gm_ff_eg_action" value="clear_logs">
                    <button type="submit" class="button button-link-delete">Clear Audit Logs</button>
                </form>
            </div>
            <table class="wp-list-table widefat fixed striped" style="font-size:13px;">
                <thead>
                    <tr>
                        <th style="width:160px;">Timestamp</th>
                        <th>Masked Email</th>
                        <th>Domain</th>
                        <th>Blocked Reason</th>
                        <th style="width:80px;">Form ID</th>
                        <th style="width:120px;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#646970;">No blocked events recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($logs, 0, 50) as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log['time'] ?? ''); ?></td>
                                <td><code><?php echo esc_html($log['email'] ?? ''); ?></code></td>
                                <td><?php echo esc_html($log['domain'] ?? ''); ?></td>
                                <td><span class="badge" style="background:#fce8e6;color:#c5221f;padding:2px 6px;border-radius:4px;font-weight:600;"><?php echo esc_html($log['reason'] ?? ''); ?></span></td>
                                <td>#<?php echo esc_html($log['form_id'] ?? 0); ?></td>
                                <td><?php echo esc_html($log['ip'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * REST API Endpoints.
 * Exposes canonical namespace: fluentform-email-guard/v1
 * And backwards-compatible namespace: glintmuse/v1
 */
add_action('rest_api_init', function () {
    $namespaces = ['fluentform-email-guard/v1', 'glintmuse/v1'];

    $test_handler = [
        'methods' => 'POST',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'callback' => function (\WP_REST_Request $request) {
            $email = sanitize_text_field($request->get_param('email'));
            $form_id = (int)$request->get_param('form_id');
            if (!$email) {
                return new \WP_Error('missing_email', 'Please provide an email to test', ['status' => 400]);
            }
            $result = gm_ff_email_guard_check($email, $form_id);
            return rest_ensure_response($result);
        }
    ];

    $public_status_handler = [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function () {
            $config = gm_ff_email_guard_get_config();
            return rest_ensure_response([
                'status' => 'ok',
                'enabled' => (bool)$config['enabled'],
                'version' => GM_FF_EMAIL_GUARD_VERSION,
            ]);
        }
    ];

    $admin_status_handler = [
        'methods' => 'GET',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'callback' => function () {
            $config = gm_ff_email_guard_get_config();
            $logs = get_option('fluentform_email_guard_logs', []);
            $disposable_map = gm_ff_email_guard_get_disposable_domains();
            $supported_integrations = gm_ff_email_guard_get_supported_integrations();
            return rest_ensure_response([
                'status' => 'ok',
                'enabled' => (bool)$config['enabled'],
                'version' => GM_FF_EMAIL_GUARD_VERSION,
                'disposable_domains_count' => count($disposable_map),
                'blocked_domains' => $config['blocked_domains'],
                'target_forms' => $config['target_forms'],
                'checks' => $config['checks'],
                'integrations' => $config['integrations'],
                'supported_integrations' => $supported_integrations,
                'recent_blocked_logs' => array_slice((array)$logs, 0, 10),
                'total_recent_blocks' => count((array)$logs)
            ]);
        }
    ];

    foreach ($namespaces as $ns) {
        register_rest_route($ns, '/test', $test_handler);
        register_rest_route($ns, '/email-guard/test', $test_handler);
        register_rest_route($ns, '/status', $public_status_handler);
        register_rest_route($ns, '/email-guard/status', $public_status_handler);
        register_rest_route($ns, '/admin/status', $admin_status_handler);
        register_rest_route($ns, '/email-guard/admin/status', $admin_status_handler);
    }
});

register_deactivation_hook(__FILE__, function () {
    $timestamp = wp_next_scheduled('gm_ff_email_guard_weekly_sync');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'gm_ff_email_guard_weekly_sync');
    }
});

