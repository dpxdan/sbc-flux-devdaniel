<?php
// ##############################################################################
// Flux Telecom - Unindo pessoas e negócios
//
// Copyright (C) 2023 Flux Telecom
// Daniel Paixao <daniel@flux.net.br>
// FluxSBC Version 4.2 and above
// License https://www.gnu.org/licenses/agpl-3.0.html
// ##############################################################################

defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| Consulta de CPF/CNPJ - Configurações
| -------------------------------------------------------------------
| IMPORTANTE:
| - Nunca comite o token real em repositórios públicos.
| - Ajuste o token em produção via arquivo local ou variável de ambiente.
|
| Obs.: A API do CNPJá (api.cnpja.com) é para CNPJ (endpoint /office/:cnpj).
|       Caso você tenha um provedor para CPF, configure abaixo.
*/

$config['documents'] = array(
    // Token da API CNPJá (Commercial). Ex.: 73 caracteres.
    'cnpja_token' => '4a58f61e-0473-47f4-bebe-a48488e9d5f6-e04a1827-a50f-427b-b8a9-0e66a2580814',

    // Base URL do provedor CNPJá (Commercial)
    'cnpja_base_url' => 'https://api.cnpja.com',

    // Endpoint para CNPJ (sem pontuação). {doc} = 14 dígitos.
    'cnpja_cnpj_path' => '/office/{doc}',

    // Parâmetros opcionais (caching strategies) - veja docs do CNPJá.
    // Ex.: array('strategy' => 'CACHE_IF_FRESH', 'maxAge' => 45)
    'cnpja_query_params' => array(),

    // Cache local (MySQL) - dias para reutilizar o último resultado antes de chamar a API novamente.
    'mysql_cache_days' => 7,

    // -----------------------------------------------------------------
    // CPF - Provedor (API CPF)
    // -----------------------------------------------------------------
    // Provedor de CPF ativo (atualmente suportado: 'apicpf')
    'cpf_provider' => 'apicpf',

    // API CPF (apicpf.com)
    'apicpf_base_url' => 'https://apicpf.com',
    'apicpf_path' => '/api/consulta',
    'apicpf_api_key' => '049e8e09a0c266b81c20579d098fc086cc10877fd6bf8824efce8970d12f422a',
    'apicpf_timeout' => 30,

    // Parâmetros opcionais de query (além de cpf=...)
    // Ex.: array('api_key' => '...') caso você prefira não enviar header.
    'apicpf_query_params' => array(),
);
