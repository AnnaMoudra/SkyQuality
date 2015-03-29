<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Database\Table\Selection;
use Mesour\DataGrid,
    Mesour\DataGrid\Grid,
    Mesour\DataGrid\Extensions\Pager;
use Mesour\DataGrid\NetteDbDataSource,
    Mesour\DataGrid\Components\Button,
    Mesour\DataGrid\Components\Link;


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
	    $selection->select('observations.id, date, observer, sqmavg, ' .
	    'location.name');

	    
	    $source = new NetteDbDataSource($selection);
	    $grid = new Grid($this, $name);
	    $primarykey= 'id';
	    $grid->setPrimaryKey($primarykey); // primary key is now used always

	    $grid->setDataSource($source);
	    $grid->addDate('date','Datum')
		    ->setFormat('d.m.y - H:i')
                    ->setOrdering(TRUE);
	    $grid->addText('observer','Pozorovatel');
	    $grid->addText('name','Lokalita');
	    $grid->addNumber('sqmavg','Průměrné sqm')->setDecimals(2);
	    $action = $grid->addActions('');
	    $action->addButton()
		    ->setType('btn-primary')
		    ->setText('detail pozorování')
		    ->setTitle('detail')
		    ->setAttribute('href', new Link('Observation:show',array(
			'observationId'=>'{'.$primarykey.'}'
		    )));
	    
	    $grid->enablePager(20);
	    $grid->enableExport($this->context->parameters['wwwDir'].'/../temp/cache');
		  


	    return $grid;
	}
	
	protected function createComponentLocationsDataGrid($name) {
	    $selection= $this->database->table('location');
	    $selection->select('id, name, latitude, altitude, longitude, accessiblestand');

	    
	    $source = new NetteDbDataSource($selection);
	    $grid = new Grid($this, $name);
	    $primarykey= 'id';
	    $grid->setPrimaryKey($primarykey); // primary key is now used always

	    $grid->setDataSource($source);
	    $grid->addText('name','Název');
	    $grid->addText('altitude','Nadmořská výška [m n.m.]');
	    $grid->addText('accessiblestand','Volně přístupné')
		   ->setCallback(function($row){
		       if($row['accessiblestand']===0){return 'ne';}else{return 'ano';}
		   });
	    $action = $grid->addActions('');
	    $action->addButton()
		    ->setType('btn-primary')
		    ->setText('detail lokality')
		    ->setTitle('detail')
		    ->setAttribute('href', new Link('Location:show',array(
			'locationId'=>'{'.$primarykey.'}'
		    )));
	    
	    $grid->enablePager(20);
	    $grid->enableExport($this->context->parameters['wwwDir'].'/../temp/cache');
		  


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





