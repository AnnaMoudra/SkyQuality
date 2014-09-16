<?php

namespace App\Presenters;

use Nette,
    App\Model\UserManager;


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

        try {
            $this->getUser()->login($values->username, $values->password); //$this->userManager->authenticate()
            $this->redirect('Homepage:');

        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Nesprávné přihlašovací jméno nebo heslo.'); //nebude nutne pri pouziti userManager
        }
    }
    


	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('Byli jste odhlášeni.');
		$this->redirect('in');
	}

}
