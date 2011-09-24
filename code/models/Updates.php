<?php

class Updates extends SuperTable {

	const NAME = 'updates';

	const C_UID = 'uid';
	const C_DT = 'dt';

	static $bind_types = array(
		self::C_UID => 'i',
		self::C_DT => 's'
	);

	static function makeNewUpdate() {
		return self::factory()->insert(array(
			self::C_DT => self::mysql_now()
		));
	}

	static function getNthLatest($n) {
		$prev = self::factory()->get_results(array(self::C_UID), array(), $n, array(self::C_UID => 'DESC'));
		return isset($prev[$n-1][self::C_UID]) ? $prev[$n-1][self::C_UID] : false;
	}

	static function getLatest() {
		//return self::getNthLatest(1);
		// this is a bit more optimized...
		return self::factory()->get_col('max('. self::C_UID .')');
	}

	static function getPrevious() {
		return self::getNthLatest(2);
	}

	static function getDate($uid) {
		return self::factory()->get_col(self::C_DT, array(self::C_UID=>$uid));
	}

	static function validRange($start, $end) {
		$start = self::factory()->get_col(self::C_UID, array(self::C_UID => $start));
		$end   = self::factory()->get_col(self::C_UID, array(self::C_UID => $end));
		return ($start > 0 && $end > 0);
	}

}
