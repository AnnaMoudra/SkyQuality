<?php
namespace App\Presenters;

use Nette,
        Nette\Database\Table\Selection;
use Exception;
use Nette\Security\Permission;



class PostPresenter extends BasePresenter
{
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

   public function renderShow($postId)
    {
        $observation = $this->database->table('observations')->get($postId);
        if (!$observation) {
            $this->error('Stránka nebyla nalezena');
        }

        $this->template->observation = $observation;
        $this->template->comments = $observation->related('comment')->order('created_at');
    }
    
    protected function createComponentCommentForm()
    {
        $form = new Nette\Application\UI\Form;

        $form->addText('name', 'Jméno:')
            ->setRequired();

        $form->addText('email', 'Email:');

        $form->addTextArea('content', 'Komentář:')
            ->setRequired();

        $form->addSubmit('send', 'Publikovat komentář');
        
        $form->onSuccess[] = $this->commentFormSucceeded;

        return $form;
    }
    
    public function commentFormSucceeded($form)
    {
        $values = $form->getValues();
        $postId = $this->getParameter('postId');

        $this->database->table('comments')->insert(array(
            'observation_id' => $postId,
            'name' => $values->name,
            'email' => $values->email,
            'content' => $values->content,
        ));

        $this->flashMessage('Děkuji za komentář', 'success');
        $this->redirect('this');
    }
    
    protected function createComponentPostForm()
    {
         if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }
	if ($this->database->table('users')->where('user_id', $this->user->id)->where('active',1)!==true){
	    $this->flashMessage('Nejprve aktivujte svůj účet.');
	}
        
        $form = new Nette\Application\UI\Form;
        $form->addText('date', 'Datum měření:')
            ->setRequired();
        $form->addText('sqm1', 'SQM:');
            //->setRequired();
        $form->addText('sqm2', 'SQM:');
            //->setRequired();
        $form->addText('sqm3', 'SQM:');
            //->setRequired();
        $form->addText('sqm4', 'SQM:');
            //->setRequired();

        $form->addSubmit('send', 'Vložit do databáze');
        $form->onSuccess[] = $this->postFormSucceeded;

        return $form;
    }
    
    public function postFormSucceeded($form)
    {
        if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }
	//if ($this->user->isInRole('member')){}
	if ($this->database->table('users')->where('user_id', $this->user->id)->where('active',1)!==true){
	   $this->error('Nejprve aktivujte svůj účet.');
	}
	$values = $form->getValues();
	$values['user_id'] = $this->user->id;
        $observation = $this->database->table('observations')->insert($values);
        
        $this->flashMessage("Příspěvek byl úspěšně vložen.", 'success');
        $this->redirect('show', $observation->id);
    }
    
    public function actionCreate()
    {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
	//if (!$this->user->isInRole('member')){}
    }
    
    public function actionDelete($postId)
    {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
	if (!$this->database->table('users')->where('user_id', $this->user->id)->where('active',1)){
	    $this->error('Nejprve aktivujte svůj účet.');
	    $this->redirect('Homepage:default');
	}
	//if ($this->user->isInRole('member')){}
        else{
            
            $post = $this->database->table('observations')
                    ->where('user_id', $this->user->id)  // id_user v observations odpovida id v userovi
                    ->get($postId);
            
            if (!$post) { 
                $this->flashMessage('Nemáte oprávnění ke smazání toho příspěvku.');
                $this->redirect('Post:show?postId='.$postId);
            }
                
            $this->database->table('observations')->where('id', $postId)->delete();
            
            $this->flashMessage("Měření bylo smazáno.", 'success');
            $this->redirect('Personal:');
            
        }
    }

        public function actionEdit($postId)
    {
        if (!$this->user->isLoggedIn()) 
        {
            $this->redirect('Sign:in');
        } 
	//if ($this->user->isInRole('member')){}
	else if (!$this->database->table('users')->where('user_id', $this->user->id)->where('active',1)){
	    $this->error('Nejprve aktivujte svůj účet.');
	    $this->redirect('Homepage:default');
	}
        else{
           
            $observation = $this->database->table('observations')
                    ->where('user_id', $this->user->id)  // id_user v observations odpovida id v userovi
                    ->get($postId);
            
            if (!$observation) { 
                $this->flashMessage('Nemáte oprávnění k editaci toho příspěvku.');
                $this->redirect('Post:show?postId='.$postId);// existuje takove mereni?
            }
            if ($this->user->id == $observation->user_id) // druha kontrola
            {
                $this['postForm']->setDefaults($observation->toArray());
            }
        }
    }
}


