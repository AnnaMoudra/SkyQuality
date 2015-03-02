<?php
namespace App\Presenters;

use Nette,
        Nette\Database\Table\Selection;
use Exception;
use Nette\Security\Permission;
use Nette\Forms\Form;
use Nette\Forms\Controls\Button;
use Nette\Forms\IControl;
use Nette\Http\FileUpload;
use Nette\Utils\Image;


class ObservationPresenter extends BasePresenter
{
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

   public function renderShow($observationId)
    {
        $observation = $this->database->table('observations')->get($observationId);
        if (!$observation) {
            $this->error('Stránka nebyla nalezena');
        }

        $this->template->observation = $observation;
        $this->template->comments = $observation->related('comment')->order('created_at');
    }
    
    protected function createComponentObservationForm()
    {
         if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }
	

	$form = (new \ObservationFormFactory($this->database))->create();

	$form->onSuccess[] = array($this, 'observationFormSucceeded'); // a přidat událost po odeslání

	return $form;
        
    }
    
    public function observationFormSucceeded($form)
    {
        if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }	
	$values = $form->getValues();
	
	$valuesObservation = $values['observation']; //data pristupna pod $valuesObservation['date']
	$valuesObservation['user_id'] = $this->user->id;
	$valuesSqm = $values['sqm'];//jen container! data pristupna pod $valuesSqm['value1']
	
	if($values['locationid']===1){ //pokud se zadava nova lokalita
	    $valuesLocation= $values['location']; // data z containeru!
	    $valuesLocation['user_id']= $this->user->id;
	    $this->database->table('location')->insert($valuesLocation);
	}
	else{
	    $valuesObservation['location_id'] = $values['locationid'];
	}
	
	if($values['equipmentid']===1){ // pokud se zadava nove zarizeni
	    $valuesEquipment= $values['equipment'];
	    $this->database->table('equipment')->insert($valuesEquipment);
	}
	else{
	    $valuesObservation['equipment_id'] = $values['equipmentid'];
	}
//
	$observation = $this->database->table('observations')
		->insert($valuesObservation);
	
	foreach ($valuesSqm as $sqm) {
	    $sqm['id_observation'] = $observation->id;
	    $this->database->table('sqm')->insert($sqm);  
	}
	
	$valuesPhotos = $values['photos'];
	if ($valuesPhotos['addphotos']==TRUE){
	    foreach ($valuesPhotos as $key => $arraypi) {
		if($key!=='addphotos'){
		$photoarray = array();
		$photo = $arraypi['photo'];
		$photoarray['info']=$arraypi['info'];
		$photoarray['observation_id'] = $observation->id;
		if ($photo->isImage()){
		$filename = $photo->getSanitizedName();
		$photoarray['photo']= $photo->getContents(); //snad osetri kolize
		$this->database->table('photos')->insert($photoarray); 
		//nahraje do db
		$path = \WWW_DIR.'/www/www/images/photos/'.$filename;
		$photo->move($path);
//budeme ukladat name do db a uploadovat na adresu www/images/photos

		}
		}
	    }
	}
	
        $this->flashMessage("Příspěvek byl úspěšně vložen.", 'success');
        $this->redirect('show', $observation->id);
    }
    
    public function actionCreate()
    {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
    
    public function actionDelete($observationId)
    {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }

        $observation = $this->database->table('observations')
		->where('user_id', $this->user->id)  // id_user v observations odpovida id v userovi
                ->get($observationId);
            
            if (!$observation) { 
                $this->flashMessage('Nemáte oprávnění ke smazání toho příspěvku.');
                $this->redirect('Observation:show?observationId='.$observationId);
            }
                
            $this->database->table('observations')->where('id', $observationId)->delete();
            
            $this->flashMessage("Měření bylo smazáno.", 'success');
            $this->redirect('Personal:');
        
    }

        public function actionEdit($observationId)
    {
        if (!$this->user->isLoggedIn()) 
        {
            $this->redirect('Sign:in');
        } 
        else{
           
            $observation = $this->database->table('observations')
                    ->where('user_id', $this->user->id)  // id_user v observations odpovida id v userovi
                    ->get($observationId);
	    
	    $sqm =  $this->database->table('sqm')
                    ->where('observation_id', $observationId)  // id_user v observations odpovida id v userovi
                    ->get($observationId);
            
            if (!$observation) { 
                $this->flashMessage('Nemáte oprávnění k editaci toho příspěvku.');
                $this->redirect('Observation:show?observationId='.$observationId);// existuje takove mereni?
            }
            if ($this->user->id == $observation->user_id) // druha kontrola
            {
	    $this['observationForm']->setDefaults($observation->toArray())->setDefaults($sqm->toArray());
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
        $observationId = $this->getParameter('observationId');

        $this->database->table('comments')->insert(array(
            'observation_id' => $observationId,
            'name' => $values->name,
            'email' => $values->email,
            'content' => $values->content,
        ));

        $this->flashMessage('Děkuji za komentář', 'success');
        $this->redirect('this');
    }
}


