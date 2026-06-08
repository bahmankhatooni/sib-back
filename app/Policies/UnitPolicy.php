<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Unit;

class UnitPolicy
{
    /**
     * بررسی آیا کاربر ادمین است
     */
    public function isAdmin(User $user)
    {
        return $user->role && $user->role->code === 'ADMIN';
    }

    /**
     * مشاهده لیست واحدها
     */
    public function viewAny(User $user)
    {
        return $this->isAdmin($user);
    }

    /**
     * مشاهده یک واحد
     */
    public function view(User $user, Unit $unit)
    {
        return $this->isAdmin($user);
    }

    /**
     * ایجاد واحد جدید
     */
    public function create(User $user)
    {
        return $this->isAdmin($user);
    }

    /**
     * ویرایش واحد
     */
    public function update(User $user, Unit $unit)
    {
        return $this->isAdmin($user);
    }

    /**
     * حذف واحد
     */
    public function delete(User $user, Unit $unit)
    {
        return $this->isAdmin($user);
    }
}
