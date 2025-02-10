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
use Microsoft\Kiota\Abstractions\Serialization\ParseNodeFromStringTrait;
use Microsoft\Kiota\Abstractions\Types\Date;
use Microsoft\Kiota\Abstractions\Types\Time;
use Psr\Http\Message\StreamInterface;

/**
 * @method onBeforeAssignFieldValues(Parsable $result)
 * @method onAfterAssignFieldValues(Parsable $result)
 */
class JsonParseNode implements ParseNode
{
    use ParseNodeFromStringTrait;

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
        if ((!is_array($this->jsonNode)) || (($this->jsonNode[$identifier] ?? null) === null)) {
            return null;
        }
        return new self($this->jsonNode[$identifier]);
    }

    /**
     * @inheritDoc
     */
    public function getStringValue(): ?string {
        return is_string($this->jsonNode) ? $this->jsonNode : null;
    }

    /**
     * @inheritDoc
     */
    public function getBooleanValue(): ?bool {
        return is_bool($this->jsonNode) ? $this->jsonNode : null;
    }

    /**
     * @inheritDoc
     */
    public function getIntegerValue(): ?int {
        return is_int($this->jsonNode) ? $this->jsonNode : null;
    }

    /**
     * @inheritDoc
     */
    public function getFloatValue(): ?float {
        if (is_float($this->jsonNode)) {
            return $this->jsonNode;
        }
        if (is_int($this->jsonNode)) {
            return floatval($this->jsonNode);
        }
        return null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getCollectionOfObjectValues(array $type): ?array {
        if (!is_array($this->jsonNode)) {
            return null;
        }
        $result = array_map(static function ($val) use($type) {
            return $val->getObjectValue($type);
        }, array_map(static function ($value) {
            return new JsonParseNode($value);
        }, $this->jsonNode));
        return array_filter($result, fn ($item) => !is_null($item));
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
            throw new InvalidArgumentException('Undefined method '. $type[1]);
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
        if (is_array($this->jsonNode)) {
            foreach ($this->jsonNode as $key => $value) {
                $deserializer = $fieldDeserializers[$key] ?? null;

                if ($deserializer !== null) {
                    $deserializer(new JsonParseNode($value));
                } else {
                    $key                  = (string)$key;
                    $additionalData[$key] = $value;
                }
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
        if (!is_array($this->jsonNode)) {
            return null;
        }
        $result = array_map(static function ($val) use($targetClass) {
            return $val->getEnumValue($targetClass);
        }, array_map(static function ($value) {
            return new JsonParseNode($value);
        }, $this->jsonNode));
        return array_filter($result, fn ($item) => !is_null($item));
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
        if (!is_array($this->jsonNode)) {
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
                return $this->getCollectionOfPrimitiveValues();
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
        return ($this->jsonNode !== null) ? new Date(strval($this->jsonNode)) : null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getTimeValue(): ?Time {
        return ($this->jsonNode !== null) ? new Time(strval($this->jsonNode)) : null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getDateTimeValue(): ?DateTime {
        return ($this->jsonNode !== null) ? new DateTime(strval($this->jsonNode)) : null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getDateIntervalValue(): ?DateInterval{
        if ($this->jsonNode === null){
            return null;
        }
        return $this->parseDateIntervalFromString(strval($this->jsonNode));
    }

    public function getBinaryContent(): ?StreamInterface {
        if (is_null($this->jsonNode)) {
            return null;
        } elseif (is_array($this->jsonNode)) {
            return Utils::streamFor(json_encode($this->jsonNode));
        }
        return Utils::streamFor(strval($this->jsonNode));
    }
}
