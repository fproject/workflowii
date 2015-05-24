<?php

namespace tests\codeception\unit\models;

use fproject\workflow\core\IWorkflowSource;

class DynamicItemWorkflowSource implements IWorkflowSource
{
    /**
     * @param Item00 $model
     * @return array|void
     */
    public function getDefinition($model)
    {
        $wfSourceClass = 'tests\codeception\unit\models\\'. $model->dynamicWorkflowId . 'Source';

        /** @var IWorkflowSource $wfSrc */
        $wfSrc = new $wfSourceClass();
        return $wfSrc->getDefinition($model);
    }
}