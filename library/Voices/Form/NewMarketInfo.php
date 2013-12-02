<?php
/**
 * Class used to build a form for new market data entry
 *
 * @see Voices_Form_BaseForm
 * @package Form
 */
class Voices_Form_NewMarketInfo extends Voices_Form_BaseForm
{
	/**
	 * @see Voices_Form_BaseForm::init()
	 */
	public function init()
	{
		parent::init();
		// initialize form
		$this->setAction('/ngo/enter-new-offering-info')
		     ->setMethod('post');
		
		// remove unwanted elements
		$this->removeElement('usertypes');
		$this->removeElement('username');
		$this->removeElement('password');
		$this->removeElement('confirm_pass');
		$this->removeElement('email');
		$this->removeElement('usertype');
		$this->removeElement('captcha');
		$this->removeElement('radio');
		$this->removeElement('currency');
		$this->removeElement('language');
		$this->removeElement('firstname');
		$this->removeElement('lastname');
		$this->removeElement('phone');
		$this->removeElement('village');
		$this->removeElement('zone');
		$this->removeElement('address');
		$this->removeElement('quant_metric');
		$this->removeElement('qual');
		
		$pid = new Zend_Form_Element_Hidden('prod_id');
		$pid->addFilter('Int');
		
		$this->addElement($pid);
		
		$this->getElement('submit')->setLabel('form-submit-new-entry')
								   ->setAttrib('id', 'prodsave');
	}
}