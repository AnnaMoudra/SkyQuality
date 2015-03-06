<?php


namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class ProjectPresenter extends BasePresenter
{
   
    private $database;
	
	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
	
	public function renderDefault()
	{
		//$this->template->info = $this->database->table('info')
			//->order('order DESC');
	}


}

