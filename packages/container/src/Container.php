<?php
declare(strict_types=1);

namespace Velo\Container;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use Velo\Container\Exceptions\ContainerException;

class Container implements ContainerInterface
{
    /**
     * @var array<string, object|callable|string>
     */
    private array $entries = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Binds a service, factory, or instantiated object to the container.
     *
     * @param string $id Service class name or interface identifier.
     * @param object|callable|string $concrete Instance, factory function, or class name.
     *
     * @note Passing already instantiated objects is optimal only when You've already used it.
     * Don't create objects just to pass them, using functions (lazy loading) is way more efficient.
     */
    public function set(string $id, object|callable|string $concrete): void
    {
        $this->entries[$id] = $concrete;

        if (is_object($concrete) && !$concrete instanceof Closure) {
            $this->instances[$id] = $concrete;
            unset($this->entries[$id]);
        }
        else {
            $this->entries[$id] = $concrete;
            unset($this->instances[$id]);
        }
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function get(string $id): mixed
    {
        if (isset($this->instances[$id]))
            return $this->instances[$id];

        if ($this->has($id)) {
            $entry = $this->entries[$id];

            if (is_callable($entry)) {
                $object = $entry($this);

                if (is_object($object))
                    $this->instances[$id] = $object;

                return $object;
            }

            // Aliases / Interfaces
            $resolvedId = $entry;

            if (isset($this->instances[$resolvedId]))
                $this->instances[$id] = $this->instances[$resolvedId];

            $object = $this->resolve($resolvedId);

            $this->instances[$resolvedId] = $object;
            $this->instances[$id] = $object;

            return $object;
        }

        $object = $this->resolve($id);
        $this->instances[$id] = $object;

        return $object;
    }

    public function has(string $id): bool
    {
        return isset($this->entries[$id]);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerException
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    private function resolve(string $id): object
    {
        $reflectionClass = new ReflectionClass($id);

        if (!$reflectionClass->isInstantiable())
            throw new ContainerException('Class "' . $id . '" is not instantiable!');

        if ($constructor = $reflectionClass->getConstructor()) {
            $params = $constructor->getParameters();

            if (!$params)
                return new $id();

            $dependencies = [];

            foreach ($params as $param) {
                $paramName = $param->getName();
                $paramType = $param->getType();

                if (!$paramType)
                    throw new ContainerException(
                        'Failed to resolve dependency: "' . $id . '" because "' . $paramName . '" is missing a type hint!');

                if ($paramType instanceof ReflectionUnionType)
                    throw new ContainerException(
                        'Failed to resolve dependency: "' . $id . '" because param"' . $paramName . '" has a union type hint!'
                    );

                if ($paramType instanceof ReflectionNamedType) {
                    if ($paramType->isBuiltin()) {
                        if ($param->isDefaultValueAvailable())
                            $dependencies[] = $param->getDefaultValue();
                        else
                            throw new ContainerException(
                                'Failed to resolve dependency: "' . $id . '" because invalid param"' . $paramName . '" (no default value)'
                            );
                    } else {
                        $typeName = $paramType->getName();

                        if ($this->has($typeName)) {
                            $dependencies[] = $this->get($typeName);
                        } else if ($param->isDefaultValueAvailable()) {
                            $dependencies[] = $param->getDefaultValue();
                        } else if ($paramType->allowsNull()) {
                            $dependencies[] = null;
                        } else {
                            $dependencies[] = $this->get($typeName);
                        }
                    }
                } else if ($paramType instanceof ReflectionIntersectionType) {
                    throw new ContainerException(
                        'Failed to resolve dependency: "' . $id . '" because param"' . $paramName . '" has an intersection type hint!'
                    );
                } else {
                    // Probably it's not reachable in current(8.5) PHP, but i'm leaving it in case of future changes or bugs
                    throw new ContainerException(
                        'Failed to resolve dependency: "' . $id . '" because invalid param"' . $paramName . '"'
                    );
                }
            }

            return $reflectionClass->newInstanceArgs($dependencies);
        }

        return new $id();
    }
}