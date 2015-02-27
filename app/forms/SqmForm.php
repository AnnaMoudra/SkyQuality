<?php

use Nette\Application\UI\Form;

class SqmFormFactory
{
    /**
     * @return Form
     */
    public function create()
    {
        $form = new Form;
	
	$form->addText('value1', 'SQM:')->setRequired();
        $form->addText('value2', 'SQM:');
        $form->addText('value3', 'SQM:');
        $form->addText('value4', 'SQM:');
        $form->addText('value5','SQM:');
	$form->addText('azimute','Azimut:')->setRequired();
	$form->addText('height','Výška:');
   
        return $form;
    }
}