<?php

namespace BpmPlatform\Engine\Impl\Pvm\Runtime\Operation;

use BpmPlatform\Engine\Impl\Pvm\PvmActivityInterface;
use BpmPlatform\Engine\Impl\Pvm\Runtime\{
    ScopeInstantiationContext,
    InstantiationStack,
    PvmExecutionImpl
};

class PvmAtomicOperationActivityInitStack implements PvmAtomicOperationInterface
{
    protected $operationOnScopeInitialization;

    public function __construct(PvmAtomicOperationInterface $operationOnScopeInitialization)
    {
        $this->operationOnScopeInitialization = $operationOnScopeInitialization;
    }

    public function getCanonicalName(): string
    {
        return "activity-stack-init";
    }

    public function execute(PvmExecutionImpl $execution): void
    {
        $executionStartContext = $execution->getScopeInstantiationContext();

        $instantiationStack = $executionStartContext->getInstantiationStack();
        //$activityStack = $instantiationStack->getActivities();
        $currentActivity = $instantiationStack->remove(0);

        $propagatingExecution = $execution;
        if ($currentActivity->isScope()) {
            $propagatingExecution = $execution->createExecution();
            $execution->setActive(false);
            $propagatingExecution->setActivity($currentActivity);
            $propagatingExecution->initialize();
        } else {
            $propagatingExecution->setActivity($currentActivity);
        }

        // notify listeners for the instantiated activity
        $propagatingExecution->performOperation($operationOnScopeInitialization);
    }

    public function isAsync(PvmExecutionImpl $instance): bool
    {
        return false;
    }

    public function getStartContextExecution(PvmExecutionImpl $execution): PvmExecutionImpl
    {
        return $execution;
    }

    public function isAsyncCapable(): bool
    {
        return false;
    }
}
