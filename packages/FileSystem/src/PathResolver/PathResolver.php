<?php
declare(strict_types=1);

namespace Velo\FileSystem\PathResolver;

use Velo\FileSystem\PathResolver\Exceptions\PathNotFoundException;

/**
 * Sets and resolves given paths.
 */
class PathResolver
{
    private array $dirPaths = [];
    private array $filePaths = [];

    public function __construct(
        string  $basePath,
        string  $publicPath,
        string  $viewsPath,
        ?string $errorGeneralPath = null,
        ?string $error403Path = null,
        ?string $error404Path = null,
        ?string $error500Path = null,
    )
    {
        $this->dirPaths['base'] = $basePath;
        $this->dirPaths['public'] = $publicPath;
        $this->dirPaths['views'] = $viewsPath;
        $this->filePaths['error403'] = $error403Path;
        $this->filePaths['error404'] = $error404Path;
        $this->filePaths['error500'] = $error500Path;
        $this->filePaths['error'] = $errorGeneralPath;
    }

    /**
     * Sets the given directory path to the given key.
     */
    public function setDirPath(string $key, string $path): self
    {
        $this->dirPaths[$key] = $path;

        return $this;
    }

    /**
     * Gets the directory path for the given key.
     *
     * @throws PathNotFoundException
     */
    public function getDirPath(string $key): string
    {
        if (!isset($this->dirPaths[$key])) {
            throw new PathNotFoundException("The requested dir path \"$key\" not found!");
        }

        return rtrim($this->dirPaths[$key], '/') . '/';
    }

    /**
     * Sets the given file path to the given key.
     */
    public function setFilePath(string $key, string $path): self
    {
        $this->filePaths[$key] = $path;

        return $this;
    }

    /**
     * Gets the file path for the given key.
     *
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
     * Returns if the given directory path is set.
     */
    public function isDirRegistered(string $path): bool
    {
        return isset($this->dirPaths[$path]);
    }

    /**
     * Returns if the given file path is set.
     */
    public function isFileRegistered(string $path): bool
    {
        return isset($this->filePaths[$path]);
    }
}