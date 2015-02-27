<?php

use Nette\Application\UI\Form;

class EquipmentFormFactory
{
    /**
     * @return Form
     */
    public function create()
    {
        $form = new Form;
      
	$form->addText('name', 'Název:')->setRequired();
	$form->addSelect('type','Typ:', array('SQM','SQM-L'))->setRequired();
	$form->addText('model', 'Model:')->setRequired();
	$form->addText('sn', 'SN:')->setRequired();
   
        return $form;
    }
}
