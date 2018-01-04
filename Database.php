<?php
/*
 * This file is part of foxverse
 * Copyright (C) 2017 Steph Lockhomes, Billy Humphreys
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
global $mysqli;

class Database {
	public function connect() {
		global $mysqli;

		if (!$mysqli)
			$this->mysqli = new mysqli(getenv("SQLI_HOST"), getenv("SQLI_USER"), getenv("SQLI_PASSWORD"), getenv("SQLI_DATABASE"));

		if ($this->mysqli->connect_error) {
			return false;
		}

		return $mysqli;
	}

	public function disconnect() {
		if ($this->mysqli) {
			return $this->mysqli->close();
		} else {
			return false;
		}
	}

	public function getResult(&$stmt) {
		$result = array();
		$stmt->store_result();

		for ($i = 0; $i < $stmt->num_rows; $i++) {
			$meta = $stmt->result_metadata();
			$params = array();

			while ($field = $meta->fetch_field()) {
				$params[] = &$result[$i][$field->name];
			}

			call_user_func_array(array($stmt, "bind_result"), $params);
			$stmt->fetch();
		}

		$stmt->close();
		return $result;
	}
}