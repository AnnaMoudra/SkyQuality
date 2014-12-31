<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Database\Table\Selection;


/**
 * Homepage presenter.
 */
class PersonalPresenter extends BasePresenter
{
   
    private $database;
	
	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}

                
	public function renderDefault()
	{
            $this->template->observations = $this->database->table('observations')
                    ->where('user_id', $this->user->id) // vytáhne userovy pozorovani z tabulky observations
                    ->order('created_at DESC');
            $this->template->personal = $this->database->table('users')
                    ->where('id', $this->user->id);
	    //if (!$this->user->isInRole('member')){}
	    if($this->database->table('users')->where('id',  $this->user->id)->where('active', 1)!==true){
		$this->flashMessage('Váš účet nebyl aktualizován. Nemuzete tak pridavat a editovat mereni');
	    }
	}
}
