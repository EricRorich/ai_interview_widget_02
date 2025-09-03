<?php
/**
 * Version Manager
 * 
 * Manages plugin version tracking and migration execution.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Setup\Upgrade;

use EricRorich\AIInterviewWidget\Setup\Contracts\MigrationInterface;

/**
 * Version Manager class
 * 
 * Handles version tracking and migration orchestration.
 * 
 * @since 2.0.0
 */
class VersionManager {

    /**
     * Option name for storing plugin version
     * 
     * @var string
     */
    const VERSION_OPTION = 'ai_interview_widget_version';

    /**
     * Option name for storing migration history
     * 
     * @var string
     */
    const MIGRATIONS_OPTION = 'ai_interview_widget_migrations';

    /**
     * Current plugin version
     * 
     * @var string
     */
    private $current_version;

    /**
     * Constructor
     * 
     * @param string $current_version Current plugin version
     */
    public function __construct(string $current_version) {
        $this->current_version = $current_version;
    }

    /**
     * Get stored plugin version
     * 
     * @return string Stored version or '0.0.0' if not set
     */
    public function getStoredVersion(): string {
        return get_option(self::VERSION_OPTION, '0.0.0');
    }

    /**
     * Update stored plugin version
     * 
     * @param string $version Version to store
     * @return bool True on success, false on failure
     */
    public function updateStoredVersion(string $version): bool {
        return update_option(self::VERSION_OPTION, $version);
    }

    /**
     * Check if upgrade is needed
     * 
     * @return bool True if upgrade is needed
     */
    public function needsUpgrade(): bool {
        return version_compare($this->getStoredVersion(), $this->current_version, '<');
    }

    /**
     * Run pending migrations
     * 
     * @param array $migrations Array of migration instances
     * @return bool True if all migrations successful
     */
    public function runPendingMigrations(array $migrations): bool {
        if (!$this->needsUpgrade()) {
            return true;
        }

        $stored_version = $this->getStoredVersion();
        $migration_history = $this->getMigrationHistory();
        $success = true;

        foreach ($migrations as $migration) {
            if (!$migration instanceof MigrationInterface) {
                continue;
            }

            $target_version = $migration->targetVersion();
            
            // Skip if migration already run or target version is not newer
            if (in_array($target_version, $migration_history) || 
                version_compare($target_version, $stored_version, '<=')) {
                continue;
            }

            // Run migration
            if ($migration->run()) {
                $migration_history[] = $target_version;
                $this->updateMigrationHistory($migration_history);
                
                // Log successful migration
                error_log(sprintf(
                    'AI Interview Widget: Migration to %s completed successfully - %s',
                    $target_version,
                    $migration->getDescription()
                ));
            } else {
                $success = false;
                
                // Log failed migration
                error_log(sprintf(
                    'AI Interview Widget: Migration to %s failed - %s',
                    $target_version,
                    $migration->getDescription()
                ));
                break;
            }
        }

        // Update version if all migrations successful
        if ($success) {
            $this->updateStoredVersion($this->current_version);
        }

        return $success;
    }

    /**
     * Get migration history
     * 
     * @return array Array of completed migration versions
     */
    public function getMigrationHistory(): array {
        return get_option(self::MIGRATIONS_OPTION, []);
    }

    /**
     * Update migration history
     * 
     * @param array $history Migration history array
     * @return bool True on success, false on failure
     */
    public function updateMigrationHistory(array $history): bool {
        return update_option(self::MIGRATIONS_OPTION, $history);
    }

    /**
     * Clear migration history (for testing/debugging)
     * 
     * @return bool True on success, false on failure
     */
    public function clearMigrationHistory(): bool {
        return delete_option(self::MIGRATIONS_OPTION);
    }

    /**
     * Reset version (for testing/debugging)
     * 
     * @return bool True on success, false on failure
     */
    public function resetVersion(): bool {
        return delete_option(self::VERSION_OPTION);
    }

    /**
     * Get current plugin version
     * 
     * @return string Current version
     */
    public function getCurrentVersion(): string {
        return $this->current_version;
    }
}