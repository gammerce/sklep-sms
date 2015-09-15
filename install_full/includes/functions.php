<?php

function SplitSQL($file, $delimiter = ';')
{
	@set_time_limit(0);

	$queries = array();

	if (is_file($file) === true) {
		$file = fopen($file, 'r');

		if (is_resource($file) === true) {
			$query = array();

			while (feof($file) === false) {
				$query[] = fgets($file);

				if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
					$query = trim(implode('', $query));
					$queries[] = $query;
				}

				if (is_string($query) === true) {
					$query = array();
				}
			}

			fclose($file);
			return $queries;
		}
	}

	return false;
}