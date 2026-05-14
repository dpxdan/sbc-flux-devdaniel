<? extend('master.php') ?>
<? startblock('extra_head') ?>
<script type="text/javascript" language="javascript">
    $(document).ready(function() {

        build_grid("event_guard_whitelist_grid", "", <? echo $grid_fields; ?>, <? echo $grid_buttons; ?>);

        $('.checkall').click(function () {
            $('.chkRefNos').prop('checked', $(this).prop('checked'));
        });

        $("#event_guard_whitelist_search_btn").click(function() {
            post_request_for_search("event_guard_whitelist_grid", "", "event_guard_whitelist_search");
        });

        $("#id_reset").click(function() {
            clear_search_request("event_guard_whitelist_grid", "");
        });

    });
</script>
<? endblock() ?>

<? startblock('page-title') ?>
<?= $page_title ?>
<? endblock() ?>

<? startblock('content') ?>
<?php $permissioninfo = $this->session->userdata('permissioninfo'); ?>

<section class="slice color-three pb-4">
    <div class="w-section inverse p-0">
        <div class="card col-md-12 pb-4">
            <form method="POST" action="del/0/" enctype="multipart/form-data" id="ListForm">
                <table id="event_guard_whitelist_grid" align="left" style="display: none;"></table>
            </form>
        </div>
    </div>
</section>

<? endblock() ?>
<? end_extend() ?>
