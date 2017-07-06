<?php

################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################


$gProgramCode = "ADVERTISINGMAINTENANCE";
include_once "scpt/utilities.inc";

switch ($_POST['action']) {
	case "search_addresses":
		$returnArray = array();
		$searchFor = $_POST['search_text'];
		if (!empty($searchFor)) {
			$whichField = ($_POST['which'] == "agency" ? "agency_contact_id" : "contact_id");
			$query  = "select * from contacts ";
			$query .= "where contact_id in (select distinct $whichField from advertising where $whichField is not null) ";
			$query .= "and (company_name like ? or address_1 like ? or city like ? or state like ? or zip_code like ?) ";
			$query .= "group by address_1,city";
			$parameters = array();
			$parameters[] = "%" . $searchFor . "%";
			$parameters[] = "%" . $searchFor . "%";
			$parameters[] = "%" . $searchFor . "%";
			$parameters[] = "%" . $searchFor . "%";
			$parameters[] = "%" . $searchFor . "%";
			$resultSet = executeQuery($query,$parameters);
			$addressCount = $resultSet['row_count'];
			$addressArray = array();
			while ($row = getNextRow($resultSet)) {
				$addressArray[] = implode("|",$row);
			}
			$returnArray['addresses'] = $addressArray;
			$returnArray['status'] = "success";
			$returnArray['address_count'] = $addressCount;
			if ($addressCount == 1) {
				$returnArray['address'] = $addressArray[0];
			}
		} else {
			$returnArray['status'] = "error";
			$returnArray['message'] = "Serch text is empty";
		}
		echo json_encode($returnArray);
		exit;
		
	case "load_address":
		$returnArray = array();
		$addressId = $_POST['address_id'];
		if (!empty($addressId)) {
			$query  = "select * from contacts where contact_id = ?";
			$resultSet = executeQuery($query,$addressId);
			$row = getNextRow($resultSet);
			$returnArray['status'] = "success";
			$returnArray['address'] = implode("|",$row);
		} else {
			$returnArray['status'] = "error";
			$returnArray['message'] = "Address id missing";
		}
		echo json_encode($returnArray);
		exit;
}
$gItemsPerPage = 20;
$companyName = empty($_GET['company_name'])?"":$_GET['company_name'];
$dealerArray = getDealerInfo($gDealerId);
$data= Array();
$urlAction = $_GET['url_action'];
switch ($urlAction) {
	case "save":
		//ksort($_POST);
		//foreach ($_POST as $key => $value) { echo $key . " = " . $value . "<br>"; }
		//break;
		
		$nameArray = explode(" ",trim($_POST['full_name']));
		$lastName = $nameArray[count($nameArray)-1];
		$firstName = trim(str_replace($lastName,"",$_POST['full_name']));
		$parameters = array();
		$parameters[] = $firstName;
		$parameters[] = $lastName;
		$parameters[] = (empty($_POST['company_name']) ? $_POST['advertiser_name'] : $_POST['company_name']);
		$parameters[] = $_POST['address_1'];
		$parameters[] = $_POST['city'];
		$parameters[] = $_POST['state'];
		$parameters[] = $_POST['zip_code'];
		$parameters[] = $_POST['email_address'];
		$parameters[] = $_POST['phone_number'];
		$parameters[] = $_POST['fax_number'];			
		if (empty($_POST['contact_id'])) {
			$query  = "insert into contacts (";
			$query .= "first_name,last_name,company_name,address_1,city,state,zip_code,email_address,";
			$query .= "phone_number,fax_number,version";
			$query .= ") values (?,?,?,?,?,?,?,?,?,?,1)";
			$resultSet = executeQuery($query,$parameters);
			$contactId = $resultSet['insert_id'];
		} else {
			$contactId = $_POST['contact_id'];
			$query  = "update contacts set first_name = ?, last_name = ?, company_name = ?, address_1 = ?, ";
			$query .= "city = ?, state = ?, zip_code = ?, email_address = ?, phone_number = ?, fax_number = ?, ";
			$query .= "version = version + 1 where contact_id = ?";
			$parameters[] = $contactId;
			$resultSet = executeQuery($query,$parameters);
		}

		// if there's an agency contact, update the record
		$agencyContactId = null;
		if (!empty($_POST['agency_contact_id']) || !empty($_POST['agency_company_name'])) {
			$nameArray = explode(" ",trim($_POST['agency_full_name']));
			$lastName = $nameArray[count($nameArray)-1];
			$firstName = trim(str_replace($lastName,"",$_POST['agency_full_name']));
			$parameters = array();
			$parameters[] = $firstName;
			$parameters[] = $lastName;
			$parameters[] = $_POST['agency_company_name'];
			$parameters[] = $_POST['agency_address_1'];
			$parameters[] = $_POST['agency_city'];
			$parameters[] = $_POST['agency_state'];
			$parameters[] = $_POST['agency_zip_code'];
			$parameters[] = $_POST['agency_email_address'];
			$parameters[] = $_POST['agency_phone_number'];
			$parameters[] = $_POST['agency_fax_number'];			
			if (empty($_POST['agency_contact_id'])) {
				$query  = "insert into contacts (";
				$query .= "first_name,last_name,company_name,address_1,city,state,zip_code,email_address,";
				$query .= "phone_number,fax_number,version";
				$query .= ") values (?,?,?,?,?,?,?,?,?,?,1)";
				$resultSet = executeQuery($query,$parameters);
				$agencyContactId = $resultSet['insert_id'];
			} else {
				$agencyContactId = $_POST['agency_contact_id'];
				$query  = "update contacts set first_name = ?, last_name = ?, company_name = ?, address_1 = ?, ";
				$query .= "city = ?, state = ?, zip_code = ?, email_address = ?, phone_number = ?, fax_number = ?, ";
				$query .= "version = version + 1 where contact_id = ?";
				$parameters[] = $agencyContactId;
				$resultSet = executeQuery($query,$parameters);
			}
		}

		$parameters = array();
		$parameters[] = $_POST['description'];
		$parameters[] = $_POST['advertising_unit_id'];
		$parameters[] = $contactId;
		$parameters[] = $agencyContactId;
		$parameters[] = $_POST['rate'];
		$parameters[] = (empty($_POST['start_date']) ? null : date('Y-m-d',strtotime($_POST['start_date'])));
		$parameters[] = (empty($_POST['end_date']) ? null : date('Y-m-d',strtotime($_POST['end_date'])));
		$parameters[] = $_POST['amount_paid'];
		$parameters[] = (empty($_POST['date_paid']) ? null : date('Y-m-d',strtotime($_POST['date_paid'])));
		$parameters[] = $_POST['impressions_paid'];
		$parameters[] = $_POST['link_url'];
		$parameters[] = $_POST['takeover_link_url'];
        //ama-38: adding the field is_newwindow starts
        $parameters[] = ($_POST['is_newwindow'] == 'Y' ? 1 : 0);
        //ama-38: adding the field is_newwindow ends
		$parameters[] = ($_POST['internal_use_only'] == 'Y' ? 1 : 0);
        $parameters[] = ($_POST['inactive'] == 'Y' ? 1 : 0);
		$parameters[] = ($_POST['enable_contest_entry'] == 'Y' ? 1 : 0);
		if (!empty($_POST['primary_id'])) {
			$primaryId = $_POST['primary_id'];
			$query  = "update advertising set description = ?, advertising_unit_id = ?, contact_id = ?, agency_contact_id = ?,";
			$query .= "rate = ?, start_date = ?, end_date = ?, amount_paid = ?, date_paid = ?, impressions_paid = ?, ";
			$query .= "link_url = ?, takeover_link_url = ?,is_newwindow = ?, internal_use_only = ?,inactive = ?, ";
			$query .= "version = version + 1, enable_contest_entry =? where advertising_id = ?";
			$parameters[] = $primaryId;
			$resultSet = executeQuery($query,$parameters);
		} else {
			$query  = "insert into advertising (";
			$query .= "description,advertising_unit_id,contact_id,agency_contact_id,";
			$query .= "rate,start_date,end_date,amount_paid,date_paid,";
			$query .= "impressions_paid,link_url,takeover_link_url,is_newwindow,internal_use_only,inactive,version,enable_contest_entry";
			$query .= ") values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)";
			$resultSet = executeQuery($query,$parameters);
			$primaryId = $resultSet['insert_id'];
		}
                //ama-38: adding field is_newwindow ends
		if (!empty($primaryId)) {
			//process new image if one was uploaded
			if (!empty($_POST['new_image_file']) && file_exists('imagecache/' . $_POST['new_image_file'])) {
				$fileType = verifyImage($_POST['new_image_file']);
				if (is_numeric($fileType)) {
					$fileContent = file_get_contents('imagecache/' . $_POST['new_image_file']);
					$query = "insert into images (image_id,file_content,file_type,version) values (null,?,?,1)";
					$resultSet = executeQuery($query,$fileContent,$fileType);
					$imageId = $resultSet['insert_id'];
					if ($imageId > 0) {
						executeQuery("update advertising set image_id = ? where advertising_id = ?",$imageId,$primaryId);
					}
				}
				unlink('imagecache/' . $_POST['new_image_file']);
			}
	
			//process new takeover image if one was uploaded
			if (!empty($_POST['new_takeover_image_file']) && file_exists('imagecache/' . $_POST['new_takeover_image_file'])) {
				$fileType = verifyImage($_POST['new_takeover_image_file']);
				if (is_numeric($fileType)) {
					$fileContent = file_get_contents('imagecache/' . $_POST['new_takeover_image_file']);
					$query = "insert into images (image_id,file_content,file_type,version) values (null,?,?,1)";
					$resultSet = executeQuery($query,$fileContent,$fileType);
					$imageId = $resultSet['insert_id'];
					if ($imageId > 0) {
						executeQuery("update advertising set takeover_pair_id = ? where advertising_id = ?",$imageId,$primaryId);
					}
				}
				unlink('imagecache/' . $_POST['new_takeover_image_file']);
			}
		}

		break;
}

$nextLocation = $_POST['g_next_location'];
if (!empty($nextLocation)) {
	header("Location: " . $nextLocation);
	exit;
}

$dataOffset = $_POST['g_data_offset'];
if (empty($dataOffset) || !is_numeric($dataOffset)) {
	$dataOffset = 0;
}

// build a data list
$primaryId = (empty($primaryId) ? $_GET['primary_id'] : $primaryId);
$filterText = ( empty($_POST['filter_text_custom']) ? $_POST['g_filter_text'] : $_POST['filter_text_custom'] );
$startDate = ( empty($_POST['filter_start_date']) || $_POST['filter_start_date'] == "Date Start" ? $_POST['g_start_date'] : date('Y-m-d',strtotime($_POST['filter_start_date'])) );
$endDate = ( empty($_POST['filter_end_date']) || $_POST['filter_end_date'] == "Date End" ? $_POST['g_end_date'] : date('Y-m-d',strtotime($_POST['filter_end_date'])) );
$dataOffset = max(0,$_POST['g_data_offset']);
$nextRecordId = "";
$previousRecordId = "";

// run the query for this set, even if we're looking at a specific product
// because we need values for the next and previous buttons
$dataList = array();
$parameters = array();
$data_campign= array();
$previousRecordIdCampaign = "";
$nextRecordIdCampaign = "";
// removed where inactive = 0 - Joel         
$filterText=$_POST['filter_text'];   
$query = "select distinct(company_name) from advertising left join contacts using (contact_id) ";
if (!empty($filterText) && $filterText != "Search") {
	$query .= "where (description like ? or company_name like ?) ";
	$parameters[] = "%" . $filterText . "%";
	$parameters[] = "%" . $filterText . "%";
} else {
	$filterText = "";
}
if (!empty($startDate)) {
	$query .= "and start_date >= ? ";
	$parameters[] = $startDate;
}
if (!empty($endDate)) {
	$query .= "and end_date <= ? ";
	$parameters[] = $endDate;
}

$query .= "order by company_name,end_date desc";

$resultSet = executeQuery($query,$parameters);
$rowCount = $resultSet['row_count']; 
$setPreviousRecordId = true;
while ($row = getNextRow($resultSet)) {
	if ($row['advertising_id'] == $primaryId) {
		$setPreviousRecordId = false;
		$row = getNextRow($resultSet);
		$nextRecordId = $row['advertising_id'];
		break;
	}
	if ($setPreviousRecordId === true) {
		$previousRecordId = $row['advertising_id'];
	}
}
//code added by arun on 02/14/2014
$queryCampaignName = "select company_name,inactive from advertising left join contacts using (contact_id) ";
$queryCampaignName .= "where advertising_id =?";
$resultSetCampaignName = executeQuery($queryCampaignName,$primaryId);
if($rowCampaign = getNextRow($resultSetCampaignName)){
$companyNames=$rowCampaign['company_name'];
$Adincative =$rowCampaign['inactive'];
}
$queryCampaignDetail = "select * from advertising left join contacts using (contact_id) ";
$queryCampaignDetail .= "where company_name = ? and inactive = ?";
$resultSetCampaignDetail = executeQuery($queryCampaignDetail,$companyNames,$Adincative);
 #For hiding Next and Previous button if Ad is only one.
 if($resultSetCampaignDetail['row_count']== 1){
        echo $disable = false;
    }
 else {
       $disable = true; 
    }
$setPreviousRecordIdCampaign = true;
while ($rowCampaign = getNextRow($resultSetCampaignDetail)) {       
	if ($rowCampaign['advertising_id'] == $primaryId) {
		$setPreviousRecordIdCampaign = false;
		$rowCampaign = getNextRow($resultSetCampaignDetail);
		$nextRecordIdCampaign = $rowCampaign['advertising_id'];
		break;
	}
	if ($setPreviousRecordIdCampaign === true) {
		$previousRecordIdCampaign = $rowCampaign['advertising_id'];
	}
}
// but we only need to finish the query if we're looking at a list
if (empty($primaryId)) {
	// set data offset -- default is last page
	if (!is_numeric($_POST['g_data_offset'])) {
		$lastPage = ceil($rowCount / $gItemsPerPage );
		$dataOffset = max(0,($lastPage - 1) * $gItemsPerPage);
	} else {
		if ($_POST['g_data_offset'] < $rowCount) {
			$dataOffset = $_POST['g_data_offset'];
		} else {
			$dataOffset = 0;
		}
	}
	if (!empty($dataOffset) && is_numeric($dataOffset)) {
		$currentPage = ceil($dataOffset / $gItemsPerPage);
	}
	$limitStatement = " limit " . $dataOffset . "," . $gItemsPerPage;
	$resultSet = executeQuery($query . $limitStatement,$parameters);
	while ($row = getNextRow($resultSet)) {
		$dataList[] = $row;
	}
}

$urlPage = $_GET['url_page'];

switch ($urlPage) {
	case "new":
                    $primaryId = "";
                    $dataRow = array();
                    if ($showList == "store" && $userCanEditStoreProducts) {
                            $userCanEditThisProduct = true;
                    }
                    if ($showList != "store" && $userCanEditNFDNProducts) {
                            $userCanEditThisProduct = true;
                    }
                    $urlPage = "show";                   
                    
		break;
	case "show":
                       $resultSet = executeQuery("select * from advertising where advertising_id = ?",$primaryId);
                       $dataRow = getNextRow($resultSet);
                       if ($dataRow === false) {
                               $urlPage = "list";
                       } else {
                               // populate the rest of the dataRow array
                               $resultSet = executeQuery("select * from contacts where contact_id = ?",$dataRow['contact_id']);
                               if ($row = getNextRow($resultSet)) {
                                       $dataRow['full_name'] = trim($row['first_name'] . " " . $row['last_name']);
                                       $dataRow['company_name'] = $row['company_name'];
                                       $dataRow['address_1'] = $row['address_1'];
                                       $dataRow['city'] = $row['city'];
                                       $dataRow['state'] = $row['state'];
                                       $dataRow['zip_code'] = $row['zip_code'];
                                       $dataRow['phone_number'] = $row['phone_number'];
                                       $dataRow['fax_number'] = $row['fax_number'];
                                       $dataRow['email_address'] = $row['email_address'];
                               }
                               $resultSet = executeQuery("select * from contacts where contact_id = ?",$dataRow['agency_contact_id']);
                               if ($row = getNextRow($resultSet)) {
                                       $dataRow['agency_full_name'] = trim($row['first_name'] . " " . $row['last_name']);
                                       $dataRow['agency_company_name'] = $row['company_name'];
                                       $dataRow['agency_address_1'] = $row['address_1'];
                                       $dataRow['agency_city'] = $row['city'];
                                       $dataRow['agency_state'] = $row['state'];
                                       $dataRow['agency_zip_code'] = $row['zip_code'];
                                       $dataRow['agency_phone_number'] = $row['phone_number'];
                                       $dataRow['agency_fax_number'] = $row['fax_number'];
                                       $dataRow['agency_email_address'] = $row['email_address'];
                               }
                       }
                       $dataRow['amount_remaining'] = (($dataRow['impressions_paid'] - $dataRow['impressions_to_date']) / 1000) * $dataRow['rate'];
                    
                    
                break;
        
        default:	
            $urlPage = "list";           
		break;
}


function verifyImage($fileName) {
	$filePath = 'imagecache/' . $fileName;
	if ($imageInfo = getimagesize($filePath)) {
		switch($imageInfo[2]) {
			case 1: //IMAGETYPE_GIF
				$fileType = 1;
				break;
			case 2: //IMAGETYPE_JPEG
				$fileType = 2;
				break;
			case 3: //IMAGETYPE_PNG
				$fileType = 3;
				break;
			default:
				// 4	IMAGETYPE_SWF
				// 5	IMAGETYPE_PSD
				// 6	IMAGETYPE_BMP
				// 7	IMAGETYPE_TIFF_II (intel byte order)
				// 8	IMAGETYPE_TIFF_MM (motorola byte order)
				// 9	IMAGETYPE_JPC
				// 10	IMAGETYPE_JP2
				// 11	IMAGETYPE_JPX
				// 12	IMAGETYPE_JB2
				// 13	IMAGETYPE_SWC
				// 14	IMAGETYPE_IFF
				// 15	IMAGETYPE_WBMP
				// 16	IMAGETYPE_XBM
				// 17	IMAGETYPE_ICO
				$fileType = "not_supported";
				break;
		}
	} else {
		$fileType = "not_image";
	}
	return $fileType;
}
// Click Through Counts
$result =executequery("select count(advertising_id) as advertising_id from orders where advertising_id = ?",$primaryId);
 
$data = getNextRow($result);  
if(!empty($data['advertising_id'])){
$clickthroughcount=$data['advertising_id'];
}
else
{
    $clickthroughcount=0;
}
$result =executequery("select sum(order_price) as click_through_value from order_items where order_id in(select order_id from orders where advertising_id= ?)",$primaryId);
 
$data = getNextRow($result);  
if(!empty($data['click_through_value'])){
$clickthroughvalue=$data['click_through_value'];
}
else
{
    $clickthroughvalue=0;
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $dealerArray['dealer_name'] . " | " . $gProgramTitle; ?></title>
	<meta name="description" content="">
	<link rel="stylesheet" href="tmpl/styles_admin.css?v=1.1">
	<link type="text/css" href="scpt/custom-theme/jquery-ui.css" rel="stylesheet" />
	<style>
		.tab { margin: 21px 12px 12px 12px; font-size: 13px; line-height: 18px; min-height: 350px; }
		.left-pane { float: left; margin-right: 27px; }
		.image-sidebar { margin: 0 0 0 700px; border-left: 1px dotted #999; padding: 12px 0 0 21px; height: 330px; text-align: center; color: #999; }
		.button, .confirm, .cancel { font-size: 12px; }
		/*.stat-pane {  margin: 0 0 0 30px; border: 1px solid #ccc; padding: 12px; background-color: #f1f1f1; }*/
                .stat-pane { border: 1px solid #ccc; background-color: #f1f1f1; }
		.stat { font-size: 15px; color: #333; }
		.section-head { margin: 0 0 9px 0; font-size: 16px; color: #666; }
		.prompt { margin: 0 0 6px 0; font-style: italic; font-weight: bold; color: #777; }
		.image-pane { width: 400px; height: 180px; text-align: center; color: #999; }
		.no-image { padding: 60px 0 0 0; color: #999; }
		.big-image { max-height: 160px; max-width: 400px; }
		.primary-thumb { margin: 0 0 21px 0; }
		.thumbnail-image { max-height: 150px; max-width: 180px; }
		.border-top { margin: 18px 0 0 0; padding: 6px 0 0 0; border-top: 1px dotted #999; }
	</style>
	<script src="scpt/jquery.js"></script>
	<script src="scpt/jquery-ui.js"></script>
	<script src="scpt/admin_v2.js"></script>
	<script src="scpt/jqprint.js"></script>
	<!--[if lt IE 9]>
	<script src="scpt/modernizr-2.0.6.js"></script>
	<![endif]-->
</head>
<body>

<?php include_once "scpt/admin_header.inc"; ?>

<div id="main">

	<?php if (!empty($gProgramHelp)) { ?>
	<div id="help_div">
		<div class="heading">What's this<div id="help_div_button"><button id="help_button">?</button></div></div>
		<div id="help_div_content">
			<?php echo nl2br($gProgramHelp); ?>
		</div>
	</div>
	<?php } 
    if(isset($_GET['url_page'])){
            if($_GET['url_page']=='show'){ ?>
          <h1><?php echo $gProgramTitle."-Campaign Detail" ?></h1>  
    <?php } }
    
elseif(isset($_GET['company_name'])){
   
        ?>
    <h1><?php echo $gProgramTitle."-Campaigns" ?></h1>
<?php }else {?>
<h1><?php echo $gProgramTitle."-Accounts" ?></h1>
<?php } ?>
<form name="edit_form" id="edit_form" method="post" action=<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>>
<input type="hidden" name="g_previous_record_id" id="g_previous_record_id" value="<?php echo $previousRecordId ?>" />
<input type="hidden" name="g_next_record_id" id="g_next_record_id" value="<?php echo $nextRecordId ?>" />
<input type="hidden" name="g_previous_campaign_record_id" id="g_previous_campaign_record_id" value="<?php echo $previousRecordIdCampaign ?>" />
<input type="hidden" name="g_next_campaign_record_id" id="g_next_campaign_record_id" value="<?php echo $nextRecordIdCampaign ?>" />
<input type="hidden" name="g_next_location" id="g_next_location" value="" />
<input type="hidden" name="g_items_per_page" id="g_items_per_page" value="<?php echo $gItemsPerPage ?>" />
<input type="hidden" name="g_data_offset" id="g_data_offset" value="<?php echo $dataOffset ?>" />
<input type="hidden" name="g_filter_text" id="g_filter_text" value="<?php echo $filterText ?>" />
<input type="hidden" name="g_start_date" id="g_start_date" value="<?php echo $startDate ?>">
<input type="hidden" name="g_end_date" id="g_end_date" value="<?php echo $endDate ?>">
<input type="hidden" name="g_data_count" id="g_data_count" value="<?php echo $rowCount ?>">
<input type="hidden" name="g_php_self" id="g_php_self" value="<?php echo $_SERVER['PHP_SELF'] ?>" />
<input type="hidden" name="primary_id" id="primary_id" value="<?php echo $primaryId ?>"/>
<input type="hidden" name="company_name" id="company_active" value="<?php echo $_GET['company_name'] ?>"/>
<input type="hidden" name="dealer_id" id="dealer_id" value="<?php echo ($gAdministratorFlag?"":$gDealerId) ?>" />
<input type="hidden" name="contact_id" id="contact_id" value="<?php echo $dataRow['contact_id'] ?>" />
<input type="hidden" name="agency_contact_id" id="agency_contact_id" value="<?php echo $dataRow['agency_contact_id'] ?>" />
<input type="hidden" name="inactive" id="inactive" value="<?php echo $dataRow['inactive'] ?>" />
<input type="hidden" name="new_image_file" id="new_image_file" value="" />
<input type="hidden" name="new_takeover_image_file" id="new_takeover_image_file" value="" />
<input type="hidden" name="active_tab" id="active_tab" value="" />
<input type="hidden" name="version" id="version" value="<?php echo (empty($dataRow['version']) ? '1' : $dataRow['version']) ?>" />

<?php

switch ($urlPage) {
	case "new":
	case "show":
?>

<div style="font-size: 18px; color: #666; text-align: center; padding: 0 0 12px 0;">
	<?php echo (empty($primaryId) ? "New Advertisement" : $dataRow['company_name'] . " : " . $dataRow['description']); ?>
</div>

<div id="tabs" style="padding:1.0em"> 
	<ul>
		<li id="tab_button_1"><a href="#tab_1">Ad Specs</a></li>
		<li id="tab_button_2"><a href="#tab_2">Company Info</a></li>
		<li id="tab_button_3"><a href="#tab_3">Content</a></li>

                <li id="tab_button_4"><a href="#tab_4">Analytics</a></li>
               	</ul>
	<div id="tab_panes">

		<div class="tab" id="tab_1">
		
			<div class="left-pane">
				<table cellpadding="0" cellspacing="6">
					<tr>
						<td width="120" class="label">Advertiser</td>
						<td align="left">
                                                    <?php if(isset($_GET['company_name'])){?>
                                                     <input type="text" name="advertiser_name" id ="advertiserName" class="field info" style="width: 350px;" data-crc="<?php echo getCrc32($_GET['company_name']) ?>" value="<?php echo $_GET['company_name'] ?>">   
                                                  <?php  }else {?>
							<input type="text" name="advertiser_name" id ="advertiserName" class="field info" style="width: 350px;" data-crc="<?php echo getCrc32($dataRow['company_name']) ?>" value="<?php echo $dataRow['company_name'] ?>">
                                                  <?php } ?>
                                                </td>
					</tr>
					<tr>
						<td class="label">Advertising Unit</td>
						<td align="left">
							<select name="advertising_unit_id" id="advertising_unit_id">
								<option value="" data-rate="">Select...</option>
								<?php
									$resultSet = executeQuery("select * from advertising_units");
									while ($row = getNextRow($resultSet)) {
										echo "<option value='" . $row['advertising_unit_id'] . "' data-rate='" . $row['default_rate'] . "'" . ($dataRow['advertising_unit_id'] == $row['advertising_unit_id'] ? " selected" : "") . ">" . $row['description'] . "</option>";
									}
								?>
							</select>
							&nbsp;&nbsp;&nbsp;<span class="label">Rate $</span>
							<input type="text" name="rate" class="field info" style="width: 40px;" data-crc="<?php echo getCrc32($dataRow['rate']) ?>" value="<?php echo $dataRow['rate'] ?>">&nbsp;&nbsp;&nbsp;<span class="label">per 1000 impressions</span>
						</td>
					</tr>
					<tr>
						<td width="120" class="label">Description</td>
						<td align="left">
							<input type="text" name="description" class="field info" style="width: 350px;" data-crc="<?php echo getCrc32($dataRow['description']) ?>" value="<?php echo $dataRow['description'] ?>">
						</td>
					</tr>
				</table>
				<table cellpadding="0" cellspacing="0"><tr><td valign="top">
				<table cellpadding="0" cellspacing="6">
					<tr>
						<td width="120" class="label">Start Date</td>
						<td align="left">
							<input type="text" name="start_date" class="field info datepicker" style="width: 80px;" data-crc="<?php echo getCrc32((empty($dataRow['start_date']) ? "" : date('m/d/Y',strtotime($dataRow['start_date'])))) ?>" value="<?php echo (empty($dataRow['start_date']) ? "" : date('m/d/Y',strtotime($dataRow['start_date']))) ?>">
						</td>
					</tr>
					<tr>
						<td class="label">End Date</td>
						<td align="left">
							<input type="text" name="end_date" class="field info datepicker" style="width: 80px;" data-crc="<?php echo getCrc32((empty($dataRow['end_date']) ? "" : date('m/d/Y',strtotime($dataRow['end_date'])))) ?>" value="<?php echo (empty($dataRow['end_date']) ? "" : date('m/d/Y',strtotime($dataRow['end_date']))) ?>">
						</td>
					</tr>
					<tr>
						<td class="label">Impressions</td>
						<td align="left">
							<!-- added commas for readability @Joel -->
							<input type="text" name="impressions_paid" class="field info" style="width: 80px;" data-crc="<?php echo getCrc32($dataRow['impressions_paid']) ?>" value="<?php echo $dataRow['impressions_paid'] ?>">
						</td>
					</tr>
					<tr>
						<td class="label">Total Cost $</td>
						<td align="left">
							<input type="text" name="amount_paid" class="field info" style="width: 80px;" data-crc="<?php echo getCrc32($dataRow['amount_paid']) ?>" value="<?php echo $dataRow['amount_paid'] ?>">
						</td>
                                	</tr>
					<tr>
						<td class="label">Date Paid</td>
						<td align="left">
							<input type="text" name="date_paid" class="field info datepicker" style="width: 80px;" data-crc="<?php echo getCrc32((empty($dataRow['date_paid']) ? "" : date('m/d/Y',strtotime($dataRow['date_paid'])))) ?>" value="<?php echo (empty($dataRow['date_paid']) ? "" : date('m/d/Y',strtotime($dataRow['date_paid']))) ?>">
						</td>            
					</tr>
					
					
					<tr>
						
						<td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						  <input type="checkbox" name="enable_contest_entry" id="enable_contest_entry" value="Y"<?php echo ($dataRow['enable_contest_entry'] == 1 ? " checked" : "") ?> /><span  class='label'>&nbsp;Enable Contest entry popup</span></td> 
					    </tr>
					<tr>
						
						<td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              <input type="checkbox" name="internal_use_only" id="internal_use_only" value="Y"<?php echo ($dataRow['internal_use_only'] == 1 ? " checked" : "") ?> /><span  class='label'>&nbsp;Suspend this advertisement</span></td> 
					</tr>
          <tr>
            <td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              <input type="checkbox" name="inactive" id="inactive" value="Y"<?php echo ($dataRow['inactive'] == 1 ? " checked" : "") ?> /><span  class='label'>&nbsp;Make this advertisement Inactive</span>
          </tr>
          <!-- ama-38-->
                                        <tr>
					    <td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              <input type="checkbox" name="is_newwindow" id="is_newwindow" value="Y"<?php echo ($dataRow['is_newwindow'] == 1 ? " checked" : "") ?> /><span  class='label'>&nbsp;Open advertisement in new tab</span>
                                            </td> 
					</tr>
          <!--ama-38-->
				</table>
				</td>
				<td>
					<div class="stat-pane">
						<table cellpadding="0" cellspacing="6">
                            <!-- added commas with number_format function @Joel-->
							<tr><td class="label">Impressions Used (IU):</td><td class="stat"><?php echo number_format($dataRow['impressions_to_date']); ?></td></tr>
							<tr><td class="label">Clicks Count (CC):</td><td class="stat"><?php echo number_format($dataRow['clicks_to_date']); ?></td></tr>
                                                        <tr><td class="label">Impressions Remaining:</td><td class="stat"><?php echo number_format($dataRow['impressions_paid'] - $dataRow['impressions_to_date']); ?></td></tr>
							<tr><td class="label">Amount Remaining:</td><td class="stat"><?php echo ($dataRow['amount_remaining'] > 0 ? "$" . number_format($dataRow['amount_remaining'],2) : "") ?></td></tr>
                                                        <tr><td class="label">Click Through Ratio(IU vs CC) :</td><td class="stat"><?php $gcd = GCD ($dataRow['impressions_to_date'],$dataRow['clicks_to_date']); echo number_format(($dataRow['impressions_to_date']/ $gcd)) .":". number_format(($dataRow['clicks_to_date']/$gcd)) ?></td></tr>
                                                        <tr><td class="label">Click Throughs Count:</td><td class="stat"><?php echo $clickthroughcount ?> </tr>
                                                        <tr><td class="label">Click Throughs Value:</td><td class="stat"><?php echo "$".$clickthroughvalue; ?> </tr>
                                                        
                                                        </table>
					</div>
				</td>
				</tr></table>
			</div>

			<div class="image-sidebar">
				<div class="primary-thumb">
					<?php if (!empty($dataRow['image_id'])) {
						echo "<img src='/imagedb/image" . $dataRow['image_id'] . "-" . getImageHashCodeB($dataRow['image_id']) . "' class='thumbnail-image'>";
					} else {
						echo "<div class='no-image'>No Image</div>";
					} ?>
				</div>
				<div class="takeover-thumb">
					<?php if (!empty($dataRow['takeover_pair_id'])) {
						echo "<img src='/imagedb/image" . $dataRow['takeover_pair_id'] . "-" . getImageHashCodeB($dataRow['takeover_pair_id']) . "' class='thumbnail-image'>";
					} ?>
				</div>
			</div>

		</div>

		<div class="tab" id="tab_2">
			<div class="left-pane">
				<div class="section-head">Advertiser</div>
				<?php if (empty($primaryId)) { ?>
				<div class="prompt">Search for an existing advertiser...</div>
				<table cellpadding="2" cellspacing="0">
					<tr>
						<td width="220" align="right"><input type="text" name="search_text_advertiser" class="info" style="width: 180px;"></td>
						<td><button class="search_button" id="search_advertiser">Search</button></td>
					</tr>
				</table>
				<div id="search_results_advertiser" style="margin: 6px 0 12px 0;"></div>
				<div id="search_results_loader_advertiser" style="display: none; margin: 6px 21px 12px; 0; text-align: center"><img src="tmpl/ajax-loader.gif"></div>
				<div id="search_message_advertiser" style="display: none; text-align: center; margin: 12px 0; font-style: italic; color: #a53831;"></div>		
				<div class="prompt">Or create a new advertiser...</div>
				<?php } ?>
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td width="100" class="label">Company Name</td>
						<td align="left"><input type="text" name="company_name" class="field info" style="width: 200px;" value="<?php echo $dataRow['company_name'] ?>" data-crc="<?php echo getCrc32($dataRow['company_name']) ?>"></td>
					</tr>
					<tr>
						<td class="label">Contact</td>
						<td align="left"><input type="text" name="full_name" class="field info" style="width: 200px;" value="<?php echo $dataRow['full_name'] ?>" data-crc="<?php echo getCrc32($dataRow['full_name']) ?>"></td>
					</tr>
					<tr>
						<td class="label">Address</td>
						<td align="left"><input type="text" name="address_1" class="field info" style="width: 200px;" value="<?php echo $dataRow['address_1'] ?>" data-crc="<?php echo getCrc32($dataRow['address_1']) ?>"></td>
					</tr>
					<tr>
						<td class="label">City</td>
						<td align="left"><input type="text" name="city" class="field info" style="width: 200px;" value="<?php echo $dataRow['city'] ?>" data-crc="<?php echo getCrc32($dataRow['city']) ?>"></td>
					</tr>
					<tr>
						<td class="label">State</td>
						<td align="left">
							<select name="state" class="field info" size="1">
								<option value=''></option>
								<?php
									foreach ($stateArray as $state) {
										echo "<option value='$state'" . ($dataRow['state'] == $state ? " selected" : "") . ">$state</option>";
									}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td class="label">Zip Code</td>
						<td align="left"><input type="text" name="zip_code" class="field info" style="width: 100px;" value="<?php echo $dataRow['zip_code'] ?>" data-crc="<?php echo getCrc32($dataRow['zip_code']) ?>"></td>
					</tr>
					<tr>
						<td class="label">Phone Number</td>
						<td align="left"><input type="text" name="phone_number" class="field info" style="width: 160px;" value="<?php echo $dataRow['phone_number'] ?>" data-crc="<?php echo getCrc32($dataRow['phone_number']) ?>"></td>
					</tr>
					<tr>
						<td class="label">Fax Number</td>
						<td align="left"><input type="text" name="fax_number" class="field info" style="width: 160px;" value="<?php echo $dataRow['fax_number'] ?>" data-crc="<?php echo getCrc32($dataRow['fax_number']) ?>"></td>
					</tr>
					<tr>
						<td class="label">Email</td>
						<td align="left"><input type="text" name="email_address" class="field info" style="width: 160px;" value="<?php echo $dataRow['email_address'] ?>" data-crc="<?php echo getCrc32($dataRow['email_address']) ?>"></td>
					</tr>
				</table>
			</div>

			<div class="left-pane">
				<div class="section-head">Agency</div>
				<?php if (empty($primaryId)) { ?>
				<div class="prompt">Search for an existing agency...</div>
				<table cellpadding="2" cellspacing="0">
					<tr>
						<td width="220" align="right"><input type="text" name="search_text_agency" class="info" style="width: 180px;"></td>
						<td><button class="search_button" id="search_agency">Search</button></td>
					</tr>
				</table>
				<div id="search_results_agency" style="margin: 6px 0 12px 0;"></div>
				<div id="search_results_loader_agency" style="display: none; margin: 6px 21px 12px; 0; text-align: center"><img src="tmpl/ajax-loader.gif"></div>
				<div id="search_message_agency" style="display: none; text-align: center; margin: 12px 0; font-style: italic; color: #a53831;"></div>		
				<div class="prompt">Or create a new agency...</div>
				<?php } ?>
				<table class="shipment_address_table" cellpadding="0" cellspacing="0">
					<tr>
						<td width="100" class="label">Company Name</td>
						<td align="left"><input type="text" name="agency_company_name" class="field info" style="width: 200px;" value="<?php echo $dataRow['agency_company_name'] ?>" data-crc="<?php echo getCrc32($dataRow['agency_company_name']) ?>"></td>
					</tr>
					<tr>
						<td class="label">Contact</td>
						<td align="left"><input type="text" name="agency_full_name" class="field info" style="width: 200px;" value="<?php echo $dataRow['agency_full_name'] ?>" data-crc="<?php echo getCrc32($dataRow['agency_full_name']) ?>"></td>
					</tr>
					<tr>
						<td class="label">Address</td>
						<td align="left"><input type="text" name="agency_address_1" class="field info" style="width: 200px;" value="<?php echo $dataRow['agency_address_1'] ?>" data-crc="<?php echo getCrc32($dataRow['agency_address_1']) ?>"></td>
					</tr>
					<tr>
						<td class="label">City</td>
						<td align="left"><input type="text" name="agency_city" class="field info" style="width: 200px;" value="<?php echo $dataRow['agency_city'] ?>" data-crc="<?php echo getCrc32($dataRow['agency_city']) ?>"></td>
					</tr>
					<tr>
						<td class="label">State</td>
						<td align="left">
							<select name="agency_state" class="field info" size="1" >
								<option value=''></option>
								<?php
									foreach ($stateArray as $state) {
										echo "<option value='$state'" . ($dataRow['agency_state'] == $state ? " selected" : "") . ">$state</option>";
									}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td class="label">Zip Code</td>
						<td align="left"><input type="text" name="agency_zip_code" class="field info" style="width: 100px;" value="<?php echo $dataRow['agency_zip_code'] ?>" data-crc="<?php echo getCrc32($dataRow['agency_zip_code']) ?>"></td>
					</tr>
					<tr>
						<td class="label">Phone Number</td>
						<td align="left"><input type="text" name="agency_phone_number" class="field info" style="width: 160px;" value="<?php echo $dataRow['agency_phone_number'] ?>" data-crc="<?php echo getCrc32($dataRow['agency_phone_number']) ?>"></td>
					</tr>
					<tr>
						<td class="label">Fax Number</td>
						<td align="left"><input type="text" name="agency_fax_number" class="field info" style="width: 160px;" value="<?php echo $dataRow['agency_fax_number'] ?>" data-crc="<?php echo getCrc32($dataRow['agency_fax_number']) ?>"></td>
					</tr>
					<tr>
						<td class="label">Email</td>
						<td align="left"><input type="text" name="agency_email_address" class="field info" style="width: 160px;" value="<?php echo $dataRow['agency_email_address'] ?>" data-crc="<?php echo getCrc32($dataRow['agency_email_address']) ?>"></td>
					</tr>
				</table>

			</div>

			<div class="image-sidebar">
				<div class="primary-thumb">
					<?php if (!empty($dataRow['image_id'])) {
						echo "<img src='/imagedb/image" . $dataRow['image_id'] . "-" . getImageHashCodeB($dataRow['image_id']) . "' class='thumbnail-image'>";
					} else {
						echo "<div class='no-image'>No Image</div>";
					} ?>
				</div>
				<div class="takeover-thumb">
					<?php if (!empty($dataRow['takeover_pair_id'])) {
						echo "<img src='/imagedb/image" . $dataRow['takeover_pair_id'] . "-" . getImageHashCodeB($dataRow['takeover_pair_id']) . "' class='thumbnail-image'>";
					} ?>
				</div>
			</div>
			<div class="clear"></div>
		
		</div>
		

		<div class="tab" id="tab_3">

			<div class="left-pane">
				<div class="section-head">Primary Image</div>
				<div id="item_image" class="image-pane">
					<?php if (empty($dataRow['image_id'])) {
						echo "<div class='no-image'>No Image</div>";
					} else {
						echo "<img src='/imagedb/image" . $dataRow['image_id'] . "-" . getImageHashCodeB($dataRow['image_id']) . "' class='big-image'/>";
					} ?>
				</div>
				<label class="upload_image" onclick="document.getElementById('photo_upload').click()">Upload Image</label>
				<input id="photo_upload" name="photo_upload" type="file" value="Upload Image"  accept="image/x-png, image/gif, image/jpeg" />
				<div id="photo_upload_message" style="margin: 6px 0 0 0;"><?php echo (empty($fileErrorMessage) ? "" : $fileErrorMessage) ?></div>
				<div class="border-top">
					<span class="label">Link</span><input type="text" name="link_url" class="field info" style="width: 340px;" data-crc="<?php echo getCrc32($dataRow['link_url']) ?>" value="<?php echo $dataRow['link_url'] ?>">
				</div>

			</div>

			<div class="left-pane">
				<div class="section-head">Takeover Pair Image</div>
				<div id="item_image_b" class="image-pane">
					<?php if (empty($dataRow['takeover_pair_id'])) {
						echo "<div class='no-image'>No Image</div>";
					} else {
						echo "<img src='/imagedb/image" . $dataRow['takeover_pair_id'] . "-" . getImageHashCodeB($dataRow['takeover_pair_id']) . "' class='big-image'/>";
					} ?>
				</div>
				<label class="upload_image" onclick="document.getElementById('photo_upload_b').click()">Upload Image</label>
				<input id="photo_upload_b" name="photo_upload_b" type="file" value="Upload Image"  accept="image/x-png, image/gif, image/jpeg" />
				<div id="photo_upload_message_b" style="margin: 6px 0 0 0;"><?php echo (empty($fileErrorMessage) ? "" : $fileErrorMessage) ?></div>
				<div class="border-top">
					<span class="label">Link</span><input type="text" name="takeover_link_url" class="field info" style="width: 340px;" data-crc="<?php echo getCrc32($dataRow['takeover_link_url']) ?>" value="<?php echo $dataRow['takeover_link_url'] ?>">
				</div>				
			</div>		
		</div>
            <!-- Analytics tab-->
            <?php
                $resultSet = executeQuery("select advertising_id,click_count,date_clicked from Adclick_count where advertising_id = ?",$primaryId);
		$data = getNextRow($resultSet);
                ?>
            <div class="tab" id="tab_4">
                <div class="left-pane">
				<table cellpadding="0" cellspacing="2" width="100%" >
              <tr align="left">
                <td></td>
                <td align="left">From</td>
                <td></td>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                <td align="left">To</td>
              </tr>    
              <tr>
                   <input type="hidden" id="advertisingId" class="field info" style="width: 160px;" value="<?php echo $data['advertising_id']  ?>">
                <td class="label">Date Range</td>
                
                  <td align="left">
                    <input type="text" class="datepicker" id="start_date1" size="10" value="<?php echo date('m/d/Y') ?>">
                    
                  </td>
                    <td>&ndash;</td>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                   <td> 
                     <input type="text" class="datepicker" id="end_date1" size="10" value="<?php echo date('m/d/Y') ?>">
                     
                   </td>
                    <td><button id="date_apply" class="inline_button">Go</button>
                   
                    </td>
              </tr>
              <tr>
                <td>
                  <br/>
                </td>
              </tr>
              <tr>
                  <td class="label">Clicks Count</td>
                  <td align="left" colspan='5'><input type="text" id="clicks_count" class="stat" value = " " readonly = "true" style ="width:300px" ></td>
              </tr>
                                        
        </table>
                </div>
                
            </div><!-- tab-4 end -->
	</div>
</div>
<div id="button_div" style="margin: 10px 0px 0px 0px;">
  <?php
      if($_GET['url_page']=='show')
      {
  ?>
  <button class="control-button" name="previous_campaign_button" id="previous_campaign_button" accesskey="," <?php echo ($disable === false) ? ' style="display:none;"' : ''?>>&larr;</button>
	<button class="control-button" name="custom_save_button" id="custom_save_button" accesskey="s" >Save</button>
  <button class="control-button" name="list_button" id="list_button" accesskey="l" >List</button>
  <button class="control-button" name="next_campaign_button" id="next_campaign_button" accesskey="." <?php echo ($disable === false) ? ' style="display:none;"' : ''?>>&rarr;</button>
  <?php
      }
      else {
  ?>
  <button class="control-button" name="custom_save_button" id="custom_save_button" accesskey="s" >Save</button>
  <button class="control-button" name="back_button" id="back_button" accesskey="l" >Back</button>
  <?php
      }
  ?>
</div>
<?php
break;
default:
?>
<div class="format_section" style="margin: 20px 0;">
<?php if ($rowCount > $gItemsPerPage) { ?>
<div id="filter_div">
<table>
<tr>
	<td class='label'>Search For</td>
	<td><input type="text" name="filter_text" id="filter_text" value="<?php echo htmlspecialchars($filterText,ENT_QUOTES,'UTF-8') ?>" /></td>
	<td><button name="filter_button" id="filter_button" >Search</button></td>
	<?php if (!empty($filterText)) { ?>
	<td><button name="list_all_button" id="list_all_button" >Clear Search</button></td>
	<?php } ?>
	<td align="right"><button name="add_button_from_list" id="add_button_from_list" >New Campaign</button></td>
	<?php if ($rowCount > count($dataList)) { ?>
	<td>&nbsp;&nbsp;|&nbsp;&nbsp;</td>
	<td><button class="control-button" name="previous_page_button" id="previous_page_button" accesskey="," >&larr;</button></td>
	<td class='label'><?php echo floor(($dataOffset / $gItemsPerPage) + 1) . " of " . ceil($rowCount / $gItemsPerPage ) ?></td>
	<td><button class="control-button" name="next_page_button" id="next_page_button" accesskey="." >&rarr;</button></td>
	<?php } ?>
</tr>
</table>
</div>
<?php } ?>
<!--start -->
<?php
 if (isset($_GET['company_name'])){ ?>
<table class="grid-table" id="list_table">
  <?php 
   if($_GET['inactive'] == true)
   {?>
          <label for="activeid">Show Active</label>
          <input id="activeid" class="activeid" name="activeid" type="checkbox" />
<?php }if($_GET['inactive'] == false){ ?>
        <label for="inactiveid">Show Inactive</label>
        <input id="inactiveid" class="inactiveid" name="inactiveid" type="checkbox" />
<?php } ?>
	<tr>          
        <th class='list left'><a class="column-header">Advertiser</a></th>
        <th class='list left'><a class="column-header">Description</a></th>
	<th class='list left'><a class="column-header">Unit</a></th>
	<th class='list left'><a class="column-header">Start Date</a></th>
	<th class='list left'><a class="column-header">End Date</a></th>
	<th class='list right'><a class="column-header">Impressions</a></th>
	<th class='list right'><a class="column-header">Remaining</a></th>
        	</tr>
	<?php
        $inactive =0;
        if($_GET['inactive'] == true){
        $inactive=1;
        }
        $rowNumber = 0;
                $resultSet = executeQuery("select a.advertising_id,
		c.company_name,
		a.description,
		a.advertising_unit_id,
		a.start_date,
		a.end_date,
		a.impressions_paid,
    a.impressions_to_date 
		FROM advertising a 
		RIGHT JOIN contacts c USING (contact_id) 
		WHERE a.inactive=? and c.company_name like ? ",$inactive,$_GET['company_name']."%");
                while ($row = getNextRow($resultSet)) {	
		$rowNumber++;
    
	?>
	<tr class='list_row'>
		<td class='list'>
			<a<?php echo ($rowNumber < 10 ? " accesskey='" . $rowNumber . "'" : "") ?> class="record-link" data-primary_id="<?php echo $row['advertising_id'] ?>">
			<?php echo $row['company_name'] ?></a>
		</td>
		<td class='list'>
			<a<?php echo ($rowNumber < 10 ? " accesskey='" . $rowNumber . "'" : "") ?> class="record-link" data-primary_id="<?php echo $row['advertising_id'] ?>">
			<?php echo htmlspecialchars($row['description'],ENT_QUOTES,"UTF-8") ?></a>
		</td>
		<td class='list'>
			<a<?php echo ($rowNumber < 10 ? " accesskey='" . $rowNumber . "'" : "") ?> class="record-link" data-primary_id="<?php echo $row['advertising_id'] ?>">
			<?php echo getFieldFromId('description','advertising_units','advertising_unit_id',$row['advertising_unit_id']) ?></a>
		</td>
		<td class='list'>
			<a<?php echo ($rowNumber < 10 ? " accesskey='" . $rowNumber . "'" : "") ?> class="record-link" data-primary_id="<?php echo $row['advertising_id'] ?>">
			<?php echo (empty($row['start_date']) ? "" : date('m/d/Y',strtotime($row['start_date']))) ?></a>
		</td>
		<td class='list'>
			<a<?php echo ($rowNumber < 10 ? " accesskey='" . $rowNumber . "'" : "") ?> class="record-link" data-primary_id="<?php echo $row['advertising_id'] ?>">
			<?php echo (empty($row['end_date']) ? "" : date('m/d/Y',strtotime($row['end_date']))) ?></a>
		<td class='list right'>
			<!-- add commas -->
			<a<?php echo ($rowNumber < 10 ? " accesskey='" . $rowNumber . "'" : "") ?> class="record-link" data-primary_id="<?php echo $row['advertising_id'] ?>">
			<?php echo number_format($row['impressions_paid']) ?></a>
		</td>
		<td class='list right'>
			<a<?php echo ($rowNumber < 10 ? " accesskey='" . $rowNumber . "'" : "") ?> class="record-link" data-primary_id="<?php echo $row['advertising_id'] ?>">
			<?php echo ($row['impressions_paid'] > 0 ? number_format($row['impressions_paid'] - $row['impressions_to_date']) : "")  ?></a>
		</td>
		</td>
	</tr>
        <?php } ?>
  
</table>

<?php }?>


<?php if (!isset($_GET['company_name'])) {
    if (count($dataList) > 0 ){?>

<table class="grid-table" id="list_table">
	<tr>          
        <th class='list left'><a class="home-column-header">Advertiser</a></th>
    </tr>
	<?php
	$rowNumber = 0;
        foreach ($dataList as $dataRow) {
		$rowNumber++;
	?>
	<tr class='list_row'>
		<td class='list'>
			<a<?php echo ($rowNumber < 10 ? " accesskey='" . $rowNumber . "'" : "") ?> class="list-link" data-company_name="<?php echo $dataRow['company_name'] ?>">
			<?php echo $dataRow['company_name'] ?></a>
		</td>
	</tr>
        <?php } ?>
  
</table>
<div id="button_div" style="margin: 10px 0 0 0;">
		<button class="control-button" name="add_button" id="add_button" accesskey="n" >New Campaign</button>
                <?php if (strpos($_SERVER['REQUEST_URI'],"url_page=list")>0){ ?>
                <button class="control-button" name="back1_button" id="back1_button" accesskey="l" >Back</button>
                <?php } ?>         
</div> 
<?php } else { ?>

<div id="empty_list">
	No advertisements in list.
        <?php if (strpos($_SERVER['REQUEST_URI'],"url_page=list")>0){ ?>
        <button class="control-button" name="back1_button" id="back1_button" accesskey="l" style="height:23px;width:50px;font-size:12px;">Back</button>
        <?php } ?>   
</div>
<?php }} 
if(isset($_GET['company_name'])){?>
<div id="button_div">
	<!--<button class="control-button" name="add_button" id="add_button" >New Campaign</button>-->
        <button class="control-button" name="back_button" id="back_button" accesskey="l" >Back</button>
</div>
<?php } ?>
</div> <!-- format_section -->

<?php break; } ?>

</form>


<div id="error_tip"><div id="error_tip_contents"><span id="error_tip_message"></span><div id="error_tip_blip"><img src="tmpl/error_blip.png"></div></div></div>

<div id="save_changes" class="hidden-element" title="Save Changes?">
<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Do you want to save the changes?
</div>
<div id="delete_record" class="hidden-element" title="Delete Record?">
<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><span id="delete_record_text">Are you sure you want to delete this record?</span>
</div>

</div> <!-- #main -->

<?php include_once "scpt/admin_footer.inc"; ?>

<script>
$(document).ready(function () {
    $("#tabs").tabs();
    $("#tabs").tabs("option","active",<?php echo (empty($_POST['active_tab']) ? 0 : $_POST['active_tab']) ?>);
    
	$(".datepicker").datepicker();

    $("#advertising_unit_id").change(function() {
    	var rate = $("#advertising_unit_id option:selected").data('rate').toFixed(2);
	    $("input[name='rate']").val(rate);
	    updateTotal();
    });
    
    $("input[name='rate']").change(function(){ updateTotal(); });
    
    $("input[name='impressions_paid']").change(function(){ updateTotal(); });

	$("input[name='advertiser_name']").change(function() { 
		$("input[name='company_name']").val($("input[name='advertiser_name']").val());
	});

	$('#photo_upload').on('change',(function(e) {
        e.preventDefault();
		var imgFile = $('#photo_upload').val().split('.').pop().toLowerCase();
		
		if($.trim(imgFile)) {
			var formData = new FormData();
			formData.append('file', $('#photo_upload')[0].files[0]);
			$("#item_image").html('<img src="tmpl/ajax-loader-bar.gif" alt="Uploading...."/>');
			
			if($.inArray(imgFile, ['gif','png','jpg','jpeg']) == -1) {
				$("#new_image_file").val("");
				$("#item_image").html("");
				$("#photo_upload_message").text('Invalid file type!').css("color","#f71a0e");
			}
			else {
				$.ajax({
					type:'POST',
					url: 'scpt/upload_image.php',
					data:formData,
					cache:false,
					contentType: false,
					processData: false,
					success:function(responseData) {
						var resData = responseData.split(':');
						if($.trim(resData[0]) == 'success') {
							$("#new_image_file").val(resData[1]); //will process and store in database on save
							$("#item_image").html("<img src='imagecache/" + resData[1] + "' style='max-width: 190px; max-height: 110px;'>");
							$("#photo_upload_message").text(resData[1] + ' was uploaded.').css("color","#1fc352");
						}
						else {						
							$("#new_image_file").val("");
							$("#item_image").html("");
							$("#photo_upload_message").text(resData[1]).css("color","#f71a0e");
						}
					},
					error: function(responseData) {
						console.log("error: "+responseData);
					}
				});
			}
		}
    }));

	$('#photo_upload_b').on('change',(function(e) {
        e.preventDefault();
		var imgFile = $('#photo_upload_b').val().split('.').pop().toLowerCase();
		
		if($.trim(imgFile)) {
			var formData = new FormData();
			formData.append('file', $('#photo_upload_b')[0].files[0]);
			$("#item_image_b").html('<img src="tmpl/ajax-loader-bar.gif" alt="Uploading...."/>');
			
			if($.inArray(imgFile, ['gif','png','jpg','jpeg']) == -1) {
				$("#new_takeover_image_file").val("");
				$("#item_image_b").html("");
				$("#photo_upload_message_b").text('Invalid file type!').css("color","#f71a0e");
			}
			else {
				$.ajax({
					type:'POST',
					url: 'scpt/upload_image.php',
					data:formData,
					cache:false,
					contentType: false,
					processData: false,
					success:function(responseData) {
						var resData = responseData.split(':');
						if($.trim(resData[0]) == 'success') {
							$("#new_takeover_image_file").val(resData[1]); //will process and store in database on save
							$("#item_image_b").html("<img src='imagecache/" + resData[1] + "' style='max-width: 190px; max-height: 110px;'>");
							$("#photo_upload_message_b").text(resData[1] + ' was uploaded.').css("color","#1fc352");
						}
						else {						
							$("#new_takeover_image_file").val("");
							$("#item_image_b").html("");
							$("#photo_upload_message_b").text(resData[1]).css("color","#f71a0e");
						}
					},
					error: function(responseData) {
						console.log("error: "+responseData);
					}
				});
			}
		}
    }));

	$("#custom_save_button").click(function() {
		$(".control-button").prop("disabled",true);
		if (verifyFields()) {
			$("#active_tab").val($("#tabs").tabs("option","active"));
			submitForm("show","save","","<?php echo $primaryId ?>");
		} else {
			$(".control-button").prop("disabled",false);
		}
		return false;
	});
	
	$(".search_button").click(function(){
		var which = $(this).attr("id").replace("search_","");
		var field = $("input[name=search_text_" + which + "]");
		
		if ($(field).val() == "" || $(field).val().length < 1) {
			$("#search_message_" + which).html("Enter an " + which + " to search for.").slideDown('fast').delay(2000).slideUp('fast');
			return false;
		}
		$("#search_results_" + which).html("");
		$(".search_button").prop('disabled',true).css('opacity', 0.5);
		$("#search_results_loader_" + which).slideDown('fast');

		$.ajax({
			url: $("#g_php_self").val(),
			type: "POST",
			data: {'action':'search_addresses','which':which,'search_text':$(field).val()},
			success: function(returnArray) {
				$("#search_results_loader_" + which).slideUp('fast');
				if (returnArray['status'] == 'success') {
					switch(returnArray['address_count']) {
						case 0:
							$("#search_message_" + which).html("'" + $(field).val() + "' not found").slideDown('fast');
							break;
						case 1:
							$("#search_message_" + which).html("").hide();
							$(field).val("");
							var addressRow = returnArray['address'].split('|');
							loadAddress(which,addressRow[0],true);
							break;
						default:
							$(field).val("");
							// build a select element with results
							var theDiv = $("#search_results_" + which);
							$(theDiv).append("<select name='use_address' style='width: 310px;'>");
							$(theDiv).find("select[name=use_address]").append("<option value=''>Select...</option>");
							$.each(returnArray['addresses'], function(key,row) {
								var addressRow = row.split('|');
								$(theDiv).find("select[name=use_address]").append("<option value='" + addressRow[0] + "'>" + addressRow[1] + ": " + addressRow[3] + ", " + addressRow[4] + " " + addressRow[5] + "</option>");
							});
							$(theDiv).append("</select>");
							$(theDiv).find("select[name=use_address]").change(function() {
								$(theDiv).find("select[name=use_address]").remove();
								loadAddress(which,$(this).val(),true);
							});
							break;
					}
					$(".search_button").prop('disabled',false).css('opacity', '');
				} else {
					$("#search_message_" + which).html(returnArray['message'] + ". Please try again!").slideDown('fast').delay(4000).slideUp('fast');
					$(".search_button").prop('disabled',false).css('opacity', '');
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				$("#search_results_loader_" + which).slideUp('fast');
				$("#search_message_" + which).html("System Error: " + errorThrown + ". Please try again!").slideDown('fast').delay(4000).slideUp('fast');
				$(".search_button").prop('disabled',false).css('opacity', '');
			},
			dataType: "json"
		});
		return false;
	});
    
});

function loadAddress(which,addressId,fromSearch) {

	$("search_message_" + which).html("").hide();
	$.ajax({
		url: $("#g_php_self").val(),
		type: "POST",
		data: {'action':'load_address','address_id':addressId},
		success: function(returnArray) {
			$("#search_results_loader_" + which).slideUp('fast');
			$("input[name=search_text_" + which + "]").val("");
			if (returnArray['status'] == 'success') {
				var addressRow = returnArray['address'].split('|');
				var extra = (which == "agency" ? "agency_" : "");
				// set field values				
				$("input[name=" + extra + "company_name]").val(addressRow[4]);
				$("input[name=" + extra + "full_name]").val(addressRow[2] + " " + addressRow[3]);
				$("input[name=" + extra + "address_1]").val(addressRow[5]);
				$("input[name=" + extra + "city]").val(addressRow[7]);
				$("select[name=" + extra + "state]").val(addressRow[8]);
				$("input[name=" + extra + "zip_code]").val(addressRow[9]);
				$("input[name=" + extra + "phone_number]").val(addressRow[12]);
				$("input[name=" + extra + "fax_number]").val(addressRow[14]);
				$("input[name=" + extra + "email_address]").val(addressRow[11]);
				
				if (which == "advertiser") {
					$("input[name=advertiser_name]").val(addressRow[4]);
				}
				
			}
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			$("#search_message_" + which).html("System Error: " + errorThrown + ". Please try again!").slideDown('fast').delay(4000).slideUp('fast');
		},
		dataType: "json"
	});
	$(".search_button").prop('disabled',false).css('opacity', '');
	return false;
}

function updateTotal() {
	var rate = parseFloat($("input[name='rate']").val());
	var impressions = parseFloat($("input[name='impressions_paid']").val());
	if (rate > 0 && impressions > 0) {
		var total = rate * (impressions / 1000);
		$("input[name='amount_paid']").val(total.toFixed(2));
	}
}

function verifyFields() {
	var error = false;
	var field = $("input[name='advertiser_name']");
	if ($(field).val() == "" || $(field).val().length < 1) {
		$(field).css("background-color","#fff6c7");
		error = true;
	}
	field = $("select[name='advertising_unit_id']");
	if ($(field).val() == "" || $(field).val().length < 1) {
		$(field).css("background-color","#fff6c7");
		error = true;
	}
	field = $("input[name='description']");
	if ($(field).val() == "" || $(field).val().length < 1) {
		$(field).css("background-color","#fff6c7");
		error = true;
	}
	if (error === true) {
		return false;
	} else {
		return true;
	}
}
/* For update clicks count */
$('#date_apply').click(function() {
       
     var AdId = $('#advertisingId').val();
     var startdate1 = $('#start_date1').val();
     var enddate1 = $('#end_date1').val();
     
      $.ajax({
		url: "/scpt/adclicks_count.php?ajax=true",
		type: "POST",
		data: {advertising_id:AdId,startdate:startdate1,enddate:enddate1},
                  success:function(result){
                  $("#clicks_count").val(result);                  
                }
                //dataType: "json"
		
	});
  return false;
    });
    
 /* for Ad inactive*/
   $('#activeid').change(function(){
      var isChecked = $('#activeid').prop('checked');
      var c_name=jQuery("#company_active").val();         
    jQuery("#edit_form").attr({action: jQuery("#g_php_self").val()+"?company_name="+c_name+ "&active="+(isChecked) ,method: "POST"}).submit();
          
   });
  
   $('#inactiveid').change(function(){
      var isChecked = $('#inactiveid').prop('checked');
      var c_name=jQuery("#company_active").val();
    jQuery("#edit_form").attr({action: jQuery("#g_php_self").val()+"?company_name="+c_name+ "&inactive="+(isChecked) ,method: "POST"}).submit();

   });
</script>
</body>
</html>