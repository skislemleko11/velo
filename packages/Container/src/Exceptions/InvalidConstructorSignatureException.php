<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Velo\Exceptions\Interfaces\VeloExceptionInterface;

final class InvalidConstructorSignatureException extends Exception implements VeloExceptionInterface, ContainerExceptionInterface
{
    public static function missingTypeDeclaration(string $className, string $paramName): self
    {
        return new self("Parameter '\$$paramName' in $className::__construct() is missing a type declaration.");
    }

    public static function unionTypeNotSupported(string $className, string $paramName): self
    {
        return new self("Parameter '\$$paramName' in $className::__construct() uses a union type, which is not supported.");
    }

    public static function intersectionTypeNotSupported(string $className, string $paramName): self
    {
        return new self("Parameter '\$$paramName' in $className::__construct() uses an intersection type, which is not supported.");
    }

    public static function unexpectedInvalidParameter(string $className, string $paramName): self
    {
        return new self("Parameter '\$$paramName' in $className::__construct() has an invalid or unsupported type.");
    }

    public static function noDefaultValue(string $className, string $paramName): self
    {
        return new self("Parameter '\$$paramName' in $className::__construct() has an invalid or unsupported type.");
    }
}