<?php

use Nette,
    Nette\Forms\Controls,
    Nette\Forms\Container,
    Nette\Forms\Controls\SubmitButton,
    Nette\Application\UI\Form,
    Nette\Database\Table\Selection;



class ObservationFormFactory extends \Nette\Application\UI\Form
{
    /**
     * @return Form
     */
    private $database;
    function __construct(\Nette\Database\Context $database) {
	    parent::__construct();
	    $this->database = $database;
	}
	
    public function create()
    {
	
	
        $form = new Form;
      
        $locations = $this->database->table('location')->fetchPairs('id','name');
	$locations[1] = 'Zadat novou lokalitu';
	$latitude = array('N'=>'severní šířky', 'S' => 'jižní šířky');
	$longitude= array('E' => 'východní délky', 'W' => 'západní délky');
	$equipment = $this->database->table('equipment')->fetchPairs('id','name');
	$equipment[1]='Zadat nové zařízení';
        //
	$locationContainer = $form->addContainer('location');
	$observationContainer = $form->addContainer('observation');
	$equipmentContainer = $form->addContainer('equipment');
	$photosContainer = $form->addContainer('photos');
        //
	$observationContainer->addText('date', 'Datum měření:')
            ->setRequired();
	$observationContainer->addText('time','Čas měření:')
		->setRequired();
	//
	$locationContainer->addSelect('location','Lokalita:',$locations)
		->setPrompt('Vyberte misto mereni')
		->setOption(1, 'Zadat novou lokalitu')
		->setRequired()
		->addCondition(Form::EQUAL, 1, 'Zadat novou lokalitu')
		->toggle('location-name')
		->toggle('location-latituderaw')
		->toggle('location-latitudehemisfera')
		->toggle('location-longituderaw')
		->toggle('location-longitudehemisfera')
		->toggle('location-altitude')
		->toggle('location-info')
		->toggle('location-accessiblestand');
	
	    $locationContainer->addText('name','Název lokality:' )
		    ->setOption('id','location-name' )->setRequired();
	    $locationContainer->addText('latituderaw','Zeměpisná šířka:' )
		    ->setOption('id','location-latituderaw' )->setRequired();
	    $locationContainer->addSelect('latitudehemisfera','',$latitude )
		    ->setPrompt('zadejte polokouli')
		    ->setOption('id', 'location-latitudehemisfera')->setRequired();
	    $locationContainer->addText('longituderaw', 'Zeměpisná délka:')
		    ->setOption('id','location-longituderaw' )->setRequired();
	    $locationContainer->addSelect('longitudehemisfera','', $longitude  )
		    ->setPrompt('zadejte polokouli')
		    ->setOption('id','location-longitudehemisfera' )->setRequired();
	    $locationContainer->addText('altitude','Nadmořská výška:' )
		    ->setOption('id','location-altitude' )->setRequired();
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
	
	$multisqm = $form->addDynamic('sqm',function(Container $sqm) {
	    	$sqm->addText('value1', 'SQM:')->setRequired();
		$sqm->addText('value2', 'SQM:');
		$sqm->addText('value3', 'SQM:');
		$sqm->addText('value4', 'SQM:');
		$sqm->addText('value5','SQM:');
		$sqm->addText('azimute','Azimut:')->setRequired();
		$sqm->addText('height','Výška:');

	    $sqm->addSubmit("removeMultisqm", "Odebrat toto sqm")
		->setValidationScope(FALSE)
		->addRemoveOnClick();
	}, 1);

        $multisqm->addSubmit('add', 'Add next sqm')
	    ->setValidationScope(FALSE)
	    ->addCreateOnClick();
	
	
		
	
	$equipmentContainer->addSelect('equipment','Měřící zařízení:', $equipment)
		->setPrompt('Vyberte zařízení')
		->setOption(1, 'Zadat nové zařízení')
		->setRequired()
		->addCondition(Form::EQUAL, 1, 'Zadat nové zařízení')
		->toggle('equipment-name')	    //id containeru ?
		->toggle('equipment-type')->toggle('equipment-model')->toggle('equipment-sn');
	
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
	
        return $form;
    }
}

