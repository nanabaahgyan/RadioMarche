<?php
/**
 * Application bootstrap file. Initializes the entire application
 *
 * @see Zend_Application_Bootstrap_Bootstrap
 * @package Application
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
 	/**
 	 * Function to get config (application.ini file) for
 	 * use in Bootstrap
 	 * NB: this function must execute before all the other
 	 *     functions in the bootstrap.
 	 *
 	 * @return $config
 	 */
	protected function _initConfig()
    {
        $config = new Zend_Config($this->getOptions());
        Zend_Registry::set('config', $config);
        return $config;	// make it available in bootstrap
    }
    
    /**
     * Function to initialize REST helper action and contexts
     * (json/xml)
     */
    protected function _initActionHelpers()
    {
    	$params = new Voices_Controller_Helper_Params();
    	Zend_Controller_Action_HelperBroker::addHelper($params);
    	
    	$contexts = new Voices_Controller_Helper_RestContexts();
    	Zend_Controller_Action_HelperBroker::addHelper($contexts);
    }
      
	/**
	 * Function to bootstrap Doctrine Object Relational Mapper (ORM)
	 * for use with Zend Framework
	 *
	 * @return $conn|a connection to the database
	 */
	protected function _initDoctrine()
	{
		require_once 'Doctrine/Doctrine.php';
		$this->getApplication()
			 ->getAutoloader()
			 ->pushAutoloader(array('Doctrine', 'autoload'), 'Doctrine');
			 
		$manager = Doctrine_Manager::getInstance();
		// enable lazy loading
		$manager->setAttribute(Doctrine::ATTR_MODEL_LOADING, Doctrine::MODEL_LOADING_CONSERVATIVE);
		
		$dbParams = $this->config->resources->db->params;
		
		// convert database parameters to DSN format:
		// dsn = mysql://username:password@host/dbname
		$dsn = "mysql://{$dbParams->username}:{$dbParams->password}@{$dbParams->host}/{$dbParams->dbname}";
		
		try {
			$conn = Doctrine_Manager::connection($dsn, 'doctrine');
			$conn->setCharset('utf8'); // set charset to UTF-8
			return $conn;
		} catch (Exception $e) {
			'Doctrine connection error: ' . $e;
		}
	}
	
	/**
	 * Function to bootstrap authentication
	 * and authorization into application
	 * NB: this function must execute before _initNavigation because of Zend_Acl
	 *     in navigation
	 */
	protected function _initAuth()
	{
		// setup application authentication
		$auth = Zend_Auth::getInstance();
//		if (!$auth->hasIdentity()){
//			$auth->setStorage(new Zend_Auth_Storage_Session());
//			$auth->getStorage()->read()->user_type = 'guest';
//		}
		// register Acl plug-in into Front Controller.
		$front = Zend_Controller_Front::getInstance();
		
		// register acl with front controller
		$front->registerPlugin(new Voices_Acl_Custom_Manager($auth));
		
		// register REST acceptance handling with front
		/**
		 * @todo enable this for REST acceptance handling
		 */
	//	$front->registerPlugin(new Market_Controller_Plugin_AcceptHandler());
	}
	
	/**
	 * Function to bootstrap Zend_Db connection for into application.
	 * Necessitated by the fact that Doctrine currently does not support
	 * database schema with composite primary keys
	 */
	protected function _initDB()
	{
		$dbParams = $this->config->resources->db->params;
		$dbAdapter = $this->config->resources->db->adapter;
		
		// make db connections here
		// make separate because of the desire to separate
		// Zend_Db from others like Doctrine. Doctrine cannot handle
		// composite keys and relationships
		$params = array('host' => $dbParams->host,
						'username' => $dbParams->username,
						'password' => $dbParams->password,
						'dbname' => $dbParams->dbname);
		try {
			$db = Zend_Db::factory($dbAdapter, $params);
			$db->getConnection();
		} catch (Zend_Db_Adapter_Exception $e) {
			'Perhaps a failed login credential, or perhaps the RDBMS is not running' . $e;
		} catch (Zend_Exception $e) {
			'Perhaps db factory() failed to load the specified Adapter class' . $e;
		}
		// persist db info in registry
		Zend_Registry::set('db', $db);
	}
	
	/**
	 * Function for getting the various routes and paths
	 * in routes.ini file of the application
	 */
	protected function _initRoutes(){
		// use config for routes for possible caching as they
		// get large as in the following example:
		// http://stackoverflow.com/questions/794126/shorten-zend-framework-route-definitions
		
		// load up routes.ini file
		$routes = new Zend_Config_Ini(APPLICATION_PATH . '/configs/routes.ini', 'routes');
//		$routes = new Zend_Config_Xml(APPLICATION_PATH . '/configs/routes.xml');
		
		$front = Zend_Controller_Front::getInstance();
		$router = $front->getRouter();
		$router->addConfig($routes, 'routes');
		
		// route for REST requests
		$restRoute = new Zend_Rest_Route($front, array(), array('market' => array('rest')));
		$router->addRoute('rest', $restRoute);
	}

	/**
	 * Function to set up layout.
	 * Note: Should be _initLayouts to prevent circular dependency.
	 */
	protected function _initLayouts()
	{
		$view = new Zend_View();
		// for jquery support
		$view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");
		$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
		$viewRenderer->setView($view);
		Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
		
		$this->bootstrap('layout');
		$layout = $this->getResource('layout');
		$view = $layout->getView();
		
		// for custom flashmessenger
		$view->addHelperPath('Voices/View/Helper','Voices_View_Helper');
		
		$view->doctype('XHTML1_STRICT');
		$view->setEncoding('ISO-8859-1');
		$view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
		// add favicon
		$view->headLink()->headLink( array( 'rel' => 'favicon',
											'href' => $view->baseUrl( 'favicon.ico' ),
											'type' => 'image/x-icon' ));
		
		// add JQuery support to view
		// using local install
		$view->jQuery()->setVersion('1.7.1')
					   ->setLocalPath($view->baseUrl('/js/jquery-1.7.1.min.js'))
			 		   ->setUiLocalPath($view->baseUrl('/js/jquery-ui-1.8.17.custom.min.js'))
			 		   ->addStylesheet($view->baseUrl('/css/jquery/ui-smoothness/jquery-ui-1.8.17.custom.css'))
			 		   ->Enable()
					   ->uiEnable();
		/**
		// using google CDN
		$view->jQuery()
			 ->setVersion('1') //jQuery version, automatically 1 = latest
		     ->setUiVersion('1') //jQuery UI version, automatically 1 = latest
		     //add the css
		     ->addStylesheet('https://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/smoothness/jquery-ui.css')
			 ->Enable()
			 ->uiEnable();
		*/
	}
	
	/**
	 * Function to set up application logging functionality
	 */
	protected function _initLogger()
	{
		// initialize logging engine
		$logger = new Zend_Log();
//		$dbLogger = new Zend_Log();
				
		// attach writer to logging engine
	//	$fWriter = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/app-logs.log');
		
	//	$logger->addWriter($fWriter);
		
		// attach db log writer to logging engine
		// add Doctrine Log Writer
        $columnMap = array(
        	'message' => 'log_message',
        	'priorityName' => 'log_level',
        	'timestamp' => 'log_time',
        	'stacktrace' => 'stack',
        	'request' => 'request',
        );
        $dbWriter = new Voices_Log_Writer_Doctrine('Voices_Model_Logs', $columnMap);
        $logger->addWriter($dbWriter);
		
		// attach formatter to writer
//		$format = '%timestamp%: %priorityName%: %request%: %host%: %message%' . PHP_EOL;
		
//		$formatter = new Zend_Log_Formatter_Simple($format); // simple text file
//		$fWriter->setFormatter($formatter);
		
		// add client IP and request URL to log message
	//	$logger->setEventItem('request', $->getRequest()->getRequestUri());
	//	$logger->setEventItem('host', $this->getRequest()->getClientIp());
		
		// persist logger in registry
		Zend_Registry::set('logger', $logger);
	}
	
	/**
	 * Function to setup l18n
	 */
	protected function _initLocale()
	{
		$session = new Zend_Session_Namespace('voices.l10n');
		if ($session->locale){
			$locale = new Zend_Locale($session->locale);
		}
		
		if (!isset($locale)) {// === null){
			try {
				$locale = new Zend_Locale('browser');
			} catch (Zend_Locale_Exception $e){
				$locale = new Zend_Locale('en_GB');
			}
		}
		$registry = Zend_Registry::getInstance();
		$registry->set('Zend_Locale', $locale);
	}
	
	/**
	 * Function to setup application translation functionality
	 */
	protected function _initTranslate()
	{
		$translate = new Zend_Translate('array',
		     							APPLICATION_PATH . '/../languages',
		     							null,
		     							array('scan' => Zend_Translate::LOCALE_DIRECTORY,
		     							'disableNotices' => 1)
										);
		$registry = Zend_Registry::getInstance();
		$registry->set('Zend_Translate', $translate);
		
		// allow automatic translation for default error messages
		Zend_Validate_Abstract::setDefaultTranslator($translate);
		
	}
}