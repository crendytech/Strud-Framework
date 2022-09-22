<?php


namespace Strud\Registry\Driver
{
	
	use Strud\Registry\Driver;
	
	class Session implements Driver
	{
		public function __construct()
		{
            // use cookies to store session IDs
            ini_set('session.use_cookies', 1);
            // use cookies only (do not send session IDs in URLs)
            ini_set('session.use_only_cookies', 1);
            // do not send session IDs in URLs
            ini_set('session.use_trans_sid', 0);
			session_start();
		}
		
		public function has($key)
		{
			return !empty($_SESSION[$key]);
		}
		
		public function get($key, $defaultValue = null)
		{
			return $this->has($key) ? $_SESSION[$key] : $defaultValue;
		}
		
		public function put($key, $value, $expires = null)
		{
			$_SESSION[$key] = $value;
		}
		
		public function remove($key)
		{
			unset($_SESSION[$key]);
		}

        public function removeAll()
        {
            $_SESSION[] = array();
        }

		public function regenerate($bool){
            ($bool == true)? session_regenerate_id(true) : session_regenerate_id();
        }
	}
}


