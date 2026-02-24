<?php

namespace App\Modules\User\Commands;

use App\Modules\User\Models\TemporaryUser;
use Illuminate\Console\Command;

/**
 * Remove registros expirados da tabela de usuários temporários.
 */
class CleanExpiredTemporaryUsersCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'temporary-users:cleanup';

    /**
     * @var string
     */
    protected $description = 'Remove usuários temporários expirados';

    public function handle(): int
    {
        $deleted = TemporaryUser::where('expires_at', '<=', now())->delete();

        $this->info("Removidos {$deleted} usuário(s) temporário(s) expirado(s).");

        return self::SUCCESS;
    }
}
