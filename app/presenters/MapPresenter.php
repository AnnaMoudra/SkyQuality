<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Database\Table\Selection;



/**
 * Homepage presenter.
 */
class MapPresenter extends BasePresenter
{
   
    private $database;
	
	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
	

	public function renderDefault()
	{                                  
		$this->template->sqm = $this->database->table('sqm');
		$this->template->observation = $this->database->table('observations');
              
		$location = $this->database->table('location');
		
	    foreach($location as $location){
		$latitude[]=$location->latitude;
		$longitude[]=$location->longitude;
		$name[]=$location->name;
		$altitude[]=$location->altitude;
		$id[]=$location->id;
		$info[]=$location->info;
	    }
	    $this->template->location= $location;
	    $this->template->latitude = $latitude;
	    $this->template->longitude = $longitude;
	    $this->template->name = $name;
	    $this->template->altitude = $altitude;
	    $this->template->id = $id;
	    $this->template->info = $info;
	    
    
	}
}





