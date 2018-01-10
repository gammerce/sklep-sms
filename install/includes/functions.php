<?php

function is_installed()
{
    if (!file_exists(SCRIPT_ROOT . "credentials/database.php")) {
        return false;
    }

    require SCRIPT_ROOT . "credentials/database.php";

    try {
        $db = new Database($db_host, $db_user, $db_pass, $db_name);
        $db->close();
    } catch (SqlQueryException $exception) {
        return false;
    }

    return true;
}