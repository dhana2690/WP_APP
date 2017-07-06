<?php

################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################

$gProgramCode = "CATEGORYPROFITMAINTENANCE";
include_once "scpt/utilities.inc";
include_once "scpt/class.datasource.php";

$dealer_id=$gDealerId;
// if admin, update the default set that gets used when new dealers are created
if ($gAdministratorFlag) {
	$resultSet = executeQuery("select * from category_profit where inactive = 0 and dealer_id is null");
	while ($row = getNextRow($resultSet)) {
		$query = "select avg(profit_percent) as profit_percent from category_profit where category_id = ? and dealer_id is not null";
		$averageResult = executeQuery($query,$row['category_id']);
		$averageRow = getNextRow($averageResult);
		executeQuery("update category_profit set profit_percent = ? where category_profit_id = ?",$averageRow['profit_percent'],$row['category_profit_id']);
	}
}



			//category profit table
			// first select all categories not currently in the dealer's category_profit table
			//   - this adds new categories as necessary
			$query = "select * from categories where inactive = 0 and dealer_id is null and category_id not in (select category_id from category_profit where ";
			$query .= " dealer_id ".($gAdministratorFlag ? "is null" : "= ".$dealer_id).")";
			$resultSet = executeQuery($query);
			if ($resultSet['row_count'] > 0) {
				while ($row = getNextRow($resultSet)) {
					// get the current average of all dealers for this category
					$query = "select avg(profit_percent) as profit_percent from category_profit where category_id = ?";
					$averageResult = executeQuery($query,$row['category_id']);
					$averageRow = getNextRow($averageResult);
					// if there is no average in this category, use a default of 20%
					$averageProfit = (empty($averageRow['profit_percent']) ? 20 : $averageRow['profit_percent']);
					$query  = "insert into category_profit (category_id,department_id,dealer_id,profit_percent,sort_order,internal_use_only,inactive,version) " ;
					// now insert a new record
					$query .= "values (?,?,".($gAdministratorFlag ? "null" : $dealer_id).",?,0,0,0,1)";
					executeQuery($query,$row['category_id'],$row['department_id'],$averageProfit);
				}			
			}


$dealerArray = getDealerInfo($gDealerId);
$dataSource = new DataSource('category_profit');
$dataSource->setFilterWhere('inactive = 0 and dealer_id ' . ($gAdministratorFlag?' is null':' = ' . $gDealerId) );
$dataSource->setDefaultSortOrder('department_id,category_id');
$dataSource->setSortOrder($_POST['g_sort_order']);
$dataSource->setReverseSort($_POST['g_reverse_sort'] == "true");
$dataSource->setFilterText($_POST['filter_text']);
$dataSource->setSearchFields('category_id'); //doesn't work with additional columns?
$additionalColumns = array(
	'department_name'=>'select description from departments where department_id = category_profit.department_id',
	'category_name'=>'select description from categories where category_id = category_profit.category_id'
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
		$dataSource->setPrimaryId($_GET['primary_id']);
		$dataRow = $dataSource->getRow();
		if ($dataRow === false) {
			$urlPage = "list";
		} else {
			$nextRecordId = $dataSource->getNextRecordId();
			$previousRecordId = $dataSource->getPreviousRecordId();
			$showAfterSave = $primaryId;
		}
		break;
	default:
		$listColumnData = array();
		$listColumnData[] = array("field_name"=>"department_name","sort_order"=>"department_name","label"=>"Department");
		$listColumnData[] = array("field_name"=>"category_name","sort_order"=>"category_name","label"=>"Category");
		$listColumnData[] = array("field_name"=>"profit_percent","sort_order"=>"profit_percent","label"=>"Default %");
		$listColumnData[] = array("field_name"=>"use_markup_amount","sort_order"=>"use_markup_amount","label"=>"Use Markup");
		$listColumnData[] = array("field_name"=>"markup_amount","sort_order"=>"markup_amount","label"=>"Default $");
		$dataList = $dataSource->getDataList();
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

<form name="edit_form" id="edit_form" method="post">
<input type="hidden" name="g_sort_order" id="g_sort_order" value="<?php echo $dataSource->getSortOrder(true) ?>" />
<input type="hidden" name="g_reverse_sort" id="g_reverse_sort" value="<?php echo ($dataSource->getReverseSort() ? "true" : "false") ?>" />
<input type="hidden" name="g_previous_record_id" id="g_previous_record_id" value="<?php echo $previousRecordId ?>" />
<input type="hidden" name="g_next_record_id" id="g_next_record_id" value="<?php echo $nextRecordId ?>" />
<input type="hidden" name="g_next_location" id="g_next_location" value="" />
<input type="hidden" name="g_php_self" id="g_php_self" value="<?php echo $_SERVER['PHP_SELF'] ?>" />
<input type="hidden" name="primary_id" id="primary_id" value="<?php echo $dataSource->getPrimaryId() ?>" />
<input type="hidden" name="version" id="version" value="<?php echo (empty($dataRow['version']) ? '1' : $dataRow['version']) ?>" />

<?php

switch ($urlPage) {
	case "new":
	case "show":
?>

<div class="format_section" style="margin: 20px 20px;">
	<div style="font-size: 18px; color: #666; text-align: center; margin: 0 0 12px 0; padding: 6px 0;">
		<?php echo getFieldFromId('description','departments','department_id',$dataRow['department_id'],''); ?> › <?php echo getFieldFromId('description','categories','category_id',$dataRow['category_id'],''); ?>
	</div>

	<table class="grid-table">
		<tr>
			<td valign="top" style="border: <?php echo ($dataRow['use_markup_amount'] == 0 ? "3px" : "1px") ?> solid #ccc; padding: 12px;">
				<h2><input type="checkbox" class="switch" id="use_profit"<?php echo ($dataRow['use_markup_amount'] == 0 ? " checked" : "") ?>> Profit Margin</h2>
				<table class="grid-table">
					<tr><td class="description" colspan="2"><br>Default profit percent</td></tr>
					<tr>
						<td class='label' width="135">Profit Percent</td>
						<td><input type="text" name="profit_percent" id="profit_percent" class="numeric" size="4" value="<?php echo htmlspecialchars($dataRow['profit_percent'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['profit_percent']) ?>" />%</td>
					</tr>
	
					<tr><td class="description" colspan="2"><br>First cost level and profit percent (optional)</td></tr>
					<tr>
						<td class='label'>Dealer Cost Under $</td>
						<td><input type="text" name="low_profit_amount" id="low_profit_amount" class="numeric" size="10" value="<?php echo htmlspecialchars($dataRow['low_profit_amount'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['low_profit_amount']) ?>" /></td>
					</tr>
					<tr>
						<td class='label'>Profit Percent</td>
						<td><input type="text" name="low_profit_percent" id="low_profit_percent" class="numeric" size="4" value="<?php echo htmlspecialchars($dataRow['low_profit_percent'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['low_profit_percent']) ?>" />%</td>
					</tr>
					<tr><td class="description" colspan="2"><br>Second cost level and profit percent (optional)</td></tr>
					<tr>
						<td class='label'>Dealer Cost Under $</td>
						<td><input type="text" name="middle_profit_amount" id="middle_profit_amount" class="numeric" size="10" value="<?php echo htmlspecialchars($dataRow['middle_profit_amount'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['middle_profit_amount']) ?>" /></td>
					</tr>
					<tr>
						<td class='label'>Profit Percent</td>
						<td><input type="text" name="middle_profit_percent" id="middle_profit_percent" class="numeric" size="4" value="<?php echo htmlspecialchars($dataRow['middle_profit_percent'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['middle_profit_percent']) ?>" />%</td>
					</tr>
				</table>
				
			</td>
			
			<td>&nbsp;&nbsp;&nbsp;</td>

			<td valign="top" style="border: <?php echo ($dataRow['use_markup_amount'] == 1 ? "3px" : "1px") ?> solid #ccc; padding: 12px;">
				<h2><input type="checkbox" class="switch" name="use_markup_amount" id="use_markup"<?php echo ($dataRow['use_markup_amount'] == 1 ? " checked" : "") ?> value="Y"> Markup Amount</h2>
				<table class="grid-table">
					<tr><td class="description" colspan="4"><br>Default amounts</td></tr>
					<tr>
						<td class='label' width="135">Markup Amount $</td>
						<td width="120"><input type="text" name="markup_amount" id="markup_amount" class="numeric" size="8" value="<?php echo htmlspecialchars($dataRow['markup_amount'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['markup_amount']) ?>" /></td>
						<td class='label' width="135">Round to Nearest $</td>
						<td>
							<select name="roundup_amount" id="roundup_amount">
								<option value="">Select...</option>
								<option value="0.99"<?php echo ($dataRow['roundup_amount'] == "0.99" ? " selected" : "") ?>>99&cent;</option>
								<option value="9.99"<?php echo ($dataRow['roundup_amount'] == "9.99" ? " selected" : "") ?>>$9.99</option>
							</select>
						</td>
					</tr>

					<tr><td class="description" colspan="4"><br>First cost level and amounts (optional)</td></tr>
					<tr>
						<td class='label'>Dealer Cost Under $</td>
						<td colspan="3"><input type="text" name="low_markup_cost" id="low_markup_cost" class="numeric" size="8" value="<?php echo htmlspecialchars($dataRow['low_markup_cost'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['low_markup_cost']) ?>" /></td>
					</tr>
					<tr>
						<td class='label'>Markup Amount $</td>
						<td><input type="text" name="low_markup_amount" id="low_markup_amount" class="numeric" size="8" value="<?php echo htmlspecialchars($dataRow['low_markup_amount'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['low_markup_amount']) ?>" /></td>
						<td class='label'>Round to Nearest $</td>
						<td>
							<select name="low_roundup_amount" id="low_roundup_amount">
								<option value="">Select...</option>
								<option value="0.99"<?php echo ($dataRow['low_roundup_amount'] == "0.99" ? " selected" : "") ?>>99&cent;</option>
								<option value="9.99"<?php echo ($dataRow['low_roundup_amount'] == "9.99" ? " selected" : "") ?>>$9.99</option>
							</select>
						</td>
					</tr>
					<tr><td class="description" colspan="4"><br>Second cost level and amounts (optional)</td></tr>
					<tr>
						<td class='label'>Dealer Cost Under $</td>
						<td colspan="3"><input type="text" name="middle_markup_cost" id="middle_markup_cost" class="numeric" size="8" value="<?php echo htmlspecialchars($dataRow['middle_markup_cost'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['middle_markup_cost']) ?>" /></td>
					</tr>
					<tr>
						<td class='label'>Markup Amount $</td>
						<td><input type="text" name="middle_markup_amount" id="middle_markup_amount" class="numeric" size="8" value="<?php echo htmlspecialchars($dataRow['middle_markup_amount'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['middle_markup_amount']) ?>" /></td>
						<td class='label'>Round to Nearest $</td>
						<td>
							<select name="middle_roundup_amount" id="middle_roundup_amount">
								<option value="">Select...</option>
								<option value="0.99"<?php echo ($dataRow['middle_roundup_amount'] == "0.99" ? " selected" : "") ?>>99&cent;</option>
								<option value="9.99"<?php echo ($dataRow['middle_roundup_amount'] == "9.99" ? " selected" : "") ?>>$9.99</option>
							</select>
						</td>
					</tr>
				</table>
			</td>			
		</tr>
	</table>
		
	<div id="button_div" style="margin: 21px 0 0 0;">
		<table width='100%'><tr>
			<td>&nbsp;</td>
			<td width='200' align='center'>
				<button class="control-button" name="custom_save_button" id="custom_save_button" accesskey="s" >Save Margins &amp; Markups</button>
			</td>
			<td width='200' align='center'>
				<button class="control-button" name="previous_button" id="previous_button" accesskey="," >&larr;</button>
				<button class="control-button" name="list_button" id="list_button" accesskey="l" >Back To List</button>
				<button class="control-button" name="next_button" id="next_button" accesskey="." >&rarr;</button>
			</td>
			<td>&nbsp;</td>
		</tr></table>
	</div>

	<div id="save_message" class="error_div hidden-element">Category Profit &amp; Markup info saved</div>

	<?php
	$errorMessage = $dataSource->getErrorMessage();
	if (!empty($errorMessage)) {
		echo "<div class='error_div'>$errorMessage</div>";
	} 
	?>

</div> <!-- format_section -->

<?php
break;
default:
?>

<div class="format_section" style="margin: 0 120px;">

<table class="grid-table" id="list_table">
	<tr>
	<?php foreach ($listColumnData as $column) { ?>
		<th class='list <?php echo ($column['label'] == "Use Markup" ? "center" : "left") ?>'><a class="column-header" data-sort_order="<?php echo $column['sort_order'] ?>"><?php echo $column['label'] ?><?php echo ($dataSource->getSortOrder(true) == $column['sort_order'] ? "&nbsp;" . ($dataSource->getReverseSort() ? "&uarr;" : "&darr;") : "") ?></a></th>
	<?php } ?>
	</tr>
	<?php
	$rowNumber = 0;
	foreach ($dataList as $dataRow) {
		$rowNumber++;
	?>
	<tr class='list_row'>
	<?php foreach ($listColumnData as $column) { ?>
		<td class='list<?php echo ($column['field_name'] == "use_markup_amount" ? " center" : "") ?>'><a<?php echo ($rowNumber < 10 ? " accesskey='" . $rowNumber . "'" : "") ?> class="record-link" data-primary_id="<?php echo $dataRow['category_profit_id'] ?>">
		<?php
			if ($column['field_name'] == "use_markup_amount") {
				echo ($dataRow[$column['field_name']] == 1 ? "<img src='tmpl/icon_checkmark.gif' height='10'>" : "");
			} else {
				echo htmlspecialchars($dataRow[$column['field_name']],ENT_QUOTES,"UTF-8");
			}
		?>
		</a></td>
	<?php } ?>
	</tr>
	<?php } ?>
</table>

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
$(document).ready(function() {

	$(".switch").change(function(){
		switch($(this).attr("id")) {
			case "use_profit":
				$("#use_markup").attr("checked",false);
				$("#use_markup").closest("td").css("border-width","1px");
				break;
			case "use_markup":
				$("#use_profit").attr("checked",false);
				$("#use_profit").closest("td").css("border-width","1px");
				break;
		}
		$(this).closest("td").css("border-width","3px");
	});

	$("#custom_save_button").click(function() {
		$(".control-button").prop("disabled",true);
		if (verifyFields()) {
			submitForm("<?php echo ($showAfterSave == "new" ? "list" : "show") ?>","save","","<?php echo ($showAfterSave == "new" ? "" : $dataSource->getPrimaryId()) ?>");
		} else {
			$(".control-button").prop("disabled",false);
		}
		return false;
	});

	<?php if ($_GET['url_action'] == 'save' && empty($errorMessage)) { ?>
		$("#save_message").slideDown(300).delay(2000).slideUp(300);
	<?php } ?>

});


function verifyFields() {
	var error = false;
	if ($("#use_profit").is(":checked")) {
		if ($("#profit_percent").val() == "" || $("#profit_percent").val().length < 1) {
			$("#profit_percent").css("background-color","#fff6c7");
			var position = $("#profit_percent").position();
			$("#error_tip_message").text("Profit percent cannot be blank");
			$("#error_tip").css({"left":position.left+$("#profit_percent").width()-24,"top":position.top-$("#profit_percent").height()-15}).show();
			error = true;
		}
	} else {
		if ($("#markup_amount").val() == "" || $("#markup_amount").val().length < 1) {
			$("#markup_amount").css("background-color","#fff6c7");
			var position = $("#markup_amount").position();
			$("#error_tip_message").text("Markup amount cannot be blank");
			$("#error_tip").css({"left":position.left+$("#markup_amount").width()-24,"top":position.top-$("#markup_amount").height()-15}).show();
			error = true;
		}
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