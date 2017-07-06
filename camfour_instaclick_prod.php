<?php

################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################
echo "here";
$gProgramCode = "IMPORT";
include_once "scpt/utilities.cron.inc"; 
                                
$orderId = 157647;

$dealerId = getFieldFromId('dealer_id','orders','order_id',$orderId);
echo $dealerId."\n";
$dealerLocationId = getFieldFromId('location_id','orders','order_id',$orderId);

$returnArray = array();

if (empty($orderId) || empty($dealerId)) {
	$returnArray['status'] = "error";
	$returnArray['message'] = "Order reference missing";
	echo json_encode($returnArray);
	exit;
}

// set the list of auto-order enabled distributors enabled for this dealer
$dealerDistributors = array();
$query  = "select * from dealer_distributors left join distributors using (distributor_id) where dealer_id = ? ";
$query .= "and auto_order = 1 and dealer_distributors.inactive = 0 order by priority";
// if this is a dealer location, see if there are any dealer distributors
$dealerLocationDistributorCount = 0;
if (!empty($dealerLocationId)) {
	$resultSet = executeQuery($query,$dealerLocationId);
	$dealerLocationDistributorCount = $resultSet['row_count'];
	$dealerIdForProcessing = $dealerLocationId;
}

if ($dealerLocationDistributorCount == 0) {
	$dealerIdForProcessing = $dealerId;
	$resultSet = executeQuery($query,$dealerId);
}

while ($row = getNextRow($resultSet)) {
	$dealerDistributors[$row['distributor_id']] = $row;
}

// process each item into an array by distributors for which automatic ordering is enabled 
// and which have inventory greater than the number being ordered
$firearmsInOrder = 0;
$productsByDistributor = array();
$resultSet = executeQuery("select * from order_items where order_id = ?",$orderId);
while ($row = getNextRow($resultSet)) {
	$orderItemId = $row['order_item_id'];
	$productId = $row['product_id'];
	$quantity = $row['quantity'];
	foreach ($dealerDistributors as $distributorArray) {
		// does this distributor have enough in stock to fulfill this order?
		$query  = "select distributor_id,product_code,product_id,dealer_cost,quantity,allocated from distributor_inventory ";
		$query .= "where product_id = ? and distributor_id = ? and quantity >= ?";
                echo $productId."\n";
		$productSet = executeQuery($query,$productId,$distributorArray['distributor_id'],$quantity);
		if ($productRow = getNextRow($productSet)) {
		#To create order details for BRS 
                if($distributorArray['distributor_id'] == 1)
		{
                    $productRow['product_name'] = getFieldFromId('description','products','product_id',$productId); 
                    $productRow['dealer_cost']  = getFieldFromId('dealer_cost','distributor_inventory','product_id',$productId,'distributor_id = ' . $distributorArray['distributor_id']);
                    $productRow['firearm'] = getFieldFromId('is_firearm','products','product_id',$productId); 
                    $fflId = null;
                    $shipping_address_id = null;
                    $fflId = getFieldFromId('ffl_address_id','orders','order_id',$orderId);          
                    $shipping_address_id = getFieldFromId('shipping_address_id','orders','order_id',$orderId);                    
                if(!empty($fflId) || !empty($shipping_address_id))
                    {
                        if(!empty($fflId) && $productRow['firearm'] ==1){
                        $brsSet = executeQuery("select * from addresses where address_id = ? ",$fflId);
                        if($row = getNextRow($brsSet))
                        {
                            $dealerArray = getDealerInfo($dealerId);
                            $productRow['first_name'] = $dealerArray['dealer_name'];
                            $productRow['address1'] = $dealerArray['dealer_address'];                           
                            $productRow['city'] = $dealerArray['dealer_city'];
                            $productRow['state'] = $dealerArray['dealer_state'];
                            $productRow['postal_code'] = $dealerArray['dealer_zip_code'];
                            $productRow['phone'] = $dealerArray['phone_number'];
                            $productRow['email'] = $dealerArray['dealer_email'];
                            $productRow['dealer_drop_ship'] = "Yes";  
                            
                        }
                       }
                        if(!empty($shipping_address_id) && $productRow['firearm'] ==0)
                        {
                        $brsSet1 = executeQuery("select * from addresses where address_id = ? ",$shipping_address_id);
                        if($row = getNextRow($brsSet1))
                            {
                                $productRow['first_name'] = $row['full_name'];
                                $productRow['last_name'] = $row['address_label'];
                                $productRow['address1'] = $row['address_1'];
                                $productRow['address2'] = $row['address_2'];
                                $productRow['city'] = $row['city'];
                                $productRow['state'] = $row['state'];
                                $productRow['postal_code'] = $row['zip_code'];
                                $productRow['country'] = $row['country_code'];
                                $productRow['phone'] = $row['phone_number'];            
                                $productRow['email'] = $row['email_address'];
                                $productRow['dealer_drop_ship'] = "No";  
                             }                        
                        }
                        
                    }
                    else
                    { 
                        $contactId = getFieldFromId('contact_id','dealers','dealer_id',$dealerId);
                        $brsSet = executeQuery("select * from contacts where contact_id = ?",$contactId);
                        if($row = getNextRow($brsSet))
                        {
                            $productRow['first_name'] = $row['first_name'];
                            $productRow['last_name'] = $row['last_name'];
                            $productRow['address1'] = $row['address_1'];
                            $productRow['address2'] = $row['address_2'];
                            $productRow['company'] = $row['company_name'];
                            $productRow['city'] = $row['city'];
                            $productRow['state'] = $row['state'];
                            $productRow['postal_code'] = $row['zip_code'];
                            $productRow['country'] = $row['country_code'];
                            $productRow['phone'] = $row['phone_number']; 
                            $productRow['email'] = $row['email_address'];
                            $productRow['dealer_drop_ship'] = "Yes";  
                        
                        }
                    }
                    
                }
                                
			$productRow['order_item_id'] = $orderItemId;
			$productRow['quantity_ordered'] = $quantity;
			
			// is this allocated stock?  is it RSR?
			if ($distributorArray['distributor_id'] == 2 && $productRow['allocated'] > 0) {
				$productRow['product_code'] .= "-NFDN";
			}
			
			// need to find the type of item for various shipping requirements
			$categoryId = getFieldFromId('category_id','products','product_id',$productId);
			$departmentId = getFieldFromId('department_id','categories','category_id',$categoryId);
			switch($departmentId) {
				case 1: // Rifles
				case 3: // Shotguns
					$productRow['shipping_group'] = 'firearms';
					$firearmsInOrder++;
					break;
				case 2: // Handguns
					$productRow['shipping_group'] = 'handguns';
					$firearmsInOrder++;
					break;
				case 9: // Ammunition
					$productRow['shipping_group'] = 'ammo';
					break;
				default:
					$productRow['shipping_group'] = 'items';
					break;
			}
			$productsByDistributor[$distributorArray['distributor_id']][] = $productRow;
			break; // this distributor has it, so don't check any more for this item
			
		}
	}
}

$shippingMethod = getFieldFromId('shipping_method_id','orders','order_id',$orderId);
if ($firearmsInOrder > 0 || $shippingMethod == 1) {
	// firearms in order, or order set to hold, so it must ship to the dealer
	$dropShipEligible = 0;
} else {
	// check the shipping address country_code
	$addressId = getFieldFromId('shipping_address_id','orders','order_id',$orderId);
	$countryCode = getFieldFromId('country_code','addresses','address_id',$addressId);
	if ($countryCode == "CAN") {
		// export to Canada, so must ship to dealer
		$dropShipEligible = 0;
	} else {
		$dropShipEligible = 1;
	}
}

include_once("distributor_order_functions.inc");

// now loop through $productsByDistributor and process accordingly
foreach($productsByDistributor as $distributorId => $inventorySet) {
    echo "entered\n";

	$dropShipEnabled = ($dealerDistributors[$distributorId]['drop_ship'] == 1 && $dropShipEligible == 1 ? 1 : 0);
	$orderedItems = array();
        echo $distributorId."\n";
	switch($distributorId) {
		case 1: // BigRock
                        if(!empty($dealerDistributors[$distributorId]['customer_token'])){
                        //$orderedItems=executeBigRockSportsOrder($orderId,$inventorySet,$dropShipEnabled,$dealerIdForProcessing,$dealerArray['development_access']);
                            
                        }
			break;
		case 2: // RSR
			//$orderedItems = executeRSROrder($orderId,$inventorySet,$dropShipEnabled,$dealerIdForProcessing,$dealerArray['development_access']);
			break;
		case 4: // Camfour
			$orderedItems = executeCamfourOrder1($orderId,$distributorId,$inventorySet,$dropShipEnabled,$dealerIdForProcessing,$dealerArray['development_access']);
			break;
		case 5: // Ellett
		case 7: // Ellett
			//$orderedItems = executeEllettOrder($orderId,$distributorId,$inventorySet,$dropShipEnabled,$dealerIdForProcessing,$dealerArray['development_access']);
			break;
		case 6: // Sports South
			//$orderedItems = executeSportsSouthOrder($orderId,$inventorySet,$dropShipEnabled,$dealerIdForProcessing,$dealerArray['development_access']);
			break;
                case 8: // Bill Hicks & Co
                        //$orderedItems  = executeBHCOrder($orderId,$inventorySet,$dropShipEnabled,$dealerIdForProcessing,$dealerArray['development_access']);
                        break;
	}
        print_r($orderedItems);
	// update the order_item record(s) with status codes
	foreach ($orderedItems as $orderItemId => $itemSet) {
		$orderStatusId = getFieldFromId('order_status_id','order_status','order_status_code',$itemSet['status_code']);
		$resultSet = executeQuery("update order_items set order_status_id = ?, fulfilled_price = ?, fulfilled_distributor_id = ?, fulfilled_distributor_reference = ? where order_item_id = ?",$orderStatusId,$itemSet['fulfilled_price'],$distributorId,$itemSet['reference'],$orderItemId);
	}
	
}




function executeCamfourOrder1($orderId, $distributorId, $inventorySet, $dropShipEnabled, $dealerId, $debug = 0, $keepFiles = 0) {
  
   $filePath = "/var/www/camfour/";
    $processedItems = array();
    $statusCodeArray = array(); // will store internal error codes along the way
    $orderStatusCode = ($dropShipEnabled == 1 ? "SUBMITTED_DS" : "SUBMITTED"); // will be changed to ERROR if there are any problems
    $resultSet = executeQuery("select * from dealer_distributors where dealer_id = ? and distributor_id = ? and inactive = 0", $dealerId, $distributorId);
    print_r($resultSet); 
    if ($row = getNextRow($resultSet)) {
        $dealerArray = getDealerInfo($dealerId);
        $accountNumber = $row['ic_account_number'];
        $accountPassword = $row['ic_password'];
        $ftpUsername = "clients";
        $ftpPassword = "slipStems14"; 
        $ECustToken = $row['customer_token'];
        $EAddrCode = $row['ship_to_id'];
        $EName = str_replace("&", " and ", $dealerArray['dealer_name']);
        $EPONo = $orderId . "-" . $distributorId . "-" . $dealerId;

        // Ground, FIRST DAY AIR, SECOND DAY AIR
        $shipFirearms = 0;
        $shipHandguns = 0;
        $shipAmmo = 0;
        $shipItems = 0;
        foreach ($inventorySet as $productSet) {
            switch ($productSet['shipping_group']) {
                case "firearms":
                    $shipFirearms++;
                    break;
                case "handguns":
                    $shipHandguns++;
                    break;
                case "ammo":
                    $shipAmmo++;
                    break;
                case "items":
                default:
                    $shipItems++;
                    break;
            }
        }
        if ($shipHandguns > 0) {
            $shippingCarrier = "U2";
        } else {
            $shippingCarrier = "U1";
        }
        $contactId = getFieldFromId('contact_id','dealers','dealer_id',$dealerId);
        $resultSet = executeQuery("select * from contacts where contact_id = ?", $contactId);
        $row = getNextRow($resultSet);
        
        $companyName = $row['company_name'];
        $address1 = $row['address_1'];
        $address2 = $row['address_2'];
        $city = $row['city'];
        $state = $row['state'];
        $postalCode = $row['zip_code'];
        $country = $row['country_code'];


        $fileName = "nfdn-" . $EPONo . ".xml";
	$EPOString = "NFDN-".$EPONo;
        $outputBuffer = "<?xml version='1.0'?>\n";
        $outputBuffer .= "<NFDN_XML MessageType='PO'>\n";        
        $outputBuffer .= "<PO>\n";
        $outputBuffer .= "<HeaderInfo>\n";
        $outputBuffer .= "\t<EPONo>$EPOString</EPONo>\n";
        $outputBuffer .= "\t<EName>$EName</EName>\n";
        $outputBuffer .= "\t<ECustNo>$ECustToken</ECustNo>\n";         
        $outputBuffer .= "\t<ESourceCode>NFDN</ESourceCode>\n";
        $outputBuffer .= "</HeaderInfo>\n";
        $outputBuffer .= "<ShipToInfo>\n";
        $contactId = getFieldFromId('contact_id', 'dealers', 'dealer_id', $dealerId);
        $resultSet = executeQuery("select * from contacts where contact_id = ?", $contactId);
        $row = getNextRow($resultSet);
        
        $outputBuffer .= "\t<ShipToName>".str_replace('&','&amp;',$companyName)."</ShipToName>\n";
        $outputBuffer .= "\t<ShipToAddress1>$address1</ShipToAddress1>\n";
        $outputBuffer .= "\t<ShipToAddress2>$address2</ShipToAddress2>\n";
        $outputBuffer .= "\t<ShipToCity>$city</ShipToCity>\n";
        $outputBuffer .= "\t<ShipToState>$state</ShipToState>\n";
        $outputBuffer .= "\t<ShipToZip>$postalCode</ShipToZip>\n";
        $outputBuffer .= "\t<ShipToCountry>$country</ShipToCountry>\n";
        $outputBuffer .= "</ShipToInfo>\n";
        $outputBuffer .= "<TrackingInfo>\n";
        $outputBuffer .= "\t<Carrier>". $shippingCarrier."</Carrier>\n";        
        $outputBuffer .= "\t<ShippingMethod></ShippingMethod>\n";
        $outputBuffer .= "</TrackingInfo>\n";
        $outputBuffer .= "<ItemInfo>\n";

        // add items to order
        $itemsOrdered = 0;
        foreach ($inventorySet as $productSet) {
            $outputBuffer .= "\t<ItemDetail><FCItemNum>" . $productSet['product_code'] . "</FCItemNum>";
            $outputBuffer .= "<EItemNum>" . $productSet['product_code'] . "</EItemNum>";
            $outputBuffer .= "<EQtyOrdered>" . $productSet['quantity_ordered'] . "</EQtyOrdered></ItemDetail>\n";
            $itemsOrdered += $productSet['quantity_ordered'];
            $dealerCost = getFieldFromId('dealer_cost', 'distributor_inventory', 'product_code', $productSet['product_code'], 'distributor_id = ' . $distributorId);
            $processedItems[$productSet['order_item_id']]['product_id'] = $productSet['product_id'];
            $processedItems[$productSet['order_item_id']]['quantity_ordered'] = $productSet['quantity_ordered'];
            $processedItems[$productSet['order_item_id']]['fulfilled_price'] = $dealerCost;
        }
        $outputBuffer .= "</ItemInfo>\n";
        $outputBuffer .= "</PO>\n";
        $outputBuffer .= "</NFDN_XML>";
		
        // save file to events table
        $query = "insert into order_fulfillment_events ";
        $query .= "(order_fulfillment_event_time,order_fulfillment_event_type_id,distributor_id,dealer_id,order_id,content) ";
        $query .= "values (now(),1,?,?,?,?)";
        $resultSet = executeQuery($query, $distributorId, $dealerId, $orderId, $fileName . "\n" . $outputBuffer);
		
        // write the outputBuffer to a file for upload to Ellett    
        echo $filePath . $fileName."\n";
        $fp = fopen($filePath . $fileName, 'w') or die("Unable to open file!");        
        fwrite($fp, $outputBuffer);
        fclose($fp);
        
        
        if ($debug == 0) {
           
            $connectionId = ftp_connect("ezgun.net");
            echo $connectionId;
             echo $filePath.$fileName."\n";
            if (ftp_login($connectionId, $ftpUsername, $ftpPassword)) {
                echo "logged in\n";
                ftp_pasv($connectionId, true);
                echo "here1\n";
                print_r(ftp_put($connectionId, "/" . $fileName, $filePath . $fileName, FTP_ASCII));
                echo "here\n";
                if (ftp_put($connectionId, "/" . $fileName, $filePath . $fileName, FTP_ASCII)) {
                    $statusCodeArray[] = "2";
                    // update events table to show file was uploaded
                    $query = "update order_fulfillment_events set order_fulfillment_event_type_id = 2 ";
                    $query .= "where distributor_id = ? and dealer_id = ? and order_id = ?";
                    $resultSet = executeQuery($query, $distributorId, $dealerId, $orderId);
                } else {
                    $statusCodeArray[] = "02";
                    $orderStatusCode = "ERROR";
                }
            }
            echo "hh\n";
            ftp_close($connectionId);
            if ($keepFiles == 0) {
               // unlink($filePath . $fileName);
            }
        } else {
            $statusCodeArray[] = "2";
        }
        
    }else {
        $statusCodeArray[] = "00";
        $orderStatusCode = "ERROR";
    }

    // add the status code to each item
    foreach ($inventorySet as $productSet) {
        $processedItems[$productSet['order_item_id']]['reference'] = implode("|", $statusCodeArray);
        $processedItems[$productSet['order_item_id']]['status_code'] = $orderStatusCode;
    }
    return $processedItems;
}



$returnArray['status'] = "ordered";
$returnArray['token'] = $orderId . chr( rand(97,122) ) . md5(uniqid(rand(),true)); // mask order_id

echo json_encode($returnArray);
exit;

?>

