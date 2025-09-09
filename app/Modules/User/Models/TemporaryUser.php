<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model que representa o usuário temporário autenticado via Google,
 * armazenado até que finalize o cadastro completo.
 *
 * @property int $id
 * @property string $email
 * @property string $google_id
 * @property string $google_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<self> whereEmail(string $email)
 * @method static \Illuminate\Database\Eloquent\Builder<self> where(string $column, mixed $operator = null, mixed $value = null)
 * @method static self create(array<string, mixed> $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder<self> orderBy(string $column, string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder<self> when(mixed $value, callable $callback, ?callable $default = null)
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, self> paginate(int $perPage = 15, array<int, string> $columns = ['*'], string $pageName = 'page', ?int $page = null)
 */
class TemporaryUser extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\Modules\User\Models\TemporaryUserFactory> */
    use HasFactory;

    /**
     * Atributos permitidos para atribuição em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'google_id',
        'google_token',
    ];

    /**
     * Atributos que não devem ser exibidos em arrays/JSON.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'google_id',
        'google_token',
    ];
}
