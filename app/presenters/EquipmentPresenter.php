<?php

namespace App\Presenters;

use Nette,
    App\Model;
use Nette\Database\Table\Selection;
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
class EquipmentPresenter extends BasePresenter {

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
     * @description Vytváří tabulku s pozorováními na dané lokalitě.
     * @memberOf PersonalPresenter 
     * @param string Name
     * @return grid
     */
    protected function createComponentEquipObsGrid($name) {
        $equipmentId = $this->getHttpRequest()->getUrl()->getQueryParameter('equipmentId');
        $selection = $this->database->table('observations')->where('equipment.id', $equipmentId);
        $selection->select('observations.id, date, observer, sqmavg,' . 'location.name');
        
        $source = new NetteDbDataSource($selection);
        $grid = new Grid($this, $name);
        $primarykey = 'id';
        $grid->setPrimaryKey($primarykey);
        $grid->setLocale('cs');
        $grid->setDataSource($source);
        $grid->addDate('date', 'Datum')
                ->setFormat('d.m.Y - H:i')
                ->setOrdering(TRUE);
        $grid->addText('name', 'Lokalita');
        $grid->addNumber('sqmavg', 'Průměrné sqm')->setDecimals(2);
        $grid->addText('observer', 'Pozorovatel');
        $grid->addText('name', 'Lokalita');
        $action = $grid->addActions('');
        $action->addButton()
                ->setType('btn-primary')
                ->setText('detail pozorování')
                ->setTitle('detail')
                ->setAttribute('href', new Link('Observation:show', array(
                    'observationId' => '{' . $primarykey . '}'
        )));
        $grid->enablePager(20);
        $grid->enableExport($this->context->parameters['wwwDir'] . '/../temp/cache');

        return $grid;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Připravuje data pro equipment.latte.
     * @memberOf LocationPresenter 
     */
    public function renderDefault($equipmentId) {
        $this->template->observations = $this->database->table('observations')
                ->where('equipment_id', $equipmentId) // vytáhne pozorování daným přístrojem z tabulky observations
                ->order('created_at DESC');
        $equipment = $this->database->table('equipment')
                ->where('id', $equipmentId);
        $this->template->locations = $this->database->table('location')
                ->where('user_id', $this->user->id)
                ->order('name');

        $this->template->equipment = $equipment;
        
    }
    
}