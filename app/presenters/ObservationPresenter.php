<?php
namespace App\Presenters;

use Nette,
        Nette\Database\Table\Selection;
use Exception;
use Nette\Security\Permission;
use Nette\Forms\Form;



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
    
    protected function createComponentCommentForm()
    {
        $form = new Nette\Application\UI\Form;

        $form->addText('name', 'Jméno:')
            ->setRequired();
        $form->addText('email', 'Email:');
        $form->addTextArea('content', 'Komentář:')
            ->setRequired();
        $form->addSubmit('send', 'Publikovat komentář');
   
        $form->onSuccess[] = $this->commentFormSucceeded;
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
    
    protected function createComponentObservationForm()
    {
         if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }
	if ($this->database->table('users')->where('user_id', $this->user->id)->where('active',1)!==true){
	    $this->flashMessage('Nejprve aktivujte svůj účet.');
	}
        $locations= $this->database->table('locations')->fetchPairs('id','name');
	$equipment = $this->database->table('equipment')->fetchPairs('id','name');
        $form = new Nette\Application\UI\Form;
	$locationContainer = $form->addContainer('location');
	$observationContainer = $form->addContainer('observation');
	$equipmentContainer = $form->addContainer('equipment');
	$sqmContainer = $form->addContainer('sqm');
	$photosContainer = $form->addContainer('photos');
        
	$observationContainer->addText('date', 'Datum měření:')
            ->setRequired();
	$observationContainer->addText('time','cas mereni:')
		->setRequired();

	$locationContainer->addSelect('location','Lokalita:',$locations)
		->setPrompt('Vyberte misto mereni')
		->setRequired();

	$observationContainer->addText('observer', 'Pozorovatel')
		->setRequired();
	$observationContainer->addText('disturbance', 'Ruseni:')
		->setRequired();
	$observationContainer->addText('nelmHD','nelmHD:');
	$observationContainer->addText('bortle','Bortle:');
	$observationContainer->addText('bortlespec','Special bortle:');
	$observationContainer->addText('quality','Kvalita:');
	$observationContainer->addText('weather','Pocasi:');
	
	$sqmContainer->addText('value1', 'SQM:')
             ->setRequired();
        $sqmContainer->addText('value2', 'SQM:');
        $sqmContainer->addText('value3', 'SQM:');
        $sqmContainer->addText('value4', 'SQM:');
        $sqmContainer->addText('value5','SQM:');
	$sqmContainer->addText('azimute','Azimut:')
		->setRequired();
	$sqmContainer->addText('height','Vyska:');

	$equipmentContainer->addSelect('equipment','Vybava',$equipment)
		->setPrompt('Vyberte vybaveni');
	$photosContainer->addUpload('photo','Nahraj fotografii:')
		->addRule(Form::IMAGE, 'Format musi byt jpg, jpeg, png nebo gif');
        $form->addSubmit('send', 'Vložit do databáze');
        $form->onSuccess[] = $this->observationFormSucceeded;

        return $form;
    }
    
    public function observationFormSucceeded($form)
    {
        if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }
	//if ($this->user->isInRole('member')){}
	//if ($this->database->table('users')->where('user_id', $this->user->id)->where('active',1)!==true){
	//   $this->error('Nejprve aktivujte svůj účet.');
	//}
	
	$values = $form->getValues();
	$valuesObservation = $values['observation'];
	$valuesSqm = $values['sqm'];
	$valuesPhotos = $values['photos'];
	$valuesObservation['user_id'] = $this->user->id;
	$valuesObservation['location_id'] = $values['location'];
	$valuesObservation['equipment_id'] = $values['equipment'];

	$observation = $this->database->table('observations')
		->insert($valuesObservation);
	$valuesSqm['observation_id'] = $observation->id;
		
	$this->database->table('sqm')->insert($valuesSqm);
	
	$valuesPhotos['observation_id'] = $observation->id;
	
	$this->database->table('photos')->insert($valuesPhotos);
	
        $this->flashMessage("Příspěvek byl úspěšně vložen.", 'success');
        $this->redirect('show', $observation->id);
    }
    
    public function actionCreate()
    {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
	//if (!$this->user->isInRole('member')){}
    }
    
    public function actionDelete($observationId)
    {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
	if (!$this->database->table('users')->where('user_id', $this->user->id)->where('active',1)){
	    $this->error('Nejprve aktivujte svůj účet.');
	    $this->redirect('Homepage:default');
	}
	//if ($this->user->isInRole('member')){}
        else{
            
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
    }

        public function actionEdit($observationId)
    {
        if (!$this->user->isLoggedIn()) 
        {
            $this->redirect('Sign:in');
        } 
	//if ($this->user->isInRole('member')){}
	else if (!$this->database->table('users')->where('user_id', $this->user->id)->where('active',1)){
	    $this->error('Nejprve aktivujte svůj účet.');
	    $this->redirect('Homepage:default');
	}
        else{
           
            $observation = $this->database->table('observations')
                    ->where('user_id', $this->user->id)  // id_user v observations odpovida id v userovi
                    ->get($observationId);
            
            if (!$observation) { 
                $this->flashMessage('Nemáte oprávnění k editaci toho příspěvku.');
                $this->redirect('Observation:show?observationId='.$observationId);// existuje takove mereni?
            }
            if ($this->user->id == $observation->user_id) // druha kontrola
            {
                $this['observationForm']->setDefaults($observation->toArray());
            }
        }
    }
}


