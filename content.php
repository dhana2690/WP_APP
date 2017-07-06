<?php

################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################

$gProgramCode = "CONTENT";
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
// POBF - 108 , Meta tags
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
   	<title><?php echo ($metaTagArray['istitleinchild']== '1'? $metaTagArray['title'] . " | " . $pageTitle : $metaTagArray['title']);
	if (empty($metaTagArray['title'])){ echo $dealerArray['dealer_name'] . " | " . $pageTitle;}?>	</title>
	<meta name="description" content="<?php echo ($metaTagArray['isdescriptioninchild']== '1' ? $pageTitle . " - " . $metaTagArray['description']:$metaTagArray['description']);
    	if (empty($metaTagArray['description'])){ echo $pageTitle . " - " . $dealerArray['dealer_name'] . " - " . $dealerArray['site_description'];}?>">
	<meta name="keywords" content="<?php echo ($metaTagArray['iskeywordinchild']== '1'? str_replace(" ",", ",strtolower($pageTitle)) . ", " . $metaTagArray['keyword']:$metaTagArray['keyword']); 
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
			
				<h1 class="default"><?php echo $pageTitle ?>
					<?php 
                                        if ($_GET['return'] == 'checkout') {
						echo "<a href='scpt/transfer_to_checkout.php' class='return_link'>Return to Checkout &rarr;</a>";
					} 
                                        if ($_GET['return'] == 'financecheckout') {                     
						echo "<a href='scpt/transfer_to_checkout.php?checkout=finance&token=".$_GET['token']."' class='return_link'>Return to FinanceCheckout &rarr;</a>";
					} 
                                        ?>
				</h1>
	
		
				<div class="page_pane">
					<?php
						switch($paragraphCode) {
						case "POLICIES":
							$resultSet = executeQuery("select * from paragraphs where paragraph_code_id = ? and dealer_id is null",$paragraphCodeId);
							while ($row = getNextRow($resultSet)) {
								echo "<h2>" . $row['description'] . "</h2>";
								echo nl2br(trim(str_replace('$dealerName',$dealerArray['dealer_name'],$row['content'])));
								echo "<br><br>";
							}
							
							/*
							if ($gDealerId > 1 && $gDealerId != $gDefaultDealerId) { //insert shipping rates for this dealer
								$preferenceCodes = array(
									'SHIPFIREARM1ST'=>'ship_firearm_1st',
									'SHIPFIREARMADDL'=>'ship_firearm_addl',
									'SHIPAMMO1ST'=>'ship_ammo_1st',
									'SHIPAMMOADDL'=>'ship_ammo_addl',
									'SHIPITEM1ST'=>'ship_item_1st',
									'SHIPITEMADDL'=>'ship_item_addl'
								);
								$shippingRates = array();
								foreach ($preferenceCodes as $preferenceCode => $preferenceField) {
									$preferenceId = getFieldFromId('preference_id','preferences','preference_code',$preferenceCode);
									$preferenceValue = getFieldFromId('preference_value','dealer_preferences','preference_id',$preferenceId,'dealer_id = ' . $gDealerId);
									// sets value to system default if empty
									//if (empty($preferenceValue)) {
									//	$preferenceValue = getFieldFromId('preference_value','dealer_preferences','preference_id',$preferenceId,'dealer_id = 1');
									//}
									$shippingRates[$preferenceField] = $preferenceValue;
								}
								echo "<h2>Shipping &amp; Handling Rates</h2>";
								echo $dealerArray['dealer_name'] . " charges the following shipping &amp; handing rates:<br>";
								echo "<div style='margin: 12px 40px;'>";
								echo "<table cellpadding='6'>";
								if (is_numeric($shippingRates['ship_firearm_1st'])) {
									echo "<tr><td>Firearms:</td><td>1st Firearm Per Order:</td><td align='right'>$" . number_format($shippingRates['ship_firearm_1st'],2) . "</td>";
								}
								if (is_numeric($shippingRates['ship_firearm_addl'])) {
									echo "<td></td><td>Each Additional Firearm Per Order:</td><td align='right'>$" . number_format($shippingRates['ship_firearm_addl'],2) . "</td></tr>";
								}
								if (is_numeric($shippingRates['ship_ammo_1st'])) {
									echo "<tr><td>Ammo:</td><td>1st Ammo Item Per Order:</td><td align='right'>$" . number_format($shippingRates['ship_ammo_1st'],2) . "</td>";
								}
								if (is_numeric($shippingRates['ship_ammo_addl'])) {
									echo "<td></td><td>Each Additional Ammo Item Per Order:</td><td align='right'>$" . number_format($shippingRates['ship_ammo_addl'],2) . "</td></tr>";
								}
								if (is_numeric($shippingRates['ship_item_1st'])) {
									echo "<tr><td>All Others:</td><td>1st Item Per Order:</td><td align='right'>$" . number_format($shippingRates['ship_item_1st'],2) . "</td>";
								}
								if (is_numeric($shippingRates['ship_item_addl'])) {
									echo "<td></td><td>Each Additional Item Per Order:</td><td align='right'>$" . number_format($shippingRates['ship_item_addl'],2) . "</td></tr>";
								}
								echo "</table>";
								echo "</div>";
							}
							*/

							$resultSet = executeQuery("select * from paragraphs where paragraph_code_id = ? and dealer_id = ?",$paragraphCodeId,($gDealerId > 1 ? $gDealerId : 1));
							while ($row = getNextRow($resultSet)) {
								echo "<h2>" . $row['description'] . "</h2>";
								echo nl2br(trim($row['content']));
								echo "<br><br>";
							}
							
							break;
						case "NEWS":
							$resultSet = executeQuery("select * from paragraphs where paragraph_code_id = ? order by paragraph_id desc",$paragraphCodeId);
							while ($row = getNextRow($resultSet)) {
								echo "<h2>" . $row['description'] . "</h2>";
								echo nl2br($row['content']);
								echo "<br><br>";
								if ($paragraphCode == "NEWS" && !empty($row['notes'])) {
									echo "<a href='" . $row['notes'] . "' target='_blank'>read more...</a><br><br>";
								}
							}
							break;
							
						case "REGULATIONS":
							if ($gDealerId > 0 && $gDealerId != $gDefaultDealerId) {
								$resultSet = executeQuery("select * from paragraphs where paragraph_code_id = ? and dealer_id = ?",$paragraphCodeId,$gDealerId);
								while ($row = getNextRow($resultSet)) {
									echo "<h2>" . $row['description'] . "</h2>";
									echo nl2br(trim($row['content']));
									echo "<br><br>";
								}
							}
							$resultSet = executeQuery("select * from paragraphs where paragraph_code_id = ? and dealer_id is null",$paragraphCodeId);
							while ($row = getNextRow($resultSet)) {
								echo $row['content'];
							}
							break;

						case "ABOUT":
							echo "<div style='margin: 120px 90px 490px 90px; text-align: center; font-size: 18px; color: #999;'>This is where dealers can post additional info about their store, e.g. hours of operation, shooting range info, extra Facebook code, etc.</div>";
							break;

						case "CUSTOM":
							include_once $paragraphContent;
							break;

						}
					?>
				</div> <!-- page_pane -->
			
			</div>
			
		</td>
	</tr></table>

<?php include_once $templateArray['footer'] . "/footer.inc"; ?>
 
<?php include_once "scpt/dealer_tracking_code.inc";?> 
</body>
</html>
