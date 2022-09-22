<?php


namespace Strud\Route\Component
{

    use Factory\DatabaseFactory;
    use Strud\Helper\Authentication\Authentication;
    use Strud\Helper\Response\Flash;
    use Strud\Registry;
	use Strud\Request;
	use Strud\Response;
	use Strud\Route\Component;
	
	abstract class Controller extends Component
	{
		/**
		 * @var mixed
		 */
		protected $parameters;
		
		/**
		 * @var Model
		 */
		protected $model;
		
		/**
		 * @var Request
		 */
		protected $request;
		
		/**
		 * @var Response
		 */
		protected $response;

        /**
         * @var Flash
         */
        protected $flash;
		
		/**
		 * @var Registry
		 */
		protected $registry;

        /**
         * @var Authentication
         */
		protected $auth;
		
		public function __construct(Model $model, $parameters = [])
		{
			$this->model = $model;
			$this->parameters = $parameters;
			$this->auth = new Authentication(DatabaseFactory::createConnection());
			$this->request = Request::getInstance();
			$this->registry = Registry::getInstance();
			$this->response = Response::getInstance();
			$this->flash = Flash::getInstance();
		}
		
		abstract public function run();


		public function getRules(){}

		protected function redirectTo($url)
		{
			header("Location: {$url}");
			exit;
		}
		
		/**
		 * @return Response
		 */
		public function getResponse()
		{
			return $this->response;
		}
		
		protected function _toArray($object)
		{
			if(!is_object($object))
			{
				$object = (object) $object;
				$this->_toArray($object);
			}
			
			if(is_object($object))
			{
				$arr = [];
				foreach($object as $key => $value)
				{
					if($key != "parameters")
					{
					     $arr[$key] = $value;
					}
				}
				return $arr;
			}
		}
	}
}


