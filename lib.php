<?php
defined('MOODLE_INTERNAL') || die();

/**
 * מורים / עורכי קורס / מנהלים — לא סטודנטים.
 *
 * @param context_course $coursecontext
 */
function local_syllabusbridge_can_use_bridge(context_course $coursecontext): bool {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return false;
    }

    if (empty(trim((string) get_config('local_syllabusbridge', 'appurl')))) {
        return false;
    }
    if (empty((string) get_config('local_syllabusbridge', 'sharedsecret'))) {
        return false;
    }

    if (is_siteadmin()) {
        return true;
    }

    if (has_capability('moodle/course:update', $coursecontext)) {
        return true;
    }

    foreach (get_user_roles($coursecontext, $USER->id, true) as $role) {
        if (in_array($role->shortname, ['teacher', 'editingteacher', 'manager', 'coordinator'], true)) {
            return true;
        }
    }

    return false;
}

/**
 * כניסה לניהול הסילבוס דרך קישור אתרי (ללא קורס).
 *
 * מותר: מנהל אתר, בעלי יכולת local/syllabusbridge:sso בהקשר מערכת,
 * או כל מי שיכול להשתמש בגשר לפחות בקורס אחד שאליו הוא רשום.
 */
function local_syllabusbridge_can_site_sso(): bool {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return false;
    }

    if (empty(trim((string) get_config('local_syllabusbridge', 'appurl')))) {
        return false;
    }
    if (empty((string) get_config('local_syllabusbridge', 'sharedsecret'))) {
        return false;
    }

    if (is_siteadmin()) {
        return true;
    }

    $syscontext = context_system::instance();
    if (has_capability('local/syllabusbridge:sso', $syscontext)) {
        return true;
    }

    foreach (enrol_get_all_users_courses($USER->id, true, ['id']) as $c) {
        $coursecontext = context_course::instance((int) $c->id);
        if (local_syllabusbridge_can_use_bridge($coursecontext)) {
            return true;
        }
    }

    return false;
}

/**
 * תפקיד ל-payload SSO (v3): כמו launch.php — עורך קורס = editor, מורה = viewer.
 * אם אין קורס מתאים אבל יש יכולת SSO במערכת — editor (סטאף ללא קורס).
 *
 * @return string admin|editor|viewer
 */
function local_syllabusbridge_user_app_role_for_sso(): string {
    global $USER;

    if (is_siteadmin()) {
        return 'admin';
    }

    $foundBridgeCourse = false;
    foreach (enrol_get_all_users_courses($USER->id, true, ['id']) as $c) {
        $ctx = context_course::instance((int) $c->id);
        if (!local_syllabusbridge_can_use_bridge($ctx)) {
            continue;
        }
        $foundBridgeCourse = true;
        if (has_capability('moodle/course:update', $ctx)) {
            return 'editor';
        }
    }

    if ($foundBridgeCourse) {
        return 'viewer';
    }

    if (has_capability('local/syllabusbridge:sso', context_system::instance())) {
        return 'editor';
    }

    return 'viewer';
}

/**
 * תפריט הקורס (מגירה / ניווט קורס ב-Boost) — קישור גלוי למורים.
 *
 * @param global_navigation $navigation
 */
function local_syllabusbridge_extend_navigation(global_navigation $navigation) {
    global $PAGE;

    if (!isset($PAGE->course->id) || (int) $PAGE->course->id === SITEID) {
        return;
    }
    if ($PAGE->context->contextlevel < CONTEXT_COURSE) {
        return;
    }

    $coursecontext = context_course::instance($PAGE->course->id);
    if (!local_syllabusbridge_can_use_bridge($coursecontext)) {
        return;
    }

    $coursenode = $navigation->find($PAGE->course->id, navigation_node::TYPE_COURSE);
    if (!$coursenode) {
        return;
    }

    $url = new moodle_url('/local/syllabusbridge/launch.php', ['id' => $PAGE->course->id]);
    $node = $coursenode->add(
        get_string('navlaunch', 'local_syllabusbridge'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'syllabusapp',
        new pix_icon('i/report', '')
    );
    $node->showinflatnavigation = true;
}

/**
 * תחת "ניהול קורס" (גלגל שיניים) — כמו תבנית local_syllabusmanagement.
 *
 * @param settings_navigation $navigation
 * @param context $context
 */
function local_syllabusbridge_extend_settings_navigation(settings_navigation $navigation, context $context) {
    global $PAGE;

    if ($context->contextlevel != CONTEXT_COURSE || (int) $PAGE->course->id === SITEID) {
        return;
    }

    $coursecontext = context_course::instance($PAGE->course->id);
    if (!local_syllabusbridge_can_use_bridge($coursecontext)) {
        return;
    }

    $courseadmin = $navigation->get('courseadmin');
    if (!$courseadmin) {
        return;
    }

    $url = new moodle_url('/local/syllabusbridge/launch.php', ['id' => $PAGE->course->id]);
    $courseadmin->add(
        get_string('navlaunch', 'local_syllabusbridge'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'syllabusapp',
        new pix_icon('i/report', '')
    );
}

/**
 * מספר יחידה לשיבוץ פעילות קישור הסילבוס (0 = כללי, 1 = יחידה ראשונה, וכו׳).
 *
 * @param int $courseid
 * @return int
 */
function local_syllabusbridge_resolve_url_section($courseid) {
    global $DB;

    $cfg = get_config('local_syllabusbridge', 'urlsection');
    if ($cfg !== null && $cfg !== false && $cfg !== '') {
        $n = (int) $cfg;
        if ($n >= 0) {
            return $n;
        }
    }

    $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC', 'section, name');
    foreach ($sections as $sec) {
        $label = trim(strip_tags((string) ($sec->name ?? '')));
        if ($label !== '') {
            if (mb_stripos($label, 'מבוא') !== false) {
                return (int) $sec->section;
            }
            if (stripos($label, 'intro') !== false) {
                return (int) $sec->section;
            }
        }
    }

    return 1;
}

/**
 * מרצים ועורכי קורס (תפקידי teacher / editingteacher) לשליחה לאפליקציית הסילבוס.
 *
 * @param int $courseid
 * @return list<array{moodle_user_id:int,firstname:string,lastname:string,email:string}>
 */
function local_syllabusbridge_get_course_teachers_for_payload($courseid) {
    global $DB;

    $courseid = (int) $courseid;
    if ($courseid <= 0) {
        return [];
    }

    $ctx = context_course::instance($courseid);
    $seen = [];
    $out = [];

    foreach (['editingteacher', 'teacher'] as $shortname) {
        $role = $DB->get_record('role', ['shortname' => $shortname], 'id', IGNORE_MISSING);
        if (!$role) {
            continue;
        }
        // false = רק שיבוץ תפקיד בהקשר הקורס הזה, בלי «ירושה» מאתר/קטגוריה (אחרת מופיעים מנהלים גלובליים וכו׳).
        $users = get_role_users(
            (int) $role->id,
            $ctx,
            false,
            'u.id, u.firstname, u.lastname, u.email, u.deleted',
            'u.lastname ASC, u.firstname ASC',
            true
        );
        foreach ($users as $u) {
            if (!empty($u->deleted)) {
                continue;
            }
            $id = (int) $u->id;
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $em = strtolower(trim((string) ($u->email ?? '')));
            if ($em === '' || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $out[] = [
                'moodle_user_id' => $id,
                'firstname' => (string) ($u->firstname ?? ''),
                'lastname' => (string) ($u->lastname ?? ''),
                'email' => $em,
            ];
        }
    }

    return $out;
}

/**
 * שדות מותאמים של הקורס במודל (שם תצוגה, shortname, ערך מוצג).
 *
 * @param int $courseid
 * @return list<array{shortname:string,name:string,value:string}>
 */
function local_syllabusbridge_get_course_custom_fields_for_payload($courseid) {
    $courseid = (int) $courseid;
    if ($courseid <= 0) {
        return [];
    }

    if (!class_exists(\core_course\customfield\course_handler::class)
        || !class_exists(\core_customfield\api::class)) {
        return [];
    }

    $out = [];

    try {
        $handler = \core_course\customfield\course_handler::create();
        $editablefields = $handler->get_editable_fields($courseid);
        $records = \core_customfield\api::get_instance_fields_data($editablefields, $courseid);
        foreach ($records as $d) {
            $field = $d->get_field();
            $sn = trim((string) $field->get('shortname'));
            if ($sn === '' || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/', $sn)) {
                continue;
            }
            if (!$handler->can_view($field, $courseid)) {
                continue;
            }
            $name = (string) $field->get('name');
            $val = '';
            if (method_exists($d, 'export_value')) {
                $val = (string) $d->export_value();
            } else {
                $val = (string) $d->get('value');
            }
            $val = trim(html_to_text($val, 0));
            if (function_exists('mb_strlen') && mb_strlen($val, 'UTF-8') > 2000) {
                $val = mb_substr($val, 0, 2000, 'UTF-8');
            } else if (strlen($val) > 2000) {
                $val = substr($val, 0, 2000);
            }
            $out[] = [
                'shortname' => $sn,
                'name' => $name,
                'value' => $val,
            ];
        }
    } catch (\Throwable $e) {
        return [];
    }

    return $out;
}

/**
 * מטא-נתונים נוספים על הקורס לחתימת הגשר.
 *
 * @param stdClass $course רשומת get_course
 * @return array<string,mixed>
 */
function local_syllabusbridge_get_course_meta_for_payload($course) {
    $catid = (int) ($course->category ?? 0);
    $catname = '';
    if ($catid > 0) {
        try {
            $c = \core_course_category::get($catid, MUST_EXIST, true);
            $catname = $c->get_formatted_name();
        } catch (\Throwable $e) {
            $catname = '';
        }
    }

    return [
        'course_category_id' => $catid,
        'course_category_name' => $catname,
        'course_startdate' => (int) ($course->startdate ?? 0),
        'course_enddate' => (int) ($course->enddate ?? 0),
        'course_format' => (string) ($course->format ?? ''),
        'course_visible' => (int) (!empty($course->visible)),
        'course_lang' => (string) ($course->lang ?? ''),
        'course_theme' => (string) ($course->theme ?? ''),
    ];
}
