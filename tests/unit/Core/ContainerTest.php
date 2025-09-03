<?php
/**
 * Container Test
 * 
 * Tests for the DI Container implementation.
 * 
 * @package EricRorich\AIInterviewWidget\Tests
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use EricRorich\AIInterviewWidget\Core\Container;

/**
 * Container Test class
 * 
 * @since 2.0.0
 */
class ContainerTest extends TestCase {

    private $container;

    protected function setUp(): void {
        $this->container = new Container();
    }

    protected function tearDown(): void {
        $this->container->flush();
    }

    public function test_bind_and_make() {
        // Test binding a closure
        $this->container->bind('test_service', function() {
            return new \stdClass();
        });

        $this->assertTrue($this->container->has('test_service'));
        
        $service = $this->container->make('test_service');
        $this->assertInstanceOf(\stdClass::class, $service);
    }

    public function test_singleton() {
        // Test singleton binding
        $this->container->singleton('singleton_service', function() {
            return new \stdClass();
        });

        $service1 = $this->container->make('singleton_service');
        $service2 = $this->container->make('singleton_service');

        $this->assertSame($service1, $service2);
    }

    public function test_class_resolution() {
        // Test class name resolution
        $this->container->bind('std_class', \stdClass::class);
        
        $service = $this->container->make('std_class');
        $this->assertInstanceOf(\stdClass::class, $service);
    }

    public function test_has_method() {
        $this->assertFalse($this->container->has('non_existent'));
        
        $this->container->bind('existing', function() {
            return 'test';
        });
        
        $this->assertTrue($this->container->has('existing'));
    }

    public function test_flush() {
        $this->container->bind('test', function() {
            return 'value';
        });
        
        $this->assertTrue($this->container->has('test'));
        
        $this->container->flush();
        
        $this->assertFalse($this->container->has('test'));
    }

    public function test_make_with_parameters() {
        $this->container->bind('parameterized', function($container, $params = []) {
            $obj = new \stdClass();
            $obj->params = $params;
            return $obj;
        });

        $service = $this->container->make('parameterized', ['test' => 'value']);
        $this->assertInstanceOf(\stdClass::class, $service);
    }

    public function test_nonexistent_service_throws_exception() {
        $this->expectException(\Exception::class);
        $this->container->make('nonexistent_service');
    }
}