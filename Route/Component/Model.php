<?php


namespace Strud\Route\Component
{
	
	use Strud\Route\Component;
	use Strud\Utils\ArrayUtil;
	use Strud\Utils\StringUtil;
	
	abstract class Model extends Component
	{
		protected $parameters;
		
		public function __construct($parameters = [])
		{
			$this->parameters = $parameters;
			
			if(!empty($parameters))
			{
				$this->reload();
			}
			return $this->parameters;
		}
		
		/**
		 * @return bool
		 */
		public function exists()
		{
			throw new \BadMethodCallException("exists() is not overridden by the class");
		}
		
		public function save()
		{
			throw new \BadMethodCallException("save() is not overridden by the class");
		}
		
		public function delete()
		{
			throw new \BadMethodCallException("delete() is not overridden by the class");
		}
		
		public function reload()
		{
			throw new \BadMethodCallException("reload() is not overridden by the class");
		}
		
		public function toArray(array $propertiesToExclude = [])
		{
			$propertiesToExclude[] = "parameters";
			
			$properties = get_object_vars($this);
			
			$exclude = function($property) use ($propertiesToExclude)
			{
				return !in_array($property, $propertiesToExclude);
			};
			
			return array_filter($properties, $exclude, ARRAY_FILTER_USE_KEY);
		}
		
		protected function importFrom($source, array $propertiesToExclude = [])
		{
			$propertiesToExclude[] = "parameters";
			
			if(!empty($source))
			{
				$properties = get_class_vars(get_class($this));
				
				foreach($properties as $property => $value)
				{
					if(!in_array($property, $propertiesToExclude))
					{
						if(is_object($source) && isset($source->{$property}))
						{
							$this->{$property} = $source->{$property};
							continue;
						}
						
						if(is_array($source) && isset($source{$property}))
						{
							$this->{$property} = $source{$property};
							continue;
						}
					}
				}
				
				$source = null;
			}
		}
	}
}