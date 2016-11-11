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
        $locationsarr = $this->database->table('location')->order('name ASC')->group('location.id')->having('COUNT(:observation.id) > 0')->fetchPairs('id', 'name');
        $locations = [];
        $locations['new'] = 'Zadat novou lokalitu';
        Arrays::insertAfter($locations, 'new', $locationsarr);
        // Zařízení
        $equipmentarr = $this->database->table('equipment')->order('name ASC')->fetchPairs('id', 'name');
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
        $form->addSelect('locationid', 'Lokalita', $locations)
                ->setPrompt('Zvolte lokalitu')
                ->setOption('new', 'Zadat novou lokalitu')
                ->setRequired()
                ->addCondition(Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->toggle('location-name')
                ->toggle('newlocation')
                ->toggle('location-latituderaw')
                ->toggle('location-latitudehemisfera')
                ->toggle('location-longituderaw')
                ->toggle('location-longitudehemisfera')
                ->toggle('location-altitude')
                ->toggle('location-info')
                ->toggle('location-accessiblestand');

        // Nová lokalita
        $locationContainer = $form->addContainer('location');
        $locationContainer->addText('name', 'Název lokality')
                ->setOption('id', 'location-name')
                ->addConditionOn($form['locationid'], Form::IS_IN, 'new', 'Zadat novou lokalitu')
                ->setRequired();
        $locationContainer->addText('latitude', 'Zeměpisná šířka')
                ->setOption('id', 'location-latituderaw')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Hodnoty vyplňujte ve formátu desetinného čísla (12.345678).')
                        ->title('Hodnoty vyplňujte ve formátu desetinného čísla (12.345678).'))
                ->addConditionOn($form['locationid'], Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->setRequired()
                ->addRule(Form::FLOAT, 'Hodnoty musí být čísla: např 50.124578 či 45.5978')
                ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 00.00-90.00', array(0, 90));
        $locationContainer->addSelect('latitudehemisfera', '', $latitude)
                ->setOption('id', 'location-latitudehemisfera')
                ->addConditionOn($form['locationid'], Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->setRequired();
        $locationContainer->addText('longitude', 'Zeměpisná délka')
                ->setOption('id', 'location-longituderaw')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Hodnoty vyplňujte ve formátu desetinného čísla (12.345678).')
                        ->title('Hodnoty vyplňujte ve formátu desetinného čísla (12.345678).'))
                ->addConditionOn($form['locationid'], Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->setRequired()
                 ->addRule(Form::FLOAT, 'Hodnoty musí být čísla: např 10.124578 či 15.5978')
                ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 00.00-180.00', array(0, 180));
        $locationContainer->addSelect('longitudehemisfera', '', $longitude)
                ->setOption('id', 'location-longitudehemisfera')
                ->addConditionOn($form['locationid'], Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->setRequired();
        $locationContainer->addText('altitude', 'Nadmořská výška')
                ->setOption('id', 'location-altitude')
                ->setOption('description', 'm. n. m.')
                ->addConditionOn($form['locationid'], Form::EQUAL, 'new', 'Zadat novou lokalitu')
                ->setRequired();
        $locationContainer->addTextArea('info', 'Popis lokality')
                ->setOption('id', 'location-info');
        $locationContainer->addCheckbox('accessiblestand', 'Lokalita je vhodným pozorovacím stanovištěm (aneb je dostupná, není na soukromém pozemku a podobně)')
                ->setOption('id', 'location-accessiblestand');


        //SQM měření
        $form->addGroup('Naměřené hodnoty');
        $multisqm = $form->addDynamic('sqm', function(Container $sqm) {
            $sqm->addText('value1', 'SQM')
                    ->setRequired()
                    ->setOption('description', '[mag/arcsec²]')
                    ->setOption('class', 'sqmvalues')
                    ->addRule(Form::FLOAT, 'Hodnoty musí být čísla: např 18.21 či 21.3')
                    ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 15.00-24.00', array(15, 24));
            $sqm->addText('value2', 'SQM')
                    ->setOption('class', 'sqmvalues')
                    ->addCondition(Form::FILLED)
                    ->addRule(Form::FLOAT, 'Hodnoty musí být čísla: např 18.21 či 21.3')
                    ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 15.00-24.00', array(15, 24));
            $sqm->addText('value3', 'SQM')
                    ->setOption('class', 'sqmvalues')
                    ->addCondition(Form::FILLED)
                    ->addRule(Form::FLOAT, 'Hodnoty musí být čísla: např 18.21 či 21.3')
                    ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 15.00-24.00', array(15, 24));
            $sqm->addText('value4', 'SQM')
                    ->setOption('class', 'sqmvalues')
                    ->addCondition(Form::FILLED)
                    ->addRule(Form::FLOAT, 'Hodnoty musí být čísla: např 18.21 či 21.3')
                    ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 15.00-24.00', array(15, 24));
            $sqm->addText('value5', 'SQM')
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
            $heightarr = array(90 => 'zenit', 60 => '60°', 'k3' => 'jiná');
            $sqm->addSelect('height', 'Výška', $heightarr)
                    ->setRequired()
                    ->setOption('id', 'height')
                    ->addCondition(Form::EQUAL, 60, '60°')
                    ->toggle($sqm->name . '-azimute')
                    ->endCondition()
                    ->addCondition(Form::EQUAL, 'k3', 'jiná')
                    ->toggle($sqm->name . '-heightspec')
                    ->toggle($sqm->name . '-azimute');
            $sqm->addText('heightspec', '')
                    ->setOption('id', $sqm->name . '-heightspec')
                    ->setOption('description', '°')
                    ->addConditionOn($sqm['height'], Form::EQUAL, 'k3', 'jiná')
                    ->setRequired()
                    ->addRule(Form::INTEGER, 'Stupňě výšky musí být celé číslo.')
                    ->addRule(Form::RANGE, 'Vyplňte hodnoty v rozsahu 0-90', array(0, 90));
            $sqm->addSelect('azimute', 'Azimut', $azimut)
                    ->setOption('id', $sqm->name . '-azimute')
                    ->addConditionOn($sqm['height'], Form::NOT_EQUAL, 'zenit')
                    ->setRequired();

            $sqm->addSubmit("removeMultisqm", "Odebrat měření")
                    ->setAttribute('class', 'button')
                    ->setValidationScope(FALSE)
                    ->addRemoveOnClick();
        }, 1);

        //Přidat další SQM měření
        $multisqm->addSubmit('add', 'Další měření')
                ->setAttribute('class', 'button')
                ->setValidationScope(FALSE)
                ->addCreateOnClick()
                ->setOption('description', 'Nejprve vyplňte všechna povinná pole.');


        //SQM zařízení
        $form->addGroup('Měřící zařízení')->setOption('class', 'observationform');
        $form->addSelect('equipmentid', 'Měřící zařízení', $equipment)
                ->setPrompt('Vyberte zařízení')
                ->setOption('new', 'Zadat nové zařízení')
                ->setRequired()
                ->addCondition(Form::EQUAL, 'new', 'Zadat nové zařízení')
                ->toggle('newequipment')
                ->toggle('equipment-name')     //id containeru ?
                ->toggle('equipment-type')
                ->toggle('equipment-model')
                ->toggle('equipment-sn');

        $equipmentContainer = $form->addContainer('equipment');

        $equipmentContainer->addText('name', 'Název')
                ->setOption('id', 'equipment-name')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Vámi zvolené jméno zařízení')
                        ->title('Vámi zvolené jméno zařízení'))
                ->addConditionOn($form['equipmentid'], Form::EQUAL, 'new', 'Zadat nové zařízení')
                ->setRequired();
        $equipmentContainer->addSelect('type', 'Typ', array('SQM', 'SQM-L'))
                ->setOption('id', 'equipment-type')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Typ zařízení najdete v návodu - SQM-L má zorný úhel 20°, SQM 60°.')
                        ->title('Typ zařízení najdete v návodu - SQM-L má zorný úhel 20°, SQM 60°.'))
                ->addConditionOn($form['equipmentid'], Form::EQUAL, 'new', 'Zadat nové zařízení')
                ->setRequired();
        $equipmentContainer->addText('model', 'Model')
                ->setOption('id', 'equipment-model')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Model a sériové číslo (SN) se zobrazí na displeji zařízení po dlouhém stisknutí měřícího tlačítka.')
                        ->title('Model a sériové číslo (SN) se zobrazí na displeji zařízení po dlouhém stisknutí měřícího tlačítka.'))
                ->addConditionOn($form['equipmentid'], Form::EQUAL, 'new', 'Zadat nové zařízení')
                ->setRequired();
        $equipmentContainer->addText('sn', 'SN')
                ->setOption('id', 'equipment-sn')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Model a sériové číslo (SN) se zobrazí na displeji zařízení po dlouhém stisknutí měřícího tlačítka.')
                        ->title('Model a sériové číslo (SN) se zobrazí na displeji zařízení po dlouhém stisknutí měřícího tlačítka.'))
                ->addConditionOn($form['equipmentid'], Form::EQUAL, 'new', 'Zadat nové zařízení')
                ->setRequired();
        $form->setCurrentGroup(Null);


        //Doplňující informace
        $form->addGroup('Doplňující informace');
        $observationContainer->addText('disturbance', 'Výrazné rušení (Měsíc, Mléčná dráha apod.)');
        $observationContainer->addText('nelm', 'MHV')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Mezní hvězdná velikost nejslabší hvězdy viditelné pouhým okem.')
                        ->title('Mezní hvězdná velikost nejslabší hvězdy viditelné pouhým okem.'));
        $observationContainer->addText('nelmHD', 'podle HD')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Mezní hvězdná velikost nejslabší hvězdy viditelné pouhým okem - označení hvězdy v HD katalogu.')
                        ->title('Mezní hvězdná velikost nejslabší hvězdy viditelné pouhým okem - označení hvězdy v HD katalogu'));
        $transparency = array(
            6 => 'Neobvyklé podmínky',
            5 => 'Velmi špatná',
            4 => 'Špatná',
            3 => 'Průměrná',
            2 => 'Dobrá',
            1 => 'Vynikající');
        $bortle = array(9 => 9, 8 => 8, 7 => 7, 6 => 6, 5 => 5, 4 => 4, 3 => 3, 2 => 2, 1 => 1);
        $bortlespec = array('lepší' => 'lepší', 'horší' => 'horší');
        $observationContainer->addSelect('transparency', 'Průzračnost', $transparency)
                ->setAttribute('id', 'transparency')
                ->setPrompt('')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Podrobnější vysvětlení jednotlivých stupňů najdete pod odkazem níže.')
                        ->title('Podrobnější vysvětlení jednotlivých stupňů najdete pod odkazem níže.'));
        $observationContainer->addSelect('bortle', 'Bortle', $bortle)
                ->setAttribute('id', 'bortle')
                ->setPrompt('')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Podrobnější vysvětlení jednotlivých stupňů najdete pod odkazem vpravo.')
                        ->title('Podrobnější vysvětlení jednotlivých stupňů najdete pod odkazem vpravo.'));
        $observationContainer->addSelect('bortlespec', '', $bortlespec)
                ->setAttribute('id', 'bortlespec')
                ->setPrompt('');
        $observationContainer->addText('weather', 'Počasí (oblačnost, teplota apod.)')
                ->setRequired()
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
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
                ->toggle('photo4')
                ->toggle('newphoto1')
                ->toggle('newphoto2')
                ->toggle('newphoto3')
                ->toggle('newphoto4');


        $photo1C->addUpload('photo', 'Nahraj fotografii')
                ->setOption('id', 'photo1')
                ->setAttribute('class', 'button')
                ->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
                ->addRule(Form::FILLED, 'Nahrajte soubor')
                ->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
                ->addCondition(Form::FILLED)
                ->toggle('newinfo1');
        $photo1C->addText('info', 'Popisek fotky')
                ->setOption('id', 'info1');
        $photo2C->addUpload('photo', 'Nahraj fotografii')
                ->setOption('id', 'photo2')
                ->setAttribute('class', 'button')
                ->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
                ->addCondition(Form::FILLED)
                ->toggle('newinfo2');
        $photo2C->addText('info', 'Popisek fotky')
                ->setOption('id', 'info2');
        $photo3C->addUpload('photo', 'Nahraj fotografii:')
                ->setOption('id', 'photo3')
                ->setAttribute('class', 'button')
                ->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
                ->addCondition(Form::FILLED)
                ->toggle('newinfo3');
        $photo3C->addText('info', 'Popisek fotky')
                ->setOption('id', 'info3');
        $photo4C->addUpload('photo', 'Nahraj fotografii')
                ->setOption('id', 'photo4')
                ->setAttribute('class', 'button')
                ->addConditionOn($photosContainer['addphotos'], Form::EQUAL, TRUE)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE, 'Formát musí být jpg, jpeg, png nebo gif')
                ->addCondition(Form::FILLED, TRUE)
                ->toggle('newinfo4');
        $photo4C->addText('info', 'Popisek fotky')
                ->setOption('id', 'info4');

        $form->setCurrentGroup(NULL);


        $form->addSubmit('send', 'Vložit do databáze')
                ->setAttribute('class', 'sendbutton');

        return $form;
    }

}
