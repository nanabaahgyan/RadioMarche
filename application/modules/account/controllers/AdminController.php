<?php
/**
 * Class for overall system administration
 *
 * @see Zend_Controller_Action
 * @package Account_Controllers
 */
class Account_AdminController extends Zend_Controller_Action
{
	protected $db = null;
	public $user = null;
	protected $translator;
	
	public function init(){
		// check URL for /admin pattern and set admin layout
		$url = $this->getRequest()->getRequestUri();
		$this->_helper->layout->setLayout('admin');
		
		$db = Zend_Registry::get('db');
		$this->db = $db;
		
		$this->translator = Zend_Registry::get('Zend_Translate');
	}
	
	public function indexAction()
	{
	//	$form = new Voices_Form_AdminAddUser();
	//	$this->view->form = $form;
		$this->view->title = 'Welcome Admin';
		
		$usernames = $this->getNewUsersFromDB();
			
		list($names, $idResults) = $usernames;
			
		$this->view->names = $names;
		$this->view->ids = $idResults;
	}
	
	/**
	 * This function is used to register a new
	 * user by admin
	 */
	public function registerNewUserAction()
	{
		if ($this->getRequest()->isPost()){
	//		if ($form->isValid($this->getRequest()->getPost())){
				$values = $this->getRequest()->getPost();
				
	/*			$this->user->username = $values['username'];
				$this->user->user_type = $values['usertypes'];
				$this->user->profile->first_name = $values['firstname'];
				$this->user->profile->last_name = $values['lastname'];
				$this->user->profile->email = $values['email'];
				$this->user->profile->contact_phone = $values['phone'];
				$this->user->profile->contact_address = $values['address'];

				if ($this->user->userNameExists($values['username'])){
					$form->addError('Username is already exists');
		  			$this->view->error = $form->getErrorMessages();
				}
				elseif ($values['usertypes'] == 'rad' && strlen($values['radio']) == 0){
					$form->addError('Please give the name of the radio station');
					$this->view->error = $form->getErrorMessages();
				}
				else{
					// save the user info
					$this->user->profile->radiostation = $values['radio'];
					$this->user->save();
					$this->_helper->getHelper('FlashMessenger')
				                  ->addMessage('Your registration successful.');
					$this->_redirect('/account/success');
				} */
		//	}
		}
	}

	/**
	 * This function is used to validate an entered offering
	 */
	public function validateNewOfferingAction(){
		if ($this->getRequest()->isPost())
		{
			$values = $this->getRequest()->getPost();
			
			if (count($values['ids']) > 0)
			{
				foreach ($values['ids'] as $key => $value){
					$q = Doctrine_Query::create()
									->update('Voices_Model_Products p')
									->set('p.valid', '?', 'yes')
									->where('p.prod_id = ?', $value);
					$q->execute();
				}
				
				$this->_helper->getHelper('FlashMessenger')
				 	 ->addMessage(count($values['ids']) . " new offerings successfully validated.");
				$this->_redirect('/admin/success');
			}
			else{
				$this->view->error = 'Please make a selection';
				$this->_redirect('/admin/home');
			}
		}
	}
	
	/**
	 * Function to create fulltext indices search on
	 * application
	 */
	public function createFulltextIndexAction()
	{
		// no view
		$this->_helper->viewRenderer->setNoRender(TRUE);
		
		// create and execute query
		$q = Doctrine_Query::create()
								->from('Voices_Model_Products p')
								->leftJoin('p.Voices_Model_Users u')
								->leftJoin('p.Voices_Model_Contacts c');
			//					->leftJoin('u.Voices_Model_Uploads up')
			//					->where('p.valid=?', 'yes')
			//					->addWhere('up.delivered=?', 'yes');
		$result = $q->fetchArray();
		
		// set encoding
	//	Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
		Zend_Search_Lucene_Analysis_Analyzer::setDefault(
    												new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8());
		// get index directory from ini file
		$config = $this->getInvokeArg('bootstrap')->getOption('indexes');
		$index = Zend_Search_Lucene::create($config['indexPath']);
		
		foreach ($result as $r) {
			// save result to index
			$index->addDocument(new Voices_Search_Market($r));
		}
		
	//  set number of documents in index
	//	$count = $index->count();
	
		$this->view->priorityMessenger($this->translator
											->translate('index-created-success'), 'info');
	}
	
	/**
	 * Function to synchronise platforms forms. It basically
	 * finds out what com_ids are on the old platform the writes
	 * the json format to a file.
	 */
	public function synchronizeCommuniquePlatformAction()
	{
		// query voice platform for all communique
		$comGen = new Voices_Model_Data_Aggregator();
		
		$hostOnline = $comGen->pingVoicePlatform();
		
		if ($hostOnline){
			$prevComs = $comGen->communiquePlatformQuery();
		
			// initialise arrays
			$prodComArray = array(); // for storing product info from db
			$prodComArrayJson = array(); // for storing json-encoded com data
			
			try{
				foreach ($prevComs as $value){
					// get all information on communique associated with
					// previous communique(s)
					$q = Doctrine_Query::create()
									->from('Voices_Model_MarketStore m')
									->where('m.com_id=?', $value)
									->orderBy('m.com_id'); //order by com_id
					$results = $q->fetchArray();
				
					// there is a match of communique on the platform
					// and in store so form a new json for REST interface
					if (!empty($results)){
						foreach($results as $scdValue)
						{
							// build the product array
							$prodArr = array('id' => $scdValue['prod_com_id'],
										 'price' => round($scdValue['price']),
										 'quantity' => round($scdValue['quantity']),
									     'contact' => $scdValue['con_com_id']);
							array_push($prodComArray, $prodArr);
						}
					
						// build format for voice platform rest interface
						$comArray = array('comm_id'=>$results[0]['com_id'],
									  	  'content'=>array('date'=> date('d-m-Y', strtotime($results[0]['ts_date_delivered'])),
									  				   'number' => $results[0]['com_number'],
									  				   'product' => $prodComArray)
									  );
						// encode format and write to file
						$jsonEncoded = Zend_Json::encode($comArray);
						// push json content to array
						array_push($prodComArrayJson, $jsonEncoded);
					
						// empty the array for a fresh entry
						$prodComArray = array();
						}
					}
				
				// date for new entry
				$timestamp = '******   ' . 'Timestamp: ' . date('Y-m-d H:i:s') .  PHP_EOL;
				$timestamp .= '******   ' .
							  'Communique Platform URL: ' . Zend_Registry::get('config')
																				->voice
																				->platform
																				->base
																				->url . PHP_EOL;
	
				// write to database, register communique creation notice in log table
				$logger = Zend_Registry::get('logger');
				
				$message = 'Communique Platform Sync Performed';
				$aggregator = new Voices_Model_Data_Aggregator();
				
				$daysPast = $aggregator->getLogTimeDifference($message);
			
				if (count($daysPast) && $daysPast > 0){
					$logger->setEventItem('stacktrace', $timestamp);
					$logger->setEventItem('request', 'JSON Array: ' . Zend_Debug::dump($prodComArrayJson));
					$logger->log($message, Zend_Log::INFO);
					
					// give feedback
					$this->view->priorityMessenger('A New Sync data written to log table after ' . $daysPast . 'day(s)', 'info');
				}
				elseif ($daysPast === NULL) { // no entry at all.
					$logger->setEventItem('stacktrace', $timestamp);
					$logger->setEventItem('request', 'JSON Array: ' . Zend_Debug::dump($prodComArrayJson));
					$logger->log($message, Zend_Log::INFO);
					
					// give feedback
					$this->view->priorityMessenger('Sync data written to log table', 'info');
				}
				else{
					// give feedback
					$this->view->priorityMessenger('Available sync data less than a day old!', 'info');
				}
				
				// redirect to home page
				$this->_redirect('/admin/home');
			}
			catch (Exception $e){
				// register communique creation notice in log
				$logger = Zend_Registry::get('logger');
			
				$logger->setEventItem('stacktrace', $e->getTraceAsString());
				$logger->setEventItem('request', 'Message: ' . $e->getMessage() .
										         'Trace ' . $e->getTrace() .
										         ' on ' . date('Y-m-d H:i:s'));
				$logger->log('Communique Platform Sync Error', Zend_Log::CRIT);
			}
		}else{
			// give feedback
			$this->view->priorityMessenger('Voice platform is offline', 'error');
			
			// redirect to home page
			$this->_redirect('/admin/home');
		}
	}
	
	/**
	 * function to add a newly registered user to system
	 */
	public function addNewUserAction()
	{
		if ($this->getRequest()->isPost()){
			$add = $this->getRequest()->getPost('add');
			$usertype = $this->getRequest()->getPost('usertypes');
			if ($add == 'Yes' && $usertype !== 'Please Select'){
				$id = $this->getRequest()->getPost('id');

				$q = Doctrine_Query::create()
										->update('Voices_Model_Users u')
										->set('u.user_type', '?', $usertype)
										->addWhere('u.user_id = ?', $id);
				$q->execute();
				
				
				$this->_helper->getHelper('FlashMessenger')
				 	 	  	  ->addMessage("User successfully registered");
				$this->_redirect('/admin/success');
			}
			else {
				$this->_redirect('/admin/home');
			}
		}
		else {
			$id = $this->_getParam('id', 0);
			if ($id > 0){
				$select = $this->db->select()
							   ->from(array('up' => 'users_profile'), array('user_id','up.profile_value'))
							   ->where('up.user_id = ?', $id)
							   ->where('up.profile_key = ?', 'first_name');
						       
				//			       $sql = $select->__toString();
				$results = $select->query()->fetchAll();
			
				$this->view->results = $results;
			}
			else {
				$this->_helper->getHelper('FlashMessenger')
				 	 	  	  ->addMessage("An error occurred");
				$this->view->title = "Error";
				$this->_redirect('/admin/success');
			}
		}
	}
	
	/**
	 * function to prevent a newly registered user
	 * from accessing the system
	 */
	public function deleteUserAction()
	{
		if ($this->getRequest()->isPost()){
			$del = $this->getRequest()->getPost('del');
			if ($del == 'Yes'){
				$id = $this->getRequest()->getPost('id');
				
				$q = Doctrine_Query::create()
								->delete('Voices_Model_UsersProfile up')
								->where('up.user_id = ?', $id);
				$q->execute();
				
				$q = Doctrine_Query::create()
								->delete('Voices_Model_Users u')
								->where('u.user_id = ?', $id);
				$q->execute();
				
				
				
				$this->_helper->getHelper('FlashMessenger')
				 	 		  ->addMessage("User successfully deleted from system");
				$this->view->title = "Success";
				$this->_redirect('/admin/success');
			}
			else {
				$this->_redirect('/admin/home');
			}
		}
		else {
			$id = $this->_getParam('id', 0);
			if ($id > 0){
				$select = $this->db->select()
							   ->from(array('up' => 'users_profile'), array('user_id','up.profile_value'))
							   ->where('up.user_id = ?', $id)
							   ->where('up.profile_key = ?', 'first_name');
						       
				// $sql = $select->__toString();
				$results = $select->query()->fetchAll();
			
				$this->view->results = $results;
			}
			else {
				$this->_helper->getHelper('FlashMessenger')
				 	 	  	  ->addMessage("An error occurred");
				$this->view->title = "Error";
				$this->_redirect('/admin/success');
			}
		}
	}
	
	/**
	 * Function to create communique generation by users
	 *
	 * @uses Voices_Model_Data_Aggregator::makeComExcelStats
	 */
	public function createCommuniqueStatsAction()
	{
		$q = Doctrine_Query::create()
							 ->from('Voices_Model_Uploads u')
							 ->addWhere('u.delivered=?', 'yes')
							 ->groupBy('u.com_id');
		$results = $q->fetchArray();
		
		$comStatData = array();
		
		$aggregator = new Voices_Model_Data_Aggregator();
		foreach ($results as $key=>$value){
			$prodInfo = $aggregator->getAllProductInfoFromCommunique($value['upload_id']);
			list($prodData, $uploadData,) =  $prodInfo;
			array_push($comStatData, $prodData);
		}
	}
	
	public function successAction()
	{
		if ($this->_helper->getHelper('FlashMessenger')->getMessages())
		{
			$this->view->messages = $this->_helper->getHelper('FlashMessenger')
											   	  ->getMessages();
		}
		else {
			$this->_redirect('/admin/home');
		}
	}
	
	private function getNewUsersFromDB()
	{
		$select = $this->db->select()
						   ->from(array('u' => 'users'),'u.user_id')
						   ->where('u.user_type = ?', 'guest');
		$idResults = $select->query()->fetchAll();
		
		$firstnames = array();
		$lastnames  = array();
		
		foreach ($idResults as $idKey => $idValue){
			$select = $this->db->select()
							   ->from(array('up' => 'users_profile'), array('up.profile_value'))
							   ->where('up.user_id = ?', $idValue['user_id'])
							   ->where('up.profile_key = ?', 'first_name');
						       
				//			       $sql = $select->__toString();
			$fresults = $select->query()->fetchAll();
			
			array_push($firstnames, ucwords($fresults[0]['profile_value']));
			
			$select = $this->db->select()
							   ->from(array('up' => 'users_profile'), array('up.profile_value'))
							   ->where('up.user_id = ?', $idValue['user_id'])
							   ->where('up.profile_key = ?', 'last_name');
						       
				$lresults = $select->query()->fetchAll();
						       
				array_push($lastnames, ucwords($lresults[0]['profile_value']));
			}
		
			$names = array();
			foreach ($firstnames as $fKey => $fValue){
				foreach ($lastnames as $lKey => $lValue){
					if ($fKey == $lKey){
						$full_name = $fValue . ' ' . $lValue;
						array_push($names, $full_name);
					}
				}
			}
			return array($names, $idResults);
	}
}