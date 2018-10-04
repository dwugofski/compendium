<?php

class ERRORS {
	const NO_ERROR 				= 0;
	const MYSQL_ERROR 			= 1;
	const USER_ERROR 			= 2;
	const PERMISSIONS_ERROR 	= 3;
	const UNKNOWN_ERROR 		= 4;

	const ERROR_STRINGS = [
		'NO ERROR             ',
		'MYSQL ERROR          ',
		'USER ERROR           ',
		'PERMISSIONS ERROR    ',
		'UNKNOWN ERROR        '
	];

	static private function error_to_string($error){
		if ($error <= self::UNKNOWN_ERROR && $error >= self::NO_ERROR) return self::ERROR_STRINGS[$error];
		else return self::ERROR_STRINGS[self::UNKNOWN_ERROR];
	}

	static public function log($error, $msg) {
		$log_string = self::error_to_string($error).": ".$msg;
		error_log($log_string);
		throw(new Exception($log_string));
	}
}

?>