<?php

namespace BpmPlatform\Model\Bpmn\Impl\Instance\Extension;

use BpmPlatform\Model\Xml\ModelBuilder;
use BpmPlatform\Model\Xml\Instance\ModelElementInstanceInterface;
use BpmPlatform\Model\Xml\Impl\Instance\ModelTypeInstanceContext;
use BpmPlatform\Model\Xml\Type\ModelTypeInstanceProviderInterface;
use BpmPlatform\Model\Bpmn\Impl\BpmnModelConstants;
use BpmPlatform\Model\Bpmn\Impl\Instance\ErrorEventDefinitionImpl as BaseErrorEventDefinitionImpl;
use BpmPlatform\Model\Bpmn\Instance\Extension\ErrorEventDefinitionInterface;

class ErrorEventDefinitionImpl extends BaseErrorEventDefinitionImpl implements ErrorEventDefinitionInterface
{
    protected static $expressionAttribute;

    public static function registerType(ModelBuilder $modelBuilder): void
    {
        $typeBuilder = $modelBuilder->defineType(
            ErrorEventDefinitionInterface::class,
            BpmnModelConstants::EXTENSION_ELEMENT_ERROR_EVENT_DEFINITION
        )
        ->namespaceUri(BpmnModelConstants::EXTENSION_NS)
        ->instanceProvider(
            new class implements ModelTypeInstanceProviderInterface
            {
                public function newInstance(ModelTypeInstanceContext $instanceContext): ModelElementInstanceInterface
                {
                    return new ErrorEventDefinitionImpl($instanceContext);
                }
            }
        );

        self::$expressionAttribute = $typeBuilder->stringAttribute(BpmnModelConstants::EXTENSION_ATTRIBUTE_EXPRESSION)
        ->namespace(BpmnModelConstants::EXTENSION_NS)
        ->build();

        $typeBuilder->build();
    }

    public function getExpression(): string
    {
        return self::$expressionAttribute->getValue($this);
    }

    public function setExpression(string $expression): void
    {
        self::$expressionAttribute->setValue($this, $expression);
    }
}
