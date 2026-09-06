<?php
/**
 * Plugin Name: Fluent Forms Email Guard & Anti-Bounce
 * Plugin URI: https://github.com/vecyang1/fluentform-email-guard
 * Description: Production-grade multi-layer real-time email defense for Fluent Forms. Blocks disposable/temporary domains (8,700+ domains), verifies live DNS MX records with 24h caching, auto-suggests typo corrections (e.g. gamil.com -> gmail.com), and prevents hard bounces in FluentCRM funnels. Includes GitHub Releases auto-updater.
 * Version: 1.1.3
 * Author: GlintMuse Engineering & Vec
 * Author URI: https://glintmuse.com/
 * License: GPL-2.0-or-later
 * Text Domain: fluentform-email-guard
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GM_FF_EMAIL_GUARD_VERSION', '1.1.3');
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
        'github_token' => '',
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
    if (!is_array($config)) {
        $config = gm_ff_email_guard_default_config();
        update_option('fluentform_email_guard_config', $config);
    }
    return wp_parse_args($config, gm_ff_email_guard_default_config());
}

function gm_ff_email_guard_update_config($new_config) {
    return update_option('fluentform_email_guard_config', $new_config);
}

/**
 * Retrieve GitHub Token with Least-Privilege & Constant Priority.
 * Priority: FLUENTFORM_EMAIL_GUARD_GH_TOKEN constant (wp-config.php) > wp_options config['github_token']
 */
function gm_ff_email_guard_get_github_token() {
    if (defined('FLUENTFORM_EMAIL_GUARD_GH_TOKEN') && !empty(FLUENTFORM_EMAIL_GUARD_GH_TOKEN)) {
        return trim((string)FLUENTFORM_EMAIL_GUARD_GH_TOKEN);
    }
    $config = gm_ff_email_guard_get_config();
    return !empty($config['github_token']) ? trim((string)$config['github_token']) : '';
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

    $upload_dir = wp_upload_dir();
    $cache_file = $upload_dir['basedir'] . '/fluentform-email-guard/disposable_domains.json';
    $domains = [];

    if (file_exists($cache_file)) {
        $raw = file_get_contents($cache_file);
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && !empty($decoded)) {
            $domains = $decoded;
        }
    }

    $all_domains = array_unique(array_merge(gm_ff_email_guard_builtin_disposable_domains(), (array)$domains));
    $domain_map = array_flip($all_domains);
    return $domain_map;
}

function gm_ff_email_guard_sync_disposable_list() {
    $url = 'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/master/disposable_email_blocklist.conf';
    $response = wp_remote_get($url, ['timeout' => 12]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return [
            'success' => false,
            'error' => is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response)
        ];
    }

    $body = wp_remote_retrieve_body($response);
    $lines = explode("\n", trim($body));
    $domains = [];
    foreach ($lines as $line) {
        $d = strtolower(trim($line));
        if ($d && $d[0] !== '#' && strpos($d, '.') !== false) {
            $domains[] = $d;
        }
    }

    if (count($domains) < 100) {
        return ['success' => false, 'error' => 'Fetched list too small or invalid'];
    }

    $upload_dir = wp_upload_dir();
    $dir = $upload_dir['basedir'] . '/fluentform-email-guard';
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }
    $file = $dir . '/disposable_domains.json';
    file_put_contents($file, json_encode(array_values(array_unique($domains))));

    return [
        'success' => true,
        'count' => count($domains),
        'file' => $file
    ];
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

    $entry = [
        'time' => current_time('mysql'),
        'email' => $masked_email,
        'domain' => $domain,
        'reason' => $reason,
        'form_id' => (int)$form_id,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
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
 * Hook into Fluent Forms email validation filter.
 */
add_filter('fluentform/validate_input_item_input_email', function ($error, $field, $formData, $fields, $form) {
    if (!empty($error)) {
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

/**
 * Settings Link on Plugins Page.
 */
add_filter('plugin_action_links_' . GM_FF_EMAIL_GUARD_BASENAME, function ($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=fluentform-email-guard') . '">Settings</a>';
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
        'Email Guard - Spam & Bounce Protection',
        'Email Guard',
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
        wp_die(__('You do not have permission to access this page.'));
    }

    $message = '';
    $message_type = 'success';

    // Handle POST Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gm_ff_eg_action'])) {
        if (!check_admin_referer('gm_ff_eg_action_nonce', 'gm_ff_eg_nonce')) {
            wp_die('Security check failed.');
        }

        $action = sanitize_text_field($_POST['gm_ff_eg_action']);

        if ($action === 'save_settings') {
            $current_config = gm_ff_email_guard_get_config();
            $current_config['enabled'] = !empty($_POST['gm_ff_eg_enabled']);
            $current_config['checks']['syntax'] = !empty($_POST['gm_ff_eg_check_syntax']);
            $current_config['checks']['mx'] = !empty($_POST['gm_ff_eg_check_mx']);
            $current_config['checks']['disposable'] = !empty($_POST['gm_ff_eg_check_disposable']);
            $current_config['checks']['typo'] = !empty($_POST['gm_ff_eg_check_typo']);
            $current_config['checks']['blocked_domains'] = !empty($_POST['gm_ff_eg_check_blocked_domains']);
            $current_config['checks']['role'] = !empty($_POST['gm_ff_eg_check_role']);

            // GitHub Token
            if (isset($_POST['gm_ff_eg_github_token'])) {
                $submitted_token = trim((string)sanitize_text_field($_POST['gm_ff_eg_github_token']));
                if ($submitted_token === '__CLEAR__') {
                    $current_config['github_token'] = '';
                } elseif ($submitted_token !== '' && strpos($submitted_token, '***') === false) {
                    $current_config['github_token'] = $submitted_token;
                }
            }

            // Blocked domains
            $raw_blocked = sanitize_textarea_field($_POST['gm_ff_eg_blocked_domains'] ?? '');
            $blocked_lines = array_filter(array_map('trim', explode("\n", strtolower($raw_blocked))));
            $current_config['blocked_domains'] = array_values(array_unique($blocked_lines));

            // Whitelist
            $raw_white = sanitize_textarea_field($_POST['gm_ff_eg_whitelist_domains'] ?? '');
            $white_lines = array_filter(array_map('trim', explode("\n", strtolower($raw_white))));
            $current_config['whitelist_domains'] = array_values(array_unique($white_lines));

            // TTL
            $current_config['cache_ttl'] = max(300, (int)($_POST['gm_ff_eg_cache_ttl'] ?? 86400));

            gm_ff_email_guard_update_config($current_config);
            $message = 'Email Guard configuration successfully updated.';
        } elseif ($action === 'sync_disposable') {
            $res = gm_ff_email_guard_sync_disposable_list();
            if (!empty($res['success'])) {
                $message = sprintf('Successfully synced %d disposable domains from GitHub upstream!', (int)$res['count']);
            } else {
                $message = 'Sync failed: ' . esc_html($res['error'] ?? 'Unknown error');
                $message_type = 'error';
            }
        } elseif ($action === 'clear_logs') {
            update_option('fluentform_email_guard_logs', []);
            $message = 'Security audit telemetry logs cleared.';
        } elseif ($action === 'check_updates') {
            delete_site_transient('update_plugins');
            $update_info = gm_ff_email_guard_check_github_update(true);
            if (!empty($update_info['new_version']) && version_compare($update_info['new_version'], GM_FF_EMAIL_GUARD_VERSION, '>')) {
                $message = sprintf('New version %s is available! Check Plugins page to update.', esc_html($update_info['new_version']));
            } else {
                $message = sprintf('You are running the latest version (%s).', GM_FF_EMAIL_GUARD_VERSION);
            }
        }
    }

    $config = gm_ff_email_guard_get_config();
    $disposable_map = gm_ff_email_guard_get_disposable_domains();
    $logs = (array)get_option('fluentform_email_guard_logs', []);
    $upload_dir = wp_upload_dir();
    $cache_file = $upload_dir['basedir'] . '/fluentform-email-guard/disposable_domains.json';
    $file_mtime = file_exists($cache_file) ? date('Y-m-d H:i:s T', filemtime($cache_file)) : 'Never';
    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            <span class="dashicons dashicons-shield-alt" style="font-size:32px;width:32px;height:32px;color:#2271b1;"></span>
            Fluent Forms Email Guard & Anti-Bounce
            <span style="font-size:12px;background:#2271b1;color:#fff;padding:2px 8px;border-radius:12px;">v<?php echo esc_html(GM_FF_EMAIL_GUARD_VERSION); ?></span>
        </h1>
        <p class="description">Production-grade defense against disposable emails, non-existent MX domains, domain typos, and bounce-inducing submissions. Zero Ghost Logic & Single Source of Truth.</p>

        <?php if ($message): ?>
            <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>

        <!-- Stat Badges -->
        <div style="display:flex;gap:16px;margin:20px 0;flex-wrap:wrap;">
            <div class="card" style="margin:0;flex:1;min-width:200px;padding:16px;border-left:4px solid <?php echo $config['enabled'] ? '#00a32a' : '#d63638'; ?>;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:600;">Engine Status</div>
                <div style="font-size:24px;font-weight:700;margin-top:6px;color:<?php echo $config['enabled'] ? '#00a32a' : '#d63638'; ?>;">
                    <?php echo $config['enabled'] ? '● ACTIVE' : '○ DISABLED'; ?>
                </div>
                <div style="font-size:12px;color:#646970;margin-top:4px;">Hooked on <code>fluentform/validate</code></div>
            </div>

            <div class="card" style="margin:0;flex:1;min-width:200px;padding:16px;border-left:4px solid #2271b1;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:600;">Disposable Blacklist</div>
                <div style="font-size:24px;font-weight:700;margin-top:6px;color:#1d2327;">
                    <?php echo number_format(count($disposable_map)); ?> <span style="font-size:14px;font-weight:400;color:#646970;">domains</span>
                </div>
                <div style="font-size:12px;color:#646970;margin-top:4px;">Last sync: <?php echo esc_html($file_mtime); ?></div>
            </div>

            <div class="card" style="margin:0;flex:1;min-width:200px;padding:16px;border-left:4px solid #f0b849;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:600;">Blocked Attempts</div>
                <div style="font-size:24px;font-weight:700;margin-top:6px;color:#1d2327;">
                    <?php echo number_format(count($logs)); ?> <span style="font-size:14px;font-weight:400;color:#646970;">events</span>
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
                                        <strong>Enable Email Guard Protection</strong>
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
                            <tr>
                                <th scope="row"><label for="gm_ff_eg_github_token">GitHub Access Token</label></th>
                                <td>
                                    <?php if (defined('FLUENTFORM_EMAIL_GUARD_GH_TOKEN') && !empty(FLUENTFORM_EMAIL_GUARD_GH_TOKEN)): ?>
                                        <p style="color:#00a32a;font-weight:600;margin:0 0 6px 0;">
                                            <span class="dashicons dashicons-lock" style="vertical-align:middle;"></span>
                                            Configured via wp-config.php constant (Hardened File-Level Isolation)
                                        </p>
                                        <code><?php 
                                            $c_tok = (string)FLUENTFORM_EMAIL_GUARD_GH_TOKEN;
                                            echo esc_html(strlen($c_tok) > 12 ? substr($c_tok, 0, 10) . '...' . substr($c_tok, -4) : '********'); 
                                        ?></code>
                                        <p class="description">Constant overrides database option. Highly secure against database/SQL injection leaks.</p>
                                    <?php else: 
                                        $token_val = $config['github_token'] ?? '';
                                        $display_val = !empty($token_val) && strlen($token_val) > 12 
                                            ? substr($token_val, 0, 10) . '...' . substr($token_val, -4) 
                                            : (!empty($token_val) ? '********' : '');
                                    ?>
                                        <input type="password" name="gm_ff_eg_github_token" id="gm_ff_eg_github_token" value="<?php echo esc_attr($token_val); ?>" class="regular-text" autocomplete="new-password" placeholder="github_pat_...">
                                        <?php if (!empty($display_val)): ?>
                                            <p class="description" style="color:#2271b1;margin-top:4px;">
                                                <span class="dashicons dashicons-yes-alt" style="vertical-align:middle;font-size:16px;"></span> Active token in DB: <code><?php echo esc_html($display_val); ?></code> (Enter <code>__CLEAR__</code> to remove)
                                            </p>
                                        <?php endif; ?>
                                        <p class="description">
                                            <strong>Least Privilege Standard:</strong> Use a GitHub <em>Fine-Grained Personal Access Token</em> scoped strictly to <code><?php echo esc_html(GM_FF_EMAIL_GUARD_GITHUB_REPO); ?></code> with <code>Contents: Read-only</code>.
                                        </p>
                                    <?php endif; ?>
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
                <!-- Updater Box -->
                <div class="card" style="max-width:none;padding:20px;margin-bottom:20px;">
                    <h3>GitHub Release Updates</h3>
                    <p style="font-size:13px;color:#646970;">Managed via authoritative GitHub repository <code><?php echo esc_html(GM_FF_EMAIL_GUARD_GITHUB_REPO); ?></code>.</p>
                    <p style="font-size:12px;"><strong>Installed Version:</strong> v<?php echo esc_html(GM_FF_EMAIL_GUARD_VERSION); ?></p>
                    <form method="post" action="">
                        <?php wp_nonce_field('gm_ff_eg_action_nonce', 'gm_ff_eg_nonce'); ?>
                        <input type="hidden" name="gm_ff_eg_action" value="check_updates">
                        <button type="submit" class="button button-secondary" style="width:100%;">
                            <span class="dashicons dashicons-update-alt" style="vertical-align:middle;"></span> Check for Updates Now
                        </button>
                    </form>
                </div>

                <!-- Sync Box -->
                <div class="card" style="max-width:none;padding:20px;margin-bottom:20px;">
                    <h3>Disposable Domain Database</h3>
                    <p style="font-size:13px;color:#646970;">Synchronizes with canonical <code>disposable-email-domains</code> upstream repository on GitHub.</p>
                    <p style="font-size:12px;"><strong>Scheduled Sync:</strong> Weekly via WP-Cron</p>
                    <form method="post" action="">
                        <?php wp_nonce_field('gm_ff_eg_action_nonce', 'gm_ff_eg_nonce'); ?>
                        <input type="hidden" name="gm_ff_eg_action" value="sync_disposable">
                        <button type="submit" class="button button-secondary" style="width:100%;">
                            <span class="dashicons dashicons-update" style="vertical-align:middle;"></span> Sync Latest List Now
                        </button>
                    </form>
                </div>

                <!-- Interactive Email Tester Sandbox -->
                <div class="card" style="max-width:none;padding:20px;">
                    <h3>Test an Email Address</h3>
                    <p style="font-size:13px;color:#646970;">Simulate real-time FluentForms email guard evaluation instantly.</p>
                    <div style="margin-bottom:12px;">
                        <input type="email" id="gm_ff_eg_test_email" placeholder="e.g. test@mailinator.com" style="width:100%;margin-bottom:8px;">
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

                        fetch('/wp-json/fluentform-email-guard/v1/test', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
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
                <form method="post" action="" onsubmit="return confirm('Clear all audit logs?');">
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
        'permission_callback' => '__return_true',
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

    $status_handler = [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function () {
            $config = gm_ff_email_guard_get_config();
            $logs = get_option('fluentform_email_guard_logs', []);
            $disposable_map = gm_ff_email_guard_get_disposable_domains();
            return rest_ensure_response([
                'enabled' => $config['enabled'],
                'version' => GM_FF_EMAIL_GUARD_VERSION,
                'disposable_domains_count' => count($disposable_map),
                'blocked_domains' => $config['blocked_domains'],
                'target_forms' => $config['target_forms'],
                'checks' => $config['checks'],
                'recent_blocked_logs' => array_slice((array)$logs, 0, 10),
                'total_recent_blocks' => count((array)$logs)
            ]);
        }
    ];

    foreach ($namespaces as $ns) {
        register_rest_route($ns, '/test', $test_handler);
        register_rest_route($ns, '/email-guard/test', $test_handler);
        register_rest_route($ns, '/status', $status_handler);
        register_rest_route($ns, '/email-guard/status', $status_handler);
    }
});

/**
 * Weekly Background Refresh of Disposable Email Domains.
 */
if (!wp_next_scheduled('gm_ff_email_guard_weekly_sync')) {
    wp_schedule_event(time() + 3600, 'weekly', 'gm_ff_email_guard_weekly_sync');
}
add_action('gm_ff_email_guard_weekly_sync', 'gm_ff_email_guard_sync_disposable_list');

register_deactivation_hook(__FILE__, function () {
    $timestamp = wp_next_scheduled('gm_ff_email_guard_weekly_sync');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'gm_ff_email_guard_weekly_sync');
    }
});

/**
 * --------------------------------------------------------------------------
 * GitHub Releases Native Plugin Auto-Updater
 * --------------------------------------------------------------------------
 * Enables automatic and 1-click updates from private or public GitHub releases.
 */

function gm_ff_email_guard_check_github_update($force = false) {
    $transient_key = 'ff_eg_github_release_latest';
    $cached = get_transient($transient_key);

    if ($cached !== false && !$force) {
        return $cached;
    }

    $token = gm_ff_email_guard_get_github_token();
    $headers = [
        'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
        'Accept' => 'application/vnd.github.v3+json',
    ];

    if (!empty($token)) {
        $headers['Authorization'] = 'Bearer ' . $token;
    }

    $api_url = 'https://api.github.com/repos/' . GM_FF_EMAIL_GUARD_GITHUB_REPO . '/releases/latest';
    $response = wp_remote_get($api_url, [
        'headers' => $headers,
        'timeout' => 10,
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        set_transient($transient_key, null, 1800); // 30 min backoff on error
        return null;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($body) || empty($body['tag_name'])) {
        return null;
    }

    $tag_version = ltrim($body['tag_name'], 'v');
    $download_url = $body['zipball_url'] ?? '';

    // Prefer uploaded .zip release asset if available
    if (!empty($body['assets']) && is_array($body['assets'])) {
        foreach ($body['assets'] as $asset) {
            if (substr($asset['name'], -4) === '.zip') {
                $download_url = (!empty($token) && !empty($asset['url']))
                    ? $asset['url']
                    : ($asset['browser_download_url'] ?? '');
                break;
            }
        }
    }

    $update_info = [
        'new_version' => $tag_version,
        'url' => $body['html_url'] ?? 'https://github.com/' . GM_FF_EMAIL_GUARD_GITHUB_REPO,
        'package' => $download_url,
        'body' => $body['body'] ?? '',
        'published_at' => $body['published_at'] ?? '',
    ];

    set_transient($transient_key, $update_info, 43200); // cache for 12 hours
    return $update_info;
}

$gm_ff_update_transient_filter = function ($transient) {
    if (!is_object($transient)) {
        $transient = new \stdClass();
    }

    $update = gm_ff_email_guard_check_github_update();
    if ($update && !empty($update['new_version'])) {
        if (version_compare($update['new_version'], GM_FF_EMAIL_GUARD_VERSION, '>')) {
            $obj = new \stdClass();
            $obj->slug = 'fluentform-email-guard';
            $obj->plugin = GM_FF_EMAIL_GUARD_BASENAME;
            $obj->new_version = $update['new_version'];
            $obj->url = $update['url'];
            $obj->package = $update['package'];
            $obj->icons = [
                'default' => 'https://raw.githubusercontent.com/' . GM_FF_EMAIL_GUARD_GITHUB_REPO . '/main/assets/icon.png'
            ];
            $transient->response[GM_FF_EMAIL_GUARD_BASENAME] = $obj;
        }
    }

    return $transient;
};
add_filter('pre_set_site_transient_update_plugins', $gm_ff_update_transient_filter);
add_filter('site_transient_update_plugins', $gm_ff_update_transient_filter);

add_filter('plugins_api', function ($result, $action, $args) {
    if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== 'fluentform-email-guard') {
        return $result;
    }

    $update = gm_ff_email_guard_check_github_update();
    if (!$update) {
        return $result;
    }

    $res = new \stdClass();
    $res->name = 'Fluent Forms Email Guard & Anti-Bounce';
    $res->slug = 'fluentform-email-guard';
    $res->version = $update['new_version'];
    $res->author = '<a href="https://glintmuse.com/">GlintMuse Engineering & Vec</a>';
    $res->homepage = 'https://github.com/' . GM_FF_EMAIL_GUARD_GITHUB_REPO;
    $res->download_link = $update['package'];
    $res->sections = [
        'description' => 'Multi-layer real-time email defense for Fluent Forms.',
        'changelog' => wp_kses_post(nl2br($update['body'] ?? 'No changelog provided.')),
    ];
    return $res;
}, 20, 3);

// Attach private repo auth token to download request when needed
add_filter('http_request_args', function ($parsed_args, $url) {
    if (strpos($url, 'api.github.com/repos/' . GM_FF_EMAIL_GUARD_GITHUB_REPO) !== false ||
        strpos($url, 'github.com/' . GM_FF_EMAIL_GUARD_GITHUB_REPO) !== false) {
        $token = gm_ff_email_guard_get_github_token();
        if (!empty($token)) {
            $parsed_args['headers']['Authorization'] = 'Bearer ' . $token;
            if (strpos($url, '/releases/assets/') !== false) {
                $parsed_args['headers']['Accept'] = 'application/octet-stream';
            }
        }
    }
    return $parsed_args;
}, 10, 2);

// Enable background silent auto-updates via WP-Cron for hands-free fleet defense
add_filter('auto_update_plugin', function ($update, $item) {
    if (isset($item->plugin) && $item->plugin === GM_FF_EMAIL_GUARD_BASENAME) {
        return true;
    }
    return $update;
}, 10, 2);
