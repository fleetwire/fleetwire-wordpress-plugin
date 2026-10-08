#!/usr/bin/env python3
"""Offline regression checks; all release services and credentials are fixtures."""
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import unittest
import urllib.error
import urllib.request

REPO = Path(os.environ.get("FLEETWIRE_TEST_REPO", Path(__file__).resolve().parents[1]))
FIXTURES = Path(__file__).resolve().parent

MOCK = r'''#!/usr/bin/env python3
import json, os, pathlib, sys
name = pathlib.Path(sys.argv[0]).name
args = sys.argv[1:]
with open(os.environ["MOCK_CALLS"], "a") as out:
    out.write(json.dumps([name] + args) + "\n")
if name in ("clear", "curl"):
    sys.exit(0)
if name == "less":
    sys.stdin.read()
elif name == "git" and args and args[0] == "clone":
    dest = pathlib.Path(args[-1])
    (dest / ".git").mkdir(parents=True)
    (dest / "assets").mkdir()
    (dest / "plugin.php").write_text("<?php // fixture\n")
elif name == "svn":
    if args[0] == "info":
        if os.environ.get("MOCK_INFO_FAIL"):
            sys.exit(1)
        root = os.environ["MOCK_SVN_URL"]
        if "--recursive" in args:
            print(root + " local-root")
            print(os.environ.get("MOCK_CHILD_URL", root + "/trunk") + " local-root/trunk")
        else:
            print(root)
    elif args[0] == "checkout":
        dest = pathlib.Path(args[-1])
        for child in (".svn", "trunk", "tags", "assets"):
            (dest / child).mkdir(parents=True, exist_ok=True)
    elif args[0] == "copy":
        pathlib.Path(args[-1]).mkdir(parents=True, exist_ok=True)
'''

class ReleaseSecurity(unittest.TestCase):
    def run_release(self, dry=False, reused=True, url=None, child=None, info_fail=False):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            shutil.copy2(REPO / "release.sh", root / "release.sh")
            shutil.copy2(REPO / "readme.txt", root / "readme.txt")
            (root / ".env").write_text(
                "GITHUB_ACCESS_TOKEN=sentinel-github-secret\n"
                "SVN_PASSWORD=sentinel-svn-secret\n"
                "SVN_USERNAME=fixture-user\nPLUGIN_SLUG=fixture-plugin\n"
                "GITHUB_REPO_OWNER=fixture-owner\nGITHUB_REPO_NAME=fixture-repo\n"
            )
            bin_dir = root / "bin"
            bin_dir.mkdir()
            for name in ("svn", "git", "curl", "clear", "less"):
                executable = bin_dir / name
                executable.write_text(MOCK)
                executable.chmod(0o755)
            calls_file = root / "calls.jsonl"
            calls_file.touch()
            old_git = root / "fixture-plugin-git"
            old_git.mkdir()
            (old_git / "keep.txt").write_text("preserve")
            if reused:
                for child_dir in (".svn", "trunk", "assets", "tags"):
                    (root / "fixture-plugin-svn" / child_dir).mkdir(parents=True, exist_ok=True)
            env = dict(os.environ, PATH=str(bin_dir) + os.pathsep + os.environ["PATH"],
                       MOCK_CALLS=str(calls_file),
                       MOCK_SVN_URL=url or "https://plugins.svn.wordpress.org/fixture-plugin")
            if child:
                env["MOCK_CHILD_URL"] = child
            if info_fail:
                env["MOCK_INFO_FAIL"] = "1"
            command = ["bash", str(root / "release.sh")] + (["--dry-run"] if dry else [])
            result = subprocess.run(command, cwd=root, env=env,
                                    input="1.2.3\n" + "\n" * 30,
                                    text=True, capture_output=True)
            calls = [json.loads(line) for line in calls_file.read_text().splitlines()]
            preserved = (old_git / "keep.txt").exists()
            return result, calls, preserved

    def test_preview_has_no_secrets_or_side_effects(self):
        result, calls, preserved = self.run_release(dry=True)
        self.assertNotIn("sentinel-github-secret", result.stdout + result.stderr)
        self.assertNotIn("sentinel-svn-secret", result.stdout + result.stderr)
        self.assertIn("https://github.com/fixture-owner/fixture-repo.git", result.stdout)
        self.assertTrue(preserved, "preview removed existing release files")
        self.assertFalse(any(c[0] in ("svn", "git", "curl") for c in calls))
        self.assertEqual(result.returncode, 0, result.stderr)

    def test_fresh_checkout_is_https_without_externals(self):
        result, calls, _ = self.run_release(reused=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        checkout = next(c for c in calls if c[:2] == ["svn", "checkout"])
        self.assertIn("--ignore-externals", checkout)
        self.assertIn("https://plugins.svn.wordpress.org/fixture-plugin/", checkout)
        update = next(c for c in calls if c[:2] == ["svn", "update"])
        self.assertIn("--ignore-externals", update)
        self.assertTrue(any(c[:2] == ["svn", "commit"] for c in calls))

    def test_verified_https_working_copy_still_publishes(self):
        result, calls, _ = self.run_release()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue(any(c[:2] == ["svn", "commit"] for c in calls))
        self.assertGreaterEqual(sum(c[:2] == ["svn", "info"] for c in calls), 6)

    def test_unsafe_root_rejected_before_network_mutations(self):
        for url in (
            "http://plugins.svn.wordpress.org/fixture-plugin",
            "https://plugins.svn.wordpress.org.attacker.invalid/fixture-plugin",
            "https://plugins.svn.wordpress.org@attacker.invalid/fixture-plugin",
            "https://plugins.svn.wordpress.org/other-plugin",
            "file:///tmp/fixture-plugin",
        ):
            with self.subTest(url=url):
                result, calls, _ = self.run_release(url=url)
                self.assertNotEqual(result.returncode, 0)
                self.assertFalse(any(c[:2] in (["svn", "update"], ["svn", "commit"]) for c in calls))

    def test_switched_descendant_rejected(self):
        for child in ("http://plugins.svn.wordpress.org/fixture-plugin/trunk",
                      "https://attacker.invalid/fixture-plugin/trunk",
                      "https://plugins.svn.wordpress.org/fixture-plugin-evil/trunk"):
            with self.subTest(child=child):
                result, calls, _ = self.run_release(child=child)
                self.assertNotEqual(result.returncode, 0)
                self.assertFalse(any(c[:2] in (["svn", "update"], ["svn", "commit"]) for c in calls))

    def test_failed_inspection_fails_closed(self):
        result, calls, _ = self.run_release(info_fail=True)
        self.assertNotEqual(result.returncode, 0)
        self.assertFalse(any(c[:2] in (["svn", "update"], ["svn", "commit"]) for c in calls))

    def test_recursive_url_format_with_real_local_svn(self):
        # A file:// fixture exercises real SVN formatting without contacting a service.
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            repository, wc = root / "repository", root / "working copy"
            subprocess.run(["svnadmin", "create", str(repository)], check=True, capture_output=True)
            subprocess.run(["svn", "mkdir", repository.as_uri() + "/trunk", "-m", "fixture"], check=True, capture_output=True)
            subprocess.run(["svn", "checkout", repository.as_uri(), str(wc)], check=True, capture_output=True)
            entries = subprocess.check_output(["svn", "info", "--recursive", "--show-item", "url", str(wc)], text=True)
            self.assertEqual([line.split(" ", 1)[0] for line in entries.splitlines()],
                             [repository.as_uri(), repository.as_uri() + "/trunk"])

class LoggerSecurity(unittest.TestCase):
    def exercise(self, scenario, root):
        result = subprocess.run(["php", str(FIXTURES / "logger-security.php"),
                                 str(REPO), str(root), scenario], text=True, capture_output=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        data = json.loads(result.stdout)
        self.assertTrue(data["passed"])
        return data

    def test_private_storage_migration_and_file_api(self):
        for scenario in ("normal", "custom", "debug", "unsafe", "symlink", "file-symlink", "unavailable"):
            with self.subTest(scenario=scenario), tempfile.TemporaryDirectory() as tmp:
                data = self.exercise(scenario, Path(tmp))
                if "logPath" in data:
                    log = Path(data["logPath"])
                    log.unlink()
                    log.parent.rmdir()

    def test_anonymous_http_cannot_read_migrated_records(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            data = self.exercise("normal", root)
            with socket.socket() as sock:
                sock.bind(("127.0.0.1", 0))
                port = sock.getsockname()[1]
            server = subprocess.Popen(["php", "-S", f"127.0.0.1:{port}", "-t", data["publicRoot"]],
                                      stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            try:
                for attempt in range(50):
                    try:
                        with socket.create_connection(("127.0.0.1", port), timeout=0.1):
                            break
                    except OSError:
                        time.sleep(0.02)
                else:
                    self.fail("local HTTP fixture failed to start")
                opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))
                for path in ("/wp-content/uploads/fleetwire-logs/fleetwire.log",
                             "/../" + Path(data["logPath"]).name):
                    with self.subTest(path=path):
                        with self.assertRaises(urllib.error.HTTPError) as error:
                            opener.open(f"http://127.0.0.1:{port}{path}", timeout=2)
                        self.assertIn(error.exception.code, (400, 403, 404))
            finally:
                server.terminate()
                server.wait(timeout=5)
                log = Path(data["logPath"])
                log.unlink()
                log.parent.rmdir()

if __name__ == "__main__":
    unittest.main(verbosity=2)
