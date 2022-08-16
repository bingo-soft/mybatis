<?php

namespace Tests\Parsing;

use PHPUnit\Framework\TestCase;
use MyBatis\Parsing\XPathParser;

class XPathParserTest extends TestCase
{
    private $resource = "tests/Resources/nodelet_test.xml";

    public function testConstructorWithInputStreamValidationVariablesEntityResolver(): void
    {
        $parser = new XPathParser(fopen($this->resource, 'r+'), false, null, null);
        $this->testEvalMethod($parser);
    }

    public function testConstructorWithInputStreamValidationVariables(): void
    {
        $parser = new XPathParser(fopen($this->resource, 'r+'), false, null);
        $this->testEvalMethod($parser);
    }

    public function testConstructorWithInputStreamValidation(): void
    {
        $parser = new XPathParser(fopen($this->resource, 'r+'), false);
        $this->testEvalMethod($parser);
    }

    public function testConstructorWithInputStream(): void
    {
        $parser = new XPathParser(fopen($this->resource, 'r+'));
        $this->testEvalMethod($parser);
    }

    public function testConstructorWithDocumentValidationVariablesEntityResolver(): void
    {
        $fd = fopen($this->resource, 'r+');
        $meta = stream_get_meta_data($fd);
        $xmlString = fread($fd, filesize($meta['uri']));
        $dom = new \DOMDocument();
        $dom->loadXML($xmlString);

        $parser = new XPathParser($dom, false, null, null);
        $this->testEvalMethod($parser);
        fclose($fd);
    }

    public function testConstructorWithPathValidationVariablesEntityResolver(): void
    {
        $parser = new XPathParser($this->resource, false, null, null);
        $this->testEvalMethod($parser);
    }

    private function testEvalMethod(XPathParser $parser): void
    {
        $this->assertEquals(1970, $parser->evalLong("/employee/birth_date/year"));
        $this->assertEquals(1970, $parser->evalNode("/employee/birth_date/year")->getLongBody());
        $this->assertEquals(6, $parser->evalShort("/employee/birth_date/month"));
        $this->assertEquals(15, $parser->evalInteger("/employee/birth_date/day"));
        $this->assertEquals(15, $parser->evalNode("/employee/birth_date/day")->getIntBody());
        $this->assertEquals(5.8, $parser->evalFloat("/employee/height"));
        $this->assertEquals(5.8, $parser->evalNode("/employee/height")->getFloatBody());
        $this->assertEquals(5.8, $parser->evalDouble("/employee/height"));
        $this->assertEquals(5.8, $parser->evalNode("/employee/height")->getDoubleBody());
        $this->assertEquals(5.8, $parser->evalNode("/employee")->evalDouble("height"));
        $this->assertEquals('${id_var}', $parser->evalString("/employee/@id"));
        $this->assertEquals('${id_var}', $parser->evalNode("/employee/@id")->getStringBody());
        $this->assertEquals('${id_var}', $parser->evalNode("/employee")->evalString("@id"));
        $this->assertEquals(true, $parser->evalBoolean("/employee/active"));
        $this->assertEquals(true, $parser->evalNode("/employee/active")->getBooleanBody());
        $this->assertEquals(true, $parser->evalNode("/employee")->evalBoolean("active"));
        $this->assertEquals(EnumTest::YES, $parser->evalNode("/employee/active")->getEnumAttribute(EnumTest::class, "bot"));
        $this->assertEquals(3.2, $parser->evalNode("/employee/active")->getFloatAttribute("score"));
        $this->assertEquals(3.2, $parser->evalNode("/employee/active")->getDoubleAttribute("score"));
        $this->assertEquals('<id>${id_var}</id>', trim($parser->evalNode("/employee/@id")));
        $this->assertCount(7, $parser->evalNodes("/employee/*"));
        $node = $parser->evalNode("/employee/height");
        $this->assertEquals("employee/height", $node->getPath());
        $this->assertEquals('employee[${id_var}]_height', $node->getValueBasedIdentifier());
    }
}
