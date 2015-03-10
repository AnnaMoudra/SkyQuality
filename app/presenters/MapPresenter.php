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
	    }
	    $this->template->location= $location;
	    $this->template->latitude = $latitude;
	    $this->template->longitude = $longitude;
            
	    	
//*foreach($observation as $observation){
//time = strtotime($observation->date . ' GMT')*1000;
//f($observation->sqmavg>=21.30){
//data1[]=[$time,$observation->sqmavg];}
//lse{
//   $data2[]=[$time,$observation->sqmavg];
//
//
//
//this->template->data1 = $data1;
//this->template->data2 = $data2;*}
//
//
    
	}
}





