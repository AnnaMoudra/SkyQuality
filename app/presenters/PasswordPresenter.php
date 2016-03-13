<?php

namespace App\Presenters;

use Nette,
    App\Model;
use App\Model\UserManager;
use Nette\Database\Table\Selection;
use Nette\Forms\Form;
use Nette\Utils\Strings;
use Nette\Utils\Html;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Security\Passwords;

/**
 * @class PasswordPresenter.
 * @author Anna Moudrá <anna.moudra@gmail.com>
 * @description Obsluhuje Password templates.
 */
class PasswordPresenter extends BasePresenter {

    private $database;
    private $userManager;

    /**
     * @description Vytváří připojení k databázi a propojení se službou UserManager.
     * @param Spojení vytvořené v config.neon
     * @param Služba definovaná v config.neon
     */
    public function __construct(Nette\Database\Context $database, \App\Model\UserManager $userManager) {
        $this->database = $database;
        $this->userManager = $userManager;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Vytváří formulář pro zadání emailu.
     * @memberOf PasswordPresenter 
     * @return array getNewpassForm
     */
    protected function createComponentGetNewpassForm() {
        $form = new Nette\Application\UI\Form;

        $form->addText('email', '')
                ->setDefaultValue('@')
                ->setRequired('Zadejte email:')
                ->addRule(Form::EMAIL, 'Zadaná adresa není platná.')
                ->setOption('description', Html::el('img')
                        ->class('help')
                        ->src('../www/images/help.svg')
                        ->alt('Zadejte e-mail, pod kterým jste zaregistrován/a')
                        ->title('Zadejte e-mail, pod kterým jste zaregistrován/a'));
       
        $form->addHidden('newpass', Strings::random(10)); //vytvoří náhodný string pro ověření uživatele při změně hesla

        $form->addSubmit('send', 'Poslat odkaz pro změnu hesla');
        $form->onSuccess[] = $this->getNewpassFormSucceeded;
        return $form;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Odesílá email s odkazem na změnu hesla.
     * @memberOf PasswordPresenter 
     * @param array Hodnoty formuláře getNewpassForm
     */
    public function getNewpassFormSucceeded($form) {

        $values = $form->getValues();

        $this->database->table('users')
                ->where('email', $values->email)
                ->update(array('newpass' => $values->newpass)); //zadá řetězec uživateli do sloupce newpass

        $message = new Message;      //vytvoří email
        $message->setFrom('SkyQuality <admin@skyquality.cz>')
                ->addTo($values->email);
        //->addBcc('anna.moudra@gmail.com');

        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/../templates/emails/changePassword.latte'); //vytvoří zprávu z příslušné šablony v templates/emails
        $template->title = 'Změna hesla k účtu na SkyQuality.cz';
        $template->values = $values; //email a newpass řetězec

        $message->setHtmlBody($template);
        $mailer = new SendmailMailer;
        $mailer->send($message);

        $this->flashMessage('Na zadanou emailovou adresu Vám přijde odkaz ke změně hesla.', 'success');
        $this->redirect('Homepage:');
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Vytvoří formulář pro změnu hesla.
     * @memberOf PasswordPresenter 
     * @return array Hodnoty z formuláře.
     */
    protected function createComponentChangePasswordForm() {
        $form = new Nette\Application\UI\Form;

        $form->addPassword('password1', 'Heslo:')
                ->setRequired('Zadejte nové heslo.')
                ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků', 6);

        $form->addPassword('password2', 'Potvrďte heslo:')
                ->setRequired('Potvrďte heslo.')
                ->addRule(Form::EQUAL, 'Hesla se neshodují.', $form['password1']);

        $form->addHidden('newpass', $this->getHttpRequest()->getUrl()->getQueryParameter('newpass'));

        $form->addSubmit('send', 'Změnit heslo');

        $form->onSuccess[] = $this->changePasswordFormSucceeded;

        return $form;
    }

    /**
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Vyhodnotí formulář a v případě úspěchu změní heslo.
     * @memberOf PasswordPresenter 
     * @param array Hodnoty formuláře changePasswordForm
     */
    public function changePasswordFormSucceeded($form) {
        $values = $form->getValues();
        $newpass = $values['newpass'];
        $user = $this->database->table('users')->where('newpass', $newpass)->fetch(); // získá správného uživatele
        if ($newpass === $user->newpass) {
            $row = $this->database->table('users')->where('newpass', $user->newpass)->fetch();

            if (!$row) {
                throw new Nette\Security\AuthenticationException('Such user was not found in the database', self::IDENTITY_NOT_FOUND);
            } else {
                $newpassword = Passwords::hash($values['password1']);
                $row->update(array('password' => $newpassword));
                $row->update(array('newpass' => NULL));
                $this->flashMessage('Vaše heslo bylo změněno. Nyní se můžete přihlásit.', 'success');
                $this->redirect('Sign:in');
            }
        } else {
            $this->flashMessage('Změna hesla se nezdařila. Pokuste se o změnu znovu s odkazem z dalšího emailu. V případě opětovného nezdaru kontaktujte administrátora.');
            $this->redirect('Sign:in');
        }
    }

}
