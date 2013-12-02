<?php
/**
 * Class for creating login form
 *
 * @see Zend_Form
 * @package Form
 *
 */
class Voices_Form_Login extends Zend_Form
{
	/**
	 * Form decorator
	 * @var array
	 */
	protected $formDecorators = array(
								'FormElements',
								array('HtmlTag', array('tag' => 'table', 'id' => 'log-in')),
						//		'FormErrors',
								'Form',
							  );
								
	/**
	 * Form element decorator
	 * @var array
	 */
	protected $elementDecorators = array(
        							'ViewHelper',
        							'Errors', //(en/dis)ables default form error
        							array(array('data' => 'HtmlTag'), array('tag' => 'td')),
       								array('Label', array('tag' => 'td')),
       								array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
       		//						array(array('Label' => 'HtmlTag'), array('tag' => 'th')),
      							);
     
	/**
	 * Form button decorator
	 * @var array
	 */
    protected $buttonDecorators = array(
        							'ViewHelper',
        							array(array('data' => 'HtmlTag'), array('tag' => 'td')),
        							array(array('label' => 'HtmlTag'), array('tag' => 'td', 'placement' => 'prepend')),
        							array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        					);

	/**
	 * @see Zend_Form::init()
	 */
	public function init()
	{
		// initialize form
		$this->setAction('/account/login');
		$this->setMethod('post');
		$this->setDecorators($this->formDecorators);
			 
		// create text input for name
		$username = new Zend_Form_Element_Text('username');
		$username->setLabel('form-contact-username')
				 ->setOptions(array('size' => '30'))
				 ->setRequired(true)
		//		 ->setAttrib('placeholder', 'your username')
				 ->addValidator('Alnum')
				 ->addFilter('HtmlEntities')
				 ->setDecorators($this->elementDecorators)
				 ->addFilter('StringTrim');
		$username->removeDecorator('Errors');
				 				 		
		// create text input for password
		$password = new Zend_Form_Element_Password('password');
		$password->setLabel('form-contact-password')
				 ->setOptions(array('size' => '30'))
				 ->setRequired(true)
		//		 ->setAttrib('placeholder', 'your password')
				 ->addFilter('HtmlEntities')
				 ->setDecorators($this->elementDecorators)
				 ->addFilter('StringTrim');
		$password->removeDecorator('Errors');
				 				 
		// create a checkbox for remember me
		$remember = new Zend_Form_Element_Checkbox('remember');
		$remember->setLabel('form-remember')
				 ->setDecorators($this->elementDecorators);
						 
		// create submit button
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('form-login')
		//	   ->setOptions(array('class' => 'submit'))
			   ->setOrder(20)
			   ->setDecorators($this->buttonDecorators);
			   			   
		// attach elements to form
		$this->addElement($username)
			 ->addElement($password)
			 ->addElement($remember)
			 ->addElement($submit);
	}

}