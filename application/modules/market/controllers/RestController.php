<?php
/**
 * The REST controller for implementing REST
 *
 * @see Zend_Rest_Controller
 * @package Market_Controllers
 */
class Market_RestController extends Zend_Rest_Controller
{
	/**
	 * @see Zend_Controller_Action::init()
	 */
	public function init()
	{
		$this->_helper->viewRenderer->setNoRender(true);
	}
	
	/**
	 * Handle GET and return a list of resources
	 *
	 * @see Zend_Rest_Controller::indexAction
	 */
	public function indexAction()
	{
		$this->getResponse()->setBody('Hello World');
    	$this->getResponse()->setHttpResponseCode(200);
	}
	
	/**
	 * Handle GET and return a specific resource item
	 *
	 * @see Zend_Rest_Controller::getAction
	 */
	public function getAction()
	{
		if (!$id = $this->_getParam('id', false)) {
    		// report error, redirect, etc.
			}
	}
	
	/**
	 * Handle POST requests to create a new resource item
	 *
	 * @see Zend_Rest_Controller::postAction
	 */
	public function postAction()
	{
		$data = $this->params();
	}
	
	/**
	 * Handle PUT requests to update a specific resource item
	 *
	 * @see Zend_Rest_Controller::putAction
	 */
	public function putAction()
	{
		$data = $this->params();
		if (!$id = $this->_getParam('id', false)) {
    		// report error, redirect, etc.
		}
	}
	
	/**
	 * Handle DELETE requests to delete a specific item
	 *
	 * @see Zend_Rest_Controller::deleteAction
	 */
	public function deleteAction()
	{
		if (!$id = $this->_getParam('id', false)) {
    		// report error, redirect, etc.
		}
	}
}