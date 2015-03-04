<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Database\Table\Selection;


/**
 * Homepage presenter.
 */
class DatabasePresenter extends BasePresenter
{
   
    private $database;
	
	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
	
	public function renderDefault()
	{                                  
		$this->template->sqm = $this->database->table('sqm');
		
	}
	
	public function renderLocations()
  {                        
    $selection = $this->database->table('location')
               ->group('location.id')         
               ->having('COUNT(:observations.id) > 0')
               ->order(':observations.id DESC');

    $this->template->selection = $selection;
    $this->template->location = $this->database->table('location');
	  $this->template->observation = $this->database->table('observations');
    $this->template->sqm = $this->database->table('sqm'); 
    $this->template->photos = $this->database->table('photos'); 
          
      
  }
}





