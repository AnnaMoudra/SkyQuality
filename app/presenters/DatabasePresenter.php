<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form;
use Nette\Database\Table\Selection;
use Nette\Utils\Arrays,
    Nette\Utils\Html;
use Mesour\DataGrid,
    Mesour\DataGrid\Grid,
    Mesour\DataGrid\Extensions\Pager;
use Mesour\DataGrid\ArrayDataSource,
    Mesour\DataGrid\NetteDbDataSource,
    Mesour\DataGrid\Components\Button,
    Mesour\DataGrid\Components\Link;


/**
 * @class DatabasePresenter.
 * @author Anna Moudrá <anna.moudra@gmail.com>
 * @description Obsluhuje Database templates.
 */
class DatabasePresenter extends BasePresenter {

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
     * @description Vytváří tabulku s pozorováními.
     * @memberOf DatabasePresenter 
     * @param string Name
     * @return grid
     */
    protected function createComponentBasicDataGrid($name) {
        $selection = $this->database->table('observations')->order('observations.id DESC');
        $selection->select('observations.id, equipment_id, date, observer, bortle, observations.info, sqmavg, transparency, ' .
                'location.name');
        $photos = $this->database->table('photos');

        $source = new NetteDbDataSource($selection);
        $grid = new Grid($this, $name);
        $primarykey = 'id';
        $grid->setPrimaryKey($primarykey);
        $grid->setLocale('cs');
        $grid->setDataSource($source);        
        $grid->enableFilter($this['obsFilter']);
        $filter_values = $grid->getFilterValues();

        if (empty($filter_values) === FALSE) {
            if (isset($filter_values['date_time']) && !empty($filter_values['date_time'])) {
                $source->where('date LIKE ?', '%' . $filter_values['date_time'] . '%');
            }
            if (isset($filter_values['location']) && !empty($filter_values['location'])) {
                $source->where('location.name LIKE ?', '%' . $filter_values['location'] . '%');
            }
            if (isset($filter_values['observer_name']) && !empty($filter_values['observer_name'])) {
                $source->where('observer LIKE ?', '%' . $filter_values['observer_name'] . '%');
            }
            if (isset($filter_values['sqmavg_gt']) && !empty($filter_values['sqmavg_gt'])) {
                $source->where('sqmavg >= ?', $filter_values['sqmavg_gt']);
            }
            if (isset($filter_values['sqmavg_lt']) && !empty($filter_values['sqmavg_lt'])) {
                $source->where('sqmavg <= ?', $filter_values['sqmavg_lt']);
            }
        }

        $grid->setDefaultOrder('id', 'DESC');
        $grid->addDate('date', 'Datum a čas (UTC)')
             ->setFormat('d. m. Y —&\nb\sp;H:i');
        $grid->addText('name', 'Lokalita');
        $grid->addNumber('sqmavg', 'Jas')
             ->setDecimals(2)
             ->setAttribute('class', 'data-grid__sqm');
        $grid->addText('observer', 'Pozorovatel');
        $grid->addText('flags', '')
             ->setAttribute('class', 'data-grid__flags')
             ->setOrdering(FALSE)
             ->setCallback(function($row) {
                if ($this->database->table('equipment')->where('id',$row['equipment_id'])->fetch()->type == 'SQM') {
                        return HTML::el('span')->class("flag flag--sqmw")->title("Obsahuje SQM-W měření");
                    }
                else {
                    return HTML::el('span')->class("flag flag--sqml")->title("Obsahuje SQM-L měření");
                }});
        $grid->addText('flags', '')
             ->setAttribute('class', 'data-grid__flags')
             ->setOrdering(FALSE)
             ->setCallback(function($row) {
                if ($row['bortle']) {
                        return HTML::el('span')->class("flag flag--bortle")->title("Obsahuje odhad Bortle");
                    }});
        $grid->addText('flags', '')
             ->setAttribute('class', 'data-grid__flags') 
             ->setOrdering(FALSE)
             ->setCallback(function($row) {
                if ($row['transparency']) {
                        return HTML::el('span')->class("flag flag--transparency")->title("Obsahuje odhad průzračnosti");
                    }});
        $grid->addText('flags', '')
             ->setAttribute('class', 'data-grid__flags') 
             ->setOrdering(FALSE)
             ->setCallback(function($row) {
                if ($row['info']) {
                        return HTML::el('span')->class("flag flag--info")->title("Obsahuje podrobnější popis");
                    }});
        $grid->addText('flags', '')
             ->setOrdering(FALSE)
             ->setAttribute('class', 'data-grid__flags') 
             ->setCallback(function($row) {
                if ($this->database->table('photos')->where('observation_id',$row['id'])->count('*') > 0) {
                        return HTML::el('span')->class("flag flag--photo")->title("Obsahuje fotografie");
                    }});
        $action = $grid->addActions('');
        $action->addButton()
               ->setType('btn-primary')
               ->setText('detail')
               ->setTitle('detail')
               ->setAttribute('href', new Link('Observation:show', array(
                    'observationId' => '{' . $primarykey . '}'
        ))); 

        $grid->enablePager(20);
        $grid->enableExport($this->context->parameters['wwwDir'] . '/../temp/cache');
        return $grid;
    }

        /**
     * @author Milada Moudrá <milada.moudra@gmail.com>
     * @description Vytváří filtrovaci formular.
     * @memberOf DatabasePresenter 
     * @param none
     * @return form
     */

    protected function createComponentObsFilter() {
        $form = new Form;
        $form->addText('date_time', 'Datum a čas');
        $form->addText('location', 'Lokalita');
        $form->addText('sqmavg_lt', 'Jas <');
        $form->addText('sqmavg_gt', 'Jas > ');
        $form->addText('observer_name', 'Pozorovatel');
     
        $form->addSubmit('filter', 'Filtrovat'); // required button with name filter
        $form->addSubmit('reset', 'Reset') // required button with name reset
            ->setAttribute('class', 'btn btn-danger');
     
        return $form;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Vytváří tabulku s lokalitami.
     * @memberOf DatabasePresenter 
     * @param string Name
     * @return grid
     */
    protected function createComponentLocationsDataGrid($name) {
        $selection = $this->database->table('location');
        $sel_length = $this->database->table('location')->count('*');
        $selection->select('id, name, latitude, altitude, longitude, accessiblestand');

        for ($i = 1; $i <= $sel_length+21; $i++) {
            if ($i < 240 || $i > 260) {         // Vyhazuje 21 nepridelenych ID (viz sel_lenght+21)
                $observation = $this->database->table('observations')
                                ->where('location_id', $selection[$i]->id)->order('date DESC');


                if ($observation->count('*') > 0) {
                    $sqms = [];
                    $pole[$i] = $selection[$i]->toArray();
                    $pole[$i]['obscount'] = $observation->count('*');
                    foreach ($observation as $observation) {
                        $sqms[] = $observation->sqmavg;
                    }
                    if (count($sqms) != 0) {
                        $sqmavg = $this->numericalAverage($sqms);
                        $pole[$i]['sqmavg'] = $sqmavg;
                    } else {
                        $pole[$i]['sqmavg'] = NULL;
                    }
                }
            }
        }



        $source = new ArrayDataSource($pole);
        $grid = new Grid($this, $name);
        $primarykey = 'id';
        $grid->setPrimaryKey($primarykey);
        $grid->setLocale('cs');
        $grid->setDataSource($source);
        
        $grid->setDefaultOrder('name', 'ASC');
        $grid->addText('name', 'Název')
             ->setAttribute('class', 'data-grid__name');
        $grid->addNumber('sqmavg', 'Jas')
             ->setDecimals(2)
             ->setAttribute('class', 'data-grid__sqm');
        $grid->addNumber('obscount', 'Měření')
             ->setAttribute('class', 'data-grid__sqm');
        $grid->addText('accessiblestand', 'Volně přístupné')
                ->setAttribute('class', 'data-grid__access')
                ->setCallback(function($row) {
                    if ($row['accessiblestand'] === 0) {
                        return 'ne';
                    } else {
                        return 'ano';
                    }
                });
        $action = $grid->addActions('');
        $action->addButton()
                ->setType('btn-primary')
                ->setText('detail')
                ->setTitle('detail')
                ->setAttribute('href', new Link('Location:show', array(
                    'locationId' => '{' . $primarykey . '}'
        )));

        $grid->enablePager(20);
        $grid->enableExport($this->context->parameters['wwwDir'] . '/../temp/cache');
        return $grid;
    }


    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Předává data z db do template Database/default.latte
     * @memberOf DatabasePresenter 
     */
    public function renderDefault() {
        $this->template->sqm = $this->database->table('sqm');
        $this->template->observation = $this->database->table('observations');
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Předává data z db do template Database/locations.latte
     * @memberOf DatabasePresenter 
     */
    public function renderLocations() {
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
