<?php

namespace App\Presenters;

use Nette,
    App\Model\UserManager;
use Nette\Forms\Form;
use Nette\Utils\Strings;

/**
 * @class SignPresenter 
 * @author Anna Moudrá <anna.moudra@gmail.com>
 * @description Obsluhuje Sign template. 
 */
class SignPresenter extends BasePresenter {

    /** @var \App\Model\UserManager */
    private $userManager;

    /**
     * @description Vytváří spojení s modelem UserManager.
     * @param Služba definovaná v config.neon
     */
    function __construct(\App\Model\UserManager $userManager) {
        $this->userManager = $userManager;
    }

    /**
     * @memberOf SignPresenter
     * @description Sign-in form factory.
     * @return array 
     */
    protected function createComponentSignInForm() {
        $form = new Nette\Application\UI\Form;
        $form->addText('username', 'Uživatelské jméno')
                ->setRequired('Prosím vyplňte své uživatelské jméno.');

        $form->addPassword('password', 'Heslo')
                ->setRequired('Prosím vyplňte své heslo.');
        $form->addCheckbox('remember', 'Zůstat přihlášen');

        $form->addSubmit('send', 'Přihlásit');

        $form->onSuccess[] = $this->signInFormSucceeded;
        return $form;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Přihlašuje uživatele.
     * @memberOf SignPresenter 
     * @param array Hodnoty ze SignInForm.
     * @return grid
     */
    public function signInFormSucceeded($form) {
        $values = $form->values;
        try {
            $active = $this->userManager->isActive($values->username);
        } catch (\Nette\Security\AuthenticationException $e) {
            $this->flashMessage('Zadané uživatelské jméno v databázi neexistuje.');
            $this->redirect('Sign:in');
        }
        if ($active == FALSE) {
            $form->addError('Nejprve si aktivujte účet pomocí emailu.');
        } else {
            try {
                $this->getUser()->login($values->username, $values->password); //$this->userManager->authenticate()
                $this->redirect('Homepage:');
            } catch (Nette\Security\AuthenticationException $e) {
                $form->addError('Nesprávné přihlašovací jméno nebo heslo.'); //nebude nutne pri pouziti userManager
            }
        }
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Odhlásí uživatele.
     * @memberOf SignPresenter 
     */
    public function actionOut() {
        $this->getUser()->logout();
        $this->flashMessage('Byli jste odhlášeni.');
        $this->redirect('in');
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Aktivuje uživatelský účet.
     * @memberOf SignPresenter 
     */
    public function actionActivate() {
        $linkhash = $this->getHttpRequest()->getUrl()->getQueryParameter("hash"); // ziska linkhash z url
        $this->userManager->validate($linkhash);
        $this->flashMessage('Váš účet byl aktivován. Nyní se můžete přihlásit');
        $this->redirect('in');
    }

}
