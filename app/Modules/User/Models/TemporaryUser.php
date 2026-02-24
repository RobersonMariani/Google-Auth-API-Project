<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Builder;
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
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<self> whereEmail(string $email)
 * @method static Builder<self> where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder<self> notExpired()
 * @method static self create(array<string, mixed> $attributes = [])
 * @method static Builder<self> orderBy(string $column, string $direction = 'asc')
 * @method static Builder<self> when(mixed $value, callable $callback, ?callable $default = null)
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, self> paginate(int $perPage = 15, array<int, string> $columns = ['*'], string $pageName = 'page', ?int $page = null)
 */
class TemporaryUser extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\Modules\User\Models\TemporaryUserFactory> */
    use HasFactory;

    public const EXPIRATION_MINUTES = 15;

    /**
     * Atributos permitidos para atribuição em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'google_id',
        'google_token',
        'expires_at',
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

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Filtra apenas registros não expirados.
     *
     * @param Builder<self> $query
     *
     * @return Builder<self>
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}
