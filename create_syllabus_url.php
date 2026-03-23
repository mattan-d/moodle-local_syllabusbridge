<?php
/**
 * API לשרת האפליקציה: יוצר או מעדכן פעילות URL בקורס עם קישור ל-PDF חתום.
 * POST JSON + כותרת X-Syllabusbridge-Signature: hex(HMAC-SHA256(body, sharedsecret))
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_SYLLABUSBRIDGE_SIGNATURE'] ?? '';

$secret = (string) get_config('local_syllabusbridge', 'sharedsecret');
if ($secret === '' || $raw === '' || !hash_equals(hash_hmac('sha256', $raw, $secret), $sig)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'invalid_signature']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data) || (int) ($data['v'] ?? 0) !== 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad_payload']);
    exit;
}

$iat = (int) ($data['iat'] ?? 0);
if ($iat <= 0 || abs(time() - $iat) > 300) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'stale']);
    exit;
}

$moodlecourseid = (int) ($data['moodle_course_id'] ?? 0);
$appsyllabusid = (int) ($data['app_syllabus_id'] ?? 0);
$pdfurl = trim((string) ($data['pdf_url'] ?? ''));
$name = trim((string) ($data['activity_name'] ?? 'סילבוס הקורס'));

if ($moodlecourseid <= 0 || $appsyllabusid <= 0 || $pdfurl === '' || !preg_match('#\Ahttps?://#i', $pdfurl)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_fields']);
    exit;
}

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/url/lib.php');
require_once($CFG->dirroot . '/lib/resourcelib.php');

$syncuserid = (int) get_config('local_syllabusbridge', 'syncuserid');
if ($syncuserid <= 0) {
    $syscontext = context_system::instance();
    $admins = get_users_by_capability($syscontext, 'moodle/site:config', 'u.*', 'u.id ASC', 0, 1, '', '', true, true);
    $adminuser = reset($admins);
    if (!$adminuser) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'no_sync_user']);
        exit;
    }
    $syncuserid = (int) $adminuser->id;
}

$user = core_user::get_user($syncuserid, '*', MUST_EXIST);
if (!$user || $user->deleted) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'bad_sync_user']);
    exit;
}

\core\session\manager::login_user($user);

$course = get_course($moodlecourseid);
$context = context_course::instance($course->id);
require_capability('moodle/course:manageactivities', $context);

$idnumber = 'syllabusapp_' . $appsyllabusid;

$cm = $DB->get_record_sql(
    "SELECT cm.* FROM {course_modules} cm
     JOIN {modules} m ON m.id = cm.module AND m.name = :modname
     WHERE cm.course = :courseid AND cm.idnumber = :idn",
    ['modname' => 'url', 'courseid' => $course->id, 'idn' => $idnumber]
);

if ($cm) {
    try {
        course_delete_module((int) $cm->id, false);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'delete_failed']);
        exit;
    }
}

$sectionnum = local_syllabusbridge_resolve_url_section($course->id);

[$urlmodule, $modcontext, $sectioninfo] = can_add_moduleinfo($course, 'url', $sectionnum);

$moduleinfo = new stdClass();
$moduleinfo->modulename = 'url';
$moduleinfo->module = $urlmodule->id;
$moduleinfo->course = $course->id;
$moduleinfo->section = $sectionnum;
$moduleinfo->visible = 1;
$moduleinfo->visibleoncoursepage = 1;
$moduleinfo->name = $name !== '' ? $name : get_string('modulename', 'url');
$moduleinfo->intro = '';
$moduleinfo->introformat = FORMAT_HTML;
$moduleinfo->externalurl = $pdfurl;
$moduleinfo->display = RESOURCELIB_DISPLAY_OPEN;
$moduleinfo->parameters = '';
$moduleinfo->cmidnumber = $idnumber;

try {
    add_moduleinfo($moduleinfo, $course);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'create_failed']);
    exit;
}

$cmnew = $DB->get_record_sql(
    "SELECT cm.* FROM {course_modules} cm
     JOIN {modules} m ON m.id = cm.module AND m.name = :modname
     WHERE cm.course = :courseid AND cm.idnumber = :idn",
    ['modname' => 'url', 'courseid' => $course->id, 'idn' => $idnumber]
);

rebuild_course_cache($course->id, true);

echo json_encode([
    'ok' => true,
    'cmid' => $cmnew ? (int) $cmnew->id : 0,
    'recreated' => true,
    'section' => $sectionnum,
]);
