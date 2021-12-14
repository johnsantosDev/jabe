<?php

namespace BpmPlatform\Engine\Impl\Pvm\Runtime\Operation;

use BpmPlatform\Engine\Impl\Pvm\{
    PvmException,
    PvmLogger
};
use BpmPlatform\Engine\Impl\Pvm\Delegate\ActivityBehaviorInterface;
use BpmPlatform\Engine\Impl\Pvm\Process\ActivityImpl;
use BpmPlatform\Engine\Impl\Pvm\Runtime\{
    CallbackInterface,
    PvmExecutionImpl
};
use BpmPlatform\Engine\Impl\Util\ActivityBehaviorUtil;
use BpmPlatform\Engine\Impl\Core\Delegate\CoreActivityBehaviorInterface;
use BpmPlatform\Engine\Impl\Core\Instance\CoreExecution;

class PvmAtomicOperationActivityExecute implements PvmAtomicOperationInterface
{
    use BasePvmAtomicOperationTrait;

    //private final static PvmLogger LOG = PvmLogger.PVM_LOGGER;

    public function isAsync(PvmExecutionImpl $execution): bool
    {
        return false;
    }

    public function execute(PvmExecutionImpl $execution): void
    {
        $execution->activityInstanceStarted();

        $execution->continueIfExecutionDoesNotAffectNextOperation(new class () implements CallbackInterface {
            public function callback($execution)
            {
                if ($execution->getActivity()->isScope()) {
                    $execution->dispatchEvent(null);
                }
                return null;
            }
        }, new class ($this) implements CallbackInterface {

            private $op;

            public function __construct(PvmAtomicOperationActivityExecute $op)
            {
                $this->op = $op;
            }

            public function callback($execution)
            {
                $activityBehavior = $op->getActivityBehavior($execution);

                $activity = $execution->getActivity();
                //LOG.debugExecutesActivity(execution, activity, activityBehavior.getClass().getName());

                try {
                    $activityBehavior->execute($execution);
                } catch (\Exception $e) {
                    throw new PvmException("couldn't execute activity <" . $activity->getProperty("type") . " id=\"" . $activity->getId() . "\" ...>: " . $e->getMessage(), $e);
                }
                return null;
            }
        }, $execution);
    }

    public function getActivityBehavior(CoreExecution $execution): CoreActivityBehaviorInterface
    {
        return ActivityBehaviorUtil::getActivityBehavior($execution);
    }

    public function getCanonicalName(): string
    {
        return "activity-execute";
    }

    public function isAsyncCapable(): bool
    {
        return false;
    }
}