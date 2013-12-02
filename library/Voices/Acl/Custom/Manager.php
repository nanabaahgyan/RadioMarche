<?php
/**
 * Class for managing user authentication
 *
 * @see Zend_Controller_Plugin_Abstract
 * @package Application
 */
class Voices_Acl_Custom_Manager extends Zend_Controller_Plugin_Abstract
{
	/**
	 * Default user role if not logged in (or invalid role found)
	 *
	 * @var string
	 * @access private
	 */
	private $_defaultRole = 'guest';
	
	/**
	 * The action to dispatch if a user doesn't have suffucient privileges
	 *
	 * @var array
	 * @access private
	 */
	private $_authRoute = array(
								 'module' => 'account',
								 'controller' => 'account',
								 'action' => 'login'
								);

	/**
	 * Defines and sets up access control list
	 * for controllers and actions using roles
	 *
	 * @param Zend_Auth $auth
	 */
	public function __construct(Zend_Auth $auth)
	{
		$this->auth = $auth;
		$this->acl = new Zend_Acl();
		
		// add the different user roles
		$this->acl->addRole(new Zend_Acl_Role($this->_defaultRole));
		$this->acl->addRole(new Zend_Acl_Role('ngo'));
		$this->acl->addRole(new Zend_Acl_Role('rad'));
		$this->acl->addRole(new Zend_Acl_Role('admin'), array('ngo', 'rad'));
		
		// add the resources to have control over
		$this->acl->addResource(new Zend_Acl_Resource('index'));
		$this->acl->addResource(new Zend_Acl_Resource('error'));
		$this->acl->addResource(new Zend_Acl_Resource('market-info'));
		$this->acl->addResource(new Zend_Acl_Resource('ngo'));
		$this->acl->addResource(new Zend_Acl_Resource('radio'));
		$this->acl->addResource(new Zend_Acl_Resource('account'));
		$this->acl->addResource(new Zend_Acl_Resource('admin'));
		
		// allow access to everything for all users by default
		// except for the account managament, ngo, radio and administration area
    	$this->acl->allow();
		$this->acl->deny(null, 'market-info');
		$this->acl->deny(null, 'ngo');
    	$this->acl->deny(null, 'radio');
		$this->acl->deny(null, 'account');
		$this->acl->deny(null, 'admin');

		// allow guest access to index controller
		$this->acl->allow($this->_defaultRole, array('index', 'error'));
		$this->acl->allow($this->_defaultRole, 'account', array('login', 'register', 'success'));
	
		// allow ngo members access to ngo resource
		$this->acl->allow('ngo', array('index','error', 'account', 'ngo', 'market-info'), null);
				
		// allow radio members access to radio resource
		$this->acl->allow('rad', array('index','error', 'account','radio', 'market-info'), null);
		
		// allow administrators access to the admin area and all others
		$this->acl->allow('admin', array('admin'));
		
		// set Zend_Acl in registry for Zend_Navigation
		Zend_Registry::set('Zend_Acl', $this->acl);
		
		/******************************for testing**********************************/
	//	$isAllowed =  $this->acl->isAllowed('admin', 'admin') ? 'allowed' : 'denied';
	}
	
	/**
	 * Before an action is dispatched, check if the current user has
	 * suffucient privileges. If not, dispatch the default action instead
	 *
	 * @see Zend_Controller_Request_Abstract
	 * @param Zend_Controller_Request_Abstract $request
	 */
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		// check if a user is logged in and has a valid role,
		// otherwise, assign them the default role (guest)
		if ($this->auth->hasIdentity())
			$role = $this->auth->getIdentity()->user_type;
		else
			$role = $this->_defaultRole;
			
		if (!$this->acl->hasRole($role))
			$role = $this->_defaultRole;
			
		// the ACL resource is the requested controller name
		$resource = $request->controller;
		
		// the ACL privilege is the requested action name
		$privilege = $request->action;
		
		// if we haven't explicitly added the resource, check
		// the default global permissions
		if (!$this->acl->has($resource))
			$resource = null;
			
		// access denied - reroute the request to the default action handler
		if (!$this->acl->isAllowed($role, $resource, $privilege)){
			$request->setModuleName($this->_authRoute['module']);
			$request->setControllerName($this->_authRoute['controller']);
			$request->setActionName($this->_authRoute['action']);
		}
	}
}