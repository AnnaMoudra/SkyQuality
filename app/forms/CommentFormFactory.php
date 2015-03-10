<?php

use Nette\Application\UI\Form;

class CommentFormFactory
{
    /**
     * @return Form
     */
    public function create()
    {
        $form = new Form;
      
        $form->addText('name', 'Jméno:')
            ->setRequired();
        $form->addTextArea('content', 'Komentář:')
            ->setRequired();
        $form->addSubmit('send', 'Publikovat komentář');
   
        return $form;
    }
}