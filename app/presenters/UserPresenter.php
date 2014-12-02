<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Presenters;

use Nette,
    App\Model\UserManager;
    
use Nette\Utils\Validators;
use Nette\Mail\Message;
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

	$form->addPassword('email', 'Email:')
            ->setRequired('Zadejte platnou emailovou adresu.');

        $form->addSubmit('send', 'Registrovat');

        //call method registerFormSucceeded() on success
        $form->onSuccess[] = $this->registerFormSucceeded;
        return $form;
    }   
            
    public function registerFormSucceeded($form) {
        $values = $form->getValues();
        
        if($values->password1 == $values->password2){		//validace hesla
            $this->userManager->add($values->username, $values->password1);
        } else {
            $this->flashMessage('Vaše hesla se neshodují.');
        }
	
	if(Validators::IsEmail($values->email)==true){		//validace emailové adresy
            $this->userManager->add($values->username, $values->password1,$values->email);
        } else {
            $this->flashMessage('Zadaná emailová adresa není platná.');
        }
	
	
	$message = new Message;		    //odešle aktivační email
	$message->addTo('test@gmail.com')
	    ->setFrom($values['email']);
	
	$template = $this->createTemplate();
	$template->setFile(__DIR__ . '/../templates/emails/activateRegistration.latte');
	$template->title = 'Aktivace účtu na SkyQuality.cz';
	$template->values = $values;
	$template->linkhash = hash($algo, $values);
	

	$message->setHtmlBody($template)
        ->send();

        $this->flashMessage('Byli jste úspěšně zaregistrováni. Na zadanou emailovou adresu vám přijde link k aktivaci vašeho účtu.', 'success');
        $this->redirect('Homepage:');
    }
}
        
        


