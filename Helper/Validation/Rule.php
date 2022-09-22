<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 7/25/2017
 * Time: 9:10 AM
 */

namespace Strud\Helper\Validation;


class Rule
{
    /**
     * @var string
     */
    private $message;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $rule;

    public function __construct($name, $rule, $message = "")
    {
        $this->name = $name;
        $this->rule = $rule;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param string $rule
     */
    public function setRule($rule)
    {
        $this->rule = $rule;
    }

    /**
     * @param string $message
     */
    public function setMessage($rule)
    {
        $this->rule = $rule;
    }
}