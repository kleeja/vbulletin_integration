<?php
/**
 *
 * @package vbulletin_integration
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

//no for directly open
if (!defined('IN_COMMON')) {
    exit();
}

if (!function_exists('vbulletin_integration_active')) {
    // the groups of vBulletin that the integration knows: administrators open the control panel,
    // and banned members can't sign in
    define('VBULLETIN_ADMIN_GROUP', 6);
    define('VBULLETIN_BANNED_GROUP', 8);
    // seconds to wait for the database of the forum, the panel checks it on every page of an administrator
    define('VBULLETIN_DB_TIMEOUT', 5);

    /**
     * the members sign in with their forum accounts: the integration is turned on in its page,
     * and define('DISABLE_INTR', true) isn't in config.php, which gives back the Kleeja accounts when the forum is down
     * @return bool
     */
    function vbulletin_integration_active(): bool
    {
        global $config;

        return !defined('DISABLE_INTR') && (int) ($config['vbulletin_intr_enabled'] ?? 0) === 1;
    }

    /**
     * the settings of the plugin and their values when it is installed
     * @return array
     */
    function vbulletin_default_settings(): array
    {
        return [
            'vbulletin_intr_enabled' => '0',
            'vbulletin_intr_type' => 'path',
            'vbulletin_intr_path' => '../vb',
            'vbulletin_intr_link' => 'http://example.com/vbulletin',
            'vbulletin_intr_config_path' => '/core/includes/config.php',
            'vbulletin_intr_version5' => '1',
            'vbulletin_intr_encoding' => 'utf8',
            'vbulletin_intr_db_server' => 'localhost',
            'vbulletin_intr_db_port' => '3306',
            'vbulletin_intr_db_user' => '',
            'vbulletin_intr_db_password' => '',
            'vbulletin_intr_db_name' => 'vbulletin',
            'vbulletin_intr_db_prefix' => '',
        ];
    }

    /**
     * the saved settings as they were typed. Kleeja keeps them encoded for HTML,
     * and the versions before 2.0 encoded them twice
     * @return array
     */
    function vbulletin_settings(): array
    {
        global $config;

        $settings = [];

        foreach (vbulletin_default_settings() as $name => $default) {
            $settings[$name] = isset($config[$name]) ? kleeja_html_decode((string) $config[$name]) : $default;
        }

        return $settings;
    }

    /**
     * the forum is vBulletin 5 or later, which keeps the passwords in token, the older ones in password and salt
     * @param  array|null $settings the settings to read, the saved ones by default
     * @return bool
     */
    function vbulletin_is_v5(?array $settings = null): bool
    {
        return ($settings ?? vbulletin_settings())['vbulletin_intr_version5'] !== '0';
    }

    /**
     * the config.php of the forum, from the folder of Kleeja, or an empty text for a file that isn't named config.php,
     * the file is included, so it can't be any file of the server
     * @param  array  $settings
     * @return string
     */
    function vbulletin_config_file(array $settings): string
    {
        $file = '/' . ltrim(str_replace('\\', '/', trim($settings['vbulletin_intr_config_path'])), '/');

        if (basename($file) !== 'config.php') {
            return '';
        }

        $folder = trim(str_replace('\\', '/', trim($settings['vbulletin_intr_path'])), '/');
        $root = rtrim(str_replace('\\', '/', realpath(PATH) ?: dirname(__DIR__, 2)), '/');
        $folder = $root . ($folder === '' ? '' : '/' . $folder);

        // without the ../ of the folder, the test shows where it is, even when the file isn't there
        return realpath($folder . $file) ?: (realpath($folder) ?: $folder) . $file;
    }

    /**
     * the $config array of a config.php of vBulletin, in a scope of its own
     * @param  string $file
     * @return array
     */
    function vbulletin_read_config(string $file): array
    {
        $config = [];

        // a space after its closing tag would be printed in the page
        ob_start();
        include $file;
        ob_end_clean();

        return is_array($config) ? $config : [];
    }

    /**
     * where the database of the forum is: the settings, or the config.php of the forum
     * @param  array $settings as vbulletin_settings() gives them
     * @return array host, port, name, user, password, prefix and charset, and 'file' for the config.php that was read.
     *               'error' is the word of the plugin that says what is wrong, when they can't be used
     */
    function vbulletin_db_details(array $settings): array
    {
        if ($settings['vbulletin_intr_type'] === 'db') {
            $details = [
                'host' => trim($settings['vbulletin_intr_db_server']),
                'port' => (int) $settings['vbulletin_intr_db_port'],
                'name' => trim($settings['vbulletin_intr_db_name']),
                'user' => trim($settings['vbulletin_intr_db_user']),
                'password' => $settings['vbulletin_intr_db_password'],
                'prefix' => trim($settings['vbulletin_intr_db_prefix']),
                'charset' => trim($settings['vbulletin_intr_encoding']),
            ];
        } else {
            $file = vbulletin_config_file($settings);

            if ($file === '') {
                return ['error' => 'VBULLETIN_INTR_ERR_CONFIG_NAME', 'file' => ''];
            }

            if (!is_file($file) || !is_readable($file)) {
                return ['error' => 'VBULLETIN_INTR_STEP_FILE_MISSING', 'file' => $file];
            }

            $forum = vbulletin_read_config($file);

            $details = [
                'host' => (string) ($forum['MasterServer']['servername'] ?? ''),
                'port' => (int) ($forum['MasterServer']['port'] ?? 3306),
                'name' => (string) ($forum['Database']['dbname'] ?? ''),
                'user' => (string) ($forum['MasterServer']['username'] ?? ''),
                'password' => (string) ($forum['MasterServer']['password'] ?? ''),
                'prefix' => (string) ($forum['Database']['tableprefix'] ?? ''),
                // some forums change the charset of their connection, Kleeja 3 connected with utf8 otherwise
                'charset' => (string) ($forum['Mysqli']['charset'] ?? ''),
                'file' => $file,
            ];

            if ($details['host'] === '' || $details['name'] === '' || $details['user'] === '') {
                return ['error' => 'VBULLETIN_INTR_STEP_FILE_EMPTY', 'file' => $file];
            }
        }

        $details['port'] = $details['port'] > 0 && $details['port'] <= 65535 ? $details['port'] : 3306;
        $details['charset'] = $details['charset'] === '' ? 'utf8' : $details['charset'];

        // each of them goes in the DSN or in the queries, as they are
        $error = match (true) {
            $details['host'] === '' || $details['name'] === '' || $details['user'] === ''
                => 'VBULLETIN_INTR_ERR_DETAILS',
            (bool) preg_match('/[;\s]/', $details['host'] . $details['name']) => 'VBULLETIN_INTR_ERR_DETAILS',
            !preg_match('/^[a-z0-9_]*$/iD', $details['prefix']) => 'VBULLETIN_INTR_ERR_PREFIX',
            !preg_match('/^[a-z0-9_]+$/iD', $details['charset']) => 'VBULLETIN_INTR_ERR_CHARSET',
            default => '',
        };

        return $error === '' ? $details : ['error' => $error, 'file' => $details['file'] ?? ''];
    }

    /**
     * a connection to the database of the forum, with its own error handling: Kleeja's database class stops the page
     * with an error when it can't connect, and the control panel needs the forum on every page of an administrator
     * @param  array        $details as vbulletin_db_details() gives them
     * @throws PDOException when it can't connect
     * @return PDO
     */
    function vbulletin_open_database(array $details): PDO
    {
        if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
            throw new PDOException('The pdo_mysql extension of PHP is not enabled.');
        }

        return new PDO(
            "mysql:host={$details['host']};port={$details['port']};dbname={$details['name']};charset={$details['charset']}",
            $details['user'],
            $details['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES => true,
                PDO::ATTR_TIMEOUT => VBULLETIN_DB_TIMEOUT,
                // prepared by the server, one statement per query, like the database class of Kleeja
                PDO::ATTR_EMULATE_PREPARES => false,
                PHP_VERSION_ID >= 80400 ? Pdo\Mysql::ATTR_MULTI_STATEMENTS : PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
            ],
        );
    }

    /**
     * the database of the forum and its table of members, opened once in a request
     * @return array|null ['db' => PDO, 'users' => quoted table name], null when it can't be reached
     */
    function vbulletin_auth_connect(): ?array
    {
        static $forum = false;

        if ($forum !== false) {
            return $forum;
        }

        $forum = null;
        $details = vbulletin_db_details(vbulletin_settings());

        if (isset($details['error'])) {
            kleeja_log('[vbulletin_integration] the settings can not be used: ' . $details['error']);

            return null;
        }

        try {
            $forum = ['db' => vbulletin_open_database($details), 'users' => "`{$details['prefix']}user`"];
        } catch (PDOException $e) {
            kleeja_log('[vbulletin_integration] no connection to the forum: ' . $e->getMessage());
        }

        return $forum;
    }

    /**
     * a member of the forum, with the hash of its password
     * @param  string     $column        userid or username
     * @param  int|string $value
     * @param  bool       $banned_too    false for the members who can sign in
     * @return array|null
     */
    function vbulletin_find_user(string $column, int|string $value, bool $banned_too = false): ?array
    {
        $forum = vbulletin_auth_connect();

        if ($forum === null) {
            return null;
        }

        $hash = vbulletin_is_v5() ? 'token' : 'password, salt';
        $column = $column === 'userid' ? 'userid' : 'username';

        try {
            $query = $forum['db']->prepare(
                "SELECT userid, username, email, usergroupid, {$hash} FROM {$forum['users']} WHERE {$column} = :value" .
                    ($banned_too ? '' : ' AND usergroupid <> :banned') .
                    ' LIMIT 1',
            );

            $query->execute(['value' => $value] + ($banned_too ? [] : ['banned' => VBULLETIN_BANNED_GROUP]));
            $row = $query->fetch();
        } catch (PDOException $e) {
            kleeja_log('[vbulletin_integration] the members of the forum can not be read: ' . $e->getMessage());

            return null;
        }

        return $row ?: null;
    }

    /**
     * a name as vBulletin keeps it: & < > and " are encoded, not ' and not the &#number; of a letter
     * @param  string $text
     * @return string
     */
    function vbulletin_html_encode(string $text): string
    {
        return str_replace(
            ['<', '>', '"'],
            ['&lt;', '&gt;', '&quot;'],
            preg_replace('/&(?!#[0-9]+;)/', '&amp;', $text),
        );
    }

    /**
     * sign in with a forum account, in place of the accounts of Kleeja, see usrcp::data()
     * @param  string $name     the name as p() gives it, or the user id of the login cookie when $hashed
     * @param  string $pass     the password as p() gives it, or the hash of the login cookie when $hashed
     * @param  int    $expire   when the login cookie expires
     * @param  bool   $hashed   checking the login cookie of an administrator, Kleeja does it on every visit
     * @param  bool   $loginadm the sign in of the control panel, it checks the password of the signed-in member
     * @return bool
     */
    function vbulletin_auth_login(
        string $name,
        #[\SensitiveParameter] string $pass,
        int $expire,
        bool $hashed = false,
        bool $loginadm = false,
    ): bool {
        global $usrcp, $userinfo;

        $row = $hashed
            ? vbulletin_find_user('userid', (int) $name)
            : vbulletin_find_user('username', vbulletin_html_encode(htmlspecialchars_decode($name, ENT_QUOTES)));

        if ($row === null) {
            return false;
        }

        $v5 = vbulletin_is_v5();
        $hash = (string) ($v5 ? $row['token'] : $row['password']);

        if ($hash === '') {
            return false;
        }

        if ($hashed) {
            // the login cookie keeps the hash of the forum, as it keeps the hash of Kleeja for its own accounts
            $valid = hash_equals($hash, $pass);
        } else {
            // p() encoded what was typed, the forum hashed it as it was typed
            $typed = htmlspecialchars_decode($pass, ENT_QUOTES);
            $valid = $v5 ? password_verify(md5($typed), $hash) : hash_equals($hash, md5(md5($typed) . $row['salt']));
        }

        if (!$valid) {
            return false;
        }

        $is_admin = (int) $row['usergroupid'] === VBULLETIN_ADMIN_GROUP;

        $user = [
            'id' => (int) $row['userid'],
            'name' => kleeja_html_display($row['username']),
            'mail' => kleeja_html_display($row['email']),
            // the groups of Kleeja: administrators, or members
            'group_id' => $is_admin ? 1 : 3,
            // the founders manage the plugins, the styles and the updates of Kleeja
            'founder' => $is_admin ? 1 : 0,
            'password' => $hash,
            'last_visit' => time(),
        ];

        // the control panel asks again for the password of the member who is signed in, not of another one
        if ($loginadm && (int) $usrcp->id() !== $user['id']) {
            return false;
        }

        $userinfo = $user;

        if ($loginadm) {
            return true;
        }

        if ($hashed) {
            // a visit of a signed-in administrator, Kleeja reads who it is from these
            $constants = [
                'id' => 'USER_ID',
                'group_id' => 'GROUP_ID',
                'name' => 'USER_NAME',
                'mail' => 'USER_MAIL',
                'last_visit' => 'LAST_VISIT',
            ];

            foreach ($constants as $key => $constant) {
                if (!defined($constant)) {
                    define($constant, $user[$key]);
                }
            }
        } else {
            $usrcp->set_login_cookie($user, $expire);
        }

        return true;
    }

    /**
     * the name of a member of the forum, for the files of a member, banned members too
     * @param  int          $user_id
     * @return string|false encoded for HTML
     */
    function vbulletin_auth_username(int $user_id): string|false
    {
        static $names = [];

        if (!array_key_exists($user_id, $names)) {
            $row = vbulletin_find_user('userid', $user_id, banned_too: true);
            $names[$user_id] = $row === null ? false : kleeja_html_display($row['username']);
        }

        return $names[$user_id];
    }

    /**
     * a page of the forum, for the links of the pages that Kleeja leaves to the forum
     * @param  string $page lostpw, register or profile
     * @return string encoded for HTML, empty when the forum address isn't a web address
     */
    function vbulletin_forum_link(string $page): string
    {
        $settings = vbulletin_settings();
        $forum = rtrim($settings['vbulletin_intr_link'], '/');

        if (!preg_match('#^https?://[^/\s]+#i', $forum)) {
            return '';
        }

        $pages = vbulletin_is_v5($settings)
            ? ['lostpw' => '/lostpw', 'register' => '/register', 'profile' => '/settings/profile']
            : [
                'lostpw' => '/login.php?do=lostpw',
                'register' => '/register.php',
                'profile' => '/profile.php?do=editprofile',
            ];

        return htmlspecialchars($forum . $pages[$page], ENT_QUOTES);
    }
}
