#!/usr/bin/env python3
"""
Tek, guvenli, tekrar kullanilabilir deploy script'i.

11 Temmuz 2026'da ayni gun icinde 3 kez yasanan production cokmesinin
(autoload dev/no-dev karisikligi, git'te dev-dahil bootstrap/cache
dosyalari, eksik Socialite config anahtari) bir daha yasanmamasi icin
yazildi. O gune kadar her deploy elle, tek seferlik script'lerle
yapiliyordu - bu script onlarin hepsinin yerini alan, kendi kendini
dogrulayan tek bir surec.

Kullanim:
    python deploy/deploy.py

Ne yapar (sirayla, herhangi bir adim basarisiz olursa DURUR):
  1. Yerel `vendor/bin/phpunit` calistirir.
  2. composer.lock son deploy'dan beri degistiyse: `composer dump-autoload
     --no-dev --optimize` ile temiz autoload uretir, uretilen dosyalarda
     require-dev'e ozel bir paketin sizip sizmedigini otomatik dogrular
     (bkz. dev_only_paketler_sizdi_mi()) - 1. cokmenin otomatik testi.
  3. Son basarili deploy'dan (deploy/.last_deployed_sha) bu yana degisen
     git-takipli dosyalari FTP ile 3 doc root'a yukler. composer.lock
     degistiyse yeni eklenen/guncellenen vendor paket dizinlerini de
     otomatik tespit edip yukler.
  4. Her doc root'ta sirasiyla /_ops/migrate, /_ops/package-discover,
     /_ops/cache-refresh cagirir (bkz. OpsController) - bootstrap/cache/
     dosyalari artik hic FTP ile tasinmiyor, hep sunucuda uretiliyor
     (2. cokmenin kok nedeni buydu).
  5. /_saglik ucunu 3 domainde de kontrol eder (bkz. HealthController);
     hepsi "ok" degilse script kirmizi ile biter.
  6. Basarili olursa deploy/.last_deployed_sha guncellenir (commit etmek
     kullaniciya birakilir).
  7. composer.lock degistiyse en sonda yerel `composer install` (normal,
     dev dahil) ile gelistirme ortami eski haline getirilir.

Bilinen sinir: otomatik rollback YOK. 5. adim basarisiz olursa script
kirmizi biter ama o ana kadar yuklenmis dosyalar sunucuda kalir - elle
mudahale gerekir (onceki .last_deployed_sha'ya git checkout + script'i
tekrar calistirmak en hizli kurtarma yolu).
"""
import ftplib
import json
import os
import subprocess
import sys
import urllib.request
import urllib.error

APP_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DEPLOY_DIR = os.path.join(APP_ROOT, 'deploy')
ENV_DEPLOY_PATH = os.path.join(DEPLOY_DIR, '.env.deploy')
LAST_SHA_PATH = os.path.join(DEPLOY_DIR, '.last_deployed_sha')

# Kod agacinda degisse bile production'a TASINMAMASI gereken yollar.
SKIP_PREFIXES = (
    '.git', '.github/', 'deploy/', 'tests/', 'storage/',
    'bootstrap/cache/', '.env', 'README', '.gitignore',
)

# Windows'ta konsol varsayilan olarak yerel kod sayfasini (ör. cp1254)
# kullanir - composer/git ciktisindaki UTF-8 karakterler (BOM, Turkce
# harfler) print() sirasinda UnicodeEncodeError ile script'i cokertiyordu.
for _stream in (sys.stdout, sys.stderr):
    if hasattr(_stream, 'reconfigure'):
        _stream.reconfigure(encoding='utf-8', errors='replace')


def fail(msg):
    print(f'\n[HATA] {msg}')
    sys.exit(1)


def info(msg):
    print(f'[..] {msg}')


def ok(msg):
    print(f'[OK] {msg}')


def load_deploy_env():
    if not os.path.exists(ENV_DEPLOY_PATH):
        fail(
            f'{ENV_DEPLOY_PATH} bulunamadi. deploy/.env.deploy.example dosyasini '
            'kopyalayip gercek FTP/OPS bilgilerini doldurun (bu dosya .gitignore\'da, '
            'asla commit edilmez).'
        )
    env = {}
    with open(ENV_DEPLOY_PATH, 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#') or '=' not in line:
                continue
            key, _, value = line.partition('=')
            env[key.strip()] = value.strip()
    required = ['FTP_HOST', 'FTP_USER', 'FTP_PASS', 'OPS_SECRET', 'DOC_ROOTS', 'DOMAINS']
    missing = [k for k in required if not env.get(k)]
    if missing:
        fail(f'{ENV_DEPLOY_PATH} icinde eksik degerler: {", ".join(missing)}')
    env['DOC_ROOTS'] = [x.strip() for x in env['DOC_ROOTS'].split(',')]
    env['DOMAINS'] = [x.strip() for x in env['DOMAINS'].split(',')]
    if len(env['DOC_ROOTS']) != len(env['DOMAINS']):
        fail('DOC_ROOTS ve DOMAINS ayni sirada ve ayni sayida olmali.')
    return env


def run(cmd, cwd=APP_ROOT, check=True):
    info('calistiriliyor: ' + ' '.join(cmd))
    # encoding='utf-8' + errors='replace' sart: Windows'ta varsayilan yerel
    # kod sayfasi (ör. cp1254) composer/git ciktisindaki UTF-8 karakterleri
    # decode edemeyip subprocess okuyucu thread'ini sessizce cokertiyor,
    # bu da result.stdout'un None kalmasina ve sonraki json.loads() gibi
    # cagrilarin TypeError ile patlamasina yol aciyordu.
    result = subprocess.run(
        cmd, cwd=cwd, capture_output=True, text=True,
        encoding='utf-8', errors='replace', shell=(os.name == 'nt'),
    )
    print(result.stdout)
    if result.returncode != 0:
        print(result.stderr)
        if check:
            fail(f'komut basarisiz oldu (exit {result.returncode}): {" ".join(cmd)}')
    return result


def step1_run_tests():
    info('Adim 1/7: yerel test paketi calistiriliyor...')
    result = run(['php', 'vendor/bin/phpunit'], check=False)
    if result.returncode != 0:
        fail('Testler basarisiz - deploy DURDURULDU. Once testleri gecir, sonra tekrar dene.')
    ok('Testler yesil.')


def get_last_deployed_sha():
    if not os.path.exists(LAST_SHA_PATH):
        fail(
            f'{LAST_SHA_PATH} bulunamadi. Bu dosya son basarili deploy commit\'ini takip eder. '
            'Ilk kurulumda "git rev-parse HEAD > deploy/.last_deployed_sha" ile elle olusturulmali.'
        )
    with open(LAST_SHA_PATH, 'r', encoding='utf-8') as f:
        return f.read().strip()


def get_changed_files(from_sha, to_sha='HEAD'):
    result = run(['git', 'diff', '--name-status', from_sha, to_sha])
    changes = []
    for line in result.stdout.strip().splitlines():
        if not line.strip():
            continue
        parts = line.split('\t')
        status, path = parts[0], parts[-1]
        if any(path.startswith(p) for p in SKIP_PREFIXES):
            continue
        changes.append((status[0], path))
    return changes


def composer_lock_changed(changed_files):
    return any(path == 'composer.lock' for _, path in changed_files)


def dev_only_paketler_sizdi_mi():
    """1. cokmenin (myclabs/deep-copy) otomatik testi: composer.json'daki
    require-dev paket isimlerinden herhangi biri, yeni uretilen --no-dev
    autoload haritasinda gecio geciyor mu kontrol eder."""
    with open(os.path.join(APP_ROOT, 'composer.json'), 'r', encoding='utf-8') as f:
        composer_json = json.load(f)
    dev_packages = list(composer_json.get('require-dev', {}).keys())

    autoload_files = [
        'vendor/composer/autoload_classmap.php',
        'vendor/composer/autoload_static.php',
        'vendor/composer/autoload_real.php',
        'vendor/composer/autoload_files.php',
    ]
    leaked = []
    for rel in autoload_files:
        path = os.path.join(APP_ROOT, rel)
        if not os.path.exists(path):
            continue
        with open(path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        for pkg in dev_packages:
            pkg_dir_fragment = pkg.split('/')[-1]
            if pkg_dir_fragment in content:
                leaked.append((pkg, rel))
    return leaked


def filter_installed_files_for_no_dev():
    """3. cokmenin otomatik testi/duzeltmesi: 'composer dump-autoload --no-dev'
    SADECE autoload class-map'ini --no-dev'e gore filtreler, installed.json
    ve installed.php dosyalarini DEGISTIRMEZ (bunlar Composer'in "ne kurulu"
    kaydidir, sadece 'composer install/update' tarafindan yazilir). Bu yuzden
    bu iki dosya HER ZAMAN yerel (dev-dahil) paket listesini tasir - production'a
    boyle yuklenince "artisan package:discover" installed.json'da hala listeli
    laravel/sail'i instantiate etmeye calisip 500'e dusuruyordu (11 Temmuz 2026,
    /_ops/package-discover ilk kez CSRF-fix sonrasi gercekten calistiginda
    ortaya cikti). Burada composer.json > require-dev'deki paket adlarini
    installed.json + installed.php'den elle cikariyoruz."""
    with open(os.path.join(APP_ROOT, 'composer.json'), 'r', encoding='utf-8') as f:
        composer_json = json.load(f)
    dev_packages = set(composer_json.get('require-dev', {}).keys())

    json_path = os.path.join(APP_ROOT, 'vendor', 'composer', 'installed.json')
    with open(json_path, 'r', encoding='utf-8') as f:
        data = json.load(f)
    before = len(data['packages'])
    data['packages'] = [p for p in data['packages'] if p['name'] not in dev_packages]
    after = len(data['packages'])
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=4, ensure_ascii=False)
    info(f'installed.json: {before} -> {after} paket (dev paketleri cikarildi)')

    php_path = os.path.join(APP_ROOT, 'vendor', 'composer', 'installed.php')
    helper_path = os.path.join(DEPLOY_DIR, '_filter_installed_php.php')
    helper_source = (
        "<?php\n"
        "$devPackages = json_decode($argv[2], true);\n"
        "$data = require $argv[1];\n"
        "foreach ($devPackages as $pkg) { unset($data['versions'][$pkg]); }\n"
        "if (isset($data['root']['dev'])) { $data['root']['dev'] = false; }\n"
        "file_put_contents($argv[1], '<?php return ' . var_export($data, true) . ';' . PHP_EOL);\n"
        "echo count($data['versions']) . ' paket kaldi' . PHP_EOL;\n"
    )
    with open(helper_path, 'w', encoding='utf-8') as f:
        f.write(helper_source)
    run(['php', helper_path, php_path, json.dumps(list(dev_packages))])
    os.remove(helper_path)


def step2_prepare_autoload(changed_files):
    if not composer_lock_changed(changed_files):
        info('Adim 2/7: composer.lock degismedi, autoload rejenerasyonu atlanidi.')
        return False

    info('Adim 2/7: composer.lock degisti, temiz (--no-dev) autoload uretiliyor...')
    # --no-scripts sart: bu komutun post-autoload-dump kancasi normalde
    # "artisan package:discover" calistirir, o da yerel (dev-dahil)
    # installed.json'da hala listeli olan dev-only paketleri (ör.
    # laravel/sail) instantiate etmeye calisip patlar - tam bugunku 2.
    # cokmenin yerel bir yan etkisi. Paket kesfi zaten production'da ayrica
    # /_ops/package-discover ile, sunucunun kendi --no-dev installed.json'undan
    # yapiliyor; yerelde hic gerekmiyor.
    run(['php', 'composer.phar', 'dump-autoload', '--no-dev', '--optimize', '--no-scripts'])
    filter_installed_files_for_no_dev()

    leaked = dev_only_paketler_sizdi_mi()
    if leaked:
        details = '\n'.join(f'  - {pkg} -> {f}' for pkg, f in leaked)
        fail(
            'GUVENLIK KONTROLU BASARISIZ: --no-dev autoload haritasinda dev-only paket izi bulundu:\n'
            f'{details}\n'
            'Bu, bugunku 1. cokmenin (myclabs/deep-copy) aynisidir - deploy DURDURULDU.'
        )
    ok('Autoload haritasi temiz, dev-only paket sizintisi yok.')
    return True


def run_quiet(cmd, cwd=APP_ROOT):
    """run()'un sessiz hali - buyuk/gurultulu ciktilar (ör. composer.lock
    JSON dump'i) icin, konsolu kirletmeden calistirir."""
    return subprocess.run(
        cmd, cwd=cwd, capture_output=True, text=True,
        encoding='utf-8', errors='replace', shell=(os.name == 'nt'),
    )


def get_composer_lock_packages(ref):
    result = run_quiet(['git', 'show', f'{ref}:composer.lock'])
    if result.returncode != 0:
        return {}
    data = json.loads(result.stdout)
    packages = {}
    for pkg in data.get('packages', []):
        packages[pkg['name']] = pkg['version']
    return packages


def get_vendor_dirs_to_upload(from_sha):
    old = get_composer_lock_packages(from_sha)
    new = get_composer_lock_packages('HEAD')
    to_upload = []
    for name, version in new.items():
        if name not in old or old[name] != version:
            to_upload.append(name)
    return to_upload


def connect_ftp(env):
    ftp = ftplib.FTP()
    ftp.connect(env['FTP_HOST'], 21, timeout=30)
    ftp.login(env['FTP_USER'], env['FTP_PASS'])
    return ftp


def ensure_dirs(ftp, doc_root, rel_path):
    parts = rel_path.replace('\\', '/').split('/')[:-1]
    current = doc_root
    for part in parts:
        current = current + '/' + part
        try:
            ftp.mkd(current)
        except ftplib.error_perm:
            pass


def upload_file(ftp, doc_root, rel_path):
    local_path = os.path.join(APP_ROOT, rel_path)
    remote_path = doc_root + '/' + rel_path.replace('\\', '/')
    ensure_dirs(ftp, doc_root, rel_path)
    with open(local_path, 'rb') as f:
        ftp.storbinary(f'STOR {remote_path}', f)


def delete_file(ftp, doc_root, rel_path):
    remote_path = doc_root + '/' + rel_path.replace('\\', '/')
    try:
        ftp.delete(remote_path)
    except ftplib.error_perm:
        pass


def upload_tree(ftp, doc_root, rel_dir):
    local_dir = os.path.join(APP_ROOT, rel_dir)
    count = 0
    for dirpath, _dirnames, filenames in os.walk(local_dir):
        rel_dirpath = os.path.relpath(dirpath, APP_ROOT)
        for fname in filenames:
            rel_file = os.path.join(rel_dirpath, fname)
            upload_file(ftp, doc_root, rel_file)
            count += 1
    return count


VENDOR_AUTOLOAD_FILES = [
    'vendor/autoload.php',
    'vendor/composer/autoload_classmap.php',
    'vendor/composer/autoload_static.php',
    'vendor/composer/autoload_real.php',
    'vendor/composer/autoload_files.php',
    'vendor/composer/autoload_namespaces.php',
    'vendor/composer/autoload_psr4.php',
    'vendor/composer/installed.php',
    'vendor/composer/installed.json',
    'vendor/composer/platform_check.php',
]


def step3_upload(env, changed_files, autoload_regenerated, from_sha):
    info('Adim 3/7: dosyalar FTP ile 3 doc root\'a yukleniyor...')
    ftp = connect_ftp(env)

    for doc_root in env['DOC_ROOTS']:
        print(f'  === {doc_root} ===')

        # public/build/ .gitignore'da (Vite build ciktisi, versiyon kontrolune
        # alinmiyor) - git diff bunu hic yakalayamaz, bu yuzden her deploy'da
        # tamamen senkronize edilir (kucuk bir klasor, maliyeti dusuk).
        build_dir = os.path.join(APP_ROOT, 'public', 'build')
        if os.path.isdir(build_dir):
            n = upload_tree(ftp, doc_root, 'public/build')
            print(f'    - public/build: {n} dosya senkronize edildi')

        for status, path in changed_files:
            if status == 'D':
                delete_file(ftp, doc_root, path)
                print(f'    - silindi: {path}')
            else:
                upload_file(ftp, doc_root, path)
                print(f'    - yuklendi: {path}')

        if autoload_regenerated:
            for rel in VENDOR_AUTOLOAD_FILES:
                upload_file(ftp, doc_root, rel)
            print(f'    - autoload dosyalari yuklendi ({len(VENDOR_AUTOLOAD_FILES)} dosya)')

            vendor_dirs = get_vendor_dirs_to_upload(from_sha)
            for pkg_name in vendor_dirs:
                rel_dir = 'vendor/' + pkg_name
                if os.path.isdir(os.path.join(APP_ROOT, rel_dir)):
                    n = upload_tree(ftp, doc_root, rel_dir)
                    print(f'    - {rel_dir}: {n} dosya yuklendi (yeni/guncellenmis paket)')

    ftp.quit()
    ok('Yukleme tamamlandi.')


def call_ops(env, domain, action):
    url = f'https://{domain}/_ops/{action}'
    req = urllib.request.Request(url, method='POST', headers={'Authorization': f'Bearer {env["OPS_SECRET"]}'})
    try:
        with urllib.request.urlopen(req, timeout=60) as resp:
            return resp.status, resp.read().decode('utf-8', errors='ignore')
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode('utf-8', errors='ignore')
    except Exception as e:
        return None, str(e)


def step4_run_ops(env):
    info('Adim 4/7: migrate + package-discover + cache-refresh tetikleniyor...')
    for domain in env['DOMAINS']:
        for action in ['migrate', 'package-discover', 'cache-refresh']:
            status, body = call_ops(env, domain, action)
            if status != 200:
                fail(f'{domain} -> /_ops/{action} basarisiz (status={status}):\n{body[:500]}')
            print(f'  {domain} -> /_ops/{action}: OK')
    ok('Tum domainlerde migrate/cache-refresh tamamlandi.')


def step5_health_check(env):
    info('Adim 5/7: /_saglik kontrolu...')
    all_ok = True
    for domain in env['DOMAINS']:
        url = f'https://{domain}/_saglik'
        try:
            with urllib.request.urlopen(url, timeout=30) as resp:
                data = json.loads(resp.read().decode('utf-8'))
        except Exception as e:
            print(f'  {domain}: ULASILAMADI ({e})')
            all_ok = False
            continue
        if data.get('status') == 'ok':
            print(f'  {domain}: OK')
        else:
            print(f'  {domain}: FAIL -> {data.get("checks")}')
            all_ok = False

    if not all_ok:
        fail(
            'Bir veya daha fazla domain saglik kontrolunu gecemedi. Dosyalar zaten yuklendi, '
            'ELLE mudahale gerekiyor (onceki .last_deployed_sha\'ya git checkout + script\'i '
            'tekrar calistirmak en hizli kurtarma yolu).'
        )
    ok('3 domain de saglikli.')


def step6_update_marker():
    head = run(['git', 'rev-parse', 'HEAD']).stdout.strip()
    with open(LAST_SHA_PATH, 'w', encoding='utf-8') as f:
        f.write(head + '\n')
    ok(f'deploy/.last_deployed_sha guncellendi -> {head} (commit etmeyi unutmayin).')


def step7_restore_local_vendor(autoload_regenerated):
    if not autoload_regenerated:
        return
    info('Adim 7/7: yerel gelistirme ortami (dev dahil vendor) geri yukleniyor...')
    run(['php', 'composer.phar', 'install'])
    ok('Yerel vendor eski haline dondu.')


def main():
    env = load_deploy_env()
    from_sha = get_last_deployed_sha()

    step1_run_tests()

    changed_files = get_changed_files(from_sha)
    if not changed_files:
        print('Son deploy\'dan beri (denylist disinda) degisen dosya yok, yapilacak bir sey kalmadi.')
        return

    print(f'\n{len(changed_files)} dosya degisecek:')
    for status, path in changed_files:
        print(f'  {status} {path}')
    print()

    autoload_regenerated = step2_prepare_autoload(changed_files)
    step3_upload(env, changed_files, autoload_regenerated, from_sha)
    step4_run_ops(env)
    step5_health_check(env)
    step6_update_marker()
    step7_restore_local_vendor(autoload_regenerated)

    print('\nDEPLOY BASARILI.')


if __name__ == '__main__':
    main()
