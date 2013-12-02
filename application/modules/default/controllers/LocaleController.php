<?php
/**
 * Class to register locale in session
 *
 * @see Zend_Controller_Action
 * @package Default_Controllers
 */
class LocaleController extends Zend_Controller_Action
{
	/**
	 * Function to manually override locale
	 */
	public function indexAction()
	{
		// if supported locale, add to session
		
		if (Zend_Validate::is($this->getRequest()->getParam('locale'), 'InArray',
			array('haystack' => array('en_GB', 'fr_FR'))))
			{
				$session = new Zend_Session_Namespace('voices.l10n');
				$session->locale = $this->getRequest()->getParam('locale');
			}
		
		// redirect to requesting URL
		$url = $this->getRequest()->getServer('HTTP_REFERER');
		$this->_redirect($url);
	}
}