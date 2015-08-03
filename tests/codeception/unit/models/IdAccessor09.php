<?php
namespace tests\codeception\unit\models;

use fproject\workflow\core\IIdAccessor;
use Yii;
use fproject\workflow\core\IStatusAccessor;
use yii\base\Component;

class IdAccessor09 implements IIdAccessor
{
	public static $instanceCount = 0;

    /**
     * This method is invoked each time you want to read the Workflow ID stored in the model.
     *
     * @param Item09 $model
     * @return string the Workflow Id
     */
    public function readId($model)
    {
        return $model->dynamicWorkflowId;
    }

    /**
     * This method is invoked each time you want to update the Workflow ID stored in the model.
     *
     * @param Component $model
     * @param String $wfId
     * @return mixed
     */
    public function updateId($model, $wfId = null)
    {
        // TODO: Implement updateId() method.
    }
}