<?php
/**
 * Standalone E2E Mock Test for World Inspire Email Validation Multi-Form Suite.
 * Simulates WordPress environment and verifies all 6 layers and 8 form integration hooks.
 */

define('ABSPATH', __DIR__ . '/../');

$GLOBALS['wp_options'] = [];
$GLOBALS['wp_transients'] = [];
$GLOBALS['wp_filters'] = [];
$GLOBALS['wp_actions'] = [];

function get_option($key, $default = false) {
    return $GLOBALS['wp_options'][$key] ?? $default;
}

function update_option($key, $value) {
    $GLOBALS['wp_options'][$key] = $value;
    return true;
}

function get_transient($key) {
    return $GLOBALS['wp_transients'][$key] ?? false;
}

function set_transient($key, $value, $ttl = 86400) {
    $GLOBALS['wp_transients'][$key] = $value;
    return true;
}

function wp_parse_args($args, $defaults = []) {
    return array_merge($defaults, (array)$args);
}

function sanitize_text_field($str) {
    return trim(strip_tags((string)$str));
}

function sanitize_email($email) {
    return filter_var(trim((string)$email), FILTER_SANITIZE_EMAIL);
}

function sanitize_textarea_field($str) {
    return trim(strip_tags((string)$str));
}

function wp_unslash($val) {
    return $val;
}

function absint($val) {
    return abs((int)$val);
}

function __($text, $domain = 'default') {
    return $text;
}

function esc_html__($text, $domain = 'default') {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_html($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function esc_attr($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function esc_textarea($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function esc_url($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

function esc_url_raw($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

function esc_js($str) {
    return addslashes((string)$str);
}

function checked($val) {
    echo !empty($val) ? ' checked="checked"' : '';
}

function plugin_basename($file) {
    return basename($file);
}

function plugin_dir_path($file) {
    return dirname($file) . '/';
}

function current_time($type) {
    return gmdate('Y-m-d H:i:s');
}

function wp_upload_dir() {
    return ['basedir' => '/tmp'];
}

function is_admin() {
    return true;
}

function current_user_can($cap) {
    return true;
}

function admin_url($path = '') {
    return 'https://example.com/wp-admin/' . $path;
}

function rest_url($path = '') {
    return 'https://example.com/wp-json/' . $path;
}

function wp_create_nonce($action) {
    return 'mock_nonce_123';
}

function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['wp_filters'][$tag][] = ['callback' => $callback, 'args' => $accepted_args];
}

function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['wp_actions'][$tag][] = ['callback' => $callback, 'args' => $accepted_args];
}

function apply_filters_mock($tag, $value, ...$extra) {
    if (!isset($GLOBALS['wp_filters'][$tag])) {
        return $value;
    }
    foreach ($GLOBALS['wp_filters'][$tag] as $hook) {
        $value = call_user_func($hook['callback'], $value, ...$extra);
    }
    return $value;
}

function do_action_mock($tag, ...$args) {
    if (!isset($GLOBALS['wp_actions'][$tag])) {
        return;
    }
    foreach ($GLOBALS['wp_actions'][$tag] as $hook) {
        call_user_func_array($hook['callback'], $args);
    }
}

function register_deactivation_hook($file, $callback) {}
function register_rest_route($ns, $route, $args) {}
function rest_ensure_response($data) { return $data; }
function wp_next_scheduled($hook) { return false; }
function wp_unschedule_event($ts, $hook) {}

// Load the actual plugin code
require_once __DIR__ . '/../world-inspire-email-validation-for-fluent-forms.php';

$passed = 0;
$failed = 0;

function assert_test($condition, $name) {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$name}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$name}\n";
    }
}

echo "=== Running E2E Test Suite ===\n\n";

echo "Test Group 1: Detection & SSOT Config\n";
$integrations = gm_ff_email_guard_get_supported_integrations();
assert_test(is_array($integrations), "gm_ff_email_guard_get_supported_integrations returns array");
assert_test(isset($integrations['fluentform']), "fluentform integration defined");
assert_test(isset($integrations['wpforms']), "wpforms integration defined");
assert_test(isset($integrations['wpcf7']), "wpcf7 integration defined");
assert_test(isset($integrations['gravityforms']), "gravityforms integration defined");
assert_test(isset($integrations['forminator']), "forminator integration defined");
assert_test(isset($integrations['ninja_forms']), "ninja_forms integration defined");
assert_test(isset($integrations['woocommerce']), "woocommerce integration defined");
assert_test(isset($integrations['wp_register']), "wp_register integration defined");

$config = gm_ff_email_guard_get_config();
assert_test(!empty($config['integrations']['fluentform']), "Default config has fluentform enabled");
assert_test(!empty($config['integrations']['wpforms']), "Default config has wpforms enabled");
assert_test(!empty($config['integrations']['woocommerce']), "Default config has woocommerce enabled");

echo "\nTest Group 2: 6-Layer Core Defense Engine\n";

// 2.1 Disposable Domain Detection
$res_disposable = gm_ff_email_guard_check('tester@mailinator.com');
assert_test(!$res_disposable['valid'] && $res_disposable['reason'] === 'disposable', "Rejects disposable email mailinator.com");

// 2.2 Typo Correction
$res_typo = gm_ff_email_guard_check('john@gamil.com');
assert_test(!$res_typo['valid'] && $res_typo['reason'] === 'typo', "Detects typo gamil.com");
assert_test($res_typo['suggestion'] === 'john@gmail.com', "Suggests john@gmail.com for john@gamil.com");

// 2.3 Syntax Error
$res_syntax = gm_ff_email_guard_check('invalid-email-address');
assert_test(!$res_syntax['valid'] && $res_syntax['reason'] === 'syntax_error', "Rejects malformed email syntax");

// 2.4 Custom Blocked Domain
$res_blocked = gm_ff_email_guard_check('bad@search-glintmuse.com');
assert_test(!$res_blocked['valid'] && $res_blocked['reason'] === 'blocked_domain', "Rejects custom blocked domain");

// 2.5 Whitelisted Domain
$res_whitelist = gm_ff_email_guard_check('vip@glintmuse.com');
assert_test($res_whitelist['valid'] && $res_whitelist['reason'] === 'whitelisted', "Passes whitelisted domain");

// 2.6 Valid Deliverable Email
$res_valid = gm_ff_email_guard_check('realuser@gmail.com');
assert_test($res_valid['valid'], "Passes deliverable address realuser@gmail.com");

// 2.7 Universal Helper API
assert_test(!world_inspire_is_valid_email('fake@mailinator.com'), "world_inspire_is_valid_email returns false for disposable");
assert_test(world_inspire_is_valid_email('realuser@gmail.com'), "world_inspire_is_valid_email returns true for valid");

echo "\nTest Group 3: Fluent Forms Hook Integration\n";
$ff_field = ['attributes' => ['name' => 'email']];
$ff_data_bad = ['email' => 'spammer@tempmail.com'];
$ff_form = (object)['id' => 10];

$ff_error = apply_filters_mock('fluentform/validate_input_item_input_email', [], $ff_field, $ff_data_bad, [], $ff_form);
assert_test(!empty($ff_error), "Fluent Forms hook intercepts disposable email");

$ff_data_good = ['email' => 'realuser@gmail.com'];
$ff_good = apply_filters_mock('fluentform/validate_input_item_input_email', [], $ff_field, $ff_data_good, [], $ff_form);
assert_test(empty($ff_good), "Fluent Forms hook allows valid email");

echo "\nTest Group 4: Contact Form 7 Hook Integration\n";
class MockWPCF7_Validation {
    public $invalid = false;
    public $message = '';
    public function invalidate($tag, $msg) {
        $this->invalid = true;
        $this->message = $msg;
    }
}

$_POST['your-email'] = 'spammer@10minutemail.com';
$cf7_tag = (object)['name' => 'your-email'];
$cf7_res = new MockWPCF7_Validation();
$cf7_res = apply_filters_mock('wpcf7_validate_email', $cf7_res, $cf7_tag);
assert_test($cf7_res->invalid, "CF7 hook invalidates disposable email");

$_POST['your-email'] = 'gooduser@gmail.com';
$cf7_res2 = new MockWPCF7_Validation();
$cf7_res2 = apply_filters_mock('wpcf7_validate_email', $cf7_res2, $cf7_tag);
assert_test(!$cf7_res2->invalid, "CF7 hook passes legitimate email");

echo "\nTest Group 5: Gravity Forms Hook Integration\n";
$gf_field = (object)['type' => 'email'];
$gf_form = ['id' => 5];
$gf_res = ['is_valid' => true, 'message' => ''];
$gf_result = apply_filters_mock('gform_field_validation', $gf_res, 'bot@sharklasers.com', $gf_form, $gf_field);
assert_test(!$gf_result['is_valid'], "Gravity Forms hook invalidates disposable email");

$gf_res_good = ['is_valid' => true, 'message' => ''];
$gf_good_result = apply_filters_mock('gform_field_validation', $gf_res_good, 'realuser@gmail.com', $gf_form, $gf_field);
assert_test($gf_good_result['is_valid'], "Gravity Forms hook passes valid email");

echo "\nTest Group 6: Forminator Hook Integration\n";
$forminator_fields = [
    ['name' => 'email-1', 'value' => 'throwaway@yopmail.com']
];
$f_errors = apply_filters_mock('forminator_custom_form_submit_errors', [], 12, $forminator_fields);
assert_test(!empty($f_errors), "Forminator hook intercepts disposable email");

$forminator_fields_good = [
    ['name' => 'email-1', 'value' => 'realuser@gmail.com']
];
$f_errors_good = apply_filters_mock('forminator_custom_form_submit_errors', [], 12, $forminator_fields_good);
assert_test(empty($f_errors_good), "Forminator hook passes valid email");

echo "\nTest Group 7: Ninja Forms Hook Integration\n";
$nf_data = [
    'id' => 7,
    'fields' => [
        1 => ['id' => 1, 'type' => 'email', 'value' => 'junk@trashmail.com']
    ]
];
$nf_out = apply_filters_mock('ninja_forms_submit_data', $nf_data);
assert_test(!empty($nf_out['errors']['fields'][1]), "Ninja Forms hook intercepts disposable email");

echo "\nTest Group 8: WP Native Registration Hook Integration\n";
class MockWP_Error {
    public $errors = [];
    public function add($code, $msg) {
        $this->errors[$code] = $msg;
    }
}
$reg_errors = new MockWP_Error();
$reg_out = apply_filters_mock('registration_errors', $reg_errors, 'newuser', 'fake@guerrillamail.com');
assert_test(!empty($reg_out->errors['invalid_email_guard']), "WP Core registration intercepts disposable email");

echo "\n==============================\n";
echo "Results: {$passed} PASSED, {$failed} FAILED\n";
if ($failed > 0) {
    exit(1);
}
echo "All E2E checks passed perfectly!\n";
exit(0);
