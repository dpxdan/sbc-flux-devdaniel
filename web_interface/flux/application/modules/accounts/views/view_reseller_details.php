<? extend('master.php') ?>
<? startblock('extra_head') ?>
<script type="text/javascript" language="javascript">
  $(document).ready(function() {
  $("#resend_password").click(function(){
    var link = "<?php echo base_url()?>"+this.getAttribute('link');
    
     $.ajax({
            type: "POST",
            url: link,
            beforeSend: function(){
          $("#resend_password").attr("disabled", true);
        },
            success: function(result) {
         var validate_ERR = 'Password reset successfully.';
         var ERR_type     = 'error';
         display_flux_message(validate_ERR,ERR_type);
         setTimeout(function(){
          window.location.reload(1);
         }, 2000);
            },
            error: function(result) {
                  alert('Sorry,Password not reset');
            }
        });
  });
  $('.is_distributor').prop("disabled", true);
   $(document).on('click', '.consult_tax_number', function(){
            var doc = $("input[name='tax_number']").val() || $('#tax_number').val();
            if(!doc){
                if(typeof print_error === 'function'){
                    alert('Informe um CPF/CNPJ no campo Tax Number.');
                }else{
                    alert('Informe um CPF/CNPJ no campo Tax Number.');
                }
                return;
            }
            $.ajax({
                type:'POST',
                url: "<?=base_url()?>accounts/documents/ajax_consultar/",
                data: {doc: doc},
                success: function(resp){
                    try{ if(typeof resp === 'string') resp = JSON.parse(resp); }catch(e){}
                    if(resp && resp.ok){
                        var m = resp.mapped || {};
                        var fullName = m.company_name || '';
                        
                        if (fullName) {
                            var parts = fullName.trim().split(/\s+/);
                            var first_name = parts.shift() || '';
                            var last_name = parts.join(' ');
                        
                            $("input[name='first_name']").val(first_name);
                            $("input[name='last_name']").val(last_name);
                        }
                        if(m.company_name) $("input[name='company_name']").val(m.company_name);
                        if(m.rms_fantasia) $("input[name='rms_fantasia']").val(m.rms_fantasia);
                        if(m.simei){
                            const simei_status = m.simei;
                            if(simei_status == true){
                                var simei = "<?php echo gettext('Active'); ?>";
                            }
                            else{
                                var simei = "<?php echo gettext('Inactive'); ?>";
                            }
                            }
                        if(simei) $("input[name='simei']").val(simei);
                        if(m.registrations) $("input[name='rms_inscricao_estadual']").val(m.registrations);
                        if(m.rms_bairro) $("input[name='rms_bairro']").val(m.rms_bairro);
                        if(m.rms_endereco_numero) $("input[name='rms_endereco_numero']").val(m.rms_endereco_numero);
                        if(m.address_1) $("input[name='address_1']").val(m.address_1);
                        if(m.address_2) $("input[name='address_2']").val(m.address_2);
                        if(m.city) $("input[name='city']").val(m.city);
                        if(m.province) $("input[name='province']").val(m.province);
                        if(m.postal_code) $("input[name='postal_code']").val(m.postal_code);
                        if(m.telephone_1) $("input[name='telephone_1']").val(m.telephone_1);
                        if(m.email){
                            $("input[name='email']").val(m.email);
                            $("input[name='notification_email']").val(m.email);
                        }
                        if(resp.doc) $("input[name='tax_number']").val(resp.doc);
                    }
                    else{
                        var err = (resp && resp.error) ? resp.error : 'Falha ao consultar.';
                        if(typeof print_error === 'function'){
                            alert(err);
                        }else{
                            alert(err);
                        }
                    }
                },
                error: function(xhr){
                    var msg = xhr.responseJSON.message;
                    if(typeof print_error === 'function'){
                        window.location.reload();
                        //alert(msg);
                    }else{
                        window.location.reload();
                        //alert(msg);
                    }
                }
            });
        });
  $(".sweep_id").change(function(){
    var sweep_id =$('.sweep_id option:selected').val();
    if(sweep_id != 0){
      $.ajax({
        type:'POST',
        url: "<?= base_url() ?>/accounts/customer_invoice_option/<?= $invoice_date ?>",
        data:"sweepid="+sweep_id, 
        success: function(response) {
          $('.invoice_day').parents('li.form-group').removeClass("d-none");               
          $('.invoice_day').selectpicker('show');
          $('#invoice_day').html(response);
          $('.selectpicker').selectpicker('refresh');
        }
      });
    }else{
      $('.invoice_day').parents('li.form-group').addClass("d-none");                  
    }
  });
  $(".change_pass").click(function(){
    $.ajax({type:'POST',
      url: "<?= base_url() ?>accounts/customer_generate_password/",
      success: function(response) {
        $('#password').val(response.trim());
      }
    });
  })
  $(".change_number").click(function(){
    $.ajax({type:'POST',
      url: "<?= base_url() ?>accounts/customer_generate_number/"+10,
      success: function(response) {
        var data=response.replace('-',' ');
        $('#number').val(data.trim());
      }
    });
  });
  $(".sweep_id").change();
});
         

</script>
<script type="text/javascript" language="javascript">
  $(document).ready(function() {
   
    $("input[type='hidden']").parents('li.form-group').addClass("d-none");
    
    
  });
</script>
<script type="text/javascript">
  $(document).ready(function(){
    $('.page-wrap').addClass('addon_wrap');
  });
</script>
<script type="text/javascript">
  $(document).ready(function(){
    $(".breadcrumb li a").removeAttr("data-ripple","");
  $(".reset_password").parents("li").removeClass('form-group').addClass('mt-4'); 
  });
</script>
<style>
label.error {
  float: left;
  color: red;
  padding-left: .3em;
  vertical-align: top;
  padding-left: 40px;
  margin-top: 20px;
  width: 1500% !important;
}
</style>
<? endblock() ?>

<? startblock('page-title') ?>
<?= $page_title ?>
<? endblock() ?>

<? startblock('content') ?>

<div class="p-0">
  <section class="slice color-three">
    <div class="w-section inverse p-0">
    <?php echo $form; ?>
    <?php
    if (isset($validation_errors) && $validation_errors != '') {
        ?>
      <script>
        var ERR_STR = '<?php echo $validation_errors; ?>';
        print_error(ERR_STR);
      </script>
    <? } ?>


  </div>
  </section>
</div>
<? endblock() ?>  
<? startblock('sidebar') ?>
Filter by
<? endblock() ?>
<? end_extend() ?>
<script type="text/javascript" language="javascript">

$(document).ready(function() {
  $("textarea").parents('li.form-group').addClass("h-auto");  
});

</script>
