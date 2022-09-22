<?php


namespace Strud\Router\Interpreter
{
	use Strud\Route;
	use Strud\Route\Component\NullController;
	use Strud\Route\Component\NullModel;
	use Strud\Route\Component\NullView;
	use Strud\Router\Interpreter;

	class ConventionBased extends Interpreter
	{
		protected $pattern = "/.+/";

		/**
		 * @var string
		 */
		private $sourceFolder;
		private $errorHandler;

		public function __construct($sourceFolder = null, callable $errorHandler = null)
		{
			$this->sourceFolder = $sourceFolder;
			$this->errorHandler = $errorHandler;
		}

		public function interpret($url)
		{
			$controllerClass = null;
			$viewClass =  null;
			$modelClass =  null;
			$parameters = [];
			
			$currentPath = $this->sourceFolder ? $this->sourceFolder : "Component";
			
			$fragments = array_filter(preg_split("/\//", $url), function($item) {
				return !empty($item);
			});
			
			foreach($fragments as $fragment)
			{
				$path = $this->appendAsNamespace($currentPath, ucfirst($fragment));
				
				if($this->directoryExists($path))
				{
					$currentPath = $path;
					
					if($this->thereIsAModelIn($currentPath))
					{
						$modelClass = $this->appendAsNamespace($currentPath, Route::MODEL);
					}
				}
				else
				{
					$parameters[] = $fragment;
				}
			}
			
			$pathToDefaultSubComponent = $this->appendAsNamespace($currentPath, Route\Component::DEFAULT_SUB_COMPONENT);
			
			if($this->directoryExists($pathToDefaultSubComponent))
			{
				$currentPath = $pathToDefaultSubComponent;
			}
			
			if($this->thereIsAControllerOrAViewIn($currentPath))
			{
				if($this->thereIsAViewIn($currentPath))
				{
					$viewClass = $this->appendAsNamespace($currentPath, Route::VIEW);
				}
				
				if($this->thereIsAControllerIn($currentPath))
				{
					$controllerClass = $this->appendAsNamespace($currentPath, Route::CONTROLLER);
				}
				
				$model = $modelClass ? new $modelClass($parameters) : new NullModel();
				$view = $viewClass ? new $viewClass($model) : new NullView($model);
				$controller = $controllerClass ? new $controllerClass($model, $parameters) : new NullController($model);
			}
			else
			{
				if(is_callable($this->errorHandler)) call_user_func($this->errorHandler);
                $model = new NullModel();
                $view =  new NullView($model);
                $controller = new NullController($model);
			}
			
			return new Route($url, $view, $controller);
		}
		
		private function directoryExists($path)
		{
			$path = preg_replace("/\\\\+/", DIRECTORY_SEPARATOR, $path);
			return file_exists(implode(DIRECTORY_SEPARATOR, [get_include_path(), "application", $path]));
		}
		
		private function thereIsAControllerOrAViewIn($path)
		{
			return $this->thereIsAControllerIn($path) | $this->thereIsAViewIn($path);
		}
		
		private function thereIsAControllerIn($path)
		{
			return class_exists($this->appendAsNamespace($path, Route::CONTROLLER));
		}
		
		private function thereIsAViewIn($path)
		{
			return class_exists($this->appendAsNamespace($path, Route::VIEW));
		}
		
		private function thereIsAModelIn($path)
		{
			return class_exists($this->appendAsNamespace($path, Route::MODEL));
		}
		
		private function appendAsNamespace($parent, $child)
		{
			return "{$parent}\\{$child}";
		}
	}
}


