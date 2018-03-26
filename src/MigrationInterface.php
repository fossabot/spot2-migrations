<?php

namespace CoiSA\Spot\Migration;

use Spot\Locator;

/**
 * Interface MigrationInterface
 *
 * @package CoiSA\Spot\Migration
 */
interface MigrationInterface
{
    /**
     * MigrationInterface constructor.
     *
     * @param Locator $locator Spot Mapper Locator Connection Object
     * @param string $path optional Where to start looking for entities
     */
    public function __construct(Locator $locator, string $path = null);

    /**
     * Returns the schema create SQL queries
     *
     * @return string
     */
    public function getCreateSql(): string;

    /**
     * Returns the difference between the connection schema and the current state of entity files
     *
     * @return string
     */
    public function getUpdateSql(): string;

    /**
     * Returns the drop queries of tables found in entities files
     *
     * @return string
     */
    public function getDropSql(): string;
}