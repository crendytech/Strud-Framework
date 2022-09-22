<?php

namespace Strud
{

    use Exception\NotFoundException;
    use Exception\PermissionDeniedException;
    use Fadaka\Error\NotFound;
    use Fadaka\Error\PermissonDenied;
    use Strud\Request\Method;
    use Strud\Route\Component\Controller;
    use Strud\Route\Component\NullModel;
    use Strud\Route\Component\NullView;

    abstract class Application extends Controller
    {

        /**
         * @var Application
         */
        protected static $instance;

        protected $route;

        public static function getInstance()
        {
            if (!static::$instance) {
                static::$instance = new static();
            }

            return static::$instance;
        }

        public function __construct()
        {
            $model = new NullModel();

            parent::__construct($model, new NullView($model));

            $handler = $this->request->using(Method::GET);

            $url = $handler->get("url", "index");

            $this->route = $this->createRouter()->find($url);
        }

        public function run()
        {
            if ($this->route) {
                if ($this->route->hasController()) {
                    $controller = $this->route->getController();
                    try{
                        $controller->run();
                    }catch (PermissionDeniedException $e)
                    {
                        new PermissonDenied($e->getMessage());
                    }catch (NotFoundException $e)
                    {
                        new NotFound($e->getMessage(), $e->getType());
                    }

                    $handler = $this->request->using(Method::POST);

                    if ($this->request->isAjax() && $handler->get("_target") != Route::VIEW) {
                        echo $controller->getResponse()->toJSON();

                        return;
                    }
                }

                if ($this->route->hasView()) {
                    $handler = $this->request->using(Method::ANY);

                    if ($handler->get("_target") === Route::VIEW) {
                        echo json_encode(["html" => $this->route->getView()->render()]);
                        return;
                    } else {
                        echo $this->route->getView()->render();
                    }
                }
            }
        }

        /**
         * @return Router
         */
        abstract protected function createRouter();
    }
}

