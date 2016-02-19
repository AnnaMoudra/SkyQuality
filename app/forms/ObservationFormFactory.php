<?php

//use Nette\Forms\Controls,
use Nette\Forms\Container,
    Nette\Forms\Controls\SubmitButton,
    Nette\Application\UI\Form,
    Nette\Database\Table\Selection,
    Nette\Forms\IControl;
use Nextras\Forms\Controls;
use Nette\Utils\Arrays;
use Nette\Utils\Html;

Container::extensionMethod('addDatePicker', function (Container $container, $name, $label = NULL) {
    return $container[$name] = new Nextras\Forms\Controls\DatePicker($label);
});
Container::extensionMethod('addDateTimePicker', function (Container $container, $name, $label = NULL) {
    return $container[$name] = new Nextras\Forms\Controls\DateTimePicker($label);
});

class ObservationFormFactory extends \Nette\Application\UI\Form {

    /**
     * @return Form
     */
    private $database;

    function __construct(\Nette\Database\Context $database) {
        parent::__construct();
        $this->database = $database;
    }

    /*
      protected function attached($parent)
      {
      parent::attached($parent);
      if ($parent instanceof Nette\Application\UI\Presenter) {
      $this->presenter === $parent; // TRUE
      $user = $this->presenter->user; // ...
      }
      } */

    public function create() {
        $form = new Form;
        // Příprava hodnot pro jednotlivá políčka
        // lokality
        $locationsarr = $this->database->table('location')->fetchPairs('id', 'name');
        $locations = [];
        $locations['new'] = 'Zadat novou lokalitu';
        Arrays::insertAfter($locations, 'new', $locationsarr);
        // Zařízení
        $equipmentarr = $this->database->table('equipment')->fetchPairs('id', 'name');
        $equipment = [];
        $equipment['new'] = 'Zadat nové zařízení';
        Arrays::insertAfter($equipment, 'new', $equipmentarr);
        // Zeměpisná poloha
        $latitude = array('N' => 'severní šířky', 'S' => 'jižní šířky');
        $longitude = array('E' => 'východní délky', 'W' => 'západní délky');


        // Vlastni policka formulare
        // Zakladni info
        $form->addGroup('Základní informace');
        $observationContainer = $form->addContainer('observation');
        $observationContainer->addDateTimePicker('date', 'Čas a datum měření (UTC)')
                ->setRequired();

        $observationContainer->addText('observer', 'Pozorovatel')
                ->setRequired();

        //Lokalita
        $form->addSelect('locationid', 'Lokalita:', $locations)
                ->setPrompt('Zvolte lokalitu')
                ->setOption('new', 'Zadat novou lokalitu')
                ->setRequired()
                ->addCondition(Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->toggle('location-name')
                ->toggle('location-latituderaw')
                ->toggle('location-latitudehemisfera')
                ->toggle('location-longituderaw')
                ->toggle('location-longitudehemisfera')
                ->toggle('location-altitude')
                ->toggle('location-info')
                ->toggle('location-accessiblestand');

        // Nová lokalita
        $locationContainer = $form->addContainer('location');	
        $locationContainer->addText('name', 'Název lokality:')
                ->setOption('id', 'location-name')
                ->addConditionOn($form['locationid'], Form::IS_IN, 'new', 'Zadat novou lokalitu')
                ->setRequired();
        $locationContainer->addText('latituderaw', 'Zeměpisná šířka:')
                ->setOption('id', 'location-latituderaw')
                ->addConditionOn($form['locationid'], Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->setRequired();
        $locationContainer->addSelect('latitudehemisfera', '', $latitude)
                ->setPrompt('zadejte polokouli')
                ->setOption('id', 'location-latitudehemisfera')
                ->addConditionOn($form['locationid'], Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->setRequired();
        $locationContainer->addText('longituderaw', 'Zeměpisná délka:')
                ->setOption('id', 'location-longituderaw')
                ->addConditionOn($form['locationid'], Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->setRequired();
        $locationContainer->addSelect('longitudehemisfera', '', $longitude)
                ->setPrompt('zadejte polokouli')
                ->setOption('id', 'location-longitudehemisfera')
                ->addConditionOn($form['locationid'], Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->setRequired();
        $locationContainer->addText('altitude', 'Nadmořská výška:')
                ->setOption('id', 'location-altitude')
                ->setOption('description', 'm. n. m.')
                ->addConditionOn($form['locationid'], Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->setRequired();
        $locationContainer->addTextArea('info', 'Popis lokality:')
                ->setOption('id', 'location-info');
        $locationContainer->addCheckbox('accessiblestand', 'Lokalita je vhodným pozorovacím stanovištěm.')
                ->setOption('id', 'location-accessiblestand');


        //SQM měření
        $form->addGroup('Naměřené hodnoty');
        $multisqm = $form->addDynamic('sqm', function(Container $sqm) {
            $sqm->addText('value1', 'SQM:')
                    ->setRequired()
                    ->setOption('description', '[mag/arcsec²]')
                    ->setOption('class', 'sqmvalues')
                    ->addRule(Form::FLOAT, 'Hodnoty musí být čísla: např 18.21 či 21.3')
                    ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 15.00-24.00', array(15, 24));
            $sqm->addText('value2', 'SQM:')
                    ->setOption('class', 'sqmvalues')
                    ->addCondition(Form::FILLED)
                    ->addRule(Form::FLOAT, 'Hodnoty musí být čísla: např 18.21 či 21.3')
                    ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 15.00-24.00', array(15, 24));
            $sqm->addText('value3', 'SQM:')
                    ->setOption('class', 'sqmvalues')
                    ->addCondition(Form::FILLED)
                    ->addRule(Form::FLOAT, 'Hodnoty musí být čísla: např 18.21 či 21.3')
                    ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 15.00-24.00', array(15, 24));
            $sqm->addText('value4', 'SQM:')
                    ->setOption('class', 'sqmvalues')
                    ->addCondition(Form::FILLED)
                    ->addRule(Form::FLOAT, 'Hodnoty musí být čísla: např 18.21 či 21.3')
                    ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 15.00-24.00', array(15, 24));
            $sqm->addText('value5', 'SQM:')
                    ->setOption('class', 'sqmvalues')
                    ->addCondition(Form::FILLED)
                    ->addRule(Form::FLOAT, 'Hodnoty musí být čísla: např 18.21 či 21.3')
                    ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 15.00-24.00', array(15, 24));
            // Azimut 
            $azimut = array(
                0 => 'sever',
                45 => 'severovýchod',
                90 => 'východ',
                135 => 'jihovýchod',
                180 => 'jih',
                225 => 'jihozápad',
                270 => 'západ',
                315 => 'severozápad');
            // Výška
            $height = array(90 => 'zenit', 60 => '60°', 'k3' => 'jiná');
            $sqm->addSelect('height', 'Výška', $height)
                    ->setRequired()
                    ->setOption('id', 'height')
                    ->addCondition(Form::EQUAL, 60, '60°')
                    ->toggle('azimute')
                    ->endCondition()
                    ->addCondition(Form::EQUAL, 'k3', 'jiná')
                    ->toggle('heightspec')
                    ->toggle('azimute');
            $sqm->addText('heightspec', '')
                    ->setOption('id', 'heightspec')
                    ->setOption('description', '°')
                    ->addConditionOn($sqm['height'], Form::EQUAL, 'k3', 'jiná')
                    ->setRequired()
                    ->addRule(Form::INTEGER, 'Stupňě výšky musí být celé číslo.')
                    ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 0-90', array(0, 90));
            $sqm->addSelect('azimute', 'Azimut', $azimut)
                    ->setOption('id', 'azimute')
                    ->addConditionOn($sqm['height'], Form::NOT_EQUAL, 'zenit')
                    ->setRequired();

            $sqm->addSubmit("removeMultisqm", "Odebrat tyto hodnoty sqm")
                    ->setValidationScope(FALSE)
                    ->addRemoveOnClick();
        }, 1);

        //Přidat další SQM měření
        $multisqm->addSubmit('add', 'Přidat další hodnoty sqm')
                ->setValidationScope(FALSE)
                ->addCreateOnClick()
                ->setOption('description', 'Nejprve vyplňte všechna povinná pole.');


        //SQM zařízení
        $form->addGroup('Měřící zařízení')->setOption('class', 'observationform');
        $form->addSelect('equipmentid', 'Měřící zařízení:', $equipment)
                ->setPrompt('Vyberte zařízení')
                ->setOption('new', 'Zadat nové zařízení')
                ->setRequired()
                ->addCondition(Form::EQUAL, 'new', 'Zadat nové zařízení')
                ->toggle('equipment-name')     //id containeru ?
                ->toggle('equipment-type')
                ->toggle('equipment-model')
                ->toggle('equipment-sn');

        $equipmentContainer = $form->addContainer('equipment');

        $equipmentContainer->addText('name', 'Název:')
                ->setOption('id', 'equipment-name')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('../www/images/help.svg')
                        ->alt('Lokalita je dostupná, není na soukromém pozemku a podobně')
                        ->title('Lokalita je dostupná, není na soukromém pozemku a podobně'))
                ->addConditionOn($form['equipmentid'], Form::EQUAL, 'new', 'Zadat nové zařízení')
                ->setRequired();
        $equipmentContainer->addSelect('type', 'Typ:', array('SQM', 'SQM-L'))
                ->setOption('id', 'equipment-type')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('../www/images/help.svg')
                        ->alt('Lokalita je dostupná, není na soukromém pozemku a podobně')
                        ->title('Lokalita je dostupná, není na soukromém pozemku a podobně'))
                ->addConditionOn($form['equipmentid'], Form::EQUAL, 'new', 'Zadat nové zařízení')
                ->setRequired();
        $equipmentContainer->addText('model', 'Model:')
                ->setOption('id', 'equipment-model')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('../www/images/help.svg')
                        ->alt('Lokalita je dostupná, není na soukromém pozemku a podobně')
                        ->title('Lokalita je dostupná, není na soukromém pozemku a podobně'))
                ->addConditionOn($form['equipmentid'], Form::EQUAL, 'new', 'Zadat nové zařízení')
                ->setRequired();
        $equipmentContainer->addText('sn', 'SN:')
                ->setOption('id', 'equipment-sn')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('../www/images/help.svg')
                        ->alt('Lokalita je dostupná, není na soukromém pozemku a podobně')
                        ->title('Lokalita je dostupná, není na soukromém pozemku a podobně'))
                ->addConditionOn($form['equipmentid'], Form::EQUAL, 'new', 'Zadat nové zařízení')
                ->setRequired();
        $form->setCurrentGroup(Null);


        //Doplňující informace
        $form->addGroup('Doplňující informace');
        $observationContainer->addText('disturbance', 'Rušení');
        $observationContainer->addText('nelm', 'MHV')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('../www/images/help.svg')
                        ->alt('Lokalita je dostupná, není na soukromém pozemku a podobně')
                        ->title('Lokalita je dostupná, není na soukromém pozemku a podobně'));
        $observationContainer->addText('nelmHD', 'podle HD')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('../www/images/help.svg')
                        ->alt('Lokalita je dostupná, není na soukromém pozemku a podobně')
                        ->title('Lokalita je dostupná, není na soukromém pozemku a podobně'));
        $transparency = array(
            NULL => '', 
            6 => 'Neobvyklé podmínky', 
            5 => 'Velmi špatná',
            4 => 'Špatná', 
            3 => 'Průměrná',
            2 => 'Dobrá', 
            1 => 'Vynikající');
        $bortle = array(NULL => '', 9, 8, 7, 6, 5, 4, 3, 2, 1);
        $bortlespec = array(NULL => '', 'lepší' => 'lepší', 'horší' => 'horší');
        $observationContainer->addSelect('transparency', 'Průzračnost', $transparency)
                ->setAttribute('id', 'transparency')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('../www/images/help.svg')
                        ->alt('Lokalita je dostupná, není na soukromém pozemku a podobně')
                        ->title('Lokalita je dostupná, není na soukromém pozemku a podobně'));
        $observationContainer->addSelect('bortle', 'Bortle', $bortle)
                ->setAttribute('id', 'bortle');
        $observationContainer->addSelect('bortlespec', '', $bortlespec)
                ->setAttribute('id', 'bortlespec')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('../www/images/help.svg')
                        ->alt('Lokalita je dostupná, není na soukromém pozemku a podobně')
                        ->title('Lokalita je dostupná, není na soukromém pozemku a podobně'));
        $observationContainer->addTextArea('weather', 'Počasí')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('../www/images/help.svg')
                        ->alt('Lokalita je dostupná, není na soukromém pozemku a podobně')
                        ->title('Lokalita je dostupná, není na soukromém pozemku a podobně'));
        $observationContainer->addTextArea('info', 'Poznámky');

        // Přidávání fotografií
        $photosContainer = $form->addContainer('photos');
        $photo1C = $photosContainer->addContainer('photo1');
        $photo2C = $photosContainer->addContainer('photo2');
        $photo3C = $photosContainer->addContainer('photo3');
        $photo4C = $photosContainer->addContainer('photo4');

        $photosContainer->addCheckbox('addphotos', 'Přidat fotografie')
                ->addCondition(Form::EQUAL, TRUE)
                ->toggle('photo1')
                ->toggle('photo2')
                ->toggle('photo3')
                ->toggle('photo4');


        $photo1C->addUpload('photo', 'Nahraj fotografii:')
                ->setOption('id', 'photo1')
                ->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
                ->addRule(Form::FILLED, 'Nahrajte soubor')
                ->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
                ->addCondition(Form::FILLED)
                ->toggle('info1');
        $photo1C->addText('info', 'Popisek fotky:')
                ->setOption('id', 'info1');
        $photo2C->addUpload('photo', 'Nahraj fotografii:')
                ->setOption('id', 'photo2')
                ->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
                ->addCondition(Form::FILLED)
                ->toggle('info2');
        $photo2C->addText('info', 'Popisek fotky:')
                ->setOption('id', 'info2');
        $photo3C->addUpload('photo', 'Nahraj fotografii:')
                ->setOption('id', 'photo3')
                ->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
                ->addCondition(Form::FILLED)
                ->toggle('info3');
        $photo3C->addText('info', 'Popisek fotky:')
                ->setOption('id', 'info3');
        $photo4C->addUpload('photo', 'Nahraj fotografii:')
                ->setOption('id', 'photo4')
                ->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
                ->addCondition(Form::FILLED, TRUE)
                ->toggle('info4');
        $photo4C->addText('info', 'Popisek fotky:')
                ->setOption('id', 'info4');

        $form->setCurrentGroup(NULL);


        $form->addSubmit('send', 'Vložit do databáze');

        return $form;
    }

}
