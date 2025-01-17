<?php

namespace Jabe\Impl\Core\Variable\Mapping;

use Jabe\Impl\Core\Variable\Mapping\Value\ParameterValueProviderInterface;
use Jabe\Impl\Core\Variable\Scope\AbstractVariableScope;

class OutputParameter extends IoParameter
{
    //private final static CoreLogger LOG = CoreLogger.CORE_LOGGER;

    public function __construct(?string $name, ParameterValueProviderInterface $valueProvider)
    {
        parent::__construct($name, $valueProvider);
    }

    public function execute(AbstractVariableScope $innerScope, ?AbstractVariableScope $outerScope = null): void
    {
        if ($outerScope === null) {
            $outerScope = $innerScope->getParentVariableScope();
        }

        // get value from inner scope
        $value = $this->valueProvider->getValue($innerScope);

        //LOG.debugMappingValuefromInnerScopeToOuterScope(value, innerScope, name, outerScope);

        // set variable in outer scope
        $outerScope->setVariable($this->name, $value);
    }
}
