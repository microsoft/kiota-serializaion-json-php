<?php

namespace Microsoft\Kiota\Serialization\Json\Tests;

use DateInterval;
use GuzzleHttp\Psr7\Utils;
use Microsoft\Kiota\Abstractions\Serialization\ComposedTypeWrapper;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Microsoft\Kiota\Abstractions\Types\Date;
use Microsoft\Kiota\Abstractions\Types\Time;
use Microsoft\Kiota\Serialization\Json\JsonSerializationWriter;
use Microsoft\Kiota\Serialization\Json\Tests\Samples\Address;
use Microsoft\Kiota\Serialization\Json\Tests\Samples\MaritalStatus;
use Microsoft\Kiota\Serialization\Json\Tests\Samples\Person;
use PHPUnit\Framework\TestCase;

class JsonSerializationWriterTest extends TestCase
{
    private SerializationWriter $jsonSerializationWriter;

    /**
     */
    public function testWriteAdditionalData(): void {
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAdditionalData(['@odata.type' => 'Type']);
        $expected = '"@odata.type":"Type"';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteLongValue(): void {
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeIntegerValue("timestamp", 28192199291929192);
        $expected = '"timestamp":28192199291929192';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testWriteDateOnlyValue(): void {
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $date = Date::createFrom(2012, 12, 3);
        $this->jsonSerializationWriter->writeAnyValue("date", $date);
        $expected = '"date":"2012-12-03"';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteUUIDValue(): void{
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeStringValue("id", '9de7828f-4975-49c7-8734-805487dfb8a2');
        $expected = '"id":"9de7828f-4975-49c7-8734-805487dfb8a2"';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     */
    public function testWriteCollectionOfNonParsableObjectValues(): void{
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeCollectionOfPrimitiveValues("stops", [1,2,3,4,5]);
        $expected = '"stops":[1,2,3,4,5]';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("stops", ["first" => 'First', 'second' => 'Second']);
        $expected2 = '"stops":{"first":"First","second":"Second"}';
        $actual2 = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expected2, $actual2);
    }

    public function testWriteFloatValue(): void{
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("height", 12.394);
        $expected = '"height":12.394';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     */
    public function testWriteEnumSetValue(): void{
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("status", new MaritalStatus('married,complicated'));
        $expected = '"status":"married,complicated"';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteNullValue(): void{
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("nextPage", null);
        $expected = '"nextPage":null';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     */
    public function testWriteCollectionOfObjectValues(): void{
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $person1 = new Person();
        $person1->setName("John");
        $person1->setMaritalStatus(new MaritalStatus('single'));
        $person2 = new Person();
        $person2->setName('Jane');
        $person2->setMaritalStatus(new MaritalStatus('married'));
        $this->jsonSerializationWriter->writeAnyValue("to", [$person1, $person2]);
        $expected = '"to":[{"name":"John","maritalStatus":"single"},{"name":"Jane","maritalStatus":"married"}]';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteObjectValue(): void{
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $person1 = new Person();
        $person1->setName("John");
        $person1->setMaritalStatus(new MaritalStatus('single'));
        $address = new Address();
        $address->setCity('Nairobi');
        $person1->setAddress($address);
        $this->jsonSerializationWriter->writeAnyValue("to", $person1);
        $expected = '"to":{"name":"John","maritalStatus":"single","address":{"city":"Nairobi"}}';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteIntersectionWrapperObjectValue(): void
    {
        $person1 = new Person();
        $person1->setName("John");
        $person1->setMaritalStatus(new MaritalStatus('single'));
        $address = new Address();
        $address->setCity('Nairobi');
        $jsonSerializationWriter = new JsonSerializationWriter();
        $beforeSerialization = fn (Parsable $n) => true;
        $afterSerialization = fn (Parsable $n) => true;
        $startSerialization = fn (Parsable $p, SerializationWriter $n) => true;
        $jsonSerializationWriter->setOnBeforeObjectSerialization($beforeSerialization);
        $jsonSerializationWriter->setOnAfterObjectSerialization($afterSerialization);
        $jsonSerializationWriter->setOnStartObjectSerialization($startSerialization);
        $jsonSerializationWriter->writeObjectValue("intersection", $person1, $address, null);
        $expected = '"intersection":{"name":"John","maritalStatus":"single","city":"Nairobi"}';
        $this->assertEquals($expected, $jsonSerializationWriter->getSerializedContent()->getContents());
    }

    public function testWriteEnumValue(): void{
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("status", [new MaritalStatus('married'), new MaritalStatus('single')]);
        $expected = '"status":["married","single"]';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteAnyValue(): void {
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $time = new Time('11:00:00');
        $this->jsonSerializationWriter->writeAnyValue("created", $time);
        $expected = '"created":"11:00:00"';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testWriteNonParsableObjectValue(): void{
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("times", (object)[
            "start" => Time::createFrom(12,0, 23),
            "end" => Time::createFrom(13, 45, 12)]);
        $expected = '"times":{"start":"12:00:23","end":"13:45:12"}';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteBooleanValue(): void {
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("available", true);
        $expected = '"available":true';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testWriteTimeOnlyValue(): void{
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("time", Time::createFromDateTime(new \DateTime('2018-12-12T12:34:42+00:00Z')));
        $expected = '"time":"12:34:42"';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteIntegerValue(): void {
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("age", 23);
        $expected = '"age":23';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteDateTimeValue(): void {
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("dateTime", new \DateTime('2018-12-12T12:34:42+00:00'));
        $expected = '"dateTime":"2018-12-12T12:34:42+00:00"';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testGetSerializedContent(): void{
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("statement", "This is a string");
        $expected = '"statement":"This is a string"';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     */
    public function testWriteStringValue(): void {
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $this->jsonSerializationWriter->writeAnyValue("statement", "This is a string\n\r\t");
        $expected = '"statement":"This is a string\\n\\r\\t"';
        $actual = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testWriteDateIntervalValue(): void
    {
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $interval = new DateInterval('P300DT100S');
        $this->jsonSerializationWriter->writeAnyValue('timeTaken', $interval);

        $content = $this->jsonSerializationWriter->getSerializedContent();
        $this->assertEquals('"timeTaken":"P300DT100S"', $content->getContents());
    }

    public function testWriteNegativeDateIntervalValue(): void
    {
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $interval = new DateInterval('P1DT6H7M7S');
        $interval->invert = 1;
        $this->jsonSerializationWriter->writeAnyValue('timeTaken', $interval);
        $content = $this->jsonSerializationWriter->getSerializedContent();
        $this->assertEquals('"timeTaken":"-P1DT6H7M7S"', $content->getContents());
    }

    public function testWriteBinaryContentValue(): void
    {
        $this->jsonSerializationWriter = new JsonSerializationWriter();
        $stream = Utils::streamFor("Hello world!!!\r\t\t\t\n");
        $this->jsonSerializationWriter->writeBinaryContent('body', $stream);
        $this->jsonSerializationWriter->writeAnyValue('body3', $stream);
        $content = $this->jsonSerializationWriter->getSerializedContent();
        $this->assertEquals("\"body\":\"Hello world!!!\\r\\t\\t\\t\\n\",\"body3\":\"Hello world!!!\\r\\t\\t\\t\\n\"", $content->getContents());
    }

    public function testWriteObjectValueForComposedTypes(): void
    {
        $this->jsonSerializationWriter = new JsonSerializationWriter();

        $obj = new class implements Parsable, ComposedTypeWrapper {
            public function getString(): ?string {return null;}
            public function getBoolean(): ?bool { return null;}
            public function getInteger(): ?int { return null;}
            public function setBoolean(?bool $value): void {}
            public function setInteger(?int $value): void {}
            public function setString(?string $value): void {}
            public function setDouble(?float $value): void {}
            public function getDouble(): ?float {return 26.6;}
            public function getFieldDeserializers(): array { return [];}
            public function serialize(SerializationWriter $writer): void
            {
                if ($this->getInteger() !== null) {
                    $writer->writeIntegerValue(null, $this->getInteger());
                } elseif ($this->getBoolean() !== null) {
                    $writer->writeBooleanValue(null, $this->getBoolean());
                } elseif ($this->getString() !== null) {
                    $writer->writeStringValue(null, $this->getString());
                } else if ($this->getDouble() !== null) {
                    $writer->writeFloatValue(null, $this->getDouble());
                }
            }

            public function createFromDiscriminator(ParseNode $parseNode): self {
                $result = new $this();
                if ($parseNode->getBooleanValue() !== null) {
                    $result->setBoolean($parseNode->getBooleanValue());
                } else if ($parseNode->getFloatValue() !== null) {
                    $result->setDouble($parseNode->getFloatValue());
                } else if ($parseNode->getIntegerValue() !== null) {
                    $result->setInteger($parseNode->getIntegerValue());
                } else if ($parseNode->getStringValue() !== null) {
                    $result->setString($parseNode->getStringValue());
                }
                return $result;
            }

        };

        $this->jsonSerializationWriter->writeObjectValue(null, $obj);

        $dd = $this->jsonSerializationWriter->getSerializedContent()->getContents();
        $this->assertEquals('26.6', $dd);
    }
}
