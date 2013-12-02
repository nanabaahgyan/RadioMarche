<?php
/**
 * This action helper class the RESTful web service format agnostic,
 * whether XML or JSON
 *
 * @author inspiration from Matthew WeierO'phinney
 * http://weierophinney.net/matthew/archives/233-Responding-to-Different-
 * Content-Types-in-RESTful-ZF-Apps.html
 *
 */
class Voices_Controller_Helper_Params
	extends Zend_Controller_Action_Helper_Abstract
	{
		/**
		 * @var array Parameters detected in raw content body
		 */
		protected $_bodyParams = array();
		
		/**
		 * do detection of content type, and retrieve parameters
		 * from raw body if present
		 * @return void
		 */
		public function init()
		{
			$request = $this->getRequest();
			$contentType = $request->getHeader('Content-Type');
			$rawBody = $request->getRawBody();
			if (!$rawBody){
				return;
			}
			switch (true) {
				case strstr($contentType, 'application/json'):
					$this->setBodyParams(Zend_Json::decode($rawBody));
				break;
				case (strstr($contentType, 'application/xml')):
					$this->setBodyParams($config->toArray());
				break;
				default:
					if($request->isPut()){
						parse_str(file_get_contents('php://input', $params));
					};
				break;
			}
		}
		
		/**
		 * set body params
		 *
		 * @param array $params
		 * @return
		 */
		public function setBodyParams(array $params)
		{
			$this->_bodyParams = $params;
			return $this;
		}
		
		/**
		 * retrieve body parameters
		 *
		 * @return array
		 */
		public function getBodyParams()
		{
			return $this->_bodyParams;
		}
		
		/**
		 * get body parameter
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function getBodyParam($name)
		{
			if ($this->hasBodyParam($name)){
				return $this->_bodyParams[$name];
			}
			return null;
		}
		
		/**
		 * is the given body parameter set?
		 *
		 * @param string $name
		 * @return bool
		 */
		public function hasBodyParam($name)
		{
			if (isset($this->_bodyParams[$name])){
				return true;
			}
			return false;
		}
		
		/**
		 * do we have any body parameters?
		 *
		 * @return bool
		 */
		public function hasBodyParams()
		{
			if(!empty($this->_bodyParams)){
				return true;
			}
			return false;
		}
		
		/**
		 * get submitted parameters
		 *
		 * @return array
		 */
		public function getSubmitParams()
		{
			if ($this->hasBodyParams()){
				return $this->getBodyParams();
			}
			return $this->getRequest()->getPost();
		}
		
		public function direct()
		{
			return $this->getSubmitParams();
		}
	}