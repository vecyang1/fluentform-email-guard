#!/usr/bin/env python3
"""Contract and syntax unit tests for Fluent Forms Email Guard WordPress Plugin."""

import re
import subprocess
import unittest
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[1]
PLUGIN_FILE = REPO_ROOT / "fluentform-email-guard.php"


class TestFluentFormEmailGuardContract(unittest.TestCase):
    def setUp(self):
        self.assertTrue(PLUGIN_FILE.is_file(), "Plugin main file must exist")
        self.content = PLUGIN_FILE.read_text(encoding="utf-8")

    def test_php_syntax_linter(self):
        res = subprocess.run(
            ["php", "-l", str(PLUGIN_FILE)],
            capture_output=True,
            text=True
        )
        self.assertEqual(res.returncode, 0, f"PHP syntax lint failed: {res.stderr}")
        self.assertIn("No syntax errors detected", res.stdout)

    def test_plugin_headers(self):
        headers = [
            "Plugin Name: Email Guard for Fluent Forms",
            "Version: 1.1.4",
            "License: GPL-2.0-or-later",
            "Text Domain: email-guard-for-fluent-forms",
        ]
        for h in headers:
            with self.subTest(header=h):
                self.assertIn(h, self.content)

    def test_wporg_guideline_7_zero_third_party_updater(self):
        """WordPress.org Guideline #7: Hosted plugins may not contain third-party update checkers."""
        forbidden_strings = [
            "site_transient_update_plugins",
            "pre_set_site_transient_update_plugins",
            "auto_update_plugin",
            "_site_transient_update_plugins",
            "gm_ff_email_guard_check_github_update",
            "gm_ff_email_guard_get_github_token",
        ]
        for s in forbidden_strings:
            with self.subTest(forbidden=s):
                self.assertNotIn(s, self.content, f"Forbidden updater symbol '{s}' must not exist in plugin")

    def test_fluent_forms_validation_hook(self):
        self.assertIn(
            "add_filter('fluentform/validate_input_item_input_email'",
            self.content,
            "Must hook into Fluent Forms input email validation filter"
        )

    def test_admin_menu_priority_99(self):
        self.assertTrue(
            bool(re.search(r"add_action\(\s*'admin_menu'.*?,\s*99\s*\);", self.content, re.DOTALL)),
            "Admin menu hook must run at priority 99 to prevent timing collision with fluent_forms"
        )

    def test_readme_txt_and_distignore_parity(self):
        readme_path = REPO_ROOT / "readme.txt"
        self.assertTrue(readme_path.is_file(), "readme.txt must exist")
        readme_txt = readme_path.read_text(encoding="utf-8")
        self.assertIn("=== Email Guard for Fluent Forms ===", readme_txt)
        self.assertIn("Contributors: hxsmyxh, vecyang1", readme_txt)
        self.assertIn("Tested up to: 7.1", readme_txt)
        self.assertIn("Stable tag: 1.1.4", readme_txt)
        self.assertIn("== Description ==", readme_txt)
        self.assertIn("== External Services ==", readme_txt)
        self.assertIn("== Installation ==", readme_txt)
        self.assertIn("== Changelog ==", readme_txt)
        self.assertIn("= 1.1.4 =", readme_txt)
        self.assertIn("https://github.com/disposable-email-domains/disposable-email-domains/blob/main/LICENSE.txt", readme_txt)

        distignore_path = REPO_ROOT / ".distignore"
        self.assertTrue(distignore_path.is_file(), ".distignore must exist")
        distignore_txt = distignore_path.read_text(encoding="utf-8")
        self.assertIn(".git/", distignore_txt)
        self.assertIn("tests/", distignore_txt)
        self.assertIn(".env*", distignore_txt)

    def test_directory_assets_exist(self):
        assets_dir = REPO_ROOT / "assets"
        self.assertTrue(assets_dir.is_dir(), "assets/ directory must exist")
        self.assertTrue((assets_dir / "banner-772x250.png").is_file())
        self.assertTrue((assets_dir / "banner-1544x500.png").is_file())
        self.assertTrue((assets_dir / "icon-256x256.png").is_file())
        self.assertTrue((assets_dir / "icon.svg").is_file())

    def test_zero_remote_download_and_offline_bundle(self):
        """Ensure no remote file downloading or WP-Cron scheduling exists (WordPress.org Guideline)."""
        self.assertNotIn("wp_schedule_event", self.content, "Must not schedule WP-Cron for remote syncing")
        self.assertNotIn("raw.githubusercontent.com", self.content, "Must not fetch raw remote GitHub lists")
        self.assertIn("data/disposable_domains.json", self.content, "Must load bundled disposable domains")

        data_file = REPO_ROOT / "data" / "disposable_domains.json"
        self.assertTrue(data_file.is_file(), "Bundled data/disposable_domains.json must exist")
        import json
        domains = json.loads(data_file.read_text(encoding="utf-8"))
        self.assertGreaterEqual(len(domains), 8000, "Bundled domains must contain 8,000+ entries")

    def test_rest_api_endpoints_security(self):
        self.assertIn("register_rest_route", self.content)
        self.assertIn("/email-guard/test", self.content)
        self.assertIn("/email-guard/status", self.content)
        self.assertIn("/admin/status", self.content)
        self.assertIn("current_user_can('manage_options')", self.content)

    def test_sanitization_and_escaping_compliance(self):
        self.assertIn("wp_unslash($_SERVER['REMOTE_ADDR'])", self.content)
        self.assertIn("wp_unslash($_POST['gm_ff_eg_action'])", self.content)
        self.assertIn("absint(wp_unslash($_POST['gm_ff_eg_cache_ttl']))", self.content)
        self.assertIn("esc_html__('You do not have permission to access this page.', 'email-guard-for-fluent-forms')", self.content)
        self.assertIn("gmdate(", self.content)


if __name__ == "__main__":
    unittest.main()
