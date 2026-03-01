<?php

namespace App\Controllers\Admin;

use App\Core\AdminController;
use App\Models\AdminModel;
use App\Core\Auth;

/**
 * Contrôleur Admin — gestion des utilisateurs.
 */
class UserController extends AdminController
{
    private AdminModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requirePermission('users');
        $this->model = new AdminModel();
    }

    public function index(): void
    {
        $this->render('admin/users/index', [
            'users'     => $this->model->allWithStats(),
            'roles'     => Auth::ROLES,
            'labels'    => Auth::ROLES_LABELS,
            'colors'    => Auth::ROLES_COLORS,
            'pageTitle' => 'Utilisateurs',
        ]);
    }

    public function create(): void
    {
        $this->render('admin/users/edit', [
            'user'      => null,
            'roles'     => Auth::ROLES,
            'labels'    => Auth::ROLES_LABELS,
            'pageTitle' => 'Nouvel utilisateur',
        ]);
    }

    public function edit(string $id): void
    {
        $user = $this->model->find((int)$id);
        if (!$user) $this->abort(404);

        $this->render('admin/users/edit', [
            'user'      => $user,
            'roles'     => Auth::ROLES,
            'labels'    => Auth::ROLES_LABELS,
            'pageTitle' => 'Éditer — ' . htmlspecialchars($user['username']),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $password = $_POST['password'] ?? '';
        if (strlen($password) < 8) {
            $this->abort(422, 'Le mot de passe doit faire au moins 8 caractères.');
        }

        $this->model->create([
            'username'     => $this->inputStr('username'),
            'email'        => $this->inputStr('email'),
            'display_name' => $this->inputStr('display_name'),
            'role'         => $this->inputStr('role'),
            'password'     => password_hash($password, PASSWORD_DEFAULT),
            'is_active'    => 1,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/users?created=1');
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $data = [
            'username'     => $this->inputStr('username'),
            'email'        => $this->inputStr('email'),
            'display_name' => $this->inputStr('display_name'),
            'role'         => $this->inputStr('role'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        $password = $_POST['password'] ?? '';
        if ($password !== '') {
            if (strlen($password) < 8) {
                $this->abort(422, 'Le mot de passe doit faire au moins 8 caractères.');
            }
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->model->update((int)$id, $data);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/users/' . $id . '/edit?updated=1');
    }

    public function toggle(string $id): void
    {
        $this->verifyCsrf();
        // Empêcher de se désactiver soi-même
        if ((int)$id === Auth::id()) $this->abort(403, 'Vous ne pouvez pas désactiver votre propre compte.');
        $this->model->toggle((int)$id);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/users');
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        if ((int)$id === Auth::id()) $this->abort(403, 'Vous ne pouvez pas supprimer votre propre compte.');
        $this->model->delete((int)$id);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/users?deleted=1');
    }
}
