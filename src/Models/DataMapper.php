<?php
declare(strict_types=1);

namespace Velo\Models;

use PDO;

abstract class DataMapper
{
    public function __construct(protected PDO $pdo)
    {

    }
}