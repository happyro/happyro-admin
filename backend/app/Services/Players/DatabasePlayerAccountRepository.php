<?php

namespace App\Services\Players;

use App\Contracts\Players\PlayerAccountRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DatabasePlayerAccountRepository implements PlayerAccountRepository
{
    private function query(): Builder
    {
        return DB::connection('game')->table(config('happyro.players.account_table', 'login'));
    }

    public function paginate(?string $username, ?string $email, ?string $status, int $perPage): LengthAwarePaginator
    {
        $query = $this->query()->select(['account_id', 'userid as username', 'email', 'sex', 'group_id', 'state', 'lastlogin', 'last_ip']);
        if ($username) {
            $query->where('userid', 'like', "%{$username}%");
        }
        if ($email) {
            $query->where('email', 'like', "%{$email}%");
        }
        if ($status === 'active') {
            $query->where('state', 0);
        }
        if ($status === 'banned') {
            $query->where('state', '>', 0);
        }

        return $query->orderByDesc('account_id')->paginate($perPage);
    }

    public function find(int $accountId): ?object
    {
        return $this->query()->where('account_id', $accountId)->first();
    }

    public function create(array $attributes): object
    {
        $id = $this->query()->insertGetId($attributes, 'account_id');

        return $this->find((int) $id);
    }

    public function update(int $accountId, array $attributes): object
    {
        $this->query()->where('account_id', $accountId)->update($attributes);

        return $this->find($accountId);
    }
}
