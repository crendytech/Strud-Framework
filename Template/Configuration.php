<?php


namespace Strud\Template
{
	class Configuration
	{
		private $fileName;
		private $extension = "html";
		private $directory;
		private $cacheDirectory;
		private $cacheEnabled = true;

		public function __construct($fileName)
		{
			$this->fileName = $fileName;
		}
		
		public function getNameWithExtension()
		{
			return $this->fileName . "." . $this->extension;
		}
		
		/**
		 * @return mixed
		 */
		public function getExtension()
		{
			return $this->extension;
		}
		
		/**
		 * @param mixed $extension
		 */
		public function setExtension($extension)
		{
			$this->extension = $extension;
		}
		
		/**
		 * @return mixed
		 */
		public function getDirectory()
		{
			return $this->directory;
		}
		
		/**
		 * @param mixed $directory
		 */
		public function setDirectory($directory)
		{
			$this->directory = $directory;
		}
		
		/**
		 * @return mixed
		 */
		public function getCacheDirectory()
		{
			return $this->cacheDirectory;
		}
		
		/**
		 * @param mixed $cacheDirectory
		 */
		public function setCacheDirectory($cacheDirectory)
		{
			$this->cacheDirectory = $cacheDirectory;
		}

		/**
		 * @return bool
		 */
		public function getCacheEnabled()
		{
			return $this->cacheEnabled;
		}

		public function setCacheEnabled($value)
		{
			$this->cacheEnabled = $value;
		}
	}
}


