<?php

declare(strict_types=1);

namespace UploadThing\Utils;

/**
 * JSON serializer/deserializer with strict typing.
 */
final class Serializer
{
    /**
     * Serialize an object to JSON.
     */
    public function serialize(object $object): string
    {
        $data = $this->objectToArray($object);
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * Deserialize JSON to an object.
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    public function deserialize(string $json, string $className): object
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('JSON must decode to an array');
        }

        return $this->arrayToObject($data, $className);
    }

    /**
     * Convert an object to an array recursively.
     */
    private function objectToArray(object $object): array
    {
        $reflection = new \ReflectionClass($object);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $array = [];
        foreach ($properties as $property) {
            $value = $property->getValue($object);

            if (is_object($value)) {
                $array[$property->getName()] = $this->objectToArray($value);
            } elseif (is_array($value)) {
                $array[$property->getName()] = $this->arrayToArray($value);
            } else {
                $array[$property->getName()] = $value;
            }
        }

        return $array;
    }

    /**
     * Convert an array to an object.
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    private function arrayToObject(array $data, string $className): object
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            // For classes without constructors, create instance and set properties
            $instance = $reflection->newInstance();

            foreach ($data as $propertyName => $value) {
                if ($reflection->hasProperty($propertyName)) {
                    $property = $reflection->getProperty($propertyName);
                    $property->setAccessible(true);
                    $property->setValue($instance, $value);
                }
            }

            return $instance;
        }

        $parameters = $constructor->getParameters();
        $args = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $value = $data[$name] ?? null;

            if ($value === null && !$parameter->allowsNull()) {
                throw new \InvalidArgumentException("Missing required parameter: {$name}");
            }

            $args[] = $value;
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Convert an array to an array recursively.
     */
    private function arrayToArray(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->arrayToArray($value);
            } elseif (is_object($value)) {
                $result[$key] = $this->objectToArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
