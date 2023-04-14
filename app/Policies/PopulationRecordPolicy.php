<?php

namespace App\Policies;

use App\Models\PopulationRecord;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PopulationRecordPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // return $user->hasRole(['Superadmin', 'Enumerator']);
        return $user->hasRole(['Barangay', 'Enumerator']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PopulationRecord $populationRecord): bool
    {
        // return $user->hasRole(['Superadmin', 'Enumerator']);
        return $user->hasRole(['Barangay', 'Enumerator']); 
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Enumerator']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PopulationRecord $populationRecord): bool
    {
        return $user->hasRole(['Enumerator']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PopulationRecord $populationRecord): bool
    {
        return $user->hasRole(['Barangay']);  
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PopulationRecord $populationRecord): bool
    {
        return $user->hasRole(['Superadmin']); 
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PopulationRecord $populationRecord): bool
    {
        return $user->hasRole(['Superadmin']);
    }
}
