<?php
/**
 * This class has other functions that are relevant to both
 * NgoController and RadioController
 *
 * @see Zend_Controller_Action
 * @package Market_Controllers
 *
 */
class Market_MarketInfoController extends Zend_Controller_Action
{
	/**
	 * An instance of Zend_Auth
	 * @var object
	 */
	protected $auth = null;
	
	/**
	 * An instance of Zend_Translate
	 * @var object
	 */
	protected $translator = null;
	
	/**
	 * An instance of Zend_Db
	 * @var object
	 */
	protected $db = null;
	
	/**
	 * @see Zend_Controller_Action::init()
	 */
	public function init()
	{
		/// instance of user identity
		$this->auth = Zend_Auth::getInstance()->getIdentity();
		$this->view->identity = $this->auth;
		
	//	$this->translator = Zend_Registry::get('Zend_Translate');
	}
	
	/**
	 * Default action to execute for controller
	 */
	public function indexAction()
	{
		// nothing for now
	}
	
	/**
	 * Function to allow full-text search capabilities
	 */
	public function searchAction()
	{
		// generate input form
		$form = new Voices_Form_Search;
		$this->view->form = $form;
		
		// get items matching search criteria
		if ($form->isValid($this->getRequest()->getParams())){
			$input = $form->getValues();
			if (!empty($input['q'])) {
				$config = $this->getInvokeArg('bootstrap')->getOption('indexes');
				try {
					$index = Zend_Search_Lucene::open($config['indexPath']);
				} catch (Exception $e) {
					
				}
				$results = $index->find(
										Zend_Search_Lucene_Search_QueryParser::parse($input['q']));
				$this->view->results = $results;
			}
		}
	}
	
	/**
	 * Function to print Excel sheet from communique
	 *
	 * @uses makeExcelSheet
	 */
	public function printAction()
	{
		if ($this->getRequest()->isPost()){
			// nothing
		}
		else{
			$id = $this->_getParam('com-id', 0);
			if ($id > 0){
				$q = Doctrine_Query::create()
								->from('Voices_Model_MarketStore m')
								->addWhere('m.com_id=?', $id);
				$mResult = $q->fetchArray();
			
				$excelGen = new Voices_Model_Data_Aggregator();
				$excelGen->makeExcelSheet($mResult);
			}
		}
	}
}