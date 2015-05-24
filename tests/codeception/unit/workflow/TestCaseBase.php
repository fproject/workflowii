<?php
namespace tests\unit\workflow;

use yii\codeception\TestCase;

class TestCaseBase extends TestCase
{
    use \Codeception\Specify;
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        else
        {
            return parent::__get($name);
        }
    }
}