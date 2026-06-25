<?php

declare(strict_types=1);

use App\Core\Env;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;

$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/../app/Features'],
    isDevMode: true,
);

$config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

$connectionParams = [
    'driver'   => Env::get('DB_DRIVER', 'pdo_mysql'),
    'host'     => Env::get('DB_HOST', '127.0.0.1'),
    'port'     => Env::get('DB_PORT', '3306'),
    'dbname'   => Env::get('DB_NAME', 'analiticaDB'),
    'user'     => Env::get('DB_USER', 'analiticaDBUser'),
    'password' => Env::get('DB_PASS', 'wOKUNNvbNgdK6jfihzW/9Q=='),
];

$connection = DriverManager::getConnection($connectionParams);

return new EntityManager($connection, $config);
