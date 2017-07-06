<?php

################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################

$gProgramCode = "CUSTOMERMAINTENANCE";
include_once "scpt/utilities.inc";
include_once "scpt/class.datasource.php";

$dealerArray = getDealerInfo($gDealerId);

$dataSource = new DataSource("orders");
$dataSource->setFilterWhere('dealer_id = ' . $gDealerId);
$dataSource->setDefaultSortOrder("last_name");
$dataSource->setSortOrder($_POST['g_sort_order']);
$dataSource->setReverseSort($_POST['g_reverse_sort'] == "true");
$dataSource->setFilterText($_POST['filter_text']);
$dataSource->setSearchFields("description");
$additionalColumns = array(
	"first_name"=>"select first_name from contacts where contact_id = orders.contact_id",
	"last_name"=>"select last_name from contacts where contact_id = orders.contact_id",
	"email_address"=>"select email_address from contacts where contact_id = orders.contact_id",
	"city"=>"select city from contacts where contact_id = orders.contact_id",
	"state"=>"select state from contacts where contact_id = orders.contact_id"
);
$dataSource->addAdditionalDataListColumns($additionalColumns);

$urlAction = $_GET['url_action'];
switch ($urlAction) {
	case "save":
		$dataSource->saveRecord(array("name_values"=>$_POST,"primary_id"=>$_POST['primary_id']));
		break;
	case "delete":
		$dataSource->deleteRecord(array("primary_id"=>$_POST['primary_id']));
		break;
}

$nextLocation = $_POST['g_next_location'];
if (!empty($nextLocation)) {
	header("Location: " . $nextLocation);
	exit;
}

$nextRecordId = "";
$previousRecordId = "";

$urlPage = $_GET['url_page'];
switch ($urlPage) {
	case "new":
		$dataSource->setPrimaryId("");
		$dataRow = array();
		$dataRow['sort_order'] = 0;
		$urlPage = "show";
		break;
	case "show":
		if ($urlPage == "show") {
			$dataSource->setPrimaryId($_GET['primary_id']);
			$dataRow = $dataSource->getRow();
		}
		if ($dataRow === false) {
			$urlPage = "list";
		} else {
			$nextRecordId = $dataSource->getNextRecordId();
			$previousRecordId = $dataSource->getPreviousRecordId();
		}
		break;
	default:
		$listColumnData = array();
		$listColumnData[] = array("field_name"=>"first_name","sort_order"=>"first_name","label"=>"First Name");
		$listColumnData[] = array("field_name"=>"last_name","sort_order"=>"last_name","label"=>"Last Name");
		$listColumnData[] = array("field_name"=>"email_address","sort_order"=>"email_address","label"=>"Email Address");
		$listColumnData[] = array("field_name"=>"city","sort_order"=>"city","label"=>"City");
		$listColumnData[] = array("field_name"=>"state","sort_order"=>"state","label"=>"State");
		$dataList = $dataSource->getDataList(array("group_by"=>"contact_id"));
		$urlPage = "list";
		break;
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $dealerArray['dealer_name'] . " | " . $gProgramTitle; ?></title>
	<meta name="description" content="">
	<meta name="author" content="zipperSNAP">
	<link rel="stylesheet" href="tmpl/styles_admin.css?v=1.0">
	<link type="text/css" href="scpt/custom-theme/jquery-ui.css" rel="stylesheet" />
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
<?php } ?>

<h1><?php echo $gProgramTitle; ?></h1>

<div id="maintenance_area">
<form name="edit_form" id="edit_form" method="post">
<input type="hidden" name="g_sort_order" id="g_sort_order" value="<?php echo $dataSource->getSortOrder(true) ?>" />
<input type="hidden" name="g_reverse_sort" id="g_reverse_sort" value="<?php echo ($dataSource->getReverseSort() ? "true" : "false") ?>" />
<input type="hidden" name="g_previous_record_id" id="g_previous_record_id" value="<?php echo $previousRecordId ?>" />
<input type="hidden" name="g_next_record_id" id="g_next_record_id" value="<?php echo $nextRecordId ?>" />
<input type="hidden" name="g_next_location" id="g_next_location" value="" />
<input type="hidden" name="g_data_offset" id="g_data_offset" value="<?php echo $dataOffset ?>" />
<input type="hidden" name="g_data_count" id="g_data_count" value="<?php echo $dataSource->getDataListCount() ?>" />
<input type="hidden" name="g_php_self" id="g_php_self" value="<?php echo $_SERVER['PHP_SELF'] ?>" />
<input type="hidden" name="primary_id" id="primary_id" value="<?php echo $dataSource->getPrimaryId() ?>" />
<input type="hidden" name="version" id="version" value="<?php echo (empty($dataRow['version']) ? '1' : $dataRow['version']) ?>" />

<?php

switch ($urlPage) {
	case "new":
	case "show":
?>

<div class="format_section" style="margin: 20px 140px;">

<table class="grid-table">
<tr>
	<td class='label'>First Name</td>
	<td class='description'><?php echo getFieldFromId('first_name','contacts','contact_id',$dataRow['contact_id']) ?></td>
</tr>
<tr>
	<td class='label'>Last Name</td>
	<td class='description'><?php echo getFieldFromId('last_name','contacts','contact_id',$dataRow['contact_id']) ?></td>
</tr>
<tr>
	<td class='label'>Address</td>
	<td class='description'>
		<?php 
			echo getFieldFromId('address_1','contacts','contact_id',$dataRow['contact_id']) . "<br>";
			echo getFieldFromId('city','contacts','contact_id',$dataRow['contact_id']) . ", ";
			echo getFieldFromId('state','contacts','contact_id',$dataRow['contact_id']) . " &nbsp;";
			echo getFieldFromId('zip_code','contacts','contact_id',$dataRow['contact_id']);
		?>
	</td>
</tr>
<tr>
	<td class='label'>Email</td>
	<td class='description'><?php 
		$email_address = getFieldFromId('email_address','contacts','contact_id',$dataRow['contact_id']);
		echo "<a href='mailto:$email_address' class='link'>$email_address</a>";
	?></td>
</tr>
<tr>
	<td class='label'>Phone</td>
	<td class='description'><?php echo getFieldFromId('phone_number','contacts','contact_id',$dataRow['contact_id']) ?></td>
</tr>
</table>

<div id="button_div">
	<button class="control-button" name="previous_button" id="previous_button" accesskey="," >&larr;</button>
	<button class="control-button" name="list_button" id="list_button" accesskey="l" >Back To List</button>
	<button class="control-button" name="next_button" id="next_button" accesskey="." >&rarr;</button>
</div>

</div> <!-- format_section -->

<?php
break; //$urlPage = new or show

default:
?>

<?php if (count($dataList)>0) { ?>

<table class="grid-table" id="list_table">
	<tr>
	<?php foreach ($listColumnData as $column) { ?>
		<th class='list left'><a class="column-header" data-sort_order="<?php echo $column['sort_order'] ?>"><?php echo $column['label'] ?><?php echo ($dataSource->getSortOrder(true) == $column['sort_order'] ? "&nbsp;" . ($dataSource->getReverseSort() ? "&uarr;" : "&darr;") : "") ?></a></th>
	<?php } ?>
	</tr>
	<?php
	$rowNumber = 0;
	foreach ($dataList as $dataRow) {
		$rowNumber++;
	?>
	<tr class='list_row'>
	<?php foreach ($listColumnData as $column) { ?>
		<td class='list left'><a<?php echo ($rowNumber < 10 ? " accesskey='" . $rowNumber . "'" : "") ?> class="record-link" data-primary_id="<?php echo $dataRow['order_id'] ?>"><?php echo htmlspecialchars($dataRow[$column['field_name']],ENT_QUOTES,"UTF-8") ?></a></td>
	<?php } ?>
	</tr>
	<?php } ?>
</table>

<?php } else { ?>

<div id="empty_list">
	No customers.
</div>

<?php } ?>

<?php break; } ?>
</form>
</div> <!-- maintenance area -->

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

	$("#new_override_button").click(function() {
		document.location = "overrideaddmaintenance.php";
		return false;
	});

});

function verifyFields() {
	var error = false;
	
	//validate here

	if (error === true) {
		return false;
	} else {
		return true;
	}
}
</script>
</body>
</html>