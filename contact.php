<?php

################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################

$gProgramCode = "CONTACT";
include_once "scpt/utilities.inc";
$dealerArray = getDealerInfo($gDealerId);
if((!isset($_SESSION[$globalSystemCode]['global_user_id']) || empty($_SESSION[$globalSystemCode]['global_user_id'])) && $dealerArray['enable_global_login'] == 1) 
{
    header("Location:http://".$dealerArray['dealer_url']);
}
if ($gDealerId > 0 && $gDealerId != $gDefaultDealerId) {
	include_once "scpt/class.shoppingcart.php";
	$cookieShoppingCartId = $_COOKIE["NFDNetwork"];
	if (empty($cookieShoppingCartId)) {
		$itemsInCart = array();
	} else {
		$shoppingCart = new ShoppingCart($gDealerId);
		$shoppingCartId = $shoppingCart->getShoppingCart($cookieShoppingCartId);	
		$itemsInCart = $shoppingCart->getShoppingCartItems();
	}
}
$templateArray = getTemplateInfo($gDealerId,$_GET['tmp']);

$leftSidebarLimit = 0;

if ($_POST['action'] == 'send') {
     $ipaddress = '';
     if (isset($_SERVER['HTTP_CLIENT_IP']))
         $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
     else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
         $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
     else if(isset($_SERVER['HTTP_X_FORWARDED']))
         $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
     else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
         $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
     else if(isset($_SERVER['HTTP_FORWARDED']))
         $ipaddress = $_SERVER['HTTP_FORWARDED'];
     else if(isset($_SERVER['REMOTE_ADDR']))
         $ipaddress = $_SERVER['REMOTE_ADDR'];
     else
         $ipaddress = 'UNKNOWN';
	$returnArray = array();
	$bad = array("http://","www.","internet","weblisting","search engine");
	$spam = 0;
	foreach ($bad as $search) {
		if (strpos(strtolower($_POST["comment"]),$search) > -1 ) { // spam
			$spam = 1;
			break;
		}
	}
	if ($spam == 1) {
		$returnArray['status'] = "spam";
		$returnArray['message'] = "Our system thinks this is spam.";
	} else {
		$content = "This is a contact form from the " . $dealerArray['dealer_name'] . " web site.\n\n";
		$content .= "--- Contact Info ----------------------\n";
		$content .= "Name: " . $_POST["full_name"] . "\n";
		$content .= "Email: " . $_POST["email_address"] . "\n";
		$content .= "IP Address: " . $ipaddress . "\n";
		if (!empty($_POST["phone_number"])) {
			$content .= "Phone: " . $_POST["phone_number"] . "\n";
		}
		if (!empty($_POST["comment"])) {
			$content .= "\n--- Comment ---------------------------\n";
			$content .= stripslashes($_POST["comment"]) . "\n";
		}
		$content .= "\n(Do not reply to this email - use customer email address above)";
		
		$mail = new PHPMailer();
		$mail->IsMail();
		$mail->IsHTML(false);
		$mail->SetFrom('NFDNweborder@nfdnetwork.com', 'NFDN');
		$mail->AddReplyTo($dealerArray['dealer_email'], $dealerArray['dealer_name']);
		$mail->AddAddress($dealerArray['dealer_email']);
		//$mail->AddBCC("system@nfdnetwork.com", "NFDN Webmaster");		
		$mail->Subject = $dealerArray['dealer_name'] . " Website Quick Request";
		$mail->Body = $content;
		if($mail->Send()) {
			$returnArray['status'] = "sent";
			$returnArray['message'] = "Your request has been sent!";
		} else {
			$returnArray['status'] = "error";
			$returnArray['message'] = "There was an error sending your Quick Request.";
		}
	}
	echo json_encode($returnArray);
	exit;
}
// POBF - 108, Meta tags
$metaTagArray = array();
$query = "select * from dealer_meta_tags where dealer_id = ? ";
$resultSet = executeQuery($query, $gDealerId);
while ($row = getNextRow($resultSet)) {
    $metaTagArray['title'] = $row['title'];
    $metaTagArray['description'] = $row['description'];
    $metaTagArray['keyword'] = $row['keyword'];
    $metaTagArray['istitleinchild'] = $row['istitleinchild'];
    $metaTagArray['isdescriptioninchild'] = $row['isdescriptioninchild'];
    $metaTagArray['iskeywordinchild'] = $row['iskeywordinchild'];
}
?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<!-- POBF - 108, Meta tag update -->
	<title><?php echo ($metaTagArray['istitleinchild']== '1'? $metaTagArray['title'].'| Contact Us':$metaTagArray['title']);
	if (empty($metaTagArray['title'])){ echo $dealerArray['dealer_name'].'| Contact Us' ;}?> </title>
	<meta name="description" content=" <?php echo ($metaTagArray['isdescriptioninchild']== '1' ? 'Contact Us -'.  $metaTagArray['description']:$metaTagArray['description']);
	if (empty($metaTagArray['description'])){ echo 'Contact Us -'.  $dealerArray['dealer_name'] . " - " . $dealerArray['site_description'];}?>">
	<meta name="keywords" content="<?php echo ($metaTagArray['iskeywordinchild']== '1'? $metaTagArray['keyword']:$metaTagArray['keyword']); 
	if (empty($metaTagArray['keyword'])){echo $dealerArray['site_keywords'];}?>">
	<!-- POBF - 108, Meta tag update -->
	<link rel="stylesheet" href="templates/default/universal-styles-v5.css">
	<link rel="stylesheet" href="<?php echo $templateArray['path'] ?>/styles-v1.css">
	<link rel="stylesheet" href="scpt/custom-theme/jquery-ui.css">
	<script type="text/javascript">
		if (top != self) {
			top.location.href = self.location.href;
		}
	</script>
	<script src="scpt/jquery.js"></script>
	<script src="scpt/jquery-ui.js"></script>
	<script src="scpt/shared_v3.js?v=120906"></script>
	<!--[if lt IE 9]>
	<script src="scpt/modernizr-2.0.6.js"></script>
	<![endif]-->
	<?php include_once "scpt/google_code.inc"; ?>
       
  </head> 
  <body>      
    <?php if ($dealerArray['enable_global_login'] == 1 && isset($_SESSION[$globalSystemCode]['global_user_id'])) {
                $globalUsername = getFieldFromId('session_data', 'sessions', 'session_id', $_SESSION[$globalSystemCode]["global_user_id"]);
                ?>
                <div id="welcomenote">
                <div class="wel-wrap">
                    <span class="welcome" style="vertical-align: middle; margin-right: 0px;color:#fff">Welcome, <?php echo $globalUsername; ?></span>                                       
                    <span style="color: #fff;" class="sep">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
                    <a id="gLogout" href="/globallogout.php" style="float:right;color:#fff;vertical-align: bottom; margin-right: 10px;">Logout</a>
                    </div>
                </div>
                <?php
            }?>
<input type="hidden" id="site_dealer_id" value="<?php echo $GLOBALS['gDealerId'] ?>" />

	<?php include_once $templateArray['header'] . "/header.inc"; ?>

	<table cellspacing="0" cellpadding="0"><tr>
		<td valign="top">
			<?php include_once $templateArray['left'] . "/sidebar.inc"; ?>
		</td>
		<td valign="top">
		
			<div id="page_content">

				<h1 class="default">Contact Us</h1>

				<div class="page_pane">

				<p>For fastest service, contact us by email: <b><?php echo $dealerArray['dealer_email'] ?></b></p>
				
				<p>You can also use the Quick Request form below...</p>
				
				<h2>Quick Request</h2>
				<table cellpadding="0" cellspacing="6">
					<tr>
						<td width="133" align="right">Your Name: </td>
						<td><input type="text" id="full_name" size="30" class="field"></td>
					</tr>
					<tr>
						<td width="133" align="right">Email Address: </td>
						<td><input type="text" id="email_address" size="30" class="field"></td>
					</tr>
					<tr>
						<td width="133" align="right">Phone Number: </td>
						<td><input type="text" id="phone_number" size="30" class="field"></td>
					</tr>
					<tr>
						<td width="133" align="right" valign="top">Comment / Request: </td>
						<td><textarea id="comment" rows="8" cols="44" class="field"></textarea></td>
					</tr>
					<tr>
						<td></td>
						<td id="send_td">
							<button id="send">Send Quick Request</button>
						</td>
					</tr>
				</table>

				<br><br>				
				
				<?php if ($gDealerId > 0) { ?>
                <div class="hei-contact"></div>
				<h2>Store Contact Info...</h2>
				<table width="100%">
					<tr>
						<td valign="top" align="center">
							<?php
								echo "<b>" . $dealerArray['dealer_name'] . "</b><br>";
								echo $dealerArray['dealer_address'] . "<br>";
								echo $dealerArray['dealer_city'] . ", " . $dealerArray['dealer_state'] . " " . $dealerArray['dealer_zip_code'] . "<br>";
								echo $dealerArray['phone_number'];
							?>
						</td>
						<?php
							if (!empty($dealerArray['store_hours'])) {
								echo "<td valign='top' align='center'>";
								echo "<b>Store Hours:</b><br>";
								echo nl2br($dealerArray['store_hours']);
								echo "</td>";
							}
						?>
					</tr>
				</table>
				<?php } ?>
				</div> <!-- page_pane -->
			
			</div>
			
		</td>
	</tr></table>

<?php include_once $templateArray['footer'] . "/footer.inc"; ?>

<div id="error_tip" style="display: none;"><div id="error_tip_contents"><span id="error_tip_message"></span><div id="error_tip_blip"><img src="tmpl/error_blip.png"></div></div></div>

<script>
$(document).ready(function () {

	$("input[type!=button][type!=hidden],textarea").focus(function(){$(this).css("background-color",""); $("#error_tip").hide();} );

	$("#send").click(function(){
		var error = false;
		if (!error && ($("#full_name").val() == "" || $("#full_name").val().length < 1)) {
			error = showError($("#full_name"),"Name cannot be blank");
		}
		if (!error && ($("#email_address").val() == "" || $("#email_address").val().length < 1)) {
			error = showError($("#email_address"),"Email address cannot be blank");
		}
		if (!error && !isValidEmailAddress($("#email_address").val()) ) {
			error = showError($("#email_address"),"Enter 'address@domain.ext' format");
		}
		if (error === false) {
			var fields = {
				action:"send",
				full_name:$("#full_name").val(),
				email_address:$("#email_address").val(),
				phone_number:$("#phone_number").val(),
				comment:$("#comment").val()
			}
			$.ajax({
				url: "contact.php",
				type: "POST",
				data: fields,
				success: function(returnArray) {
					$("#send_td").html(returnArray['message']);
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					$("#send_td").html("An error occurred: " + errorThrown);
				},
				dataType: "json"
			});
		}			
	});
});

function showError(field,message) {
	var xOffset = 24;
	var yOffset = 15;
	if ($(field).attr("type") == "checkbox") {
		xOffset = 27;
		yOffset = 24;
	}
	if ($(field).attr("id") == "phone_number") {
		xOffset = 120;
	}
	
	$(field).css("background-color","#fff6c7");
	var position = $(field).position();
	$("#error_tip_message").text(message);
	$("#error_tip").css({"left":position.left+$(field).width()-xOffset,"top":position.top-$(field).height()-yOffset}).show();
	return true;
}

function isValidEmailAddress(emailAddress) {
	var pattern = new RegExp(/^(("[\w-+\s]+")|([\w-+]+(?:\.[\w-+]+)*)|("[\w-+\s]+")([\w-+]+(?:\.[\w-+]+)*))(@((?:[\w-+]+\.)*\w[\w-+]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
	return pattern.test(emailAddress);
};

function isValidPhoneNumber(phoneNumber) {
	var pattern = /^\(?(\d{3})\)?[- ]?(\d{3})[- ]?(\d{4})$/;  
	return pattern.test(phoneNumber);  
}
</script>
<?php include_once "scpt/dealer_tracking_code.inc";?> 
</body>
</html>
