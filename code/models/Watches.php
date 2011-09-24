<?php

class Watches extends SuperTable {

	const NAME = 'watches';

	const C_WID = 'wid';
	const C_UID = 'uid'; // this is actually USER id, not UPDATE id. Bad naming...
	const C_APPID = 'appid';
	const C_CURRENCY = 'currency';
	const C_PRICE = 'price';
	const C_CREATED = 'created';
	const C_TRIGGEREDBYUPDATE = 'triggeredByUpdate';
	const C_SENT = 'sent';
	const C_DELETED = 'deleted';

	static $bind_types = array(
		self::C_WID => 'i',
		self::C_UID => 'i',
		self::C_APPID => 's',
		self::C_CURRENCY => 's',
		self::C_PRICE => 'd',
		self::C_CREATED => 's',
		self::C_TRIGGEREDBYUPDATE => 'i',
		self::C_SENT => 's',
		self::C_DELETED => 'i'
	);

	static function getWatches($uid) {
		return self::factory()->get_results(array('*'), array(self::C_UID => $uid), 0, array(self::C_WID));
	}

	static function softDeleteWatch($wid, $uid) {
		return self::factory()->update(array(self::C_DELETED => 1), array(self::C_UID => $uid, self::C_WID => $wid));
	}

	static function getWatchesWithAppData($uid) {
		// TODO: Update with apps
		return self::factory()->sql_get_results('SELECT * FROM ' . self::NAME
												. ' JOIN ' . Apps::NAME . ''
												. ' WHERE ' . self::NAME .'.'. self::C_UID . ' = ? '
												. ' AND ' . Apps::NAME .'.'. Apps::C_APPID . ' = ' . self::NAME .'.'. self::C_APPID
												. ' AND ' . Apps::NAME .'.'. Apps::C_CURRENCY . ' = ' . self::NAME .'.'. self::C_CURRENCY
												. ' ORDER BY ' . self::C_CREATED . ' DESC ',
												'i', array($uid));
	}

	static function addWatch($uid, $appid, $currency, $price) {
		if (!Users::getUserById($uid)) {
			return false;
		}
		if (!Apps::getApp($currency, $appid)) {
			return false;
		}
		if (!in_array($currency, History::$currencies)) {
			return false;
		}
		if ($price <= 0 ) {
			return false;
		}
		return self::factory()->insert(array(
			self::C_UID => $uid,
			self::C_APPID => $appid,
			self::C_CURRENCY => $currency,
			self::C_PRICE => $price,
			self::C_CREATED => self::mysql_now()
		));
	}

	static function getNextToEmail() {
		return self::factory()->sql_get_results('SELECT * FROM ' . self::NAME
												. ' JOIN ' . Users::NAME . ' USING('. self::C_UID .')'
												. ' JOIN ' . Apps::NAME . ' ON(' . Apps::NAME .'.'. Apps::C_APPID    .' = '. self::NAME .'.'. self::C_APPID
																		. ' AND '. Apps::NAME .'.'. Apps::C_CURRENCY .' = '. self::NAME .'.'. self::C_CURRENCY . ')'
												. ' WHERE `'. self::C_TRIGGEREDBYUPDATE .'` IS NOT NULL'
												. ' AND `'. self::C_SENT .'` IS NULL'
												. ' ORDER BY `'. self::C_TRIGGEREDBYUPDATE .'` ASC LIMIT 1', '', array());
	}

	static function clearWatch($wid) {
		return self::factory()->update(array(self::C_SENT => self::mysql_now()), array(self::C_WID => $wid));
	}

	static function trigger($updateid, $appid, $currency, $currentPrice) {
		// demo code
//		$sql= 'SELECT count(*) as count FROM ' . self::NAME
//			. ' WHERE `' . self::C_APPID . '` = ?'
//			. ' AND `' . self::C_CURRENCY . '` = ?'
//			. ' AND `' . self::C_TRIGGEREDBYUPDATE . '` IS NULL'
//			. ' AND `' . self::C_SENT . '` IS NULL'
//			. ' AND `' . self::C_DELETED . '` = ?'
//			. ' AND `' . self::C_PRICE . '` >= ?'
//			;
//		$results = self::factory()->sql_get_results($sql, 'ssii', array($appid, $currency, 0, $currentPrice));
//		return $results[0]['count'];

		// actual update
		$sql= 'UPDATE ' . self::NAME . ' SET `' . self::C_TRIGGEREDBYUPDATE . '` = ?'
			. ' WHERE `' . self::C_APPID . '` = ?'
			. ' AND `' . self::C_CURRENCY . '` = ?'
			. ' AND `' . self::C_TRIGGEREDBYUPDATE . '` IS NULL'
			. ' AND `' . self::C_SENT . '` IS NULL'
			. ' AND `' . self::C_DELETED . '` = ?'
			. ' AND `' . self::C_PRICE . '` >= ?'
			;
		$stmt = self::factory()->bound_query($sql, 'issii', array($updateid, $appid, $currency, 0, $currentPrice));
		$affected = $stmt->affected_rows;
		$stmt->close();
		return $affected;
	}

}
