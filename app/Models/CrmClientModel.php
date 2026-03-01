<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modèle CRM Client.
 */
class CrmClientModel extends Model
{
    protected string $table = 'crm_clients';

    /* ── Listes paginées ─────────────────────────────────────────────────── */

    public function paginate(string $search = '', string $type = '', int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = '(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.company LIKE ? OR c.ref LIKE ?)';
            $term     = "%$search%";
            $params   = array_merge($params, [$term, $term, $term, $term, $term]);
        }
        if ($type !== '') {
            $where[]  = 'c.type = ?';
            $params[] = $type;
        }

        $whereSql = implode(' AND ', $where);
        $total    = (int)$this->queryValue("SELECT COUNT(*) FROM crm_clients c WHERE $whereSql", $params);
        $rows     = $this->query(
            "SELECT c.*, (SELECT COUNT(*) FROM crm_devis d WHERE d.client_id = c.id) AS devis_count
             FROM crm_clients c
             WHERE $whereSql
             ORDER BY c.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return ['data' => $rows, 'total' => $total, 'pages' => max(1, (int)ceil($total / $perPage))];
    }

    public function countAll(): int
    {
        return $this->count();
    }

    /* ── Conversion ──────────────────────────────────────────────────────── */

    /**
     * Convertit un contact en client CRM.
     */
    public function createFromContact(array $contact): int
    {
        return (int)$this->create([
            'name'       => $contact['name'],
            'email'      => $contact['email'] ?? null,
            'phone'      => $contact['phone'] ?? null,
            'city'       => $contact['city']  ?? null,
            'type'       => 'particulier',
            'ref'        => 'CLI-' . strtoupper(substr(uniqid(), -6)),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
