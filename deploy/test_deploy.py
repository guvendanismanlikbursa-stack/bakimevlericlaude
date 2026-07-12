#!/usr/bin/env python3
"""
deploy.py icin birim testleri (stdlib unittest, ek bagimlilik yok).

12 Temmuz 2026'da fark edildi: deploy.py'nin dosya silme ve paket
versiyon-guncelleme mantigi hic gercek bir senaryoyla sinanmamisti -
"mantiksal olarak dogru" olmasi yeterli guvence degildi. Bu dosya o
bosluğu kalici olarak kapatir: her push'ta (bkz. .github/workflows/
tests.yml) otomatik calisir, deploy.py'ye yapilacak gelecekteki
degisiklikler de bu testlerden gecmek zorunda kalir.

Calistirma: python -m unittest deploy.test_deploy -v
"""
import json
import os
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import MagicMock, patch

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import deploy


class DeleteFileTest(unittest.TestCase):
    """delete_file() FTP DELE komutunu dogru cagiriyor mu - 12 Temmuz 2026'da
    production'a gercek bir dosya yuklenip silinerek uctan uca dogrulandi,
    burada aynı davranis mock ile kalici olarak korunuyor."""

    def test_calls_ftp_delete_with_correct_path(self):
        ftp = MagicMock()
        deploy.delete_file(ftp, 'public_html', 'public/deploy-test-marker.txt')
        ftp.delete.assert_called_once_with('public_html/public/deploy-test-marker.txt')

    def test_windows_backslash_paths_are_normalized(self):
        ftp = MagicMock()
        deploy.delete_file(ftp, 'public_html', 'app\\Models\\Facility.php')
        ftp.delete.assert_called_once_with('public_html/app/Models/Facility.php')

    def test_missing_file_error_is_silently_ignored(self):
        import ftplib
        ftp = MagicMock()
        ftp.delete.side_effect = ftplib.error_perm('550 No such file')
        try:
            deploy.delete_file(ftp, 'public_html', 'zaten-yok.txt')
        except ftplib.error_perm:
            self.fail('delete_file() dosya-yok hatasinda patlamamali')


class UploadFileTest(unittest.TestCase):
    def test_calls_ftp_storbinary_with_correct_path(self):
        ftp = MagicMock()
        with tempfile.NamedTemporaryFile(delete=False) as f:
            f.write(b'test')
            tmp_path = f.name
        try:
            rel_path = os.path.relpath(tmp_path, deploy.APP_ROOT)
            deploy.upload_file(ftp, 'public_html', rel_path)
            self.assertEqual(ftp.storbinary.call_count, 1)
            args = ftp.storbinary.call_args[0]
            self.assertTrue(args[0].startswith('STOR public_html/'))
        finally:
            os.remove(tmp_path)


class VersionBumpDetectionTest(unittest.TestCase):
    """12 Temmuz 2026'da izole bir git worktree'de elle dogrulanan senaryonun
    kalici, otomatik versiyonu: composer.lock'ta SADECE versiyonu degisen
    paket dogru tespit edilmeli, digerleri yanlislikla eklenmemeli."""

    @classmethod
    def setUpClass(cls):
        cls.tmp_repo = tempfile.mkdtemp(prefix='deploy_test_repo_')
        run = lambda *args: subprocess.run(args, cwd=cls.tmp_repo, check=True, capture_output=True)
        run('git', 'init', '-q')
        run('git', 'config', 'user.email', 'test@test.local')
        run('git', 'config', 'user.name', 'Test')

        lock_v1 = {'packages': [
            {'name': 'vendor/paket-a', 'version': 'v1.0.0'},
            {'name': 'vendor/paket-b', 'version': 'v2.0.0'},
        ]}
        with open(os.path.join(cls.tmp_repo, 'composer.lock'), 'w') as f:
            json.dump(lock_v1, f)
        run('git', 'add', '.')
        run('git', 'commit', '-q', '-m', 'v1')
        cls.sha_before = subprocess.run(
            ['git', 'rev-parse', 'HEAD'], cwd=cls.tmp_repo, capture_output=True, text=True
        ).stdout.strip()

        # SADECE paket-a'nin versiyonu degisiyor; paket-b ayni kaliyor,
        # paket-c yeni ekleniyor.
        lock_v2 = {'packages': [
            {'name': 'vendor/paket-a', 'version': 'v1.1.0'},
            {'name': 'vendor/paket-b', 'version': 'v2.0.0'},
            {'name': 'vendor/paket-c', 'version': 'v1.0.0'},
        ]}
        with open(os.path.join(cls.tmp_repo, 'composer.lock'), 'w') as f:
            json.dump(lock_v2, f)
        run('git', 'add', '.')
        run('git', 'commit', '-q', '-m', 'v2')

    @classmethod
    def tearDownClass(cls):
        import shutil
        shutil.rmtree(cls.tmp_repo, ignore_errors=True)

    def test_only_changed_and_new_packages_are_flagged(self):
        with patch.object(deploy, 'APP_ROOT', self.tmp_repo):
            to_upload = deploy.get_vendor_dirs_to_upload(self.sha_before)
        self.assertEqual(set(to_upload), {'vendor/paket-a', 'vendor/paket-c'})
        self.assertNotIn('vendor/paket-b', to_upload, 'degismeyen paket yanlislikla yuklenmemeli')


class DevPackageLeakCheckTest(unittest.TestCase):
    """dev_only_paketler_sizdi_mi() - 1. cokmenin (myclabs/deep-copy) otomatik
    testi. Hem sizinti VARKEN hem YOKKEN dogru sonucu verdigini dogrular."""

    def setUp(self):
        self.tmp_dir = tempfile.mkdtemp(prefix='deploy_test_leak_')
        os.makedirs(os.path.join(self.tmp_dir, 'vendor', 'composer'), exist_ok=True)
        with open(os.path.join(self.tmp_dir, 'composer.json'), 'w') as f:
            json.dump({'require-dev': {'phpunit/phpunit': '^11.0', 'laravel/sail': '^1.0'}}, f)

    def tearDown(self):
        import shutil
        shutil.rmtree(self.tmp_dir, ignore_errors=True)

    def _write_autoload_files(self, content):
        for fname in ['autoload_classmap.php', 'autoload_static.php', 'autoload_real.php', 'autoload_files.php']:
            with open(os.path.join(self.tmp_dir, 'vendor', 'composer', fname), 'w') as f:
                f.write(content)

    def test_detects_leaked_dev_package(self):
        self._write_autoload_files("<?php return ['sail' => 'vendor/laravel/sail/src/SailServiceProvider.php'];")
        with patch.object(deploy, 'APP_ROOT', self.tmp_dir):
            leaked = deploy.dev_only_paketler_sizdi_mi()
        self.assertTrue(any('laravel/sail' in pkg for pkg, _ in leaked))

    def test_clean_autoload_reports_no_leak(self):
        self._write_autoload_files("<?php return ['socialite' => 'vendor/laravel/socialite/src/SocialiteServiceProvider.php'];")
        with patch.object(deploy, 'APP_ROOT', self.tmp_dir):
            leaked = deploy.dev_only_paketler_sizdi_mi()
        self.assertEqual(leaked, [])


class SkipPrefixesTest(unittest.TestCase):
    """get_changed_files()'in SKIP_PREFIXES filtrelemesi - deploy/, .github/,
    tests/, storage/ gibi yollarin asla production'a gitmemesi gerekiyor."""

    def test_skip_prefixes_cover_known_sensitive_paths(self):
        sensitive_paths = [
            'deploy/.env.deploy', '.github/workflows/tests.yml',
            'tests/Feature/PlatformFeatureTest.php', 'storage/logs/laravel.log',
            'bootstrap/cache/config.php', '.env', '.gitignore',
        ]
        for path in sensitive_paths:
            with self.subTest(path=path):
                self.assertTrue(
                    any(path.startswith(p) for p in deploy.SKIP_PREFIXES),
                    f'{path} SKIP_PREFIXES tarafindan kapsanmiyor!'
                )


if __name__ == '__main__':
    unittest.main()
