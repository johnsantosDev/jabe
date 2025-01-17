<?php

namespace Jabe\Impl\Bpmn\Parser;

use Jabe\Impl\Cfg\BpmnParseFactoryInterface;
use Jabe\Impl\El\ExpressionManagerInterface;
use Sax\Parser;

class BpmnParser extends Parser
{
    /**
    * The BPMN 2.0 namespace
    */
    public const BPMN20_NS = "http://www.omg.org/spec/BPMN/20100524/MODEL";

    /**
     * The location of the BPMN 2.0 XML schema.
     */
    public const BPMN_20_SCHEMA_LOCATION = "src/Resources/BPMN20.xsd";

    /**
     * The namespace of the custom BPMN extensions.
     */
    public const BPMN_EXTENSIONS_NS = "http://activiti.org/bpmn";

    /**
     * The namespace of the custom BPMN extensions.
     */
    public const BPMN_EXTENSIONS_NS_PREFIX = "extension";

    /**
     * The namepace of the BPMN 2.0 diagram interchange elements.
     */
    public const BPMN_DI_NS = "http://www.omg.org/spec/BPMN/20100524/DI";

    public const BPMN_DI_NS_PREFIX = "bpmndi";

    /**
     * The namespace of the BPMN 2.0 diagram common elements.
     */
    public const BPMN_DC_NS = "http://www.omg.org/spec/DD/20100524/DC";

    public const BPMN_DC_NS_PREFIX = "bpmndc";

    /**
     * The namespace of the generic OMG DI elements (don't ask me why they didnt use the BPMN_DI_NS ...)
     */
    public const OMG_DI_NS = "http://www.omg.org/spec/DD/20100524/DI";

    public const OMG_DI_NS_PREFIX = "omgdi";

    /**
     * The Schema-Instance namespace.
     */
    public const XSI_NS = "http://www.w3.org/2001/XMLSchema-instance";

    public const XSI_NS_PREFIX = "xsi";

    protected $expressionManager;
    protected $parseListeners = [];

    protected $bpmnParseFactory;

    public function __construct(ExpressionManagerInterface $expressionManager, BpmnParseFactoryInterface $bpmnParseFactory)
    {
        $this->expressionManager = $expressionManager;
        $this->bpmnParseFactory = $bpmnParseFactory;
    }

    /**
     * Creates a new BpmnParse instance that can be used
     * to parse only one BPMN 2.0 process definition.
     */
    public function createParse(): BpmnParse
    {
        return $this->bpmnParseFactory->createBpmnParse($this);
    }

    public function getExpressionManager(): ExpressionManagerInterface
    {
        return $this->expressionManager;
    }

    public function setExpressionManager(ExpressionManagerInterface $expressionManager): void
    {
        $this->expressionManager = $expressionManager;
    }

    public function getParseListeners(): array
    {
        return $this->parseListeners;
    }

    public function setParseListeners(array $parseListeners): void
    {
        $this->parseListeners = $parseListeners;
    }
}
