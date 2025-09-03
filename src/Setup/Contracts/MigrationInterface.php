<?php
/**
 * Migration Interface
 * 
 * Contract for database/options migrations.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Setup\Contracts;

/**
 * Migration Interface
 * 
 * Defines the contract for plugin upgrade migrations.
 * 
 * @since 2.0.0
 */
interface MigrationInterface {

    /**
     * Get the target version for this migration
     * 
     * @return string Version string (e.g., '1.1.0')
     */
    public function targetVersion(): string;

    /**
     * Run the migration
     * 
     * @return bool True on success, false on failure
     */
    public function run(): bool;

    /**
     * Rollback the migration (optional)
     * 
     * @return bool True on success, false on failure
     */
    public function rollback(): bool;

    /**
     * Get migration description
     * 
     * @return string Migration description
     */
    public function getDescription(): string;
}