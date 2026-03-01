<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modèle Contact — gestion des demandes de contact et du pipeline CRM léger.
 */
class ContactModel extends Model
{
    protected string $table = 'contacts';

    /* ── Listes paginées ─────────────────────────────────────────────────── */

    /**
     * Retourne les contacts filtrés + paginés avec leurs tags.
     *
     * @param array $filters  ['search' => '', 'pipeline' => '', 'tag' => '', 'archived' => false, 'due' => false]
     * @param int   $page
     * @param int   $limit
     * @param string $orderCol
     * @param string $orderDir
     */
    public function paginate(
        array  $filters  = [],
        int    $page     = 1,
        int    $limit    = 20,
        string $orderCol = 'created_at',
        string $orderDir = 'desc'
    ): array {
        $offset = ($page - 1) * $limit;

        $allowedCols = [
            'created_at'      => 'c.created_at',
            'name'            => 'c.name',
            'city'            => 'c.city',
            'pipeline_status' => 'c.pipeline_status',
            'next_followup_at' => 'c.next_followup_at',
        ];
        $allowedDirs = ['asc', 'desc'];

        $orderCol = $allowedCols[$orderCol] ?? 'c.created_at';
        $orderDir = in_array(strtolower($orderDir), $allowedDirs, true) ? strtoupper($orderDir) : 'DESC';

        [$whereSql, $params] = $this->buildContactWhere($filters);

        $total = (int)$this->queryValue(
            "SELECT COUNT(*) FROM contacts c $whereSql",
            $params
        );

        $rows = $this->query(
            "SELECT c.*,
                    GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ',') AS tags
             FROM contacts c
             LEFT JOIN contact_tags ct ON ct.contact_id = c.id
             LEFT JOIN tags t          ON t.id = ct.tag_id
             $whereSql
             GROUP BY c.id
             ORDER BY $orderCol $orderDir
             LIMIT $limit OFFSET $offset",
            $params
        );

        return ['data' => $rows, 'total' => $total, 'pages' => max(1, (int)ceil($total / $limit))];
    }

    /* ── Stats ───────────────────────────────────────────────────────────── */

    public function stats(): array
    {
        return [
            'total'    => $this->count(),
            'new'      => $this->count(['status' => 'new']),
            'treated'  => $this->count(['status' => 'treated']),
            'today'    => (int)$this->queryValue("SELECT COUNT(*) FROM contacts WHERE DATE(created_at) = CURDATE()"),
            'month'    => (int)$this->queryValue("SELECT COUNT(*) FROM contacts WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"),
        ];
    }

    public function recentContacts(int $limit = 5): array
    {
        return $this->query(
            "SELECT id, name, service, status, city, created_at
             FROM contacts ORDER BY created_at DESC LIMIT $limit"
        );
    }

    /** Contacts par mois sur les 12 derniers mois (pour graphique). */
    public function contactsByMonth(): array
    {
        return $this->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS m,
                   SUM(status = 'new')     AS new_c,
                   SUM(status = 'treated') AS treated_c
            FROM contacts
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 11 MONTH)
            GROUP BY m
            ORDER BY m ASC
        ");
    }

    /** Top services (pour graphique). */
    public function topServices(int $limit = 6): array
    {
        return $this->query(
            "SELECT service, COUNT(*) AS c
             FROM contacts
             WHERE service IS NOT NULL AND service != ''
             GROUP BY service ORDER BY c DESC LIMIT $limit"
        );
    }

    /* ── Tags ────────────────────────────────────────────────────────────── */

    public function getTags(int $contactId): array
    {
        return $this->query(
            "SELECT t.id, t.name
             FROM tags t
             INNER JOIN contact_tags ct ON ct.tag_id = t.id
             WHERE ct.contact_id = ?",
            [$contactId]
        );
    }

    public function addTag(int $contactId, string $name): void
    {
        $tag = $this->queryOne("SELECT id FROM tags WHERE name = ?", [strtolower($name)]);
        if (!$tag) {
            $this->execute("INSERT INTO tags (name) VALUES (?)", [strtolower($name)]);
            $tagId = (int)$this->pdo->lastInsertId();
        } else {
            $tagId = (int)$tag['id'];
        }
        $this->execute(
            "INSERT IGNORE INTO contact_tags (contact_id, tag_id) VALUES (?, ?)",
            [$contactId, $tagId]
        );
    }

    public function removeTag(int $contactId, int $tagId): void
    {
        $this->execute(
            "DELETE FROM contact_tags WHERE contact_id = ? AND tag_id = ?",
            [$contactId, $tagId]
        );
    }

    /* ── Notes ───────────────────────────────────────────────────────────── */

    public function getNotes(int $contactId): array
    {
        return $this->query(
            "SELECT n.*, a.display_name AS admin_name
             FROM contact_notes n
             LEFT JOIN admins a ON a.id = n.admin_id
             WHERE n.contact_id = ?
             ORDER BY n.created_at DESC",
            [$contactId]
        );
    }

    public function addNote(int $contactId, int $adminId, string $content): void
    {
        $this->execute(
            "INSERT INTO contact_notes (contact_id, admin_id, content, created_at) VALUES (?,?,?,NOW())",
            [$contactId, $adminId, $content]
        );
    }

    /* ── Archive ─────────────────────────────────────────────────────────── */

    public function archive(int $id): void
    {
        $this->execute("UPDATE contacts SET archived_at = NOW() WHERE id = ?", [$id]);
    }

    public function restore(int $id): void
    {
        $this->execute("UPDATE contacts SET archived_at = NULL WHERE id = ?", [$id]);
    }

    /* ── Helpers privés ──────────────────────────────────────────────────── */

    private function buildContactWhere(array $f): array
    {
        $where  = [];
        $params = [];

        if (!empty($f['archived'])) {
            $where[] = "c.archived_at IS NOT NULL";
        } else {
            $where[] = "c.archived_at IS NULL";
        }

        if (!empty($f['due'])) {
            $where[] = "c.next_followup_at IS NOT NULL AND c.next_followup_at <= NOW()";
        }

        $allowedPipeline = ['new','in_progress','quoted','won','lost'];
        if (!empty($f['pipeline']) && in_array($f['pipeline'], $allowedPipeline, true)) {
            $where[]  = "c.pipeline_status = ?";
            $params[] = $f['pipeline'];
        }

        if (!empty($f['search'])) {
            $where[]  = "(c.name LIKE ? OR c.email LIKE ? OR c.city LIKE ?)";
            $term     = '%' . $f['search'] . '%';
            $params   = array_merge($params, [$term, $term, $term]);
        }

        if (!empty($f['tag'])) {
            $where[]  = "EXISTS (
                SELECT 1 FROM contact_tags ct2
                INNER JOIN tags t2 ON t2.id = ct2.tag_id
                WHERE ct2.contact_id = c.id AND t2.name = ?
            )";
            $params[] = strtolower($f['tag']);
        }

        $sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        return [$sql, $params];
    }
}
