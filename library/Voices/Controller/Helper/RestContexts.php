<?php
/**
 * This class exposes RESTful controllers methods as
 * context-aware
 *
 * @author inspiration from Matthew WeierO'phinney
 * http://weierophinney.net/matthew/archives/233-Responding-to-Different-
 * Content-Types-in-RESTful-ZF-Apps.html
 */
class Voices_Controller_Helper_RestContexts
	extends Zend_Controller_Action_Helper_Abstract
	{
		protected $_contexts = array( 'xml', 'json',);
		
		public function preDispatch()
		{
			$controller = $this->getActionController();
			if (!$controller instanceof Market_RestController){
				return;
			}
			
			$this->_initContexts();
			
			// set a Vary response header based on the Accept header
			$this->getResponse()->setHeader('Vary', 'Accept');
		}
		
		protected function _initContexts()
		{
			$cs = $this->getActionController()->contextSwitch;
			$cs->setAutoJsonSerialization(false);
			foreach ($this->_contexts as $context){
				foreach (array('index', 'post', 'get', 'put', 'delete') as $action){
					$cs->addActionContext($action, $context);
				}
			}
			$cs->initContext();
		}
	}