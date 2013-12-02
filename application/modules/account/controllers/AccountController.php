<?php
/**
 * Class for user account management
 *
 * @see Zend_Controller_Action
 * @package Account_Controllers
 */
class Account_AccountController extends Zend_Controller_Action
{
	/**
	 * Instance of Zend_Db object
	 * @var object
	 */
	protected $db = null;
	
	/**
	 * Instance of Zend_Auth object
	 * @var object
	 */
	protected $user = null;
	
	/**
	 * Instance of Zend_Translate object
	 * @var object
	 */
	protected $translator = null;
		
	/**
	 * @see Zend_Controller_Action::init()
	 */
	public function init()
	{
		// no login if system is under maintenance
		$mode = Zend_Registry::get('config')->system->maintenance->mode;
		if (Zend_Registry::get('config')->system->maintenance->mode){
			$this->_redirect($this->view->baseUrl().'/maintenance');
		}
		
		$db = Zend_Registry::get('db');
		
		$this->db = $db;
		$this->user = new Voices_Model_DatabaseObject_User($this->db);
		
		$this->translator = Zend_Registry::get('Zend_Translate');
	}
	
	/**
	 * Function for signing in a user
	 */
	public function loginAction()
	{
		// if a user's already logged in, send them
		// to their account home page
		$auth = Zend_Auth::getInstance();
		//if ($auth->hasIdentity())
		//	$this->_redirect('/market-info/index');/* review this */
	
		// create login form and render for output
		$form = new Voices_Form_Login();
		$this->view->title = 'Login';
		
					
		$request = $this->getRequest();
		
		// determine the page the user was originally
		// trying to request
		$redirect = $request->getPost('redirect');
		if (strlen($redirect) == 0)
			$redirect = $request->getServer('REQUEST_URI');
		if (strlen($redirect) == 0)
			$redirect = '/account/login';
		
		// process login if request is POST
		if ($request->isPost()){
			if ($form->isValid($this->getRequest()->getPost())){
				$values = $form->getValues();
								
				$adapter = new Zend_Auth_Adapter_DbTable($this->db,
														 'users',
														 'username',
														 'password',
														 'md5(?)');
				$adapter->setIdentity($values['username']);
				$adapter->setCredential($values['password']);
				
				// try and authenticate user
				$result = $auth->authenticate($adapter);
				
				if ($result->isValid()){
					$user = new Voices_Model_DatabaseObject_User($this->db);
					$user->load($adapter->getResultRowObject()->user_id);
					
					// record login attempt
					$user->loginSuccess();
					
					// does user want to be remebered?
        			$seconds  = 60 * 60 * 24 * 7; // 7 days
 
        			if ($values['remember'] == 1) {
           				 Zend_Session::RememberMe($seconds);
        			}
        			else {
           				 Zend_Session::ForgetMe();
        			}
					
					// create identity data and write it to session
					$identity = $user->createAuthIdentity();
					$auth->getStorage()->write($identity);
					
					//$this->view->authenticated = true;
					
					//$username = $auth->getIdentity()->username;
					
					// direct authenticated users to appropriate pages
					if ($identity->user_type == 'guest'){
						$auth->getStorage()->clear();
					//	$form->getElement('username')
					//         ->addError($this->translator->translate('login-unauth-error'));
					    $this->view->priorityMessenger($this->translator
					    								    ->translate('login-unauth-error'), 'error');
					}
					elseif ($identity->user_type == 'ngo'){
						$this->_redirect('/ngo/home');
					}
					elseif ($identity->user_type == 'rad'){
						$this->_redirect('/radio/home');
					}
					elseif ($identity->user_type == 'admin'){
						$this->_redirect('/admin/home');
					}
					else{
						// send user to page they originally requested
						$this->_redirect($redirect);
					}
				}
				else {
					Voices_Model_DatabaseObject_User::LoginFailure($values['username'],
																   $result->getCode());
				//	$form->getElement('username')
				//	     ->addError($this->translator->translate('login-error'));
					$this->view->priorityMessenger($this->translator
														->translate('login-error'), 'error');
				}
			}
		}
		$this->view->form = $form;
	}
	
	/**
	 * Function for logging out a user
	 */
	public function logoutAction()
	{
		Zend_Auth::getInstance()->clearIdentity();
		//Zend_Session::destroy();
		
		$this->_redirect('/account/login');
	}
	
	/**
	 * Function for registering a new user
	 */
	public function registerAction()
	{// no login if system is under maintenance
		if (Zend_Registry::get('config')->system->maintenance->status){
			$this->_redirect('/maintenance');
		}
		$form = new Voices_Form_UserRegister();
		$this->view->title = 'Register';
		
		if ($this->getRequest()->isPost())
		{
			if ($form->isValid($this->getRequest()->getPost()))
			{
				$values = $form->getValues();
				$this->user->user_type = 'guest'; // version for ngo only!
				$this->user->username = $values['username'];
				$this->user->password = $values['password'];
				$this->user->profile->first_name = $values['firstname'];
				$this->user->profile->last_name = $values['lastname'];
				$this->user->profile->email = $values['email'];
				$this->user->profile->contact_phone = $values['phone'];
				$this->user->profile->contact_address = $values['address'];
																		
				if ($this->user->userNameExists($values['username'])){
					$form->getElement('username')
						 ->addError($this->translator->translate('username-exists'));
					$this->view->error = $form->getErrorMessages();
				}
				else{
					// save the user info if no errors found
					$this->user->save();
					
					// log registration
					$message = sprintf('Successful registration from new user %s',
										$_SERVER['REMOTE_ADDR'],
										$values['firstname'].' '. $values['lastname']);
		
					$logger = Zend_Registry::get('logger');
		
					$logger->setEventItem('stacktrace', ' ');
					$logger->setEventItem('request', 'Registration:' . $message);
					$logger->log('User Registration', Zend_Log::INFO);
					
					// send mail to notify admin
					$mail = new Zend_Mail();
					$mail->setBodyText('New user with name' . $values['firstname'].' '. $values['lastname'].
									   ' on system requires validation');
					$mail->setFrom(Zend_Registry::get('config')->email->from->email,
					   			   Zend_Registry::get('config')->email->from->name);
					$mail->addTo(Zend_Registry::get('config')->system->admin->email,
								 Zend_Registry::get('config')->system->admin->name);
					$mail->setSubject('New User Registration on VOICES');
				//	$mail->send();
					
					$this->_helper->getHelper('FlashMessenger')
							      ->addMessage($this->translator->translate('register-success'));
					$this->_redirect('/account/success');
				}
			}
			$this->view->form = $form;
		}
		else{
			$this->view->form = $form;
		}
	}
	
	/**
	 * Function for changing a forgotten password
	 */
	public function fetchPasswordAction()
	{
		// nothing for now
	}
	
	/**
	 * Helper function for giving feedback to a user
	 */
	public function successAction()
	{
		if ($this->_helper->getHelper('FlashMessenger')->getMessages())
		{
			$this->view->messages = $this->_helper ->getHelper('FlashMessenger')
											   	   ->getMessages();
			$this->view->title = $this->translator->translate('header-success');
		}
		else {
			$this->_redirect('/');
		}
	}
}