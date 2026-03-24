<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Syllabus management bridge';
$string['navlaunch'] = 'Syllabus management';
$string['appurl'] = 'Syllabus management base URL';
$string['appurl_desc'] = 'Full URL to the management app public folder, with no trailing slash. Provided by M.R. CentricApp Ltd. (CentricApp services on centricapp.co.il), e.g. https://syllabus.centricapp.co.il/public or the exact URL supplied by the company.';
$string['sharedsecret'] = 'Shared secret (HMAC)';
$string['sharedsecret_desc'] = 'Must match MOODLE_BRIDGE_SECRET in the management app config.php. Obtain this secret from M.R. CentricApp Ltd.; do not expose it or send it over insecure channels.';
$string['errorconfig'] = 'The syllabus management URL or shared secret is not configured. Ask your administrator.';
$string['erroremail'] = 'Your Moodle account must have a valid email address to use syllabus management.';
$string['cannotusebridge'] = 'You do not have permission to open syllabus management for this course (teacher or course editor access is required).';
$string['syncuserid'] = 'User ID for course module creation';
$string['syncuserid_desc'] = 'Moodle user ID used to create/update the URL activity (must have moodle/course:manageactivities in target courses). Leave 0 to use the first site administrator found.';
$string['urlsection'] = 'Course section number for syllabus link';
$string['urlsection_desc'] = '-1 = auto (first section whose name contains "מבוא" or "intro"), 0 = general top section, 1 = first topic, etc.';
$string['navsso'] = 'Syllabus management (sign-in)';
$string['cannotusesso'] = 'You cannot use this sign-in link. You need teacher or course editor access in at least one enrolled course, or the site-level permission «Use site SSO link to syllabus management» from an administrator.';
$string['ssosettings'] = 'Direct sign-in link (SSO)';
$string['ssosettings_desc'] = 'Bookmark or place this address in a block or custom menu. Logged-in Moodle users who are allowed to use the bridge (or hold the SSO permission) open the syllabus app automatically. Link: <code>{$a->url}</code>. New users get a default role from Moodle; an app administrator can change roles under Users in the syllabus app.';
$string['syllabusbridge:sso'] = 'Use site SSO link to syllabus management';
