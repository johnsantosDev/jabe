<?php

namespace Jabe\Impl\Bpmn\Behavior;

use Jabe\Impl\Pvm\Delegate\ActivityExecutionInterface;

abstract class GatewayActivityBehavior extends FlowNodeActivityBehavior
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function lockConcurrentRoot(ActivityExecutionInterface $execution): void
    {
        $concurrentRoot = null;
        if ($execution->isConcurrent()) {
            $concurrentRoot = $execution->getParent();
        } else {
            $concurrentRoot = $execution;
        }
        $concurrentRoot->forceUpdate();
    }
}
