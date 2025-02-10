<?php

namespace Microsoft\Kiota\Serialization\Json\Tests;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Exception;
use GuzzleHttp\Psr7\Utils;
use Microsoft\Kiota\Abstractions\Enum;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Types\Date;
use Microsoft\Kiota\Abstractions\Types\Time;
use Microsoft\Kiota\Serialization\Json\JsonParseNode;
use Microsoft\Kiota\Serialization\Json\JsonParseNodeFactory;
use Microsoft\Kiota\Serialization\Json\Tests\Samples\Address;
use Microsoft\Kiota\Serialization\Json\Tests\Samples\MaritalStatus;
use Microsoft\Kiota\Serialization\Json\Tests\Samples\Person;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class JsonParseNodeTest extends TestCase
{
    private ParseNode $parseNode;
    private StreamInterface $stream;

    protected function setUp(): void {
        $this->stream = Utils::streamFor('{"@odata.type":"Missing","name":"Silas Kenneth","age":98,"height":123.122,"maritalStatus":"complicated,single","address":{"city":"Nairobi","street":"Luthuli"}}');
    }

    public function testGetIntegerValue(): void {
        $this->parseNode = new JsonParseNode(1243.78);
        $expected = $this->parseNode->getIntegerValue();
        $this->assertEquals(null, $expected);
        $this->parseNode = new JsonParseNode(1243);
        $this->assertEquals(1243, $this->parseNode->getIntegerValue());
    }

    public function testGetCollectionOfObjectValues(): void {
        $str = Utils::streamFor('[{"name": "Silas Kenneth", "age": 98, "height": 123.122, "maritalStatus": "complicated,single"},{"name": "James Bay", "age": 23, "height": 163.122, "maritalStatus": "married"}, null]');
        $this->parseNode = (new JsonParseNodeFactory())->getRootParseNode('application/json', $str);

        /** @var array<Person> $expected */
        $expected = $this->parseNode->getCollectionOfObjectValues(array(Person::class, 'createFromDiscriminatorValue'));
        $this->assertCount(2, $expected);
        $this->assertInstanceOf(Person::class, $expected[1]);
        $this->assertInstanceOf(Person::class, $expected[0]);
        $this->assertEquals('James Bay', $expected[1]->getName());
    }

    /**
     * @throws \Exception
     */
    public function testGetObjectValue(): void {
        $this->parseNode = (new JsonParseNodeFactory())->getRootParseNode('application/json', $this->stream);
        /** @var Person $expected */
        $expected = $this->parseNode->getObjectValue(array(Person::class, 'createFromDiscriminatorValue'));
        $this->assertInstanceOf(Person::class, $expected);
        $this->assertEquals('Silas Kenneth', $expected->getName());
        $this->assertInstanceOf(Enum::class, $expected->getMaritalStatus());
        $this->assertEquals(98, $expected->getAge());
        $this->assertEquals(123.122, $expected->getHeight());
    }

    public function testGetFloatValueWithFloat(): void {
        $this->parseNode = new JsonParseNode(1243.12);
        $expected = $this->parseNode->getFloatValue();
        $this->assertEquals(1243.12, $expected);
    }

    public function testGetFloatValueWithInt(): void {
        $this->parseNode = new JsonParseNode(1243);
        $expected = $this->parseNode->getFloatValue();
        $this->assertEquals(1243.00, $expected);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionOfPrimitiveValues(): void {
        $this->parseNode = new JsonParseNode([1921, 1212,123,45,56]);
        $expected = $this->parseNode->getCollectionOfPrimitiveValues();
        $this->assertEquals([1921, 1212,123,45,56], $expected);
    }

    /**
     * @throws Exception
     */
    public function testGetAnyValue(): void {
        $this->parseNode = new JsonParseNode(12);
        $expectedInteger = $this->parseNode->getAnyValue('int');
        $this->parseNode = new JsonParseNode(12.009);
        $expectedFloat = $this->parseNode->getAnyValue('float');
        $this->parseNode = new JsonParseNode((new DateTime('2022-01-27'))->format(DateTimeInterface::RFC3339));
        $expectedDate = $this->parseNode->getAnyValue(Date::class);
        $this->parseNode = new JsonParseNode("Silas Kenneth");
        $expectedString = $this->parseNode->getAnyValue('string');
        $this->assertEquals(12, $expectedInteger);
        $this->assertEquals(12.009, $expectedFloat);
        $this->assertEquals('2022-01-27', (string)$expectedDate);
        $this->assertEquals('Silas Kenneth', $expectedString);
    }

    public function testGetEnumValue(): void {
        $this->parseNode = new JsonParseNode('married');
        /** @var Enum $expected */
        $expected = $this->parseNode->getEnumValue(MaritalStatus::class);
        $this->assertInstanceOf(Enum::class, $expected);
        $this->assertEquals('married', $expected->value());
        $this->parseNode = new JsonParseNode('married,single');
        /** @var Enum $expected */
        $expected = $this->parseNode->getEnumValue(MaritalStatus::class);
        $this->assertInstanceOf(Enum::class, $expected);
        $this->assertEquals('married,single', $expected->value());
    }

    /**
     * @throws \Exception
     */
    public function testGetTimeOnlyValue(): void{
        $this->parseNode = new JsonParseNode((new DateTime('2022-01-27T12:59:45.596117'))->format(DATE_ATOM));
        $expected = $this->parseNode->getTimeValue();
        $this->assertInstanceOf(Time::class, $expected);
        $this->assertEquals('12:59:45', (string)$expected);
    }

    /**
     * @throws \Exception
     */
    public function testGetDateOnlyValue(): void{
        $this->parseNode = new JsonParseNode((new DateTime('2022-01-27T12:59:45.596117'))->format(DATE_ATOM));
        $expected = $this->parseNode->getDateValue();
        $this->assertInstanceOf(Date::class, $expected);
        $this->assertEquals('2022-01-27', (string)$expected);
    }

    public function testGetBooleanValue(): void {
        $this->parseNode = new JsonParseNode(true);
        $expected = $this->parseNode->getBooleanValue();
        $this->assertEquals('bool', get_debug_type($expected));
        $this->assertEquals(true, $expected);
    }

    /**
     * @throws \Exception
     */
    public function testGetDateTimeValue(): void {
        $value = (new DateTime('2022-01-27T12:59:45.596117'))->format(DateTimeInterface::RFC3339);
        $this->parseNode = new JsonParseNode($value);
        $expected = $this->parseNode->getDateTimeValue();
        $this->assertInstanceOf(DateTime::class, $expected);
        $this->assertEquals($value, $expected->format(DateTimeInterface::RFC3339));
    }

    /**
     */
    public function testGetStringValue(): void{
        $this->parseNode = new JsonParseNode('Silas Kenneth was here');
        $expected = $this->parseNode->getStringValue();
        $this->assertEquals('Silas Kenneth was here', $expected);
    }

    /**
     */
    public function testGetStringValueWithSlashAndWhitespace(): void
    {
        $this->parseNode = new JsonParseNode('\ This is a test with a tab at then and slash at the beginning \t.');
        $expected = $this->parseNode->getStringValue();
        $this->assertEquals('\ This is a test with a tab at then and slash at the beginning \t.', $expected);
    }

    public function testGetChildNode(): void {
        $this->stream->rewind();
        $this->parseNode = (new JsonParseNodeFactory())->getRootParseNode('application/json', $this->stream);

        $child = $this->parseNode->getChildNode('address');
        /** @var Address $address */
        $address = $child->getObjectValue(array(Address::class, 'createFromDiscriminatorValue'));
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals('Nairobi', $address->getCity());

    }

    public function testCallbacksAreCalled(): void {
        $this->parseNode = (new JsonParseNodeFactory())->getRootParseNode('application/json', $this->stream);
        $assigned = false;
        $onAfterAssignValues = function ($result) use (&$assigned) {
            $assigned = true;
        };
        $this->parseNode->setOnAfterAssignFieldValues($onAfterAssignValues);
        $person = $this->parseNode->getObjectValue([Person::class, 'createFromDiscriminatorValue']);
        $this->assertTrue($assigned);
    }

    public function testGetBinaryContent(): void {
        $this->parseNode = new JsonParseNode(100);
        $this->assertEquals("100", $this->parseNode->getBinaryContent()->getContents());
    }

    public function testGetBinaryContentFromArray(): void {
        $this->parseNode = new JsonParseNode(json_decode($this->stream->getContents(), true));
        $this->stream->rewind();
        $this->assertEquals($this->stream->getContents(), $this->parseNode->getBinaryContent()->getContents());
    }

    /**
     * @throws Exception
     */
    public function testGetNegativeDateInterval(): void
    {
        $this->parseNode = new JsonParseNode('-P1D');
        $expected = new DateInterval('P1D');
        $expected->invert = 1;
        $this->assertEquals($this->parseNode->getDateIntervalValue(), $expected);
    }
}
