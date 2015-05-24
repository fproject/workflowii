<?php
namespace tests\codeception\unit\models;

use Yii;
use fproject\workflow\core\IWorkflowSource;

class Item08Workflow1Source implements IWorkflowSource
{
    public function getDefinition($model) {
        return [ 
            'initialStatusId' => 'draft',
            'status' => [
                'draft' => [
                    'transition' => ['correction']
                ],
                'correction' => [
                    'transition' => ['draft','ready']
                ],
                'ready' => [
                    'transition' => ['draft', 'correction', 'published']
                ],
                'published' => [
                    'transition' => ['ready', 'archived']
                ],
                'archived' => [
                    'transition' => ['ready']
                ]
            ]
        ];
    }
}