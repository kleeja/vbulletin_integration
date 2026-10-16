<?php
//
// vbulletin_integration, its guide on the help page of the control panel
// English
//
// The guide is built by the admin_help_guides hook in init.php, the texts here can have HTML.
//  - a list is numbered: VBULLETIN_INTR_HELP_TIP_1, VBULLETIN_INTR_HELP_TIP_2 ... it ends at the first missing number,
//    so an item can be added or removed without touching the code
//  - VBULLETIN_INTR_HELP_{LIST}_TITLE is the title of the list, a list without it takes the title that Kleeja gives its type
//  - questions and answers are VBULLETIN_INTR_HELP_FAQ_Q_1 and VBULLETIN_INTR_HELP_FAQ_A_1
//  - names in <strong> are written as the control panel shows them: the words of Kleeja are in lang/en/,
//    the words of the plugin in vbulletin_integration_translations() of init.php
//

return [
    'VBULLETIN_INTR_HELP_TITLE' => 'vBulletin Integration',
    'VBULLETIN_INTR_HELP_INTRO' =>
        'Let the members of your vBulletin forum sign in to Kleeja with their forum username and password, instead of creating separate Kleeja accounts.',

    //
    // what the plugin does
    //
    'VBULLETIN_INTR_HELP_FEATURE_1' =>
        'Members sign in with their forum accounts. Kleeja only reads the forum database and never changes it.',
    'VBULLETIN_INTR_HELP_FEATURE_2' =>
        'Forum administrators join the Kleeja administrators group and can open the control panel. Other members join the members group, and banned members can’t sign in.',
    'VBULLETIN_INTR_HELP_FEATURE_3' =>
        'The <strong>Register</strong> page, password recovery and profile editing send members to the matching pages of the forum.',
    'VBULLETIN_INTR_HELP_FEATURE_4' =>
        'Kleeja reads the database details from the forum’s <code>config.php</code> when the forum is on the same server, or uses the details you enter.',
    'VBULLETIN_INTR_HELP_FEATURE_5' =>
        'The <strong>Connection test</strong> tab checks the connection details, the database, the members table and the forum administrators, and says how to fix each problem it finds.',
    'VBULLETIN_INTR_HELP_FEATURE_6' => 'Works with vBulletin 5 and later, and with vBulletin 3 and 4.',

    //
    // how to use it
    //
    'VBULLETIN_INTR_HELP_STEP_TITLE' => 'Connect Kleeja to your forum',
    'VBULLETIN_INTR_HELP_STEP_1' =>
        'Open <strong>vBulletin Integration</strong>, enter the <strong>Forum address</strong>, and choose the <strong>vBulletin version</strong>.',
    'VBULLETIN_INTR_HELP_STEP_2' =>
        'If the forum is on this server, choose <strong>From the forum’s config.php</strong>, then enter the <strong>Forum folder</strong> and the <strong>Config file</strong>. Otherwise, choose <strong>Enter them here</strong> and enter the database details.',
    'VBULLETIN_INTR_HELP_STEP_3' =>
        'Click <strong>Save settings</strong>, open the <strong>Connection test</strong> tab, and click <strong>Run the test</strong>. Fix what it reports until every check passes.',
    'VBULLETIN_INTR_HELP_STEP_4' =>
        'Click <strong>Test and turn on</strong>. You are signed out, so sign in again with the account of a forum administrator.',

    //
    // common questions
    //
    'VBULLETIN_INTR_HELP_FAQ_Q_1' => 'I can’t open the control panel after turning the integration on. What can I do?',
    'VBULLETIN_INTR_HELP_FAQ_A_1' =>
        'Only members whose primary group in the forum is Administrators can open the control panel. If no such account works, or the forum database is down, add <code>define(\'DISABLE_INTR\', true);</code> to the end of <code>config.php</code>. Kleeja accounts work again until you remove that line.',
    'VBULLETIN_INTR_HELP_FAQ_Q_2' => 'A member can’t sign in. What should I check?',
    'VBULLETIN_INTR_HELP_FAQ_A_2' =>
        'Check that the member isn’t banned and can sign in to the forum, then run the <strong>Connection test</strong>. When Kleeja can’t reach the forum database, nobody can sign in.',
    'VBULLETIN_INTR_HELP_FAQ_Q_3' => 'Names show as question marks or odd symbols. How do I fix them?',
    'VBULLETIN_INTR_HELP_FAQ_A_3' =>
        'Set the <strong>Character set</strong> to the one the forum uses. Older forums that store UTF-8 text in latin1 tables need <code>latin1</code>. With <strong>From the forum’s config.php</strong>, Kleeja uses the <code>$config[\'Mysqli\'][\'charset\']</code> of the forum, or utf8 when it is not set.',
    'VBULLETIN_INTR_HELP_FAQ_Q_4' => 'What happens to the existing Kleeja accounts?',
    'VBULLETIN_INTR_HELP_FAQ_A_4' =>
        'They stay in the database, but nobody can sign in with them while the integration is on. Turning it off brings them back.',

    //
    // good to know
    //
    'VBULLETIN_INTR_HELP_TIP_1' =>
        'Give Kleeja a database user of its own with only the SELECT permission on the <code>user</code> table of the forum. Kleeja needs nothing more.',
    'VBULLETIN_INTR_HELP_TIP_2' =>
        'While the integration is on, new connection details are saved only if the test passes with them, so a typo can’t lock everyone out.',
    'VBULLETIN_INTR_HELP_TIP_3' =>
        'Use the <code>https://</code> address of the forum when it has one, so members reset their passwords over a secure connection.',

    //
    // be careful
    //
    'VBULLETIN_INTR_HELP_WARNING_1' =>
        'Files belong to user numbers. Once the integration is on, the files that Kleeja account number 7 uploaded belong to forum member number 7, and the reverse happens when you turn it off.',
    'VBULLETIN_INTR_HELP_WARNING_2' =>
        'Administrators are signed out as soon as you turn the integration on or off. Other members who are signed in at that moment keep their previous account until they sign out or their sign-in expires.',
    'VBULLETIN_INTR_HELP_WARNING_3' =>
        'Only a founder can open this page, because the integration decides who can sign in and who becomes a founder. Forum administrators become founders.',
];
