<?php
/**
 * Class for default display
 *
 * @see Zend_Controller_Action
 * @package Default_Controllers
 */
class IndexController extends Zend_Controller_Action
{
	protected $locale = null;

    public function init()
    {
		$this->locale = Zend_Registry::get('Zend_Locale');
		$this->translator = Zend_Registry::get('Zend_Translate');
    }

    public function indexAction()
    {
    	if ($this->locale == 'en_GB' || $this->locale == 'en'){
    		$this->view->title = 'Welcome';
    		$this->_helper->viewRenderer('index');
    	}
    	else{
    		$this->view->title = 'Bienvenue';
    		$this->_helper->viewRenderer('index-fr');
    	}
    }
    
    public function aboutUsAction()
    {
    	if ($this->locale == 'en_GB' || $this->locale == 'en'){
    		$this->view->title = 'About Us';
    		$this->_helper->viewRenderer('about-us');
    	}
    	else{
    		$this->view->title = 'A Propos';
    		$this->_helper->viewRenderer('about-us-fr');
    	}
    }
    
    public function maintenanceAction()
    {
    	$this->view->title = $this->translator->translate('title-sys-maintenance');
    }
}