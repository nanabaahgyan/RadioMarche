<?php
/**
 * class for detecting the header of a request
 * and injecting the contest into the request
 *
 * @author inspiration from Matthew WeierO'phinney
 * http://weierophinney.net/matthew/archives/233-Responding-to-Different-
 * Content-Types-in-RESTful-ZF-Apps.html
 */
class Voices_Controller_Plugin_AcceptHandler extends Zend_Controller_Plugin_Abstract
{
	/**
	 * @param object Zend_Controller_Request_Abstract
	 * @see Zend_Controller_Plugin_Abstract::dispatchLoopStartup()
	 */
	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
	{
		if (!$request instanceof Zend_Controller_Request_Http){
				return;
		}
			
		$header = $request->getHeader('Accept');
		switch (true){
			case (strstr($header, 'application/json')):
				$request->setParam('format', 'json');
				break;
			case (strstr($header, 'application/xml') && (!strstr($header, 'html'))):
				$request->setParam('format', 'xml');
				break;
			default:
			break;
		}
	}
}