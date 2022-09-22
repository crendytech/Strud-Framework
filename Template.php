<?php


namespace Strud
{
	
	use Strud\Collection\ArrayMap;
	use Strud\Template\Configuration;
	use Strud\Template\Engine;
	
	class Template
	{
		/**
		 * @var Configuration
		 */
		private $configuration;
		/**
		 * @var ArrayMap
		 */
		private $entries;
		/**
		 * @var Engine
		 */
		private $engine;
		
		public function __construct(Configuration $configuration, Engine $engine)
		{
			$this->configuration = $configuration;
			$this->engine = $engine;
			$this->entries = new ArrayMap();
		}
		
		/**
		 * @param $name
		 * @param $value
		 * @return $this
		 */
		public function put($name, $value)
		{
			$this->entries->put($name, $value);
			
			return $this;
		}
		
		/**
		 * @param $name
		 * @return $this
		 */
		public function remove($name)
		{
			$this->entries->remove($name);
			
			return $this;
		}
		
		/**
		 * @param $directory
		 * @return $this
		 */
		public function setDirectory($directory)
		{
			$this->configuration->setDirectory($directory);
			
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getDirectory()
		{
			return $this->configuration->getDirectory();
		}
		
		/**
		 * @return string
		 */
		public function render()
		{
			return $this->engine->process($this->configuration, $this->entries->getSource());
		}
	}
}


