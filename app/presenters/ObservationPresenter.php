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
	$this->template->photos = $observation->related('photo')->order('photo');
        
    //    $sqm = $this->database->table('sqm');
    //    $sqm_id = $sqm->id;
    //    $sqmeach = $sqm_id->where('observation.id', $sqm_id);
    //    
    //     $this->template->sqm = $sqmeach;
        
        $this->template->sqm = $observation->related('sqm')->order('height DESC')->order('azimute ASC');
        $phosel = $this->database->table('photos')->where('observation.id', $observationId); 
    
        $this->template->phosel = $phosel;
                
        $photos = $this->database->table('photos');
        foreach ($photos as $photos) {
        $img[] = Image::fromFile('http://skyquality.cz/www/images/photos/'.$photos->photo)->resize(600, NULL);	
	}
        
        $this->template->img = $img;
        
        // Data pro Graf
        $locationId = $observation->location_id;
        $data3=[];
        $data1=[];
        $data2=[];
        
        $observ = $this->database->table('observations')
                ->where('location_id', $locationId)
                ->where('NOT (id ?)', $observationId)
                ->order('date DESC');
        
        foreach($observ as $observ){
        $time = strtotime($observ->date . ' GMT')*1000;  
        $equipmentId = $observ->equipment_id;
        $equipment = $this->database->table('equipment')->where('id', $equipmentId)->fetch('type');
            if ($equipment->type === 'SQM') {

            $data1[]=[$time,$observ->sqmavg]; 
            } 
            else {
            $data2[]=[$time,$observ->sqmavg];
            }
        } 
        
        $thistime = strtotime($observation->date . ' GMT')*1000; 
        $data3[]=[$thistime, $observation->sqmavg];

        $this->template->data2 = $data1;
        $this->template->data3 = $data2;
        $this->template->data4 = $data3;


    }

    public function converseToCD($a){
	$b = 10800*pow(10,(-0.4*$a));
	return $b;
    }
    
    public function numericalAverage(array $array){
	$length = count($array);
	$sum = array_sum($array);
	$number = $sum/$length;
	return $number;
    }
    
    public function converseToSQM($a){
	$log = log($a/10800, 10);
	$b = $log/-0.4;
	return $b;
    }
  
    protected function createComponentObservationForm()
    {
         if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.'); //ověří, že je uživatel přihlášen
        }
	

	$form = (new \ObservationFormFactory($this->database))->create(); //vytvoří formulář

	$form->onSuccess[] = array($this, 'observationFormSucceeded'); // přidá událost po odeslání

	return $form;
        
    }
    
    public function observationFormSucceeded($form)
    {
        if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }
	
	$observationId = $this->getParameter('observationId');
	$values = $form->getValues();
	$valuesObservation = $values['observation']; //data pristupna pod $valuesObservation['date']
	$valuesObservation['user_id'] = $this->user->id;
        $valuesSqm = $values['sqm'];//jen container! data pristupna pod $valuesSqm['value1']
	//EDITACE
	if ($observationId) { 
	    $observation = $this->database->table('observations')->get($observationId);
	    $sqm = $this->database->table('sqm')->where('id_observation',$observationId)->delete();
	    $updatephotos = $this->database->table('photos')->where('observation_id',$observationId)->fetchAll();
	    
	    if($values['locationid']===1){ //pokud se zadava nova lokalita
		$valuesLocation= $values['location']; // data z containeru!
		$valuesLocation['user_id']= $this->user->id;
		$this->database->table('location')->insert($valuesLocation);
	    }
	    else{$valuesObservation['location_id'] = $values['locationid'];}
	    if($values['equipmentid']===1){ // pokud se zadava nove zarizeni
		$valuesEquipment= $values['equipment'];
		$this->database->table('equipment')->insert($valuesEquipment);
	    }
	    else{
		$valuesObservation['equipment_id'] = $values['equipmentid'];
	    }
	    
	    $observation->update($valuesObservation);
	    //VYPOCET VALUEAVG V TABULCE SQM
	    foreach ($valuesSqm as $sqm) {
		$sqm['id_observation'] = $observation->id;
		
		$array = [$sqm['value1'],$sqm['value2'],$sqm['value3'],$sqm['value4'],$sqm['value5']];
		
		foreach ($array as $sqms) {
		    if ($sqms > 0){
		    $sqmlinear = $this->converseToCD($sqms);
		    $arraylinear[] = $sqmlinear;
		    }
		}
		
		$average = $this->numericalAverage($arraylinear);
		$sqm['valueavg'] = $this->converseToSQM($average);
		$arraylinear = null;
		
		$this->database->table('sqm')->where('id_observation',$observationId)->insert($sqm);  
	    }
	    
	    //VYPOCET SQMAVG V TABULCE OBSERVATION
	    $valuesavg = $this->database->table('sqm')->where('id_observation',$observationId);
	     
	    $arraylinear2 = [];
	    foreach ($valuesavg as $sqmavg) {
		    $sqmavg = $sqmavg->valueavg;
		    $sqmlinear = $this->converseToCD($sqmavg);
		    $arraylinear2[] = $sqmlinear;
		}
		
	    $average = $this->numericalAverage($arraylinear2);
	    $valuesObservation['sqmavg'] = $this->converseToSQM($average);
	    $observation->update($valuesObservation);
	    
	    $valuesPhotos = $values['photos'];
	    if ($valuesPhotos['addphotos']==TRUE){
		foreach ($valuesPhotos as $key => $arraypi) {
		    if($key!=='addphotos'){
			$photoarray = array();
			$photo = $arraypi['photo'];
			$photoarray['info']=$arraypi['info'];
			$photoarray['observation_id'] = $observation->id;
			$photoarray['location_id'] = $values['locationid'];
			if ($photo->isImage()){
			    $filename = $photo->getSanitizedName();
			    $photoarray['photo']= $observation->id.$filename; //snad osetri kolize
			    $this->database->table('photos')->insert($photoarray); //prepise fotky v db, max 4
			    $path = $this->context->parameters['wwwDir'].'/images/photos/'.$observation->id.$filename;
			    $fotka= $photo->toImage();
			    $fotka->save($path);//budeme ukladat name do db a uploadovat na adresu www/images/photos
			}
		    }
		}
	    }
	    
	    
	    $this->flashMessage("Měření upraveno!", "success");
	    
	}
	//NOVA OBSERVATION
	else{

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
	    $observation = $this->database->table('observations')
		    ->insert($valuesObservation);

	    foreach ($valuesSqm as $sqm) {
		$sqm['id_observation'] = $observation->id;
		
		$array = [$sqm['value1'],$sqm['value2'],$sqm['value3'],$sqm['value4'],$sqm['value5']];
		
		foreach ($array as $sqms) {
		    if ($sqms > 0){
		    $sqmlinear = $this->converseToCD($sqms);
		    $arraylinear[] = $sqmlinear;
		    }
		}
		
		$average = $this->numericalAverage($arraylinear);
		$sqm['valueavg'] = $this->converseToSQM($average);
		$arraylinear = null;
		
		$this->database->table('sqm')->where('id_observation',$observationId)->insert($sqm);  
	    }
	    //VYPOCET SQMAVG V TABULCE OBSERVATION
	    $valuesavg = $this->database->table('sqm')->where('id_observation',$observation->id);
	     
	    $arraylinear2 = [];
	    foreach ($valuesavg as $sqmavg) {
		    $sqmavg = $sqmavg->valueavg;
		    $sqmlinear = $this->converseToCD($sqmavg);
		    $arraylinear2[] = $sqmlinear;
		}
		
	    $average = $this->numericalAverage($arraylinear2);
	    $valuesObservation['sqmavg'] = $this->converseToSQM($average);
	    $observation->update($valuesObservation);

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
			    $photoarray['photo']= $observation->id.$filename; //snad osetri kolize
			    $photoarray['location_id'] = $observation->location->id;
			    $this->database->table('photos')->insert($photoarray); 
			    //nahraje data do db, uklada se name, info a 2 id
			    $path = $this->context->parameters['wwwDir'].'/images/photos/'.$observation->id.$filename;
			    $fotka= $photo->toImage();
			    $fotka->save($path);
			    //budeme ukladat name do db a uploadovat na adresu www/images/photos
			}
		    }
		}
	    }
	    
	    $this->flashMessage("Příspěvek byl úspěšně vložen.", 'success');
	}
	
        
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
	    $this->database->table('comments')->where('observation_id', $observationId)->delete();
	    $this->database->table('photos')->where('observation_id', $observationId)->delete();
            $this->database->table('sqm')->where('id_observation', $observationId)->delete();  
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
            
            if (!$observation) { 
                $this->flashMessage('Nemáte oprávnění k editaci toho příspěvku.');
                $this->redirect('Observation:show?observationId='.$observationId);// existuje takove mereni?
            }
	    
	    $location = $observation['location_id'];
	    $equipment = $observation['equipment_id'];
	    $sqm = $observation->related('sqm')->order('id');
	    
	    $this['observationForm']['observation']->setDefaults($observation);
	    $this['observationForm']['locationid']->setValue($location);
	    $this['observationForm']['sqm']->setDefaults($observation);
	    $this['observationForm']['equipmentid']->setValue($equipment);
	    $this['observationForm']['sqm']->setDefaults($sqm);

        }
    }


    public function actionErasePhoto($photoId, $observationId)
    {
	if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }

        $observation = $this->database->table('observations')
		->where('user_id', $this->user->id)  // id_user v observations odpovida id v userovi
                ->get($observationId);
            
            if (!$observation) { 
                $this->flashMessage('Nemáte oprávnění ke smazání této fotografie.');
                $this->redirect('Observation:show?observationId='.$observationId);
            }
	    else{
		$this->database->table('photos')->where('id',$photoId)->delete();
		$this->flashMessage('Fotografie byla vymazána.');
                $this->redirect('Observation:show?observationId='.$observationId);
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
            'content' => $values->content,
        ));

        $this->flashMessage('Děkuji za komentář', 'success');
        $this->redirect('this');
    }
}


