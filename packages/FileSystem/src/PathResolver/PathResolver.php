<?php
declare(strict_types=1);

namespace Velo\FileSystem\PathResolver;

use Velo\FileSystem\PathResolver\Exceptions\PathNotFoundException;

/**
 * Sets and resolves paths.
 */
class PathResolver
{
    public const string ROOT_DIR_KEY = 'root';
    public const string PUBLIC_DIR_KEY = 'public';
    public const string VIEWS_DIR_KEY = 'views';
    private const string ERROR_GENERAL_KEY = 'error';
    private const string ERROR_KEYS_PREFIX = 'error_';

    /**
     * @var array<string, string>
     */
    private array $dirPaths = [];

    /**
     * @var array<string, string>
     */
    private array $filePaths = [];

    public function setDirPath(string $key, string $path): self
    {
        $this->dirPaths[$key] = $path;

        return $this;
    }

    /**
     * @throws PathNotFoundException
     */
    public function getDirPath(string $key): string
    {
        if (!isset($this->dirPaths[$key])) {
            throw new PathNotFoundException("The requested dir path \"$key\" not found!");
        }

        return rtrim($this->dirPaths[$key], '/') . '/';
    }

    public function setFilePath(string $key, string $path): self
    {
        $this->filePaths[$key] = $path;

        return $this;
    }

    /**
     * @throws PathNotFoundException
     */
    public function getFilePath(string $key): string
    {
        if (!$this->isFileRegistered($key)) {
            throw new PathNotFoundException("The requested file path \"$key\" not found!");
        }

        return $this->filePaths[$key];
    }

    /**
     * @return string Path relative to the views dir.
     *
     * @throws PathNotFoundException
     */
    public function getErrorFilePath(int $statusCode): string
    {
        return $this->getFilePath(self::ERROR_KEYS_PREFIX . $statusCode);
    }

    /**
     * @return string Path relative to the views dir.
     *
     * @throws PathNotFoundException
     */
    public function getErrorGeneralFilePath(): string
    {
        return $this->getFilePath(self::ERROR_GENERAL_KEY);
    }

    /**
     * @param string $filePath Must be relative to the views dir.
     */
    public function setErrorFilePath(int $statusCode, string $filePath): self
    {
        return $this->setFilePath(self::ERROR_KEYS_PREFIX . $statusCode, $filePath);
    }

    /**
     * @param string $filePath Must be relative to the views dir.
     */
    public function setErrorGeneralFilePath(string $filePath): self
    {
        return $this->setFilePath(self::ERROR_GENERAL_KEY, $filePath);
    }

    public function isDirRegistered(string $key): bool
    {
        return isset($this->dirPaths[$key]);
    }

    public function isFileRegistered(string $key): bool
    {
        return isset($this->filePaths[$key]);
    }

    public function isErrorFileRegistered(int $statusCode): bool
    {
        return $this->isFileRegistered(self::ERROR_KEYS_PREFIX . $statusCode);
    }

    public function isErrorGeneralFileRegistered(): bool
    {
        return $this->isFileRegistered(self::ERROR_GENERAL_KEY);
    }

    /**
     * It resolves the file path for a given error status code.
     *
     * It tries to find a specific error file first, and if not found, falls back to the general error file.
     * If it's not set either, it returns false.
     *
     * @return string|false String - filePath on success, false on failure.
     *
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function resolveErrorFilePath(int $statusCode): string|false
    {
        if ($this->isErrorFileRegistered($statusCode)) {
            return $this->getErrorFilePath($statusCode);
        }

        if ($this->isErrorGeneralFileRegistered()) {
            return $this->getErrorGeneralFilePath();
        }

        return false;
    }
}