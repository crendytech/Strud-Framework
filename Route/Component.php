<?php


namespace Strud\Route
{
	
	use Strud\Utils\ArrayUtil;
	use Strud\Utils\StringUtil;
	
	abstract class Component
	{
		const DEFAULT_SUB_COMPONENT = "Display";
		
		public function __get($name)
		{
			$function = "get" . ucfirst($name);
			
			return $this->{$function}();
		}
		
		public function __set($name, $value)
		{
			$function = "set" . ucfirst($name);
			
			return $this->{$function}($value);
		}
		
		public function __call($name, $arguments)
		{
			if(StringUtil::startsWith("get", $name))
			{
				return $this->handleGetter(StringUtil::stripFromStart("get", $name));
			}
			elseif(StringUtil::startsWith("set", $name))
			{
				return $this->handleSetter(StringUtil::stripFromStart("set", $name), $arguments);
			}
			else
			{
				return $this->handleUnknownCall($name, $arguments);
			}
		}
		
		private function handleUnknownCall($name, $arguments)
		{
			if(ArrayUtil::isEmpty($arguments))
			{
				return $this->handleGetter($name);
			}
			else
			{
				return $this->handleSetter($name, $arguments);
			}
		}
		
		private function handleGetter($property)
		{
			if(property_exists($this, lcfirst($property)))
			{
				return $this->{lcfirst($property)};
			}
			
			return null;
		}
		
		private function handleSetter($property, $arguments)
		{
			if(property_exists($this, lcfirst($property)))
			{
				$this->{lcfirst($property)} = $arguments[0];
			}
			
			return $this;
		}
	}
}


