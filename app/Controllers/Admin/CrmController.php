<?php

namespace App\Controllers\Admin;

use App\Core\AdminController;
use App\Models\CrmClientModel;
use App\Models\CrmDevisModel;

/**
 * Contrôleur Admin — CRM (clients, devis, factures).
 */
class CrmController extends AdminController
{
    private CrmClientModel $clients;
    private CrmDevisModel  $devis;

    public function __construct()
    {
        parent::__construct();
        $this->requirePermission('crm');
        $this->clients = new CrmClientModel();
        $this->devis   = new CrmDevisModel();
    }

    /* ── Clients ─────────────────────────────────────────────────────────── */

    public function clients(): void
    {
        $search = $this->inputStr('q');
        $type   = $this->inputStr('type');
        $page   = max(1, $this->inputInt('p', 1));

        $result = $this->clients->paginate($search, $type, $page);

        $this->render('admin/crm/clients', [
            'clients'   => $result['data'],
            'total'     => $result['total'],
            'pages'     => $result['pages'],
            'page'      => $page,
            'search'    => $search,
            'type'      => $type,
            'pageTitle' => 'Clients CRM',
        ]);
    }

    public function clientEdit(string $id = '0'): void
    {
        $client = (int)$id > 0 ? $this->clients->find((int)$id) : null;
        $this->render('admin/crm/client-edit', [
            'client'    => $client,
            'pageTitle' => $client ? 'Éditer — ' . htmlspecialchars($client['name']) : 'Nouveau client',
        ]);
    }

    public function clientStore(): void
    {
        $this->verifyCsrf();
        $id = $this->clients->create($this->collectClientData() + ['created_at' => date('Y-m-d H:i:s')]);
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/crm/clients/' . $id . '/edit?created=1');
    }

    public function clientUpdate(string $id): void
    {
        $this->verifyCsrf();
        $this->clients->update((int)$id, $this->collectClientData() + ['updated_at' => date('Y-m-d H:i:s')]);
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/crm/clients/' . $id . '/edit?updated=1');
    }

    public function clientDestroy(string $id): void
    {
        $this->verifyCsrf();
        $this->clients->delete((int)$id);
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/crm/clients?deleted=1');
    }

    /* ── Devis / Factures ────────────────────────────────────────────────── */

    public function devis(): void
    {
        $this->render('admin/crm/devis', [
            'items'     => $this->devis->recentWithClient(50),
            'stats'     => $this->devis->stats(),
            'pageTitle' => 'Devis & Factures',
        ]);
    }

    public function devisEdit(string $id = '0'): void
    {
        $item  = (int)$id > 0 ? $this->devis->findWithClient((int)$id) : null;
        $lines = (int)$id > 0 ? $this->devis->getLines((int)$id) : [];

        $this->render('admin/crm/devis-edit', [
            'item'      => $item,
            'lines'     => $lines,
            'clients'   => $this->clients->paginate('', '', 1, 1000)['data'],
            'pageTitle' => $item ? 'Éditer — ' . ($item['ref'] ?? '') : 'Nouveau devis',
        ]);
    }

    public function devisStore(): void
    {
        $this->verifyCsrf();
        $data         = $this->collectDevisData();
        $data['ref']  = $this->devis->generateRef($data['type']);
        $id           = $this->devis->create($data);
        $this->devis->saveLines($id, $_POST['lines'] ?? []);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/crm/devis/' . $id . '/edit?created=1');
    }

    public function devisUpdate(string $id): void
    {
        $this->verifyCsrf();
        $this->devis->update((int)$id, $this->collectDevisData() + ['updated_at' => date('Y-m-d H:i:s')]);
        $this->devis->saveLines((int)$id, $_POST['lines'] ?? []);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/crm/devis/' . $id . '/edit?updated=1');
    }

    public function devisDestroy(string $id): void
    {
        $this->verifyCsrf();
        $this->devis->delete((int)$id);
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/crm/devis?deleted=1');
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    private function collectClientData(): array
    {
        return [
            'name'    => $this->inputStr('name'),
            'email'   => $this->inputStr('email'),
            'phone'   => $this->inputStr('phone'),
            'company' => $this->inputStr('company'),
            'address' => $this->inputStr('address'),
            'zip'     => $this->inputStr('zip'),
            'city'    => $this->inputStr('city'),
            'type'    => $this->inputStr('type', 'particulier'),
            'notes'   => $_POST['notes'] ?? '',
        ];
    }

    private function collectDevisData(): array
    {
        return [
            'client_id'    => $this->inputInt('client_id'),
            'type'         => $this->inputStr('type', 'devis'),
            'status'       => $this->inputStr('status', 'draft'),
            'issued_at'    => $this->inputStr('issued_at') ?: null,
            'valid_until'  => $this->inputStr('valid_until') ?: null,
            'notes'        => $_POST['notes'] ?? '',
            'conditions'   => $_POST['conditions'] ?? '',
            'total_ht'     => (float)($this->inputStr('total_ht') ?: 0),
            'total_ttc'    => (float)($this->inputStr('total_ttc') ?: 0),
            'created_at'   => date('Y-m-d H:i:s'),
        ];
    }
}
