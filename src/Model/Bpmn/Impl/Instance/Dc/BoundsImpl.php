<?php

namespace BpmPlatform\Model\Bpmn\Impl\Instance\Dc;

use BpmPlatform\Model\Xml\ModelBuilder;
use BpmPlatform\Model\Xml\Instance\ModelElementInstanceInterface;
use BpmPlatform\Model\Xml\Impl\Instance\ModelTypeInstanceContext;
use BpmPlatform\Model\Xml\Type\ModelTypeInstanceProviderInterface;
use BpmPlatform\Model\Bpmn\Impl\BpmnModelConstants;
use BpmPlatform\Model\Bpmn\Impl\Instance\BpmnModelElementInstanceImpl;
use BpmPlatform\Model\Bpmn\Instance\Dc\BoundsInterface;

class BoundsImpl extends BpmnModelElementInstanceImpl implements BoundsInterface
{
    protected static $xAttribute;
    protected static $yAttribute;
    protected static $widthAttribute;
    protected static $heightAttribute;

    public function __construct(ModelTypeInstanceContext $instanceContext)
    {
        parent::__construct($instanceContext);
    }

    public static function registerType(ModelBuilder $modelBuilder): void
    {
        $typeBuilder = $modelBuilder->defineType(
            BoundsInterface::class,
            BpmnModelConstants::DC_ELEMENT_BOUNDS
        )
        ->namespaceUri(BpmnModelConstants::DC_NS)
        ->instanceProvider(
            new class extends ModelTypeInstanceProviderInterface
            {
                public function newInstance(ModelTypeInstanceContext $instanceContext): ModelElementInstanceInterface
                {
                    return new BoundsImpl($instanceContext);
                }
            }
        );

        self::$xAttribute = $typeBuilder->doubleAttribute(BpmnModelConstants::DC_ATTRIBUTE_X)
        ->required()
        ->build();

        self::$yAttribute = $typeBuilder->doubleAttribute(BpmnModelConstants::DC_ATTRIBUTE_Y)
        ->required()
        ->build();

        self::$widthAttribute = $typeBuilder->doubleAttribute(BpmnModelConstants::DC_ATTRIBUTE_WIDTH)
        ->required()
        ->build();

        self::$heightAttribute = $typeBuilder->doubleAttribute(BpmnModelConstants::DC_ATTRIBUTE_HEIGHT)
        ->required()
        ->build();

        $typeBuilder->build();
    }

    public function getX(): float
    {
        return self::$xAttribute->getValue($this);
    }

    public function setX(float $x): void
    {
        self::$xAttribute->setValue($this, $x);
    }

    public function getY(): float
    {
        return self::$yAttribute->getValue($this);
    }

    public function setY(float $y): void
    {
        self::$yAttribute->setValue($this, $y);
    }

    public function getWidth(): float
    {
        return self::$widthAttribute->getValue($this);
    }

    public function setWidth(float $width): void
    {
        self::$widthAttribute->setValue($this, $width);
    }

    public function getHeight(): float
    {
        return self::$heightAttribute->getValue($this);
    }

    public function setHeight(float $height): void
    {
        self::$heightAttribute->setValue($this, $height);
    }
}