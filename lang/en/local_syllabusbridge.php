<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Syllabus standalone app bridge';
$string['navlaunch'] = 'Syllabus management app';
$string['appurl'] = 'Standalone app base URL';
$string['appurl_desc'] = 'Full URL to the app public folder, without trailing slash (e.g. https://example.org/standalone/public).';
$string['sharedsecret'] = 'Shared secret (HMAC)';
$string['sharedsecret_desc'] = 'Must match MOODLE_BRIDGE_SECRET in standalone config.php.';
$string['errorconfig'] = 'The syllabus app URL or shared secret is not configured. Ask your administrator.';
$string['erroremail'] = 'Your Moodle account must have a valid email address to use the syllabus app.';
$string['cannotusebridge'] = 'You do not have permission to open the syllabus app for this course (teacher or course editor access is required).';
$string['syncuserid'] = 'User ID for course module creation';
$string['syncuserid_desc'] = 'Moodle user ID used to create/update the URL activity (must have moodle/course:manageactivities in target courses). Leave 0 to use the first site administrator found.';
$string['urlsection'] = 'Course section number for syllabus link';
$string['urlsection_desc'] = '-1 = auto (first section whose name contains "מבוא" or "intro"), 0 = general top section, 1 = first topic, etc.';
