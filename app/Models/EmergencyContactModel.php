<?php
/**
 * EmergencyContactModel — secondary emergency contacts for staff.
 *
 * The crew_profiles table carries a single primary contact (emergency_name /
 * emergency_phone / emergency_relation). This table supports additional
 * contacts with full details. Row is_primary=1 mirrors the crew_profiles
 * primary for display consistency.
 */
class EmergencyContactModel {

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM emergency_contacts WHERE id = ?", [$id]);
    }

    public static function forUser(int $userId): array {
        return Database::fetchAll(
            "SELECT * FROM emergency_contacts WHERE user_id = ?
             ORDER BY is_primary DESC, sort_order ASC, id ASC",
            [$userId]
        );
    }

    public static function create(int $userId, int $tenantId, array $data): int {
        return Database::insert(
            "INSERT INTO emergency_contacts
             (tenant_id, user_id, contact_name, relation, phone_primary, phone_alt, email, address, is_primary, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $tenantId,
                $userId,
                trim($data['contact_name'] ?? ''),
                !empty($data['relation']) ? $data['relation'] : null,
                !empty($data['phone_primary']) ? $data['phone_primary'] : null,
                !empty($data['phone_alt'])     ? $data['phone_alt']     : null,
                !empty($data['email'])         ? $data['email']         : null,
                !empty($data['address'])       ? $data['address']       : null,
                !empty($data['is_primary']) ? 1 : 0,
                (int) ($data['sort_order'] ?? 0),
            ]
        );
    }

    public static function update(int $id, array $data): void {
        Database::execute(
            "UPDATE emergency_contacts
             SET contact_name  = ?, relation = ?,
                 phone_primary = ?, phone_alt = ?,
                 email = ?, address = ?, is_primary = ?, sort_order = ?
             WHERE id = ?",
            [
                trim($data['contact_name'] ?? ''),
                !empty($data['relation']) ? $data['relation'] : null,
                !empty($data['phone_primary']) ? $data['phone_primary'] : null,
                !empty($data['phone_alt'])     ? $data['phone_alt']     : null,
                !empty($data['email'])         ? $data['email']         : null,
                !empty($data['address'])       ? $data['address']       : null,
                !empty($data['is_primary']) ? 1 : 0,
                (int) ($data['sort_order'] ?? 0),
                $id,
            ]
        );
    }

    public static function delete(int $id, int $userId): void {
        Database::execute(
            "DELETE FROM emergency_contacts WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }
}
