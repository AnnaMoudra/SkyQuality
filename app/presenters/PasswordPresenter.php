<?php

namespace App\Presenters;

use Nette,
	App\Model;
use App\Model\UserManager;
use Nette\Database\Table\Selection;
use Nette\Forms\Form;
use Nette\Utils\Strings;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Security\Passwords;


/**
 * Homepage presenter.
 */
class PasswordPresenter extends BasePresenter
{
   
    private $database;
    private $userManager;
	
	public function __construct(Nette\Database\Context $database, \App\Model\UserManager $userManager)
	{
		$this->database = $database;
		$this->userManager = $userManager;
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
		    ->where('email',$values->email)
		    ->update(array('newpass'=>$values->newpass)); //zada newpass userovi do db

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
		->setRequired('Zadejte nové heslo.')
		->addRule(Form::MIN_LENGTH,'Heslo musí mít alespoň %d znaků',6);

	    $form->addPassword('password2', 'Potvrďte heslo:')
		->setRequired('Potvrďte heslo.')
		->addRule(Form::EQUAL, 'Hesla se neshodují.', $form['password1']);

	    $form->addSubmit('send', 'Změnit heslo');

	    $form->onSuccess[] = $this->changePasswordFormSucceeded;

	    return $form;
	}   
	
	//public function changepass($newpass,$password) { tato fce uz neni potreba, je v userManager
	//    
	//   $row = $this->database->table('users')->where('newpass', $newpass)->fetch();
	//   
	//   if(!$row)
	//   {
	//	throw new Nette\Security\AuthenticationException('Such user was not found in the database', self::IDENTITY_NOT_FOUND);
	//   }
	//   
	//   $row->update(array('password' => Passwords::hash($password)));
	//   $row->update(array('newpass' => NULL));
	//    
	//}
         
	public function changePasswordFormSucceeded($form) {
	    $values = $form->getValues();
	    $newpass= $this->getHttpRequest()->getUrl()->getQueryParameter('newpass');// ziska linkhash z url
	    $user = $this->database->table('users')->where('newpass',$newpass)->get('id'); // ziska spravneho usera
	    $usernewpass = $this->database->table('users')->where('id',$user)->get('newpass');
	    dump($usernewpass);

	    if($newpass == $usernewpass){
	    //$this->changepass($usernewpass,$values['password1']);//zada udaje do db a vymaze newpass
	    //$this->userManager->changepass($usernewpass,$values['password1']);
	    $row = $this->database->table('users')->where('newpass', $newpass)->fetch();
	   
	   if(!$row)
	   {
		throw new Nette\Security\AuthenticationException('Such user was not found in the database', self::IDENTITY_NOT_FOUND);
	   }
	   
	    //$this->database->table('users')->where('newpass', $newpass)->update('password', Passwords::hash($values['password1']));
	    $row->update('newpass', NULL);
		
	    
	    $this->flashMessage('Vaše heslo bylo změněno. Nyní se můžete přihlásit.', 'success');
	    $this->redirect('Sign:in');
	    }
	    else{
		$this->flashMessage('Změna hesla se nezdařila. Pokuste se o změnu znovu s odkazem z dalšího emailu. V případě opětovného nezdaru, kontaktujte administrátora.');
		$this->redirect('Sign:in');   
	    }

	}

}

