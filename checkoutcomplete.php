<?php
################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################

$gProgramCode = "CHECKOUTCOMPLETE";
include_once "scpt/utilities.inc";
// this was set in checkout.php
// forcing a current session helps prevent fake tokens 
$shoppingCartId = $_SESSION["shopping_cart_id"];
if (empty($shoppingCartId)) {
	//header("Location: http://nfdmall.com/?error=no_cart");
	//exit;
}

//trackLog function
function trackLog($message)
{
   $fp = fopen('/var/www/logs/transactionLog-'.date('Y-m-d').'.log', 'a');
   fwrite($fp, date('Y-m-d h:i:s a',time()).' '.$message."\r\n"); 
   fclose($fp);
}

trackLog("---------------- IN CHECKOUT COMPLETE ----------------");

//when sent from a dealer's domain, there will be a 'token' consisting 
//of the order cart id followed by a random string
if (empty($_GET['token'])) {
	header("Location: http://" . $gDealerUrl . "/?error=no_token");
	trackLog("---------------- TOKEN EMPTY ----------------");
	exit;
}

// extract the order_id
$token = $_GET['token'];
$tokenId = "";
for ($i = 0; $i < strlen($token); $i++) {
	$digit = substr($token,$i,1);
	if (is_numeric($digit)) {
		$tokenId .= $digit;
	} else {
		break;
	}
}

if (empty($tokenId)) {
	
	header("Location: http://nfdmall.com/?error=no_order_id");
	//header("Location: http://" . $_SESSION['dealerurl'] . "/?error=no_token");
	exit;
}

$orderId = $tokenId;

trackLog("---------------- TOKEN ID : $orderId ----------------");

// set dealerId and dealer URL using shopping cart since domain is currently nfdnetwork.com
$gDealerId = getFieldFromId('dealer_id','orders','order_id',$orderId);
$gDealerUrl = getFieldFromId('domain_name','domain_names','dealer_id',$gDealerId);

$dealerArray = getDealerInfo($gDealerId);
$dealerArray['dealer_url'] = $gDealerUrl; // was retrieved earlier
#$res = executeQuery("select user_id from users where user_type_id=9 and dealer_id = ?",$gDealerId);
#$row = getNextRow($res);
#$_SESSION["global_user_session_data"] = getFieldFromId('session_id', 'sessions', 'session_id',session_id(),'dealer_id', $gDealerId);
if((!isset($_SESSION["global_user_session_data"]) || empty($_SESSION["global_user_session_data"])) && $dealerArray['enable_global_login'] == 1)
{
    header("Location:http://".$dealerArray['dealer_url']);
}

$templateArray = getTemplateInfo($gDealerId);

#################
// fraud prevention methods
$alertSubtotal = 0; // capture amounts for potential fraud alert
$alertDepartments = array(7=>600); // department_id and amount that will trigger a fraud alert
$alertShippingAddress = false;

#################
// adding multiple location dealers
$dealerLocations = array();
$resultSet = executeQuery("select * from dealers where master_dealer_id = ?",$gDealerId);
while ($row = getNextRow($resultSet)) {
	$dealerLocations[] = array('location_id'=>$row['dealer_id'],'location_name'=>getFieldFromId('company_name','contacts','contact_id',$row['contact_id']));
}
if (count($dealerLocations)>0) {
	if (empty($_SESSION['cart_info']['location_id'])) {
		$_SESSION['cart_info']['location_id'] = $dealerLocations[0]['location_id'];
	}
	if (empty($_SESSION['cart_info']['location_name'])) {
		$_SESSION['cart_info']['location_name'] = $dealerLocations[0]['location_name'];
	}
	foreach($dealerLocations as $dealerLocation) {
		if ($dealerLocation['location_id'] == $_SESSION['cart_info']['location_id']) {
			$dealerLocationId = $dealerLocation['location_id'];
			$dealerLocationName = $dealerLocation['location_name'];
			break;
		}
	}
} else {
	$dealerLocationId = $gDealerId;
	$dealerLocationName = $dealerArray['dealer_city'] . ", " . $dealerArray['dealer_state'];
}
	
$details = array();

$resultSet = executeQuery("select * from orders where order_id = ? and dealer_id = ?",$orderId,$gDealerId);
if ($row = getNextRow($resultSet)) {
	$dealerOrderNumber = $row['order_number'];
	
	// get total of all payment methods applied to this order, including gift cards
	$orderSet = executeQuery("select sum(amount) as total from order_payments where order_id = ?",$orderId);
	$orderRow = getNextRow($orderSet);
	$orderTotal = $orderRow['total'];
	$details[] = "<table width='100%'>";
	$details[] = "<tr>";
	$details[] = "<td><div style='background-color: #e1e1e1; border-top: 1px solid #999; margin: 0px 0px 12px 0px; padding: 6px;'>";
	$details[] = "<h2 style='font-size: 18px; margin: 0px;'>Order Details</h2></div></td>";
	$details[] = "</tr>";
	$details[] = "<tr>";
	$details[] = "<td style='font-size: 12px; padding: 0px 6px 0px 6px;'>";
	$details[] = "<table cellpadding='0' cellspacing='0' width='100%' style='font-size: 12px;'><tr>";
	$details[] = "<td valign='top'><b>Order Number:</b> " . $dealerOrderNumber . "</td>";
	$details[] = "<td valign='top' align='center'><b>Order Date:</b> " . date("m/d/Y",strtotime($row['order_date'])) . "</td>";
	$details[] = "<td valign='top' align='right'><b>Order Total:</b> $" . number_format($orderTotal,2);
	$details[] = "</td></tr></table>";
	$details[] = "</td>";
	$details[] = "</tr>";
	$details[] = "<tr>";
	$details[] = "<td><div style='background-color: #e1e1e1; border-top: 1px solid #999; margin: 12px 0px 12px 0px; padding: 6px;'>";
	$details[] = "<h2 style='font-size: 18px; margin: 0px;'>Customer, Billing and Shipping Info</h2></div></td>";
	$details[] = "</tr>";
	$details[] = "<tr>";
	$details[] = "<td>";
	$details[] = "<table cellpadding='0' cellspacing='0' width='100%' style='font-size: 12px;'>";
	$details[] = "<tr>";
	$details[] = "<td valign='top' width='33%'>";
	$details[] = "<b>Customer</b><br>";
	$resultSet = executeQuery("select * from contacts where contact_id = ?",$row['contact_id']);
	$contactRow = getNextRow($resultSet);
	$resultSet = executeQuery("select * from addresses where address_id = ?",$row['billing_address_id']);
	$billingRow = getNextRow($resultSet);
	$details[] = $billingRow['full_name'] . "<br>";
	$customerEmail = $contactRow['email_address'];
	$details[] = "Email: " . $contactRow['email_address'] . "<br>";
	$details[] = "Phone: " . $billingRow['phone_number'] . "<br>";
	if (!empty($row['notes'])) {
		$details[] = "Note: " . $row['notes'] . "<br>";
	}
	$details[] = "</td>";

	//billing info
	$details[] = "<td valign='top' style='font-size: 12px;'>";
	$details[] = "<b>Billing</b><br>";
	$details[] = $billingRow['full_name'] . "<br>";
	$details[] = $billingRow['address_1'] . "<br>";
	$details[] = $billingRow['city'] . ", " . $billingRow['state'] . " " . $billingRow['zip_code'] . "<br>";
	if ($billingRow['country_code'] == "CAN") {
		$details[] = "CANADA<br>";
	}
	// get credit card payment applied to this order
	$orderSet = executeQuery("select * from order_payments where (payment_method_id is null or payment_method_id = 1) and order_id = ?",$orderId);
	if ($orderRow = getNextRow($orderSet)) {
		$details[] = "<br>Credit Card: " . $orderRow['reference_number'] . ($orderRow['amount'] < $orderTotal ? "&nbsp;&nbsp;$".$orderRow['amount'] : "");
	}
	// get total of gift cards applied to this order
	$orderSet = executeQuery("select * from order_payments where payment_method_id = 2 and order_id = ?",$orderId);
	if ($orderRow = getNextRow($orderSet)) {
		$details[] = "<br>Gift Card Number: " . $orderRow['reference_number'] . ($orderRow['amount'] < $orderTotal ? "&nbsp;&nbsp;$".$orderRow['amount'] : "");
	}
	$details[] = "</td>";

	//shipping info
	$details[] = "<td valign='top' width='33%' style='font-size: 12px;'>";
	$details[] = "<b>Shipping</b><br>";
	if ($row['shipping_method_id'] == 1) {
		$details[] = "Hold for pickup at our ";
		if (empty($row['location_id'])) {
			$details[] = $dealerArray['dealer_city'] . ", " . $dealerArray['dealer_state'];
		} else {
			$resultSet = executeQuery("select * from dealers where dealer_id = ?",$row['location_id']);
			$locationRow = getNextRow($resultSet);
			$details[] = getFieldFromId('company_name','contacts','contact_id',$locationRow['contact_id']);
		}
		$details[] = " store. (We will contact you when your order is available for in-store pickup.)";
	} else {
		if (!empty($row['shipping_address_id'])) {
			$resultSet = executeQuery("select * from addresses where address_id = ?",$row['shipping_address_id']);
			$shippingRow = getNextRow($resultSet);
			$details[] = $shippingRow['full_name'] . "<br>";
			$details[] = $shippingRow['address_1'] . "<br>";
			$details[] = $shippingRow['city'] . ", " . $shippingRow['state'] . " " . $shippingRow['zip_code'] . "<br>";
			if ($shippingRow['country_code'] == "CAN") {
				$details[] = "CANADA<br>";
			}
			// check for po box in shipping address
			if (stripos(str_replace(" ","",$shippingRow['address_1']),'pobox') !== false) {
				$alertShippingAddress = true;
			}
		}
		if (!empty($row['ffl_address_id'])) {
			$details[] = "<br>Firearms will be shipped to dealer shown below.";
		}
		
	}
	$details[] = "</td>";
	$details[] = "</tr>";
	$details[] = "</table>";
	$details[] = "</td>";
	$details[] = "</tr>";

	if (!empty($row['ffl_address_id'])) {
		$resultSet = executeQuery("select * from addresses where address_id = ?",$row['ffl_address_id']);
		$fflRow = getNextRow($resultSet);
		$details[] = "<tr>";
		$details[] = "<td><div style='background-color: #e1e1e1; border-top: 1px solid #999; margin: 12px 0px 12px 0px; padding: 6px;'>";
		$details[] = "<h2 style='font-size: 18px; margin: 0px;'>FFL Dealer Information</h2></div></td>";
		$details[] = "</tr>";
		$details[] = "<tr>";
		$details[] = "<td style='font-size: 12px; padding: 0px 0px 0px 6px;'>";
		$details[] = "<table cellpadding='0' cellspacing='0' width='100%' style='font-size: 12px;'><tr>";
		$details[] = "<td width='50%' valign='top'>";
		$details[] = $fflRow['full_name'] . "<br>";
		$details[] = $fflRow['address_1'] . "<br>";
		$details[] = $fflRow['city'] . ", " . $fflRow['state'] . " " . $fflRow['zip_code'] . "<br>";
		if ($fflRow['country_code'] == "CAN") {
			$details[] = "CANADA<br>";
		}
		$details[] = $fflRow['phone_number'];
		$details[] = "</td>";
		$details[] = "<td valign='top'>";
		$details[] = "Firearms in this order will be shipped <br>to the FFL dealer shown.<br>";
		$details[] = "</td>";
		$details[] = "</tr></table>";
		$details[] = "</td>";
		$details[] = "</tr>";
		// check for po box in shipping address
		if (stripos(str_replace(" ","",$fflRow['address_1']),'pobox') !== false) {
			$alertShippingAddress = true;
		}
	}

	$details[] = "<tr>";
	$details[] = "<td><div style='background-color: #e1e1e1; border-top: 1px solid #999; margin: 12px 0px 12px 0px; padding: 6px;'>";
	$details[] = "<h2 style='font-size: 18px; margin: 0px;'>Item Details</h2></div></td>";
	$details[] = "</tr>";
	$details[] = "<tr>";
	$details[] = "<td>";
	$details[] = "<table cellpadding='0' cellspacing='0' width='100%' style='font-size: 12px;'>";
	$details[] = "<tr>";
	$details[] = "<td style='border-bottom: 1px solid #ccc;'><b>Product ID</b></td>";
	$details[] = "<td style='border-bottom: 1px solid #ccc;'><b>Item</b></td>";
	$details[] = "<td style='border-bottom: 1px solid #ccc;' align='center'><b>Qty</b></td>";
	$details[] = "<td style='border-bottom: 1px solid #ccc;' align='right'><b>Price</b></td>";
	$details[] = "</tr>";

	
	$itemDetailsCustomer = array();
	$subtotal = 0;
	$resultSet = executeQuery("select * from order_items where order_id = ?",$orderId);
	while ($itemRow = getNextRow($resultSet)) {
		$productArray = getProductInfo($itemRow['product_id'],$itemRow['dealer_id']);
		
		$itemDetailsCustomer[] = "<tr>";
		
		
		$itemDetailsCustomer[] = "<td style='border-bottom: 1px solid #ccc;'>" . (empty($productArray['upc'])?$itemRow['product_id']:$productArray['upc']) . "</td>";
		
		
		$itemDetailsCustomer[] = "<td style='border-bottom: 1px solid #ccc;'>" . $productArray['description'] . ($productArray['low_quantity']?" <br>&nbsp;<span style='font-size: 11px; font-style: italic'>This item may be subject to backorder.</span>":"") . "</td>";
		
		
		$itemDetailsCustomer[] = "<td style='border-bottom: 1px solid #ccc; text-align: center;'>" . $itemRow['quantity'] . "</td>";
		
		
		$itemDetailsCustomer[] = "<td style='border-bottom: 1px solid #ccc; text-align: right;'>$" . number_format($itemRow['quantity']*$itemRow['order_price'],2) . "</td>";

		
		$itemDetailsCustomer[] = "</tr>";
		
		
		
		
		if (array_key_exists($productArray['department_id'], $alertDepartments) && $itemRow['quantity'] * $itemRow['order_price'] >= $alertDepartments[$productArray['department_id']] ) {
			$alertSubtotal += $itemRow['quantity'] * $itemRow['order_price'];
		}
		
		$subtotal += $itemRow['quantity'] * $itemRow['order_price'];
		
	}
	
	$detailsFooter = array();
	
	$detailsFooter[] = "<tr>";
	$detailsFooter[] = "<td colspan='3' align='right'><b>Subtotal:</b></td>";
	$detailsFooter[] = "<td align='right'><b>$" . number_format($subtotal,2) . "</b></td>";
	$detailsFooter[] = "</tr>";
	$detailsFooter[] = "<tr>";
	$detailsFooter[] = "<td colspan='3' align='right'><b>Shipping:</b></td>";
	$detailsFooter[] = "<td align='right'><b>$" . number_format($row['shipping_charge'],2) . "</b></td>";
	$detailsFooter[] = "</tr>";
	if ($row['insurance_charge'] > 0) {
		$detailsFooter[] = "<tr>";
		$detailsFooter[] = "<td colspan='3' align='right'><b>Insurance:</b></td>";
		$detailsFooter[] = "<td align='right'><b>$" . number_format($row['insurance_charge'],2) . "</b></td>";
		$detailsFooter[] = "</tr>";
	}
	$detailsFooter[] = "<tr>";
	$detailsFooter[] = "<td colspan='3' align='right'><b>Tax:</b></td>";
	$detailsFooter[] = "<td align='right'><b>$" . number_format($row['tax_charge'],2) . "</b></td>";
	$detailsFooter[] = "</tr>";

	$chargeTotal = 0;
	$chargeDescription = "";
	$resultSet = executeQuery("select * from order_charges where order_id = ?",$orderId);
	while ($chargeRow = getNextRow($resultSet)) {
		if (!empty($chargeDescription)) {
			$chargeDescription .= ", ";
		}
		$chargeDescription .= $chargeRow['description'];
		$chargeTotal += $chargeRow['amount'];
	}
	if ($chargeTotal > 0) {
		$detailsFooter[] = "<tr>";
		$detailsFooter[] = "<td colspan='3' align='right'><b>* Charges:</b></td>";
		$detailsFooter[] = "<td align='right'><b>$" . number_format($chargeTotal,2) . "</b></td>";
		$detailsFooter[] = "</tr>";
	}
	
	$promoChargeTotal = 0;
	$promoChargeDescription = "";
	$resultSet = executeQuery("select * from order_promo_charges where order_id = ?",$orderId);
	while ($pChargeRow = getNextRow($resultSet)) {
		if (!empty($promoChargeDescription)) {
			$promoChargeDescription .= ", ";
		}
		$promoChargeDescription .= $pChargeRow['description'];
		$promoChargeTotal += $pChargeRow['amount'];
	}
	if ($promoChargeTotal > 0) {
		$detailsFooter[] = "<tr>";
		$detailsFooter[] = "<td colspan='3' align='right'><b>Promo Discounts ($promoChargeDescription) :</b></td>"; 
		$detailsFooter[] = "<td align='right'><b>$" . number_format($promoChargeTotal,2) . "</b></td>";
		$detailsFooter[] = "</tr>";
	}
	
	$detailsFooter[] = "<tr>";
	$detailsFooter[] = "<td colspan='3' align='right'><b>Total Amount:</b></td>";
	$detailsFooter[] = "<td align='right'><b>$" . number_format($orderTotal,2) . "</b></td>";
	$detailsFooter[] = "</tr>";
	$detailsFooter[] = "</table>";
	$detailsFooter[] = "</td>";
	$detailsFooter[] = "</tr>";
	$detailsFooter[] = "</table>";

	if (!empty($chargeDescription)) {
		$detailsFooter[] =  "<div style='text-align: center; font-size: 12px; color: #333;'>* " . $chargeDescription . "</div>";
	}
	
	
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
	<title><?php echo ($metaTagArray['istitleinchild']== '1'? $metaTagArray['title']:$metaTagArray['title']); 
	if (empty($metaTagArray['title'])){ echo $dealerArray['dealer_name'];}?></title>
        <meta name="description" content="<?php echo($metaTagArray['isdescriptioninchild']== '1' ? $productArray['description'] . " | " . $metaTagArray['description']:$metaTagArray['description']);
	if (empty($metaTagArray['description'])){ echo $productArray['description'] . " | " . $dealerArray['site_description'];}?>">
	<!-- POBF - 108, Meta tag update -->
	<link rel="stylesheet" href="templates/default/universal-styles-v5.css">
	<link rel="stylesheet" href="<?php echo $templateArray['path'] ?>/styles-v1.css">
	<link rel="stylesheet" href="<?php echo $templateArray['checkout_styles'] ?>/checkout.css">
	<link rel="stylesheet" href="scpt/custom-theme/jquery-ui.css">
	<script src="scpt/jquery.js"></script>
	<script src="scpt/jquery-ui.js"></script>
	<script src="scpt/shared_v3.js"></script>
	<script src="scpt/jqprint.js"></script>
	<!--[if lt IE 9]>
	<script src="scpt/modernizr-2.0.6.js"></script>
	<![endif]-->
	<?php include_once "scpt/google_code.inc"; ?>
	<script>
		function printPage() {
			$("#checkout_info").jqprint({ importCSS: true });
		}
	</script>
</head>
<body>
	<?php trackLog("---------------- CHECKOUT COMPLETE DISPLAY : START -----------------"); ?>
    <?php if ($dealerArray['enable_global_login'] == 1 && isset($_SESSION['global_user_session_data'])) {
                $globalUsername = getFieldFromId('session_data', 'sessions', 'session_id', $_SESSION["global_user_session_data"]);
                ?>
                <div id="welcomenote">
                    <span style="vertical-align: middle; margin-right: 10px;color:#fff">Welcome, <?php echo $globalUsername; ?></span>                                       
                    <span style="color: #fff;">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
                    <a id="gLogout" href="http://<?php echo $dealerArray['dealer_url'] ?>/globallogout.php?zy=<?php echo $_SESSION['global_user_session_data'];?>" style="color:#fff;vertical-align: bottom; margin-right: 10px;">Logout</a>
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
			<div class="checkout_content">
                            <h1 class="default">Checkout Complete</h1>
				<div id="print_button_div"><button class='button' id='print_button' onclick='printPage()'>PRINT THIS PAGE</button></div>
				<div id="checkout_info" class="section_pane">

					<?php
					$resultSet = executeQuery("select * from orders where order_id = ?",$orderId);
						
					if ($row = getNextRow($resultSet)) {
						$orderTotal = getFieldFromId('amount','order_payments','order_id',$orderId);
						echo "<h2 class='center'>Thank You for Your Order!</h2>";
						echo "<p>Your transaction has been approved and we are sending a confirmation email to <b>";
						$EmailOfTheBuyer = getFieldFromId('email_address','contacts','contact_id',$row['contact_id']);
						echo $EmailOfTheBuyer;
						echo "</b></p>";
						echo "<p>We recommend that you also print or save this page for your records.</p>";
						
						foreach($details as $detailLine) {
							echo $detailLine;
						}
						foreach($itemDetailsCustomer as $detailLine) {
							echo $detailLine;
						}
						foreach($detailsFooter as $detailLine) {
							echo $detailLine;
						}
						
					}
					?>
				
					<br>
					<h2 class='center'>Questions about this order?</h2>
					<table><tr>
						<td width="300" align="right">
							<b>Phone:</b>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><?php echo $dealerArray['phone_number'] ?></td> 
						
						</td>
					</tr><tr>
						<td align="right">
							<b>Email:</b>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><?php echo "<a href='mailto:".$dealerArray['dealer_email']."'>" . $dealerArray['dealer_email'] . "</a>" ?></td> 
						</td>
					</tr><tr>
						<td valign="top" align="right"><b>Address:</b>&nbsp;&nbsp;&nbsp;&nbsp;</td>
						<td>
							<?php
								echo $dealerArray['dealer_name'] . "<br>";
								echo $dealerArray['dealer_address'] . "<br>";
								echo $dealerArray['dealer_city'] . ", " . $dealerArray['dealer_state'] . " " . $dealerArray['dealer_zip_code'] . "<br><br>";
							?>
						</td>
					</tr></table>

				</div> <!-- section_pane -->				
				
			</div> <!-- content -->
			
		</td>
	</tr></table>
<?php include_once $templateArray['footer'] . "/footer.inc"; ?>
<?php echo $dealerArray['extra_code'] ?>
<script type="text/javascript">
var EmailOfTheBuyer ="<?php echo $EmailOfTheBuyer ?>";
var orderAmountWithoutDollarSign ="<?php echo $orderTotal ?>";
var orderId ="<?php echo $orderId ?>";
</script>
<script src="https://s3.amazonaws.com/nfdsocialnetwork.nextbee.com/js/dealer_order_tracker.js" type="text/javascript"></script>
<?php include_once "scpt/dealer_tracking_code.inc";?> 
</body>
<?php trackLog("---------------- CHECKOUT COMPLETE DISPLAY : END -----------------"); ?>
</html>

