<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();
global $USER;

if (!local_syllabusbridge_can_site_sso()) {
    throw new moodle_exception('cannotusesso', 'local_syllabusbridge');
}

$appurl = trim((string) get_config('local_syllabusbridge', 'appurl'));
$secret = (string) get_config('local_syllabusbridge', 'sharedsecret');
if ($appurl === '' || $secret === '') {
    throw new moodle_exception('errorconfig', 'local_syllabusbridge');
}
$appurl = rtrim($appurl, '/');

$email = strtolower(trim((string) ($USER->email ?? '')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new moodle_exception('erroremail', 'local_syllabusbridge');
}

$app_role = local_syllabusbridge_user_app_role_for_sso();

$payload = [
    'v' => 3,
    'iat' => time(),
    'moodle_user_id' => (int) $USER->id,
    'email' => $email,
    'firstname' => (string) ($USER->firstname ?? ''),
    'lastname' => (string) ($USER->lastname ?? ''),
    'app_role' => $app_role,
];

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    throw new moodle_exception('errorconfig', 'local_syllabusbridge');
}

$p = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
$sig = hash_hmac('sha256', $json, $secret);

$target = $appurl . '/moodle_sso.php';
if (!preg_match('#\Ahttps?://#i', $target)) {
    throw new moodle_exception('errorconfig', 'local_syllabusbridge');
}

\core\session\manager::write_close();

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Robots-Tag: noindex, nofollow');

$action = htmlspecialchars($target, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$pesc = htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$sesc = htmlspecialchars($sig, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo get_string('navsso', 'local_syllabusbridge'); ?></title>
</head>
<body>
<p><?php echo get_string('navsso', 'local_syllabusbridge'); ?>…</p>
<form method="post" action="<?php echo $action; ?>" id="moodlebridgesso">
    <input type="hidden" name="p" value="<?php echo $pesc; ?>">
    <input type="hidden" name="s" value="<?php echo $sesc; ?>">
    <noscript>
        <p><button type="submit"><?php echo get_string('navsso', 'local_syllabusbridge'); ?></button></p>
    </noscript>
</form>
<script>
document.getElementById('moodlebridgesso').submit();
</script>
</body>
</html>
<?php
exit;
