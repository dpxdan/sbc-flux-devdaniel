<? extend('master.php') ?>
<? startblock('extra_head') ?>

<script type="text/javascript">
    $(document).ready(function() {
        // Clique no ícone de lupa dentro do campo
        $(document).on('click', '.documentos_consultar', function(){
            $('#documentos_consulta_form').submit();
        });

        // Se o usuário apertar Enter no campo doc, submete
        $(document).on('keypress', '#doc', function(e){
            if(e.which === 13){
                $('#documentos_consulta_form').submit();
                return false;
            }
        });
    });
</script>
<?php endblock()?>

<?php startblock('page-title')?>
<?=$page_title?>
<?php endblock()?>

<?php startblock('content')?>
<div class="p-0">
    <section class="slice color-three">
        <div class="w-section inverse p-0">

            <?php echo $form; ?>

            <?php if (!empty($message)) { ?>
                <div class="alert alert-success mt-3" role="alert"><?php echo $message; ?></div>
            <?php } ?>

            <?php if (!empty($error)) { ?>
                <div class="alert alert-danger mt-3" role="alert"><?php echo $error; ?></div>
            <?php } ?>

            <?php if (!empty($result)) { ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <strong>Última consulta:</strong>
                        <?php echo htmlspecialchars($result['doc_type'].' '.$result['doc_number']); ?>
                        <span class="text-muted">| <?php echo htmlspecialchars($result['created_at']); ?></span>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Status:</strong>
                            <?php echo ($result['success'] ? '<span class="text-success">OK</span>' : '<span class="text-danger">ERRO</span>'); ?>
                            <span class="text-muted">(HTTP <?php echo (int) $result['http_code']; ?>, provider: <?php echo htmlspecialchars($result['provider']); ?>)</span>
                        </p>

                        <?php if (!$result['success'] && !empty($result['error_message'])) { ?>
                            <div class="alert alert-warning" role="alert">
                                <?php echo htmlspecialchars($result['error_message']); ?>
                            </div>
                        <?php } ?>

                        <?php if (!empty($result['response'])) { ?>
                            <pre style="white-space: pre-wrap; word-break: break-word; max-height: 450px; overflow:auto;" class="bg-light p-3 border rounded"><?php echo htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); ?></pre>
                        <?php } else if (!empty($result['response_json'])) { ?>
                            <pre style="white-space: pre-wrap; word-break: break-word; max-height: 450px; overflow:auto;" class="bg-light p-3 border rounded"><?php echo htmlspecialchars($result['response_json']); ?></pre>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

        </div>
    </section>
</div>
<?endblock()?>

<?startblock('sidebar')?>
<?endblock()?>

<?end_extend()?>
