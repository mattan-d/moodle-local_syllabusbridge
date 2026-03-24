<?php
defined('MOODLE_INTERNAL') || die();

// תוספי local: Moodle מעביר לכאן את $hassiteconfig (לא $hideshow).
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_syllabusbridge', get_string('pluginname', 'local_syllabusbridge'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_syllabusbridge/appurl',
        get_string('appurl', 'local_syllabusbridge'),
        get_string('appurl_desc', 'local_syllabusbridge'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_syllabusbridge/sharedsecret',
        get_string('sharedsecret', 'local_syllabusbridge'),
        get_string('sharedsecret_desc', 'local_syllabusbridge'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_syllabusbridge/syncuserid',
        get_string('syncuserid', 'local_syllabusbridge'),
        get_string('syncuserid_desc', 'local_syllabusbridge'),
        '0',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_syllabusbridge/urlsection',
        get_string('urlsection', 'local_syllabusbridge'),
        get_string('urlsection_desc', 'local_syllabusbridge'),
        '-1',
        PARAM_INT
    ));

    $ssourl = (new moodle_url('/local/syllabusbridge/sso.php'))->out(false);
    $settings->add(new admin_setting_description(
        'local_syllabusbridge/sso_help',
        get_string('ssosettings', 'local_syllabusbridge'),
        get_string('ssosettings_desc', 'local_syllabusbridge', (object) ['url' => $ssourl])
    ));
}
