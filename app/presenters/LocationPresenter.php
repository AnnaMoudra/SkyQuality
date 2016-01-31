<?php

namespace App\Presenters;

use Nette,
    Nette\Database\Table\Selection;
use Exception;
use Nette\Security\Permission;
use Nette\Forms\Form;
use Nette\Image;
use Mesour\DataGrid,
    Mesour\DataGrid\Grid,
    Mesour\DataGrid\Extensions\Pager,
    Mesour\DataGrid\Components\Link,
    Mesour\DataGrid\Components\Button,
    Mesour\DataGrid\Render,
    Mesour\DataGrid\NetteDbDataSource;

/**
 * @class LocationPresenter.
 * @author Anna Moudrá <anna.moudra@gmail.com>
 * @description Obsluhuje Location templates.
 */
class LocationPresenter extends BasePresenter {

    /** @var Nette\Database\Context */
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
     * @description Připravuje data pro vykreslení náhledu jednotlivých lokalit
     * @memberOf LocationPresenter 
     * @param int Id lokality k nahlédnutí.
     */
    public function renderShow($locationId) {
        $absoluteUrls = TRUE;
        $location = $this->database->table('location')->get($locationId);
        if (!$location) {
            $this->error('Stránka nebyla nalezena');
        }

        $this->template->location = $location;
        $this->template->comments = $location->related('comment')->order('created_at');
        $phosel = $this->database->table('photos')->where('location.id', $locationId);
        $this->template->phosel = $phosel;
        $username = $this->database->table('users')
                        ->where('id', $location->user_id)->fetch('name');
        $this->template->username = $username->name;
        $this->template->sqmcount = $location->related('observations')->where('sqmavg')->count('*');
        $this->template->bortlecount = $location->related('observations')->where('bortle')->count('*');

        if ($phosel->count() > 0) {
            foreach ($phosel as $photos) {
                $popis = $photos->info;
                $img[] = Image::fromFile('http://skyquality.cz/www/images/photos/' . $photos->photo)->resize(600, NULL);
            }
            $this->template->img = $img;
            $this->template->popis = $popis;
        }

        //Počítá průměrné hodnoty SQM a Bortle
        $observation = $this->database->table('observations')
                ->where('location_id', $locationId)
                ->order('date DESC');

        $sqms = [];
        $bortles = [];

        foreach ($observation as $observation) {
            $sqms[] = $observation->sqmavg;
            if ($observation->bortle != 0) {
                $bortles[] = $observation->bortle;
            }
        }

        $sqmavg = $this->numericalAverage($sqms);
        if (count($bortles) !== 0) {
            $bortleavg = $this->numericalAverage($bortles);
        } else {
            $bortleavg = 0;
        }
        $this->template->sqmavg = $sqmavg;
        $this->template->bortle = $bortleavg;


        //Připravuje data pro graf
        $observation = $this->database->table('observations')
                ->where('location_id', $locationId)
                ->order('date DESC');
        $data1 = [];
        $data2 = [];

        foreach ($observation as $observation) {
            $time = strtotime($observation->date . ' GMT') * 1000;
            $equipmentId = $observation->equipment_id;
            $equipment = $this->database->table('equipment')->where('id', $equipmentId)->fetch('type');
            if ($equipment->type === 'SQM') {
                $data1[] = [$time, $observation->sqmavg];
            } else {
                $data2[] = [$time, $observation->sqmavg];
            }
        }

        $this->template->data2 = $data1;
        $this->template->data3 = $data2;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Vytváří tabulku s pozorováními na dan0 lokalitě.
     * @memberOf LocationPresenter 
     * @param string Name
     * @return grid
     */
    protected function createComponentBasicDataGrid($name) {
        $location_id = $this->getHttpRequest()->getUrl()->getQueryParameter('locationId');
        $selection = $this->database->table('observations')->where('location.id', $location_id);
        $selection->select('observations.id, date, observer, sqmavg, ' . 'location.name');

        $source = new NetteDbDataSource($selection);
        $grid = new Grid($this, $name);
        $primarykey = 'id';
        $grid->setPrimaryKey($primarykey);
        $grid->setLocale('cs');
        $grid->setDataSource($source);
        $grid->addDate('date', 'Datum')
                ->setFormat('d.m.y - H:i')
                ->setOrdering(TRUE);
        $grid->addNumber('sqmavg', 'Průměrné sqm')->setDecimals(2);
        $grid->addText('observer', 'Pozorovatel');
        $grid->enablePager(10);
        $grid->enableExport($this->context->parameters['wwwDir'] . '/../temp/cache');
        $actions = $grid->addActions('');
        $actions->addButton()
                ->setType('btn-primary')
                ->setText('detail pozorování')
                ->setTitle('detail')
                ->setAttribute('href', new Link('Observation:show', array(
                    'observationId' => '{' . $primarykey . '}'
        )));
        return $grid;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Vytváří formulář pro komentáře.
     * @memberOf LocationPresenter 
     * @return form CommentForm
     */
    protected function createComponentCommentForm() {
        $form = (new \CommentFormFactory())->create(); //formulář je ve složce app/forms
        $form->onSuccess[] = array($this, 'commentFormSucceeded');
        return $form;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Ukládá data z formuláře do databáze.
     * @memberOf LocationPresenter 
     * @param array Data z formuláře.
     */
    public function commentFormSucceeded($form) {
        $values = $form->getValues();
        $locationId = $this->getParameter('locationId');

        $this->database->table('comments')->insert(array(
            'location_id' => $locationId,
            'name' => $values->name,
            'content' => $values->content,
        ));

        $this->flashMessage('Děkuji za komentář', 'success');
        $this->redirect('this');
    }

}
