<?php

namespace CoiSA\Spot\Migration;

use Spot\Locator;
use Spot\Mapper;
use Symfony\Component\Finder\Finder;

/**
 * Class Migration
 *
 * @package CoiSA\Spot\Migration
 */
class Migration implements MigrationInterface
{
    /** @var string Path to looking for entities */
    private $path;

    /** @var Locator Spot Mapper Locator Connection Object */
    private $locator;

    /**
     * MigrationTool constructor.
     *
     * @param Locator $locator Spot Mapper Locator Connection Object
     * @param string $path optional Where to start looking for entities
     */
    public function __construct(Locator $locator, string $path = null)
    {
        $this->locator = $locator;
        $this->path = $path ?: dirname(__DIR__, 4);
    }

    /**
     * Returns the schema create SQL queries
     *
     * @return string
     */
    public function getCreateSql(): string
    {
        $queries = [];

        foreach ($this->getAllMappers() as $mapper) {
            $resolver = $mapper->resolver();

            $schema = $resolver->migrateCreateSchema();
            $queries += $schema->toSql($mapper->connection()->getDatabasePlatform());
        }

        return implode(";\n", $queries) . ';';
    }

    /**
     * Returns the difference between the connection schema and the current state of entity files
     *
     * @return string
     */
    public function getUpdateSql(): string
    {
        $queries = [];

        foreach ($this->getAllMappers() as $mapper) {
            $table = $mapper->table();
            $connection = $mapper->connection();
            $schemaManager = $connection->getSchemaManager();

            if (false === $schemaManager->tablesExist([$table])) {
                // Create new table
                $newSchema = $mapper->resolver()->migrateCreateSchema();
                $queries += $newSchema->toSql($connection->getDatabasePlatform());
            } else {
                // Update existing table
                $fromSchema = new \Doctrine\DBAL\Schema\Schema([
                    $schemaManager->listTableDetails($table)
                ]);
                $newSchema = $mapper->resolver()->migrateCreateSchema();
                $queries += $fromSchema->getMigrateToSql($newSchema, $connection->getDatabasePlatform());
            }
        }

        return implode(";\n", $queries) . ';';
    }

    /**
     * Returns the drop queries of tables found in entities files
     *
     * @return string
     */
    public function getDropSql(): string
    {
        $queries = [];

        foreach ($this->getAllMappers() as $mapper) {
            $table = $mapper->table();
            $schemaManager = $mapper->connection()->getSchemaManager();

            if ($schemaManager->tablesExist([$table])) {
                $queries []= $schemaManager->getDatabasePlatform()->getDropTableSQL($table);
            }
        }

        return implode("\n", $queries);
    }

    /**
     * Returns a collection of Mappers for each entity file found in the provided path
     *
     * @return Mapper[]
     */
    private function getAllMappers(): array
    {
        foreach ($this->getFinder()->getIterator() as $file) {
            include_once $file->getRealPath();
        }

        $mappers = [];

        foreach (get_declared_classes() as $className) {
            if ($className === 'Spot\\Entity') {
                continue;
            }

            if (in_array('Spot\\EntityInterface', class_implements($className))) {
                $mappers []= $this->locator->mapper($className);
            }
        }

        return $mappers;
    }

    /**
     * @return Finder
     */
    private function getFinder(): Finder
    {
        $finder = new Finder();

        $finder->files()
            ->in($this->path)
            ->name('/\.php$/')
            ->exclude('/.*vendor\/.*/')
            ->contains('Spot\\Entity');

        return $finder;
    }
}