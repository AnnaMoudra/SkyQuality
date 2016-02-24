<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings,
	Nette\Security\Passwords;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
	const
		TABLE_NAME = 'users',
		COLUMN_ID = 'id',
		COLUMN_NAME = 'username',
		COLUMN_PASSWORD_HASH = 'password',
		COLUMN_EMAIL = 'email',
		COLUMN_NAME2 = 'name',
		COLUMN_LINKHASH = 'linkhash',
		COLUMN_ACTIVE = 'active',
		COLUMN_ROLE = 'role',
		COLUMN_NEWPASSWORD = 'newpass';


	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;

		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_NAME, $username)->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);

		} elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);

		}elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update(array(
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			));
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
	}


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function add($username, $password, $email, $name, $linkhash)
	{
		$this->database->table(self::TABLE_NAME)->insert(array(
			self::COLUMN_NAME => $username,
			self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			self::COLUMN_EMAIL => $email,
			self::COLUMN_NAME2 => $name,
			self::COLUMN_LINKHASH => $linkhash
		));
	}
	/**
	 * @author Anna Moudrá <anna.moudra@gmail.com>
	 * @description Aktivuje uživatelský účet.
	 * @param string
	 */
	public function validate($linkhash){
	    
	    $row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_LINKHASH, $linkhash)->fetch();
	    
	    if(!$row)
	    {
		throw new Nette\Security\AuthenticationException('Such user was not found in the database', self::IDENTITY_NOT_FOUND);
	    }

	    $row->update(array(self::COLUMN_ACTIVE => true));   
	}
	/**
	 * @author Anna Moudrá <anna.moudra@gmail.com>
	 * @description Změní heslo. (V současnosti se tato fce nevyužívá a změna hesla probíhá přes Presenter)
	 * @params string,string 
	 */
	public function changepass($newpass,$password) {
	    
	   $row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_NEWPASSWORD, $newpass)->fetch();
	   
	   if(!$row)
	   {
		throw new Nette\Security\AuthenticationException('Such user was not found in the database', self::IDENTITY_NOT_FOUND);
	   }
	   
	   $row->update(array(self::COLUMN_PASSWORD_HASH => Passwords::hash($password)));
	   $row->update(array(self::COLUMN_NEWPASSWORD => NULL));
	    
	}
	/**
	 * @author Anna Moudrá <anna.moudra@gmail.com>
	 * @description Ověřuje, zda je účet aktivní.
	 * @params string Uživatel 
	 */
	public function isActive($username){
	    $row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_NAME, $username)->fetch();
	    if(!$row)
	    {
		throw new Nette\Security\AuthenticationException('Such user was not found in the database', self::IDENTITY_NOT_FOUND);
	    }
	    if($row[self::COLUMN_ACTIVE] == TRUE){
		return TRUE;
	    }
	    else{
		return FALSE;
	    }
	
	}

}
