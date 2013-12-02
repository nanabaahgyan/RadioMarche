<?php
class Voices_Form_FeedBack extends Voices_Form_BaseForm
{
	protected  $translator = null;
	
	public function init()
	{
		parent::init();
		
		$this->translator = Zend_Registry::get('Zend_Translate');
		
		// initialize form
		$this->setAction('/ngo/feedback')
		     ->setMethod('post');
		     
		$this->removeDecorator('fieldset');
		
		// remove unwanted elements
		$this->removeElement('zone');
		$this->removeElement('village');
		$this->removeElement('prod');
		$this->removeElement('qual');
		$this->removeElement('quant');
		$this->removeElement('price');
		$this->removeElement('currency');
		$this->removeElement('quant_metric');
		$this->removeElement('price');
		
		$this->removeElement('usertypes');
		$this->removeElement('usertype');
		$this->removeElement('username');
		$this->removeElement('password');
		$this->removeElement('confirm_pass');
		$this->removeElement('firstname');
		$this->removeElement('lastname');
		$this->removeElement('email');
		$this->removeElement('radio');
		$this->removeElement('phone');
		$this->removeElement('contact');
		$this->removeElement('language');
		
		$this->removeElement('captcha');
		
		$this->getElement('address')
			 ->setOptions(array('rows' => '10', 'cols' => '70'))
		     ->setLabel('form-feedback')
		     ->setRequired(true)
		     ->addFilter('StringTrim')
		     ->addFilter('HtmlEntities');
		     
		$feedback_type = new Zend_Form_Element_Select('feedback_type');
		$feedback_type->setLabel('form-type')
					  ->setRequired(true)
					  ->addMultiOption('',  $this->translator->translate('form-select'))
					  ->addMultiOptions(array(
					  				'Design' => 'Design',
					  				'Functionality' => 'Functionality',
					  				'Other' => 'Other'
					  ));
		$feedback_type->setDecorators($this->elementDecorators)->setOrder(0);
		
		$this->addElement($feedback_type);
		$this->getElement('submit')->setLabel('form-submit-new-entry');
	}
}