<?php
/**
 * 
 * Class with methods for ajax
 * @author nbgyan
 *
 */
class AsyncController extends Zend_Controller_Action
{
	public function init()
	{
		$this->_helper->viewRenderer->setNoRender();
        $this->_helper->getHelper('layout')->disableLayout();
	}
	
	/**
	 * Function for use with ajax form validation
	 */
	public function validateFormAction()
	{
		$f = new Voices_Form_NewMarketInfo();
		$f->isValid($this->_getAllParams());
		$this->_helper->json($f->getMessages());
	}
	
	public function submitFormDataAction()
	{
		
	}
	
	public function preDispatch()
	{
		
	}
}