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
