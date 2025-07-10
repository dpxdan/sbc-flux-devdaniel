<?php include(FCPATH.'application/views/popup_header.php'); ?>
<link rel="stylesheet"
	href="<?php echo base_url(); ?>assets/css/flexigrid.css"
	type="text/css">
<link href="<?php echo base_url(); ?>assets/css/facebox.css"
	rel="stylesheet" media="all" />
<script type="text/javascript">
    $("#submit").click(function(){
        submit_form("api_test_form");
    });
</script>
<script type="text/javascript">
  $(document).ready(function(){
      $(".breadcrumb li a").removeAttr("data-ripple","");
      
  });
</script>

<section class="slice m-0">
	<div class="w-section inverse p-0">
		<div>
			<div>
				<div class="col-md-12 p-0 card-header">
					<h3 class="fw4 p-4 m-0"><? echo $page_title; ?></h3 class="text-light p-3 rounded-top">
				</div>
			</div>
		</div>
	</div>
</section>

<div>
	<div>
		<section class="slice m-0">
			<div class="w-section inverse p-4">
				<div style="">
                <?php

if (isset($validation_errors)) {
                    echo $validation_errors;
                }
                ?> 
            </div>
            <form method="post" id="api_test_form" action="<?= base_url('api_endpoints/api_test_send') ?>">
    <div class="form-group">
      <label for="endpoint_url">URL do Endpoint</label>
      <input type="text" class="form-control" name="endpoint_url" id="endpoint_url" value="<?= isset($endpoint_info['endpoint_url']) ? $endpoint_info['endpoint_url'] : '' ?>" readonly/>
      <input type="hidden" name="url" id="final_url" />
    </div>
    
                      <div class='form-group'>
                      <label for="destination_endpoints"><?php echo gettext('Destination Endpoint'); ?></label>
                      <select name="destination_endpoints" id="api_destination_endpoints" class="form-control">
                        <?php foreach($destination_endpoints as $key1 => $destination_endpoint) {  ?>
				<option value= "<?php echo $key1; ?>"> <?php echo  $destination_endpoint ?> </option>
			<?php } ?>
                      </select>
		    </div>

    <div class="form-group">
      <label for="method">Método HTTP</label>
      <select name="method" id="method" class="form-control">
        <option value="GET">GET</option>
        <option value="POST" selected>POST</option>
        <option value="PUT">PUT</option>
        <option value="DELETE">DELETE</option>
      </select>
    </div>

    <hr>
    <h5>Autenticação</h5>

    <div class="form-group">
      <label for="endpoint_auth">Tipo de Autenticação</label>
      <select name="endpoint_auth" id="endpoint_auth" class="form-control">
        <option value="">Nenhuma</option>
        <option value="basic">Basic</option>
        <option value="bearer">Bearer Token</option>
      </select>
    </div>

    <div class="form-group">
      <label for="endpoint_user">Usuário</label>
      <input type="text" class="form-control" name="endpoint_user" id="endpoint_user" value="<?php echo (isset($endpoint_info['endpoint_user']))?$endpoint_info['endpoint_user']:'' ?>">
    </div>

    <div class="form-group">
      <label for="endpoint_password">Senha</label>
      <input type="password" class="form-control" name="endpoint_password" id="endpoint_password" value="<?php echo (isset($endpoint_info['endpoint_password']))?$endpoint_info['endpoint_password']:'' ?>">
    </div>

    <div class="form-group">
      <label for="endpoint_token">Token</label>
      <input type="text" class="form-control" name="endpoint_token" id="endpoint_token" value="<?php echo (isset($endpoint_info['endpoint_token']))?$endpoint_info['endpoint_token']:'' ?>">
    </div>

    <hr>
    <h5>Headers Personalizados</h5>

    <div id="headers-container"></div>

    <button type="button" class="btn btn-outline-primary mb-3" id="add-header">+ Adicionar Header</button>
    <hr>
    <div class="form-group">
      <label for="body">Body (JSON)</label>
      <textarea class="form-control" name="body" id="body" rows="6" placeholder='{"key": "value"}' style="font-size: inherit;"></textarea>
    </div>

    <button type="submit" class="btn btn-success">Enviar Requisição</button>
    <br/><br/>
  </form>
        </div>
		</section>
	</div>
</div>

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

<script type="text/javascript" language="javascript">
    document.getElementById('add-header').addEventListener('click', function () {
      const container = document.getElementById('headers-container');
      const div = document.createElement('div');
      div.className = 'header-pair';
      div.innerHTML = `
        <input type="text" name="headers[key][]" placeholder="Header" class="form-control" />
        <input type="text" name="headers[value][]" placeholder="Valor" class="form-control" />
        <button type="button" onclick="this.parentNode.remove()" class="btn btn-danger btn-sm">Remover</button>
      `;
      container.appendChild(div);
    });

    document.getElementById('api_test_form').addEventListener('submit', function (e) {
      const authType = document.getElementById('endpoint_auth').value.trim().toLowerCase();
      const user = document.getElementById('endpoint_user').value;
      const pass = document.getElementById('endpoint_password').value;
      const token = document.getElementById('endpoint_token').value;
      const container = document.getElementById('headers-container');

      const existingHeaders = container.querySelectorAll('.header-pair');
      existingHeaders.forEach(pair => {
        const keyInput = pair.querySelector('input[name="headers[key][]"]');
        if (keyInput && keyInput.value.toLowerCase() === 'authorization') {
          pair.remove();
        }
      });

      let authHeader = null;
      if (authType === 'basic' && user && pass) {
        authHeader = 'Basic ' + btoa(user + ':' + pass);
      } else if (authType === 'bearer' && token) {
        authHeader = 'Bearer ' + token;
      }

      if (authHeader) {
        const div = document.createElement('div');
        div.className = 'header-pair';
        div.innerHTML = `
          <input type="text" name="headers[key][]" class="form-control" value="Authorization" readonly />
          <input type="text" name="headers[value][]" class="form-control" value="${authHeader}" readonly />
          <button type="button" onclick="this.parentNode.remove()" class="btn btn-danger btn-sm">Remover</button>
        `;
        container.appendChild(div);
      }
    });
  </script>
  <script type="text/javascript">
  $(document).ready(function () {
    function updateFinalURL() {
      const baseUrl = $('#endpoint_url').val();
      const destination = $('#api_destination_endpoints').val();
      const finalUrl = baseUrl.replace(/\/$/, '') + '/' + destination;
      $('#final_url').val(finalUrl);
    }

    $('#api_destination_endpoints').on('change', updateFinalURL);

    $('#api_test_form').on('submit', function () {
      updateFinalURL();
    });

    updateFinalURL();
  });
</script>

