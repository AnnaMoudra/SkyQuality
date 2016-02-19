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
class MapPresenter extends BasePresenter {

    private $database;

    /**
     * @description Vytváří připojení k databázi.
     * @param Spojení vytvořené v config.neon
     */
    public function __construct(Nette\Database\Context $database) {
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
     * @description Připravuje data pro zakreslení markerů do mapy.
     * @memberOf MapPresenter 
     */
    public function renderDefault() {
        $this->template->sqm = $this->database->table('sqm');

        $location = $this->database->table('location');

        foreach ($location as $location) {
            $latitude[] = $location->latitude;
            $longitude[] = $location->longitude;
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
