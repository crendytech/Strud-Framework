<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 7/25/2017
 * Time: 8:19 AM
 */

namespace Strud\Helper\Validation;

use Strud\Request;
use Strud\Response;
use Strud\Utils\ArrayUtil;
use Validation\Validation as Validate;

class Validation
{
    private static $instance;
    private $error;
    private $validator;

    /**
     * @return mixed
     */
    public static function getInstance()
    {
        if(!static::$instance)
        {
            static::$instance = new  static();
        }
        return static::$instance;
    }

    private function __construct()
    {
        $this->validator = Validate::getInstance();
    }

    public function validate($controller, Rules $rules)
    {
        $this->error = $this->validator->is_valid($_REQUEST, $rules->getRules(), false);
        if(is_array($this->error) && !empty($this->error))
        {
            if($controller->request->isAjax())
            {
                $controller->response->put("status", "error");
                $controller->response->put("message", $this->error);
                $controller->response->put("statusCode", "422");
//                exit(422);
            }else{
                $controller->flash->error($this->error);
            }
            return false;
        }else{
            return true;
        }
    }
}