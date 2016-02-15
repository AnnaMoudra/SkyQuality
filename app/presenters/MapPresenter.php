<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Database\Table\Selection;

/**
 * @class MapPresenter.
 * @author Anna Moudrá <anna.moudra@gmail.com>
 * @description Obsluhuje Map template.
 */

class MapPresenter extends BasePresenter
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
	* @description Připravuje data pro zakreslení markerů do mapy.
	* @memberOf MapPresenter 
	*/
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





