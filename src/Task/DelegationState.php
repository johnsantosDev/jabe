<?php

namespace Jabe\Task;

class DelegationState
{
    /**
     * The owner delegated the task and wants to review the result
     * after the assignee has resolved the task.  When the assignee
     * completes the task, the task is marked as {@link #RESOLVED} and
     * sent back to the owner. When that happens, the owner is set as
     * the assignee so that the owner gets this task back in the ToDo
     */
    public const PENDING = "Pending";

    /**
     * The assignee has resolved the task, the assignee was set to the owner
     * again and the owner now finds this task back in the ToDo list for review.
     * The owner now is able to complete the task.
     */
    public const RESOLVED = "Resolved";
}
