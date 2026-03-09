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
    'cnpja_token' => '39b63007-06bf-4f75-8f4f-52127b9da8cc-f21e216b-7eeb-494d-bc34-1af3e474f13b',

    // Base URL do provedor CNPJá (Commercial)
    'cnpja_base_url' => 'https://open.cnpja.com',

    // Endpoint para CNPJ (sem pontuação). {doc} = 14 dígitos.
    'cnpja_cnpj_path' => '/office/{doc}?registrations=BR',

    // Parâmetros opcionais (caching strategies) - veja docs do CNPJá.
    // Ex.: array('strategy' => 'CACHE_IF_FRESH', 'maxAge' => 45)
    'cnpja_query_params' => array('registrations' => 'BR'),

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
