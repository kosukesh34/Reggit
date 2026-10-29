"""Merge gate contract tests; GitHub and Git are replaced by local fakes."""
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
  'branch --show-current') echo feature/merged;;
  'switch --quiet --detach') [[ -f "$REGGIT_TEST_ROOT/merged" ]] && touch "$REGGIT_TEST_ROOT/detached";;
  'branch -D feature/merged') [[ -f "$REGGIT_TEST_ROOT/detached" ]] && touch "$REGGIT_TEST_ROOT/branch-deleted";;
  *) exit 99;;
esac
''',
                'gh': '''#!/bin/bash
case "$*" in
  'pr view 123 --json headRefOid --jq .headRefOid')
    if [[ "$REGGIT_TEST_CASE" == changed_pr_head ]]; then echo different; else echo "$REGGIT_TEST_HEAD"; fi;;
  'pr view 123 --json commits --jq '*)
    jq -r "$7" <<< '{"commits":[{"messageHeadline":"chore: sample","messageBody":"detail\\nCo-Authored-By: Claude などの署名を説明する行\\n\\nCo-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"},{"messageHeadline":"Merge origin/main","messageBody":"Co-authored-by: Claude <noreply@anthropic.com>\\n🤖 Generated with [Claude Code](https://claude.com/claude-code)"}]}';;
  'pr view '*) printf '{"headRefOid":"%s","state":"%s","baseRefName":"%s"}\\n' "$REGGIT_TEST_HEAD" "$REGGIT_TEST_STATE" "$REGGIT_TEST_BASE";;
  'pr checks '*)
    [[ "$REGGIT_TEST_CASE" != changed_head ]] || touch "$REGGIT_TEST_ROOT/changed"
    [[ "$REGGIT_TEST_CASE" != changed_base ]] || touch "$REGGIT_TEST_ROOT/new-base"
    [[ "$REGGIT_TEST_CASE" != failing_ci ]];;
  "api repos/{owner}/{repo}/commits/$REGGIT_TEST_HEAD/check-runs?check_name=main "*)
    case "$REGGIT_TEST_CASE" in failing_ci) echo failure;; missing_ci) echo;; *) echo success;; esac;;
  'pr merge '*)
    printf '%s' "$*" > "$REGGIT_TEST_ROOT/merged"
    if [[ "$REGGIT_TEST_CASE" == worktree && "$*" == *--delete-branch* ]]; then
      echo "fatal: main is already used by another worktree" >&2
      exit 1
    fi;;
  *) exit 99;;
esac
''',
                'python3': '''#!/bin/bash
[[ "$1" == -c ]] || exit 99
exec "$REGGIT_TEST_PYTHON" "$@"
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
            self.branch_deleted = (root / 'branch-deleted').exists()
            return result, merged

    def test_only_open_main_pr_is_accepted_even_when_python_is_optimized(self):
        for state, base in [('CLOSED', 'main'), ('OPEN', 'develop')]:
            with self.subTest(state=state, base=base):
                result, merged = self.execute(state=state, base=base)
                self.assertNotEqual(0, result.returncode, result.stdout + result.stderr)
                self.assertIsNone(merged)

    def test_failed_or_stale_validation_never_merges(self):
        for scenario in ['dirty', 'wrong_head', 'stale_base', 'failing_ci', 'missing_ci',
                         'changed_head', 'changed_base', 'changed_pr_head']:
            with self.subTest(scenario=scenario):
                result, merged = self.execute(scenario)
                self.assertNotEqual(0, result.returncode, result.stdout + result.stderr)
                self.assertIsNone(merged)
                self.assertFalse(self.branch_deleted)

    def test_successful_merge_deletes_the_local_branch_without_switching_to_main(self):
        result, merged = self.execute()
        self.assertEqual(0, result.returncode, result.stdout + result.stderr)
        self.assertTrue(self.branch_deleted)

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

    def test_squash_body_drops_claude_attribution(self):
        result, merged = self.execute()
        self.assertEqual(0, result.returncode, result.stdout + result.stderr)
        self.assertIn('* chore: sample\n\ndetail', merged)
        self.assertIn('* Merge origin/main', merged)
        self.assertIn('Co-Authored-By: Claude などの署名を説明する行', merged)
        self.assertNotIn('noreply@anthropic.com', merged)
        self.assertNotIn('Generated with', merged)


if __name__ == '__main__':
    unittest.main()
