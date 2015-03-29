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
use Nette\Application\UI\Form;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Utils\Strings;
use Nette\Forms\IControl;
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
            ->setRequired('Zadejte heslo.')
	    ->addRule(Form::MIN_LENGTH,'Heslo musi mit alespoň %d znaku',6);

        $form->addPassword('password2', 'Potvrďte heslo:')
            ->setRequired('Potvrďte heslo.')
	    ->addRule(Form::EQUAL, 'Hesla se neshodují.', $form['password1']);
	
	$form->addText('email', 'Email:')
            ->setRequired('Zadejte platnou emailovou adresu.')
	    ->addRule(Form::EMAIL,'Zadaná adresa není platná');
	    
	$form->addHidden('linkhash', Strings::random(10));

        $form->addSubmit('send', 'Registrovat');
	
        $form->onSuccess[] = $this->registerFormSucceeded;

        return $form;
    }   
         
    public function registerFormSucceeded($form) {
        $values = $form->getValues();
	
	$message = new Message;		    //vytvoreni emailu
	$message->setFrom('SkyQuality <admin@skyquality.cz>') //od koho
		->addTo($values->email)	// adds recipient
		->addBcc('anna.moudra@gmail.com'); //pro testovaci ucely
	
	$template = $this->createTemplate();
	$template->setFile(__DIR__ . '/../templates/emails/activateRegistration.latte'); //prislusna sablona
	$template->title = 'Aktivace účtu na SkyQuality.cz'; // predmet zpravy
	$template->values = $values; //jmeno, email a linkhash
	
	$message->setHtmlBody($template);
        	
	$this->userManager->add($values->username, $values->password1, $values->email, $values->linkhash);//zada udaje do db
	$mailer = new SendmailMailer;
	$mailer->send($message);

	$this->flashMessage('Byli jste úspěšně zaregistrováni. Na zadanou emailovou adresu vám přijde odkaz k aktivaci vašeho účtu.', 'success');
	$this->redirect('Homepage:');

    }
}
   

        
    
