<?php

namespace Jabe\Impl\Cmd;

use Jabe\Impl\MessageCorrelationBuilderImpl;
use Jabe\Impl\Context\Context;
use Jabe\Impl\Interceptor\CommandContext;
use Jabe\Impl\Persistence\Entity\{
    ExecutionEntity,
    ExecutionVariableSnapshotObserver,
    ProcessDefinitionEntity
};
use Jabe\Impl\Runtime\{
    CorrelationHandlerResult,
    MessageCorrelationResultImpl
};
use Jabe\Runtime\{
    MessageCorrelationResultType,
    ProcessInstanceInterface
};
use Jabe\Variable\{
    VariableMapInterface,
    Variables
};

abstract class AbstractCorrelateMessageCmd
{
    protected $messageName;

    protected $builder;

    protected $variablesListener;
    protected bool $variablesEnabled = false;
    protected bool $deserializeVariableValues = false;

    /**
     * Initialize the command with a builder
     *
     * @param builder
     */
    public function __construct(MessageCorrelationBuilderImpl $builder, ?bool $variablesEnabled = null, ?bool $deserializeVariableValues = null)
    {
        $this->builder = $builder;
        $this->messageName = $builder->getMessageName();
        if ($variablesEnabled !== null) {
            $this->variablesEnabled = $variablesEnabled;
            $this->deserializeVariableValues = $deserializeVariableValues;
        }
    }

    protected function triggerExecution(CommandContext $commandContext, CorrelationHandlerResult $correlationResult): void
    {
        $executionId = $correlationResult->getExecutionEntity()->getId();
        $processVariables = $this->builder->getPayloadProcessInstanceVariables();
        $localVariables = $this->builder->getPayloadProcessInstanceVariablesLocal();
        $command = new MessageEventReceivedCmd($this->messageName, $executionId, ($processVariables ? $processVariables->asValueMap() : []), ($localVariables ? $localVariables->asValueMap() : []), $this->builder->isExclusiveCorrelation());
        $command->execute($commandContext);
    }

    protected function instantiateProcess(CommandContext $commandContext, CorrelationHandlerResult $correlationResult): ProcessInstanceInterface
    {
        $processDefinitionEntity = $correlationResult->getProcessDefinitionEntity();

        $messageStartEvent = $processDefinitionEntity->findActivity($correlationResult->getStartEventActivityId());
        $processInstance = $processDefinitionEntity->createProcessInstance($this->builder->getBusinessKey(), null, $messageStartEvent);

        if ($this->variablesEnabled) {
            $this->variablesListener = new ExecutionVariableSnapshotObserver($processInstance, false, $this->deserializeVariableValues);
        }

        $startVariables = $this->resolveStartVariables();

        $processInstance->start($startVariables);

        return $processInstance;
    }

    protected function checkAuthorization(CorrelationHandlerResult $correlation): void
    {
        $commandContext = Context::getCommandContext();

        foreach ($commandContext->getProcessEngineConfiguration()->getCommandCheckers() as $checker) {
            if (MessageCorrelationResultType::EXECUTION == $correlation->getResultType()) {
                $execution = $correlation->getExecutionEntity();
                $checker->checkUpdateProcessInstanceById($execution->getProcessInstanceId());
            } else {
                $definition = $correlation->getProcessDefinitionEntity();
                $checker->checkCreateProcessInstance($definition);
            }
        }
    }

    protected function createMessageCorrelationResult(CommandContext $commandContext, CorrelationHandlerResult $handlerResult): MessageCorrelationResultImpl
    {
        $resultWithVariables = new MessageCorrelationResultImpl($handlerResult);
        if (MessageCorrelationResultType::EXECUTION == $handlerResult->getResultType()) {
            $execution = $this->findProcessInstanceExecution($commandContext, $handlerResult);
            if ($this->variablesEnabled && $execution !== null) {
                $this->variablesListener = new ExecutionVariableSnapshotObserver($execution, false, $this->deserializeVariableValues);
            }
            $this->triggerExecution($commandContext, $handlerResult);
        } else {
            $instance = $this->instantiateProcess($commandContext, $handlerResult);
            $resultWithVariables->setProcessInstance($instance);
        }

        if ($this->variablesListener !== null) {
            $resultWithVariables->setVariables($this->variablesListener->getVariables());
        }

        return $resultWithVariables;
    }

    protected function findProcessInstanceExecution(CommandContext $commandContext, CorrelationHandlerResult $handlerResult): ExecutionEntity
    {
        $execution = $commandContext->getExecutionManager()->findExecutionById($handlerResult->getExecution()->getProcessInstanceId());
        return $execution;
    }

    protected function resolveStartVariables(): VariableMapInterface
    {
        $mergedVariables = Variables::createVariables();
        $mergedVariables->putAll($this->builder->getPayloadProcessInstanceVariables());
        $mergedVariables->putAll($this->builder->getPayloadProcessInstanceVariablesLocal());
        return $mergedVariables;
    }
}
