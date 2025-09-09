<?php

namespace App\Modules\User\Enums;

enum UserMessagesEnum: string
{
    case CREATED   = 'Usuário criado com sucesso.';
    case UPDATED   = 'Usuário atualizado com sucesso.';
    case DELETED   = 'Usuário removido com sucesso.';
    case NOT_FOUND = 'Usuário não encontrado.';
    case LISTING   = 'Lista de usuários carregada com sucesso.';
}
