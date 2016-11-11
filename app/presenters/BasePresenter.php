<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

  /**
   * @var Nette\Database\Context
   */
  private $database;

  /**
   * @param Nette\Database\Context
   */
  public function injectDatabase(Nette\Database\Context $context)
  {
    $this->database = $context;
  }
  
    /**
     * 
     * @author Anna Moudrá <anna.moudra@gmail.com>
     * @description Načítá data z databáze pro tabulku statistik v sidebaru.
     * @memberOf BasePresenter
     * 
     */
   protected function beforeRender(){
	   parent::beforeRender();
	   $this->template->location = $this->database->table('location')->count('*');
	   $this->template->sqm = $this->database->table('sqm')->count('*');
	   $this->template->obscount = $this->database->table('observations')->count('*');
     $this->template->photos = $this->database->table('photos')->count('*'); 	
    }

}





