<?php
/**
 * Class used to build a form for creating communique(s)
 *
 * @see Voices_Form_BaseForm
 * @package Form
 */
class Voices_Form_Communique extends Voices_Form_BaseForm
{
	/**
	 * @see Voices_Form_BaseForm::init()
	 */
	public function init()
	{
		parent::init();
		
		$this->clearDecorators();
		
		// remove unwanted form elements
		$this->removeElement('usertypes');
		$this->removeElement('usertype');
		$this->removeElement('username');
		$this->removeElement('password');
		$this->removeElement('confirm_pass');
		$this->removeElement('firstname');
		$this->removeElement('lastname');
		$this->removeElement('email');
		$this->removeElement('contact');
		$this->removeElement('phone');
		$this->removeElement('usertype');
		$this->removeElement('address');
		$this->removeElement('captcha');
		
		$this->removeElement('radio');
		$this->removeElement('language');
		
		// elements on product
		$this->removeElement('prod');
//		$this->removeElement('comque_id');
		$this->removeElement('village');
		$this->removeElement('zone');
		$this->removeElement('quant');
		$this->removeElement('quant_metric');
		$this->removeElement('currency');
		$this->removeElement('qual');
		$this->removeElement('price');
		
		// communique information
		$this->removeElement('contact_id');
		$this->removeElement('contact');
				
		$this->getElement('submit')
			 ->setLabel('form-create-communique');
	}
}