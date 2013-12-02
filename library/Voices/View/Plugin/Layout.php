<?php
/**
 * Class Voice_View_Plugin_Layout
 *
 * @author inspiration from:
 * http://www.zfforums.com/zend-framework-components-13/model-
 * view-controller-mvc-21/modules-layouts-2645.html
 */
class Voices_View_Plugin_Layout extends Zend_Controller_Plugin_Abstract
{
	protected $_moduleLayouts;
	
	/**
	 * registration of module layout
	 *
	 * @param $module
	 * @param $layoutPath
	 * @param $layout
	 */
	public function registerModuleLayout ($module, $layoutPath, $layout=null)
	{
		$this->_moduleLayouts[$module] = array(
											'layoutPath' => $layoutPath,
											'layout' => $layout
									     );
}

	/**
	 * @see Zend_Controller_Plugin_Abstract::preDispatch
	 * @param Zend_Controller_Request_Abstract $request
	 */
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		if(isset($this->_moduleLayouts[$request->getModuleName()])){
			$config = $this->_moduleLayouts[$request->getModuleName()];
			$layout = Zend_Layout::getMvcInstance();
			if($layout->getMvcEnabled()){
				$layout->setLayoutPath($config['layoutPath']);
				if($config['layout'] !== null){
					$layout->setLayout($config['layout']);
				}
			}
		}
	}
}