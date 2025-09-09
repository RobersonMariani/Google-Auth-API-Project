<?php

namespace App\Modules\User\Jobs;

use App\Modules\User\Models\MailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Job responsável por enviar o e-mail de confirmação de cadastro
 * e registrar o log do envio na base de dados.
 */
class SendRegistrationEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Endereço de e-mail do destinatário.
     *
     */
    protected string $email;

    /**
     * Cria uma nova instância do job.
     *
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * Executa o job: envia o e-mail e registra o log do envio.
     *
     */
    public function handle(): void
    {
        try {
            Log::info('Sending registration email to: ' . $this->email);

            Mail::raw('Seu cadastro foi concluído com sucesso!', function ($message) {
                $message->to($this->email)->subject('Cadastro concluído');
            });

            MailLog::create([
                'user_email' => $this->email,
                'subject'    => 'Cadastro concluído',
                'status'     => 'sent',
                'sent_at'    => now(),
            ]);
        } catch (Throwable $e) {
            MailLog::create([
                'user_email' => $this->email,
                'subject'    => 'Cadastro concluído',
                'status'     => 'failed',
                'sent_at'    => now(),
            ]);
        }
    }
}
