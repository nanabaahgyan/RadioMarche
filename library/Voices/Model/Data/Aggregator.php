<?php
/**
 * Class to communique generation and relation functionality
 *
 * @package Application
 */
class Voices_Model_Data_Aggregator
{
	/**
	 * This function accomplishes the following
	 * 		* to publish communique on voice platform
	 * 		* notify by emails.
	 * 		* makes a backup of communque information
	 *
	 * @param int com_id
	 * @return array
	 */
	public function aggregate($comId)
	{
		$data = array('communique' => Zend_Json::encode(array('comm_id' => $comId)));
				
		// initiate curl session
		$ch = curl_init();
		
		$publishArrUrl = Zend_Registry::get('config')->voice->platform->base->url . '/rest/publish_communique.php';
		
		// url for curl
		curl_setopt($ch, CURLOPT_URL, $publishArrUrl);
		// return with result on success
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// using POST and not GET
		curl_setopt($ch, CURLOPT_POST, true);
		// POST data
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
					
		// get wave file a.k.a communique
		$result = curl_exec($ch);
		
		curl_close($ch);

		$status = Zend_Json::decode($result);
		$httpRspCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if ($status['status'] == 'FAIL'){
			$message = $status['message'];
			
			throw new Exception('Audio Generator Publish Error: ' . $message, $httpRspCode);
		}
		else
		{
			// update databse
			$q = Doctrine_Query::create()
							->update('Voices_Model_Uploads u')
							->set('delivered','?','yes')
							->set('ts_date_delivered','?', date('Y-m-d H:i:s', strtotime('now')))
							->where('com_id=?', $comId);
			$q->execute();
			
			$db = Zend_Registry::get('db'); // get db instance
					
			//send mails to radio stations as well
			$select = $db->select()
					 	 ->from(array('u' => 'users'), array('user_type'))
					 	 ->join(array('up' => 'users_profile'),
					 	 		'u.user_id = up.user_id',
					 	 		array('profile_value', 'user_id'))
					 	 ->where('u.user_type=?', 'rad')
					 	 ->where('up.profile_key=?', 'email');
			$stmt = $db->query($select);
			$results = $stmt->fetchAll();
			
			if (count($results)){
				// instance of Zend_Locale
				$locale = Zend_Registry::get('Zend_Locale');
				if ($locale == 'en' || $locale == 'en_GB')
					$file = 'notice-email-en.phtml';
				else
					$file = 'notice-email-fr.phtml';
				
				foreach ($results as $key => $value) {
					// get user profile information
					$userProfile = new Voices_Model_Profile_User($db);
					$userProfile->setUserId($value['user_id']);
					$userProfile->load();
				
					// send mails to radio users with email
					if (isset($userProfile->email)){
						$user = new Voices_Model_DatabaseObject_User($db);
						
						$user->sendEmail($file,
										 $userProfile->email,
									 	 $userProfile->first_name,
									 	 $userProfile->last_name);
					}
					else
					{ 	// register no email notice in log
						$logger = Zend_Registry::get('logger');
		
						$message = 'Product offering created but no email sent to'.
									  $userProfile->first_name.' '.$userProfile->last_name;
						$logger->setEventItem('stacktrace', ' ');
						$logger->setEventItem('request', 'User email not available. ' . $message);
						$logger->log('No Email Sent', Zend_Log::NOTICE);
					}
				}
			}
		
			// store communique-related market info for future analysis
			$q = Doctrine_Query::create()
								->select('u.prod_com_ids, u.ts_date_delivered, u.com_number')
								->from('Voices_Model_Uploads u')
								->where('u.com_id=?', $comId)
								->limit(1);
			$uplResults = $q->fetchArray();
		
			$prodIds = $uplResults[0]['prod_com_ids'];
		
			$prodIdsArray = explode('|', $prodIds);
			
			$prodArray = array();
			
			foreach ($prodIdsArray as $key => $value)
			{
				$q = Doctrine_Query::create()
								->from('Voices_Model_Products p')
								->leftJoin('p.Voices_Model_Contacts')
								->where('p.prod_id=?', $value);
				$pResult = $q->fetchArray();
			
				$store = new Voices_Model_MarketStore();
			
				$store->com_id = $comId;
				$store->prod_com_id = $pResult[0]['prod_com_id'];
				$store->con_com_id = $pResult[0]['Voices_Model_Contacts']['con_com_id'];
				$store->user_id = $pResult[0]['user_id'];
				$store->com_number = $uplResults[0]['com_number'];
				$store->prod_name = $pResult[0]['prod_name'];
				$store->unit_measure = $pResult[0]['unit_measure'];
				$store->quantity = $pResult[0]['quantity'];
				$store->quality = $pResult[0]['quality'];
				$store->price = $pResult[0]['price'];
				$store->currency = $pResult[0]['currency'];
				$store->ts_date_entered = $pResult[0]['ts_date_entered'];
				$store->ts_date_delivered = $uplResults[0]['ts_date_delivered'];
				$store->contact_fname = $pResult[0]['Voices_Model_Contacts']['first_name'];
				$store->contact_lname = $pResult[0]['Voices_Model_Contacts']['last_name'];
				$store->contact_tel = $pResult[0]['Voices_Model_Contacts']['tel_number'];
				$store->contact_address = $pResult[0]['Voices_Model_Contacts']['address'];
				$store->village = $pResult[0]['Voices_Model_Contacts']['village'];
				$store->zone = $pResult[0]['Voices_Model_Contacts']['zone'];
			
				$store->save();
			}
		}
		return $status;
	}

	/**
	 * Function to request audio file (a.k.a communique)
	 *
	 * @param array $comData
	 * @return array
	 */
	public function requestAudioFile($comData)
	{
		if (is_array($comData)){
			// name of textarea input on communique generator form
			$data = array('communique' => Zend_Json::encode($comData));
				
			// initiate curl session
			$ch = curl_init();
		
			$audioGenUrl = Zend_Registry::get('config')
									->voice->platform->base->url . '/rest/get_audio_communique.php';
			// url for curl
			curl_setopt($ch, CURLOPT_URL, $audioGenUrl);
			// return with result on success
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// using POST and not GET
			curl_setopt($ch, CURLOPT_POST, true);
			// POST data
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
					
			// get wave file a.k.a communique
			$result = curl_exec($ch);
			
			$result_decoded = Zend_Json::decode($result);
			
			$httpRspCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			curl_close($ch);
			
			$status = $result_decoded['status'];
			
			if ($status == 'FAIL') // response was not ok
			{
				$message = $result_decoded['message'];
				
				throw new Exception('Audio Generation Error: ' . $message, $httpRspCode);
			}
			else {
				$waveURL = array();
				$waveURL = $result_decoded['audio'];
				
			}
			return array($httpRspCode, $status, $waveURL);
		}
	}
	
	/**
	 * Funttion to delete unpublished communique from
	 * voice platform and database
	 *
	 * @param int $id
	 * @throws Exception
	 */
	public function deleteUnpublishedCommunique($id)
	{
		/** remove entry from communique platform **/
				
		$data = array('communique' => Zend_Json::encode(array('comm_id' => $id)));
				
		// initiate curl session
		$ch = curl_init();
				
		$deleteUrl = Zend_Registry::get('config')->voice->platform->base->url . '/rest/delete.php';
				
		// url for curl
		curl_setopt($ch, CURLOPT_URL, $deleteUrl);
		// return with result on success
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// using POST and not GET
		curl_setopt($ch, CURLOPT_POST, true);
		// POST data
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				
		// delete entry from platform
		$delete = curl_exec($ch);
				
		$statusDel = Zend_Json::decode($delete);
				
		$httpRspCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				
		if ($statusDel['status'] == 'FAIL'){
			$message = strip_tags($statusDel['message']);
					
			throw new Exception('Communique Deletion Error: '. $message .$httpRspCode);
		}
		else{
				
			$logger = Zend_Registry::get('logger');
		
			$message = 'Communiqué with com_id: '. $id . ' deleted';
			$logger->setEventItem('stacktrace', 'deleted by user with user_id: '.$this->auth->user_id);
			$logger->setEventItem('request', $message.' on '. date('Y-m-d H:i:s'));
			$logger->log('Generated communiqué not published', Zend_Log::NOTICE);
					
			$q = Doctrine_Query::create()
									->delete('Voices_Model_Uploads u')
									->where('u.com_id=?', $id);
			$q->execute();
		}
	}
	
	/**
	 * Auxiliary function to create communique in
	 * Excel format
	 *
	 * @param array $data
	 * @param int $id
	 */
	public function makeExcelSheet($data)
	{
		/** Reading Excel with PHPExcel_IOFactory */
		require_once 'PHPExcel/IOFactory.php';

		$objReader = PHPExcel_IOFactory::createReader('Excel5');
		
		// template file
		$objPHPExcel = $objReader->load(APPLICATION_PATH . '/../data/templates/com-tpl.xls');

		$baseRow = 11; // base row in template file
		
		foreach($data as $r => $dataRow) {
			$row = $baseRow + ($r+1);
			
			$objPHPExcel->getActiveSheet()->insertNewRowBefore($row,1);
			
			if (!empty($dataRow)){
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$row, ucfirst($dataRow['zone']));
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$row, ucfirst($dataRow['village']));
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$row, ucfirst($dataRow['prod_name']));
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$row, $dataRow['unit_measure']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$row, $dataRow['quantity']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$row, ucfirst($dataRow['quality']));
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$row, $dataRow['price']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$row,
														 		 ucfirst($dataRow['contact_fname']).' '.
														 		 ucfirst($dataRow['contact_lname']).' '.
														 		 'TEL: ' . $dataRow['contact_tel']);
			}
		}
		$objPHPExcel->getActiveSheet()->removeRow($baseRow-1,1);
		
		// fill data area with white color
		$objPHPExcel->getActiveSheet()
					->getStyle('A'.$baseRow .':'.'H'.$row)
					->getFill()
					->applyFromArray(
           							 array(
            		        		  'type'      	=> PHPExcel_Style_Fill::FILL_SOLID,
            						  'startcolor' 	=> array('rgb' => 'FFFFFF'),
            				    	  'endcolor' 	=> array('rgb' => 'FFFFFF')
            					   ));
		
		$comNo = $dataRow['com_number'];
		$deliveryDate = date('d-m-Y', strtotime($dataRow['ts_date_delivered']));
		
		$ComTitle = "INFORMATION SUR LES PRODUITS FORESTIERS NON LIGNEUX DU " .
		            "CERCLE DE TOMINIAN  No: $comNo Du $deliveryDate";
		
		$titleRow =  $baseRow-1;
		// create new row
		$objPHPExcel->getActiveSheet()->insertNewRowBefore($titleRow,1);
		$objPHPExcel->getActiveSheet()
					->mergeCells('A'.$titleRow. ':'.'H'.$titleRow)
					->setCellValue('A'.$titleRow, $ComTitle)
					->getStyle('A'.$titleRow)->getFont()
					->setBold(true)
					->setSize(13)
					->setColor(new PHPExcel_Style_Color());
					
	//	$objPHPExcel->getActiveSheet()->getRowDimension('A'.$titleRow)->setRowHeight(10);
					
		$title = 'ComNo'.$comNo;
		// set title of sheet
		$objPHPExcel->getActiveSheet()->setTitle($title);

		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="communique.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}
	
	/**
	 * Function to query current communique on voice platform
	 *
	 * @throws Exception
	 * @return array | array with audio urls
	 */
	public function voicePlatformQuery()
	{
		$data = array('communique' => Zend_Json::encode(array('voice' => 1)));
		
		// initiate curl session
		$ch = curl_init();
		
		$queryArrUrl = Zend_Registry::get('config')->voice->platform->base->url . '/rest/query.php';
		
		// url for curl
		curl_setopt($ch, CURLOPT_URL, $queryArrUrl);
		// return with result on success
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// using POST and not GET
		curl_setopt($ch, CURLOPT_POST, true);
		// POST data
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			
		// get wave file a.k.a communique
		$result = curl_exec($ch);
		
		$resultInArr = Zend_Json::decode($result);
		$httpRspCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		curl_close($ch);
		
		if ($resultInArr['status'] == 'FAIL'){
			$message = $status['message'];
			
			throw new Exception('Voice Platform Query Error: ' . $message, $httpRspCode);
		}
		else{
			$urlString = $resultInArr['voice'][0]; // retrieve only one url string
			
			return array($urlString, $resultInArr['voice']);
		}
	}
	
	/**
	 * Function to retrieve all published communique on voice platform
	 *
	 * @throws Exception
	 * @return array | an array of communique ids on platform
	 */
	public function communiquePlatformQuery()
	{
		$data = array('communique' => Zend_Json::encode(array('comm_id' => 1)));
		
		// initiate curl session
		$ch = curl_init();
		
		$queryArrUrl = Zend_Registry::get('config')->voice->platform->base->url . '/rest/query.php';
		
		// url for curl
		curl_setopt($ch, CURLOPT_URL, $queryArrUrl);
		// return with result on success
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// using POST and not GET
		curl_setopt($ch, CURLOPT_POST, true);
		// POST data
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			
		// get wave file a.k.a communique
		$result = curl_exec($ch);
		
		$resultInArr = Zend_Json::decode($result);
		$httpRspCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		curl_close($ch);
		
		if ($resultInArr['status'] == 'FAIL'){
			$message = $status['message'];
			
			throw new Exception('Voice Platform Query Error: ' . $message, $httpRspCode);
		}
		else{
			return $resultInArr['comm_id'];
		}
	}
	
	/**
	 * Function to check if service is online
	 *
	 * @return boolean | whether or not the platform is online
	 */
	public function pingVoicePlatform()
	{
		// initiate curl session
		$ch = curl_init();
		
		$queryUrl = Zend_Registry::get('config')->voice->platform->base->url;
		
		// url for curl
		curl_setopt($ch, CURLOPT_URL, $queryUrl);
		// return with result on success
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// only headers, no body
		curl_setopt($ch, CURLOPT_NOBODY, true);
			
		// test connection
		curl_exec($ch);
		
		$httpRspCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		curl_close($ch);
		
		if ($httpRspCode >= 200 && $httpRspCode < 400)
			return true;
		else{
			return false;
		}
	}
	
	/**
	 * Utility function to calculate date difference using
	 * log message
	 *
	 * @param string $message
	 * @return int | the number of days past
	 */
	public function getLogTimeDifference($message)
	{
		// has there been an instance for today?
		$query = Doctrine_Query::create()
									->select('l.log_time')
									->from('Voices_Model_Logs l')
									->where('l.log_message=?', $message)
									->orderBy('l.log_time DESC');
		$result = $query->fetchArray();
		
		if (count($result)){
			// the time for the last log
			$last_log_time = $result[0]['log_time'];
				
			// calculate difference between times
			$diff = abs(strtotime('now') - strtotime($last_log_time));
				
			// days gone since last log
			$days = floor($diff / (60*60*24));
			
			return $days;
		}
		else
			return;
	}

	/**
	 * Utility method to log critical system state
	 * and send email accordingly to SysAdmin
	 *
	 * @param string $message
	 * @param object $auth
	 */
	public function logCritStatusAndSendEmail($message, $auth, $request)
	{
		// get logger from registry
		$logger = Zend_Registry::get('logger');
		
		$logger->setEventItem('stacktrace', 'Access initiated by user with user_id: '
								. $auth->user_id . ' on Voice Platform with url '.
								Zend_Registry::get('config')->voice->platform->base->url
							 );
		$logger->setEventItem('request',
							   Zend_Debug::dump($request, null, false)
							 );
		$logger->log($message, Zend_Log::CRIT);
			
		// send email
		// get an array of admin emails
		$sysAdmin = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('admin');
		
		$user = new Voices_Model_DatabaseObject_User(Zend_Registry::get('db'));
		foreach ($sysAdmin as $adminData){
			$user->sendEmail('system-crit-email.phtml',
							 $adminData['email'],
							 $adminData['first']['name'],
							 $adminData['last']['name']);
		}
	}
	
/**
	 * Auxilliary function to select product information
	 * from a generated communique
	 *
	 * @param int $id
	 * @return array
	 */
	public function getAllProductInfoFromCommunique($id)
	{
		$q = Doctrine_Query::create()
							//	->select('u.prod_com_ids, u.com_number, u.ts_date_submitted')
								->from('Voices_Model_Uploads u')
								->where('u.upload_id=?', $id);
		$uResults = $q->fetchArray();
			
		$prodIds = explode('|', $uResults[0]['prod_com_ids']);
			
		$prods = array();
			
		// get product info from prod_com_ids
		foreach ($prodIds as $key=>$value)
		{
			$q = Doctrine_Query::create()
									->from('Voices_Model_Products p')
									->leftJoin('p.Voices_Model_Contacts')
									->where('p.prod_id =?', $value);
			$pResults = $q->fetchArray();
				
			if (count($pResults) > 0){
				array_push($prods, $pResults);
			}
		}
		// $uResults for results from initial query
		// needed by other functions (eg excelPreviewAction)
		return array($prods, $uResults);
	}
	
	/**
	 * Function to generate excel sheet of statistics on
	 * communique generation
	 *
	 * @param array $data
	 */
	public function makeComStatsExcelSheet($data)
	{
		/** Reading Excel with PHPExcel_IOFactory */
		require_once 'PHPExcel/IOFactory.php';

		$objReader = PHPExcel_IOFactory::createReader('Excel5');
		
		// template file
		$objPHPExcel = $objReader->load(APPLICATION_PATH . '/../data/templates/com-stats-tpl.xls');
	}
}