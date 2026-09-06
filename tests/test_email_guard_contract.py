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
            "Plugin Name: Fluent Forms Email Guard & Anti-Bounce",
            "Version: 1.1.3",
            "License: GPL-2.0-or-later",
            "Text Domain: fluentform-email-guard",
        ]
        for h in headers:
            with self.subTest(header=h):
                self.assertIn(h, self.content)

    def test_token_least_privilege_and_isolation(self):
        self.assertIn("gm_ff_email_guard_get_github_token", self.content)
        self.assertIn("FLUENTFORM_EMAIL_GUARD_GH_TOKEN", self.content)
        self.assertIn("Least Privilege Standard", self.content)

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

    def test_github_releases_updater_contract(self):
        self.assertIn("pre_set_site_transient_update_plugins", self.content)
        self.assertIn("site_transient_update_plugins", self.content)
        self.assertIn("auto_update_plugin", self.content)
        self.assertIn("plugins_api", self.content)
        self.assertIn("api.github.com/repos/", self.content)

    def test_wp_cron_weekly_sync(self):
        self.assertIn("gm_ff_email_guard_weekly_sync", self.content)
        self.assertIn("wp_schedule_event", self.content)
        self.assertIn("disposable-email-domains", self.content)

    def test_rest_api_endpoints(self):
        self.assertIn("register_rest_route", self.content)
        self.assertIn("/email-guard/test", self.content)
        self.assertIn("/email-guard/status", self.content)


if __name__ == "__main__":
    unittest.main()
