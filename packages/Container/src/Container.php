<?php
declare(strict_types=1);

namespace Velo\Container;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use Velo\Container\Exceptions\InvalidConstructorSignatureException;
use Velo\Container\Exceptions\IsNotInstantiableException;

/**
 * Dependency Injection Container
 */
final class Container implements ContainerInterface
{
    /**
     * @var array<string, callable|string>
     */
    private array $entries = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Binds an alias/interface, a factory, or an instantiated object to the container.
     *
     * @param string $id Dependency ID - class name or alias/interface.
     * @param object|callable|string $concrete Instance, factory function which takes the container as an argument, or class name.
     * Passing already instantiated objects is optimal only when You've already used it.
     * Don't create objects just to pass them, using functions (lazy loading) is way more efficient.
     */
    public function set(string $id, object|callable|string $concrete): void
    {
        if (is_object($concrete) && !$concrete instanceof Closure) {
            $this->instances[$id] = $concrete;
            unset($this->entries[$id]);
        } else {
            $this->entries[$id] = $concrete;
            unset($this->instances[$id]);
        }
    }

    /**
     * It gets an object of the requested id.
     *
     * @throws InvalidConstructorSignatureException
     * @throws IsNotInstantiableException
     * @throws ReflectionException
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if ($this->has($id)) {
            $entry = $this->entries[$id];

            if (is_callable($entry)) {
                $object = $entry($this);

                if (is_object($object)) {
                    $this->instances[$id] = $object;
                }

                return $object;
            }

            // Aliases / Interfaces
            $resolvedId = $entry;

            if (isset($this->instances[$resolvedId])) {
                $this->instances[$id] = $this->instances[$resolvedId];
                return $this->instances[$id];
            }

            $object = $this->resolve($resolvedId);

            $this->instances[$resolvedId] = $object;
            $this->instances[$id] = $object;

            return $object;
        }

        $object = $this->resolve($id);
        $this->instances[$id] = $object;

        return $object;
    }

    /**
     * Checks if the given ID is set in entries or instances arrays.
     *
     * @param string $id Dependency ID - class name or alias/interface.
     */
    public function has(string $id): bool
    {
        return isset($this->entries[$id]) || isset($this->instances[$id]);
    }

    /**
     * It resolves an unbound dependency.
     *
     * @param string $id Dependency ID - class name or alias/interface.
     *
     * @throws IsNotInstantiableException
     * @throws InvalidConstructorSignatureException
     * @throws ReflectionException
     */
    private function resolve(string $id): object
    {
        $reflectionClass = new ReflectionClass($id);

        if (!$reflectionClass->isInstantiable()) {
            throw new IsNotInstantiableException('Class "' . $id . '" is not instantiable!');
        }

        if ($constructor = $reflectionClass->getConstructor()) {
            $params = $constructor->getParameters();

            if (!$params) {
                return new $id();
            }

            $dependencies = [];

            foreach ($params as $param) {
                $paramName = $param->getName();
                $paramType = $param->getType();

                if (!$paramType) {
                    throw InvalidConstructorSignatureException::missingTypeDeclaration($id, $paramName);
                }

                if ($paramType instanceof ReflectionUnionType) {
                    throw InvalidConstructorSignatureException::unionTypeNotSupported($id, $paramName);
                }

                if ($paramType instanceof ReflectionNamedType) {
                    if ($paramType->isBuiltin()) {
                        if ($param->isDefaultValueAvailable()) {
                            $dependencies[] = $param->getDefaultValue();
                        } else {
                            throw InvalidConstructorSignatureException::noDefaultValue($id, $paramName);
                        }
                    } else {
                        $typeName = $paramType->getName();

                        if ($this->has($typeName)) {
                            $dependencies[] = $this->get($typeName);
                        } elseif ($param->isDefaultValueAvailable()) {
                            $dependencies[] = $param->getDefaultValue();
                        } elseif ($paramType->allowsNull()) {
                            $dependencies[] = null;
                        } else {
                            $dependencies[] = $this->get($typeName);
                        }
                    }
                } elseif ($paramType instanceof ReflectionIntersectionType) {
                    throw InvalidConstructorSignatureException::intersectionTypeNotSupported($id, $paramName);
                } else {
                    // Probably it's not reachable in current(8.5) PHP, but I'm leaving it in case of future changes or bugs
                    throw InvalidConstructorSignatureException::unexpectedInvalidParameter($id, $paramName);
                }
            }

            return $reflectionClass->newInstanceArgs($dependencies);
        }

        return new $id();
    }
}