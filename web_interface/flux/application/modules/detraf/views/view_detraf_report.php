<? extend('master.php') ?>
<? startblock('extra_head') ?>
<script type="text/javascript" language="javascript">
    $(document).ready(function() {
        build_grid("detraf_grid", "<?= base_url() ?>/detraf/detraf_list_json/", <?= $grid_fields ?>, <?= $grid_buttons ?>);

        $("#detraf_search_btn").click(function() {
            post_request_for_search("detraf_grid", "", "detraf_search");
        });

        $("#id_reset").click(function() {
            clear_search_request("detraf_grid", "");
        });

        $("#detraf_from_date").datetimepicker({
            uiLibrary: 'bootstrap4',
            iconsLibrary: 'fontawesome',
            modal: true,
            format: 'yyyy-mm-dd HH:MM:ss',
            footer: true
        });

        $("#detraf_to_date").datetimepicker({
            uiLibrary: 'bootstrap4',
            iconsLibrary: 'fontawesome',
            modal: true,
            format: 'yyyy-mm-dd HH:MM:ss',
            footer: true
        });

        $('#detraf_email_form').submit(function(e) {
            e.preventDefault();
            var email_to = $('#email_to').val().trim();
            if (!email_to) {
                $('#email_feedback').html('<div class="alert alert-danger"><?= gettext("Informe o e-mail de destino.") ?></div>');
                return;
            }
            $('#btn_send_email').prop('disabled', true).text('<?= gettext("Enviando...") ?>');
            $.ajax({
                type: 'POST',
                url: '<?= base_url() ?>/detraf/send_email/',
                data: { email_to: email_to },
                dataType: 'json',
                success: function(resp) {
                    if (resp.status === 'success') {
                        $('#email_feedback').html('<div class="alert alert-success">' + resp.message + '</div>');
                        setTimeout(function() { $('#detraf_email_modal').modal('hide'); }, 2000);
                    } else {
                        $('#email_feedback').html('<div class="alert alert-danger">' + resp.message + '</div>');
                    }
                },
                error: function() {
                    $('#email_feedback').html('<div class="alert alert-danger"><?= gettext("Erro ao conectar com o servidor.") ?></div>');
                },
                complete: function() {
                    $('#btn_send_email').prop('disabled', false).text('<?= gettext("Enviar") ?>');
                }
            });
        });

        $('#detraf_email_modal').on('hidden.bs.modal', function() {
            $('#email_feedback').html('');
            $('#email_to').val('');
        });
    });
</script>
<? endblock() ?>

<? startblock('page-title') ?>
<?= $page_title ?>
<? endblock() ?>

<? startblock('content') ?>

<section class="slice color-three">
    <div class="w-section inverse p-0">
        <div class="col-12">
            <div class="portlet-content mb-4" id="search_bar" style="display: none">
                <?php echo $form_search; ?>
            </div>
        </div>
    </div>
</section>

<section class="slice color-three pb-4">
    <div class="w-section inverse p-0">
        <div class="card col-md-12 pb-4">
            <table id="detraf_grid" align="left" style="display: none;"></table>
        </div>
        <div class="col-md-12 pb-3 text-right">
            <button type="button"
                    class="btn btn-primary"
                    data-toggle="modal"
                    data-target="#detraf_email_modal">
                <i class="fa fa-envelope-o mr-1"></i> <?= gettext('Enviar por E-mail') ?>
            </button>
        </div>
    </div>
</section>

<div class="modal fade" id="detraf_email_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fa fa-envelope-o mr-2"></i><?= gettext('Enviar Relatório por E-mail') ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="detraf_email_form">
                <div class="modal-body">
                    <div id="email_feedback"></div>
                    <div class="form-group">
                        <label for="email_to"><?= gettext('Destinatário') ?></label>
                        <input type="email" name="email_to" id="email_to"
                               class="form-control"
                               placeholder="destinatario@exemplo.com.br"
                               required />
                        <small class="form-text text-muted">
                            <?= gettext('O relatório será enviado como anexo CSV.') ?>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <?= gettext('Cancelar') ?>
                    </button>
                    <button type="submit" id="btn_send_email" class="btn btn-primary">
                        <i class="fa fa-paper-plane-o mr-1"></i><?= gettext('Enviar') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<? endblock() ?>
<? end_extend() ?>
