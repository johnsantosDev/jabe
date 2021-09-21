<?php

namespace Tests\Xml\TestModel;

use BpmPlatform\Model\Xml\ModelInstanceInterface;
use BpmPlatform\Model\Xml\Impl\ModelInstanceImpl;
use BpmPlatform\Model\Xml\Impl\Parser\AbstractModelParser;
use BpmPlatform\Model\Xml\Instance\DomDocumentInterface;

class TestModelParser extends AbstractModelParser
{
    private const JAXP_SCHEMA_SOURCE = "http://java.sun.com/xml/jaxp/properties/schemaSource";
    private const JAXP_SCHEMA_LANGUAGE = "http://java.sun.com/xml/jaxp/properties/schemaLanguage";
    private const SCHEMA_LOCATION = "tests/Xml/TestModel/Resources/TestModel/Testmodel.xsd";
    private const W3C_XML_SCHEMA = "http://www.w3.org/2001/XMLSchema";
    private const TEST_NS = "http://test.org/animals";

    public function __construct()
    {
        $this->addSchema(self::TEST_NS, file_get_contents(self::SCHEMA_LOCATION));
    }

    protected function createModelInstance(DomDocumentInterface $document): ModelInstanceInterface
    {
        return new ModelInstanceImpl(TestModel::getTestModel(), TestModel::getModelBuilder(), $document);
    }
}