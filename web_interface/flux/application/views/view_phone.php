<?php
  include './connection.php';
  
  if (!isset($_SESSION)) {
		session_start();
		$_SESSION['session']['user_session_id'] = session_id();
		$_SESSION['session']['user_referer'] = $_SERVER['HTTP_REFERER'];
	}
  
  if (array_key_exists('security',$_SESSION)) {
			$_SESSION['session']['last_activity'] = time();
			if (!isset($_SESSION['session']['created'])) {
				$_SESSION['session']['created'] = time();
			} else if (time() - $_SESSION['session']['created'] > 1200) {
				// session started more than 2 hours ago
				session_regenerate_id(true);    // rotate the session id
				$_SESSION['session']['user_session_id'] = session_id();
				$_SESSION['session']['created'] = time();  // update creation time
				$_SESSION['session']['user_referer'] = $_SERVER['HTTP_REFERER'];
			}
		}
  $extension = $this->session->userdata('sipnumber');
  $page_title = $this->session->userdata('page_title');
  $domain_name = $_SERVER['SERVER_NAME'];
//  $extension = $_GET['id'];  
  if (isset($extension) && $extension != '') {
  $extension_id = $extension;
  }
  else {
  $extension_id = '0';
  }
  $_SESSION['phone_open'] = true;
  $_SESSION['session-phone'] = true;
  $_SESSION['extension_id'] = $extension_id;
  $session_login = json_encode($_SESSION);
  date_default_timezone_set('America/Sao_Paulo');
 function time_diff_conv($start, $s)
 {
    $string="";
    $t = array( //suffixes
        'd' => 86400,
        'h' => 3600,
        'm' => 60,
    );
    $s = abs($s - $start);
    foreach($t as $key => &$val) {
        $$key = floor($s/$val);
        $s -= ($$key*$val);
        $string .= ($$key==0) ? '' : $$key . "$key ";
    }
    return $string . $s. 's';
}

$date = date('h:i'); 
$sth = $conn->prepare("select * from sip_devices where id = ".$extension_id."");
$sth->execute();

$result = $sth->fetchAll();
foreach ($result as &$row) {
	$vars = json_decode($row['dir_vars']);
	$vars_old = json_decode($row['dir_vars'], true);
	$vars_new = json_decode($row['dir_params'], true);
	$passwords = json_decode($row['dir_params']);
	$extension = $row['username'];
	$extension_password = $vars_new['password'];
	$extension_id = $row['id'];
	$outbound_caller_id_name = $vars_old['effective_caller_id_name'];
	$outbound_caller_id_number = $vars_old['effective_caller_id_number'];
	$extension_enabled = $row['status'];
	$extension_accountid = $row['accountid'];
}
  
?>

<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title><?php echo $page_title ?></title>
    <link rel="icon" href="/upload/favicon.ico" type="image/x-icon" />





    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/fonts/fontawesome6/css/all.css"/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Exo" rel="stylesheet">   
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sip/mdb.min.css" /> 
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sip/style_v1.css" />
    


  </head>
    <body id="sipClient">
    <div id="app"></div>
    <video id="remoteVideo" hidden="hidden"></video>
    <video id="localVideo" hidden="hidden" muted="muted"></video>
    <div class="container main-content">
   <ul class="nav nav-pills nav-justified mb-3" id="nav-items" role="tablist">
     <li class="nav-item tabPhone" id="tabPhone" role="presentation">
           <a
             class="nav-link active"
             id="tab-phone"
             title="Ramal"
             data-mdb-toggle="pill"
             href="#pills-phone"
             role="tab"
             aria-controls="pills-phone"
             aria-selected="true"
             ><span><i class="fas fa-phone fa-lg"></i></span></a
           >
         </li>
     <li class="nav-item me-3 me-lg-1 content_history" id="tabHistory" role="presentation">
       <a
         class="nav-link"
         id="tab-history"
         title="Histórico de Chamadas"
         data-mdb-toggle="pill"
         href="#pills-history"
         role="tab"
         aria-controls="pills-history"
         aria-selected="false"
         ><span><i class="fas fa-history fa-lg"></i></span></a
       >
     </li>
     <!--<li class="nav-item tabContacts content_contacts" id="tabContacts" role="presentation">
       <a
         class="nav-link"
         id="tab-contacts"
         title="Contatos"
         data-mdb-toggle="pill"
         href="#pills-contacts"
         role="tab"
         aria-controls="pills-contacts"
         aria-selected="false"
         ><span><i class="fas fa-address-book fa-lg"></i></span></a
       >
     </li>-->
     <li class="nav-item tabSettings content_settings" id="tabSettings" role="presentation">
       <a
         class="nav-link"
         id="tab-settings"
         title="Configurações"
         data-mdb-toggle="pill"
         href="#pills-settings"
         role="tab"
         aria-controls="pills-settings"
         aria-selected="false"
         ><span><i class="fas fa-user-cog fa-lg"></i></span></a
       >
     </li>
   </ul>
   <!-- Pills content -->
   <div id="pillsContent" class="tab-content">
   <div class="tab-pane fade show active" id="pills-phone" role="tabpanel" aria-labelledby="tab-phone">
   <video id="remoteVideo" hidden="hidden"></video>
   <video id="localVideo" hidden="hidden" muted="muted"></video>
   <div class="clearfix sipStatus">
               <div id="txtCallStatus" class="pull-right">&nbsp;</div>
               <div id="txtRegStatus" class="pull-left"></div>
             </div>
      <div class="form-group" id="phoneUI">
               <div class="input-group">
              <!-- <div name="number" id="output" class="form-control text-center input-sm" style="border: none;">
               </div>-->
               <input type="text" name="number" id="numDisplay" class="form-control text-center input-sm" value="" placeholder="Insira o número..." autocomplete="off" />
               </div>
               <div class="container input-group" style="top: 50px;width: 100%;float: left;">
              <div class="container sip-dialpad" id="sip-dialpad" style="background-color: #fff;border-radius: 20px;">
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
              <!-- <div class="container input-group" style="top: 50px;width: 50%;float: right;">
				 <div id="sip-log-phone" class="panel panel-default hide" style="width: 100%;">
						  <div class="panel-heading">
							<h6 class="text-muted panel-title">Chamadas Recentes <span class="pull-right"><i class="fa fa-trash text-muted sipLogClear" title="Clear Log" style="cursor: pointer;"></i></span></h6>
						  </div>
						  <div id="sip-logitems-phone" class="list-group">
							<p class="text-muted text-center">Nenhuma chamada recente.</p>
						  </div>
						</div>
                    </div>-->
             </div>
                       </div>
   <div class="tab-pane fade content_history" id="pills-history" role="tabpanel" aria-labelledby="tab-history">
        <div class="well-sip">
               <div id="sip-log" class="panel panel-default hide">
                                 <div class="panel-heading">
                                   <h6 class="text-muted panel-title">Chamadas Recentes <span class="pull-right"><i class="fa fa-trash text-muted sipLogClear" title="Clear Log" style="cursor: pointer;"></i></span></h6>
                                 </div>
                                 <div id="sip-logitems" class="list-group">
                                   <p class="text-muted text-center">Nenhuma chamada recente.</p>
                                 </div>
                               </div>
              </div>
        </div>
   <!--<div class="tab-pane fade content_contacts" id="pills-contacts" role="tabpanel" aria-labelledby="tab-contacts">
            <div id="contacts" class="table-responsive table-full-width">
            </div>
          </div>-->
   <div class="tab-pane fade content_settings" id="pills-settings" role="tabpanel" aria-labelledby="tab-settings">
   <div class="text-center mb-3">
                     <p>Configurações:</p>
						<button type="button" data-mdb-toggle="modal" data-mdb-target="#mdlLogin" class="btn btn-primary btn-floating mx-1 btnExtSettings" title="Configurações de Ramal">
						<i class="fa fa-cloud"></i>
					  </button>
                     <button type="button" data-mdb-toggle="modal" data-mdb-target="#audioModal" class="btn btn-primary btn-floating mx-1 btnAudioSettings" title="Configurações de Áudio">
                       <i class="fa fa-phone-volume"></i>
                     </button>
<!--                     <button type="button" class="btn btn-primary btn-floating mx-1 btnAgentSettings" title="Configurações de Agente">
                       <i class="fab fa-teamspeak"></i>
                     </button>-->
                     <button id="asknotificationpermission" type="button" class="btn btn-primary btn-floating mx-1 btnNotifySettings" title="Configurações de Notificação">
                       <i class="fa fa-envelope"></i>
                     </button>
                   </div>
   <div class="modal top fade" id="audioModal" tabindex="-1" aria-labelledby="audioModalLabel" aria-hidden="true" data-mdb-backdrop="true" data-mdb-keyboard="true">
			 <div class="modal-dialog">
				 <div class="modal-content text-left">
					 <div class="modal-header h5 text-white bg-primary justify-content-left">
						 Configurações de Áudio
						 <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
					 </div>
					 <div class="modal-body px-5">
						 <div class="form-outline">
						  <label class="form-label" for="sldVolume">Volume: </label>
						  <div class="range">
							  <input type="range" class="form-range" min="0" max="100" value="100" step="1" id="sldVolume" />
							</div>
						 <label class="form-label" for="micSrc">Dispositivo: </label>
						 <div style="font-size: 12px; margin-bottom: 4px; width: 100%;" id="listmic"> 
						</div>
						</div>
						<button type="button" class="btn btn-primary btn-block mb-3 btnSaveAudio" id="btnSaveAudio">Salvar</button>
					 </div>
				 </div>
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
   </div>
   </div>   
    <script type="text/html" id="template-addon-phone">


</script>
    <script type="text/html" id="template-transfer">
   <div class="modal top fade" id="mdlTransfer" tabindex="-1" aria-labelledby="mdlTransferModalLabel" aria-hidden="true" data-mdb-backdrop="true" data-mdb-keyboard="true">
   <div class="modal-dialog modal-md">
     <div class="modal-content">
     <div class="modal-header">
       <h6 class="modal-title" id="mdlTransfer">Transferência</h6>
       <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
     </div>
       <div class="modal-body">
         <form class="form-inline transfer-form" id="transfer-form">
           <div class="form-group">
           <input type="text" id="numTransfer" class="form-control" name="transfer"/>
         </div>
       <div class="modal-footer">
       <button id="transfercall" class="btn btn-sm btn-secondary transfercall" type="button">Transferir</button>       
       <button id="transferCancel" class="btn btn-sm btn-danger transferCancel" data-mdb-dismiss="modal" style="display: none;" type="button">Cancelar</button>
       <button id="warm" class="btn btn-sm btn-primary warm" type="button">Discar</button>
<!--       <button id="tpark" class="btn btn-sm btn-info tpark">Estacionar</button>-->
       <button id="complete" class="btn btn-sm btn-success complete" type="button">Completar</button>
     </div>
         </form>

       </div>
     </div>
   </div>
 </div>
</script>
    <audio id="ringtone" src="<?php echo base_url(); ?>assets/audio/mp3/ringtone_in.mp3" loop></audio>
    <audio id="dtmfTone" src="<?php echo base_url(); ?>assets/audio/mp3/dtmf.mp3"></audio>
    <audio id="audioRemote"></audio>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/plugins/jquery-3.6.1.min.js"></script>


    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/fluxWebPhone.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/flux.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/flux-web-phone.js"></script>
    

	<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/plugins/script.js"></script>
	<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/plugins/mdb.min.js"></script>
	<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/plugins/popper.min.js"></script>


	<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/plugins/bootstrap.min.js"></script>
	<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/plugins/perfect-scrollbar.min.js"></script>
	<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/plugins/smooth-scrollbar.min.js"></script>




	<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/plugins/bootstrap-notify.js"></script>
	<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/plugins/chartjs.min.js"></script>
	<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/plugins/moment.min.js"></script>


	<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/plugins/pt-br.js"></script>


    <script type="text/javascript">
	var extension_id = '<?php echo $extension_id; ?>';
	var session_login = '<?php echo $session_login; ?>';
	var ramal = '<?php echo $extension; ?>';
	var username = '<?php echo $extension; ?>';
	var login = '<?php echo $extension; ?>';
	var pass = '<?php echo $extension_password; ?>';
	var nome = '<?php echo $outbound_caller_id_name; ?>';
	var domain = '<?php echo $domain_name; ?>';
	var server = '<?php echo $domain_name; ?>';
	var fromNumber = '<?php echo $outbound_caller_id_number; ?>';
	var phoneStatus = '<?php echo $extension_enabled; ?>';
	var accountid = '<?php echo $extension_accountid; ?>';
	var sessid = '<?php echo $_SESSION["user_session_id"]; ?>';
	var wssport = '7442';
	var wsserver = server+':'+wssport+'';
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
		'turnServers' : [{urls:'turn:sbcdev4.flux.net.br:9579', username : 'fluxDev' , credential: 'FluxDev4SBC201'}],
		'Display' : nome,
		'WSServer' : 'wss://'+wsserver+'',
		'Loglevel' : 3
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
		'appKey' : accountid,
		'appAgent': login,
		'appUser' : login,
		'outboundProxy'  : wsserver,
		'authorizationId' : ramal,
		'wsServers' : wsserver,
		'iceCheckingTimeout': 500,
		'traceSip': false,
		'loglevel' : 10,
		'stunServers' : ['stun:stun.l.google.com:19302'],
		'turnServers' : [{urls:'turn:sbcdev4.flux.net.br:9579', username : 'fluxDev' , credential: 'FluxDev4SBC201'}],
		'displayName' : nome,
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
	console.log(session_login);

	</script>
	<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sip/fluxphone.js"></script>

  </body>
</html>
