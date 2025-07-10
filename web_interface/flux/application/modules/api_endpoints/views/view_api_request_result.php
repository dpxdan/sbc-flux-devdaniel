<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Resultado da Requisição</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
  <style>
    pre {
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 6px;
      overflow-x: auto;
    }
  </style>
</head>
<body class="container py-4">
<div class="container">
    <h2><?= gettext('Resultado da Requisição de API') ?></h2>

    <!-- Resumo da Resposta -->
    <div class="card mb-4">
        <div class="card-header"><strong><?= gettext('Resumo da Resposta') ?></strong></div>
        <div class="card-body">
            <p><strong>Status HTTP:</strong> <?= $http_code ?? 'N/A' ?></p>
            <?php if (isset($total_time)) : ?>
                <p><strong>Tempo de resposta:</strong> <?= round($total_time, 3) ?> segundos</p>
            <?php endif; ?>
            <?php if (!empty($curl_error)) : ?>
                <p style="color: red;"><strong>Erro cURL:</strong> <?= $curl_error ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Headers da Resposta -->
    <?php if (!empty($response_headers)) : ?>
        <div class="card mb-4">
            <div class="card-header"><strong>Headers da Resposta</strong></div>
            <div class="card-body">
                <pre><?= htmlspecialchars($response_headers) ?></pre>
            </div>
        </div>
    <?php endif; ?>

    <!-- Body da Resposta -->
    <div class="card mb-4">
        <div class="card-header"><strong>Body da Resposta</strong></div>
        <div class="card-body">
            <pre><?php
      // tenta formatar JSON, senão imprime cru
      $json = json_decode($response_body, true);
      if ($json) {
          echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
      } else {
          echo htmlspecialchars($response_body);
      }
    ?></pre>
        </div>
    </div>

    <!-- Requisição Enviada (opcional) -->
    <?php if (!empty($request_summary)) : ?>
        <div class="card mb-4">
            <div class="card-header"><strong>Requisição Enviada</strong></div>
            <div class="card-body">
                <pre><?= htmlspecialchars($request_summary) ?></pre>
            </div>
        </div>
    <?php endif; ?>

    <a href="<?php echo base_url(); ?>api_endpoints/api_endpoints_list/" class="btn btn-secondary"><?= gettext('Back') ?></a>
</div>
</body>
</html>
