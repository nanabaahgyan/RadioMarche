<?php
class Voices_Form_Element_Captcha extends Zend_Form_Element_Captcha
{

    /**
     * Render form element.
     * Has been used to override default render method
     * which translate default error messages rather poorly
     *
     * @param  Zend_View_Interface $view
     * @return string
     */
    public function render(Zend_View_Interface $view = null) {

                return  str_replace(
                        'Captcha value is wrong',
                        utf8_decode($this->getTranslator()->translate('wrong-captcha')),
                        parent::render()
                );
    }
}