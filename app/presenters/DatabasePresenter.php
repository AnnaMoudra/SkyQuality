<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Database\Table\Selection;
use Mesour\DataGrid\Grid;
use Mesour\DataGrid\NetteDbDataSource;


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
	
	protected function createComponentBasicDataGrid($name) {
	    $selection= $this->database->table('observations');
	    $source = new NetteDbDataSource($selection);

	    $grid = new Grid($this, $name);

	    $grid->setPrimaryKey('id'); // primary key is now used always

	    $grid->setDataSource($source);
	    $grid->addDate('date','Datum')
		    ->setFormat('j.n.Y H:i:s')
                    ->setOrdering(TRUE);
	    $grid->addText('observer','Pozorovatel');
	    $grid->addNumber('sqmavg','Prumerne sqm')->setDecimals(2);
	    $grid->addText('weather','Pocasi');
	    $grid->enablePager();
            $grid->enableMultiOrdering();
		  


	    return $grid;
	}
	
        
	public function renderDefault()
	{                                  
		$this->template->sqm = $this->database->table('sqm');
		$this->template->observation = $this->database->table('observations');
              
    
	}
	
	public function renderLocations()   {                        
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





