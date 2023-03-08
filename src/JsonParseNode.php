<?php

namespace Microsoft\Kiota\Serialization\Json;

use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Microsoft\Kiota\Abstractions\Enum;
use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Types\Date;
use Microsoft\Kiota\Abstractions\Types\Time;
use Psr\Http\Message\StreamInterface;

/**
 * @method onBeforeAssignFieldValues(Parsable $result)
 * @method onAfterAssignFieldValues(Parsable $result)
 */
class JsonParseNode implements ParseNode
{
    /** @var mixed|null $jsonNode*/
    private $jsonNode;

    /** @var callable|null */
    public $onBeforeAssignFieldValues;
    /** @var callable|null */
    public $onAfterAssignFieldValues;
    /**
     * @param mixed|null $content
     */
    public function __construct($content) {
        $this->jsonNode = $content;

    }

    /**
     * @inheritDoc
     */
    public function getChildNode(string $identifier): ?ParseNode {
        if ($this->jsonNode === null || !($this->jsonNode[$identifier] ?? null)) {
            return null;
        }
        return new self($this->jsonNode[$identifier] ?? null);
    }

    /**
     * @inheritDoc
     */
    public function getStringValue(): ?string {
        return $this->jsonNode !== null ? addcslashes($this->jsonNode, "\\\r\n") : null;
    }

    /**
     * @inheritDoc
     */
    public function getBooleanValue(): ?bool {
        return $this->jsonNode !== null ? (bool)$this->jsonNode : null;
    }

    /**
     * @inheritDoc
     */
    public function getIntegerValue(): ?int {
        return $this->jsonNode !== null ? (int)$this->jsonNode : null;
    }

    /**
     * @inheritDoc
     */
    public function getFloatValue(): ?float {
        return $this->jsonNode !== null ? (float)$this->jsonNode : null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getCollectionOfObjectValues(array $type): ?array {
        if ($this->jsonNode === null) {
            return null;
        }
        return array_map(static function ($val) use($type) {
            return $val->getObjectValue($type);
        }, array_map(static function ($value) {
            return new JsonParseNode($value);
        }, $this->jsonNode));
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getObjectValue(array $type): ?Parsable {
        if ($this->jsonNode === null) {
            return null;
        }
        if (!is_subclass_of($type[0], Parsable::class)){
            throw new InvalidArgumentException("Invalid type $type[0] provided.");
        }
        if (!is_callable($type, true, $callableString)) {
            throw new \RuntimeException('Undefined method '. $type[1]);
        }
        $result = $callableString($this);
        if($this->getOnBeforeAssignFieldValues() !== null) {
            $this->getOnBeforeAssignFieldValues()($result);
        }
        $this->assignFieldValues($result);
        if ($this->getOnAfterAssignFieldValues() !== null){
            $this->getOnAfterAssignFieldValues()($result);
        }
        return $result;
    }

    /**
     * @param Parsable|AdditionalDataHolder $result
     * @return void
     */
    private function assignFieldValues($result): void {
        $fieldDeserializers = [];
        if (is_a($result, Parsable::class)){
            $fieldDeserializers = $result->getFieldDeserializers();
        }
        $isAdditionalDataHolder = false;
        $additionalData = [];
        if (is_a($result, AdditionalDataHolder::class)) {
            $isAdditionalDataHolder = true;
            $additionalData = $result->getAdditionalData() ?? [];
        }
        foreach ($this->jsonNode as $key => $value){
            $deserializer = $fieldDeserializers[$key] ?? null;

            if ($deserializer !== null){
                $deserializer(new JsonParseNode($value));
            } else {
                $key = (string)$key;
                $additionalData[$key] = $value;
            }
        }

        if ( $isAdditionalDataHolder ) {
            $result->setAdditionalData($additionalData);
        }
    }

    /**
     * @inheritDoc
     */
    public function getEnumValue(string $targetEnum): ?Enum{
        if ($this->jsonNode === null){
            return null;
        }
        if (!is_subclass_of($targetEnum, Enum::class)) {
            throw new InvalidArgumentException('Invalid enum provided.');
        }
        return new $targetEnum($this->jsonNode);
    }

    /**
     * @inheritDoc
     */
    public function getCollectionOfEnumValues(string $targetClass): ?array {
        if ($this->jsonNode === null) {
            return null;
        }
        return array_map(static function ($val) use($targetClass) {
            return $val->getEnumValue($targetClass);
        }, array_map(static function ($value) {
            return new JsonParseNode($value);
        }, $this->jsonNode));
    }

    /**
     * @inheritDoc
     */
    public function getOnBeforeAssignFieldValues(): ?callable {
        return $this->onBeforeAssignFieldValues;
    }

    /**
     * @inheritDoc
     */
    public function getOnAfterAssignFieldValues(): ?callable {
        return $this->onAfterAssignFieldValues;
    }

    /**
     * @inheritDoc
     */
    public function setOnAfterAssignFieldValues(callable $value): void {
        $this->onAfterAssignFieldValues = $value;
    }

    /**
     * @inheritDoc
     */
    public function setOnBeforeAssignFieldValues(callable $value): void {
        $this->onBeforeAssignFieldValues = $value;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getCollectionOfPrimitiveValues(?string $typeName = null): ?array {
        if ($this->jsonNode === null){
            return null;
        }
        return array_map(static function ($x) use ($typeName) {
            $type = empty($typeName) ? get_debug_type($x) : $typeName;
            return (new JsonParseNode($x))->getAnyValue($type);
        }, $this->jsonNode);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getAnyValue(string $type) {
        switch ($type){
            case 'bool':
                return $this->getBooleanValue();
            case 'string':
                return $this->getStringValue();
            case 'int':
                return $this->getIntegerValue();
            case 'float':
                return $this->getFloatValue();
            case 'null':
                return null;
            case 'array':
                return $this->getCollectionOfPrimitiveValues(null);
            case Date::class:
                return $this->getDateValue();
            case Time::class:
                return $this->getTimeValue();
            default:
                if (is_subclass_of($type, Enum::class)){
                    return $this->getEnumValue($type);
                }
                if (is_subclass_of($type, StreamInterface::class)) {
                    return $this->getBinaryContent();
                }
                throw new InvalidArgumentException("Unable to decode type $type");
        }

    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getDateValue(): ?Date {
        return ($this->jsonNode !== null) ? new Date($this->jsonNode) : null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getTimeValue(): ?Time {
        return ($this->jsonNode !== null) ? new Time($this->jsonNode) : null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getDateTimeValue(): ?DateTime {
        return ($this->jsonNode !== null) ? new DateTime($this->jsonNode) : null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getDateIntervalValue(): ?DateInterval{
        return ($this->jsonNode !== null) ? new DateInterval($this->jsonNode) : null;
    }

    /**
     * @inheritDoc
     */
    public function getBinaryContent(): ?StreamInterface {
        return ($this->jsonNode !== null) ? Utils::streamFor($this->jsonNode) : null;
    }
}
