<?php

namespace tests\unit\workflow\factories\assoc;

use fproject\workflow\core\ArrayWorkflowItemFactory;
use fproject\workflow\serialize\SimpleArrayDeserializer;
use Yii;
use yii\codeception\TestCase;

/**
 * @property SimpleArrayDeserializer deserializer
 */
class SimpleArrayDeserializerTest extends TestCase
{
	use \Codeception\Specify;
	
	public $src;
	
	protected function setUp()
	{
		parent::setUp();
		Yii::$app->set('deserializer',[
			'class' => SimpleArrayDeserializer::className(),
		]);
		
		$this->src = new ArrayWorkflowItemFactory([
			'deserializer' => 'deserializer'
		]);
	}

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

    public function getDeserializer()
    {
        return Yii::$app->deserializer;
    }

	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage Workflow definition must be provided as an array
	 */
	public function testParseInvalidType()
	{
		$this->deserializer->deserialize('WID',null,$this->src);
	}
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage Missing argument : workflow Id
	 */
	public function testMissingWorkflowId()
	{
		$this->deserializer->deserialize('',null,$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage Workflow definition must be provided as associative array
	 */
	public function testNonAssociativeArray1()
	{
		$this->deserializer->deserialize('WID',['a'],$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage Workflow definition must be provided as associative array
	 */
	public function testNonAssociativeArray2()
	{
		$this->deserializer->deserialize('WID',['a'=> [], 'b'],$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage Status must belong to workflow : EXT/a
	 */
	public function testExternalStatusError()
	{
		$this->deserializer->deserialize('WID',[
			'EXT/a' => [],
			'b' => []
		],$this->src);
	}
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage  Associative array not supported (status : WID/a)
	 */
	public function testEndStatusAssociativeError()
	{
		$this->deserializer->deserialize('WID',[
			'a' => ['b' => 'value'],
			'b' => []
		],$this->src);
	}
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage End status list must be an array for status  : WID/a
	 */
	public function testEndStatusTypeNotSupported()
	{
		$this->deserializer->deserialize('WID',[
			'a' => 4,
			'b' => []
		],$this->src);
	}		
	
	public function testParseArraySuccess()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'a' => ['b','c'],
			'b' => ['a'],
			'c' => []
		],$this->src);
				
		verify('status "a" is set ', array_key_exists('WID/a',($workflow['status'])) )->true();
		verify('status "b" is set ', array_key_exists('WID/b',($workflow['status'])) )->true();
		verify('status "c" is set ', array_key_exists('WID/c',($workflow['status'])) )->true();
		
		verify('status transitions from "a" are set ', $workflow['status']['WID/a']['transition'])->equals(['WID/b'=>[],'WID/c'=>[]]);
		verify('status transitions from "b" are set ', $workflow['status']['WID/b']['transition'])->equals(['WID/a'=>[]]);
		verify('status transitions from "a" are set ', $workflow['status']['WID/c'])->equals(null);
	}		
	
	public function testParseStringSuccess()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'a' => 'b,c',
			'b' => 'a',
			'c' => []
		],$this->src);
				
		verify('status "a" is set ', array_key_exists('WID/a',($workflow['status'])) )->true();
		verify('status "b" is set ', array_key_exists('WID/b',($workflow['status'])) )->true();
		verify('status "c" is set ', array_key_exists('WID/c',($workflow['status'])) )->true();
		
		verify('status transitions from "a" are set ', $workflow['status']['WID/a']['transition'])->equals(['WID/b'=>[],'WID/c'=>[]]);
		verify('status transitions from "b" are set ', $workflow['status']['WID/b']['transition'])->equals(['WID/a'=>[]]);
		verify('status transitions from "a" are set ', $workflow['status']['WID/c'])->equals(null);
	}
}
