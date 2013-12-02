<?php
/**
 * Class for managing generated system errors
 *
 * @see Zend_Controller_Action
 * @package Default_Controllers
 */
class ErrorController extends Zend_Controller_Action
{
	/**
	 * Instance of Zend_Translate
	 * @var object
	 */
	protected $translator = null;
	
	/**
	 * @see Zend_Controller_Action::init()
	 */
	public function init()
	{
		// get instance of Zend_Translate in Registry
		$this->translator = Zend_Registry::get('Zend_Translate');
	}

	/**
	 * Function for handling system errors
	 */
    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        
        if (!$errors || !$errors instanceof ArrayObject) {
            $this->view->message = 'You have reached the error page';
            return;
        }
        
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $priority = Zend_Log::NOTICE;
                $this->view->title = $this->translator->translate('title-page-not-found');
                $this->view->message = $this->translator->translate('page-not-found');
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $priority = Zend_Log::CRIT;
                $this->view->title = $this->translator->translate('title-server-error');
                $this->view->message = $this->translator->translate('internal-server-error');
                break;
        }
        // initialize logging engine
        $logger = Zend_Registry::get('logger');
        
        // add additional data to log message - stack trace and request parameters
        $logger->setEventItem('stacktrace', $errors->exception->getTraceAsString());
        $logger->setEventItem('request', Zend_Debug::dump($errors->request->getParams()));
        
        // log exception to writer
        $logger->log($errors->exception->getMessage(), $priority);
                       
        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }
        
        $this->view->request = $errors->request;
    }
    
    /**
     * @see Zend_Controller_Action::preDispatch()
     */
	public function preDispatch()
	{
		// instance of user identity
		$auth = Zend_Auth::getInstance()->getIdentity();
		$this->view->identity = $auth;
	}
}

