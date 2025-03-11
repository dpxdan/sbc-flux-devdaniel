
<? extend('master.php') ?>
<?php error_reporting(E_ERROR); ?>

<? startblock('extra_head') ?>
<?php endblock() ?>

<?php startblock('page-title') ?>
<?= $page_title ?>
<?php endblock() ?>

<?php startblock('content') ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>Flux WebRTC</title>
    <link rel="stylesheet" href="/assets/fonts/fontawesome6/css/all.css"/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Exo" rel="stylesheet">   
    <link rel="stylesheet" href="/assets/css/flux_phone/mdb.min.css" /> 
    <link rel="stylesheet" href="/assets/css/flux_phone/style_v1.css" />
    </head>
    <body id="sipClient">
<div id="app"></div>
<video id="remoteVideo" hidden="hidden"></video>
<video id="localVideo" hidden="hidden" muted="muted"></video>
<div class="container">
<!-- Tabs navs -->
<ul class="nav nav-pills nav-justified mb-3" id="ex-with-icons" role="tablist">
  <li class="nav-item" role="presentation">
    <a class="nav-link active" id="ex-with-icons-tab-1" data-mdb-toggle="pill" href="#ex-with-icons-tabs-1" role="tab"
      aria-controls="ex-with-icons-tabs-1" aria-selected="true"><i class="fa fa-phone fa-fw me-2"></i>Ramal</a>
  </li>
  <li class="nav-item" role="presentation">
    <a class="nav-link" id="ex-with-icons-tab-2" data-mdb-toggle="pill" href="#ex-with-icons-tabs-2" role="tab"
      aria-controls="ex-with-icons-tabs-2" aria-selected="false"><i class="fa fa-history fa-fw me-2"></i>Histórico</a>
  </li>
  <li class="nav-item" role="presentation">
    <a class="nav-link" id="ex-with-icons-tab-3" data-mdb-toggle="pill" href="#ex-with-icons-tabs-3" role="tab"
      aria-controls="ex-with-icons-tabs-3" aria-selected="false"><i class="fa fa-cog fa-fw me-2"></i>Configurações</a>
  </li>
</ul>
<!-- Tabs content -->
<div class="tab-content" id="ex-with-icons-content">
     <div class="tab-pane fade show active" id="ex-with-icons-tabs-1" role="tabpanel" aria-labelledby="ex-with-icons-tab-1">
       <div class="clearfix sipStatus">
                   <div id="txtRegStatus" class="pull-left"></div>
                   <div id="txtCallStatus" class="pull-right">&nbsp;</div>
                 </div>
       <div class="form-group" id="phoneUI">
                      <div class="input-group">
                      <input type="text" name="number" id="numDisplay" class="form-control text-center input-sm" value="" placeholder="Insira o número..." autocomplete="off" />
                      </div>
                      <div class="container input-group" style="top: 10px;">
                     <div class="container sip-dialpad" id="sip-dialpad">
                     <button type="button" class="btn btn-default digit" data-digit="1">1<span>&nbsp;</span></button>
                     <button type="button" class="btn btn-default digit" data-digit="2">2<span>ABC</span></button>
                     <button type="button" class="btn btn-default digit" data-digit="3">3<span>DEF</span></button>
                     <button type="button" class="btn btn-default digit" data-digit="4">4<span>GHI</span></button>
                     <button type="button" class="btn btn-default digit" data-digit="5">5<span>JKL</span></button>
                     <button type="button" class="btn btn-default digit" data-digit="6">6<span>MNO</span></button>
                     <button type="button" class="btn btn-default digit" data-digit="7">7<span>PQRS</span></button>
                     <button type="button" class="btn btn-default digit" data-digit="8">8<span>TUV</span></button>
                     <button type="button" class="btn btn-default digit" data-digit="9">9<span>WXYZ</span></button>
                     <button type="button" class="btn btn-default digit" data-digit="*">*<span>&nbsp;</span></button>
                     <button type="button" class="btn btn-default digit" data-digit="0">0<span>+</span></button>
                     <button type="button" class="btn btn-default digit" data-digit="#">#<span>&nbsp;</span></button>
                     <div class="clearfix">&nbsp;</div>
                     <button class="btn btn-info btn-block btnCall" title="Discar">
                     <i class="fa fa-play"></i> Discar
                     </button>
                   </div>
                      </div>
           </div>
     </div>
     <div class="tab-pane fade" id="ex-with-icons-tabs-2" role="tabpanel" aria-labelledby="ex-with-icons-tab-2">
       <div id="sip-log" class="panel panel-default">
       <div id="logPanel" class="panel-heading">
         <h6 class="text-muted panel-title"><span id="asknotificationpermission" class="asknotificationpermission pull-left"><i class="fa fa-envelope text-muted asknotificationpermission" title="Habilitar Notificações" style="cursor: pointer;"></i></span>&nbsp;Chamadas Recentes<span id="sipLogClear" class="sipLogClear pull-right"><i class="fa fa-trash text-muted sipLogClear" title="Limpar Histórico" style="cursor: pointer;"></i></span></h6>
       </div>
       <div id="sip-logitems" class="list-group" style="text-align: left; font-size: 12px;">
         <p class="text-muted text-center">Nenhuma chamada recente.</p>
       </div>
     </div>
     </div>
     <div class="tab-pane fade" id="ex-with-icons-tabs-3" role="tabpanel" aria-labelledby="ex-with-icons-tab-3">
  <ul class="list-group list-group-light">
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center">
      
       <i class="fa fa-phone-volume fa-fw me-2" title="Volume"></i>
        <div class="ms-3">
          <div class="range">
            <input type="range" class="form-range" id="sldVolume" />
          </div>
        </div>
      </div>
    </li>
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <i class="fa fa-microphone fa-fw me-2" title="Dispositivos"></i>
        <div class="ms-3">
         <div id="listmic"></div>
        </div>
      </div>
    </li>
  </ul>
  
<!-- Tabs content -->
</div>
</div>
</div>
<div class="modal top fade" id="mdlLogin" tabindex="-1" aria-labelledby="mdlLoginModalLabel" aria-hidden="true" data-mdb-backdrop="true" data-mdb-keyboard="true">
                			 <div class="modal-dialog">
                				 <div class="modal-content text-left">
                					 <div class="modal-header h5 text-white bg-primary justify-content-left">
                						 Configurações de SIP
                						 <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
                					 </div>
                					<div class="modal-body">
   								<form>
					  <p class="text-center">Para usar nossa demonstração, você precisará de suas credenciais SIP de sua conta. Todos os campos são necessários</p>
					  <div class="form-outline mb-4">
						<input type="text" name="Display" id="Display" class="form-control" />
						<label class="form-label" for="Display">Nome de Exibição</label>
					  </div>
					  <div class="form-outline mb-4">
						<input type="text" name="User" id="User" class="form-control" />
						<label class="form-label" for="User">Usuário SIP</label>
					  </div>
					  <div class="form-outline mb-4">
						<input type="password" name="Pass" id="Pass" class="form-control" />
						<label class="form-label" for="Pass">Senha SIP</label>
					  </div>
					  <div class="form-outline mb-4">
						<input type="text" name="WSServer" id="WSServer" class="form-control" />
						<label class="form-label" for="WSServer">Servidor WebRTC</label>
					  </div>
					  <div class="form-outline mb-4">
					   <input type="text" name="Realm" id="Realm" class="form-control" />
					   <label class="form-label" for="Realm">Domínio SIP</label>
					 </div>        
					  <button type="button" id="btnConfig" class="btn btn-primary btn-block mb-3">Salvar</button>
					</form>
   			 </div>
                				 </div>
                			 </div>
                		 </div>
<script type="text/html" id="template-addon-phone">
</script>
<script type="text/html" id="template-transfer">
<div class="modal fade sizefull" id="mdlTransfer" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
   <div class="modal-dialog modal-md">
     <div class="modal-content">
     <div class="modal-header">
       <h6 class="modal-title" id="mdlTransfer">Transferência</h6>
       <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close">
        </button>
     </div>
       <div class="modal-body">
         <form class="form-inline transfer-form" id="transfer-form">
           <div class="form-group">
           <input type="text" id="numTransfer" class="form-control" name="transfer"/>
         </div>
       <div class="modal-footer">
       <button class="btn btn-sm btn-secondary" type="submit">Transferir</button>
       
       <button id="transferCancel" class="btn btn-sm btn-danger transferCancel" data-mdb-dismiss="modal" style="display: none;" type="button">Cancelar</button>
       <button id="warm" class="btn btn-sm btn-primary warm" type="button">Discar</button>
       <button id="tpark" class="btn btn-sm btn-info tpark">Estacionar</button>
       <button id="complete" class="btn btn-sm btn-success complete" type="button">Completar</button>
     </div>
         </form>

       </div>
     </div>
   </div>
 </div>
</script>
<script type="text/html" id="template-accepted">

</script>
<script type="text/html" id="template-error">
  <div class="modal fade" id="mdlError" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="exampleModalLabel">Transferir</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
        <div class="modal-body">
          <form class="form-inline transfer-form">
            <div class="form-group">
            <input type="text" class="form-control" name="transfer"/>
          </div>
          <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm btn-success complete">Transferir</button>
      </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</script>
<script type="text/html" id="template-incoming">
   <div class="modal fade" tabindex="-1" id="mdlIncoming" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="mdlIncoming">Chamada de Entrada</h6>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <form class="form-inline forward-form">
            <div class="wrap-input100 validate-input m-b-16 form-group" data-validate="">
             <label>Encaminhar Chamada:</label>
               <input type="text" name="forward" id="forward" class="input100 form-control text-center input-sm" placeholder="" autocomplete="off" />
              <span class="focus-input100"></span>&nbsp;
            </div>
            <div class="container-login100-form-btn p-t-15">
              <button class="btn btn-primary btn-block mb-3" type="submit">
              Enviar
              </button>
            </div>
                    </form>
                </div>
                <div class="modal-footer before-answer">
                    <button class="btn btn-success answer">Atender</button>
                    <button class="btn btn-danger decline">Rejeitar</button>
                    <!--<button class="btn btn-info toPark">Estacionar</button>-->
                   <!--<button class="btn btn-warning toVoicemail">Caixa Postal</button>-->
                </div>
                <div class="modal-footer answered" style="display: none">Conectando...</div>
            </div>
        </div>
    </div>
</script>

<div id='cmd_reponse' style='display: none;'></div>
<audio id="ringtone" src="/assets/audio/mp3/incoming.mp3"></audio>
<audio id="ringbacktone" src="/assets/audio/mp3/outgoing.mp3"></audio>
<audio id="dtmfTone" src="/assets/audio/mp3/dtmf.mp3"></audio>
<audio id="audioRemote"></audio>
<video id="remoteVideo"></video>
<script src="/assets/js/flux_phone/jquery-3.3.1.slim.min.js"></script>
<script type="text/javascript" src="/assets/js/flux_phone/mdb.min.js"></script>
<script src="/assets/js/flux_phone/popper.min.js"></script>
<script src="/assets/js/flux_phone/perfect-scrollbar.min.js"></script>
<script src="/assets/js/flux_phone/smooth-scrollbar.min.js"></script>
<script src="/assets/js/flux_phone/bootstrap-notify.js"></script>
<script type="text/javascript" src="/assets/js/flux_phone/moment.min.js"></script>
<script type="text/javascript" src="/assets/js/flux_phone/pt-br.js"></script>


<script type="text/javascript" src="/assets/js/flux_phone/fluxPhone.js"></script>
<script type="text/javascript" src="/assets/js/flux_phone/flux.js"></script>
<script type="text/javascript" src="/assets/js/flux_phone/flux-web-phone.js"></script>

<script type="text/javascript">
	var extension_id = '<?php echo $extension_id; ?>';
	var ramal = '<?php echo $extension; ?>';
	var username = '<?php echo $extension; ?>';
	var login = '<?php echo $extension; ?>';
	var pass = '<?php echo $extension_password; ?>';
	var nome = '<?php echo $outbound_caller_id_name; ?>';
	var domain = '<?php echo $domain; ?>';
	var fromNumber = '<?php echo $outbound_caller_id_number; ?>';
	var phoneStatus = '<?php echo $extension_enabled; ?>';
	var accountid = '<?php echo $extension_accountid; ?>';
	var sessid = '<?php echo $extension_id; ?>';
	var server = '<?php echo $domain; ?>';
	var wssport = '<?php echo $wss_port; ?>';
	var wsserver = '<?php echo $domain;?>'+wssport+'';
	var userStatus = 'Online';
	var agent_uuid = 'ab488492-8ebe-4961-9890-13a7eb52f676';
	var user = {
		'User' : ramal,
		'Pass' : pass,
		'Agent' : accountid,
		'Realm' : domain,
		'Domain' : domain,
		'Session' : sessid,
		'Status' : phoneStatus,
		'stunServers' : ['stun:stun.l.google.com:19302'],
		'turnServers' : [{urls:'turn:<?php echo $domain;?>:9579', username : 'fluxDev' , credential: 'FluxDev4SBC201'}],
		'Display' : nome,
		'WSServer' : 'wss://'+wsserver+'',
		'logLevel' : 3
	};
	var sip = {
		'username' : ramal,
		'ext' : ramal,
		'login' : login,
		'password' : pass,
		'transport' : 'WSS',
		'domain'   : domain,
		'fromNumber' : fromNumber,
		'username' : username,
		'outboundProxy'  : wsserver,
		'transport'      : 'WSS',
		'authorizationId' : ramal,
		'wsServers' : wsserver,
		'iceCheckingTimeout': 500,
		'logLevel' : 1,
		'stunServers' : ['stun:stun.l.google.com:19302'],
		'turnServers' : [{urls:'turn:<?php echo $domain;?>:9579', username : 'fluxDev' , credential: 'FluxDev4SBC201'}],
		'displayName' : nome,
		'traceSip': false,
		'hackIpInContact': true,
		'hackWssInTransport': true,
		'hackViaTcp': false
	};
	var sipInfo = [sip];
	var sipdata = {
	'sipInfo' : sipInfo
	};
	var callcenter = [user];
	var cData = {
	'cData' : callcenter
	};
	var regData = {'regData' : user};
	var sipData = {'sipData' : sip};
	var data = [sipdata,regData];
	localStorage.setItem('SIPCreds', JSON.stringify(user));
	localStorage.setItem('User', JSON.stringify(data));
	localStorage.setItem('regData', JSON.stringify(regData));
	localStorage.setItem('sipData', JSON.stringify(sipData));
	localStorage.setItem('cData', JSON.stringify(cData));

	</script>
<script type="text/javascript" src="/assets/js/flux_phone/flux_phone.js"></script>

  </body>
</html>

