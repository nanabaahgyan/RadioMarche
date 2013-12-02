<?php
/**
 * This is the main class for actions permitted by an NGO user.
 *
 * @see Zend_Controller_Action
 * @package Market_Controllers
 *
 */
class Market_NgoController extends Zend_Controller_Action
{
	/**
	 *
	 * @var object
	 */
	protected $auth = null;
	
	/**
	 *
	 * @var object
	 */
	protected $translator = null;
	
	/**
	 * @see Zend_Controller_Action::init()
	 */
	public function init()
	{
		// instance of user identity
		$this->auth = Zend_Auth::getInstance()->getIdentity();
		$this->view->identity = $this->auth;
		
		// get instance of translator
		$this->translator = Zend_Registry::get('Zend_Translate');
		
		/**
		 * context switcher for ajax calls
		 * inspired by Michelangelo van Dam
		 * http://www.dragonbe.com/2010/04/zend-framework-context-switching-for.html
		 
		$contextSwitch = $this->_helper->getHelper('contextSwitch');
        $contextSwitch->addActionContext('enter-new-offering-info', 'json')
         			//  ->addActionContext('create-new-communique', 'json')
         			  ->setAutoJsonSerialization(true)
         			  ->initContext();  */
	}
	
	/**
	 * Default action to execute for controller
	 */
	public function indexAction()
	{
//		$form = new Voices_Form_NewMarketInfo();
		
//		$this->view->text = $form;
	}
	
	/**
	 * Function to create new communique
	 */
	public function createNewCommuniqueAction()
	{
		$q = Doctrine_Query::create()
							->from('Voices_Model_Products p')
							->leftJoin('p.Voices_Model_Contacts c')
							->addWhere('p.user_id = ?', $this->auth->user_id);
		$result = $q->fetchArray();
		
		// unpublished new communique
		$q = Doctrine_Query::create()
							->from('Voices_Model_Uploads u')
							->where('u.delivered=?', 'no')
							->addWhere('u.user_id=?', $this->auth->user_id)
							->orderBy('ts_date_submitted DESC')
							->groupBy('u.com_id'); // unique entries
		$com = $q->fetchArray();
		
		// add product information
		$form = new Voices_Form_NewMarketInfo();
		
		$this->view->form = $form;
		$this->view->records = $result;
		$this->view->com = $com;
		
		// check whether voice platform online?
		$aggregator = new Voices_Model_Data_Aggregator();
		$pingHost = $aggregator->pingVoicePlatform();
		
		if ($pingHost) // voice platform is online
		{
			if ($this->getRequest()->isPost()) // user sent a request for new communique
			{
				$frmValues = $this->getRequest()->getPost();
				
				// does com_number already exist?
				$q = Doctrine_Query::create()
									-> select('u.com_number, u.user_id')
									-> from('Voices_Model_Uploads u')
									-> where('u.com_number=?', $frmValues['com_number'])
									-> addWhere('u.user_id=?', $this->auth->user_id);
				$numExist = $q->fetchArray();
				
				if (count($numExist) > 0){
					$this->view->priorityMessenger(
										$this->translator
											 ->translate('comnum-exist-error'), 'error');
					$this->_redirect('/ngo/create-new-communique');
				}
				else {
					$comValid = new Zend_Validate_Int(); // validate com_number
		
					if (isset($frmValues['prod_ids']) && count($frmValues['prod_ids']) > 0 &&
						strlen($frmValues['com_number']) > 0 &&
						$comValid->isValid($frmValues['com_number']))
					{
						$arrProd = array();
						// for storing products ids for use with printing
						$arrProdComIds = array();
						
						foreach ($frmValues['prod_ids'] as $key => $value)
						{
							$q = Doctrine_Query::create()
												->from('Voices_Model_Products p')
												->leftJoin('p.Voices_Model_Contacts c')
										//		->addWhere('p.delivered=?', 'no')
												->where('p.prod_id = ?', $value);
							$records = $q->fetchArray();
		
							array_push($arrProd, $records);
							array_push($arrProdComIds, $value);
						}
						
						$prodComArr = array();
						
						foreach ($arrProd as $fstKey => $fstValue)
						{
							foreach($fstValue as $scdKey => $scdValue)
							{
								$prodArr = array('id' => $scdValue['prod_com_id'],
												 'price' => round($scdValue['price']),
										  		 'quantity' => round($scdValue['quantity']),
									             'contact' => $scdValue['Voices_Model_Contacts']['con_com_id']);
								array_push($prodComArr, $prodArr);
							}
						}
						
						// a unique id for each set of communique
						$com_id = uniqid();
		
						$comArr = array('comm_id' => $com_id,
										'content' => array(
													   'date' => date('d-m-Y', strtotime("now")),
													   'number' => trim($frmValues['com_number']),
													   'product' => $prodComArr));
					 
					//	$aggregator = new Voices_Model_Data_Aggregator;
						$rsp = $aggregator->requestAudioFile($comArr);
						
	//					var_dump($rsp);
	//					die();
						
						list($httpCode, $status, $audioUrl) = $rsp;
		
						// respond based on respose codes
						if ($status == 'SUCCESS') // audio was generated so store info on it
						{
							foreach ($audioUrl as $key => $value){
								$upload = new Voices_Model_Uploads();
								$upload->user_id = $this->auth->user_id;
		
								$upload->com_number = trim($frmValues['com_number']);
								$upload->prod_com_ids = implode('|', $arrProdComIds);
								$upload->com_id = $com_id;
								$upload->lang_code = $value['language'];
								$upload->rad_sta_code = $value['radioid'];
								$upload->content = $value['uri'];
								$upload->ts_date_submitted = date('Y-m-d H:i:s');
		
								$upload->save();
							}
							// register communique creation notice in log
							$logger = Zend_Registry::get('logger');
		
							$message = 'Communiqué with id '. $com_id .' created for products: '.
										implode('|', $arrProdComIds);
							$logger->setEventItem('stacktrace', 'created by user with user_id: '.
													$this->auth->user_id);
							$logger->setEventItem('request', $message.' on '. date('Y-m-d H:i:s'));
							$logger->log('Communiqué created', Zend_Log::NOTICE);
							
							$this->view->priorityMessenger($this->translator
															    ->translate('offering-created'), 'info');
							$this->_redirect('/ngo/create-new-communique');
						}
					}
					else
					{
						$this->view->priorityMessenger($this->translator->translate('com-create-error'), 'error');
						$this->_redirect('/ngo/create-new-communique');
					}
				}
			}
		}
		else { // voice platform is OFFLINE!
			$this->view->priorityMessenger($this->translator
												->translate('voice-platform-offline'), 'error');
			// log as critical
			$this->comparePreviousOfflineState();
		}
	}
	
	/**
	 * Function to allow logged users to create new
	 * market info for submission. This should be
	 * shown to only NGO people
	 */
	public function enterNewOfferingInfoAction()
	{
		$form = new Voices_Form_NewMarketInfo();
		$this->view->form = $form;
		$this->view->title = $this->translator->translate('title-enter-new');
		
		if ($this->getRequest()->isPost()){// values have been posted
			if ($form->isValid($this->getRequest()->getPost())){
		
				// get form values
				$values = $form->getValues();
				
				$contact_com_id = $values['contact'];
				
				// get product info for input
				$product = new Voices_Model_Products();
				
				$q = Doctrine_Query::create()
									 ->select('c.con_com_id')
									 ->from('Voices_Model_Contacts c')
									 ->where('c.con_com_id=?', $contact_com_id);
								//	 ->addwhere('c.user_id=?', $this->auth->user_id);
				$conExists = $q->fetchArray();
				
				if (count($conExists) > 0){ // contact already exists in db ...
					
					$product_file_path = APPLICATION_PATH . '/../data/txt-files/products.txt';
					$products = $form->getTextContents($product_file_path);
				
					$prod_com_id = $values['prod'];
				
					$product->con_id = $conExists[0]['con_id']; //... and so use existing con_id
					$product->prod_com_id = $prod_com_id;
					$product->user_id = $this->auth->user_id;
					$product->prod_name = trim($products[$prod_com_id]);
				
					$metric_file_path = APPLICATION_PATH . '/../data/txt-files/metrics.txt';
					$metrics = $form->getTextContents($metric_file_path);
				
					$metric = $metrics[$prod_com_id];
				
					$product->unit_measure = trim($metrics[$prod_com_id]);
				
					$product->quantity = $values['quant'];
					
					$prod_qual_file_path = APPLICATION_PATH . '/../data/txt-files/prod-quality.txt';
					$quality = $form->getTextContents($prod_qual_file_path);
					
					$product->quality = trim($quality[$prod_com_id]);
					
					$product->price = sprintf('%F',$values['price']);
				
					$product->currency = 'CFA';
					$product->valid = 'no';
					$product->ts_date_entered =  date('Y-m-d H:i:s');
					$product->save();
					
					$this->view->priorityMessenger($this->translator
														->translate('entry-success'), 'info');
					$this->_redirect('/ngo/enter-new-offering-info');
				}
				else{// contact is not in db so..
					$contact = new Voices_Model_Contacts(); // create query for data input
				
					// get contacts file
					$contact_file_path = APPLICATION_PATH . '/../data/txt-files/contacts.txt';
					$contacts = $form->getTextContents($contact_file_path); // from file
				
					$contact_name = trim($contacts[$contact_com_id]);
					$names = explode(' ', $contact_name);
					$fName = $names[0];
					$lName = $names[1];
				
					$contact->con_com_id = $contact_com_id;
					$contact->user_id =  $this->auth->user_id;
					$contact->first_name = $fName;
					$contact->last_name = $lName;
				
					$phone_file_path = APPLICATION_PATH . '/../data/txt-files/tel-numbers.txt';
					$phone = $form->getTextContents($phone_file_path);
				
					$contact->tel_number = trim($phone[$contact_com_id]);
				
					$village_file_path = APPLICATION_PATH . '/../data/txt-files/villages.txt';
					$village = $form->getTextContents($village_file_path);
				
					$contact->village = trim($village[$contact_com_id]);
				
					$zone_file_path = APPLICATION_PATH . '/../data/txt-files/zones.txt';
					$zone = $form->getTextContents($zone_file_path);
								
					$contact->zone = trim($zone[$contact_com_id]);
				
					$contact->save();
				
					// get id of last saved contact info
					$con_id = $contact->identifier();
				
					$product_file_path = APPLICATION_PATH . '/../data/txt-files/products.txt';
					$products = $form->getTextContents($product_file_path);
				
					$prod_com_id = $values['prod'];
				
					$product->con_id = $con_id['con_id'];
					$product->prod_com_id = $prod_com_id;
					$product->user_id = $this->auth->user_id;
					$product->prod_name = trim($products[$prod_com_id]);
				
					$metric_file_path = APPLICATION_PATH . '/../data/txt-files/metrics.txt';
					$metrics = $form->getTextContents($metric_file_path);
				
					$metric = $metrics[$prod_com_id];
				
					$product->unit_measure = trim($metrics[$prod_com_id]);
				
					$product->quantity = $values['quant'];
					
					$prod_qual_file_path = APPLICATION_PATH . '/../data/txt-files/prod-quality.txt';
					$quality = $form->getTextContents($prod_qual_file_path);
					
					$product->quality = trim($quality[$prod_com_id]);
					
					$product->price = sprintf('%F',$values['price']);
				
					$product->currency = 'CFA';
					$product->valid = 'no';
					$product->ts_date_entered =  date('Y-m-d H:i:s');
					$product->save();
					
			//		$this->view->test = 'output from view';
					
					$this->view->priorityMessenger($this->translator
													    ->translate('entry-success'), 'info');
								  
					$this->_redirect('/ngo/enter-new-offering-info');
				}
			}
		}
	}
	
	/**
	 * Function to publish communique on voice platforms
	 *
	 * @uses Voices_Model_Data_Aggregator()
	 */
	public function createProductOfferingAction()
	{
		$comID = $this->_getParam('com-id', 0);
		if ($comID > 0){
			
			$productOffer = new Voices_Model_Data_Aggregator();
			$isSystemOnline = $productOffer->pingVoicePlatform();
			
			if ($isSystemOnline){
				$createOffer = $productOffer->aggregate($comID); // publish on voice platform
			
				if ($createOffer['status'] == 'SUCCESS'){
					// register communique creation notice in log
					$logger = Zend_Registry::get('logger');
		
					$message = 'Communiqué published with com_id: '. $comID;
					$logger->setEventItem('stacktrace', 'published by user with user_id: '.$this->auth->user_id);
					$logger->setEventItem('request', $message.' on '. date('Y-m-d H:i:s'));
					$logger->log('Communiqué published on voice platform', Zend_Log::NOTICE);
				
					$this->view->priorityMessenger($this->translator
												        ->translate('offering-created'), 'info');
					// redirect to show feedback to user
					$this->_redirect('/ngo/create-new-communique');
				}
				else{
					// register communique creation notice in log
					throw new Exception('Communiqué published but with errors');
				}
			}
			else{
				$this->view->priorityMessenger($this->translator
												    ->translate('system-offline'), 'error');
				// redirect to show feedback to user
				$this->_redirect('/ngo/create-new-communique');
			}
		}
	}
	
	/**
	 * Function to delete created communique.
	 */
	public function deleteCommuniqueAction()
	{
		$aggregator = new Voices_Model_Data_Aggregator();
		$isSystemOnline = $aggregator->pingVoicePlatform();
			
		if ($isSystemOnline){
			if ($this->getRequest()->isPost()){
				$del = $this->getRequest()->getPost('del');
				if ($del == 'Yes' || $del == 'Oui'){
					$id = $this->getRequest()->getPost('id');
				
					$aggregator->deleteUnpublishedCommunique($id);
				
			   		$this->view->priorityMessenger($this->translator
												    ->translate('com-delete-success'), 'info');
					$this->_redirect('/ngo/create-new-communique');
				}
				else {
					$this->_redirect('/ngo/create-new-communique');
				}
			}
			else {
				$id = $this->_getParam('com-id', 0);
				if ($id > 0){
					$q = Doctrine_Query::create()
									->select('u.content, u.com_id')
									->from('Voices_Model_Uploads u')
									->where('u.com_id=?', $id)
									->limit(1); // communiques are in pairs
					$results = $q->fetchArray();
			
					$file = $results[0]['content'];
				
					$this->view->communique = basename($file);
					$this->view->results = $results;
				}
			}
		}
		else{
			$this->view->priorityMessenger($this->translator
												->translate('system-offline'), 'error');
			// redirect to show feedback to user
			$this->_redirect('/ngo/create-new-communique');
		}
	}
	
	/**
	 * Function to edit communique information.
	 */
	public function editCommuniqueInfoAction()
	{
		$mktInfoForm = new Voices_Form_NewMarketInfo();
		$mktInfoForm->submit->setLabel($this->translator->translate('save'));
		$mktInfoForm->setAction('/ngo/edit-communique-info'); // override action
		
		$this->view->form = $mktInfoForm;
		
		if ($this->getRequest()->isPost()){
			$mktInfoFormData = $this->getRequest()->getPost();
			if ($mktInfoForm->isValid($mktInfoFormData)){
							
				$prodId = (int)$mktInfoForm->getValue('prod_id');
				
				$contact_com_id = $mktInfoForm->getValue('contact'); // contact communique id
				
				$contact_com_id_file_path = APPLICATION_PATH . '/../data/txt-files/contacts.txt';
				$contact_com_ids = $mktInfoForm->getTextContents($contact_com_id_file_path); // from file
				$names = explode(' ', $contact_com_ids[$contact_com_id]);
				$fName = $names[0];
				$lName = $names[1];
				
				$prod = $mktInfoForm->getValue('prod');
				
				$prod_file_path = APPLICATION_PATH . '/../data/txt-files/products.txt';
				$prods = $mktInfoForm->getTextContents($prod_file_path); // from file
				$prod_name = $prods[$prod];
				
				$prod_qual_file_path = APPLICATION_PATH . '/../data/txt-files/prod-quality.txt';
				$quality = $mktInfoForm->getTextContents($prod_qual_file_path);
				
				$zone_file_path = APPLICATION_PATH . '/../data/txt-files/zones.txt';
				$zones = $mktInfoForm->getTextContents($zone_file_path);
				
				$village_file_path = APPLICATION_PATH . '/../data/txt-files/villages.txt';
				$villages = $mktInfoForm->getTextContents($village_file_path);
				
				$phone_no_file_path = APPLICATION_PATH . '/../data/txt-files/tel-numbers.txt';
				$phone_numbers = $mktInfoForm->getTextContents($phone_no_file_path);
				
				$metric_file_path = APPLICATION_PATH . '/../data/txt-files/metrics.txt';
				$metrics = $mktInfoForm->getTextContents($metric_file_path); // from file
				
				// Check to see if contact information already exists in db
				$q = Doctrine_Query::create()
									->select('c.con_com_id, c.con_id')
									->from('Voices_Model_Contacts c')
									->where('c.con_com_id=?', $contact_com_id);
						//			->addWhere('c.user_id=?', $this->auth->user_id);
				$conExists = $q->fetchArray();
				
				if (count($conExists) > 0){ // contact already exist so only update product information
									
    				$q = Doctrine_Query::create()
    								->update('Voices_Model_Products')
    								->set('con_id','?', $conExists[0]['con_id']) // update with current con_id in db
    								->set('prod_name','?', $prod_name)
    								->set('user_id','?', $this->auth->user_id)
    								->set('prod_com_id','?', $prod)
    								->set('unit_measure','?', $metrics[$prod])
    								->set('quantity','?', $mktInfoForm->getValue('quant'))
    								->set('quality','?', $quality[$prod])
    								->set('price','?', $mktInfoForm->getValue('price'))
    								->set('ts_date_entered','?', date('Y-m-d', strtotime('now')))
    								->where('prod_id=?', $prodId)
    								->addwhere('user_id=?', $this->auth->user_id);
    				$q->execute();
				}
				else{ // contact not found so add new contact and update product contact information as well
					
					$newCon = new Voices_Model_Contacts();
					
					$newCon->con_com_id = $contact_com_id;
					$newCon->user_id = $this->auth->user_id;
					$newCon->first_name = $fName;
					$newCon->last_name = $lName;
					$newCon->tel_number = $phone_numbers[$contact_com_id];
					$newCon->village = $villages[$contact_com_id];
					$newCon->zone = $zones[$contact_com_id];
					
					$newCon->save();
					
					$newConId = $newCon->Identifier(); // get the new contact id from db
					
					$q = Doctrine_Query::create()
    								->update('Voices_Model_Products')
    								->set('prod_name','?', $prod_name)
    								->set('prod_com_id','?', $prod)
    								->set('unit_measure','?', $metrics[$prod])
    								->set('quantity','?', $mktInfoForm->getValue('quant'))
    								->set('quality','?', $quality[$prod])
    								->set('price','?', $mktInfoForm->getValue('price'))
    								->set('ts_date_entered','?', date('Y-m-d', strtotime('now')))
    								->set('con_id','?', $newConId['con_id']) // newly inserted con id
    								->where('prod_id=?', $prodId)
    								->addwhere('user_id=?', $this->auth->user_id);
    				$q->execute();
				}
				$this->view->priorityMessenger($this->translator
												    ->translate('entry-edit-success'), 'info');
				$this->_redirect('/ngo/create-new-communique');
			}
		}
		else {
			$id = $this->_getParam('id', 0);
			if ($id > 0){ // id exists and is valid
				// are there unpublished communiques?
				$isWithUnpubNum = $this->unpublishedCommuniqueAvailable($id);
				
				if (!empty($isWithUnpubNum) ){
					
					$this->view->priorityMessenger($this->translator
												        ->translate('com-unpub-exists').' '.
					   							   					$isWithUnpubNum.'.', 'error');
				
					$this->view->priorityMessenger($this->translator
												    	->translate('com-pub-first'), 'info');
				
					$this->_redirect('/ngo/create-new-communique');
				}
				else{
 					$this->getProductInfoForEdit($mktInfoForm, $id);
				}
			}
			else{
				$this->getProductInfoForEdit($mktInfoForm, $id);
			}
		}
	}
	
	/**
	 * Function to delete communique information
	 *
	 * @uses unpublishedCommuniqueAvailable
	 */
	public function deleteCommuniqueInfoAction()
	{
		if ($this->getRequest()->isPost()){
			$del = $this->getRequest()->getPost('del');
			if ($del == 'Yes' || $del == 'Oui'){
				$id = $this->getRequest()->getPost('id');
				
				$q = Doctrine_Query::create()
								->delete()
								->from('Voices_Model_Products p')
								->where('p.prod_id = ?', $id);
				$results = $q->execute();
				
				$this->view->priorityMessenger($this->translator
												    ->translate('delete-success'), 'info');
				
				$this->_redirect('/ngo/create-new-communique');
			}
			else {
				$this->_redirect('/ngo/create-new-communique');
			}
		}
		else {
			$id = $this->_getParam('id', 0);
			if ($id > 0){
				// is $id associated with unpublished communique?
				$isWithUnpubNum = $this->unpublishedCommuniqueAvailable($id);
				
				if (!empty($isWithUnpubNum)){ // it is...

					$this->view->priorityMessenger($this->translator
												    	->translate('com-unpub-exists').' '.
					   							   					$isWithUnpubNum.'.', 'error');
					$this->view->priorityMessenger($this->translator
												    	->translate('com-pub-first'), 'info');
					
					$this->_redirect('/ngo/create-new-communique');
				}
				else{ // it's not...
					$q = Doctrine_Query::create()
										->from('Voices_Model_Products p')
										->leftJoin('p.Voices_Model_Contacts ')
										->where('p.prod_id=?', $id);
					$results = $q->fetchArray();
			
					$this->view->results = $results;
				}
			}
		}
	}

	/**
	 * Function for preview of communique in excel format
	 *
	 * @uses Voices_Data_Model_Aggregator::makeExcelSheet
	 */
	public function excelPreviewAction()
	{
		$id = $this->_getParam('id', 0);
		if ($id > 0){
			
			$aggregator = new Voices_Model_Data_Aggregator();
			$prodInfo = $aggregator->getAllProductInfoFromCommunique($id);
			
			list($prods, $uResults) = $prodInfo;
			
			if (count($prods) > 0){
				foreach ($prods as $pKey=>$pValue){
					foreach ($pValue as $vKey => $vValue){
						if (isset($pValue[0]['Voices_Model_Contacts'])){
						
							$prods[$pKey][$vKey]['con_com_id'] = $vValue['Voices_Model_Contacts']['con_com_id'];
							$prods[$pKey][$vKey]['contact_fname'] = $vValue['Voices_Model_Contacts']['first_name'];
							$prods[$pKey][$vKey]['contact_lname'] = $vValue['Voices_Model_Contacts']['last_name'];
							$prods[$pKey][$vKey]['contact_tel'] = $vValue['Voices_Model_Contacts']['tel_number'];
							$prods[$pKey][$vKey]['address'] = $vValue['Voices_Model_Contacts']['address'];
							$prods[$pKey][$vKey]['village'] = $vValue['Voices_Model_Contacts']['village'];
							$prods[$pKey][$vKey]['zone'] = $vValue['Voices_Model_Contacts']['zone'];
							$prods[$pKey][$vKey]['com_number'] = $uResults[0]['com_number'];
							// here ts_date_delivered = ts_date_submitted for the
							// upload since the communique is not been published
							// yet.
							$prods[$pKey][$vKey]['ts_date_delivered'] = $uResults[0]['ts_date_submitted'];
						}
					 }
				}
			
				$data = array();
			
				foreach ($prods as $aKey => $aValue) {
					foreach ($aValue as $bKey => $bValue){
						array_push($data, $bValue);
					}
				}
			
	//			$excelGen = new Voices_Model_Data_Aggregator();
	//			$excelGen->makeExcelSheet($data);
				$aggregator->makeExcelSheet($data);
			}
		}
	}
	
	/**
	 * Function to retrieve all delivered communique.
	 */
	public function communiqueStoreAction()
	{
		// get online communique
		$aggregator = new Voices_Model_Data_Aggregator();
		$systemIsOnline = $aggregator->pingVoicePlatform();
		
		if ($systemIsOnline){ // is platform online?
			$comOnlineData = $aggregator->voicePlatformQuery();
			
			list ($urlString, $queryData) = $comOnlineData;
			
			$stringPart = substr($urlString, -17); // com_id will always be 13 chars long (uniqid() fxn) + .wav = 17
			$divideString = explode('.', $stringPart);
			$com_id = $divideString[0];
					
			// use the com_id to fetch from db
			$q = Doctrine_Query::create()
							->from('Voices_Model_Uploads u')
							->where('u.delivered=?', 'yes')
							->andWhere('u.user_id=?', $this->auth->user_id)
							->andWhere('u.com_id=?', $com_id)
							->groupBy('u.com_id'); // unique selection
			$comOnline = $q->fetchArray();

//			$this->view->com_id = $com_id; // com_id needed for preview
			$this->view->comOnline = $comOnline;
		}else {
			$this->view->sysOffline = 'offline';
		}
		
		// all other communique
		$q = Doctrine_Query::create()
							->from('Voices_Model_Uploads u')
							->where('u.delivered=?', 'yes')
							->andWhere('u.user_id=?', $this->auth->user_id)
							->orderBy('u.ts_date_delivered DESC')
							->groupBy('u.com_id'); // unique selection
		$allPrevCom = $q->fetchArray();
		
		// set up pagination
		if (isset($allPrevCom)){
			$paginator = Zend_Paginator::factory($allPrevCom);
		 	$paginator->setItemCountPerPage(Zend_Registry::get('config')->item->count->per->page);
			$paginator->setCurrentPageNumber($this->_getParam('page'));
			Zend_Paginator::setDefaultScrollingStyle('Sliding');
			Zend_View_Helper_PaginationControl::setDefaultViewPartial(
												'my_pagination_control.phtml'
											 	);
			$paginator->setView($this->view);
		}
		
		$this->view->com = $allPrevCom;
		$this->view->paginator = $paginator;
	}
	
	/**
	 * Function to show content of a communique
	 */
	public function showCommuniqueContentAction()
	{
		$id = $this->_getParam('id', 0);
		if ($id > 0){
			$aggregator = new Voices_Model_Data_Aggregator();
			$prodInfo = $aggregator->getAllProductInfoFromCommunique($id);
			
			list($prods, $otherQueryResults) = $prodInfo;
			
			$this->view->prodInfo = $prods;
			$this->view->title =  $this->translator->translate('title-show-content');
		}
	}
	
	/**
	 * Helper function to give feedback to user.
	 */
	public function successAction()
	{
		if ($this->_helper->getHelper('FlashMessenger')->getMessages())
		{
			$this->view->messages = $this->_helper->getHelper('FlashMessenger')
											   	  ->getMessages();
			$this->view->title = $this->translator->translate('header-success');
		}
		else {
			$this->_redirect('/ngo/home');
		}
	}
	
	/**
	 * Auxilliary function to see if there are unpublished
	 * communique available
	 */
	public function unpublishedCommuniqueAvailable($prod_id)
	{
		// verify the edit wont affect an unpublished communique
		$q = Doctrine_Query::create()
							->select('u.prod_com_ids')
							->from('Voices_Model_Uploads u')
							->where('u.delivered=?', 'no')
							->orderBy('u.ts_date_submitted ASC')
							->groupBy('u.prod_com_ids'); // select unique ids
		$unPubComExists = $q->fetchArray();
				
		$prod_upl_ids = array(); // to store prod_com_ids (in string separated by '|'
	
				
		foreach ($unPubComExists as $aKey=>$aValue){
			array_push($prod_upl_ids, $aValue['prod_com_ids'].','.$aValue['upload_id']);
		}
		
		foreach ($prod_upl_ids as $pKey=>$pValue){
				
			$split_ids = explode(',', $pValue); // separate into array of prod_ids and upload_id
					
			$prod_ids = explode('|',$split_ids[0]); // separate prod_ids into an array
				
			if (in_array($prod_id, $prod_ids)){ // there's an unpublished communique qith product to be edited
				$q = Doctrine_Query::create()
						->select('u.com_number')
						->from('Voices_Model_Uploads u')
						->where('u.upload_id=?', $split_ids[1]);
				$com_id = $q->fetchArray();  // the com_id of the associated product
				
				return $com_id[0]['com_number'];
			}
			else
				return;
		}
	}
	
	/**
	 * Auxilliary function to get product info from database
	 *
	 * @param integer $id
	 * @param object  $formObj|an instance of Zend_Form
	 */
	public function getProductInfoForEdit($formObj, $id)
	{
		$q = Doctrine_Query::create()
							->from('Voices_Model_Products p')
							->leftJoin('p.Voices_Model_Contacts')
							->where('p.prod_id=?', $id);
		$results = $q->fetchArray();
				
		$prod = ucfirst($results[0]['prod_name']);
		$prod_file_path = APPLICATION_PATH . '/../data/txt-files/products.txt';
		$prod_index = $formObj->getTextIndex($prod, $prod_file_path);
				
		$metric = ucfirst($results[0]['unit_measure']);
		$metric_file_path = APPLICATION_PATH . '/../data/txt-files/metrics.txt';
		$metric_index = $formObj->getTextIndex($metric, $metric_file_path);
				
		$contact_name = ucfirst($results[0]['Voices_Model_Contacts']['first_name']) . ' ' .
						ucfirst($results[0]['Voices_Model_Contacts']['last_name']);
						   
		$contact_file_path = APPLICATION_PATH . '/../data/txt-files/contacts.txt';
		$contact_index = $formObj->getTextIndex($contact_name, $contact_file_path);
				
		$frmData = array(
							'prod_id' => $results[0]['prod_id'],
							'prod' => $prod_index,
							'contact' => $contact_index
						);
				
		$formObj->getElement('quant')->setValue(sprintf('%d',$results[0]['quantity'])); // out value in signed decimal
		$formObj->getElement('price')->setValue(sprintf('%d',$results[0]['price']));
				
		$formObj->populate($frmData);
	}
	
	/**
	 * Utility method to log system critical state
	 */
	public function comparePreviousOfflineState()
	{
		$message = 'Voice Platform is offline.'; // FORMAT IS IMPORTANT!
		$aggregator = new Voices_Model_Data_Aggregator();
		
		$daysPast = $aggregator->getLogTimeDifference($message);
		
		if (count($daysPast)){
			if ($daysPast > 0) // at least a day is gone so log entry and send email
				$aggregator->logCritStatusAndSendEmail($message, $this->auth, $this->getRequest()->getParams());
		}
		else { // only log new for another day
			$aggregator->logCritStatusAndSendEmail($message, $this->auth, $this->getRequest()->getParams());
		}
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