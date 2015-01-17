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
    
    protected function createComponentObservationForm()
    {
         if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }
	if ($this->database->table('users')->where('user_id', $this->user->id)->where('active',1)!==true){
	    $this->flashMessage('Nejprve aktivujte svůj účet.');
	}
        $locations= $this->database->table('location')->fetchPairs('id','name');
	$locations[1]= 'Zadat novou lokalitu';
	$latitude = array('N'=>'severní šířky', 'S' => 'jižní šířky');
	$longitude= array('E' => 'východní délky', 'W' => 'západní délky');
	$equipment = $this->database->table('equipment')->fetchPairs('id','name');
	$equipment[1]='Zadat nové zařízení';
        
	$form = new Nette\Application\UI\Form;
	$locationContainer = $form->addContainer('location');
	$observationContainer = $form->addContainer('observation');
	$equipmentContainer = $form->addContainer('equipment');
	$sqmContainer1 = $form->addContainer('sqm1');
	//$sqmContainer2 = $form->addContainer('sqm2');

	$photosContainer = $form->addContainer('photos');
        
	$observationContainer->addText('date', 'Datum měření:')
            ->setRequired();
	$observationContainer->addText('time','Čas měření:')
		->setRequired();
	
	$locationContainer->addSelect('location','Lokalita:',$locations)
		->setPrompt('Vyberte misto mereni')
		->setOption(1, 'Zadat novou lokalitu')
		->setRequired()
		->addCondition(Form::EQUAL, 1, 'Zadat novou lokalitu')
		->toggle('location-name')	    //id containeru ?
		->toggle('location-latituderaw')
		->toggle('location-latitudehemisfera')
		->toggle('location-longituderaw')
		->toggle('location-longitudehemisfera')
		->toggle('location-altitude')
		->toggle('location-info')
		->toggle('location-accessiblestand');
	
	    $locationContainer->addText('name','Název lokality:' )
		    ->setOption('id','location-name' )
		    ->setRequired();
	    $locationContainer->addText('latituderaw','Zeměpisná šířka:' )
		    ->setOption('id','location-latituderaw' )
		    ->setRequired();
	    $locationContainer->addSelect('latitudehemisfera','',$latitude )
		    ->setPrompt('zadejte polokouli')
		    ->setOption('id', 'location-latitudehemisfera')
		    ->setRequired();
	    $locationContainer->addText('longituderaw', 'Zeměpisná délka:')
		    ->setOption('id','location-longituderaw' )
		    ->setRequired();
	    $locationContainer->addSelect('longitudehemisfera','', $longitude  )
		    ->setPrompt('zadejte polokouli')
		    ->setOption('id','location-longitudehemisfera' )
		    ->setRequired();
	    $locationContainer->addText('altitude','Nadmořská výška:' )
		    ->setOption('id','location-altitude' )
		    ->setRequired();
	    $locationContainer->addTextArea('info','Popis lokality:' )
		    ->setOption('id', 'location-info');
	    $locationContainer->addCheckbox( 'accessiblestand', 'Lokalita je volně přístupná.' )
		    ->setOption('id', 'location-accessiblestand' );
	
	$observationContainer->addText('observer', 'Pozorovatel')
		->setRequired();
	$observationContainer->addText('disturbance', 'Rušení:')
		->setRequired();
	$observationContainer->addText('nelmHD','NelmHD:');
	$observationContainer->addText('bortle','Bortle:');
	$observationContainer->addText('bortlespec','Specifický bortle:');
	$observationContainer->addText('quality','Kvalita:');
	$observationContainer->addTextArea('weather','Počasí:');
	
	$sqmContainer1->addText('value1', 'SQM:')->setRequired();
        $sqmContainer1->addText('value2', 'SQM:');
        $sqmContainer1->addText('value3', 'SQM:');
        $sqmContainer1->addText('value4', 'SQM:');
        $sqmContainer1->addText('value5','SQM:');
	$sqmContainer1->addText('azimute','Azimut:')->setRequired();
	$sqmContainer1->addText('height','Výška:');
	$sqmContainer1->addCheckbox('addanother','Přidat další měření:')
		->addCondition(Form::EQUAL, TRUE)
		->toggle('sqm2');
	
	$equipmentContainer->addSelect('equipment','Měřící zařízení:', $equipment)
		->setPrompt('Vyberte zařízení')
		->setOption(1, 'Zadat nové zařízení')
		->setRequired()
		->addCondition(Form::EQUAL, 1, 'Zadat nové zařízení')
		->toggle('equipment-name')	    //id containeru ?
		->toggle('equipment-type')
		->toggle('equipment-model')
		->toggle('equipment-sn');
	
	    $equipmentContainer->addText('name', 'Název:')
		->setOption('id', 'equipment-name')
		->setRequired();
	    $equipmentContainer->addSelect('type','Typ:', array('SQM','SQM-L'))
		   ->setOption('id', 'equipment-type')
		    ->setRequired();
	    $equipmentContainer->addText('model', 'Model:')
		    ->setRequired()
		    ->setOption('id', 'equipment-model');
	    $equipmentContainer->addText('sn', 'SN:')
		    ->setOption('id', 'equipment-sn')
		    ->setRequired();

	$form->addCheckbox('addphotos','Přidat fotografie')
		->addCondition(Form::EQUAL,TRUE)
		->toggle('photo1')
		->toggle('photo2')
		->toggle('photo3')
		->toggle('photo4')
		->toggle('photo5');
		
	$photosContainer->addUpload('photo1','Nahraj fotografii:')
		->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')->setOption('id', 'photo1');
	$photosContainer->addUpload('photo2','Nahraj fotografii:')
		->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')->setOption('id', 'photo2');
	$photosContainer->addUpload('photo3','Nahraj fotografii:')
		->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')->setOption('id', 'photo3');
	$photosContainer->addUpload('photo4','Nahraj fotografii:')
		->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')->setOption('id', 'photo4');
	$photosContainer->addUpload('photo5','Nahraj fotografii')
		->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')->setOption('id', 'photo5');
	
        $form->addSubmit('send', 'Vložit do databáze');
        $form->onSuccess[] = $this->observationFormSucceeded;

        return $form;
    }
    
    public function observationFormSucceeded($form)
    {
        if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }	
	$values = $form->getValues();
	$valuesObservation = $values['observation'];
	$valuesSqm = $values['sqm'];
	$valuesPhotos = $values['photos'];
	$valuesObservation['user_id'] = $this->user->id;
	
	if($values['location']['location']==1){
	    $valuesLocation= $values['location'];
	    $valuesLocation['user_id']= $this->user->id;
	    $this->database->table('location')->insert($valuesLocation);
	}
	else{
	    $valuesObservation['location_id'] = $values['location'];
	}
	
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
    }
    
    public function actionDelete($observationId)
    {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
	if (!$this->database->table('users')->where('user_id', $this->user->id)->where('active',1)){
	    $this->error('Nejprve aktivujte svůj účet.');
	    $this->redirect('Homepage:default');
	}else{
            
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


