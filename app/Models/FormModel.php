<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modèle Formulaire — formulaires dynamiques et soumissions.
 */
class FormModel extends Model
{
    protected string $table = 'forms';

    /* ── Formulaires ─────────────────────────────────────────────────────── */

    public function allWithSubmissionCount(): array
    {
        return $this->query(
            "SELECT f.*, (SELECT COUNT(*) FROM form_submissions s WHERE s.form_id = f.id) AS sub_count
             FROM forms f ORDER BY f.created_at ASC"
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->findWhere(['slug' => $slug, 'is_active' => 1]);
    }

    public function duplicate(int $id): ?int
    {
        $original = $this->find($id);
        if (!$original) return null;

        unset($original['id'], $original['created_at'], $original['updated_at']);
        $original['name']       = $original['name'] . ' (copie)';
        $original['slug']       = $original['slug'] . '-copie-' . time();
        $original['created_at'] = date('Y-m-d H:i:s');

        return (int)$this->create($original);
    }

    /* ── Soumissions ─────────────────────────────────────────────────────── */

    public function getSubmissions(int $formId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $total  = (int)$this->queryValue(
            "SELECT COUNT(*) FROM form_submissions WHERE form_id = ?",
            [$formId]
        );
        $rows = $this->query(
            "SELECT * FROM form_submissions
             WHERE form_id = ?
             ORDER BY submitted_at DESC
             LIMIT $limit OFFSET $offset",
            [$formId]
        );
        return ['data' => $rows, 'total' => $total, 'pages' => max(1, (int)ceil($total / $limit))];
    }

    public function countUnread(): int
    {
        return (int)$this->queryValue(
            "SELECT COUNT(*) FROM form_submissions WHERE is_read = 0"
        );
    }

    public function markRead(int $submissionId): void
    {
        $this->execute(
            "UPDATE form_submissions SET is_read = 1 WHERE id = ?",
            [$submissionId]
        );
    }

    public function deleteSubmission(int $id): void
    {
        $this->execute("DELETE FROM form_submissions WHERE id = ?", [$id]);
    }

    public function clearSubmissions(int $formId): void
    {
        $this->execute("DELETE FROM form_submissions WHERE form_id = ?", [$formId]);
    }

    public function exportSubmissions(int $formId): array
    {
        return $this->query(
            "SELECT * FROM form_submissions WHERE form_id = ? ORDER BY submitted_at ASC",
            [$formId]
        );
    }
}
