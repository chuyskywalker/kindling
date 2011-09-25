<?php

// utility function to pretty print
function clie($msg) {
	echo '['. date('Y-m-d H:i:s') .'] ' . $msg . "\n";
}

function tableResults($r) {
	if (empty($r)) {
		return '<p>No Results</p>';
	}
	$t = '<table><thead><tr><th>'. count($r) .'</th>';
	foreach ($r[0] as $key => $data) {
		$t .= '<th>'. $key .'</th>';
	}
	$t .= '</tr></thead><tbody>';
	foreach ($r as $gamedata) {
		$t .= '<tr>';
		/// <img src="http://cdn.steampowered.com/v/gfx/apps/20920/capsule_sm_120.jpg?t=1305625352" alt="Buy The Witcher 2: Assassins of Kings Digital Premium Edition" width="120" height="45">

		$t .= '<td>' . (isset($gamedata[History::C_APPID]) ? '<img width="120" height="45" src="http://cdn.steampowered.com/v/gfx/'. str_replace('/', 's/', $gamedata[History::C_APPID]) .'/capsule_sm_120.jpg" />' : '') .'</td>';
		foreach ($gamedata as $colname => $field) {
			switch ($colname) {
				case History::CURRENCY_US:
				case History::CURRENCY_UK:
				case History::CURRENCY_SE:
				case History::CURRENCY_NO:
				case History::CURRENCY_AU:
					$field = $field / 100;
					$t .= '<td>'. formatCurrency($field, $colname) .'</td>';
					break;
				case History::C_TITLE:
					if (isset($gamedata[History::C_APPID])) {
						$field = '<a href="http://store.steampowered.com/'. $gamedata[History::C_APPID] .'/">' . $field . '</a>';
					}
					$t .= '<td>'. $field .'</td>';
					break;
				default:
					$split = explode('_', $colname);
					if (count($split) == 2 && in_array($split[0], array('p','c','change')) && in_array($split[1], History::$currencies)) {
						$field = $field / 100;
						$t .= '<td>'. formatCurrency($field, $split[1]) .'</td>';
					}
					else {
						$t .= '<td>'. $field .'</td>';
					}
			}
		}
		$t .= '</tr>';
	}
	$t .= '</tbody></table>';
	return $t;
}

/**
 * Return a url safe string
 * @param string $str
 * @param array $replace
 * @param string $delimiter
 * @return string
 */
function toAscii($str, $replace=array(), $delimiter='-') {
	setlocale(LC_ALL, 'en_US.UTF8');
	if( !empty($replace) ) {
		$str = str_replace((array)$replace, ' ', $str);
	}

	$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
	$clean = preg_replace("#[^a-zA-Z0-9/_|+ -]#", '', $clean);
    $clean = preg_replace("#[/_|+ -]+#", $delimiter, $clean);
    $clean = strtolower(trim($clean, " $delimiter"));

	return $clean;
}