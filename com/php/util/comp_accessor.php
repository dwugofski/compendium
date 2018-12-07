<?php

include_once(__DIR__."/errors.php");
include_once(__DIR__."/mysql.php");

class CompAccessor {
	protected $id;

	static protected function _accessor_find_by($colname, $val, $columns, $types, $tablename, $primary_key) {
		if (empty($tablename)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find_by() No tablename entered");
		elseif (empty($colname)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find_by(table='%s') No column name entered", $tablename);
		elseif (is_null($val)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find_by(table='%s') No value entered", $tablename);
		elseif (empty($columns)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find_by(table='%s') No columns entered", $tablename);
		elseif (empty($types)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find_by(table='%s') No types entered", $tablename);
		elseif (empty($primary_key)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find_by(table='%s') No primary key entered", $tablename);
		elseif (in_array($primary_key, $columns)) {
			if (in_array($colname, $columns)) {
				$sql = "SELECT ".$primary_key." FROM ".$tablename." WHERE ".$colname." = ?";
				return MYSQL::run_query($sql, $types[$colname], [&$val]);
			} else ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find_by(table='%s') column \"%s\" not recognized", $tablename, $colname);
		} else ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find_by(table='%s') parimary key \"%s\" not recognized", $tablename, $primary_key);
	}

	static protected function _accessor_find($value, $identifier, $identifiers, $columns, $types, $tablename, $primary_key) {
		$rows = null;

		if (empty($tablename)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find() No tablename entered");
		elseif (is_null($value)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find(table='%s') No value entered", $tablename);
		elseif (empty($identifier)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find(table='%s') No identifier entered", $tablename);
		elseif (empty($identifiers)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find(table='%s') No identifiers entered", $identifiers);
		elseif (empty($columns)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find(table='%s') No columns entered", $tablename);
		elseif (empty($types)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find(table='%s') No types entered", $tablename);
		elseif (empty($primary_key)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_find(table='%s') No primary key entered", $tablename);
		else {
			if (array_key_exists($identifier, $identifiers)) {
				$rows = self::_accessor_find_by($identifiers[$identifier], $value, $columns, $types, $tablename, $primary_key);
			} else ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_accessor_find() Cannot find a column name match for identifier '%s'", json_encode($page_ident));
		}

		return $rows;
	}

	protected function _accessor_get($colname, $count, $columns, $types, $tablename, $primary_key) {
		$ret = null;

		if (empty($tablename)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_get() No tablename entered");
		elseif (empty($colname)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_get(table='%s') No column name entered", $tablename);
		elseif (empty($columns)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_get(table='%s') No columns entered", $tablename);
		elseif (empty($types)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_get(table='%s') No types entered", $tablename);
		elseif (empty($primary_key)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_get(table='%s') No primary key entered", $tablename);
		elseif (empty($colname)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_get() No column name entered");
		elseif (in_array($colname, $columns)) {
			$rows = MYSQL::run_query("SELECT ".$colname." FROM ".$tablename." WHERE ".$primary_key." = ?", $types[$primary_key], [$this->id]);
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

	protected function _accessor_set($colname, $val, $columns, $types, $tablename, $primary_key) {
		if (empty($tablename)) ERRORS::log(ERRORS::ACCESSOR_ERROR, "CompAccessor::_accessor_set() No tablename entered");
		elseif (empty($colname)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_accessor_set(table='%s') No column name entered", $tablename);
		elseif (is_null($val)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_accessor_set(table='%s') No value entered", $tablename);
		elseif (empty($columns)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_accessor_set(table='%s') No columns entered", $tablename);
		elseif (empty($types)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_accessor_set(table='%s') No types entered", $tablename);
		elseif (empty($primary_key)) ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_accessor_set(table='%s') No primary key entered", $tablename);
		elseif (in_array($colname, self::COLUMN_NAMES)) {
			$rows = MYSQL::run_query(
				"UPDATE ".$tablename." SET ".$col_name." = ? WHERE ".$primary_key." = ?",
				$types[$colname].$types[$primary_key],
				[$val, $this->id]
			);
		} else ERRORS::log(ERRORS::PAGE_ERROR, "CompAccessor::_accessor_set() Column '%s' not recognized", $colname);
	}
}

?>