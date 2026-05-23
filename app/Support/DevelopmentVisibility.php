<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

trait DevelopmentVisibility
{
    protected function currentAuthUserId(): ?int
    {
        $id = session('auth_user.id');
        return $id ? (int) $id : null;
    }

    protected function currentUserIsAdmin(): bool
    {
        $userId = $this->currentAuthUserId();
        if (!$userId) {
            return false;
        }

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return false;
        }

        $roleName = DB::table('roles')->where('id', $user->role_id)->value('nombre');

        return mb_strtolower(trim((string) $roleName)) === 'admin';
    }

    protected function visibleDevelopmentIdsForCurrentUser(): array
    {
        $userId = $this->currentAuthUserId();
        if (!$userId) {
            return [];
        }

        return $this->visibleDevelopmentIdsForUser($userId);
    }

    protected function visibleDevelopmentIdsForUser(int $userId): array
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return [];
        }

        $roleName = DB::table('roles')->where('id', $user->role_id)->value('nombre');
        if (mb_strtolower(trim((string) $roleName)) === 'admin') {
            return DB::table('developments')
                ->whereNull('fecha_baja')
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->values()
                ->all();
        }

        $roleIds = DB::table('role_developments')
            ->where('role_id', $user->role_id)
            ->pluck('development_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $userIds = DB::table('user_developments')
            ->where('user_id', $userId)
            ->pluck('development_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        return collect(array_merge($roleIds, $userIds))
            ->unique()
            ->values()
            ->all();
    }

    protected function applyDevelopmentVisibilityFilter(Builder $query, string $developmentColumn = 'development_id'): Builder
    {
        if ($this->currentUserIsAdmin()) {
            return $query;
        }

        $visibleIds = $this->visibleDevelopmentIdsForCurrentUser();

        if (empty($visibleIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($developmentColumn, $visibleIds);
    }

    protected function canAccessDevelopment(int $developmentId): bool
    {
        if ($this->currentUserIsAdmin()) {
            return true;
        }

        return in_array($developmentId, $this->visibleDevelopmentIdsForCurrentUser(), true);
    }

    protected function abortIfCannotAccessDevelopment(int $developmentId): void
    {
        abort_if(!$this->canAccessDevelopment($developmentId), 403, 'No tienes permiso para acceder a esta lotificación.');
    }

    protected function abortIfCannotAccessContract(int $contractId): void
    {
        $developmentId = DB::table('contracts')
            ->where('id', $contractId)
            ->whereNull('fecha_baja')
            ->value('development_id');

        abort_if(!$developmentId, 404, 'Contrato no encontrado.');

        $this->abortIfCannotAccessDevelopment((int) $developmentId);
    }

    protected function abortIfCannotAccessReservation(int $reservationId): void
    {
        $developmentId = DB::table('reservations')
            ->where('id', $reservationId)
            ->whereNull('fecha_baja')
            ->value('development_id');

        abort_if(!$developmentId, 404, 'Apartado no encontrado.');

        $this->abortIfCannotAccessDevelopment((int) $developmentId);
    }

    protected function abortIfCannotAccessLot(int $lotId): void
    {
        $developmentId = DB::table('lots')
            ->where('id', $lotId)
            ->whereNull('fecha_baja')
            ->value('development_id');

        abort_if(!$developmentId, 404, 'Lote no encontrado.');

        $this->abortIfCannotAccessDevelopment((int) $developmentId);
    }

    protected function abortIfCannotAccessCharge(int $chargeId): void
    {
        $charge = DB::table('charges')
            ->where('id', $chargeId)
            ->whereNull('fecha_baja')
            ->first();

        abort_if(!$charge, 404, 'Cobro no encontrado.');

        if (!empty($charge->contract_id)) {
            $this->abortIfCannotAccessContract((int) $charge->contract_id);
            return;
        }

        if (!empty($charge->reservation_id)) {
            $this->abortIfCannotAccessReservation((int) $charge->reservation_id);
            return;
        }

        abort(403, 'No tienes permiso para acceder a este cobro.');
    }
}