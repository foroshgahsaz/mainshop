<?php

/**
 * ساخت servicePassword رمزنگاری‌شده برای درگاه نوپی (CPG).
 *
 * الگوریتم: AES-128-CBC
 *   publicKey  = کلید ۱۶ بایتی (base64)
 *   privateKey = IV ۱۶ بایتی (base64)
 *
 * اجرا از خط فرمان:
 *   php docs/nopay-encrypt-password.php
 *   php docs/nopay-encrypt-password.php --password=C3456 --private-key='UwDrbOqLmWhk9ld5dsdmxQ=='
 *
 * یا در مرورگر باز کنید و فرم را پر کنید.
 */

declare(strict_types=1);

const DEFAULT_PUBLIC_KEY = 'WtX3qPVc1lrHX/+ug9iILQ==';

function nopayEncryptPassword(string $plainPassword, string $publicKeyB64, string $privateKeyB64): string
{
    $plainPassword = trim($plainPassword);
    $publicKeyB64 = preg_replace('/\s+/', '', trim($publicKeyB64)) ?? '';
    $privateKeyB64 = preg_replace('/\s+/', '', trim($privateKeyB64)) ?? '';

    if ($plainPassword === '') {
        throw new InvalidArgumentException('رمز عبور خالی است.');
    }

    $key = base64_decode($publicKeyB64, true);
    $iv = base64_decode($privateKeyB64, true);

    if ($key === false || strlen($key) !== 16) {
        throw new InvalidArgumentException('publicKey باید base64 معادل دقیقاً ۱۶ بایت باشد.');
    }

    if ($iv === false || strlen($iv) !== 16) {
        throw new InvalidArgumentException('privateKey باید base64 معادل دقیقاً ۱۶ بایت باشد (IV).');
    }

    $encrypted = openssl_encrypt($plainPassword, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);

    if ($encrypted === false || $encrypted === '') {
        throw new RuntimeException('رمزنگاری ناموفق بود.');
    }

    return base64_encode($encrypted);
}

function cliArgs(array $argv): array
{
    $out = [
        'username' => '',
        'password' => '',
        'public_key' => DEFAULT_PUBLIC_KEY,
        'private_key' => '',
        'merchant' => '',
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if (! str_starts_with($arg, '--')) {
            continue;
        }

        [$name, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, '');
        $map = [
            'username' => 'username',
            'user' => 'username',
            'password' => 'password',
            'pass' => 'password',
            'public-key' => 'public_key',
            'public_key' => 'public_key',
            'private-key' => 'private_key',
            'private_key' => 'private_key',
            'merchant' => 'merchant',
            'merchant-number' => 'merchant',
        ];

        if (isset($map[$name])) {
            $out[$map[$name]] = $value;
        }
    }

    return $out;
}

function prompt(string $label, string $default = ''): string
{
    $suffix = $default !== '' ? " [{$default}]" : '';
    echo $label.$suffix.': ';
    $line = fgets(STDIN);

    if ($line === false) {
        return $default;
    }

    $line = trim($line);

    return $line !== '' ? $line : $default;
}

$isCli = PHP_SAPI === 'cli';

if ($isCli) {
    $input = cliArgs($argv);

    if ($input['password'] === '' || $input['private_key'] === '') {
        echo "=== رمزنگاری رمز نوپی (AES-128-CBC) ===\n";
        $input['username'] = prompt('serviceUserName (اختیاری)', $input['username']);
        $input['password'] = prompt('servicePassword (متن خام)', $input['password']);
        $input['public_key'] = prompt('publicKey', $input['public_key'] ?: DEFAULT_PUBLIC_KEY);
        $input['private_key'] = prompt('privateKey (IV)', $input['private_key']);
        $input['merchant'] = prompt('merchantNumber (اختیاری)', $input['merchant']);
    }

    try {
        $encrypted = nopayEncryptPassword(
            $input['password'],
            $input['public_key'] !== '' ? $input['public_key'] : DEFAULT_PUBLIC_KEY,
            $input['private_key'],
        );
    } catch (Throwable $e) {
        fwrite(STDERR, 'خطا: '.$e->getMessage().PHP_EOL);
        exit(1);
    }

    echo PHP_EOL;
    echo "servicePassword (encrypted):\n{$encrypted}\n";

    if ($input['username'] !== '') {
        echo "serviceUserName: {$input['username']}\n";
    }

    if ($input['merchant'] !== '') {
        echo "merchantNumber: {$input['merchant']}\n";
    }

    echo "\nهمین مقدار encrypted را در /admin/nopay یا برای پشتیبانی بفرستید.\n";
    exit(0);
}

// --- مرورگر ---
$error = null;
$result = null;
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$publicKey = trim((string) ($_POST['public_key'] ?? DEFAULT_PUBLIC_KEY));
$privateKey = trim((string) ($_POST['private_key'] ?? ''));
$merchant = trim((string) ($_POST['merchant'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $result = nopayEncryptPassword($password, $publicKey !== '' ? $publicKey : DEFAULT_PUBLIC_KEY, $privateKey);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>رمزنگاری رمز نوپی</title>
    <style>
        body { font-family: Tahoma, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; background: #f6f7f9; color: #111; }
        label { display: block; margin: .75rem 0 .25rem; font-weight: 600; }
        input { width: 100%; padding: .6rem .7rem; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 8px; }
        button { margin-top: 1rem; padding: .7rem 1.2rem; border: 0; border-radius: 8px; background: #0f766e; color: #fff; font-weight: 700; cursor: pointer; }
        .box { background: #fff; border-radius: 12px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .err { color: #b91c1c; margin-top: 1rem; }
        .ok { margin-top: 1rem; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 1rem; border-radius: 8px; word-break: break-all; }
        code { font-family: ui-monospace, monospace; }
        .hint { color: #64748b; font-size: .9rem; margin-top: .35rem; }
    </style>
</head>
<body>
<div class="box">
    <h1>رمزنگاری رمز نوپی (AES-128-CBC)</h1>
    <p class="hint">مقادیر را از فایل‌های onboarding نوپی بگذارید. خروجی را در ادمین یا برای پشتیبانی بفرستید.</p>
    <form method="post">
        <label for="username">serviceUserName (اختیاری)</label>
        <input id="username" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" placeholder="cbazar">

        <label for="password">servicePassword (متن خام)</label>
        <input id="password" name="password" type="password" value="<?= htmlspecialchars($password, ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="public_key">publicKey</label>
        <input id="public_key" name="public_key" value="<?= htmlspecialchars($publicKey, ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="private_key">privateKey (IV)</label>
        <input id="private_key" name="private_key" value="<?= htmlspecialchars($privateKey, ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="merchant">merchantNumber (اختیاری)</label>
        <input id="merchant" name="merchant" value="<?= htmlspecialchars($merchant, ENT_QUOTES, 'UTF-8') ?>" placeholder="233333020">

        <button type="submit">ساخت رمز رمزنگاری‌شده</button>
    </form>

    <?php if ($error !== null): ?>
        <p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($result !== null): ?>
        <div class="ok">
            <div><strong>servicePassword (encrypted):</strong></div>
            <code><?= htmlspecialchars($result, ENT_QUOTES, 'UTF-8') ?></code>
            <?php if ($username !== ''): ?>
                <div style="margin-top:.75rem"><strong>serviceUserName:</strong> <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($merchant !== ''): ?>
                <div><strong>merchantNumber:</strong> <?= htmlspecialchars($merchant, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
