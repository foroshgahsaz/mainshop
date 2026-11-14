<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Collection;

class UserSoftDeletionService
{
    /**
     * @return 'deleted'|'skipped'
     */
    public function delete(User $user): string
    {
        if ($user->orders()->withTrashed()->exists()) {
            return 'skipped';
        }

        $user->delete();

        return 'deleted';
    }

    /**
     * @param  Collection<int, User>|iterable<int, User>  $users
     */
    public function deleteMany(iterable $users): UserSoftDeletionResult
    {
        $deleted = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if ($this->delete($user) === 'skipped') {
                $skipped++;
            } else {
                $deleted++;
            }
        }

        return new UserSoftDeletionResult($deleted, $skipped);
    }
}
