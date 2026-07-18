<?php
declare(strict_types=1);

namespace Velo\Container\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Container\Container;
use Velo\Container\Exceptions\ContainerException;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    #[Test]
    #[DataProvider('bindingCases')]
    public function it_binds(string $key, callable|string $value): void
    {
        $this->container->set($key, $value);
        $this->assertEquals([$key => $value], $this->container->entries);
    }

    public static function bindingCases(): array
    {
        return [
            [SimpleInterface::class, SimpleClass::class],
            [SimpleClass::class, fn() => new SimpleClass()],
        ];
    }

    #[Test]
    public function it_gets_binded_callable(): void
    {
        $this->container->set(SimpleClass::class, fn() => new SimpleClass());
        $this->assertInstanceOf(SimpleClass::class, $this->container->get(SimpleClass::class));
    }

    #[Test]
    public function it_throws_not_instanciable_exception(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->get(SimpleInterface::class);
    }

    #[Test]
    public function it_resolves_with_injected_dependency(): void
    {
        $dependency = $this->createStub(SimpleDependency::class);
        $this->container->set(SimpleDependency::class, fn() => $dependency);
        $this->container->set(ClassWithDependency::class, fn() => new ClassWithDependency($dependency));

        $this->assertInstanceOf(ClassWithDependency::class, $this->container->get(ClassWithDependency::class));
    }

    #[Test]
    public function it_gets_binded_by_interface(): void
    {
        $dependency = $this->createStub(SimpleDependency::class);
        $this->container->set(SimpleDependency::class, fn() => $dependency);
        $this->container->set(SimpleInterface::class, ClassWithDependency::class);

        $this->assertInstanceOf(ClassWithDependency::class, $this->container->get(SimpleInterface::class));
    }

    #[Test]
    public function it_throws_missing_type_hint_exception(): void
    {
        $testClass = new class(2) {
            public function __construct($untyped)
            {
            }
        };

        $this->expectException(ContainerException::class);
        $this->container->get($testClass::class);
    }

    #[Test]
    public function it_throws_union_type_hint_exception(): void
    {
        $testClass = new class(2) {
            public function __construct(int|string $id)
            {
            }
        };

        $this->expectException(ContainerException::class);
        $this->container->get($testClass::class);
    }

    #[Test]
    public function it_throws_intersection_type_hint_exception(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->get(NeedsIntersection::class);
    }

    #[Test]
    public function it_throws_no_default_value_exception(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->get(NeedsNoDefaultValue::class);
    }

    #[Test]
    public function it_resolves_builtin_with_default_value(): void
    {
        $result = $this->container->get(ClassWithDefaultValue::class);
        $this->assertInstanceOf(ClassWithDefaultValue::class, $result);
        $this->assertEquals('default', $result->value);
    }

    #[Test]
    public function it_resolves_simple_class_without_constructor(): void
    {
        $result = $this->container->get(SimpleClassWithoutConstructor::class);
        $this->assertInstanceOf(SimpleClassWithoutConstructor::class, $result);
    }

    #[Test]
    public function it_has_registered_entry(): void
    {
        $this->container->set('test_key', 'test_value');
        $this->assertTrue($this->container->has('test_key'));
    }

    #[Test]
    public function it_does_not_have_unregistered_entry(): void
    {
        $this->assertFalse($this->container->has('non_existent_key'));
    }

    #[Test]
    public function it_resolves_nested_dependencies(): void
    {
        $this->container->set(SimpleDependency::class, SimpleDependency::class);
        $result = $this->container->get(ClassWithNestedDependency::class);
        $this->assertInstanceOf(ClassWithNestedDependency::class, $result);
        $this->assertInstanceOf(SimpleDependency::class, $result->dependency);
    }

    #[Test]
    public function it_resolves_callable_with_container_access(): void
    {
        $dependency = $this->createStub(SimpleDependency::class);
        $this->container->set(SimpleDependency::class, fn() => $dependency);

        $this->container->set(
            ClassWithDependency::class,
            fn(Container $c) => new ClassWithDependency($c->get(SimpleDependency::class))
        );

        $result = $this->container->get(ClassWithDependency::class);
        $this->assertInstanceOf(ClassWithDependency::class, $result);
    }

    #[Test]
    public function it_resolves_interfaces(): void
    {
        $this->container->set(Class2Interface::class, Class2::class);
        $result = $this->container->get(Class1::class);
        $this->assertInstanceOf(Class1::class, $result);
    }

    #[Test]
    public function it_returns_the_same_instance_for_autowired_class_singleton(): void
    {
        $instance1 = $this->container->get(SimpleClassWithoutConstructor::class);
        $instance2 = $this->container->get(SimpleClassWithoutConstructor::class);

        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function it_returns_the_same_instance_for_callable_singleton(): void
    {
        $this->container->set(SimpleClass::class, fn() => new SimpleClass());

        $instance1 = $this->container->get(SimpleClass::class);
        $instance2 = $this->container->get(SimpleClass::class);

        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function it_shares_the_same_instance_between_interface_and_concrete_class(): void
    {
        $this->container->set(SimpleInterface::class, SimpleClass::class);

        $fromInterface = $this->container->get(SimpleInterface::class);
        $fromConcrete  = $this->container->get(SimpleClass::class);

        $this->assertSame($fromInterface, $fromConcrete);
    }

    #[Test]
    public function it_clears_cached_instance_when_overridden(): void
    {
        $this->container->set(SimpleInterface::class, SimpleClass::class);
        $firstInstance = $this->container->get(SimpleInterface::class);

        $anotherConcrete = new SimpleClass();
        $this->container->set(SimpleInterface::class, fn() => $anotherConcrete);

        $secondInstance = $this->container->get(SimpleInterface::class);

        $this->assertNotSame($firstInstance, $secondInstance);
        $this->assertSame($anotherConcrete, $secondInstance);
    }
}

// ===== Test Fixtures =====

interface SimpleInterface
{
}

class SimpleClass implements SimpleInterface
{
}

class SimpleDependency
{
}

class ClassWithDependency
{
    public function __construct(public SimpleDependency $dependency)
    {
    }
}

class ClassWithNestedDependency
{
    public function __construct(public SimpleDependency $dependency)
    {
    }
}

class SimpleClassWithoutConstructor
{
}

class ClassWithDefaultValue
{
    public function __construct(public string $value = 'default')
    {
    }
}

class Class1
{
    public function __construct(Class2Interface $class2)
    {
    }
}

interface Class2Interface
{

}

class Class2 implements Class2Interface
{

}


// Intersection types (IA&IB)
interface IA
{
}

interface IB
{
}

class NeedsIntersection
{
    public function __construct(IA&IB $p)
    {
    }
}

class NeedsNoDefaultValue
{
    public function __construct(string $p)
    {
    }
}

