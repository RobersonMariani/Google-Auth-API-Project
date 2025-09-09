<?php

namespace App\Modules\User\Enums;

enum AuthMessagesEnum: string
{
    case INTEGRATION_ERROR      = 'Erro ao integrar com o Google.';
    case TEMPORARY_USER_CREATED = 'Usuário pendente criado com sucesso.';
    case TEMPORARY_USER_UPDATED = 'Usuário pendente atualizado com sucesso.';
    case USER_FOUND             = 'Usuário encontrado com sucesso.';
    case TOKEN_INVALID          = 'Token do Google inválido ou expirado.';
    case TOKEN_EXPIRED          = 'Token expirado. Por favor, autentique-se novamente.';
}
