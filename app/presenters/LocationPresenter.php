<?php

namespace App\Presenters;

use Nette,
        Nette\Database\Table\Selection;
use Exception;
use Nette\Security\Permission;
use Nette\Forms\Form;



class LocationPresenter extends BasePresenter
{
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

   public function renderShow($locationId)
    {
        $observation = $this->database->table('location')->get($locationId);
        if (!$location) {
            $this->error('Stránka nebyla nalezena');
        }

        $this->template->location = $location;
        $this->template->comments = $location->related('comment')->order('created_at');
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
        $locationId = $this->getParameter('locationId');

        $this->database->table('comments')->insert(array(
            'location_id' => $locationId,
            'name' => $values->name,
            'email' => $values->email,
            'content' => $values->content,
        ));

        $this->flashMessage('Děkuji za komentář', 'success');
        $this->redirect('this');
    }
    
    protected function createComponentLocationForm()
    {
        
    }
    
    public function observationFormSucceeded($form)
    {
        if (!$this->user->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }
	//if ($this->user->isInRole('member')){}
	//if ($this->database->table('users')->where('user_id', $this->user->id)->where('active',1)!==true){
	//   $this->error('Nejprve aktivujte svůj účet.');
	//}

    }
    
    public function actionCreate()
    {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
	//if (!$this->user->isInRole('member')){}
    }
    
    public function actionDelete($locationId)
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
            
            $location = $this->database->table('location')
                    ->where('user_id', $this->user->id)  // id_user v observations odpovida id v userovi
                    ->get($locationId);
            
            if (!$location) { 
                $this->flashMessage('Nemáte oprávnění ke smazání toho příspěvku.');
                $this->redirect('Location:show?locationId='.$locationId);
            }
                
            $this->database->table('location')->where('id', $locationId)->delete();
            
            $this->flashMessage("Lokalita byla smazána.", 'success');
            $this->redirect('Personal:');
            
        }
    }

        public function actionEdit($locationId)
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
           
            $location = $this->database->table('location')
                    ->where('user_id', $this->user->id)  // id_user v observations odpovida id v userovi
                    ->get($locationId);
            
            if (!$location) { 
                $this->flashMessage('Nemáte oprávnění k editaci toho příspěvku.');
                $this->redirect('Location:show?locationId='.$locationId);// existuje takova lokalita
            }
            if ($this->user->id == $location->user_id) // druha kontrola
            {
                $this['locationForm']->setDefaults($location->toArray());
            }
        }
    }
}




