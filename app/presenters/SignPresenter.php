<?php

namespace App\Presenters;

use Nette,
    App\Model\UserManager;
use Nette\Forms\Form;
use Nette\Utils\Strings;



/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter
{
        /** @var \App\Model\UserManager */
    private $userManager;
    
    function __construct(\App\Model\UserManager $userManager) {
        $this->userManager = $userManager;
    }

	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
    protected function createComponentSignInForm()
    {
        $form = new Nette\Application\UI\Form;
        $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Prosím vyplňte své uživatelské jméno.');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Prosím vyplňte své heslo.');

        $form->addCheckbox('remember', 'Zůstat přihlášen');

        $form->addSubmit('send', 'Přihlásit');

        // zavolá metodu signInFormSucceeded() při úspěšném odeslání
        $form->onSuccess[] = $this->signInFormSucceeded;
        return $form;
    }


    public function signInFormSucceeded($form)
    {
        $values = $form->values;
	try{
	$active = $this->userManager->isActive($values->username);
	}catch(\Nette\Security\AuthenticationException $e){
	    $this->flashMessage('Zadané uživatelské jméno v databázi neexistuje.');
	    $this->redirect('Sign:in');
	}
	
	
	if($active == FALSE){
	    $form->addError('Nejprve si aktivujte účet pomocí emailu.');
	}
	else{
	    try {
	    $this->getUser()->login($values->username, $values->password); //$this->userManager->authenticate()
	    $this->redirect('Homepage:');

	    } catch (Nette\Security\AuthenticationException $e) {
		$form->addError('Nesprávné přihlašovací jméno nebo heslo.'); //nebude nutne pri pouziti userManager
	    }
	}
	
    }
    


	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('Byli jste odhlášeni.');
		$this->redirect('in');
	}


	public function actionActivate()
	{
	    $linkhash= $this->getHttpRequest()->getUrl()->getQueryParameter("hash");// ziska linkhash z url


		$this->userManager->validate($linkhash);
		$this->flashMessage('Váš účet byl aktivován. Nyní se můžete přihlásit');
		$this->redirect('in');
	    
	
	}
	
	protected function createComponentGetNewpassForm() {
	    $form = new Nette\Application\UI\Form;

	    $form->addText('email', 'Email:')
		->setRequired('Zadejte email:')
		->addRule(Form::EMAIL,'Zadaná adresa není platná.');
	    $form->addHidden('newpass', Strings::random(10));

	    $form->addSubmit('send', 'Odeslat link pro změnu hesla.');

	    $form->onSuccess[] = $this->getNewpassFormSucceeded;

	    return $form;
	}
	
	public function getNewpassFormSucceeded($form) {
	    
	    $values = $form->getValues();

	    $this->database->table('users')
		    ->where('email',$values['email'])
		    ->update('newpass',$values['newpass']); //zada newpass userovi do db

	    $message = new Message;		    //vytvoreni emailu
	    $message->setFrom('SkyQuality <admin@skyquality.cz>') //od koho
		    ->addTo($values->email)	// adds recipient
		    ->addBcc('anna.moudra@gmail.com'); //pro testovaci ucely

	    $template = $this->createTemplate();
	    $template->setFile(__DIR__ . '/../templates/emails/changePassword.latte'); //prislusna sablona
	    $template->title = 'Změna hesla k účtu na SkyQuality.cz'; // predmet zpravy
	    $template->values = $values; //email a newpass

	    $message->setHtmlBody($template);

	    $mailer = new SendmailMailer;
	    $mailer->send($message);

	    $this->flashMessage('Na zadanou emailovou adresu vám přijde link ke změně vašeho hesla.', 'success');
	    $this->redirect('Homepage:');


	}
	

	protected function createComponentChangePasswordForm()
	{
	    $form = new Nette\Application\UI\Form;

	    $form->addPassword('password1', 'Heslo:')
		->setRequired('Zadejte heslo.')
		->addRule(Form::MIN_LENGTH,'Heslo musí mít alespoň %d znaků',6);

	    $form->addPassword('password2', 'Potvrďte heslo:')
		->setRequired('Potvrďte heslo.')
		->addRule(Form::EQUAL, 'Hesla se neshodují.', $form['password1']);

	    $form->addSubmit('send', 'Změnit heslo');

	    $form->onSuccess[] = $this->changePasswordFormSucceeded;

	    return $form;
	}   
         
	public function changePasswordFormSucceeded($form) {
	    $values = $form->getValues();
	    $newpass= $this->getHttpRequest()->getUrl()->getQueryParameter("newpass");// ziska linkhash z url
	    $user = $this->database->table('users')->where('newpass',$newpass)->id; // ziska spravneho usera

	    if($newpass === $this->database->table('users')->$user->newpass){
	    $this->userManager->changepass($newpass,$values->password1);//zada udaje do db a vymaze newpass

	    $this->flashMessage('Vaše heslo bylo změněno. Nyní se můžete přihlásit.', 'success');
	    $this->redirect('Sign:in');
	    }
	    else{
		$this->flashMessage('Změna hesla se nezdařila. Pokuste se o změnu znovu s odkazem z dalšího emailu. V případě opětovného nezdaru, kontaktujte administrátora.');
		$this->redirect('Sign:in');   
	    }

	}
    
    
	

	
	
	
	    
	

}
