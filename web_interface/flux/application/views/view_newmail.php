
 <link href="<?= base_url() ?>assets/css/popup.css" rel="stylesheet" type="text/css">
 <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/popup/custom.js"></script>
      <link href="<?= base_url() ?>assets/css/checkbox.css" rel="stylesheet"/>
     <link href="<?= base_url() ?>assets/css/bootstrap.min.css" rel="stylesheet"/>
     <!-- Google Font -->
       <link href="https://fonts.googleapis.com/css?family=Montserrat:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet"> 
     <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap-select.css"/>
     <link href="<?= base_url() ?>assets/css/sidebar_style.css" rel="stylesheet" type="text/css"/>
     <link href="<?php echo base_url(); ?>/assets/css/jquery.datetimepicker.min.css" rel="stylesheet" />
     <link rel="stylesheet" media="all" type="text/css" href="<?php echo base_url(); ?>/assets/css/tabcontent.css"/>
     <link href="<?= base_url() ?>assets/fonts/font-awesome-4.7.0/css/font-awesome.css" rel="stylesheet"/>
     <link href="<?= base_url() ?>assets/css/global-style.css" rel="stylesheet" type="text/css"/>
     <link href="<?php echo base_url(); ?>assets/css/facebox.css" rel="stylesheet" media="all" />	
     <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/flexigrid.css" type="text/css"/>
     <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-glyphicons.css" rel="stylesheet">
     <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/animate.css" type="text/css"/>
     <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/style.css" type="text/css"/>
     <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sticky_menu.css" type="text/css"/>
     <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/global-style.css" type="text/css"/>
     <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/com-style.css" type="text/css"/>
<script type="text/javascript">

function validateForm(){
    var email = document.forms["form1"]["email"].value;
    if (email == null || email == "") {
	  $("#email_err").html('Please enter your email');    
        return false;
    }else{
  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/; 
	var check = regex.test(email);
	if(check == true ){
	  $("#email_err").html('');    
	}else{
	  $("#email_err").html('Please enter proper from address');    
          return false;
	}
    }
    var to = document.forms["form1"]["to"].value;
    if (to == null || to == "") {
	  $("#to_err").html('Please enter your email');    
        return false;
    }else{
  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/; 
	var check = regex.test(email);
	if(check == true ){
	  $("#to_err").html('');    
	}else{
	  $("#to_err").html('Please enter proper to address');    
          return false;
	}
    }
    var subject = document.forms["form1"]["subject"].value;
    if (subject == null || subject == "") {
	  $("#subject_err").html('This field is required');    
        return false;
    }else{
	  $("#subject_err").html('');    
    }
    var template = document.forms["form1"]["template"].value;
    if (template == null || template == "") {
	  $("#template_err").html('This field is required');    
        return false;
    }else{
	  $("#template_err").html('');    
    }

 }
 
 $(document).ready(function(){
  $('#name').change(function(){
    $('#une').text('');
    return false;
  });
   $('#email').change(function(){
    $('#email1').text('');
    return false;
  });
  $('#email').change(function(){
    $('#email2').text('');
    return false;
  });
  $('#feedback').change(function(){
    $('#feedback1').text('');
    return false;
  });
  });
</script>

 
<html>
<title>FluxSBC-Feedback</title>
<div id="form-main">
  <div id="form-div" style="height:700px;">
  <center><h1><b>Test Mail</b><h1></center>
    <form class="form" id="form1" name="form1" method="post" action="<?=base_url()?>newmail/customer_mail_result">
      
      
      <p>Test mail to Email setting.</p>
      <div class="name">    
        <input name="from" type="text" class=" feedback-input" placeholder="From" id="email" />
        <span id="email_err" style="color:red;"> </span> 
      </div>
      <div class="email">
        <input name="to" type="text" class="validate[required,custom[email]] feedback-input" id="to" placeholder="To" />
        <span id="to_err" style="color:red;"> </span> 
      </div>
      <div class="Subject">
     
        <input name="subject" type="text" class="validate[required,custom[email]] feedback-input" id="subject" placeholder="Subject" />
        <span id="subject_err" style="color:red;"> </span> 
      </div>
      <div class="text">   
        <textarea name="message" name="feedback" id="feedback" class="validate[required,length[6,300]] feedback-input" id="template" placeholder="Body"></textarea>
        <span id="template_err" style="color:red;"> </span> 
      </div>
       <font color='red'> <span id="feedback1" name="feedback1"> </span> </font>
        <center><input type="submit" value="Send Mail"  class="btn btn-line-parrot" onclick="return validateForm();"/></center>
    </form>
  </div>
  </html>
