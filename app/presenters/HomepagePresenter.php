<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{
   
    private $database;
	
	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
	
	public function renderDefault()
	{
		$this->template->observation = $this->database->table('observations')
                        ->order('id DESC')->limit(10);
              
              }


}
