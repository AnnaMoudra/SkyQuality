<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Presenters;

use Nette,
    App\Model\UserManager;

/**
 * Register presenter.
 */
class UserPresenter extends BasePresenter
{
    /** @var \App\Model\UserManager */
    private $userManager;
    
    function __construct(\App\Model\UserManager $userManager) {
        $this->userManager = $userManager;
    }
    
    protected function createComponentRegisterForm()
    {
        $form = new Nette\Application\UI\Form;
        $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Zadejte vaše uživatelské jméno.');

        $form->addPassword('password1', 'Heslo:')
            ->setRequired('Zadejte heslo.');

        $form->addPassword('password2', 'Potvrďte heslo:')
            ->setRequired('Potvrďte heslo.');

        $form->addSubmit('send', 'Registrovat');

        //call method registerFormSucceeded() on success
        $form->onSuccess[] = $this->registerFormSucceeded;
        return $form;
    }   
            
    public function registerFormSucceeded($form) {
        $values = $form->getValues();
        
        if($values->password1 == $values->password2){
            $this->userManager->add($values->username, $values->password1);
        } else {
            $this->flashMessage('Vaše hesla se neshodují.');
        }

        $this->flashMessage('Byli jste úspěšně zaregistrováni.', 'success');
        $this->redirect('Sign:in');
    }
}
        
        


