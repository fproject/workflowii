<?php
namespace fproject\workflow\core;

interface IAbstractWorkflowItem
{
    /**
     *
     * @param string $paramName when null the method returns the complet metadata array, otherwise it returns the
     * value of the corresponding metadata.
     * @param string $defaultValue
     *
     * @return string
     *
     * @throws WorkflowException
     */
    public function getMetadata($paramName = null, $defaultValue = null);
}
