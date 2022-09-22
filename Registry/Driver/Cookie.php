<?php


namespace Strud\Registry\Driver
{

    use Strud\Registry\Driver;
	
	class Cookie extends CookieHelper implements Driver
	{
		public function __construct()
        {
            parent::__construct();
        }

        public function has($key)
		{
			return !empty($_COOKIE[$key]);
		}
		
		public function get($key, $defaultValue = null)
		{
			return $this->has($key) ? $_COOKIE[$key] : $defaultValue;
		}
		
		public function put($key, $value, $additional = null)
		{
		    $this->setName($key);
		    $this->setValue($key);
		    if(is_array($additional))
            {
                if(array_key_exists("expires", $additional))
                {
                    $this->setExpiryTime($additional['expires']);
                }
                if(array_key_exists("params", $additional))
                {
                    $params = $additional['params'];
                    if (!empty($params['path'])) {
                        $this->setPath($params['path']);
                    }

                    if (!empty($params['domain'])) {
                        $this->setDomain($params['domain']);
                    }

                    $this->setHttpOnly($params['httponly']);
                    $this->setSecureOnly($params['secure']);

                    return $this->save();
                }
            }
//			setcookie($key, $value, $additional);
		}
		
		public function remove($key)
		{
			unset($_COOKIE[$key]);
		}
	}
}


