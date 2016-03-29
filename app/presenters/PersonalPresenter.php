<?php

namespace App\Presenters;

use Nette,
    App\Model;
use Nette\Database\Table\Selection;
use Nette\Utils\Html;
use Mesour\DataGrid,
    Mesour\DataGrid\Grid,
    Mesour\DataGrid\Extensions\Pager,
    Mesour\DataGrid\Components\Link,
    Mesour\DataGrid\Components\Button,
    Mesour\DataGrid\Render,
    Mesour\DataGrid\NetteDbDataSource;

/**
 * @class PersonalPresenter 
 * @author Anna Moudrá <anna.moudra@gmail.com>
 * @description Obsluhuje Personal template.* 
 */
class PersonalPresenter extends BasePresenter {

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
     * @description Vytváří tabulku s pozorováními na dané lokalitě.
     * @memberOf PersonalPresenter 
     * @param string Name
     * @return grid
     */
    protected function createComponentMyObsGrid($name) {
        $selection = $this->database->table('observations')->where('user.id', $this->user->id);
        $selection->select('observations.id, date, observations.info, bortle, transparency, equipment_id, observer, sqmavg,' . 'location.name');

        $source = new NetteDbDataSource($selection);
        $grid = new Grid($this, $name);
        $primarykey = 'id';
        $grid->setPrimaryKey($primarykey);
        $grid->setLocale('cs');
        $grid->setDataSource($source);
        $grid->addDate('date', 'Datum')
                ->setFormat('d.m.Y —&\nb\sp;H:i')
                ->setOrdering(TRUE);
        $grid->addText('name', 'Lokalita');
        $grid->addNumber('sqmavg', 'Jas')->setDecimals(2)->setAttribute('class', 'data-grid__sqm');
        $grid->addText('flags', '')
             ->setAttribute('class', 'data-grid__flags')
             ->setOrdering(FALSE)
             ->setCallback(function($row) {
                if ($this->database->table('equipment')->where('id',$row['equipment_id'])->fetch()->type == 'SQM') {
                        return HTML::el('span')->class("flag flag--sqmw");
                    }
                else {
                    return HTML::el('span')->class("flag flag--sqml");
                }});
        $grid->addText('flags', '')
             ->setAttribute('class', 'data-grid__flags')
             ->setOrdering(FALSE)
             ->setCallback(function($row) {
                if ($row['bortle']) {
                        return HTML::el('span')->class("flag flag--bortle");
                    }});
        $grid->addText('flags', '')
             ->setAttribute('class', 'data-grid__flags') 
             ->setOrdering(FALSE)
             ->setCallback(function($row) {
                if ($row['transparency']) {
                        return HTML::el('span')->class("flag flag--transparency");
                    }});
        $grid->addText('flags', '')
             ->setAttribute('class', 'data-grid__flags') 
             ->setOrdering(FALSE)
             ->setCallback(function($row) {
                if ($row['info']) {
                        return HTML::el('span')->class("flag flag--info");
                    }});
        $grid->addText('flags', '')
             ->setOrdering(FALSE)
             ->setAttribute('class', 'data-grid__flags') 
             ->setCallback(function($row) {
                if ($this->database->table('photos')->where('observation_id',$row['id'])->count('*') > 0) {
                        return HTML::el('span')->class("flag flag--photo");
                    }});
        $action = $grid->addActions('');
        $grid->setDefaultOrder('date', 'DESC');
        $action->addButton()
                ->setType('btn-primary')
                ->setText('detail')
                ->setTitle('detail')                
                ->setAttribute('class', 'data-grid__detail')
                ->setAttribute('href', new Link('Observation:show', array(
                    'observationId' => '{' . $primarykey . '}'
        )));
        if ($selection->count('*') > 20) {
            $grid->enablePager(20);
        }
        $grid->enableExport($this->context->parameters['wwwDir'] . '/../temp/cache');

        return $grid;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Připravuje data pro default.latte.
     * @memberOf LocationPresenter 
     */
    public function renderDefault() {
        $this->template->observations = $this->database->table('observations')
                ->where('user_id', $this->user->id) // vytáhne uživatelova pozorování z tabulky observations
                ->order('created_at DESC');
        $personal = $this->database->table('users')
                ->where('id', $this->user->id);
        $userid = $this->database->table('users')
                        ->where('id', $this->user->id)->fetch('id');
        $this->template->locations = $this->database->table('location')
                ->where('user_id', $this->user->id)
                ->order('name');

        $this->template->personal = $personal;
        $this->template->userid = $userid;
        //Počítá průměrné hodnoty SQM a Bortle
        $observation = $this->database->table('observations')
                ->where('user_id', $userid->id)
                ->order('date DESC');

        $sqms = [];
        $bortles = [];

        foreach ($observation as $observation) {
            $sqms[] = $observation->sqmavg;
            if ($observation->bortle != 0) {
                $bortles[] = $observation->bortle;
            }
        }
        if (count($sqms) != 0) {
            $sqmavg = $this->numericalAverage($sqms);
        } else {
            $sqmavg = 0;
        }
        if (count($bortles) !== 0) {
            $bortleavg = $this->numericalAverage($bortles);
        } else {
            $bortleavg = 0;
        }
        $this->template->sqmavg = $sqmavg;
        $this->template->bortle = $bortleavg;
    }

}
