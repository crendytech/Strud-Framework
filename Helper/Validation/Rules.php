<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 7/25/2017
 * Time: 9:07 AM
 */

namespace Strud\Helper\Validation;

use Strud\Utils\StringUtil;

class Rules
{
    /**
     * @var \ArrayObject
     */
    protected $rules;

    public function __construct()
    {
        $this->rules = new \ArrayObject();
    }

    /**
     * @param Column $column
     * @return $this
     */
    public function addRule(Rule $rule)
    {
        $this->rules->append($rule);

        return $this;
    }

    /**
     * @param $name
     * @return Column
     * @throws \Error
     */
    public function getColumn($name)
    {
        foreach($this->rules as $rule)
        {
            if($rule->getName() == $name)
            {
                return $rule;
            }
        }

        throw new \Error("Column with name { $name } not found in table { $this->name }");
    }

    /**
     * @param $name
     * @return $this
     * @throws \Error
     */
    public function removeRules($name)
    {
        foreach($this->rules as $index => $rule)
        {
            if($rule->getName() == $name)
            {
                $this->rules->offsetUnset($index);

                return $this;
            }
        }

        throw new \Error("Field with name { $name } not found.");
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->rules->getArrayCopy();
    }

    /**
     * @return \ArrayObject
     */
    public function getRules()
    {
        $rules = [];
        foreach($this->rules as $index => $rule)
        {
            $rules[$rule->getName()] = $rule->getRule();
        }
        return $rules;
    }
}