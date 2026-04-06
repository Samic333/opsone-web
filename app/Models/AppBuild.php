<?php
/**
 * AppBuild Model — tracks enterprise build versions
 */
class AppBuild {
    public static function all(): array {
        try {
            return Database::fetchAll(
                "SELECT b.*, u.name as uploaded_by_name FROM app_builds b
                 LEFT JOIN users u ON b.uploaded_by = u.id
                 ORDER BY b.created_at DESC"
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM app_builds WHERE id = ?", [$id]);
    }

    public static function latest(): ?array {
        try {
            return Database::fetch(
                "SELECT * FROM app_builds WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1"
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO app_builds (version, build_number, platform, release_notes, file_path, file_size, min_os_version, is_active, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['version'], $data['build_number'], $data['platform'] ?? 'ios',
                $data['release_notes'] ?? null, $data['file_path'] ?? null,
                $data['file_size'] ?? 0, $data['min_os_version'] ?? '16.0',
                $data['is_active'] ?? 1, $data['uploaded_by'] ?? null,
            ]
        );
    }

    public static function deactivateAll(): void {
        Database::execute("UPDATE app_builds SET is_active = 0");
    }
}
