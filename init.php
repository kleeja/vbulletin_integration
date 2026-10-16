<?php
// Kleeja Plugin
// vbulletin_integration
// Version: 2.0
// Developer: Kleeja team

// Prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}

// Plugin Basic Information
$kleeja_plugin['vbulletin_integration']['information'] = [
    // The casual name of this plugin, anything can a human being understands
    'plugin_title' => [
        'en' => 'vBulletin Integration',
        'ar' => 'التكامل مع vBulletin',
    ],
    //settings page, if there is one (what after ? like cp=j_plugins)
    'settings_page' => 'cp=vbulletin_settings',
    // Who wrote this plugin?
    'plugin_developer' => 'Kleeja.net',
    // This plugin version
    'plugin_version' => '2.0',
    // Explain what is this plugin, why should I use it?
    'plugin_description' => [
        'en' => 'Let the members of a vBulletin forum sign in to Kleeja with their forum accounts.',
        'ar' => 'تتيح لأعضاء منتدى vBulletin تسجيل الدخول إلى كليجا بحساباتهم في المنتدى.',
    ],
    // Min version of Kleeja that's requiered to run this plugin
    'plugin_kleeja_version_min' => '4.0.0',
    // Max version of Kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '4.9',
    // Should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0,
];

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['vbulletin_integration']['first_run']['ar'] = "
أضافت هذه الإضافة صفحة «التكامل مع vBulletin» إلى لوحة التحكم. أدخل طريقة وصول كليجا إلى قاعدة بيانات المنتدى،
وشغّل اختبار الاتصال، ثم فعّل التكامل، فيسجّل أعضاء المنتدى دخولهم إلى كليجا بحساباتهم في المنتدى.
<br>
<a href='./index.php?cp=vbulletin_settings'>التكامل مع vBulletin</a> -
<a href='./index.php?cp=s_help#help-vbulletin_integration'>دليل الإضافة</a> -
<a href='https://github.com/kleeja/vbulletin_integration/issues' target='_blank' rel='noopener'>الإبلاغ عن مشكلة</a>
";

$kleeja_plugin['vbulletin_integration']['first_run']['en'] = "
This plugin added the vBulletin Integration page to the control panel. Enter how Kleeja reaches the forum database,
run the connection test, then turn the integration on, and forum members sign in to Kleeja with their forum accounts.
<br>
<a href='./index.php?cp=vbulletin_settings'>vBulletin Integration</a> -
<a href='./index.php?cp=s_help#help-vbulletin_integration'>Plugin guide</a> -
<a href='https://github.com/kleeja/vbulletin_integration/issues' target='_blank' rel='noopener'>Report a problem</a>
";

// Plugin Installation function
$kleeja_plugin['vbulletin_integration']['install'] = function ($plg_id) {
    $options = [];

    foreach (vbulletin_default_settings() as $name => $value) {
        $options[$name] = ['value' => $value, 'plg_id' => (int) $plg_id, 'type' => 'vb_integration'];
    }

    add_config_r($options);

    foreach (vbulletin_integration_translations() as $lang_id => $words) {
        add_olang($words, $lang_id, (int) $plg_id);
    }
};

// the words are written again, so a new version brings its own (2.0 rewrote all of them),
// with queries of its own: add_config(), add_olang() and delete_cache() run hooks, and the plugins are still loading.
// $kleeja_plugin isn't a global, Kleeja includes this file in a method, so the closure takes the information above with it
$kleeja_plugin['vbulletin_integration']['update'] = function ($old_version, $new_version) use ($kleeja_plugin) {
    global $SQL, $dbprefix;

    $result = $SQL->build([
        'SELECT' => 'plg_id',
        'FROM' => "{$dbprefix}plugins",
        'WHERE' => 'plg_name = :name',
        'BIND' => ['name' => 'vbulletin_integration'],
    ]);

    $row = $SQL->fetch_array($result);
    $plg_id = (int) ($row['plg_id'] ?? 0);
    $SQL->freeresult($result);

    //a plg_id of 0 would delete the words of the core and of every plugin
    if ($plg_id > 0) {
        $SQL->build([
            'DELETE' => "{$dbprefix}lang",
            'WHERE' => 'plg_id = :plg_id',
            'BIND' => ['plg_id' => $plg_id],
        ]);

        foreach (vbulletin_integration_translations() as $lang_id => $words) {
            foreach ($words as $word => $trans) {
                $SQL->build([
                    'INSERT' => 'word, trans, lang_id, plg_id',
                    'INTO' => "{$dbprefix}lang",
                    'VALUES' => ':word, :trans, :lang_id, :plg_id',
                    'BIND' => ['word' => $word, 'trans' => $trans, 'lang_id' => $lang_id, 'plg_id' => $plg_id],
                ]);
            }
        }

        // the first versions had fewer settings, a setting without its row could never be saved
        $result = $SQL->build([
            'SELECT' => 'name',
            'FROM' => "{$dbprefix}config",
            'WHERE' => 'name IN (:names)',
            'BIND' => ['names' => array_keys(vbulletin_default_settings())],
        ]);

        $saved = [];

        while ($row = $SQL->fetch_array($result)) {
            $saved[] = $row['name'];
        }

        $SQL->freeresult($result);

        foreach (array_diff_key(vbulletin_default_settings(), array_flip($saved)) as $name => $value) {
            $SQL->build([
                'INSERT' => '`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`',
                'INTO' => "{$dbprefix}config",
                'VALUES' => ':name, :value, :option, 0, :type, :plg_id, 0',
                'BIND' => [
                    'name' => $name,
                    'value' => $value,
                    'option' => '',
                    'type' => 'vb_integration',
                    'plg_id' => $plg_id,
                ],
            ]);
        }
    }

    // the developer and the description that the old versions were installed with,
    // the list of plugins shows them while the plugin is disabled
    if (version_compare($old_version, '2.0', '<')) {
        $SQL->build([
            'UPDATE' => "{$dbprefix}plugins",
            'SET' => 'plg_author = :author, plg_dsc = :description',
            'WHERE' => 'plg_name = :name',
            'BIND' => [
                'author' => 'Kleeja.net',
                'description' => kleeja_html_encode(
                    $kleeja_plugin['vbulletin_integration']['information']['plugin_description']['en'],
                ),
                'name' => 'vbulletin_integration',
            ],
        ]);
    }

    // the cached words and settings, and the compiled page of the control panel, which is a Bootstrap 5 one since 2.0.
    // They are PHP files, and OPcache would keep running the old ones for a while, or until PHP restarts
    // when it doesn't check the files. Before a file is deleted, OPcache finds a relative path only when the file exists
    $cached = array_merge(
        glob(PATH . 'cache/data_lang*.php') ?: [],
        glob(PATH . 'cache/tpl_admin_vbulletin_settings*.php') ?: [],
        [PATH . 'cache/data_config.php'],
    );

    foreach ($cached as $file) {
        if (file_exists($file)) {
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($file, true);
            }

            @unlink($file);
        }
    }
};

// Plugin Uninstallation, function to be called at unistalling
$kleeja_plugin['vbulletin_integration']['uninstall'] = function ($plg_id) {
    delete_config(array_keys(vbulletin_default_settings()));

    //a plg_id of 0 would delete the words of the core and of every plugin
    if ((int) $plg_id > 0) {
        foreach (array_keys(vbulletin_integration_translations()) as $lang_id) {
            delete_olang(null, $lang_id, (int) $plg_id);
        }
    }
};

// Plugin functions
$kleeja_plugin['vbulletin_integration']['functions'] = [
    //add to admin menu
    'begin_admin_page' => function ($args) {
        $adm_extensions = $args['adm_extensions'];
        $ext_icons = $args['ext_icons'];

        $adm_extensions[] = 'vbulletin_settings';
        $ext_icons['vbulletin_settings'] = 'users';

        //the words that the database misses, in the language of the admin, or in English for a language without them
        $olang = (array) ($args['olang'] ?? []) + vbulletin_integration_words();

        return compact('adm_extensions', 'ext_icons', 'olang');
    },

    //add as admin page to reach when click on admin menu item we added.
    'not_exists_vbulletin_settings' => function () {
        $include_alternative = __DIR__ . '/vbulletin_settings.php';

        return compact('include_alternative');
    },

    // the sign in, and the check of the login cookie of an administrator, with the accounts of the forum
    'data_func_usr_class' => function ($args) {
        if (!vbulletin_integration_active()) {
            return;
        }

        $return_now = true;
        $login_status = vbulletin_auth_login(
            (string) $args['name'],
            (string) $args['pass'],
            (int) $args['expire'],
            (bool) $args['hashed'],
            (bool) $args['loginadm'],
        );

        return compact('return_now', 'login_status');
    },

    // the names of the members, on the pages of their files
    'auth_func_usr_class' => function ($args) {
        if (!vbulletin_integration_active()) {
            return;
        }

        $return_now = true;
        $auth_status = vbulletin_auth_username((int) $args['user_id']);

        return compact('return_now', 'auth_status');
    },

    // the pages that the forum has for its members: the new password, the registration and the profile
    'login_before_submit' => function () {
        $forget_pass_link = vbulletin_integration_active() ? vbulletin_forum_link('lostpw') : '';

        return $forget_pass_link === '' ? null : compact('forget_pass_link');
    },

    'get_pass_resetpass_link' => function () {
        $forgetpass_link = vbulletin_integration_active() ? vbulletin_forum_link('lostpw') : '';

        return $forgetpass_link === '' ? null : compact('forgetpass_link');
    },

    'register_not_default_sys' => function () {
        $goto_forum_link = vbulletin_integration_active() ? vbulletin_forum_link('register') : '';

        return $goto_forum_link === '' ? null : compact('goto_forum_link');
    },

    // the profile page of Kleeja reads the row of the member in its own table, a forum member has none,
    // and the page has only a link to the forum for them: they go to the profile of the forum at once
    'begin_usrcp_page' => function () {
        global $usrcp;

        if (g('go') !== 'profile' || !vbulletin_integration_active() || !$usrcp->name()) {
            return;
        }

        $profile = vbulletin_forum_link('profile');

        if ($profile !== '') {
            redirect(htmlspecialchars_decode($profile, ENT_QUOTES));
        }
    },

    // Kleeja leaves the registration, the passwords and the profiles to the forum
    'end_common' => function () {
        global $config;

        if (!vbulletin_integration_active()) {
            return;
        }

        $config['user_system'] = 'vbulletin';

        return compact('config');
    },

    // the guide of the plugin on the help page of the control panel, its words are in language/help_{code}.php
    'admin_help_guides' => function ($args) {
        $help_guides = $args['help_guides'];
        $words = vbulletin_integration_help_words();

        // the name of the plugin is the key, so Kleeja knows that the plugin has its guide, and shows its icon,
        // and the help button of the page of the plugin opens it
        $help_guides['vbulletin_integration'] = [
            'group' => 'plugins',
            'title' => $words['VBULLETIN_INTR_HELP_TITLE'],
            'intro' => $words['VBULLETIN_INTR_HELP_INTRO'],
            'page' => 'vbulletin_settings',
            'link' => './?cp=vbulletin_settings',
            // tips and warnings are shown beside the others on wide screens
            'sections' => [
                vbulletin_integration_help_section($words, 'features', 'FEATURE'),
                vbulletin_integration_help_section($words, 'steps', 'STEP'),
                vbulletin_integration_help_section($words, 'faq', 'FAQ'),
                vbulletin_integration_help_section($words, 'tips', 'TIP'),
                vbulletin_integration_help_section($words, 'warnings', 'WARNING'),
            ],
        ];

        return compact('help_guides');
    },
];

/**
 * special functions
 */

if (!function_exists('vbulletin_integration_word')) {
    /**
     * a word of the plugin, from the database, or from vbulletin_integration_words() when the database misses it
     * @param  string $name
     * @return string
     */
    function vbulletin_integration_word(string $name): string
    {
        global $olang;

        return $olang[$name] ?? (vbulletin_integration_words()[$name] ?? $name);
    }

    /**
     * the words of the plugin in the language of the page, and in English for a language that has no translation of them.
     * The database has the words of the version that was installed, these cover the ones that it misses
     * @return array
     */
    function vbulletin_integration_words(): array
    {
        global $config;
        static $words = [];

        $language = (string) ($config['language'] ?? 'en');

        if (!isset($words[$language])) {
            $translations = vbulletin_integration_translations();
            $words[$language] = ($translations[$language] ?? []) + $translations['en'];
        }

        return $words[$language];
    }

    /**
     * the words of the guide on the help page, in the language of the admin,
     * a word that the translation misses is shown in English
     * @return array
     */
    function vbulletin_integration_help_words(): array
    {
        global $config;

        $words = (array) require __DIR__ . '/language/help_en.php';
        $language = preg_replace('/[^a-z0-9_-]/i', '', (string) ($config['language'] ?? ''));
        $translation = __DIR__ . "/language/help_{$language}.php";

        if ($language !== '' && $language !== 'en' && file_exists($translation)) {
            // in its own line, Prettier drops the brackets of (require $translation) + $words
            $translated = require $translation;
            $words = (array) $translated + $words;
        }

        return $words;
    }

    /**
     * a section of the guide from its numbered words, like VBULLETIN_INTR_HELP_TIP_1, VBULLETIN_INTR_HELP_TIP_2 ..
     * its title, when it has its own, is VBULLETIN_INTR_HELP_TIP_TITLE
     * @param  array  $words
     * @param  string $type  how Kleeja shows it: features, steps, tips, warnings or faq
     * @param  string $name  the name of its words, VBULLETIN_INTR_HELP_{name}_1
     * @return array
     */
    function vbulletin_integration_help_section(array $words, string $type, string $name): array
    {
        $prefix = 'VBULLETIN_INTR_HELP_' . $name;
        $section = ['type' => $type, 'title' => $words[$prefix . '_TITLE'] ?? '', 'items' => []];

        for ($n = 1; isset($words[$prefix . ($type === 'faq' ? '_Q_' : '_') . $n]); $n++) {
            $section['items'][] =
                $type === 'faq'
                    ? ['q' => $words[$prefix . '_Q_' . $n], 'a' => $words[$prefix . '_A_' . $n] ?? '']
                    : $words[$prefix . '_' . $n];
        }

        return $section;
    }

    /**
     * the words of the plugin, they are added to the language table when it is installed or updated,
     * each one fits the 255 letters of that table on MySQL
     * @return array
     */
    function vbulletin_integration_translations(): array
    {
        return [
            'en' => [
                'R_VBULLETIN_SETTINGS' => 'vBulletin Integration',
                'VBULLETIN_INTR_TAB_SETTINGS' => 'Settings',
                'VBULLETIN_INTR_TAB_TEST' => 'Connection test',
                'VBULLETIN_INTR_STATUS_ON' =>
                    'The integration is on. Members sign in to Kleeja with their vBulletin accounts, and only forum administrators can open this control panel.',
                'VBULLETIN_INTR_STATUS_OFF' =>
                    'The integration is off, so Kleeja uses its own accounts. Enter how Kleeja reaches the forum database, save, then click <strong>Test and turn on</strong>.',
                'VBULLETIN_INTR_STATUS_OFF_NOTE' =>
                    'Turning it on signs you out. Sign in again with the account of a forum administrator.',
                'VBULLETIN_INTR_STATUS_PAUSED' =>
                    'The integration is on, but <code>DISABLE_INTR</code> is defined in config.php, so Kleeja accounts are used until you remove it.',
                'VBULLETIN_INTR_TURN_ON' => 'Test and turn on',
                'VBULLETIN_INTR_TURN_OFF' => 'Turn off',
                'VBULLETIN_INTR_TURN_OFF_CONFIRM' =>
                    'Turn off the integration? Kleeja accounts will be used again, and you will need to sign in with a Kleeja administrator account.',
                'VBULLETIN_INTR_TURNED_ON' =>
                    'The integration is on. Sign in again with the account of a forum administrator.',
                'VBULLETIN_INTR_TURNED_OFF' =>
                    'The integration is off, and Kleeja uses its own accounts again. Sign in again with a Kleeja administrator account.',
                'VBULLETIN_INTR_NOT_TURNED_ON' =>
                    'The integration is still off, because the test found a problem. Fix it, then try again.',
                'VBULLETIN_INTR_NOT_SAVED' =>
                    'These settings were not saved. The integration is on, and Kleeja can’t reach the forum with them, so nobody could sign in. Fix what the test found, or turn the integration off first.',
                'VBULLETIN_INTR_SAVE' => 'Save settings',
                'VBULLETIN_INTR_SECTION_FORUM' => 'Forum',
                'VBULLETIN_INTR_LINK' => 'Forum address',
                'VBULLETIN_INTR_LINK_HELP' =>
                    'Members are sent there to register, reset their password or edit their profile. For example: https://example.com/forum',
                'VBULLETIN_INTR_VERSION' => 'vBulletin version',
                'VBULLETIN_INTR_VERSION_HELP' =>
                    'Each version stores passwords differently and has its own page addresses.',
                'VBULLETIN_INTR_VERSION_5' => 'vBulletin 5 or later',
                'VBULLETIN_INTR_VERSION_4' => 'vBulletin 3 or 4',
                'VBULLETIN_INTR_SECTION_DB' => 'Forum database',
                'VBULLETIN_INTR_TYPE' => 'Connection details',
                'VBULLETIN_INTR_TYPE_HELP' =>
                    'Read them from the forum files when the forum is on this server, or enter them here.',
                'VBULLETIN_INTR_TYPE_PATH' => 'From the forum’s config.php',
                'VBULLETIN_INTR_TYPE_DB' => 'Enter them here',
                'VBULLETIN_INTR_PATH' => 'Forum folder',
                'VBULLETIN_INTR_PATH_HELP' =>
                    'The location of the forum, relative to the Kleeja folder. For example: ../forum',
                'VBULLETIN_INTR_CONFIG_PATH' => 'Config file',
                'VBULLETIN_INTR_CONFIG_PATH_HELP' =>
                    'Its location inside the forum folder: /core/includes/config.php for vBulletin 5 and later, /includes/config.php for vBulletin 3 and 4.',
                'VBULLETIN_INTR_DB_SERVER' => 'Database server',
                'VBULLETIN_INTR_DB_SERVER_HELP' => 'Usually localhost when the database is on this server.',
                'VBULLETIN_INTR_DB_PORT' => 'Port',
                'VBULLETIN_INTR_DB_NAME' => 'Database name',
                'VBULLETIN_INTR_DB_USER' => 'Database username',
                'VBULLETIN_INTR_DB_PASSWORD' => 'Database password',
                'VBULLETIN_INTR_DB_PASSWORD_HELP' =>
                    'The saved password is never shown. Leave the field empty to keep it.',
                'VBULLETIN_INTR_DB_PREFIX' => 'Table prefix',
                'VBULLETIN_INTR_DB_PREFIX_HELP' =>
                    'The prefix of the forum tables, like vb_ in vb_user. Leave it empty if the tables have none.',
                'VBULLETIN_INTR_ENCODING' => 'Character set',
                'VBULLETIN_INTR_ENCODING_HELP' =>
                    'The character set of the connection, usually utf8mb4 or utf8. Older forums that store UTF-8 text in latin1 tables need latin1.',
                'VBULLETIN_INTR_ERR_TITLE' => 'The settings were not saved:',
                'VBULLETIN_INTR_ERR_LINK' => 'Enter the full forum address, starting with http:// or https://.',
                'VBULLETIN_INTR_ERR_LINK_DEFAULT' =>
                    'Enter the address of your forum in the settings before turning the integration on. Members are sent there to register and to reset their password.',
                'VBULLETIN_INTR_ERR_CONFIG_NAME' => 'The config file of vBulletin is named config.php.',
                'VBULLETIN_INTR_ERR_DETAILS' => 'Enter the database server, name and username, without spaces.',
                'VBULLETIN_INTR_ERR_PORT' => 'The port is a number from 1 to 65535.',
                'VBULLETIN_INTR_ERR_PREFIX' =>
                    'The table prefix can only contain Latin letters, digits and underscores.',
                'VBULLETIN_INTR_ERR_CHARSET' => 'The character set is a name like utf8mb4, utf8 or latin1.',
                'VBULLETIN_INTR_TEST_INTRO' =>
                    'The test connects to the forum database with the saved settings and checks that Kleeja can read its members. It changes nothing in the forum.',
                'VBULLETIN_INTR_TEST_RUN' => 'Run the test',
                'VBULLETIN_INTR_TEST_RESULTS' => 'Test results',
                'VBULLETIN_INTR_TEST_OK' => 'All checks passed: Kleeja can read the forum members.',
                'VBULLETIN_INTR_TEST_FAILED' =>
                    'The test found a problem. Fix it in the settings, then run the test again.',
                'VBULLETIN_INTR_TEST_SKIPPED' => 'Not checked, because a previous check failed.',
                'VBULLETIN_INTR_STEP_DETAILS' => 'Connection details',
                'VBULLETIN_INTR_STEP_DETAILS_FILE' => 'Read from %s.',
                'VBULLETIN_INTR_STEP_DETAILS_DB' => 'Database %1$s on %2$s, with the user %3$s.',
                'VBULLETIN_INTR_STEP_FILE_MISSING' => 'Kleeja can’t find or read %s.',
                'VBULLETIN_INTR_STEP_FILE_EMPTY' => '%s has no vBulletin database details.',
                'VBULLETIN_INTR_STEP_FILE_HINT' => 'Check the forum folder and the config file in the settings.',
                'VBULLETIN_INTR_STEP_CONNECT' => 'Database connection',
                'VBULLETIN_INTR_STEP_CONNECT_OK' => 'Connected to MySQL %s.',
                'VBULLETIN_INTR_STEP_CONNECT_NO_DRIVER' =>
                    'PHP can’t connect to MySQL, because its pdo_mysql extension is not enabled.',
                'VBULLETIN_INTR_STEP_CONNECT_NO_DRIVER_HINT' => 'Ask your host to enable the pdo_mysql extension.',
                'VBULLETIN_INTR_STEP_CONNECT_DENIED_HINT' => 'Check the database username and password.',
                'VBULLETIN_INTR_STEP_CONNECT_NO_DB_HINT' =>
                    'Check the database name, and that this user can access it.',
                'VBULLETIN_INTR_STEP_CONNECT_CHARSET_HINT' => 'Check the character set.',
                'VBULLETIN_INTR_STEP_CONNECT_SERVER_HINT' =>
                    'Check the server and the port, and that the database server accepts connections from this server.',
                'VBULLETIN_INTR_STEP_MEMBERS' => 'Members table',
                'VBULLETIN_INTR_STEP_MEMBERS_OK' => 'Members in %1$s: %2$s.',
                'VBULLETIN_INTR_STEP_MEMBERS_NO_TABLE' => 'The database has no %s table.',
                'VBULLETIN_INTR_STEP_MEMBERS_NO_TABLE_HINT' =>
                    'Check the table prefix, and that this is the forum database.',
                'VBULLETIN_INTR_STEP_MEMBERS_NO_COLUMNS' =>
                    'The members table doesn’t have the password columns of the vBulletin version you chose.',
                'VBULLETIN_INTR_STEP_MEMBERS_NO_COLUMNS_HINT' => 'Check the vBulletin version in the settings.',
                'VBULLETIN_INTR_STEP_ADMINS' => 'Forum administrators',
                'VBULLETIN_INTR_STEP_ADMINS_OK' => 'Administrators who can open the control panel: %s.',
                'VBULLETIN_INTR_STEP_ADMINS_NONE' =>
                    'No member’s primary group is Administrators, so nobody could open the control panel once the integration is on.',
                'VBULLETIN_INTR_STEP_ADMINS_NONE_HINT' =>
                    'Kleeja lets in the members whose primary group in vBulletin is Administrators (group 6).',
            ],
            'ar' => [
                'R_VBULLETIN_SETTINGS' => 'التكامل مع vBulletin',
                'VBULLETIN_INTR_TAB_SETTINGS' => 'الإعدادات',
                'VBULLETIN_INTR_TAB_TEST' => 'اختبار الاتصال',
                'VBULLETIN_INTR_STATUS_ON' =>
                    'التكامل مفعّل. يسجّل الأعضاء دخولهم إلى كليجا بحساباتهم في منتدى vBulletin، ولا يدخل لوحة التحكم إلا مديرو المنتدى.',
                'VBULLETIN_INTR_STATUS_OFF' =>
                    'التكامل معطّل، فتستخدم كليجا حساباتها الخاصة. أدخل طريقة وصول كليجا إلى قاعدة بيانات المنتدى واحفظها، ثم اضغط <strong>اختبار وتفعيل</strong>.',
                'VBULLETIN_INTR_STATUS_OFF_NOTE' =>
                    'يؤدي التفعيل إلى تسجيل خروجك، فسجّل الدخول مجددًا بحساب أحد مديري المنتدى.',
                'VBULLETIN_INTR_STATUS_PAUSED' =>
                    'التكامل مفعّل، لكن <code dir="ltr">DISABLE_INTR</code> معرَّف في config.php، لذا تُستخدم حسابات كليجا إلى أن تحذفه.',
                'VBULLETIN_INTR_TURN_ON' => 'اختبار وتفعيل',
                'VBULLETIN_INTR_TURN_OFF' => 'تعطيل',
                'VBULLETIN_INTR_TURN_OFF_CONFIRM' =>
                    'هل تريد تعطيل التكامل؟ ستعود كليجا إلى استخدام حساباتها، وستحتاج إلى تسجيل الدخول بحساب مدير في كليجا.',
                'VBULLETIN_INTR_TURNED_ON' => 'فُعّل التكامل. سجّل الدخول مجددًا بحساب أحد مديري المنتدى.',
                'VBULLETIN_INTR_TURNED_OFF' =>
                    'عُطّل التكامل، وعادت كليجا إلى استخدام حساباتها. سجّل الدخول مجددًا بحساب مدير في كليجا.',
                'VBULLETIN_INTR_NOT_TURNED_ON' =>
                    'ما زال التكامل معطّلًا لأن الاختبار وجد مشكلة. أصلحها ثم أعد المحاولة.',
                'VBULLETIN_INTR_NOT_SAVED' =>
                    'لم تُحفظ هذه الإعدادات. التكامل مفعّل، ولا تصل كليجا بها إلى المنتدى، فلن يتمكن أحد من تسجيل الدخول. أصلح ما وجده الاختبار، أو عطّل التكامل أولًا.',
                'VBULLETIN_INTR_SAVE' => 'حفظ الإعدادات',
                'VBULLETIN_INTR_SECTION_FORUM' => 'المنتدى',
                'VBULLETIN_INTR_LINK' => 'عنوان المنتدى',
                'VBULLETIN_INTR_LINK_HELP' =>
                    'يُوجَّه إليه الأعضاء للتسجيل واستعادة كلمة المرور وتعديل الملف الشخصي، مثل <span dir="ltr">https://example.com/forum</span>',
                'VBULLETIN_INTR_VERSION' => 'إصدار vBulletin',
                'VBULLETIN_INTR_VERSION_HELP' => 'يحفظ كل إصدار كلمات المرور بطريقة مختلفة، وله عناوين صفحات خاصة به.',
                'VBULLETIN_INTR_VERSION_5' => 'vBulletin 5 أو أحدث',
                'VBULLETIN_INTR_VERSION_4' => 'vBulletin 3 أو 4',
                'VBULLETIN_INTR_SECTION_DB' => 'قاعدة بيانات المنتدى',
                'VBULLETIN_INTR_TYPE' => 'بيانات الاتصال',
                'VBULLETIN_INTR_TYPE_HELP' => 'تُقرأ من ملفات المنتدى إن كان على هذا الخادم، أو تُدخلها هنا.',
                'VBULLETIN_INTR_TYPE_PATH' => 'من ملف config.php للمنتدى',
                'VBULLETIN_INTR_TYPE_DB' => 'إدخالها هنا',
                'VBULLETIN_INTR_PATH' => 'مجلد المنتدى',
                'VBULLETIN_INTR_PATH_HELP' =>
                    'موقع المنتدى بالنسبة إلى مجلد كليجا، مثل <span dir="ltr">../forum</span>',
                'VBULLETIN_INTR_CONFIG_PATH' => 'ملف الإعدادات',
                'VBULLETIN_INTR_CONFIG_PATH_HELP' =>
                    'موقعه داخل مجلد المنتدى: <span dir="ltr">/core/includes/config.php</span> في vBulletin 5 وما بعده، و<span dir="ltr">/includes/config.php</span> في vBulletin 3 و4.',
                'VBULLETIN_INTR_DB_SERVER' => 'خادم قاعدة البيانات',
                'VBULLETIN_INTR_DB_SERVER_HELP' => 'غالبًا localhost إن كانت قاعدة البيانات على هذا الخادم.',
                'VBULLETIN_INTR_DB_PORT' => 'المنفذ',
                'VBULLETIN_INTR_DB_NAME' => 'اسم قاعدة البيانات',
                'VBULLETIN_INTR_DB_USER' => 'اسم مستخدم قاعدة البيانات',
                'VBULLETIN_INTR_DB_PASSWORD' => 'كلمة مرور قاعدة البيانات',
                'VBULLETIN_INTR_DB_PASSWORD_HELP' =>
                    'لا تُعرض كلمة المرور المحفوظة أبدًا. اترك الحقل فارغًا للإبقاء عليها.',
                'VBULLETIN_INTR_DB_PREFIX' => 'بادئة الجداول',
                'VBULLETIN_INTR_DB_PREFIX_HELP' =>
                    'بادئة جداول المنتدى، مثل <span dir="ltr">vb_</span> في <span dir="ltr">vb_user</span>. اتركها فارغة إن لم تكن للجداول بادئة.',
                'VBULLETIN_INTR_ENCODING' => 'ترميز الأحرف',
                'VBULLETIN_INTR_ENCODING_HELP' =>
                    'ترميز الاتصال، وغالبًا ما يكون utf8mb4 أو utf8. المنتديات القديمة التي تحفظ نصوص UTF-8 في جداول latin1 تحتاج إلى latin1.',
                'VBULLETIN_INTR_ERR_TITLE' => 'لم تُحفظ الإعدادات:',
                'VBULLETIN_INTR_ERR_LINK' =>
                    'أدخل عنوان المنتدى كاملًا، بدءًا بـ <span dir="ltr">http://</span> أو <span dir="ltr">https://</span>.',
                'VBULLETIN_INTR_ERR_LINK_DEFAULT' =>
                    'أدخل عنوان منتداك في الإعدادات قبل تفعيل التكامل، فإليه يُوجَّه الأعضاء للتسجيل واستعادة كلمة المرور.',
                'VBULLETIN_INTR_ERR_CONFIG_NAME' => 'اسم ملف إعدادات vBulletin هو config.php.',
                'VBULLETIN_INTR_ERR_DETAILS' => 'أدخل خادم قاعدة البيانات واسمها واسم المستخدم، دون مسافات.',
                'VBULLETIN_INTR_ERR_PORT' => 'المنفذ رقم من 1 إلى 65535.',
                'VBULLETIN_INTR_ERR_PREFIX' => 'لا تحتوي بادئة الجداول إلا على حروف لاتينية وأرقام وشرطات سفلية (_).',
                'VBULLETIN_INTR_ERR_CHARSET' => 'ترميز الأحرف اسم مثل utf8mb4 أو utf8 أو latin1.',
                'VBULLETIN_INTR_TEST_INTRO' =>
                    'يتصل الاختبار بقاعدة بيانات المنتدى بالإعدادات المحفوظة، ويتحقق من أن كليجا تستطيع قراءة أعضائه، ولا يغيّر شيئًا في المنتدى.',
                'VBULLETIN_INTR_TEST_RUN' => 'تشغيل الاختبار',
                'VBULLETIN_INTR_TEST_RESULTS' => 'نتائج الاختبار',
                'VBULLETIN_INTR_TEST_OK' => 'نجحت كل الفحوصات: تستطيع كليجا قراءة أعضاء المنتدى.',
                'VBULLETIN_INTR_TEST_FAILED' => 'وجد الاختبار مشكلة. أصلحها في الإعدادات، ثم أعد تشغيل الاختبار.',
                'VBULLETIN_INTR_TEST_SKIPPED' => 'لم يُفحص لأن فحصًا سابقًا فشل.',
                'VBULLETIN_INTR_STEP_DETAILS' => 'بيانات الاتصال',
                'VBULLETIN_INTR_STEP_DETAILS_FILE' => 'قُرئت من %s.',
                'VBULLETIN_INTR_STEP_DETAILS_DB' => 'قاعدة البيانات %1$s على %2$s، باسم المستخدم %3$s.',
                'VBULLETIN_INTR_STEP_FILE_MISSING' => 'لا تجد كليجا الملف %s، أو لا تستطيع قراءته.',
                'VBULLETIN_INTR_STEP_FILE_EMPTY' => 'لا يحتوي %s على بيانات قاعدة بيانات vBulletin.',
                'VBULLETIN_INTR_STEP_FILE_HINT' => 'راجع مجلد المنتدى وملف الإعدادات في الإعدادات.',
                'VBULLETIN_INTR_STEP_CONNECT' => 'الاتصال بقاعدة البيانات',
                'VBULLETIN_INTR_STEP_CONNECT_OK' => 'تم الاتصال بـ MySQL %s.',
                'VBULLETIN_INTR_STEP_CONNECT_NO_DRIVER' =>
                    'لا يستطيع PHP الاتصال بـ MySQL لأن امتداده pdo_mysql غير مفعّل.',
                'VBULLETIN_INTR_STEP_CONNECT_NO_DRIVER_HINT' => 'اطلب من مزوّد الاستضافة تفعيل الامتداد pdo_mysql.',
                'VBULLETIN_INTR_STEP_CONNECT_DENIED_HINT' => 'راجع اسم مستخدم قاعدة البيانات وكلمة مرورها.',
                'VBULLETIN_INTR_STEP_CONNECT_NO_DB_HINT' =>
                    'راجع اسم قاعدة البيانات، وتأكد من أن هذا المستخدم يملك صلاحية الوصول إليها.',
                'VBULLETIN_INTR_STEP_CONNECT_CHARSET_HINT' => 'راجع ترميز الأحرف.',
                'VBULLETIN_INTR_STEP_CONNECT_SERVER_HINT' =>
                    'راجع الخادم والمنفذ، وتأكد من أن خادم قاعدة البيانات يقبل الاتصال من هذا الخادم.',
                'VBULLETIN_INTR_STEP_MEMBERS' => 'جدول الأعضاء',
                'VBULLETIN_INTR_STEP_MEMBERS_OK' => 'عدد الأعضاء في %1$s: %2$s.',
                'VBULLETIN_INTR_STEP_MEMBERS_NO_TABLE' => 'لا يوجد في قاعدة البيانات جدول باسم %s.',
                'VBULLETIN_INTR_STEP_MEMBERS_NO_TABLE_HINT' =>
                    'راجع بادئة الجداول، وتأكد من أنها قاعدة بيانات المنتدى.',
                'VBULLETIN_INTR_STEP_MEMBERS_NO_COLUMNS' =>
                    'لا يحتوي جدول الأعضاء على أعمدة كلمات المرور الخاصة بإصدار vBulletin الذي اخترته.',
                'VBULLETIN_INTR_STEP_MEMBERS_NO_COLUMNS_HINT' => 'راجع إصدار vBulletin في الإعدادات.',
                'VBULLETIN_INTR_STEP_ADMINS' => 'مديرو المنتدى',
                'VBULLETIN_INTR_STEP_ADMINS_OK' => 'عدد المديرين الذين يمكنهم دخول لوحة التحكم: %s.',
                'VBULLETIN_INTR_STEP_ADMINS_NONE' =>
                    'لا يوجد عضو مجموعته الأساسية «المديرون»، فلن يتمكن أحد من دخول لوحة التحكم بعد تفعيل التكامل.',
                'VBULLETIN_INTR_STEP_ADMINS_NONE_HINT' =>
                    'تسمح كليجا بدخول الأعضاء الذين مجموعتهم الأساسية في vBulletin هي «المديرون» (المجموعة 6).',
            ],
        ];
    }
}

//includes integration functions
include_once __DIR__ . '/vbulletin.php';
