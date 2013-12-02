<?php
/**
 * This is the main class for actions permitted by an radio user.
 *
 * @see Zend_Controller_Action
 * @package Market_Controllers
 *
 */
Class Market_RadioController extends Zend_Controller_Action
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
	protected $auth = null;
	
	/**
	 * @see Zend_Controller_Action::init()
	 */
	public function init()
	{
		$this->auth = Zend_Auth::getInstance()->getIdentity();
		$this->db = Zend_Registry::get('db');
	}
	
	/**
	 * default action for controller
	 */
	public function indexAction()
	{
		// get the radio station of radio user
		$userProfile = new Voices_Model_Profile_User($this->db);
		$userProfile->setUserId($this->auth->user_id);
		$userProfile->load();
		
		$userRadio = $userProfile->radio;
					
		$q = Doctrine_Query::create()
							->from('Voices_Model_Uploads u')
							->addWhere('u.rad_sta_code = ?', $userRadio)
							->addWhere('u.delivered = ?', 'yes');
		$result = $q->fetchArray();
		
		$this->view->records = $result;
	}
	
	public function saveAudioAction()
	{
		$id = $this->_getParam('id', 0);
		if ($id > 0){
				
			$q = Doctrine_Query::create()
								->select('u.content')
								->from('Voices_Model_Uploads u')
								->where('u.upload_id=?', $id);
			$results = $q->fetchArray();
			
			$audioUrl = $results[0]['content'];
			
/**			// initiate curl session
			$ch = curl_init();
		
			// url for curl
			curl_setopt($ch, CURLOPT_URL, $audioUrl);
			// return with result on success
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			$path = APPLICATION_PATH . '/../data/uploads/communique.wav';
			
   			$fp = fopen($path, 'w');
   			
       		chmod($path, 0755);  */
       		
			$response = $this->getResponse();
  // 		$response->setHeader('Content-Length', filesize($path));
   			$response->setHeader('Cache-Control', 'no-cache');
    		$response->setHeader('Content-Type','audio/wav',true);
    		$response->setHeader('Content-Disposition:attachment', ';filename="communique.wav"');
   			
  /** 		curl_setopt($ch, CURLOPT_FILE, $fp);
    		
			// get wave file a.k.a communique
			$result = curl_exec($ch);
    		
   // 		ob_end_clean();
    		
			readfile($path);
			
   // 		$bits = readfile($path);
   // 		$response->setBody($bits);
    		
    		curl_close($ch);
    		fclose($fp);  */
    		
    		echo file_get_contents($audioUrl);
    		
    		// do not output any  view file
    		$this->_helper->layout->disableLayout();
    		$this->_helper->viewRenderer->setNoRender(true);
		}
	}
	
	public function printCommuniqueAction()
	{
		
	}
		
	public function readProductOffering()
	{
		$q = Doctrine_Query::create()
							->from('Voices_Model_Products p')
							->leftJoin('p.Voices_Model_Contacts c')
							->addWhere('p.valid = ?', 'yes')
							->addWhere('p.delivered = ?', 'no');
							
		$result = $q->fetchArray();
		
		$this->view->records = $result;
		
	}
	
	public function downloadProductOfferingAction()
	{
		$q = Doctrine_Query::create()->from('Voices_Model_Uploads u')
									 ->orderBy('u.ts_date_submitted DESC')
									 ->limit(1);
		$result = $q->fetchArray();
		
		if (count($result) > 0){
		
			foreach ($result as $r){
				$content = stripslashes($result[0]['content']);
				$size = $result[0]['size'];
				
			$this->getResponse()
				 ->setHeader('Content-length', $size)
				 ->setHeader('Content-type', 'application/xml')
     			 ->setHeader('Content-Disposition:inline', ';filename=product-offering.xml');
			
			echo $content;
			}
		}
		// do not output any  view file
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
	}
			
	/**
	 * @see Zend_Controller_Action::preDispatch()
	 */
	public function preDispatch()
	{
		$this->view->identity = $this->auth;
	}
}