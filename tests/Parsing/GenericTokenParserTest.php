<?php

namespace Tests\Parsing;

use PHPUnit\Framework\TestCase;
use MyBatis\Parsing\{
    GenericTokenParser,
    VariableTokenHandler as DefaultVariableTokenHandler
};

class GenericTokenParserTest extends TestCase
{
    public function testShouldDemonstrateGenericTokenReplacement(): void
    {
        $parser = new GenericTokenParser('${', '}', new VariableTokenHandler(
            [
                'first_name' =>  'James',
                'initial' => 'T',
                'last_name' => 'Kirk',
                'var{with}brace' => 'Hiya',
                '' => ''
            ]
        ));
        $this->assertEquals('James T Kirk reporting.', $parser->parse('${first_name} ${initial} ${last_name} reporting.'));
        $this->assertEquals('Hello captain James T Kirk', $parser->parse('Hello captain ${first_name} ${initial} ${last_name}'));
        $this->assertEquals('James T Kirk', $parser->parse('${first_name} ${initial} ${last_name}'));
        $this->assertEquals('JamesTKirk', $parser->parse('${first_name}${initial}${last_name}'));
        $this->assertEquals('{}JamesTKirk', $parser->parse('{}${first_name}${initial}${last_name}'));
        $this->assertEquals('}JamesTKirk', $parser->parse('}${first_name}${initial}${last_name}'));

        $this->assertEquals('}James{{T}}Kirk', $parser->parse('}${first_name}{{${initial}}}${last_name}'));
        $this->assertEquals('}James}T{Kirk', $parser->parse('}${first_name}}${initial}{${last_name}'));
        $this->assertEquals('}James}T{Kirk', $parser->parse('}${first_name}}${initial}{${last_name}'));
        $this->assertEquals('}James}T{Kirk{{}}', $parser->parse('}${first_name}}${initial}{${last_name}{{}}'));
        $this->assertEquals('}James}T{Kirk{{}}', $parser->parse('}${first_name}}${initial}{${last_name}{{}}${}'));

        $this->assertEquals('{$$something}JamesTKirk', $parser->parse('{$$something}${first_name}${initial}${last_name}'));
        $this->assertEquals('${', $parser->parse('${'));
        $this->assertEquals('${\\}', $parser->parse('${\\}'));
        $this->assertEquals('Hiya', $parser->parse('${var{with\\}brace}'));
        $this->assertEquals('', $parser->parse('${}'));
        $this->assertEquals('}', $parser->parse('}'));
        $this->assertEquals('Hello ${ this is a test.', $parser->parse('Hello ${ this is a test.'));
        $this->assertEquals('Hello } this is a test.', $parser->parse('Hello } this is a test.'));
        $this->assertEquals('Hello } ${ this is a test.', $parser->parse('Hello } ${ this is a test.'));
    }

    public function testShallNotInterpolateSkippedVaiables(): void
    {
        $parser = new GenericTokenParser('${', '}', new VariableTokenHandler([]));
        $this->assertEquals('${skipped} variable', $parser->parse('\\${skipped} variable'));
        $this->assertEquals('This is a ${skipped} variable', $parser->parse('This is a \\${skipped} variable'));
        $this->assertEquals('null ${skipped} variable', $parser->parse('${skipped} \\${skipped} variable'));
        $this->assertEquals('The null is ${skipped} variable', $parser->parse('The ${skipped} is \\${skipped} variable'));
    }

    public function testShallNotSkipZeroValue(): void
    {
        $parser = new GenericTokenParser('${', '}', new DefaultVariableTokenHandler([]));
        $this->assertEquals('0', $parser->parse('0'));
    }
}
