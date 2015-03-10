<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Database\Table\Selection;

use Mesour\DataGrid,
    Mesour\DataGrid\Grid,
    Mesour\DataGrid\Extensions\Pager,
    Mesour\DataGrid\Components\Link,
    Mesour\DataGrid\Components\Button,
    Mesour\DataGrid\Render,
    Mesour\DataGrid\NetteDbDataSource;


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
	protected function createComponentMyObsGrid($name) {
	    $selection= $this->database->table('observations')->where('user.id',$this->user->id);
	    $selection->select('observations.id, date, observer, sqmavg,' . 'location.name');
	    
	    $source = new NetteDbDataSource($selection);
	    $grid = new Grid($this, $name);
	    $primarykey = 'id';
	    $grid->setPrimaryKey($primarykey); // primary key is now used always
	    $grid->setDataSource($source);
	    $grid->addDate('date','Datum')
		    ->setFormat('d.m.y - H:i')
                    ->setOrdering(TRUE);
	    $grid->addText('name','Lokalita');            
	    $grid->addNumber('sqmavg','Průměrné sqm')->setDecimals(2);
	    $grid->addText('observer','Pozorovatel');
	    $grid->addText('name','Lokalita');
            $action = $grid->addActions('');
	    $action->addButton()
		    ->setType('btn-primary')
		    ->setText('detail pozorování')
		    ->setTitle('detail')
		    ->setAttribute('href', new Link('Observation:show',array(
			'observationId'=>'{'.$primarykey.'}'
		    )));
	    $grid->enablePager(20);
	    $grid->enableExport($this->context->parameters['wwwDir'].'/../temp/cache');
		  


	    return $grid;
	}

                
	public function renderDefault()
	{
            $this->template->observations = $this->database->table('observations')
                    ->where('user_id', $this->user->id) // vytáhne userovy pozorovani z tabulky observations
                    ->order('created_at DESC');
            $this->template->personal = $this->database->table('users')
                    ->where('id', $this->user->id);
	    $this->template->locations = $this->database->table('location')
		    ->where('user_id',$this->user->id)
		    ->order('name');

	    
	}
        
        
}
