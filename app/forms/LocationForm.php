<?php

use Nette\Application\UI\Form;

class LocationFormFactory
{
    /**
     * @return Form
     */
    public function create()
    {
        $form = new Form;
	
	$latitude = array('N'=>'severní šířky', 'S' => 'jižní šířky');
	$longitude= array('E' => 'východní délky', 'W' => 'západní délky');
	
	$form->addText('name','Název lokality:' )->setRequired();
	$form->addText('latituderaw','Zeměpisná šířka:' )->setRequired();
	$form->addSelect('latitudehemisfera','',$latitude )
	    ->setPrompt('zadejte polokouli')
	    ->setRequired();
	$form->addText('longituderaw', 'Zeměpisná délka:')
	    ->setRequired();
	$form->addSelect('longitudehemisfera','', $longitude  )
	    ->setPrompt('zadejte polokouli')
	    ->setRequired();
	$form->addText('altitude','Nadmořská výška:' )->setRequired();
	$form->addTextArea('info','Popis lokality:' );
	$form->addCheckbox( 'accessiblestand', 'Lokalita je volně přístupná.' );
   
        return $form;
    }
}