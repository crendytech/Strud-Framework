<?php


namespace Strud\Route\Component
{
	
	use Strud\Request;
	use Strud\Route\Component;
	
	abstract class View extends Component
	{
		/**
		 * @var Model
		 */
		protected $model;
		
		public function __construct(Model $model)
		{
			$this->model = $model;
		}
		
		/**
		 * @return string
		 */
		abstract public function render();
	}
}


