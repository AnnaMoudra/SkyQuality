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
        $location = $this->database->table('location')->get($locationId);
        if (!$location) {
            $this->error('Stránka nebyla nalezena');
        }

        $this->template->location = $location;
        $this->template->comments = $location->related('comment')->order('created_at');
    }
    
        public function actionEdit($locationId)
    {
        if (!$this->user->isLoggedIn()) 
        {
            $this->redirect('Sign:in');
        } else{
           
            $location = $this->database->table('location')
                    ->where('user_id', $this->user->id)  // id_user v observations odpovida id v userovi
                    ->get($locationId);
            
            if (!$location) { 
                $this->flashMessage('Nemáte oprávnění k editaci toho příspěvku.');
                $this->redirect('Location:show?locationId='.$locationId);// existuje takova lokalita
            }
            if ($this->user->id == $location->user_id) // druha kontrola
            {
                $this['locationForm']->setDefaults($location->toArray()); //neexistuje takovy Form!!!
            }
        }
    }
    
      protected function createComponentCommentForm()
    {
        $form = (new \CommentFormFactory())->create();

	$form->onSuccess[] = array($this, 'commentFormSucceeded'); // a přidat událost po odeslání

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
}
