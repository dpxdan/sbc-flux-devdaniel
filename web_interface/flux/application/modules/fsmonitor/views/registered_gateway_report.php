<? extend('master.php') ?>
<? startblock('extra_head') ?>
<?php
session_start();
include "view_freeswitch_request.php";
include "config.php";
$url_array=explode("/",$_SERVER['REQUEST_URI']);
$url='';
$folder_name=$url_array[1];
for($i=1;$i<count($url_array)-1;$i++){
	$url.="/".$url_array[$i];
}
?>
<style>

.label-sm {
    padding: 0.2em 0.9em 0.3em;
    font-size: 11px;
    line-height: 1;
    height: 20px;
}
.label-inverse, .label.label-inverse, .badge.badge-inverse, .badge-inverse {
    background-color: #87B87F;
}
.label-inverse_red, .label_red.label-inverse_red, .badge_red.badge-inverse_red, .badge-inverse_red {
    background-color: red;
}
/*.label {
    border-radius: 0px;
    text-shadow: none;
    font-weight: 400;
    color: #FFF;
    display: inline-block;
    background-color: #ABBAC3;
}*/
.label-inverse_red.arrowed:before_red{
	border-right-color:#333;
	-moz-border-right-colors:#333
}

.label-inverse_red.arrowed-in:before_red{
	border-color:#333 #333 #333 transparent;
	-moz-border-right-colors:#333
}

.label-inverse_red.arrowed-right_red:after{
	border-left-color:#333;
	-moz-border-left-colors:#333
}

.label-inverse_red.arrowed-in-right_red:after{
	border-color:#333 transparent #333 #333;
	-moz-border-left-colors:#333
}

.label-sm_red.arrowed_red{
	margin-left:4px
}

.label-sm_red.arrowed_red:before{
	left:-8px;
	border-width:9px 4px
}

.label-sm_red.arrowed-in_red{
	margin-left:4px
}

.label-sm_red.arrowed-in_red:before{
	left:-4px;
	border-width:10px 4px
}

.label-sm_red.arrowed-right_red{
	margin-right:4px
}

.label-sm_red.arrowed-right_red:after{
	right:-8px;
	border-width:9px 4px
}

.label-sm_red.arrowed-in-right_red{
	margin-right:4px
}

.label-sm_red.arrowed-in-right_red:after{
	right:-4px;
	border-width:9px 4px
}
.label_red.arrowed_red,.label_red.arrowed-in_red{
	position:relative;
	z-index:1
}

.label_red.arrowed_red:before,.label_red.arrowed-in_red:before{
	display:inline-block;
	content:"";
	position:absolute;
	top:0;
	z-index:-1;
	border-color: transparent;
	border-style: solid;
	/*border-width: 0px;*/
	/*border:0px solid transparent;*/
	border-right-color:#D15B47;
	-moz-border-right-colors:#D15B47
}

.label_red.arrowed-in_red:before{
	border-color:#D15B47;
	border-left-color:transparent;
	-moz-border-left-colors:none
}
.label-sm_red {
    padding: 0.2em 0.9em 0.3em;
    font-size: 11px;
    line-height: 1;
    height: 20px;
}
.label-inverse_red, .label_red.label-inverse_red, .badge_red.badge-inverse_red, .badge-inverse_red {
    background-color: #D15B47;
}

.label_red.arrowed_red, .label_red.arrowed-in_red {
    position: relative;
    z-index: 1;
}
.label-sm_red.arrowed-in_red {
    margin-left: 4px;
}
.label_red {
    border-radius: 0px;
    text-shadow: none;
    font-weight: 400;
    color: #FFF;
    display: inline-block;
    background-color: #ABBAC3;
}

</style>
<script type="text/javascript">

$(document).ready(function(){ 
  var ip = location.host;
  $.ajax({
    type:'POST',
    url: "<?php echo base_url();?>fsmonitor/sip_devices_file_exits/",
    cache    : false,                 
    async: false, 
    success: function(data) {
	if(!data){
	      window.location.href = "<?php echo base_url();?>fsmonitor/gateways/";
	}
    }
   });
});
</script>
<?php 
	$filename = $licence_file;
	if(file_exists($filename))
	{ 
	 $localkey = file_get_contents($filename);
	}
	else{
	 $localkey = '';
	}
?>

<?php
    $this->db->select("value"); 
    $this->db->where("name",'refresh_second'); 
    $system = $this->db->get("system");
    $system_res=$system->result_array();
	
if (!empty($system_res[0])){
	$result=$system_res[0];
}
?>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/module_js/generate_grid.js"></script>
<?php
	if(isset($result['value']) && !empty($result['value'])){
?>
<meta http-equiv="refresh" content="<?php echo $result['value']; ?>" >
<?php
	}
?>


<?php
if(empty($_POST)){
	$_POST['host_id']=0;
} 
if(isset($_POST['second_reload']) && $_POST['second_reload'] != ''){
	$update_array = array("value"=>$_POST['second_reload']);
	$this->db->where("name","refresh_second");
	$this->db->update("system",$update_array);
//	$qry=mysql_query("UPDATE system SET value = '".$_POST['second_reload']."' WHERE name ='refresh_second'")or die(mysql_error());

}

?>
<script type="text/javascript">
$(document).ready(function(){
//  var id = document.getElementById("host_id").value;
 var id="<?php echo $_POST['host_id']?>";

$("#gateway_grid").flexigrid({
    url: "<?php echo base_url();?>fsmonitor/gateways_json/"+id,
    method: 'GET',
    dataType: 'json',
	colModel : [
		{display: '<?php echo gettext("Gateway Name"); ?>', name: 'name', width: 120, sortable: false, align: 'center'},
		{display: '<?php echo gettext("Proxy"); ?>', name: 'proxy', width: 150, sortable: false, align: 'center'},
		{display: '<?php echo gettext("User Name"); ?>', name: 'username', width: 120, sortable: false, align: 'center'},
		{display: '<?php echo gettext("Call In"); ?>', name: 'call-in', width: 120, sortable: false, align: 'center'},
		{display: '<?php echo gettext("Call Out"); ?>', name: 'call-out', width: 120, sortable: false, align: 'center'},
		{display: '<?php echo gettext("Fail Call In"); ?>', name: 'fail-call-in', width: 120, sortable: false, align: 'center'},
		{display: '<?php echo gettext("Fail Call Out"); ?>', name: 'fail-call-out', width: 120, sortable: false, align: 'center'},
		{display: '<?php echo gettext("Status"); ?>', name: 'status', width:80, sortable: false, align: 'center'},
		{display: '<?php echo gettext("State"); ?>', name: 'state', width: 150, sortable: false, align: 'center'},
        {display: '<?php echo gettext("Action"); ?>', name: 'action', width: 150, sortable: false, align: 'center'},
		],
	/*buttons : [
		{name: ' ', bclass: 'reload', onpress : reload_button}	
		],*/
	nowrap: false,
	showToggleBtn: false,
	sortname: "call-id",
	sortorder: "asc",
	usepager: true,
	resizable: true,
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: "auto",
	height: "auto",
	pagetext: '<?php echo gettext("Page"); ?>',
	outof: '<?php echo gettext("of"); ?>',
	nomsg: '<?php echo gettext("No Records"); ?>',
	procmsg: '<?php echo gettext("Processing, please wait ..."); ?>',
	pagestat: '<?php echo sprintf(_("Displaying %s to %s of %s items"), "{from}", "{to}", "{total}"); ?>',

	onSuccess: function(data){
	$('a[rel*=facebox]').facebox({
		    loadingImage : '<?php echo base_url();?>/assets/images/loading.gif',
		    closeImage   : '<?php echo base_url();?>/assets/images/closelabel.png'
	    });

	},
	onError: function(){
	    alert('<?php echo gettext("Sorry, we are unable to connect to FluxSBC!"); ?>');
	}
});
  $("#host_id").change(function(){
	var id = document.getElementById("host_id").value;
  });
});
</script>
<script>
function myFunction() {
    //location.reload();
		document.getElementById("extension").submit();
}
</script>
<? endblock() ?>
<? startblock('page-title') ?>
<?=$page_title?>
<? endblock() ?>
<? startblock('content') ?>


<section class="slice color-three">
	<div class="w-section inverse p-0">
		<div class="card col-md-12 py-4">     
			<form method="POST" action="del/0/" enctype="multipart/form-data" id="ListForm">    
				<table id="gateway_grid" class="flex_grid" align="left" style="display:none;"></table>
			</form>
		</div>  
	</div>
</section>

<? endblock() ?>
<? end_extend() ?>  
