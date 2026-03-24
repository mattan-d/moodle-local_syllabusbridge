<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/syllabusbridge:sso' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
        ],
    ],
];
