<?php

namespace App\Models\Scopes;

use App\Enums\RoleName;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class WeddingScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Never apply to admin or super_admin
        if ($user->hasRole(RoleName::ADMIN->value) || $user->hasRole(RoleName::SUPER_ADMIN->value)) {
            return;
        }

        // Applies only when user has role 'user'
        if (! $user->hasRole(RoleName::USER->value)) {
            return;
        }

        $userId = $user->id;

        if ($model instanceof Wedding) {
            $builder->where(function (Builder $query) use ($userId) {
                $query->where('weddings.owner_id', $userId)
                    ->orWhereExists(function ($sub) use ($userId) {
                        $sub->selectRaw('1')
                            ->from('wedding_members')
                            ->whereColumn('wedding_members.wedding_id', 'weddings.id')
                            ->where('wedding_members.user_id', $userId);
                    });
            });
        } else {
            $table = $model->getTable();
            $builder->where(function (Builder $query) use ($table, $userId) {
                $query->whereIn("{$table}.wedding_id", function ($sub) use ($userId) {
                    $sub->select('id')
                        ->from('weddings')
                        ->where('owner_id', $userId)
                        ->union(
                            WeddingMember::query()
                                ->select('wedding_id')
                                ->where('user_id', $userId)
                                ->toBase()
                        );
                });
            });
        }
    }
}
