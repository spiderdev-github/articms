<?php

namespace App\Core;

use PDO;

/**
 * Classe de base pour tous les modèles.
 * Injecte automatiquement PDO et propose des helpers CRUD génériques.
 */
abstract class Model
{
    protected PDO $pdo;

    /** Nom de la table (à définir dans chaque modèle enfant). */
    protected string $table = '';

    /** Clé primaire (personnalisable). */
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /* ── CRUD de base ────────────────────────────────────────────────────── */

    /** Renvoie tous les enregistrements. */
    public function all(string $order = ''): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($order) {
            $sql .= " ORDER BY $order";
        }
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Trouve un enregistrement par sa clé primaire. */
    public function find(int|string $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Trouve le premier enregistrement correspondant aux conditions. */
    public function findWhere(array $conditions): ?array
    {
        [$where, $params] = $this->buildWhere($conditions);
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->table}` WHERE $where LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Retourne tous les enregistrements correspondant aux conditions. */
    public function where(array $conditions, string $order = '', int $limit = 0, int $offset = 0): array
    {
        [$where, $params] = $this->buildWhere($conditions);
        $sql = "SELECT * FROM `{$this->table}` WHERE $where";
        if ($order)  $sql .= " ORDER BY $order";
        if ($limit)  $sql .= " LIMIT $limit";
        if ($offset) $sql .= " OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insère un enregistrement et retourne son ID.
     *
     * @param array $data  ['colonne' => 'valeur', ...]
     */
    public function create(array $data): int|string
    {
        $cols   = array_keys($data);
        $fields = implode(', ', array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));

        $sql  = "INSERT INTO `{$this->table}` ($fields) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return $this->pdo->lastInsertId();
    }

    /**
     * Met à jour un enregistrement par sa clé primaire.
     */
    public function update(int|string $id, array $data): bool
    {
        if (empty($data)) return false;

        $sets   = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;

        $stmt = $this->pdo->prepare("UPDATE `{$this->table}` SET $sets WHERE `{$this->primaryKey}` = ?");
        return $stmt->execute($values);
    }

    /**
     * Supprime un enregistrement par sa clé primaire.
     */
    public function delete(int|string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Compte les enregistrements (avec conditions optionnelles).
     */
    public function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            return (int)$this->pdo->query("SELECT COUNT(*) FROM `{$this->table}`")->fetchColumn();
        }
        [$where, $params] = $this->buildWhere($conditions);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->table}` WHERE $where");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /* ── Helpers SQL ─────────────────────────────────────────────────────── */

    /**
     * Prépare la clause WHERE depuis un tableau ['col' => 'val'].
     * Supporte la valeur NULL (IS NULL) et les tableaux (IN (...)).
     */
    protected function buildWhere(array $conditions): array
    {
        $clauses = [];
        $params  = [];

        foreach ($conditions as $col => $val) {
            if (is_null($val)) {
                $clauses[] = "`$col` IS NULL";
            } elseif (is_array($val)) {
                $in = implode(',', array_fill(0, count($val), '?'));
                $clauses[] = "`$col` IN ($in)";
                $params    = array_merge($params, $val);
            } else {
                $clauses[] = "`$col` = ?";
                $params[]  = $val;
            }
        }

        return [implode(' AND ', $clauses), $params];
    }

    /**
     * Exécute une requête SQL brute et retourne les résultats.
     * À n'utiliser que pour des requêtes complexes non couvertes par les helpers.
     */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    protected function queryValue(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    protected function execute(string $sql, array $params = []): bool
    {
        return $this->pdo->prepare($sql)->execute($params);
    }
}
