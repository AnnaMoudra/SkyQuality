<?php

namespace App\Presenters;

use Nette,
    App\Model;
use Nette\Database\Table\Selection,
    Nette\Utils\Html;
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
        $selection->select('observations.id, date, equipment_id,  observer, bortle, observations.info, sqmavg, transparency, ' . 'location.name');
        
        $source = new NetteDbDataSource($selection);
        $grid = new Grid($this, $name);
        $primarykey = 'id';
        $grid->setPrimaryKey($primarykey);
        $grid->setLocale('cs');
        $grid->setDataSource($source);
        $grid->setDefaultOrder('date', 'DESC');
        $grid->addDate('date', 'Datum a čas (UTC)')
                ->setFormat('d. m. Y —&\nb\sp;H:i')
                ->setOrdering(TRUE);
        $grid->addText('name', 'Lokalita');
        $grid->addNumber('sqmavg', 'Jas')->setDecimals(2)->setAttribute('class', 'data-grid__sqm');
        $grid->addText('observer', 'Pozorovatel');
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
        $action->addButton()
                ->setType('btn-primary')
                ->setText('detail')
                ->setTitle('detail')
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