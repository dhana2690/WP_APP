<?php

################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################

$gProgramCode = "CHECKOUT";
include_once "scpt/utilities.inc";
$dealerArray = getDealerInfo($gDealerId);
if((!isset($_SESSION[$systemCode]['global_user_id']) || empty($_SESSION[$systemCode]['global_user_id'])) && $dealerArray['enable_global_login'] == 1) 
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

$paragraphCode = strtoupper($_GET['page']);
if (empty($paragraphCode)) {
	$paragraphCode = "POLICIES";
}

switch ($paragraphCode) {
	case "CUSTOM":
		$pageTitle = ucwords($_GET['content']);
		$paragraphContent = $dealerArray['template_path'] . "/" . str_replace(" ","_",$_GET['content']) . ".html";
		break;
	case "ABOUT":
		$pageTitle = "About " . $dealerArray['dealer_name'];
		break;
	default:
		$paragraphCodeId = getFieldFromId('paragraph_code_id','paragraph_codes','paragraph_code',$paragraphCode);
		if (empty($paragraphCodeId)) {
			$paragraphCodeId = 1;
		}
		$pageTitle = getFieldFromId('heading','paragraph_codes','paragraph_code_id',$paragraphCodeId);
}

//POBF - 108, Meta tags
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
	<title><?php echo ($metaTagArray['istitleinchild']== '1'? $metaTagArray['title']: $metaTagArray['title']);
	if (empty($metaTagArray['title'])){ echo $dealerArray['dealer_name']; }?></title>
	<meta name="description" content="<?php echo ($metaTagArray['isdescriptioninchild']== '1'? $pageTitle . " - " . $metaTagArray['description']:$metaTagArray['description']);
	if (empty($metaTagArray['description'])){ echo $pageTitle . " - " . $dealerArray['dealer_name'] . " - " . $dealerArray['site_description'];}?>">
	<meta name="keywords" content="<?php echo ($metaTagArray['iskeywordinchild']== '1' ? str_replace(" ",", ",strtolower($pageTitle)) . ", " . $metaTagArray['keyword']:$metaTagArray['keyword']); 
	if (empty($metaTagArray['keyword'])){echo  str_replace(" ",", ",strtolower($pageTitle)) . ", " . $dealerArray['site_keywords'];}?>">
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
	<?php 
		if (!empty($GLOBALS['gExtraCode'])) {
			$find = array('/<script(.*?)>/is','/<\/script>/is');
			$replace = array('','');
			$scrubbed = preg_replace($find,$replace,$GLOBALS['gExtraCode']);
			$scrubbed = preg_replace('/\$\((.*?)\}\)\;/is', '', $scrubbed);
			echo "<script>" . $scrubbed . "</script>";
		}	
	?>	
</head>
<body>
    <?php if ($dealerArray['enable_global_login'] == 1 && isset($_SESSION[$systemCode]['global_user_id'])) {
                $globalUsername = getFieldFromId('session_data', 'sessions', 'session_id', $_SESSION[$systemCode]["global_user_id"]);
                ?>
                <div id="welcomenote">
                    <span style="vertical-align: middle; margin-right: 10px;color:#fff">Welcome, <?php echo $globalUsername; ?></span>                                       
                    <span style="color: #fff;">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
                    <a id="gLogout" href="/globallogout.php" style="color:#fff;vertical-align: bottom; margin-right: 10px;">Logout</a>
                </div>
                <?php
            }?>
<input type="hidden" id="site_dealer_id" value="<?php echo $GLOBALS['gDealerId'] ?>" />

	<?php include_once $templateArray['header'] . "/header.inc"; ?>

	<table cellspacing="0" cellpadding="0"><tr>
		<td valign="top">
			<?php include_once $templateArray['checkout_sidebar'] . "/checkout_sidebar.inc"; ?>
		</td>
		<td valign="top">
		
			<div id="page_content">
			
				<h1 class="default">Chekout</h1>
		
				<div class="page_pane">
					<div style="margin: 200px 120px 220px 0; color: #999; font-size: 21px; text-align: center;">
						Checkout is temporarily unavailable.<br>
						Please contact customer service.
					</div>
				</div> <!-- page_pane -->
			
			</div>
			
		</td>
	</tr></table>

<?php include_once $templateArray['footer'] . "/footer.inc"; ?>
<?php include_once "scpt/dealer_tracking_code.inc";?> 
</body>
</html>
