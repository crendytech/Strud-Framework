<?php


namespace Strud\Database
{
	class Configuration
	{
		/**
		 * @var string
		 */
		private $host;
		/**
		 * @var string
		 */
		private $database;
		/**
		 * @var string
		 */
		private $username;
		/**
		 * @var string
		 */
		private $password;
		
		/**
		 * Configuration constructor.
		 * @param $host
		 * @param $database
		 * @param $username
		 * @param $password
		 */
		public function __construct($host, $database, $username, $password)
		{
			$this->host = $host;
			$this->database = $database;
			$this->username = $username;
			$this->password = $password;
		}
		
		/**
		 * @return string
		 */
		public function getHost()
		{
			return $this->host;
		}
		
		/**
		 * @param string $host
		 * @return Configuration
		 */
		public function setHost($host)
		{
			$this->host = $host;
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getDatabase()
		{
			return $this->database;
		}
		
		/**
		 * @param string $database
		 * @return Configuration
		 */
		public function setDatabase($database)
		{
			$this->database = $database;
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getUsername()
		{
			return $this->username;
		}
		
		/**
		 * @param string $username
		 * @return Configuration
		 */
		public function setUsername($username)
		{
			$this->username = $username;
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getPassword()
		{
			return $this->password;
		}
		
		/**
		 * @param string $password
		 * @return Configuration
		 */
		public function setPassword($password)
		{
			$this->password = $password;
			return $this;
		}
		
	}
}


