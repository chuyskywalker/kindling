<?php

class History extends SuperTable {

	const NAME = 'history';

	const C_HID = 'hid';
	const C_UID = 'uid';
	const C_APPID = 'appid';
	const C_TITLE = 'title';
	const C_TYPE = 'type';
	const C_CURRENCY = 'currency';
	const C_AMOUNT = 'amount';

	static $bind_types = array(
		self::C_HID => 'i',
		self::C_UID => 'i',
		self::C_APPID => 's',
		self::C_TITLE => 's',
		self::C_TYPE => 's',
		self::C_CURRENCY => 's',
		self::C_AMOUNT => 'd'
	);

	static function getPriceHistory($currency, $appid) {
		// Not only do we get the history, we pare it down to just the events where change occured

		$priceHistory = self::factory()->sql_get_results('
SELECT
	history.amount, updates.dt
FROM history
JOIN updates using(uid)
WHERE history.currency = ? AND history.appid = ?
ORDER BY updates.uid ASC

', 'ss', array($currency, $appid));

		$lastPrice = 0;
		$keepers = array();
		foreach ($priceHistory as $k => $historyState) {
			if ($lastPrice != $historyState['amount']) {
				if (isset($priceHistory[$k-1])) {
					$keepers[] = $k-1;
				}
				$keepers[] = $k;
				$lastPrice = $historyState['amount'];
			}
		}
		// if the last price isn't already included, do so here.
		if ($k != count($priceHistory)-1) {
			$keepers[] = count($priceHistory)-1;
		}

		$retArr = array();
		foreach ($keepers as $k) {
			$retArr[] = $priceHistory[$k];
		}

		return $retArr;

	}

}
