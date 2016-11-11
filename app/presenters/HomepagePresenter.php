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
     * @description Počítá aritmetický průměr.
     * @memberOf LocationPresenter 
     * @param array
     * @return float Aritmetický průměr
     */
    public function numericalAverage(array $array) {
        $length = count($array);
        $sum = array_sum($array);
        $number = $sum / $length;
        return $number;
    }
	
	/**
	* @author Anna Moudrá <anna.moudra@gmail.com>
	* @description Předává data pro tabulku posledních pozorování a připravuje data pro zakreslení markerů do mapy.
	* @memberOf HomepagePresenter 
	*/
	
	public function renderDefault()
	{	
        $this->template->comments = $this->database->table('comments')->order('id DESC')->limit(5);


		/* Příprava pro tabulku */	
	    $this->template->tabobservation = $this->database->table('observations')
						->order('id DESC')->limit(10);

	   $this->template->locationcount = $this->database->table('location')->count('*');
	   $this->template->sqmcount = $this->database->table('sqm')->count('*');
	   $this->template->obscount = $this->database->table('observations')->count('*');
       $this->template->photoscount = $this->database->table('photos')->count('*'); 

					
        $this->template->sqm = $this->database->table('sqm');

        $location = $this->database->table('location')->group('location.id')->having('COUNT(:observation.id)>0');

        foreach ($location as $location) {
	    
	    $a=$location->latitude;
	    $b=$location->longitude;
	    $latitude[] = ($location->latitudehemisfera=="N")?($a):(-$a);
	    $longitude[] = ($location->longitudehemisfera=="E")?($b):(-$b);
            $name[] = $location->name;
            $altitude[] = $location->altitude;
            $id[] = $location->id;
            $info[] = $location->info;
            $locationId = $location->id;

            $observation = $this->database->table('observations')
                    ->where('location_id', $locationId)->order('date DESC');
            foreach ($observation as $observations) {
                $sqms[] = $observations->sqmavg;
            }
            if (count($sqms) != 0) {
            $sqmavg = $this->numericalAverage($sqms);
            }

            $sqmloc[] = array('hodnota' => $sqmavg);
            $sqms=array();
        }

        $this->template->location = $location;
        $this->template->latitude = $latitude;
        $this->template->longitude = $longitude;
        $this->template->name = $name;
        $this->template->altitude = $altitude;
        $this->template->id = $id;
        $this->template->info = $info;
        $this->template->sqmloc = $sqmloc;
            
            
    }
}
