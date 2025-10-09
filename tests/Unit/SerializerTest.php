<?php

declare(strict_types=1);

namespace UploadThing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use UploadThing\Utils\Serializer;

class SerializerTest extends TestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new Serializer();
    }

    public function testSerializeObject(): void
    {
        $object = new class {
            public string $name = 'test';
            public int $value = 123;
        };
        
        $json = $this->serializer->serialize($object);
        $data = json_decode($json, true);
        
        $this->assertEquals('test', $data['name']);
        $this->assertEquals(123, $data['value']);
    }

    public function testDeserializeToObject(): void
    {
        $json = '{"name":"test","value":123}';
        
        // Create a simple test class instead of using stdClass
        $testClass = new class {
            public string $name;
            public int $value;
        };
        
        $object = $this->serializer->deserialize($json, get_class($testClass));
        
        $this->assertEquals('test', $object->name);
        $this->assertEquals(123, $object->value);
    }

    public function testSerializeWithNestedObject(): void
    {
        $object = new class {
            public string $name = 'test';
            public object $nested;
            
            public function __construct()
            {
                $this->nested = new class {
                    public int $value = 456;
                };
            }
        };
        
        $json = $this->serializer->serialize($object);
        $data = json_decode($json, true);
        
        $this->assertEquals('test', $data['name']);
        $this->assertEquals(456, $data['nested']['value']);
    }
}
