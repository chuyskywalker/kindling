<?php

// utility function to pretty print
function clie($msg) {
	echo '['. date('Y-m-d H:i:s') .'] ' . $msg . "\n";
}

//function tableResults($r) {
//	if (empty($r)) {
//		return '<p>No Results</p>';
//	}
//	$t = '<table><thead><tr><th>'. count($r) .'</th>';
//	foreach ($r[0] as $key => $data) {
//		$t .= '<th>'. $key .'</th>';
//	}
//	$t .= '</tr></thead><tbody>';
//	foreach ($r as $gamedata) {
//		$t .= '<tr>';
//		/// <img src="http://cdn.steampowered.com/v/gfx/apps/20920/capsule_sm_120.jpg?t=1305625352" alt="Buy The Witcher 2: Assassins of Kings Digital Premium Edition" width="120" height="45">
//
//		$t .= '<td>' . (isset($gamedata[History::C_APPID]) ? '<img width="120" height="45" src="http://cdn.steampowered.com/v/gfx/'. str_replace('/', 's/', $gamedata[History::C_APPID]) .'/capsule_sm_120.jpg" />' : '') .'</td>';
//		foreach ($gamedata as $colname => $field) {
//			switch ($colname) {
//				case History::CURRENCY_US:
//				case History::CURRENCY_UK:
//				case History::CURRENCY_SE:
//				case History::CURRENCY_NO:
//				case History::CURRENCY_AU:
//					$field = $field / 100;
//					$t .= '<td>'. formatCurrency($field, $colname) .'</td>';
//					break;
//				case History::C_TITLE:
//					if (isset($gamedata[History::C_APPID])) {
//						$field = '<a href="http://store.steampowered.com/'. $gamedata[History::C_APPID] .'/">' . $field . '</a>';
//					}
//					$t .= '<td>'. $field .'</td>';
//					break;
//				default:
//					$split = explode('_', $colname);
//					if (count($split) == 2 && in_array($split[0], array('p','c','change')) && in_array($split[1], History::$currencies)) {
//						$field = $field / 100;
//						$t .= '<td>'. formatCurrency($field, $split[1]) .'</td>';
//					}
//					else {
//						$t .= '<td>'. $field .'</td>';
//					}
//			}
//		}
//		$t .= '</tr>';
//	}
//	$t .= '</tbody></table>';
//	return $t;
//}

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

/**
 * Find $imgName in the content/images folder, copy it to the site folder, apply any thumbnailing
 * routines ($params), and return the absolute path usable in a src attribute
 *
 * The params you may use mirror the params for use in TimThumb (http://www.binarymoon.co.uk/projects/timthumb/)
 *
 * I have also added 'au' which means "Allow Upsizeing" and is a boolean value (passing true/false or 0/1 will work).
 * Use the 'au' param if some of your images are NOT larger than the size you are shrinking to so that the image does
 * not get scaled up to the w/h parameters you set. (Unless you want that to happen, of course)
 *
 * @param string $imgName
 * @param array $params Specify options for how to treat the image at copy. See phpDoc for more info
 * @return mixed
 */
function image($imgName, $params=array()) {

	$source = BASEDIR . '/uploads/' . $imgName;

	if (count($params)) {
		require_once BASEDIR.'/../code/libs/phpthumb/ThumbLib.inc.php';
		require_once BASEDIR.'/../code/libs/phpthumb/PhpThumb.inc.php';
		require_once BASEDIR.'/../code/libs/phpthumb/ThumbBase.inc.php';
		require_once BASEDIR.'/../code/libs/phpthumb/GdThumb.inc.php';
		// this image is supposed to be sized differently
		// figure out what the cached file name would be (based on the options)
		// If the file exists AND the source file HAS NOT changed, then the thumb need not be recreated.
		// If the file does not exist OR the source HAS changed, then recreate the thumbnail
		$fileToBe = imageCacheName($source, $params);

		if (!file_exists($fileToBe)) {
			// ah, either this thumb has never been made, or the source file changed, thus we need a refresh
			try
			{
				$thumb = PhpThumbFactory::create($source, $params);
				if (isset($params['w']) && (int)$params['w'] > 0 && isset($params['h']) && (int)$params['h'] > 0) {
					// adaptive
					$thumb
						->adaptiveResizeQuadrant($params['w'], $params['h'], (isset($params['c']) ? $params['c'] : null))
						->save($fileToBe);
				}
				elseif ((isset($params['w']) && (int)$params['w'] > 0) || (isset($params['h']) && (int)$params['h'] > 0)) {
					// resize to max of one
					$thumb
						->resize((isset($params['w']) ? (int)$params['w'] : 0), (isset($params['h']) ? (int)$params['h'] : 0))
						->save($fileToBe);
				}
				else {
					// You have to pick at least one resize value...
//					die('You must select at least one dimension when resizing an image.' . $source . json_encode($params));
					return str_replace(BASEDIR, '', $source);
				}
			}
			catch (Exception $e) {
//				die('Failed to open image.' . $e->getMessage());
				return str_replace(BASEDIR, '', $source);
			}
		}

		$source = $fileToBe;

	}

	return str_replace(BASEDIR, '', $source);

}

/**
 * Given a file name and an array of parameters, come up with a unique filename.
 * @param string $imgFile
 * @param array $params
 * @return string
 */
function imageCacheName($imgFile, $params) {
	$filename = basename($imgFile);
	$hash = md5(serialize($params) . $imgFile);
	return str_replace($filename, 'th_'.$hash.'_'.$filename, $imgFile);
}

function getItemClasses() {
    static $classes;
    if (!isset($classes)) {
        $classes = array();
        $all = get_declared_classes();
        foreach ($all as $classname) {
            if (preg_match('/^Item_(.*)/', $classname, $m)) {
                $classes[] = $m[1];
            }
        }
    }
    return $classes;
}

function hasAuth() {
    return isset($_SESSION['hasAuth']) && $_SESSION['hasAuth'] === true;
}

function giveAuth() {
    session_regenerate_id(true);
    $_SESSION['hasAuth'] = true;
}
