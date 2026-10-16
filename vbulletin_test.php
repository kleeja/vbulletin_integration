<?php
//
// vbulletin_integration, the connection test of its page in the control panel
//

// not for directly open
if (!defined('IN_ADMIN')) {
    exit();
}

if (!function_exists('vbulletin_integration_test')) {
    /**
     * can Kleeja read the members of the forum with these settings: their details, the connection,
     * the table of the members and its administrators. It only reads, nothing is written to the forum
     * @param  array $settings as vbulletin_settings() gives them
     * @return array the steps, each one has its status (ok, error or skipped), title, detail and hint, with HTML
     */
    function vbulletin_integration_test(array $settings): array
    {
        $steps = [];

        foreach (['DETAILS', 'CONNECT', 'MEMBERS', 'ADMINS'] as $name) {
            $steps[] = [
                'status' => 'skipped',
                'title' => vbulletin_integration_word('VBULLETIN_INTR_STEP_' . $name),
                'detail' => vbulletin_integration_word('VBULLETIN_INTR_TEST_SKIPPED'),
                'hint' => '',
            ];
        }

        $path_type = $settings['vbulletin_intr_type'] !== 'db';
        $details = vbulletin_db_details($settings);

        // the connection details
        if (isset($details['error'])) {
            $steps[0] = vbulletin_test_result(
                $steps[0],
                'error',
                sprintf(vbulletin_integration_word($details['error']), vbulletin_test_code($details['file'])),
                $path_type ? 'VBULLETIN_INTR_STEP_FILE_HINT' : '',
            );

            return $steps;
        }

        $database = sprintf(
            vbulletin_integration_word('VBULLETIN_INTR_STEP_DETAILS_DB'),
            vbulletin_test_code($details['name']),
            vbulletin_test_code($details['host'] . ':' . $details['port']),
            vbulletin_test_code($details['user']),
        );

        $steps[0] = vbulletin_test_result(
            $steps[0],
            'ok',
            ($path_type
                ? sprintf(
                        vbulletin_integration_word('VBULLETIN_INTR_STEP_DETAILS_FILE'),
                        vbulletin_test_code($details['file']),
                    ) . ' '
                : '') . $database,
        );

        // the connection
        if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
            $steps[1] = vbulletin_test_result(
                $steps[1],
                'error',
                vbulletin_integration_word('VBULLETIN_INTR_STEP_CONNECT_NO_DRIVER'),
                'VBULLETIN_INTR_STEP_CONNECT_NO_DRIVER_HINT',
            );

            return $steps;
        }

        try {
            $db = vbulletin_open_database($details);
            $version = (string) $db->query('SELECT VERSION()')->fetchColumn();
        } catch (PDOException $e) {
            // the error of a connection has its number in the message: SQLSTATE[HY000] [1045] Access denied ..
            $number = preg_match('/\[(\d{4})\]/', $e->getMessage(), $match) ? (int) $match[1] : 0;

            $steps[1] = vbulletin_test_result(
                $steps[1],
                'error',
                htmlspecialchars($e->getMessage(), ENT_QUOTES),
                match ($number) {
                    1045 => 'VBULLETIN_INTR_STEP_CONNECT_DENIED_HINT',
                    1044, 1049 => 'VBULLETIN_INTR_STEP_CONNECT_NO_DB_HINT',
                    1115, 2019 => 'VBULLETIN_INTR_STEP_CONNECT_CHARSET_HINT',
                    default => 'VBULLETIN_INTR_STEP_CONNECT_SERVER_HINT',
                },
            );

            return $steps;
        }

        $steps[1] = vbulletin_test_result(
            $steps[1],
            'ok',
            sprintf(vbulletin_integration_word('VBULLETIN_INTR_STEP_CONNECT_OK'), vbulletin_test_code($version)),
        );

        // the table of the members, with the columns that the sign in reads
        $table = $details['prefix'] . 'user';
        $hash = vbulletin_is_v5($settings) ? 'token' : 'password, salt';

        try {
            $db->query("SELECT userid, username, email, usergroupid, {$hash} FROM `{$table}` LIMIT 1")->fetchAll();
            $members = (int) $db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        } catch (PDOException $e) {
            $number = (int) ($e->errorInfo[1] ?? 0);

            $steps[2] = match ($number) {
                1146 => vbulletin_test_result(
                    $steps[2],
                    'error',
                    sprintf(
                        vbulletin_integration_word('VBULLETIN_INTR_STEP_MEMBERS_NO_TABLE'),
                        vbulletin_test_code($table),
                    ),
                    'VBULLETIN_INTR_STEP_MEMBERS_NO_TABLE_HINT',
                ),
                1054 => vbulletin_test_result(
                    $steps[2],
                    'error',
                    vbulletin_integration_word('VBULLETIN_INTR_STEP_MEMBERS_NO_COLUMNS'),
                    'VBULLETIN_INTR_STEP_MEMBERS_NO_COLUMNS_HINT',
                ),
                default => vbulletin_test_result($steps[2], 'error', htmlspecialchars($e->getMessage(), ENT_QUOTES)),
            };

            return $steps;
        }

        $steps[2] = vbulletin_test_result(
            $steps[2],
            'ok',
            sprintf(
                vbulletin_integration_word('VBULLETIN_INTR_STEP_MEMBERS_OK'),
                vbulletin_test_code($table),
                number_format($members),
            ),
        );

        // the members who will open the control panel
        try {
            $query = $db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE usergroupid = :admins");
            $query->execute(['admins' => VBULLETIN_ADMIN_GROUP]);
            $admins = (int) $query->fetchColumn();
        } catch (PDOException $e) {
            $steps[3] = vbulletin_test_result($steps[3], 'error', htmlspecialchars($e->getMessage(), ENT_QUOTES));

            return $steps;
        }

        $steps[3] = $admins
            ? vbulletin_test_result(
                $steps[3],
                'ok',
                sprintf(vbulletin_integration_word('VBULLETIN_INTR_STEP_ADMINS_OK'), number_format($admins)),
            )
            : vbulletin_test_result(
                $steps[3],
                'error',
                vbulletin_integration_word('VBULLETIN_INTR_STEP_ADMINS_NONE'),
                'VBULLETIN_INTR_STEP_ADMINS_NONE_HINT',
            );

        return $steps;
    }

    /**
     * a step of the test with what it found
     * @param  array  $step
     * @param  string $status ok or error
     * @param  string $detail with HTML
     * @param  string $hint   the word of the plugin that says how to fix it, if any
     * @return array
     */
    function vbulletin_test_result(array $step, string $status, string $detail, string $hint = ''): array
    {
        return [
            'status' => $status,
            'detail' => $detail,
            'hint' => $hint === '' ? '' : vbulletin_integration_word($hint),
        ] + $step;
    }

    /**
     * a value in the detail of a step, a path or a server name reads from left to right in Arabic too
     * @param  string $text
     * @return string
     */
    function vbulletin_test_code(string $text): string
    {
        return '<code dir="ltr">' . htmlspecialchars($text, ENT_QUOTES) . '</code>';
    }
}
