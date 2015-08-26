<?php

function update_info(&$everything_ok) {
	global $templates, $files_priv, $files_del, $modules;
	
	// Sprawdzamy ustawienia modułuów
	$server_modules = '';
	foreach ($modules as $module) {
		if ($module['value']) {
			$status = "correct";
			$title = "Prawidłowo";
		} else {
			$status = "incorrect";
			$title = "Nieprawidłowo";
		}

		$server_modules .= eval($templates->install_render('module'));
		
		if (!$module['value'] && $module['must-be'])
			$everything_ok = false;
	}
	if (strlen($server_modules)) {
		$text = "Moduły na serwerze";
		$data = $server_modules;
		$server_modules = eval($templates->install_render('update_info_brick'));
	}

	$files_privilages = '';
	foreach ($files_priv as $file) {
		if (!strlen($file))
			continue;

		if (is_writable(SCRIPT_ROOT . $file))
			$status = "ok";
		else {
			$status = "bad";
			$everything_ok = false;
		}

		$files_privilages .= eval($templates->install_render('file'));
	}
	if (strlen($files_privilages)) {
		$text = "Uprawnienia do zapisu";
		$data = $files_privilages;
		$files_privilages = eval($templates->install_render('update_info_brick'));
	}

	$files_delete = '';
	foreach ($files_del as $file) {
		if (!strlen($file))
			continue;

		if (!file_exists(SCRIPT_ROOT . $file))
			$status = "ok";
		else {
			$status = "bad";
			$everything_ok = false;
		}

		$files_delete .= eval($templates->install_render('file'));
	}
	if (strlen($files_delete)) {
		$text = "Pliki do usunięcia";
		$data = $files_delete;
		$files_delete = eval($templates->install_render('update_info_brick'));
	}

	return eval($templates->install_render('update_info'));
}

function SplitSQL($file, $delimiter = ';')
{
    set_time_limit(0);
    
    $queries = array();

    if (is_file($file) === true)
    {
        $file = fopen($file, 'r');

        if (is_resource($file) === true)
        {
            $query = array();

            while (feof($file) === false)
            {
                $query[] = fgets($file);

                if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1)
                {
					$query = trim(implode('', $query));
                    $queries[] = $query;
                }

                if (is_string($query) === true)
                {
                    $query = array();
                }
            }

			fclose($file);
            return $queries;
        }
    }

    return false;
}
