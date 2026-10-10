"""Merge gate contract tests; GitHub, Git and application CI are replaced by local fakes."""
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

SCRIPT = Path(__file__).resolve().parents[1] / 'merge-pr.sh'
HEAD = 'a' * 40


class MergeGateTest(unittest.TestCase):
    def execute(self, scenario='success', state='OPEN', base='main'):
        with tempfile.TemporaryDirectory(prefix='reggit-merge-gate-test-') as directory:
            root = Path(directory)
            commands = {
                'git': '''#!/bin/bash
case "$*" in
  'rev-parse --show-toplevel') echo "$REGGIT_TEST_ROOT";;
  'status --porcelain') [[ "$REGGIT_TEST_CASE" != dirty ]] || echo ' M file';;
  'rev-parse HEAD')
    if [[ "$REGGIT_TEST_CASE" == wrong_head || -f "$REGGIT_TEST_ROOT/changed" ]]; then echo different; else echo "$REGGIT_TEST_HEAD"; fi;;
  'fetch origin main') :;;
  'merge-base --is-ancestor origin/main HEAD') [[ "$REGGIT_TEST_CASE" != stale_base && ! -f "$REGGIT_TEST_ROOT/new-base" ]];;
  *) exit 99;;
esac
''',
                'gh': '''#!/bin/bash
case "$*" in
  'pr view '*) printf '{"headRefOid":"%s","state":"%s","baseRefName":"%s"}\\n' "$REGGIT_TEST_HEAD" "$REGGIT_TEST_STATE" "$REGGIT_TEST_BASE";;
  'api '*) if [[ "$REGGIT_TEST_CASE" == workflow_enabled ]]; then echo active; else echo disabled_manually; fi;;
  'pr merge '*)
    printf '%s' "$*" > "$REGGIT_TEST_ROOT/merged"
    if [[ "$REGGIT_TEST_CASE" == worktree && "$*" == *--delete-branch* ]]; then
      echo "fatal: main is already used by another worktree" >&2
      exit 1
    fi;;
  *) exit 99;;
esac
''',
                'mktemp': '''#!/bin/bash
: > \"$REGGIT_TEST_ROOT/ci.log\"
echo \"$REGGIT_TEST_ROOT/ci.log\"
''',
                'python3': '''#!/bin/bash
if [[ "$1" == -c ]]; then exec "$REGGIT_TEST_PYTHON" "$@"; fi
[[ "$*" == scripts/local-ci.py ]] || exit 99
[[ "$REGGIT_TEST_CASE" != failing_ci ]] || exit 1
[[ "$REGGIT_TEST_CASE" != changed_head ]] || touch "$REGGIT_TEST_ROOT/changed"
[[ "$REGGIT_TEST_CASE" != changed_base ]] || touch "$REGGIT_TEST_ROOT/new-base"
echo 'Fake local CI success'
''',
            }
            for name, contents in commands.items():
                path = root / name
                path.write_text(contents)
                path.chmod(0o700)
            environment = {
                'PATH': str(root) + ':/usr/bin:/bin', 'TMPDIR': str(root),
                'PYTHONOPTIMIZE': '1', 'REGGIT_TEST_ROOT': str(root),
                'REGGIT_TEST_CASE': scenario, 'REGGIT_TEST_STATE': state,
                'REGGIT_TEST_BASE': base, 'REGGIT_TEST_HEAD': HEAD,
                'REGGIT_TEST_PYTHON': sys.executable,
            }
            result = subprocess.run(['/bin/bash', str(SCRIPT), '123'], env=environment,
                                    capture_output=True, text=True, timeout=10)
            merged = (root / 'merged').read_text() if (root / 'merged').exists() else None
            return result, merged

    def test_only_open_main_pr_is_accepted_even_when_python_is_optimized(self):
        for state, base in [('CLOSED', 'main'), ('OPEN', 'develop')]:
            with self.subTest(state=state, base=base):
                result, merged = self.execute(state=state, base=base)
                self.assertNotEqual(0, result.returncode, result.stdout + result.stderr)
                self.assertIsNone(merged)

    def test_failed_or_stale_validation_never_merges(self):
        for scenario in ['dirty', 'wrong_head', 'stale_base', 'workflow_enabled',
                         'failing_ci', 'changed_head', 'changed_base']:
            with self.subTest(scenario=scenario):
                result, merged = self.execute(scenario)
                self.assertNotEqual(0, result.returncode, result.stdout + result.stderr)
                self.assertIsNone(merged)

    def test_worktree_merge_does_not_switch_to_an_occupied_main(self):
        result, merged = self.execute(scenario='worktree')
        self.assertEqual(0, result.returncode, result.stdout + result.stderr)
        self.assertIn('--match-head-commit ' + HEAD, merged)
        self.assertNotIn('--delete-branch', merged)

    def test_successful_validation_merges_only_the_checked_head(self):
        result, merged = self.execute()
        self.assertEqual(0, result.returncode, result.stdout + result.stderr)
        self.assertIn('--match-head-commit ' + HEAD, merged)
        self.assertNotIn('--admin', merged)


if __name__ == '__main__':
    unittest.main()
