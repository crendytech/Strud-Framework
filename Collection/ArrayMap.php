<?php


namespace Strud\Collection
{
	class ArrayMap
	{
		private $source;
		
		public function __construct(array $source = [])
		{
			$this->source = $source;
		}
		
		public function get($key, $defaultValue = null)
		{
			return $this->keyExists($key) ? $this->source[$key] : $defaultValue;
		}
		
		public function put($key, $value)
		{
			$this->source[$key] = $value;
		}
		
		public function remove($key)
		{
			unset($key, $this->source);
		}
		
		public function keyExists($key)
		{
			return key_exists($key, $this->source);
		}
		
		public function getKeys()
		{
			return array_keys($this->source);
		}
		
		public function getValues()
		{
			return array_values($this->source);
		}
		
		/**
		 * @return array
		 */
		public function getSource()
		{
			return $this->source;
		}
		
	}
}


