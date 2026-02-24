<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Model que representa um usuário final cadastrado no sistema após autenticação via Google.
 *
 * @property int $id
 * @property string $name
 * @property string $cpf
 * @property \Illuminate\Support\Carbon $birth_date
 * @property string $google_token
 * @property string $google_email
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<self> where(string $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder<self> when(mixed $value, callable $callback, ?callable $default = null)
 * @method static static create(array<string, mixed> $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder<self> orderBy(string $column, string $direction = 'asc')
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, self> paginate(int $perPage = 15, array<int, string> $columns = ['*'], string $pageName = 'page', ?int $page = null)
 */
class User extends Authenticatable
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\Modules\User\Models\UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use SoftDeletes;

    /**
     * Atributos permitidos para atribuição em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'cpf',
        'birth_date',
        'google_token',
        'google_email',
    ];

    /**
     * Atributos que não devem ser exibidos em arrays/JSON.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'google_token',
        'deleted_at',
    ];

    /**
     * Conversão automática de tipos de atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'deleted_at' => 'datetime',
    ];
}
