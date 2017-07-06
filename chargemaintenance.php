<?php

################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################

$gProgramCode = "CHARGEMAINTENANCE";

include_once "scpt/utilities.inc";
include_once "scpt/class.datasource.php";

$gItemsPerPage = 30;

$dealerArray = getDealerInfo($gDealerId);

$dataSource = new DataSource("dealer_charges");
$dataSource->setFilterWhere("dealer_id = " . $gDealerId . " and inactive = 0");
$dataSource->setDefaultSortOrder("description");
$dataSource->setSortOrder($_POST['g_sort_order']);
$dataSource->setReverseSort($_POST['g_reverse_sort'] == "true");
$dataSource->setFilterText($_POST['filter_text']);
$dataSource->setSearchFields("description");

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

$dataOffset = $_POST['g_data_offset'];
if (empty($dataOffset) || !is_numeric($dataOffset)) {
	$dataOffset = 0;
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
		$dataSource->setPrimaryId($_GET['primary_id']);
		$dataRow = $dataSource->getRow();
		if ($dataRow === false) {
			$urlPage = "list";
		} else {
			$nextRecordId = $dataSource->getNextRecordId();
			$previousRecordId = $dataSource->getPreviousRecordId();
		}
		break;
	default:
		$listColumnData = array();
		$listColumnData[] = array("field_name"=>"description","sort_order"=>"description","label"=>"Description");
		$listColumnData[] = array("field_name"=>"amount","sort_order"=>"amount","label"=>"amount");
		$parameterList = array("start_row"=>$dataOffset,"row_count"=>$gItemsPerPage);
		$dataList = $dataSource->getDataList($parameterList);
		$filterText = $dataSource->getFilterText();
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

<?php include "scpt/admin_header.inc"; ?>

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

<form name="edit_form" id="edit_form" method="post">
<input type="hidden" name="g_sort_order" id="g_sort_order" value="<?php echo $dataSource->getSortOrder(true) ?>" />
<input type="hidden" name="g_reverse_sort" id="g_reverse_sort" value="<?php echo ($dataSource->getReverseSort() ? "true" : "false") ?>" />
<input type="hidden" name="g_previous_record_id" id="g_previous_record_id" value="<?php echo $previousRecordId ?>" />
<input type="hidden" name="g_next_record_id" id="g_next_record_id" value="<?php echo $nextRecordId ?>" />
<input type="hidden" name="g_next_location" id="g_next_location" value="" />
<input type="hidden" name="g_items_per_page" id="g_items_per_page" value="<?php echo $gItemsPerPage ?>" />
<input type="hidden" name="g_data_offset" id="g_data_offset" value="<?php echo $dataOffset ?>" />
<input type="hidden" name="g_filter_text" id="g_filter_text" value="<?php echo $filterText ?>" />
<input type="hidden" name="g_data_count" id="g_data_count" value="<?php echo $dataSource->getDataListCount() ?>" />
<input type="hidden" name="g_php_self" id="g_php_self" value="<?php echo $_SERVER['PHP_SELF'] ?>" />
<input type="hidden" name="primary_id" id="primary_id" value="<?php echo $dataSource->getPrimaryId() ?>" />
<input type="hidden" name="dealer_id" id="dealer_id" value="<?php echo ($gAdministratorFlag?"":$gDealerId) ?>" />
<input type="hidden" name="sort_order" id="sort_order" value="<?php echo $dataRow['sort_order'] ?>" />
<input type="hidden" name="version" id="version" value="<?php echo (empty($dataRow['version']) ? '1' : $dataRow['version']) ?>" />

<?php

switch ($urlPage) {
	case "new":
	case "show":
?>

<div class="format_section" style="margin: 40px 230px;">

<table class="grid-table">
<tr>
	<td class='label'>Description</td>
	<td><input type="text" name="description" id="description" size="40" value="<?php echo htmlspecialchars($dataRow['description'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['description']) ?>" /></td>
</tr>
<tr>
	<td class='label'>Amount</td>
	<td><input type="text" name="amount" id="amount" class="numeric" size="8" value="<?php echo htmlspecialchars($dataRow['amount'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['amount']) ?>" /></td>
</tr>
<?php
	$resultSet = executeQuery("select * from category_groups where inactive = 0 and (dealer_id is null or dealer_id = ?) order by description",$gDealerId);
	if ($resultSet['row_count'] > 0) { ?>
<tr>
	<td class='label'>Category Group</td>
	<td>
		<select name='category_group_id' id='category_group_id'>
		<option value=''>All</option>
		<?php
			while ($row = getNextRow($resultSet)) {
				echo "<option value='" . $row['category_group_id'] . "'" . ($row['category_group_id']==$dataRow['category_group_id']?" selected":"") . ">" . $row['description'] . "</option>";
			}
		?>
		</select>
	</td>
</tr>
<?php } ?>
<tr><td>&nbsp;</td><td></td></tr>
<tr><td>&nbsp;</td><td><i>Apply this charge when the shopping cart contains the following items...</i></td></tr>
<tr>
	<td></td>
	<td><input type="checkbox" name="apply_firearm" id="apply_firearm" value="Y"<?php echo ($dataRow['apply_firearm'] == 1 ? " checked" : "") ?> /><span class='label'>&nbsp;Firearms - rifles and handguns</span></td>
</tr>
<tr>
	<td></td>
	<td><input type="checkbox" name="apply_ammo" id="apply_ammo" value="Y"<?php echo ($dataRow['apply_ammo'] == 1 ? " checked" : "") ?> /><span class='label'>&nbsp;Ammunition</span></td>
</tr>
<tr>
	<td></td>
	<td><input type="checkbox" name="apply_nfa" id="apply_nfa" value="Y"<?php echo ($dataRow['apply_nfa'] == 1 ? " checked" : "") ?> /><span class='label'>&nbsp;NFA/Class III</span></td>
</tr>
<tr>
	<td></td>
	<td><input type="checkbox" name="apply_other" id="apply_other" value="Y"<?php echo ($dataRow['apply_other'] == 1 ? " checked" : "") ?> /><span class='label'>&nbsp;All other items</span></td>
</tr>
<tr><td>&nbsp;</td><td></td></tr>
<tr><td>&nbsp;</td><td><i>Apply this charge when the following delivery methods are selected...</i></td></tr>
<tr>
	<td></td>
	<td><input type="checkbox" name="apply_hold" id="apply_hold" value="Y"<?php echo ($dataRow['apply_hold'] == 1 ? " checked" : "") ?> /><span class='label'>&nbsp;In-store pickup orders</span></td>
</tr>
<tr>
	<td></td>
	<td><input type="checkbox" name="apply_ship" id="apply_ship" value="Y"<?php echo ($dataRow['apply_ship'] == 1 ? " checked" : "") ?> /><span class='label'>&nbsp;Shipped orders</span></td>
</tr>
<tr><td>&nbsp;</td><td></td></tr>
<tr>
	<td></td>
	<td><input type="checkbox" name="internal_use_only" id="internal_use_only" value="Y"<?php echo ($dataRow['internal_use_only'] == 1 ? " checked" : "") ?> /><span  class='label'>&nbsp;Deactivate this charge </span><span class="note">(can be reactivated at any time)</span></td>
</tr>
</table>

</div> <!-- format section -->

<div id="button_div" class="top_border">
	<button class="control-button" name="previous_button" id="previous_button" accesskey="," >&larr;</button>
	<button class="control-button" name="add_button" id="add_button" accesskey="n" >New</button>
	<button class="control-button" name="save_button" id="save_button" accesskey="s" >Save</button>
	<button class="control-button" name="delete_button" id="delete_button" accesskey="d" >Delete</button>
	<button class="control-button" name="list_button" id="list_button" accesskey="l" >List</button>
	<button class="control-button" name="next_button" id="next_button" accesskey="." >&rarr;</button>
</div>


<?php
break;
default:
?>

<div class="format_section" style="margin: 20px 140px;">

<?php if ($dataSource->getDataListCount() > $gItemsPerPage) { ?>
<div id="filter_div">
<table>
<tr>
	<td class='label'>Search For</td>
	<td><input type="text" name="filter_text" id="filter_text" value="<?php echo htmlspecialchars($filterText,ENT_QUOTES,'UTF-8') ?>" /></td>
	<td><button name="filter_button" id="filter_button" >Search</button></td>
	<?php if (!empty($filterText)) { ?>
	<td><button name="list_all_button" id="list_all_button" >Clear Search</button></td>
	<?php } ?>
	<td align="right"><button name="add_button_from_list" id="add_button_from_list" >Add Charge</button></td>
	<?php if ($dataSource->getDataListCount() > count($dataList)) { ?>
	<td>&nbsp;&nbsp;|&nbsp;&nbsp;</td>
	<td><button class="control-button" name="previous_page_button" id="previous_page_button" accesskey="," >&larr;</button></td>
	<td class='label'><?php echo floor(($dataOffset / $gItemsPerPage) + 1) . " of " . ceil($dataSource->getDataListCount() / $gItemsPerPage ) ?></td>
	<td><button class="control-button" name="next_page_button" id="next_page_button" accesskey="." >&rarr;</button></td>
	<?php } ?>
</tr>
</table>
</div>

<?php } ?>

<?php if ($dataSource->getDataListCount() > 0) { ?>

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
		<td class='list'><a<?php echo ($rowNumber < 10 ? " accesskey='" . $rowNumber . "'" : "") ?> class="record-link" data-primary_id="<?php echo $dataRow['dealer_charge_id'] ?>"><?php echo htmlspecialchars($dataRow[$column['field_name']],ENT_QUOTES,"UTF-8") ?></a></td>
	<?php } ?>
	</tr>
	<?php } ?>
</table>

<?php } else { ?>

<div id="empty_list">
	No additional charges.
</div>

<?php } ?>

<div id="button_div">
	<button class="control-button" name="add_button" id="add_button" >Add Charge</button>
</div>

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

<?php include "scpt/admin_footer.inc"; ?>

<script>
function verifyFields() {
	var error = false;
	if (!error && ($("#description").val() == "" || $("#description").val().length < 1)) {
		error = showError($("#description"),"Description cannot be blank");
	}
	if (!error && ($("#amount").val() == "" || $("#amount").val().length < 1)) {
		error = showError($("#amount"),"Amount cannot be blank");
	}
	if (error === true) {
		return false;
	} else {
		return true;
	}
}
</script>
</body>
</html>