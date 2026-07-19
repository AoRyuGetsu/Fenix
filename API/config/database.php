<?php

declare(strict_types=1);

use App\Core\Env;
use App\Infrastructure\Doctrine\SoftDeleteFilter;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;

$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/../app/Features', __DIR__ . '/../app/Infrastructure'],
    isDevMode: Env::get('APP_ENV', 'production') === 'development',
);

$config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

$config->addFilter('soft-delete', SoftDeleteFilter::class);

$connectionParams = [
    'driver'   => Env::get('DB_DRIVER', 'pdo_mysql'),
    'host'     => Env::get('DB_HOST', '127.0.0.1'),
    'port'     => Env::get('DB_PORT', '3306'),
    'dbname'   => Env::get('DB_NAME', 'analiticaDB'),
    'user'     => Env::get('DB_USER', 'analiticaDBUser'),
    'password' => Env::get('DB_PASS', 'wOKUNNvbNgdK6jfihzW/9Q=='),
];

$connection = DriverManager::getConnection($connectionParams);

$em = new EntityManager($connection, $config);
$em->getFilters()->enable('soft-delete');

return $em;
