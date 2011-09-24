<?php

class Users extends SuperTable {

	const NAME = 'users';

	const C_UID = 'uid';
	const C_EMAIL = 'email';
	const C_SIGNUP = 'signup';
	const C_CODE = 'code';
	const C_CONFIRMED = 'confirmed';

	static $bind_types = array(
		self::C_UID => 'i',
		self::C_EMAIL => 's',
		self::C_SIGNUP => 's',
		self::C_CODE => 's',
		self::C_CONFIRMED => 'i'
	);

	static function getUserByEmail($email) {
		return self::factory()->get_row(array('*'), array(self::C_EMAIL => $email));
	}

	static function getUserById($id) {
		return self::factory()->get_row(array('*'), array(self::C_UID => $id));
	}

	static function getUserByCode($code) {
		return self::factory()->get_row(array('*'), array(self::C_CODE => $code));
	}

	static function isUser($uid) {
		$user = self::getUserById($uid);
		return isset($user[self::C_UID]);
	}

	static function confirmUser($uid) {
		return self::factory()->update(array(self::C_CONFIRMED => 1), array(self::C_UID => $uid));
	}

	static function addUser($email) {
		if (self::getUserByEmail($email)) {
			return false;
		}
		$uid = self::factory()->insert(array(
			self::C_EMAIL => $email,
			self::C_CODE => md5(uniqid('', true)),
			self::C_SIGNUP => self::mysql_now()
		));
		return self::getUserById($uid);
	}

	static function deleteUser($email) {
		self::factory()->delete(array(
			self::C_EMAIL => $email,
		), 1);
		return true;
	}

}
