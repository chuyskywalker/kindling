<?php

class Apps extends SuperTable {

	const NAME = 'apps';

	const C_APPID = 'appid';
	const C_TITLE = 'title';
	const C_TYPE = 'type';
	const C_CURRENCY = 'currency';

	const C_AMOUNT = 'amount';
	const C_PREVIOUSAMOUNT = 'previousamount';

	const C_FIRSTUID = 'firstuid';
	const C_LATESTUID = 'latestuid';
	const C_CHANGEUID = 'changeuid';

	static $bind_types = array(
		self::C_APPID => 's',
		self::C_TITLE => 's',
		self::C_TYPE => 's',
		self::C_CURRENCY => 's',
		self::C_AMOUNT => 'd',
		self::C_PREVIOUSAMOUNT => 'd',
		self::C_FIRSTUID => 's',
		self::C_LATESTUID => 's',
		self::C_CHANGEUID => 's',
	);

	static function getApp($currency, $appid) {
		return self::factory()->get_row(array('*'), array(self::C_CURRENCY => $currency, self::C_APPID=> $appid));
	}

	static function getApps($currency=false, $columns=array('*'), $order=array()) {
		$where = array();
		if (in_array($currency, self::$currencies)) {
			$where = array(self::C_CURRENCY => $currency);
		}
		return self::factory()->get_results($columns, $where, 0, $order);
	}

	/**
	 * @static
	 * @param string $currency
	 * @param integer $count
	 * @return array
	 */
	static function getRandomApps($currency, $count) {
		return self::factory()->sql_get_results('SELECT * FROM apps WHERE currency = ? ORDER BY rand() LIMIT ?', 'si', array($currency, $count));
	}

	static function insertUpdate($appid, $currency, $title, $type, $amount, $uid) {
		$app = self::getApp($currency, $appid);
		if ($app) {
			// already exists, update
			$update = array(
				// in weird cases, the title/type can actually change from update to update...
				self::C_TITLE => $title,
				self::C_TYPE => $type,
				self::C_LATESTUID => $uid,
				self::C_AMOUNT => $amount,
			);

			if ($app[self::C_AMOUNT] != $amount) {
				// price change!
				$update[self::C_PREVIOUSAMOUNT] = $app[self::C_AMOUNT];
				$update[self::C_CHANGEUID] = $uid;
			}

			self::factory()->update($update, array(
				self::C_CURRENCY => $currency,
				self::C_APPID => $appid,
			));
		}
		else {
			// new app! Insert!
			self::factory()->insert(array(
				self::C_APPID => $appid,
				self::C_CURRENCY => $currency,
				self::C_TITLE => $title,
				self::C_TYPE => $type,
				self::C_AMOUNT => $amount,
				self::C_FIRSTUID => $uid,
				self::C_LATESTUID => $uid,
			));
		}
		// insert into apps (appid, currency, title, type, amount, firstuid, latestuid) values (?,?,?,?,?,?,?)
		// ON DUPLICATE KEY UPDATE previousamount = amount, changeuid = ?, latestuid = ?, amount = ?
	}


	static function getChangedGames($currency, $old=null, $current=null) {
		if (empty($current)) {
			$current = Updates::getLatest();
		}
		if (empty($old)) {
			$old = Updates::getPrevious();
		}
		$sql = trim('
SELECT
*, amount-previousamount as `change`
FROM `apps`
where
    currency = ?
and changeuid BETWEEN ? and ?
ORDER BY amount-previousamount asc;
');

		return self::factory()->sql_get_results($sql, 'sii', array($currency, $old, $current));

	}

	static function getNewGames($currency, $old=null, $current=null) {
		if (empty($current)) {
			$current = Updates::getLatest();
		}
		if (empty($old)) {
			$old = Updates::getPrevious();
		}
		$sql = trim('
SELECT
*
FROM `apps`
where
    currency = ?
and firstuid BETWEEN ? and ?
ORDER BY title;
');

		return self::factory()->sql_get_results($sql, 'sii', array($currency, $old, $current));

	}

	static function searchLatest($currency, $title, $limit=10) {
		if (!is_numeric($limit) || $limit <= 0) {
			$limit = 10;
		}
		return self::factory()->sql_get_results('SELECT * FROM ' . self::NAME . ' WHERE `'. self::C_CURRENCY .'` = ? AND `' . self::C_TITLE . '` LIKE ? LIMIT ' . $limit, 'ss', array($currency, '%'.$title.'%'));
	}

	static function searchCount($currency, $title) {
		$rows = self::factory()->sql_get_results('SELECT count(*) as count FROM ' . self::NAME . ' WHERE `'. self::C_CURRENCY .'` = ? AND `' . self::C_TITLE . '` LIKE ?', 'is', array($currency, '%'.$title.'%'));
		return isset($rows[0]['count']) ? $rows[0]['count'] : 0;
	}

}
