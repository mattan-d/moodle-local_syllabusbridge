<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('id', PARAM_INT);
require_login();
global $USER;

$course = get_course($courseid);
$context = context_course::instance($courseid);

if (!local_syllabusbridge_can_use_bridge($context)) {
    throw new moodle_exception('cannotusebridge', 'local_syllabusbridge');
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

$app_role = 'viewer';
if (is_siteadmin()) {
    $app_role = 'admin';
} else if (has_capability('moodle/course:update', $context)) {
    $app_role = 'editor';
}

$summary = '';
if (!empty($course->summary)) {
    try {
        $sformat = isset($course->summaryformat) ? (int) $course->summaryformat : FORMAT_HTML;
        $summary = format_text($course->summary, $sformat, [
            'context' => $context,
            'noclean' => false,
        ]);
        $summary = html_to_text($summary, 0);
        $summax = 2000;
        if (class_exists('core_text')) {
            if (core_text::strlen($summary) > $summax) {
                $summary = core_text::substr($summary, 0, $summax);
            }
        } else if (function_exists('mb_strlen') && mb_strlen($summary, 'UTF-8') > $summax) {
            $summary = mb_substr($summary, 0, $summax, 'UTF-8');
        } else if (strlen($summary) > $summax) {
            $summary = substr($summary, 0, $summax);
        }
    } catch (\Throwable $e) {
        $summary = '';
    }
}

$meta = local_syllabusbridge_get_course_meta_for_payload($course);
$teachers = local_syllabusbridge_get_course_teachers_for_payload((int) $course->id);
$customfields = local_syllabusbridge_get_course_custom_fields_for_payload((int) $course->id);

$payload = [
    'v' => 2,
    'iat' => time(),
    'moodle_user_id' => (int) $USER->id,
    'email' => $email,
    'firstname' => (string) ($USER->firstname ?? ''),
    'lastname' => (string) ($USER->lastname ?? ''),
    'app_role' => $app_role,
    'moodle_course_id' => (int) $course->id,
    'course_fullname' => (string) $course->fullname,
    'course_shortname' => (string) $course->shortname,
    'course_idnumber' => (string) ($course->idnumber ?? ''),
    'summary' => $summary,
    'course_teachers' => $teachers,
    'course_custom_fields' => $customfields,
] + $meta;

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    throw new moodle_exception('errorconfig', 'local_syllabusbridge');
}

$p = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
$sig = hash_hmac('sha256', $json, $secret);

$bridgepath = $appurl . '/moodle_bridge.php';
if (!preg_match('#\Ahttps?://#i', $bridgepath)) {
    throw new moodle_exception('errorconfig', 'local_syllabusbridge');
}

\core\session\manager::write_close();

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Robots-Tag: noindex, nofollow');

$action = htmlspecialchars($bridgepath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$pesc = htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$sesc = htmlspecialchars($sig, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo get_string('navlaunch', 'local_syllabusbridge'); ?></title>
</head>
<body>
<p><?php echo get_string('navlaunch', 'local_syllabusbridge'); ?>…</p>
<form method="post" action="<?php echo $action; ?>" id="moodlebridge">
    <input type="hidden" name="p" value="<?php echo $pesc; ?>">
    <input type="hidden" name="s" value="<?php echo $sesc; ?>">
    <noscript>
        <p><button type="submit"><?php echo get_string('navlaunch', 'local_syllabusbridge'); ?></button></p>
    </noscript>
</form>
<script>
document.getElementById('moodlebridge').submit();
</script>
</body>
</html>
<?php
exit;
