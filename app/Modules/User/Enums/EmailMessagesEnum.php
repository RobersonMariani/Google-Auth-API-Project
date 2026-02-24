<?php

namespace App\Modules\User\Enums;

enum EmailMessagesEnum: string
{
    case REGISTRATION_SUBJECT = 'Cadastro concluído';
    case REGISTRATION_BODY    = 'Seu cadastro foi concluído com sucesso!';
}
