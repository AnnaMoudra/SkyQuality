<?php

namespace App\Presenters;

use Nette,
	App\Model;

/**
 * @class HomepagePresenter.
 * @author Anna Moudrá <anna.moudra@gmail.com>
 * @description Obsluhuje Homepage/default.latte.
 */

class HomepagePresenter extends BasePresenter
{
   
    private $database;
    
	/**
	* @description Vytváří připojení k databázi.
	* @param Spojení vytvořené v config.neon
	*/
    
	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
                
	}
	
	/**
	* @author Anna Moudrá <anna.moudra@gmail.com>
	* @description Předává data pro tabulku posledních pozorování.
	* @memberOf HomepagePresenter 
	*/
	
	public function renderDefault()
	{
	    $this->template->observation = $this->database->table('observations')
						->order('id DESC')->limit(10);
            
            
            
        }
}
