<?php


namespace Strud
{
	
	use Strud\Route\Component\Controller;
	use Strud\Route\Component\View;
	
	class Route
	{
		const MODEL = "Model";
		const VIEW = "View";
		const CONTROLLER = "Controller";
		
		private $url;
		private $view;
		private $controller;
		
		public function __construct($url, View $view, Controller $controller)
		{
			$this->url = $url;
			$this->view = $view;
			$this->controller = $controller;
		}
		
		/**
		 * @return bool
		 */
		public function hasController()
		{
			return !empty($this->controller);
		}
		
		/**
		 * @return bool
		 */
		public function hasView()
		{
			return !empty($this->view);
		}
		
		/**
		 * @return View
		 */
		public function getView()
		{
			return $this->view;
		}
		
		/**
		 * @return Controller
		 */
		public function getController()
		{
			return $this->controller;
		}
		
		/**
		 * @return string
		 */
		public function getUrl()
		{
			return $this->url;
		}
	}
}


