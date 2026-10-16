<?php
//
// vbulletin_integration, its page in the control panel: ?cp=vbulletin_settings
//

// not for directly open
if (!defined('IN_ADMIN')) {
    exit();
}

include_once __DIR__ . '/vbulletin_test.php';

//current template
$stylee = 'admin_vbulletin_settings';
$styleePath = __DIR__;

// the integration decides who signs in and who is a founder, so only a founder changes it, like the plugins
if ((int) ($userinfo['founder'] ?? 0) !== 1) {
    kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS'], basename(ADMIN_PATH));
}

// template variables
$action = basename(ADMIN_PATH) . '?cp=vbulletin_settings';
$vb_test_url = $action . '&amp;case=test';
$H_FORM_KEYS = kleeja_add_form_key('adm_vbulletin_settings');

$vb_settings = vbulletin_settings();
$vb_on = (int) $vb_settings['vbulletin_intr_enabled'] === 1;
$vb_tab_test = g('case') === 'test';
$vb_test_steps = $vb_errors = [];
$vb_alert = '';
$vb_alert_type = 'danger';

/**
 * save settings, they are kept as they were typed, update_config() encodes them once.
 * The settings are cached in a PHP file, and OPcache would run the old one for a while,
 * or until PHP restarts on a server that doesn't check the files. OPcache finds a relative path only while it exists
 */
$vb_save = function (array $values): void {
    $cached = PATH . 'cache/data_config.php';

    if (function_exists('opcache_invalidate') && file_exists($cached)) {
        @opcache_invalidate($cached, true);
    }

    foreach ($values as $name => $value) {
        update_config($name, (string) $value);
    }
};

$vb_passed = fn(array $steps): bool => !in_array('error', array_column($steps, 'status'), true);

if (ip('vb_action')) {
    if (!kleeja_check_form_key('adm_vbulletin_settings', 3600)) {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], $action);
    }

    switch (p('vb_action')) {
        // the test of the saved settings
        case 'test':
            $vb_tab_test = true;
            $vb_test_steps = vbulletin_integration_test($vb_settings);

            break;

        // it is turned on only when Kleeja can read the members of the forum.
        // The account of the admin is a Kleeja one, the next page signs it out
        case 'enable':
            // the members would be sent to the example address to register and to get a new password
            if ($vb_settings['vbulletin_intr_link'] === vbulletin_default_settings()['vbulletin_intr_link']) {
                $vb_tab_test = false;
                $vb_alert = vbulletin_integration_word('VBULLETIN_INTR_ERR_LINK_DEFAULT');

                break;
            }

            $vb_tab_test = true;
            $vb_test_steps = vbulletin_integration_test($vb_settings);

            if ($vb_passed($vb_test_steps)) {
                $vb_save(['vbulletin_intr_enabled' => '1']);
                kleeja_admin_info(vbulletin_integration_word('VBULLETIN_INTR_TURNED_ON'), $action);
            }

            $vb_alert = vbulletin_integration_word('VBULLETIN_INTR_NOT_TURNED_ON');

            break;

        case 'disable':
            $vb_save(['vbulletin_intr_enabled' => '0']);
            kleeja_admin_info(vbulletin_integration_word('VBULLETIN_INTR_TURNED_OFF'), $action);

            break;

        case 'save':
            $posted = [];

            foreach ($vb_settings as $name => $saved) {
                // p() encodes what it reads
                $posted[$name] = ip($name) ? htmlspecialchars_decode(p($name), ENT_QUOTES) : $saved;
            }

            // the buttons on top of the page turn it on and off
            $posted['vbulletin_intr_enabled'] = $vb_settings['vbulletin_intr_enabled'];
            $posted['vbulletin_intr_type'] = $posted['vbulletin_intr_type'] === 'db' ? 'db' : 'path';
            $posted['vbulletin_intr_version5'] = $posted['vbulletin_intr_version5'] === '0' ? '0' : '1';

            // the saved password is never shown, an empty field keeps it
            if ($posted['vbulletin_intr_db_password'] === '') {
                $posted['vbulletin_intr_db_password'] = $vb_settings['vbulletin_intr_db_password'];
            }

            if (!preg_match('#^https?://[^/\s]+\S*$#iD', $posted['vbulletin_intr_link'])) {
                $vb_errors[] = 'VBULLETIN_INTR_ERR_LINK';
            }

            if ($posted['vbulletin_intr_type'] === 'path') {
                if (vbulletin_config_file($posted) === '') {
                    $vb_errors[] = 'VBULLETIN_INTR_ERR_CONFIG_NAME';
                }
            } else {
                $port = $posted['vbulletin_intr_db_port'];

                if (
                    trim($posted['vbulletin_intr_db_server']) === '' ||
                    trim($posted['vbulletin_intr_db_name']) === '' ||
                    trim($posted['vbulletin_intr_db_user']) === '' ||
                    preg_match(
                        '/[;\s]/',
                        trim($posted['vbulletin_intr_db_server']) . trim($posted['vbulletin_intr_db_name']),
                    )
                ) {
                    $vb_errors[] = 'VBULLETIN_INTR_ERR_DETAILS';
                }

                if (!preg_match('/^\d{1,5}$/D', $port) || (int) $port < 1 || (int) $port > 65535) {
                    $vb_errors[] = 'VBULLETIN_INTR_ERR_PORT';
                }

                if (!preg_match('/^[a-z0-9_]*$/iD', trim($posted['vbulletin_intr_db_prefix']))) {
                    $vb_errors[] = 'VBULLETIN_INTR_ERR_PREFIX';
                }

                if (!preg_match('/^[a-z0-9_]+$/iD', trim($posted['vbulletin_intr_encoding']))) {
                    $vb_errors[] = 'VBULLETIN_INTR_ERR_CHARSET';
                }
            }

            // while it is on, the members sign in through these settings: new ones are saved only if they work,
            // or nobody could sign in, the admin neither
            $connection = fn(array $settings): array => array_diff_key($settings, [
                'vbulletin_intr_enabled' => 0,
                'vbulletin_intr_link' => 0,
            ]);

            if (!$vb_errors && $vb_on && $connection($posted) !== $connection($vb_settings)) {
                $vb_test_steps = vbulletin_integration_test($posted);

                if (!$vb_passed($vb_test_steps)) {
                    $vb_alert = vbulletin_integration_word('VBULLETIN_INTR_NOT_SAVED');
                }
            }

            if (!$vb_errors && $vb_alert === '') {
                $vb_save($posted);
                kleeja_admin_info($lang['CONFIGS_UPDATED'], $action);
            }

            // the form keeps what was typed, to be fixed
            $vb_settings = $posted;

            break;
    }
}

// the form, its values are encoded once for HTML, the password isn't shown
$vb_form = array_map('kleeja_html_encode', $vb_settings);
$vb_form['vbulletin_intr_db_password'] = '';
$vb_has_password = $vb_settings['vbulletin_intr_db_password'] !== '';
$vb_type_db = $vb_settings['vbulletin_intr_type'] === 'db';
$vb_v5 = vbulletin_is_v5($vb_settings);
$vb_paused = $vb_on && defined('DISABLE_INTR');
$vb_error_list = array_map(fn(string $word): array => ['text' => vbulletin_integration_word($word)], $vb_errors);

// the steps of the test, with the icon and the color of their status
$vb_test_passed = $vb_passed($vb_test_steps);
$vb_test_summary = vbulletin_integration_word(
    $vb_test_passed ? 'VBULLETIN_INTR_TEST_OK' : 'VBULLETIN_INTR_TEST_FAILED',
);
$vb_test_styles = [
    'ok' => ['circle-check', 'success'],
    'error' => ['circle-xmark', 'danger'],
    'skipped' => ['circle-minus', 'secondary'],
];

foreach ($vb_test_steps as $i => $step) {
    [$vb_test_steps[$i]['icon'], $vb_test_steps[$i]['color']] = $vb_test_styles[$step['status']];
}
