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
use Nette\Utils\Html;
use Nette\Forms\IControl;

/**
 * @class UserPresenter
 * @description Obsluha User templates a registrace uživatelů.
 */
class UserPresenter extends BasePresenter {

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
     * @memberOf UserPresenter
     * @description Register form factory.
     * @return array 
     */
    protected function createComponentRegisterForm() {
        $form = new Nette\Application\UI\Form;
        $form->addText('username', 'Uživatelské jméno')
                ->setRequired('Zadejte vaše uživatelské jméno.')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Jméno, email nebo přezdívka, pod kterou se budete přihlašovat')
                        ->title('Jméno, email nebo přezdívka, pod kterou se budete přihlašovat'));

        $form->addText('name', 'Jméno')
                ->setRequired('Zadejte jméno, které se bude zobrazovat u Vašich pozorování.')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Jméno, které se bude předvyplňovat u Vašich pozorování')
                        ->title('Jméno, které se bude předvyplňovat u Vašich pozorování'));

        $form->addText('email', 'Email')
                ->setRequired('Zadejte platnou emailovou adresu.')
                ->addRule(Form::EMAIL, 'Zadaná adresa není platná');

        $form->addPassword('password1', 'Heslo')
                ->setRequired('Zadejte heslo.')
                ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků', 6)
                ->setOption('description', Html::el('img style="margin-left: 0.1em;"')
                        ->class('help')
                        ->src('http://www.skyquality.cz/images/icons/help.svg')
                        ->alt('Heslo musí mít aspoň 6 znaků')
                        ->title('Heslo musí mít aspoň 6 znaků'));


        $form->addPassword('password2', 'Potvrďte heslo')
                ->setRequired('Potvrďte heslo.')
                ->addRule(Form::EQUAL, 'Hesla se neshodují.', $form['password1']);




        $form->addHidden('linkhash', Strings::random(10));
        $form->addSubmit('send', 'Registrovat')
                ->setAttribute('id', 'registersubmit');
        $form->onSuccess[] = $this->registerFormSucceeded;
        return $form;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Ukládá data z formuláře do databáze a odesílá email pro aktivaci účtu.
     * @memberOf UserPresenter 
     * @param array Data z formuláře.
     */
    public function registerFormSucceeded($form) {
        $values = $form->getValues();

        $message = new Message;
        $message->setFrom('SkyQuality <admin@skyquality.cz>')
                ->addTo($values->email);
        //->addBcc('anna.moudra@gmail.com');

        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/../templates/emails/activateRegistration.latte'); //vybere příslušnou šablonu pro odeslání
        $template->title = 'Aktivace účtu na SkyQuality.cz';
        $template->values = $values; // username, jmeno, email a linkhash

        $message->setHtmlBody($template);

        $this->userManager->add($values->username, $values->password1, $values->email, $values->name, $values->linkhash); //zadá údaje do databáze
        $mailer = new SendmailMailer;
        $mailer->send($message);

        $this->flashMessage('Byli jste úspěšně zaregistrováni. Na zadanou emailovou adresu Vám přijde odkaz k aktivaci účtu.', 'success');
        $this->redirect('Homepage:');
    }

}
