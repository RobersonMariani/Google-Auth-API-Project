<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model responsável por registrar os logs de envio de e-mails.
 *
 * @method static MailLog create(array<string, mixed> $attributes = [])
 * @method static \Database\Factories\Modules\User\Models\MailLogFactory factory(...$parameters)
 *
 */
class MailLog extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\Modules\User\Models\MailLogFactory> */
    use HasFactory;

    /**
     * Atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_email',
        'subject',
        'status',
        'sent_at',
    ];

    /**
     * Conversão automática de tipos de atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
