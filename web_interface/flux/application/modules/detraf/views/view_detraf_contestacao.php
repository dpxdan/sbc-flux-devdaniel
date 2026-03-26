<? extend('master.php') ?>
<? startblock('extra_head') ?>
<script>
$(document).ready(function() {

    $('#btn_upload_csv').click(function() {
        var file = $('#input_csv').val();
        if (!file) {
            alert('<?= gettext("Selecione um arquivo CSV.") ?>');
            return;
        }
        var fd = new FormData($('#form_upload_csv')[0]);
        $('#upload_feedback').html('<div class="alert alert-info"><?= gettext("Importando...") ?></div>');
        $('#btn_upload_csv').prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: '<?= base_url() ?>/detraf/contestacao_upload/',
            data: fd,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(r) {
                if (r.status === 'success') {
                    $('#upload_feedback').html('<div class="alert alert-success">' + r.message + '</div>');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    $('#upload_feedback').html('<div class="alert alert-danger">' + r.message + '</div>');
                }
            },
            error: function() {
                $('#upload_feedback').html('<div class="alert alert-danger"><?= gettext("Erro no upload.") ?></div>');
            },
            complete: function() { $('#btn_upload_csv').prop('disabled', false); }
        });
    });

    $(document).on('click', '.btn-view-batch', function() {
        var batch_id = $(this).data('batch');
        var grid_id  = 'contestacao_grid_' + batch_id;

        $('#batch_panel_' + batch_id).show();

        if (!$.data(document, 'grid_built_' + batch_id)) {
            var grid_fields  = <?= $grid_fields_json ?? '[]' ?>;
            var grid_buttons = [];
            build_grid(grid_id, '<?= base_url() ?>/detraf/contestacao_json/' + batch_id + '/', grid_fields, grid_buttons);
            $.data(document, 'grid_built_' + batch_id, true);
        }
    });

    $(document).on('click', '.btn-del-batch', function() {
        if (!confirm('<?= gettext("Excluir este lote importado?") ?>')) return;
        var batch_id = $(this).data('batch');
        $.post('<?= base_url() ?>/detraf/contestacao_delete/' + batch_id + '/', function() {
            location.reload();
        });
    });

    $(document).on('click', '.btn-export-div', function() {
        var batch_id = $(this).data('batch');
        window.location = '<?= base_url() ?>/detraf/contestacao_export_csv/' + batch_id + '/1/';
    });
    $(document).on('click', '.btn-export-full', function() {
        var batch_id = $(this).data('batch');
        window.location = '<?= base_url() ?>/detraf/contestacao_export_csv/' + batch_id + '/0/';
    });
});
</script>
<? endblock() ?>

<? startblock('page-title') ?>
<?= gettext('DETRAF - Contestação') ?>
<? endblock() ?>

<? startblock('content') ?>

<section class="slice color-three pb-2">
    <div class="w-section inverse p-0">
        <div class="col-12 pt-3">

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <strong><i class="fa fa-upload mr-2"></i><?= gettext('Importar DETRAF Recebido') ?></strong>
                </div>
                <div class="card-body">
                    <form id="form_upload_csv" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-5">
                                <input type="file" name="detraf_csv" id="input_csv"
                                       class="form-control" accept=".csv,.txt">
                                <small class="text-muted">
                                    <?= gettext('CSV no padrão DETRAF ANATEL. Separador ; ou ,') ?>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <button type="button" id="btn_upload_csv" class="btn btn-primary">
                                    <i class="fa fa-cloud-upload mr-1"></i><?= gettext('Importar e Comparar') ?>
                                </button>
                            </div>
                            <div class="col-md-4">
                                <div id="upload_feedback"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($batch_list)): ?>
                <div class="alert alert-info"><?= gettext('Nenhum DETRAF importado ainda.') ?></div>
            <?php else: ?>
                <?php foreach ($batch_list as $batch): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fa fa-file-text-o mr-2"></i>
                            <strong><?= htmlspecialchars($batch['filename']) ?></strong>
                            &nbsp;&mdash;&nbsp;
                            <?= gettext('Ref.:') ?> <strong><?= htmlspecialchars($batch['referencia']) ?></strong>
                            &nbsp;|&nbsp;
                            <?= gettext('EOT Credora:') ?> <?= htmlspecialchars($batch['eot_credora']) ?>
                            &nbsp;|&nbsp;
                            <?= gettext('EOT Devedora:') ?> <?= htmlspecialchars($batch['eot_devedora']) ?>
                            &nbsp;|&nbsp;
                            <?= $batch['total_rows'] ?> <?= gettext('linhas') ?>
                            &nbsp;|&nbsp;
                            <small class="text-muted"><?= $batch['created_at'] ?></small>
                        </span>
                        <span class="btn-group">
                            <button class="btn btn-sm btn-info btn-view-batch"
                                    data-batch="<?= $batch['batch_id'] ?>">
                                <i class="fa fa-search"></i> <?= gettext('Ver Comparativo') ?>
                            </button>
                            <button class="btn btn-sm btn-success btn-export-div"
                                    data-batch="<?= $batch['batch_id'] ?>">
                                <i class="fa fa-exclamation-triangle"></i> <?= gettext('CSV Divergências') ?>
                            </button>
                            <button class="btn btn-sm btn-secondary btn-export-full"
                                    data-batch="<?= $batch['batch_id'] ?>">
                                <i class="fa fa-download"></i> <?= gettext('CSV Completo') ?>
                            </button>
                            <button class="btn btn-sm btn-danger btn-del-batch"
                                    data-batch="<?= $batch['batch_id'] ?>">
                                <i class="fa fa-trash"></i>
                            </button>
                        </span>
                    </div>
                    <div id="batch_panel_<?= $batch['batch_id'] ?>" style="display:none;" class="card-body p-2">
                        <div class="mb-2">
                            <span class="badge badge-success">OK</span> <?= gettext('Dados conferem') ?>&nbsp;&nbsp;
                            <span class="badge badge-danger">CONTESTAR</span> <?= gettext('Diferença encontrada') ?>&nbsp;&nbsp;
                            <span class="badge badge-warning">INDEVIDO</span> <?= gettext('Linha sem correspondência nos nossos CDRs') ?>
                        </div>
                        <table id="contestacao_grid_<?= $batch['batch_id'] ?>"
                               align="left" style="display:none;"></table>
                        <script>
                        (function() {
                            var batch_id = '<?= $batch['batch_id'] ?>';
                            var grid_fields = <?= $this->detraf_form->build_detraf_contestacao() ?>;
                            $(document).on('click', '.btn-view-batch[data-batch="' + batch_id + '"]', function() {
                                if (!$.data(document, 'grid_built_' + batch_id)) {
                                    build_grid(
                                        'contestacao_grid_' + batch_id,
                                        '<?= base_url() ?>/detraf/contestacao_json/' + batch_id + '/',
                                        grid_fields,
                                        []
                                    );
                                    $.data(document, 'grid_built_' + batch_id, true);
                                }
                            });
                        })();
                        </script>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</section>

<? endblock() ?>
<? end_extend() ?>