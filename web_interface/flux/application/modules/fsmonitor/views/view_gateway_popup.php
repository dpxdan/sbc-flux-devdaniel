<script>
function closepopup() {
$(document).trigger('close.facebox');
    
}
</script>
<section class="slice m-0">
 <div class="w-section inverse p-0">
   <div>
     <div>
        <div class="col-md-12 p-0 card-header">
	        <h3 class="fw4 p-4 m-0"><? echo $page_title; ?></h3>
		</div>
     </div>
    </div>
  </div>    
</section>

<?php if($fail == 1){
?>
<div>
<h3 class="bg-secondary text-light p-3 rounded-top"><?php echo gettext("Register Gateway Information"); ?></h3>
<?php
echo "Gateway ragister fail please try again";
?>
<button class='btn btn-line-parrot' value='Reload' onclick="javascript:window.location ='<?= base_url() ?>fsmonitor/gateways/'"><i class='fa fa-repeat'></i><?php echo gettext("Relaod"); ?></button>
</div>
<center>
  <div class="col-lg-12 py-2">
    <input class="btn btn-line-sky  mx-2" onclick="closepopup()" name="action" value="Cancel" type="button">
  </div>
</center>
<?php
}
else{
 ?>
<section class="slice color-three m-0">
   <div class="w-section inverse p-4">
     <div class="pop_md col-12 pb-4">
          <form id="popupid" name="popupname" enctype="multipart/form-data" >
      <div class="card">
       <div class="col-12">  
<div id="floating-label" class="pb-4 col-12">
<h3 class="bg-secondary text-light p-3 rounded-top"><?php echo gettext("Register Gateway Information"); ?></h3>
       
 <div  class="col-md-12 py-4">
 <div  class="row">
 <div  class="col-md-6 border-right">
	<div  class="col-md-12">
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Gateway Name"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $gname; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Proxy"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $proxy; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Realm"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $realm; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("User Name"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $username; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Context"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $context; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Status"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $status; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Profile"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $profile; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Password"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $password; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("From"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $from; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("To"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $to; ?></dd>
		</dl>
	</div>
</div>
<div  class="col-md-6">
<div  class="col-md-12">
		<dl class="row">
			<dt class="col-md-6 p-0"><?php echo gettext("State"); ?>:</dt>
			<dd class="col-md-6"><?php if($state== 'NOREG'){ ?>
			<span class="label label-sm label-inverse arrowed-in"  style="padding-top:4px;">NOREG</span> 
		<!--	<img src="<?php echo base_url();?>/assets/images/green_btn.png"/>-->
			     <? }elseif($state == 'REGED'){ ?>
			<span class="label label-sm label-inverse arrowed-in" style="padding-top:4px;">REGED</span> 
		<!--	<img src="<?php echo base_url();?>/assets/images/red.png"/>-->
			     <? }else{ ?>
			<span class="label_red label-sm_red label-inverse_red arrowed-in_red" style="padding-top:4px;">FAIL_WAIT</span> 
		<!--	<img src="<?php echo base_url();?>/assets/images/red.png"/>-->
		  	     <?php } ?> </dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Expires"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $expires; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Frequency"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $frequency; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Ping Frequency"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $ping_frequency; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Ping"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $ping; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Call In"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $call_in; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Call Out"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $call_out; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Fails Call In"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $fail_call_in; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Fails Call Out"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $fail_call_out; ?></dd>
		</dl>
		<dl class="row">
			<dt class="col-md-6 p-0 font-weight-bold"><?php echo gettext("Contact"); ?>:</dt>
			<dd class="col-md-6 label_2"><?php echo $contact; ?></dd>
		</dl>
	</div>
   </div>
</div>
</div>
    </div>
       </div>
     </div>      
</form>
    </div>      
	
	  <div class="col-lg-12 text-center mb-4">
	    <button class="btn btn-secondary" onclick="closepopup()" name="action" value="Cancel" type="button">Cancel</button>
	  </div>
	
   </div>
</section>
<?php
}
 ?>