<?php

namespace App\Model;

use Nette;


class DemoModel extends \Nette\Object {

	/** @var Nette\Database\Context */
	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	public function getBigSelection() {
		return $this->database->table('way_node');
	}

	public function getUserSelection() {
		return $this->database->table('user');
	}

	public function getEmptySelection() {
		return $this->database->table('empty');
	}

	public function getUserGroupsSelection($user_id) {
		return $this->database->table('user_group')
		    ->where(':user2group.user_id = ?', $user_id);
	}

	public function getUserBigSelection() {
		return $this->database->table('user_big');
	}

	public function getPageSelection() {
		return $this->database->table('page');
	}

	/**
	 * @return Nette\Database\Context
	 */
	public function getDatabase() {
		return $this->database;
	}

}
