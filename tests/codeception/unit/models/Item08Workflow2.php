<?php
namespace tests\codeception\unit\models;

use Yii;
use fproject\workflow\core\IWorkflowSource;

class Item08Workflow2 implements IWorkflowSource
{
    public function getDefinition($model) {
        return [ 
            'initialStatusId' => 'success',
            'status' => [
                'success' => [
                    'transition' => ['onHold']
                ],
                'onHold' => [
                    'transition' => ['success']
                ],
            ]
        ];
    }
}