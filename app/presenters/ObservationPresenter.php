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

/**
 * @class ObservationPresenter.
 * @author Anna Moudrá <anna.moudra@gmail.com>
 * @description Obsluhuje Observation templates.
 */
class ObservationPresenter extends BasePresenter {

    /** @var Nette\Database\Context */
    private $database;

    /**
     * @description Vytváří připojení k databázi.
     * @param Spojení vytvořené v config.neon
     */
    public function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Připravuje data pro vykreslení náhledu jednotlivých pozorování.
     * @memberOf ObservationPresenter 
     * @param int Id pozorování k nahlédnutí.
     */
    public function renderShow($observationId) {
        $observation = $this->database->table('observations')->get($observationId);
        if (!$observation) {
            $this->error('Stránka nebyla nalezena');
        }
        $phosel = $this->database->table('photos')->where('observation.id', $observationId);

        $this->template->observation = $observation;
        $this->template->comments = $observation->related('comment')->order('created_at');
        $this->template->sqm = $observation->related('sqm')->order('height DESC')->order('azimute ASC');
        $this->template->phosel = $phosel;


        if ($phosel->count() > 0) {
            foreach ($phosel as $photos) {
                $imgl[] = array(
                    'fotky' => Image::fromFile('http://skyquality.cz/www/images/photos/' . $photos->photo)->resize(600, NULL),
                    'popisky' => ($photos->info)
                );
            }
            $this->template->imgl = $imgl;
        }

        // Připravuje data pro graf
        $locationId = $observation->location_id;
        $data3 = [];
        $data1 = [];
        $data2 = [];

        $observ = $this->database->table('observations')
                ->where('location_id', $locationId)
                ->where('NOT (id ?)', $observationId)
                ->order('date DESC');

        foreach ($observ as $observ) {
            $time = strtotime($observ->date . ' GMT') * 1000;
            $equipmentId = $observ->equipment_id;
            $equipment = $this->database->table('equipment')->where('id', $equipmentId)->fetch('type');
            if ($equipment->type === 'SQM') {
                $data1[] = [$time, $observ->sqmavg];
            } else {
                $data2[] = [$time, $observ->sqmavg];
            }
        }

        $thistime = strtotime($observation->date . ' GMT') * 1000;
        $data3[] = [$thistime, $observation->sqmavg];

        $this->template->data2 = $data1;
        $this->template->data3 = $data2;
        $this->template->data4 = $data3;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Konvertuje jednotky MSA na cd/m**2.
     * @memberOf ObservationPresenter 
     * @param float SQM hodnota v MSA.
     * @return float SQM hodnota v cd/m**2.
     */
    public function converseToCD($a) {
        $b = 10800 * pow(10, (-0.4 * $a));
        return $b;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Počítá aritmetický průměr.
     * @memberOf OservationPresenter 
     * @param array
     * @return float Aritmetický průměr
     */
    public function numericalAverage(array $array) {
        $length = count($array);
        $sum = array_sum($array);
        $number = $sum / $length;
        return $number;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Konvertuje jednotky z cd/m**2 na jednotky MSA.
     * @memberOf ObservationPresenter 
     * @param float SQM hodnota v cd/m**2.
     * @return float  SQM hodnota v MSA.
     */
    public function converseToSQM($a) {
        $log = log($a / 10800, 10);
        $b = $log / -0.4;
        return $b;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Vytváří formulář pro přidání pozorování.
     * @memberOf ObservationPresenter. 
     * @return array Hodnoty z formuláře.
     */
    protected function createComponentObservationForm() {
        if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.'); //ověří, zda je uživatel přihlášen
        }

        $form = (new \ObservationFormFactory($this->database))->create(); //vytvoří formulář za složky app/forms

        $form->onSuccess[] = array($this, 'observationFormSucceeded'); // přidá událost po odeslání
        return $form;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Vyhodnocuje hodnoty z ObservationForm a ukládá je do databáze.
     * @memberOf ObservationPresenter. 
     * @param array Hodnoty z formuláře ObservationForm.
     */
    public function observationFormSucceeded($form) {
        if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }
        //$this['ObservationForm']['date']->setDefaults(date('%d.%m.%Y %H:%m'));
        //$this['ObservationForm']['observert']->setDefaultValue($this->user->name);

        $observationId = $this->getParameter('observationId');
        $values = $form->getValues();
        $valuesObservation = $values['observation']; //data pristupna pod $valuesObservation['%']
        $valuesObservation['user_id'] = $this->user->id;
        $valuesSqm = $values['sqm']; //data pristupna pod $valuesSqm['value1']

        /**
         * @description Tato část vyhodnocuje editaci formuláře.
         */
        if ($observationId) {
            $observation = $this->database->table('observations')->get($observationId);
            $sqm = $this->database->table('sqm')->where('id_observation', $observationId)->delete();
            $updatephotos = $this->database->table('photos')->where('observation_id', $observationId)->fetchAll();

            if ($values['locationid'] === 'new') { // případ zadávání zcela nové lokality
                $valuesLocation = $values['location']; // přebírá data z containeru
                $valuesLocation['user_id'] = $this->user->id;
                $this->database->table('location')->insert($valuesLocation);
                $name = $valuesLocation['name'];
                $valuesObservation['location_id'] = $this->database->table('location')->where('name', $name)->fetch('id');
            } else {
                $valuesObservation['location_id'] = $values['locationid'];
            }
            if ($values['equipmentid'] === 'new') { // případ zadávání zcela nového zařízení
                $valuesEquipment = $values['equipment'];
                $this->database->table('equipment')->insert($valuesEquipment);
                $nameE = $valuesEquipment['name'];
                $valuesObservation['equipment_id'] = $this->database->table('equipment')->where('name', $nameE)->fetch('id');
            } else {
                $valuesObservation['equipment_id'] = $values['equipmentid'];
            }

            $observation->update($valuesObservation); //upraví data v tabulce observations

            /**
             * @description Tento cyklus vypočítává správné hodnoty sqm pro tabulku sqm.
             */
            foreach ($valuesSqm as $sqm) {
                $sqm['id_observation'] = $observation->id;
                $array = [$sqm['value1'], $sqm['value2'], $sqm['value3'], $sqm['value4'], $sqm['value5']];
                foreach ($array as $sqms) {
                    if ($sqms > 0) {
                        $sqmlinear = $this->converseToCD($sqms);
                        $arraylinear[] = $sqmlinear;
                    }
                }
                $average = $this->numericalAverage($arraylinear);
                $sqm['valueavg'] = $this->converseToSQM($average);
                $arraylinear = null;

                if ($sqm['height'] === 90) {
                    $qm['azimute'] = NULL;
                    unset($sqm['heightspec']);
                } else if ($sqm['height'] === 60) {
                    unset($sqm['heightspec']);
                } else {
                    $sqm['height'] = $sqm['heightspec'];
                    unset($sqm['heightspec']);
                }

                $this->database->table('sqm')->where('id_observation', $observationId)->insert($sqm);
            }

            /**
             * @description Tato část vypočítává správné hodnoty sqm pro tabulku observation.
             */
            $valuesavg = $this->database->table('sqm')->where('id_observation', $observationId);
            $arraylinear2 = [];
            foreach ($valuesavg as $sqmavg) {
                $sqmavg = $sqmavg->valueavg;
                $sqmlinear = $this->converseToCD($sqmavg);
                $arraylinear2[] = $sqmlinear;
            }
            $average = $this->numericalAverage($arraylinear2);
            $valuesObservation['sqmavg'] = $this->converseToSQM($average);
            $observation->update($valuesObservation);


            /**
             * @description Tento část nahrává nové fotky.
             */
            $valuesPhotos = $values['photos'];
            if ($valuesPhotos['addphotos'] == TRUE) {
                foreach ($valuesPhotos as $key => $arraypi) {
                    if ($key !== 'addphotos') {
                        $photoarray = array();
                        $photo = $arraypi['photo'];
                        $photoarray['info'] = $arraypi['info'];
                        $photoarray['observation_id'] = $observation->id;
                        $photoarray['location_id'] = $values['locationid'];
                        if ($photo->isImage()) {
                            $filename = $photo->getSanitizedName();
                            $photoarray['photo'] = $observation->id . $filename; //přidáním id pozorování ošetříme případné kolize stejných názvů fotek
                            $this->database->table('photos')->insert($photoarray);  //uloží jména fotek do tabulky photos
                            $path = $this->context->parameters['wwwDir'] . '/images/photos/' . $observation->id . $filename;
                            $fotka = $photo->toImage();
                            $fotka->save($path);    //nahraje fotky na adresu www/images/photos
                        }
                    }
                }
            }
            $this->flashMessage("Měření upraveno!", "success");
        }
        /**
         * @description Tato část vyhodnocuje přidání nového pozorování.
         */ else {
            if ($values['locationid'] === 'new') { //v případě zadání nové lokality
                $valuesLocation = $values['location'];
                $valuesLocation['user_id'] = $this->user->id;
                $this->database->table('location')->insert($valuesLocation);
                $name = $valuesLocation['name'];
                $valuesObservation['location_id'] = $this->database->table('location')->where('name', $name)->fetch('id');
            } else {
                $valuesObservation['location_id'] = $values['locationid'];
            }
            if ($values['equipmentid'] === 'new') { // v případě zadání nového zařízení
                $valuesEquipment = $values['equipment'];
                $this->database->table('equipment')->insert($valuesEquipment);
                $nameE = $valuesEquipment['name'];
                $valuesObservation['equipment_id'] = $this->database->table('equipment')->where('name', $nameE)->fetch('id');
            } else {
                $valuesObservation['equipment_id'] = $values['equipmentid'];
            }
            $observation = $this->database->table('observations')
                    ->insert($valuesObservation);

            /**
             * @description Tento cyklus vypočítává správné hodnoty sqm pro tabulku sqm.
             */
            foreach ($valuesSqm as $sqm) {
                $sqm['id_observation'] = $observation->id;
                $array = [$sqm['value1'], $sqm['value2'], $sqm['value3'], $sqm['value4'], $sqm['value5']];
                foreach ($array as $sqms) {
                    if ($sqms > 0) {
                        $sqmlinear = $this->converseToCD($sqms);
                        $arraylinear[] = $sqmlinear;
                    }
                }
                $average = $this->numericalAverage($arraylinear);
                $sqm['valueavg'] = $this->converseToSQM($average);
                $arraylinear = null;

                if ($sqm['height'] === 90) {
                    $sqm['azimute'] = NULL;
                    unset($sqm['heightspec']);
                } else if ($sqm['height'] === 60) {
                    unset($sqm['heightspec']);
                } else {
                    $sqm['height'] = $sqm['heightspec'];
                    unset($sqm['heightspec']);
                }

                $this->database->table('sqm')->where('id_observation', $observationId)->insert($sqm);
            }

            /**
             * @description Tato část vypočítává správné hodnoty sqm pro tabulku observation.
             */
            $valuesavg = $this->database->table('sqm')->where('id_observation', $observation->id);

            $arraylinear2 = [];
            foreach ($valuesavg as $sqmavg) {
                $sqmavg = $sqmavg->valueavg;
                $sqmlinear = $this->converseToCD($sqmavg);
                $arraylinear2[] = $sqmlinear;
            }
            $average = $this->numericalAverage($arraylinear2);
            $valuesObservation['sqmavg'] = $this->converseToSQM($average);
            $observation->update($valuesObservation);

            /**
             * @description Tento část nahrává nové fotky.
             */
            $valuesPhotos = $values['photos'];
            if ($valuesPhotos['addphotos'] == TRUE) {
                foreach ($valuesPhotos as $key => $arraypi) {
                    if ($key !== 'addphotos') {
                        $photoarray = array();
                        $photo = $arraypi['photo'];
                        $photoarray['info'] = $arraypi['info'];
                        $photoarray['observation_id'] = $observation->id;
                        if ($photo->isImage()) {
                            $filename = $photo->getSanitizedName();
                            $photoarray['photo'] = $observation->id . $filename; //snad osetri kolize
                            $photoarray['location_id'] = $observation->location->id;
                            $this->database->table('photos')->insert($photoarray);
                            //nahraje data do tabulky, ukladá se name, info a 2 id
                            $path = $this->context->parameters['wwwDir'] . '/images/photos/' . $observation->id . $filename;
                            $fotka = $photo->toImage();
                            $fotka->save($path);
                            //fotky nahrajeme na adresu www/images/photos
                        }
                    }
                }
            }

            $this->flashMessage("Příspěvek byl úspěšně vložen.", 'success');
        }
        $this->redirect('show', $observation->id);
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Vykreslí stránku s formulářem.
     * @memberOf ObservationPresenter. 
     */
    public function actionCreate() {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Smaže pozorování.
     * @memberOf ObservationPresenter. 
     * @param int Id pozorování, které chceme smazat.
     */
    public function actionDelete($observationId) {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
        $observation = $this->database->table('observations')
                ->where('user_id', $this->user->id)
                ->get($observationId);

        if (!$observation) {
            $this->flashMessage('Nemáte oprávnění ke smazání toho příspěvku.');
            $this->redirect('Observation:show?observationId=' . $observationId);
        }
        $this->database->table('comments')->where('observation_id', $observationId)->delete();
        $this->database->table('photos')->where('observation_id', $observationId)->delete();
        $this->database->table('sqm')->where('id_observation', $observationId)->delete();
        $this->database->table('observations')->where('id', $observationId)->delete();

        $this->flashMessage("Měření bylo smazáno.", 'success');
        $this->redirect('Personal:');
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Vykreslí formulář pro editaci pozorování a vloží do něj příslušná data.
     * @memberOf ObservationPresenter. 
     * @param int Id pozorování, které chceme upravit.
     */
    public function actionEdit($observationId) {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        } else {
            $observation = $this->database->table('observations')
                    ->where('user_id', $this->user->id)
                    ->get($observationId);

            if (!$observation) {
                $this->flashMessage('Nemáte oprávnění k editaci toho příspěvku.');
                $this->redirect('Observation:show?observationId=' . $observationId);
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

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Smaže fotografii.
     * @memberOf ObservationPresenter. 
     * @param int, int Id fotografie a pozorování.
     */
    public function actionErasePhoto($photoId, $observationId) {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }

        $observation = $this->database->table('observations')
                ->where('user_id', $this->user->id)
                ->get($observationId);

        if (!$observation) {
            $this->flashMessage('Nemáte oprávnění ke smazání této fotografie.');
            $this->redirect('Observation:show?observationId=' . $observationId);
        } else {
            $this->database->table('photos')->where('id', $photoId)->delete();
            $this->flashMessage('Fotografie byla vymazána.');
            $this->redirect('Observation:show?observationId=' . $observationId);
        }
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Vytvoří formulář pro přidávání komentářů.
     * @memberOf ObservationPresenter. 
     */
    protected function createComponentCommentForm() {
        $form = (new \CommentFormFactory())->create(); // vytvoří formulář ze složky app/forms
        $form->onSuccess[] = array($this, 'commentFormSucceeded');
        return $form;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Uloží komentář do databáze.
     * @memberOf ObservationPresenter. 
     * @param array Hodnoty z formuláře CommentForm.
     */
    public function commentFormSucceeded($form) {
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
