<?php
/**
 * Class used to build a form for user registration
 *
 * @see Voices_Form_BaseForm
 * @package Form
 */
class Voices_Form_UserRegister extends Voices_Form_BaseForm
{
	/**
	 * @see Voices_Form_BaseForm::init()
	 */
	public function init()
	{
		parent::init();
		
		// remove unwanted form elements
		$this->removeElement('usertypes');
		$this->removeElement('usertype');
		$this->removeElement('radio');
		$this->removeElement('language');
		
		// elements on product
		$this->removeElement('prod');
		$this->removeElement('comque_id');
		$this->removeElement('village');
		$this->removeElement('zone');
		$this->removeElement('quant');
		$this->removeElement('quant_metric');
		$this->removeElement('currency');
		$this->removeElement('qual');
		$this->removeElement('price');
		
		// communique informatio
		$this->removeElement('contact_id');
		$this->removeElement('contact');
	}
}