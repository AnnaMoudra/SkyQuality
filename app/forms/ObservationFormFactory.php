<?php

use Nette\Forms\Controls,
    Nette\Forms\Container,
    Nette\Forms\Controls\SubmitButton,
    Nette\Application\UI\Form,
    Nette\Database\Table\Selection,
    Nette\Forms\IControl;



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

	$locationContainer = $form->addContainer('location');
	$observationContainer = $form->addContainer('observation');
	$equipmentContainer = $form->addContainer('equipment');
	
	$observationContainer->addText('date', 'Datum měření:')
            ->setRequired();
	$observationContainer->addText('time','Čas měření:')
	    ->setRequired();
		$observationContainer->addText('observer', 'Pozorovatel')
	    ->setRequired();
	$observationContainer->addText('disturbance', 'Rušení:')
	    ->setRequired();
	$observationContainer->addText('nelmHD','NelmHD:');
	$observationContainer->addText('bortle','Bortle:');
	$observationContainer->addText('bortlespec','Specifický bortle:');
	$observationContainer->addText('quality','Kvalita:');
	$observationContainer->addTextArea('weather','Počasí:');
	
	$form->addSelect('locationid','Lokalita:',$locations)
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
		    ->setOption('id','location-name' )
		    ->addConditionOn($form['locationid'], Form::IS_IN, 1, 'Zadat novou lokalitu')
			->setRequired();
	    $locationContainer->addText('latituderaw','Zeměpisná šířka:' )
		    ->setOption('id','location-latituderaw' )
		    ->addConditionOn($form['locationid'], Form::EQUAL, 1, 'Zadat novou lokalitu')
			->setRequired();
	    $locationContainer->addSelect('latitudehemisfera','',$latitude )
		    ->setPrompt('zadejte polokouli')
		    ->setOption('id', 'location-latitudehemisfera')
		    ->addConditionOn($form['locationid'], Form::EQUAL, 1, 'Zadat novou lokalitu')
			->setRequired();
	    $locationContainer->addText('longituderaw', 'Zeměpisná délka:')
		    ->setOption('id','location-longituderaw' )
		    ->addConditionOn($form['locationid'], Form::EQUAL, 1, 'Zadat novou lokalitu')
			->setRequired();
	    $locationContainer->addSelect('longitudehemisfera','', $longitude  )
		    ->setPrompt('zadejte polokouli')
		    ->setOption('id','location-longitudehemisfera' )
		    ->addConditionOn($form['locationid'], Form::EQUAL, 1, 'Zadat novou lokalitu')
			->setRequired();
	    $locationContainer->addText('altitude','Nadmořská výška:' )
		    ->setOption('id','location-altitude' )
		    ->addConditionOn($form['locationid'], Form::EQUAL, 1, 'Zadat novou lokalitu')
			->setRequired();
	    $locationContainer->addTextArea('info','Popis lokality:' )
		    ->setOption('id', 'location-info');
	    $locationContainer->addCheckbox( 'accessiblestand', 'Lokalita je volně přístupná.' )
		    ->setOption('id', 'location-accessiblestand' );
	
	$multisqm = $form->addDynamic('sqm',function(Container $sqm) {
		$sqm->addText('azimute','Azimut:')->setRequired();
	    	$sqm->addText('value1', 'SQM:')->setRequired();
		$sqm->addText('value2', 'SQM:');
		$sqm->addText('value3', 'SQM:');
		$sqm->addText('value4', 'SQM:');
		$sqm->addText('value5','SQM:');
		$sqm->addText('height','Výška:');

	    $sqm->addSubmit("removeMultisqm", "Odebrat toto sqm")
		->setValidationScope(FALSE)
		->addRemoveOnClick();
	}, 1);

        $multisqm->addSubmit('add', 'Add next sqm')
	    ->setValidationScope(FALSE)
	    ->addCreateOnClick();
	

	$form->addSelect('equipmentid','Měřící zařízení:', $equipment)
		->setPrompt('Vyberte zařízení')
		->setOption(1, 'Zadat nové zařízení')
		->setRequired()
		->addCondition(Form::EQUAL, 1, 'Zadat nové zařízení')
		->toggle('equipment-name')	    //id containeru ?
		->toggle('equipment-type')->toggle('equipment-model')->toggle('equipment-sn');
	
	    $equipmentContainer->addText('name', 'Název:')
		->setOption('id', 'equipment-name')
		->addConditionOn($form['equipmentid'], Form::EQUAL, 1, 'Zadat nové zařízení')
		    ->setRequired();
	    $equipmentContainer->addSelect('type','Typ:', array('SQM','SQM-L'))
		->setOption('id', 'equipment-type')
		->addConditionOn($form['equipmentid'], Form::EQUAL, 1, 'Zadat nové zařízení')
		    ->setRequired();
	    $equipmentContainer->addText('model', 'Model:')
		->setOption('id', 'equipment-model')
		->addConditionOn($form['equipmentid'], Form::EQUAL, 1, 'Zadat nové zařízení')
		    ->setRequired();
	    $equipmentContainer->addText('sn', 'SN:')
		->setOption('id', 'equipment-sn')
		->addConditionOn($form['equipmentid'], Form::EQUAL, 1, 'Zadat nové zařízení')
		    ->setRequired();
	    
	$photosContainer = $form->addContainer('photos');
	$photo1C= $photosContainer->addContainer('photo1');
	$photo2C= $photosContainer->addContainer('photo2');
	$photo3C= $photosContainer->addContainer('photo3');
	$photo4C= $photosContainer->addContainer('photo4');
	
	$photosContainer->addCheckbox('addphotos','Přidat fotografie')
		->addCondition(Form::EQUAL,TRUE)
		->toggle('photo1')
		->toggle('photo2')
		->toggle('photo3')
		->toggle('photo4');
	
	$photo1C->addUpload('photo','Nahraj fotografii:')
		->setOption('id', 'photo1')
		->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
		    ->addRule(Form::FILLED, 'Nahrajte soubor')
		    ->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
		    ->addCondition(Form::FILLED)
			->toggle('info1');
	    $photo1C->addText('info','Popisek fotky:')
		->setOption('id', 'info1');
	$photo2C->addUpload('photo','Nahraj fotografii:')
		->setOption('id', 'photo2')
		->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
		    ->addCondition(Form::FILLED)
			->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
			->addCondition(Form::FILLED)
			->toggle('info2');
	    $photo2C->addText('info','Popisek fotky:')
		->setOption('id', 'info2');
	$photo3C->addUpload('photo','Nahraj fotografii:')
		->setOption('id', 'photo3')
		->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
		    ->addCondition(Form::FILLED)
			->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
			->addCondition(Form::FILLED)
			    ->toggle('info3');
	    $photo3C->addText('info','Popisek fotky:')
		->setOption('id', 'info3');
	$photo4C->addUpload('photo','Nahraj fotografii:')
		->setOption('id', 'photo4')
		->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
		    ->addCondition(Form::FILLED)
			->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
			->addCondition(Form::FILLED, TRUE)
			    ->toggle('info4');
	    $photo4C->addText('info','Popisek fotky:')
		->setOption('id', 'info4');
	$form->setCurrentGroup(NULL);
        $form->addSubmit('send', 'Vložit do databáze');  
	
        return $form;
    }
}

