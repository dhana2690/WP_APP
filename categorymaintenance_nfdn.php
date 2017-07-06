<?php

################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################

$gProgramCode = "CATEGORYMAINTENANCENFDN";

include_once "scpt/utilities.inc";
include_once "scpt/class.datasource.php";

$gItemsPerPage = 30;
$controlListId = 2; // categories

$dealerArray = getDealerInfo($gDealerId);

$dataSource = new DataSource("categories");
$dataSource->setFilterWhere("dealer_id is null and inactive = 0"); //  and internal_use_only = 0
$dataSource->setDefaultSortOrder("department_id,sort_order,description");
$dataSource->setSortOrder($_POST['g_sort_order']);
$dataSource->setReverseSort($_POST['g_reverse_sort'] == "true");
$dataSource->setFilterText($_POST['filter_text']);
$dataSource->setSearchFields("description");
$additionalColumns = array(
	'department_name'=>'select description from departments where department_id = categories.department_id',
);
$dataSource->addAdditionalDataListColumns($additionalColumns);

if ($_POST['action'] == "save") {
	$returnArray = array();
	if (!empty($_POST['category_id'])) {
		$query = "select * from dealer_control_lists where dealer_id = ? and control_list_id = ? and control_list_item_id = ?";
		$resultSet = executeQuery($query,$gDealerId,$controlListId,$_POST['category_id']);
		if ($resultSet['row_count'] == 0) {
			$query = "insert into dealer_control_lists (dealer_id,control_list_id,control_list_item_id,internal_use_only,inactive,version) values (?,?,?,1,0,1)";
			$insertSet = executeQuery($query,$gDealerId,$controlListId,$_POST['category_id']);
			$returnArray['hidden'] = 'true';
		} else {
			$row = getNextRow($resultSet);
			$internalUseOnly = ($row['internal_use_only']==1?0:1);
			$query = "update dealer_control_lists set internal_use_only = ? where dealer_id = ? and control_list_id = ? and control_list_item_id = ?";
			$updateSet = executeQuery($query,$internalUseOnly,$gDealerId,$controlListId,$_POST['category_id']);
			$returnArray['hidden'] = ($internalUseOnly==1?'true':'');
		}
		$returnArray['status'] = 'success';
	} else {
		$returnArray['status'] = 'error';
		$returnArray['message'] = 'Item ID missing';
	}
	echo json_encode($returnArray);
	exit;
}

$dataOffset = $_POST['g_data_offset'];
if (empty($dataOffset) || !is_numeric($dataOffset)) {
	$dataOffset = 0;
}

$parameterList = array("start_row"=>$dataOffset,"row_count"=>$gItemsPerPage);
$dataList = $dataSource->getDataList($parameterList);
$filterText = $dataSource->getFilterText();

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

<div class="format_section" style="margin: 20px 140px;">

<?php if ($dataSource->getDataListCount() > $gItemsPerPage || !empty($filterText)) { ?>
<div id="filter_div">
<table>
<tr>
	<td class='label'>Search For</td>
	<td><input type="text" name="filter_text" id="filter_text" value="<?php echo htmlspecialchars($filterText,ENT_QUOTES,'UTF-8') ?>" /></td>
	<td><button name="filter_button" id="filter_button" >Search</button></td>
	<?php if (!empty($filterText)) { ?>
	<td><button name="list_all_button" id="list_all_button" >Clear Search</button></td>
	<?php } ?>
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
		<th class='list left'>Department</th>
		<th class='list left'>Category</th>
		<th class='list left'>Hidden</th>
	</tr>
	<?php
	$rowNumber = 0;
	foreach ($dataList as $dataRow) {
		$rowNumber++;
		$query  = "select * from dealer_control_lists where control_list_id = ? and control_list_item_id = ? ";
		$query .= "and dealer_id = ?";
		$resultSet = executeQuery($query,$controlListId,$dataRow['category_id'],$gDealerId);
		if ($row = getNextRow($resultSet)) {
			$internal_use_only = $row['internal_use_only'];
		} else {
			$internal_use_only = $dataRow['internal_use_only'];
		}
	?>
	<tr class='category_row' data-item_id='<?php echo $dataRow['category_id'] ?>'>
		<td class='list'><?php echo htmlspecialchars($dataRow['department_name'],ENT_QUOTES,"UTF-8") ?></td>
		<td class='list'><?php echo htmlspecialchars($dataRow['description'],ENT_QUOTES,"UTF-8") ?></td>
		<td class='list'>&nbsp;&nbsp;&nbsp;<input type="checkbox"<?php echo ($internal_use_only == 0 ? "" : " checked") ?>></td>
	</tr>
	<?php } ?>
</table>

<?php } else { ?>

<div id="empty_list">
	No additional categories.
</div>

<?php } ?>

</form>

</div> <!-- #main -->

<?php include_once "scpt/admin_footer.inc"; ?>

<script>
$(document).ready(function ($) {
	$(".category_row").mouseover(function(){
		$(this).css({"cursor":"pointer","background-color":"#e4e4e4"});
	});
	$(".category_row").mouseout(function(){
		$(this).css({"background-color":""});
	});
	$(".category_row").add(".on-off-checkbox").click(function() {
		if ($(this).prop("tagName") == "TR") {
			var row = $(this);
		} else {
			var row = $(this).closest("tr");
		}
		$.ajax({
			url: $("#g_php_self").val(),
			type: "POST",
			data: {action:'save',category_id:$(row).data('item_id')},
			success: function(returnArray) {
				if (returnArray['status'] == 'success') {
					$(row).find('input:checkbox').prop("checked",returnArray['hidden']);
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				alert("System Error: " + errorThrown + ". Please try again!");
			},
			dataType: "json"
		});
		return false;
	});
});
</script>
</body>
</html>