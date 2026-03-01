<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modèle CRM Devis / Facture.
 */
class CrmDevisModel extends Model
{
    protected string $table = 'crm_devis';

    /* ── Stats dashboard ─────────────────────────────────────────────────── */

    public function stats(): array
    {
        return [
            'devis_count'    => (int)$this->queryValue("SELECT COUNT(*) FROM crm_devis WHERE type='devis'"),
            'factures_count' => (int)$this->queryValue("SELECT COUNT(*) FROM crm_devis WHERE type='facture'"),
            'pending'        => (int)$this->queryValue("SELECT COUNT(*) FROM crm_devis WHERE status='sent'"),
            'ca_ttc'         => (float)$this->queryValue(
                "SELECT COALESCE(SUM(total_ttc),0) FROM crm_devis WHERE status IN ('accepted','invoiced','paid')"
            ),
        ];
    }

    /* ── Listes ──────────────────────────────────────────────────────────── */

    public function recentWithClient(int $limit = 6): array
    {
        return $this->query(
            "SELECT d.id, d.ref, d.type, d.status, d.total_ttc, d.issued_at, d.created_at,
                    c.name AS client_name
             FROM crm_devis d
             JOIN crm_clients c ON c.id = d.client_id
             ORDER BY d.created_at DESC
             LIMIT $limit"
        );
    }

    public function allForClient(int $clientId): array
    {
        return $this->query(
            "SELECT * FROM crm_devis WHERE client_id = ? ORDER BY created_at DESC",
            [$clientId]
        );
    }

    public function findWithClient(int $id): ?array
    {
        return $this->queryOne(
            "SELECT d.*, c.name AS client_name, c.email AS client_email,
                    c.phone AS client_phone, c.address AS client_address,
                    c.city AS client_city, c.zip AS client_zip
             FROM crm_devis d
             JOIN crm_clients c ON c.id = d.client_id
             WHERE d.id = ?",
            [$id]
        );
    }

    /* ── Lignes ──────────────────────────────────────────────────────────── */

    public function getLines(int $devisId): array
    {
        return $this->query(
            "SELECT * FROM crm_devis_lines WHERE devis_id = ? ORDER BY sort_order ASC",
            [$devisId]
        );
    }

    public function saveLines(int $devisId, array $lines): void
    {
        $this->execute("DELETE FROM crm_devis_lines WHERE devis_id = ?", [$devisId]);
        foreach ($lines as $pos => $line) {
            $this->execute(
                "INSERT INTO crm_devis_lines (devis_id, label, description, qty, unit_price, tva_rate, sort_order)
                 VALUES (?,?,?,?,?,?,?)",
                [
                    $devisId,
                    $line['label']       ?? '',
                    $line['description'] ?? '',
                    (float)($line['qty']        ?? 1),
                    (float)($line['unit_price'] ?? 0),
                    (float)($line['tva_rate']   ?? 20),
                    $pos,
                ]
            );
        }
    }

    /* ── Ref auto ────────────────────────────────────────────────────────── */

    public function generateRef(string $type = 'devis'): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        $year   = date('Y');
        $last   = $this->queryValue(
            "SELECT ref FROM crm_devis WHERE type = ? AND YEAR(created_at) = ? ORDER BY id DESC LIMIT 1",
            [$type, $year]
        );
        $n = 1;
        if ($last) {
            preg_match('/(\d+)$/', $last, $m);
            $n = (int)($m[1] ?? 0) + 1;
        }
        return "$prefix-$year-" . str_pad($n, 4, '0', STR_PAD_LEFT);
    }
}
