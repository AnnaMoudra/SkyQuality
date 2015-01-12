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
        $form = new Nette\Application\UI\Form;
	$locationContainer = $form->addContainer('location');
	$observationContainer = $form->addContainer('observation');
	$equipmentContainer = $form->addContainer('equipment');
	$sqmContainer1 = $form->addContainer('sqm1');
	//$sqmContainer2 = $form->addContainer('sqm2');
	//$sqmContainer3 = $form->addContainer('sqm3');
	//$sqmContainer4 = $form->addContainer('sqm4');
	//$sqmContainer5 = $form->addContainer('sqm5');
	$photosContainer = $form->addContainer('photos');
        
	$observationContainer->addText('date', 'Datum měření:')
            ->setRequired();
	$observationContainer->addText('time','cas mereni:')
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
	
	$locationContainer->addText('name','Zadejte nazev lokality:' )
		->setOption('id','location-name' )
		->setRequired();
	$locationContainer->addText('latituderaw','Zadejte zeměpisnou šířku:' )
		->setOption('id','location-latituderaw' )
		->setRequired();
	$locationContainer->addSelect('latitudehemisfera','Zadejte polokouli',$latitude )
		->setPrompt('zadejte polokouli')
		->setOption('id', 'location-latitudehemisfera')
		->setRequired();
	$locationContainer->addText('longituderaw', 'Zadejte zemepisnou delku:')
		->setOption('id','location-longituderaw' )
		->setRequired();
	$locationContainer->addSelect('longitudehemisfera','Zadejte polokouli', $longitude  )
		->setPrompt('zadejte polokouli')
		->setOption('id','location-longitudehemisfera' )
		->setRequired();
	$locationContainer->addText('altitude','Nadmořská výška:' )
		->setOption('id','location-altitude' )
		->setRequired();
	$locationContainer->addText('info','Popis lokality:' )
		->setOption('id', 'location-info');
	$locationContainer->addCheckbox( 'accessiblestand', 'Lokalita je volně přístupná.' )
		->setOption('id', 'location-accessiblestand' );
	
	$observationContainer->addText('observer', 'Pozorovatel')
		->setRequired();
	$observationContainer->addText('disturbance', 'Ruseni:')
		->setRequired();
	$observationContainer->addText('nelmHD','nelmHD:');
	$observationContainer->addText('bortle','Bortle:');
	$observationContainer->addText('bortlespec','Special bortle:');
	$observationContainer->addText('quality','Kvalita:');
	$observationContainer->addText('weather','Pocasi:');
	
	$sqmContainer1->addText('value1', 'SQM:')->setRequired();
        $sqmContainer1->addText('value2', 'SQM:');
        $sqmContainer1->addText('value3', 'SQM:');
        $sqmContainer1->addText('value4', 'SQM:');
        $sqmContainer1->addText('value5','SQM:');
	$sqmContainer1->addText('azimute','Azimut:')->setRequired();
	$sqmContainer1->addText('height','Vyska:');
	$sqmContainer1->addCheckbox('addanother','Zapsat dalsi mereni:')
		->addCondition(Form::EQUAL, TRUE)
		->toggle('sqm2');
	
	$equipmentContainer->addSelect('equipment','Vybava',$equipment)
		->setPrompt('Vyberte vybaveni');
	$form->addCheckbox('addphotos','Pridat fotografie')
		->addCondition(Form::EQUAL,TRUE)
		->toggle('photo1')
		->toggle('photo2')
		->toggle('photo3')
		->toggle('photo4')
		->toggle('photo5');
		
	$photosContainer->addUpload('photo1','Nahraj fotografii:')
		->addRule(Form::IMAGE, 'Format musi byt jpg, jpeg, png nebo gif')
		->setOption('id', 'photo1');
	$photosContainer->addUpload('photo2','Nahraj fotografii:')
		->addRule(Form::IMAGE, 'Format musi byt jpg, jpeg, png nebo gif')
		->setOption('id', 'photo2');
	$photosContainer->addUpload('photo3','Nahraj fotografii:')
		->addRule(Form::IMAGE, 'Format musi byt jpg, jpeg, png nebo gif')
		->setOption('id', 'photo3');
	$photosContainer->addUpload('photo4','Nahraj fotografii:')
		->addRule(Form::IMAGE, 'Format musi byt jpg, jpeg, png nebo gif')
		->setOption('id', 'photo4');
	$photosContainer->addUpload('photo5','Nahraj fotografii')
		->addRule(Form::IMAGE, 'Format musi byt jpg, jpeg, png nebo gif')
		->setOption('id', 'photo5');
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
}


