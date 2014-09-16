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
            $this->template->posts = $this->database->table('posts')
                    ->where('user_id', $this->user->id) // vytáhne userovy post z tabulky posts
                    ->order('created_at DESC');
            $this->template->personal = $this->database->table('users')
                    ->where('id', $this->user->id);
	}
}
