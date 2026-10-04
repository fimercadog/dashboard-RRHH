<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employees.manage') || $user->hasPermissionTo('attendance.manage');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $employee->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('employees.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $employee->company_id === $user->company_id
            && $user->hasPermissionTo('employees.manage');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $employee->company_id === $user->company_id
            && $user->hasPermissionTo('employees.manage');
    }
}
