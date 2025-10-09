<?php

declare(strict_types=1);

namespace UploadThing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use UploadThing\Utils\MultipartBuilder;

class MultipartBuilderTest extends TestCase
{
    public function testAddField(): void
    {
        $builder = new MultipartBuilder();
        $builder->addField('name', 'value');
        
        $this->assertInstanceOf(MultipartBuilder::class, $builder);
    }

    public function testAddFile(): void
    {
        $builder = new MultipartBuilder();
        $builder->addFile('file', 'test.txt', 'content', 'text/plain');
        
        $this->assertInstanceOf(MultipartBuilder::class, $builder);
    }

    public function testBuildWithFields(): void
    {
        $builder = new MultipartBuilder();
        $builder->addField('name', 'value');
        
        $result = $builder->build();
        
        $this->assertStringContainsString('name="name"', $result);
        $this->assertStringContainsString('value', $result);
        $this->assertStringContainsString('--', $result);
    }

    public function testBuildWithFiles(): void
    {
        $builder = new MultipartBuilder();
        $builder->addFile('file', 'test.txt', 'content', 'text/plain');
        
        $result = $builder->build();
        
        $this->assertStringContainsString('name="file"', $result);
        $this->assertStringContainsString('filename="test.txt"', $result);
        $this->assertStringContainsString('Content-Type: text/plain', $result);
        $this->assertStringContainsString('content', $result);
    }

    public function testBuildWithFieldsAndFiles(): void
    {
        $builder = new MultipartBuilder();
        $builder->addField('name', 'value');
        $builder->addFile('file', 'test.txt', 'content', 'text/plain');
        
        $result = $builder->build();
        
        $this->assertStringContainsString('name="name"', $result);
        $this->assertStringContainsString('value', $result);
        $this->assertStringContainsString('name="file"', $result);
        $this->assertStringContainsString('filename="test.txt"', $result);
        $this->assertStringContainsString('content', $result);
    }

    public function testGetContentType(): void
    {
        $builder = new MultipartBuilder();
        $contentType = $builder->getContentType();
        
        $this->assertStringStartsWith('multipart/form-data; boundary=', $contentType);
    }

    public function testDefaultMimeType(): void
    {
        $builder = new MultipartBuilder();
        $builder->addFile('file', 'test.txt', 'content');
        
        $result = $builder->build();
        
        $this->assertStringContainsString('Content-Type: application/octet-stream', $result);
    }

    public function testBoundaryUniqueness(): void
    {
        $builder1 = new MultipartBuilder();
        $builder2 = new MultipartBuilder();
        
        $contentType1 = $builder1->getContentType();
        $contentType2 = $builder2->getContentType();
        
        $this->assertNotEquals($contentType1, $contentType2);
    }

    public function testChaining(): void
    {
        $builder = new MultipartBuilder();
        $result = $builder
            ->addField('field1', 'value1')
            ->addField('field2', 'value2')
            ->addFile('file1', 'test1.txt', 'content1')
            ->addFile('file2', 'test2.txt', 'content2')
            ->build();
        
        $this->assertStringContainsString('field1', $result);
        $this->assertStringContainsString('field2', $result);
        $this->assertStringContainsString('file1', $result);
        $this->assertStringContainsString('file2', $result);
    }
}
