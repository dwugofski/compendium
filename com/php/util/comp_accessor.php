<?php

include_once(__DIR__."/errors.php");
include_once(__DIR__."/mysql.php");

class CompAccessor {
	const TABLE_NAME = null;
	const PRIMARY_KEY = null;
	const COLUMN_NAMES = null;
	const COLUMN_TYPES = null;
	const IDENTIFIERS = null;

	protected $id;

	static protected function _find_by($colname, $val) {
		if (empty(static::TABLE_NAME)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find_by() No tablename entered");
		elseif (empty($colname)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find_by(table='%s') No column name entered", static::TABLE_NAME);
		elseif (is_null($val)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find_by(table='%s') No value entered", static::TABLE_NAME);
		elseif (empty(static::COLUMN_NAMES)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find_by(table='%s') No columns entered", static::TABLE_NAME);
		elseif (empty(static::COLUMN_TYPES)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find_by(table='%s') No types entered", static::TABLE_NAME);
		elseif (empty(static::PRIMARY_KEY)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find_by(table='%s') No primary key entered", static::TABLE_NAME);
		elseif (in_array(static::PRIMARY_KEY, static::COLUMN_NAMES)) {
			if (in_array($colname, static::COLUMN_NAMES)) {
				$sql = "SELECT ".static::PRIMARY_KEY." FROM ".static::TABLE_NAME." WHERE ".$colname." = ?";
				return MYSQL::run_query($sql, static::COLUMN_TYPES[$colname], [&$val]);
			} else ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find_by(table='%s') column \"%s\" not recognized", static::TABLE_NAME, $colname);
		} else ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find_by(table='%s') parimary key \"%s\" not recognized", static::TABLE_NAME, static::PRIMARY_KEY);
	}

	static protected function _find($value, $identifier) {
		$rows = null;

		if (empty(static::TABLE_NAME)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find() No tablename entered");
		elseif (is_null($value)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find(table='%s') No value entered", static::TABLE_NAME);
		elseif (empty($identifier)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find(table='%s') No identifier entered", static::TABLE_NAME);
		elseif (empty(static::IDENTIFIERS)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find(table='%s') No identifiers entered", $identifiers);
		elseif (empty(static::COLUMN_NAMES)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find(table='%s') No columns entered", static::TABLE_NAME);
		elseif (empty(static::COLUMN_TYPES)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find(table='%s') No types entered", static::TABLE_NAME);
		elseif (empty(static::PRIMARY_KEY)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_find(table='%s') No primary key entered", static::TABLE_NAME);
		else {
			if (is_a($value, static::class)) {
				$value = $value->id;
				$identifier = static::PRIMARY_KEY;
			}

			if (array_key_exists($identifier, static::IDENTIFIERS)) {
				$rows = self::_find_by(static::IDENTIFIERS[$identifier], $value);
			} else ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_find() Cannot find a column name match for identifier '%s'", json_encode($identifier));
		}

		return $rows;
	}

	static public function is($value, $identifier) {
		$rows = self::_find($value, $identifier);
		return (!empty($rows) && is_array($rows));
	}

	static public function equals($a, $b) {
		return $a->id == $b->id;
	}

	protected function _get($colname, $count=null) {
		$ret = null;

		if (empty(static::TABLE_NAME)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_get() No tablename entered");
		elseif (empty($colname)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_get(table='%s') No column name entered", static::TABLE_NAME);
		elseif (empty(static::COLUMN_NAMES)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_get(table='%s') No columns entered", static::TABLE_NAME);
		elseif (empty(static::COLUMN_TYPES)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_get(table='%s') No types entered", static::TABLE_NAME);
		elseif (empty(static::PRIMARY_KEY)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_get(table='%s') No primary key entered", static::TABLE_NAME);
		elseif (empty($colname)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_get() No column name entered");
		elseif (in_array($colname, static::COLUMN_NAMES)) {
			$rows = MYSQL::run_query(
				"SELECT ".$colname." FROM ".static::TABLE_NAME." WHERE ".static::PRIMARY_KEY." = ?",
				static::COLUMN_TYPES[static::PRIMARY_KEY],
				[$this->id]
			);
			if (is_array($rows) && count($rows) > 0) {
				if (isset($count)) {
					$count = (($count <= count($rows)) && ($count > 0)) ? $count : count($rows);
					$ret = [];
					for ($i=0; $i<$count; $i=$i+1) {
						$ret[] = $rows[$i][$colname];
					}
				} else {
					$ret = $rows[0][$colname];
				}
			} else ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_get() Page '%d' not found when trying to get '%s'", $this->id, $colname);
		} else ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_get() Column '%s' not recognized", $colname);

		return $ret;
	}

	protected function _set($colname, $val) {
		if (empty(static::TABLE_NAME)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_set() No tablename entered");
		elseif (empty($colname)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_set(table='%s') No column name entered", static::TABLE_NAME);
		elseif (is_null($val)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_set(table='%s') No value entered", static::TABLE_NAME);
		elseif (empty(static::COLUMN_NAMES)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_set(table='%s') No columns entered", static::TABLE_NAME);
		elseif (empty(static::COLUMN_TYPES)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_set(table='%s') No types entered", static::TABLE_NAME);
		elseif (empty(static::PRIMARY_KEY)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_set(table='%s') No primary key entered", static::TABLE_NAME);
		elseif (in_array($colname, self::COLUMN_NAMES)) {
			$rows = MYSQL::run_query(
				"UPDATE ".static::TABLE_NAME." SET ".$col_name." = ? WHERE ".static::PRIMARY_KEY." = ?",
				static::COLUMN_TYPES[$colname].static::COLUMN_TYPES[static::PRIMARY_KEY],
				[$val, $this->id]
			);
		} else ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_set() Column '%s' not recognized", $colname);
	}
}

?>