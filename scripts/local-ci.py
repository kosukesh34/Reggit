#!/usr/bin/env python3
"""Validate the committed tree in disposable containers, without application secrets."""
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import uuid

ROOT = Path(__file__).resolve().parent.parent


def run(*args, **kwargs):
    print('+', ' '.join(map(str, args)), flush=True)
    return subprocess.run(args, check=True, cwd=ROOT, **kwargs)


def git(*args):
    return subprocess.check_output(['git', *args], cwd=ROOT, text=True).strip()


def main():
    if git('status', '--porcelain'):
        sys.exit('Local CI requires a clean, committed working tree.')
    head = git('rev-parse', 'HEAD')
    if not (ROOT / 'vendor/autoload.php').is_file():
        sys.exit('Install composer.lock dependencies first (composer install).')
    identifier = 'reggit-local-ci-' + uuid.uuid4().hex[:12]
    with tempfile.TemporaryDirectory(prefix=identifier + '-') as directory:
        source = Path(directory)
        archive = source / 'source.tar'
        run('git', 'archive', '--output', str(archive), head)
        run('tar', '-xf', str(archive), '-C', str(source))
        archive.unlink()
        (source / '.env.testing').write_text(
            'APP_ENV=testing\nAPP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=\n'
            'DB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_DATABASE=reggit_test\n'
            'DB_USERNAME=root\nDB_PASSWORD=review\nCACHE_STORE=array\n'
            'SESSION_DRIVER=array\nQUEUE_CONNECTION=sync\nLOG_CHANNEL=stderr\n'
        )
        for path in ['storage/framework/views', 'storage/framework/cache/data',
                     'storage/framework/sessions', 'storage/logs', 'bootstrap/cache', 'home']:
            (source / path).mkdir(parents=True, exist_ok=True)
        try:
            run('docker', 'run', '-d', '--name', identifier, '--network', 'none',
                '--read-only', '--cpus', '2', '--memory', '1g', '--pids-limit', '128',
                '--tmpfs', '/var/lib/mysql:rw,size=512m',
                '--tmpfs', '/var/run/mysqld:rw,size=16m', '--tmpfs', '/tmp:rw,size=32m',
                '-e', 'MYSQL_ROOT_PASSWORD=review', '-e', 'MYSQL_DATABASE=reggit_test',
                'mysql:8.4', stdout=subprocess.DEVNULL)
            run('docker', 'exec', identifier, 'sh', '-c',
                'for i in $(seq 1 60); do mysqladmin ping -h127.0.0.1 -uroot -preview '
                '--silent && exit 0; sleep 1; done; exit 1', timeout=70)
            backend = ['docker', 'run', '--rm', '--network', 'container:' + identifier,
                       '--read-only', '--cpus', '2', '--memory', '1g', '--pids-limit', '128',
                       '--tmpfs', '/tmp:rw,size=64m', '--tmpfs', '/work:rw,size=512m,mode=1777',
                       '-v', str(source) + ':/source:ro',
                       '-v', str(ROOT / 'vendor') + ':/work/vendor:ro', '-w', '/work',
                       'reggit-app', 'env', '-i', 'PATH=/usr/local/bin:/usr/bin:/bin',
                       'HOME=/work/home', 'TMPDIR=/tmp', 'APP_ENV=testing',
                       'SMAREGI_CLIENT_ID=', 'SMAREGI_CLIENT_SECRET=', 'SMAREGI_CONTRACT_ID=',
                       'sh', '-c']
            run(*backend, 'cp -r /source/. /work/ && '
                'timeout 900 ./vendor/bin/pint --test && '
                'timeout 900 ./vendor/bin/phpstan analyse --memory-limit=768M && '
                'timeout 900 php artisan test --exclude-group=smaregi-integration', timeout=2400)
            # Install locked dependencies without running package lifecycle scripts. Only this
            # preparation container has network access; all actual frontend checks run offline.
            run('docker', 'volume', 'create', identifier, stdout=subprocess.DEVNULL)
            frontend = ['docker', 'run', '--rm', '--cpus', '2', '--memory', '2g',
                        '--pids-limit', '256', '-v', str(source) + ':/source:ro',
                        '-v', identifier + ':/work', '-w', '/work', 'node:24-bookworm-slim']
            run(*frontend, 'sh', '-c', 'cp -r /source/. /work/ && npm ci --ignore-scripts', timeout=600)
            # Put Docker flags before the image, not after it.
            offline = frontend[:-1] + ['--network', 'none', frontend[-1]]
            run(*offline, 'sh', '-c', 'npm run typecheck && npm run lint && '
                'npm run test:unit:frontend && npm run build', timeout=900)
            if git('rev-parse', 'HEAD') != head or git('status', '--porcelain'):
                sys.exit('Local CI refused: checkout changed during verification.')
            print('LOCAL CI PASSED: ' + head, flush=True)
        finally:
            subprocess.run(['docker', 'rm', '-f', identifier], stdout=subprocess.DEVNULL,
                           stderr=subprocess.DEVNULL)
            subprocess.run(['docker', 'volume', 'rm', identifier], stdout=subprocess.DEVNULL,
                           stderr=subprocess.DEVNULL)


if __name__ == '__main__':
    try:
        main()
    except (subprocess.CalledProcessError, subprocess.TimeoutExpired) as error:
        sys.exit('LOCAL CI FAILED: ' + str(error))
