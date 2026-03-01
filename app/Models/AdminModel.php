<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modèle Admin — gestion des comptes administrateurs.
 */
class AdminModel extends Model
{
    protected string $table = 'admins';

    /* ── Authentification ────────────────────────────────────────────────── */

    public function findByUsername(string $username): ?array
    {
        return $this->findWhere(['username' => $username]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->findWhere(['email' => $email]);
    }

    public function findActive(int $id): ?array
    {
        return $this->queryOne(
            "SELECT id, username, email, display_name, avatar, role, is_active
             FROM admins WHERE id = ? AND is_active = 1",
            [$id]
        );
    }

    /* ── Liste ───────────────────────────────────────────────────────────── */

    public function allWithStats(): array
    {
        return $this->query(
            "SELECT a.*, (SELECT COUNT(*) FROM admins a2 WHERE a2.role = a.role) AS role_count
             FROM admins a ORDER BY a.created_at DESC"
        );
    }

    public function countActive(): int
    {
        return $this->count(['is_active' => 1]);
    }

    /* ── Mise à jour ─────────────────────────────────────────────────────── */

    public function updatePassword(int $id, string $hashedPassword): void
    {
        $this->update($id, ['password' => $hashedPassword, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function updateLastLogin(int $id): void
    {
        $this->execute(
            "UPDATE admins SET last_login_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    public function toggle(int $id): void
    {
        $this->execute(
            "UPDATE admins SET is_active = NOT is_active WHERE id = ?",
            [$id]
        );
    }

    /* ── Reset de mot de passe ───────────────────────────────────────────── */

    public function createResetToken(int $adminId, string $token): void
    {
        $this->execute(
            "INSERT INTO password_resets (admin_id, token, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
             ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)",
            [$adminId, hash('sha256', $token)]
        );
    }

    public function findByResetToken(string $token): ?array
    {
        return $this->queryOne(
            "SELECT pr.admin_id, a.email
             FROM password_resets pr
             JOIN admins a ON a.id = pr.admin_id
             WHERE pr.token = ? AND pr.expires_at > NOW()",
            [hash('sha256', $token)]
        );
    }

    public function deleteResetToken(int $adminId): void
    {
        $this->execute("DELETE FROM password_resets WHERE admin_id = ?", [$adminId]);
    }
}
