<?php

class ERRORS {
	const NO_ERROR 				= 0;
	const MYSQL_ERROR 			= 1;
	const USER_ERROR 			= 2;
	const PERMISSIONS_ERROR 	= 3;
	const PAGE_ERROR 			= 4;
	const UNKNOWN_ERROR 		= 5;

	const ERROR_STRINGS = [
		'NO ERROR             ',
		'MYSQL ERROR          ',
		'USER ERROR           ',
		'PERMISSIONS ERROR    ',
		'PAGE ERROR           ',
		'UNKNOWN ERROR        '
	];

	static private function error_to_string($error){
		if ($error <= self::UNKNOWN_ERROR && $error >= self::NO_ERROR) return self::ERROR_STRINGS[$error];
		else return self::ERROR_STRINGS[self::UNKNOWN_ERROR];
	}

	static public function log() {
		$size = func_num_args();
		$error = ($size < 1) ? self::UNKNOWN_ERROR : func_get_arg(0);
		$msg_args = array();
		$msg_args[] = ($size < 2) ? "" : func_get_arg(1);
		for ($i=2; $i<$size; $i+=1){
			$msg_args[] = func_get_arg($i);
		}
		$msg = call_user_func_array('sprintf', $msg_args);
		$log_string = self::error_to_string($error).": ".$msg;
		error_log($log_string);
		throw(new Exception($log_string));
	}
}

?>