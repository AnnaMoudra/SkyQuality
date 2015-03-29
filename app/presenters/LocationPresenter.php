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



class LocationPresenter extends BasePresenter
{
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }
    
    public function numericalAverage(array $array){
	$length = count($array);
	$sum = array_sum($array);
	$number = $sum/$length;
	return $number;
    }
    

   public function renderShow($locationId)
    {
        $absoluteUrls = TRUE;
        $location = $this->database->table('location')->get($locationId);
        if (!$location) {
            $this->error('Stránka nebyla nalezena');
        }
        
        $location_id = $location->id;
      
        $this->template->location = $location;
        $this->template->comments = $location->related('comment')->order('created_at');
        $obssel = $this->database->table('observations')->where('location.id', $location_id)->select('observations.id, observations.user_id, observer, sqmavg'); 
	$this->template->observationgraf = $this->database->table('observations');
        $this->template->obssel = $obssel;
        $this->template->users = $this->database->table('users');
        $phosel = $this->database->table('photos')->where('location.id', $locationId); 
    
        $this->template->phosel = $phosel;
                
        $photos = $this->database->table('photos');
        foreach ($photos as $photos) {
        $img[] = Image::fromFile('http://skyquality.cz/www/images/photos/'.$photos->photo)->resize(250, NULL);}
        
        $this->template->img = $img;
        
        
        
        $this->template->observations = $this->database->table('observations');
	
	//VYPOCTY VZORECKU
	$observation = $this->database->table('observations')
                ->where('location_id',$locationId)
                ->order('date DESC');
	
	$sqms = [];
	$bortles = [];
	
	foreach ($observation as $observation) {
	    $sqms[] = $observation->sqmavg;
	    $bortles[] = $observation->bortle;
	}
	$sqmavg = $this->numericalAverage($sqms);
	$bortleavg = $this->numericalAverage($bortles);
	$this->template->sqmavg = $sqmavg;
	$this->template->bortle = $bortleavg;
        

        // Pro Grafy
	$observation = $this->database->table('observations')
                ->where('location_id',$locationId)
                ->order('date DESC');
	$data=[];
        $data1=[];
        $data2=[];
	foreach($observation as $observation){
	$time = strtotime($observation->date . ' GMT')*1000;
	$data[]=[$time,$observation->sqmavg];
	}
	$this->template->data = $data;
        
        
     //   DRUHY GRAF
          
        $observation = $this->database->table('observations')
                ->where('location_id',$locationId)
                ->order('date DESC');
        foreach($observation as $observation){
        
        $time = strtotime($observation->date . ' GMT')*1000;    
        $equipmentId = $observation->equipment_id;
        $equipment = $this->database->table('equipment')->where('id', $equipmentId)->fetch('type');
        if ($equipment->type === 'SQM' ) {
            
        $data1[]=[$time,$observation->sqmavg]; } 
        else{
        $data2[]=[$time,$observation->sqmavg];
    }
       
        }

       $this->template->data2 = $data1;
       $this->template->data3 = $data2;


    }
    
    protected function createComponentBasicDataGrid($name) {
            $location_id = $this->getHttpRequest()->getUrl()->getQueryParameter('locationId');
	    $selection= $this->database->table('observations')->where('location.id',$location_id);
	    $selection->select('observations.id, date, observer, sqmavg, ' . 'location.name');

	    $source = new NetteDbDataSource($selection);
	    $grid = new Grid($this, $name);
	    $primarykey = 'id';
	    $grid->setPrimaryKey($primarykey);

	    $grid->setDataSource($source);
	    $grid->addDate('date','Datum')
		    ->setFormat('d.m.y - H:i')
                    ->setOrdering(TRUE);
	    $grid->addNumber('sqmavg','Průměrné sqm')->setDecimals(2);
	    $grid->addText('observer','Pozorovatel');
	    $grid->enablePager(10);
	    $grid->enableExport($this->context->parameters['wwwDir'].'/../temp/cache');
	    $actions = $grid->addActions('');
	    $actions->addButton()
		    ->setType('btn-primary')
		    ->setText('detail pozorování')
		    ->setTitle('detail')
		    ->setAttribute('href', new Link('Observation:show',array(
			'observationId'=>'{'.$primarykey.'}'
		    )));
		
	    return $grid;
	}

  
    
        public function actionEdit($locationId)
    {
        if (!$this->user->isLoggedIn()) 
        {
            $this->redirect('Sign:in');
        } else{
           
            $location = $this->database->table('location')
                    ->where('user_id', $this->user->id)  // id_user v observations odpovida id v userovi
                    ->get($locationId);
            
            if (!$location) { 
                $this->flashMessage('Nemáte oprávnění k editaci toho příspěvku.');
                $this->redirect('Location:show?locationId='.$locationId);// existuje takova lokalita
            }
            if ($this->user->id == $location->user_id) // druha kontrola
            {
                $this['locationForm']->setDefaults($location->toArray()); //neexistuje takovy Form!!!
            }
        }
    }
    
      protected function createComponentCommentForm()
    {
        $form = (new \CommentFormFactory())->create();

	$form->onSuccess[] = array($this, 'commentFormSucceeded'); // a přidat událost po odeslání

	return $form;
    }
    
    public function commentFormSucceeded($form)
    {
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
