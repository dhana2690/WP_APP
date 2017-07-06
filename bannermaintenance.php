<?php

################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################

$gProgramCode = "BANNERMAINT";
include_once "scpt/utilities.inc";

$dealerArray = getDealerInfo($gDealerId);

$gItemsPerPage = 20;

$urlAction = $_GET['url_action'];
switch ($urlAction) {
	case "save":
		$parameters = array();
		$parameters[] = $_POST['description'];
		$parameters[] = $_POST['brand'];
		$parameters[] = $_POST['banner_link'];
		$parameters[] = $_POST['banner_link_sandbox'];
		$parameters[] = $_POST['banner_category_id'];
		$parameters[] = ($_POST['mall_exclusive'] == 'Y' ? 1 : 0);
		$parameters[] = $_POST['sort_order'];
		$parameters[] = ($_POST['internal_use_only'] == 'Y' ? 1 : 0);
		$parameters[] = ($_POST['inactive'] == 'Y' ? 1 : 0);
                $parameters[]=($_POST['is_newwindow'] == 'Y' ? 1 : 0);
		if (!empty($_POST['primary_id'])) {
			$parameters[] = $_POST['primary_id'];	
			$query  = "update banners set description = ?, brand = ?, banner_link = ?, banner_link_sandbox = ?, banner_category_id = ?, ";
			$query .= "mall_exclusive = ?, sort_order = ?, internal_use_only = ?, inactive = ?,is_newwindow=?,version = version + 1 where banner_id = ?";
			$resultSet = executeQuery($query,$parameters);
			$bannerId = $_POST['primary_id'];
		} else { 
                        $query  = "insert into banners(banner_id,description,brand,banner_link,banner_link_sandbox,image_id,date_added,banner_category_id,mall_exclusive,sort_order,internal_use_only,inactive,is_newwindow,version)";
			$query .= " values(null,?,?,?,?,null,now(),?,?,?,?,?,?,1)";
                        $resultSet = executeQuery($query,$parameters);
			$bannerId = $resultSet['insert_id'];   
		}
			
		//process image if present
		if (!empty($_POST['image']) && file_exists('imagecache/' . $_POST['image'])) {
			switch($_POST['banner_category_id']) {
				case 1:
					$width = 954;
					$height = 539;
					break;
				case 5:
					$width = 954;
					$height = 270;
					break;
				case 2:
					$width = 200;
					$height = 0; // 0 = determine height in proportion to width
					break;
				case 3:
				default:
					$width = 175;
					$height = 112;
					break;
			}
			include_once "scpt/process_image_functions.inc";
			$fileType = resizeImage($_POST['image'],$width,$height);
			//resizeImage($_POST['image'],$width,$height);
			if (is_numeric($fileType) && $fileType != 99) {
				$fileContent = file_get_contents('imagecache/'.$_POST['image']);
				$resultSet = executeQuery("insert into images (image_id,file_content,version) values (null,?,1)",$fileContent);
				$imageId = $resultSet['insert_id'];
				if ($imageId > 0) {
					executeQuery("update banners set image_id = ? where banner_id = ?",$imageId,$bannerId);
				}
			}
		}
		break;

	case "delete":
		executeQuery("update banners set inactive = 1, version = version + 1 where banner_id = ?",$_POST['primary_id']);
		break;
}

$primaryId = $_GET['primary_id'];
$filterText = (empty($_POST['filter_text'])?$_POST['g_filter_text']:$_POST['filter_text']);

$dataOffset = $_POST['g_data_offset'];
if (empty($dataOffset) || !is_numeric($dataOffset)) {
	$dataOffset = 0;
}

$nextLocation = $_POST['g_next_location'];
if (!empty($nextLocation)) {
	header("Location: " . $nextLocation);
	exit;
}

$nextRecordId = "";
$previousRecordId = "";

$dataList = array();

$parameters = array();
$query  = "select * from banners where inactive = 0";
if (!empty($filterText)) {
	$query .= " and (banners.brand like ? or banners.description like ? or banners.detailed_description like ?)";
	$parameters[] = "%" . $filterText . "%";
	$parameters[] = "%" . $filterText . "%";
	$parameters[] = "%" . $filterText . "%";
}
$query .= " order by banner_category_id,brand";
$resultSet = executeQuery($query,$parameters);
	
$rowCount = $resultSet['row_count'];

$setPreviousRecordId = true;
while ($row = getNextRow($resultSet)) {
	if ($row['banner_id'] == $primaryId) {
		$setPreviousRecordId = false;
		$row = getNextRow($resultSet);
		$nextRecordId = $row['banner_id'];
		break;
	}
	if ($setPreviousRecordId === true) {
		$previousRecordId = $row['banner_id'];
	}
}

$limitStatement = "limit " . $dataOffset . "," . $gItemsPerPage;
$resultSet = executeQuery($query . " " . $limitStatement,$parameters);
while ($row = getNextRow($resultSet)) {
	$dataList[] = $row;
}

$urlPage = $_GET['url_page'];
switch ($urlPage) {
	case "new":
		$primaryId = "";
		$dataRow = array();
		$dataRow['sort_order'] = 0;
		$dataRow['mall_exclusive'] = 0;
		$dataRow['internal_use_only'] = 0;
		$dataRow['inactive'] = 0;
                $dataRow['is_newwindow']=0;
		$urlPage = "show";
		break;
	case "show":
		$resultSet = executeQuery("select * from banners where banner_id = ?",$primaryId);
		$dataRow = getNextRow($resultSet);
		if ($dataRow === false) {
			$urlPage = "list";
		}
		break;
	default:	
		$urlPage = "list";
		break;
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $gProgramTitle; ?></title>
	<meta name="description" content="">
	<link rel="stylesheet" href="tmpl/styles_admin.css?v=1.1">
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
		<input type="hidden" name="g_previous_record_id" id="g_previous_record_id" value="<?php echo $previousRecordId ?>" />
		<input type="hidden" name="g_next_record_id" id="g_next_record_id" value="<?php echo $nextRecordId ?>" />
		<input type="hidden" name="g_next_location" id="g_next_location" value="" />
		<input type="hidden" name="g_data_offset" id="g_data_offset" value="<?php echo $dataOffset ?>" />
		<input type="hidden" name="g_items_per_page" id="g_items_per_page" value="<?php echo $gItemsPerPage ?>" />
		<input type="hidden" name="g_data_count" id="g_data_count" value="<?php echo $rowCount ?>" />
		<input type="hidden" name="g_filter_text" id="g_filter_text" value="<?php echo $filterText ?>" />
		<input type="hidden" name="g_php_self" id="g_php_self" value="<?php echo $_SERVER['PHP_SELF'] ?>" />
		<input type="hidden" name="primary_id" id="primary_id" value="<?php echo $primaryId ?>" />
		<input type="hidden" name="version" id="version" value="<?php echo (empty($dataRow['version']) ? '1' : $dataRow['version']) ?>" />
		<input type="hidden" name="image" id="image" value="" />
		
		<?php
		
		switch ($urlPage) {
			case "new":
			case "show":
		?>
		
		<div class="format_section" style="margin: 20px 80px;">
		
			<table class="grid-table">
			<tr>
				<td class='label'>Image</td>
				<td>
					<table><tr>
						<td id="item_image" width="320">
							<?php if (!empty($dataRow['image_id'])) {
								$width = ($dataRow['banner_category_id'] == 1 ? 300 : 120);
								echo "<img src='/imagedb/image" . $dataRow['image_id'] . "-" . getImageHashCode($dataRow['image_id']) . ".jpg' width='$width'>";
							} ?>
						</td>
						<td width="140">
							<label class="upload_image" onclick="document.getElementById('photo_upload').click()">Upload Image</label>
							<input id="photo_upload" name="photo_upload" type="file" value="Upload Image"  accept="image/x-png, image/gif, image/jpeg" />
							<div id="photo_upload_message" style="margin: 6px 0 0 0;"></div>
						</td>
						<td><span class="label">
							Home Page Marquee: 954 x 270<br>
							Skyscraper Ad: 200 wide<br>
							Vendor Badge: 175 x 112</span>
						</td>
					</tr></table>
				</td>
			</tr>
			<tr>
				<td class='label'><label for="brand">Brand</label></td>
				<td><input type="text" name="brand" id="brand" size="30" maxlength="50" value="<?php echo htmlspecialchars($dataRow['brand'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['brand']) ?>" /></td>
			</tr>
			<tr>
				<td class='label'><label for="banner_link">Banner Link</label></td>
				<td><input type="text" name="banner_link" id="banner_link" size="60" maxlength="255" value="<?php echo htmlspecialchars($dataRow['banner_link'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['banner_link']) ?>" /></td>
			</tr>
			<tr>
				<td class='label'><label for="banner_link">Banner&nbsp;Link&nbsp;Sandbox</label></td>
				<td><input type="text" name="banner_link_sandbox" id="banner_link_sandbox" size="60" maxlength="255" value="<?php echo htmlspecialchars($dataRow['banner_link_sandbox'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['banner_link_sandbox']) ?>" /></td>
			</tr>
			<tr>
				<td class='label'><label for="description">Description</label></td>
				<td><input type="text" name="description" id="description" size="60" maxlength="255" value="<?php echo htmlspecialchars($dataRow['description'],ENT_QUOTES,'UTF-8') ?>" data-crc="<?php echo getCrc32($dataRow['description']) ?>" /></td>
			</tr>
			<tr>
				<td class='label'><label for="banner_category">Category</label></td>
				<td>
					<select name='banner_category_id' id='banner_category_id'>
					<option value=''>Select...</option>
					<?php
						$resultSet = executeQuery("select * from banner_categories where inactive = 0 order by description");
						while ($row = getNextRow($resultSet)) {
							echo "<option value='" . $row['banner_category_id'] . "'" . ($row['banner_category_id']==$dataRow['banner_category_id']?" selected":"") . ">" . $row['description'] . "</option>";
						}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="sort_order">Sort Order</label></td>
				<td><input type="text" name="sort_order" id="sort_order" size="3" value="<?php echo $dataRow['sort_order'] ?>" data-crc="<?php echo getCrc32($dataRow['sort_order']) ?>" /><span class='label'> <i>0 = random order</i></span></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="checkbox" name="mall_exclusive" id="mall_exclusive" value="Y"<?php echo ($dataRow['mall_exclusive'] == 1 ? " checked" : "") ?> data-crc="<?php echo getCrc32($dataRow['mall_exclusive']) ?>" /><span class='label'><label for="mall_exclusive">&nbsp;For Mall Only</label></span></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="checkbox" name="internal_use_only" id="internal_use_only" value="Y"<?php echo ($dataRow['internal_use_only'] == 1 ? " checked" : "") ?> data-crc="<?php echo getCrc32($dataRow['internal_use_only']) ?>" /><span class='label'><label for="internal_use_only">&nbsp;Hide From Websites</label></span></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="checkbox" name="inactive" id="inactive" value="Y"<?php echo ($dataRow['inactive'] == 1 ? " checked" : "") ?> data-crc="<?php echo getCrc32($dataRow['inactive']) ?>" /><span class='label'><label for="inactive">&nbsp;Inactive (deleted)</label></span></td>
			</tr>
                        <tr>
				<td></td>
				<td><input type="checkbox" name="is_newwindow" id="is_newwindow" value="Y"<?php echo ($dataRow['is_newwindow'] == 1 ? " checked" : "") ?> data-crc="<?php echo getCrc32($dataRow['is_newwindow']) ?>" /><span class='label'><label for="is_newwindow">&nbsp;Open advertisement in new tab</label></span></td>
			</tr>
			</table>
		
		</div>
		
		<div id="button_div">
			<table width='100%'><tr>
				<td>&nbsp;</td>
				<td width='250' align='center'>
				<button class="control-button" name="save_button" id="save_button" accesskey="s" >Save This Banner</button>
				<button class="control-button" name="add_button" id="add_button" accesskey="a" >New Banner</button>
				</td>
				<td width='200' align='center'>
					<button class="control-button" name="previous_button" id="previous_button" accesskey="," >&larr;</button>
					<button class="control-button" name="list_button" id="list_button" accesskey="l" >Back To List</button>
					<button class="control-button" name="next_button" id="next_button" accesskey="." >&rarr;</button>
				</td>
				<td>&nbsp;</td>
			</tr></table>
		</div>
				
		
		<?php
		break; //$urlPage = new or show
		
		default:
		?>
		
		<div id="filter_div">
		<table>
		<tr>
			<td class='label'>Search For</td>
			<td><input type="text" name="filter_text" id="filter_text" class="searchfield" size="15" value="<?php echo htmlspecialchars($filterText,ENT_QUOTES,'UTF-8') ?>" /></td>
			<td>&nbsp;<button class="control-button" name="filter_button" id="filter_button" >Search</button></td>
		
			<td>&nbsp;&nbsp;|&nbsp;&nbsp;</td>
			<td><button class="control-button" name="add_button_from_list" id="add_button_from_list" accesskey="a" >New Banner</button></td>
			<?php if ($rowCount > count($dataList)) { ?>
			<td>&nbsp;&nbsp;|&nbsp;&nbsp;</td>
			<td><button class="control-button" name="previous_page_button" id="previous_page_button" accesskey="," >&larr;</button></td>
			<td class='label'><?php echo (floor($dataOffset / $gItemsPerPage) + 1) . " of " . ceil($rowCount / $gItemsPerPage ) ?></td>
			<td><button class="control-button" name="next_page_button" id="next_page_button" accesskey="." >&rarr;</button></td>
			<?php } ?>
		</tr>
		</table>
		</div>
		
		<?php if (count($dataList)>0) { ?>
		
		<table class="grid-table" id="list_table">
			<tr>
			<th class='list left'><a class="column-header">Preview</a></th>
			<th class='list left'><a class="column-header">Brand</a></th>
			<th class='list left'><a class="column-header">Description</a></th>
			<th class='list left'><a class="column-header">Link</a></th>
			<th class='list center'><a class="column-header">Preferred</a></th>
			<th class='list center'><a class="column-header">Mall Only</a></th>
			<th class='list center'><a class="column-header">Hidden</a></th>
			</tr>
			<?php
			$rowNumber = 0;
			foreach ($dataList as $dataRow) {
				$rowNumber++;
			?>
			<tr class='list_row'>
			<td class='list center'><a class="record-link" data-primary_id="<?php echo $dataRow['banner_id']; ?>"><?php echo "<img src='/imagedb/image" . $dataRow['image_id'] . "-" . getImageHashCode($dataRow['image_id']) . ".jpg' width='" . ($dataRow['banner_category_id']==1?100:60) . "'>" ?></a></td>
			<td class='list left'><a class="record-link" data-primary_id="<?php echo $dataRow['banner_id']; ?>"><?php echo htmlspecialchars($dataRow['brand'],ENT_QUOTES,"UTF-8"); ?></a></td>
			<td class='list left'><a class="record-link" data-primary_id="<?php echo $dataRow['banner_id']; ?>"><?php echo htmlspecialchars($dataRow['description'],ENT_QUOTES,"UTF-8"); ?></a></td>
			<td class='list left'><a class="record-link" data-primary_id="<?php echo $dataRow['banner_id']; ?>"><?php echo htmlspecialchars($dataRow['banner_link'],ENT_QUOTES,"UTF-8"); ?></a></td>
			<td class='list center'><a class="record-link" data-primary_id="<?php echo $dataRow['banner_id']; ?>"><?php echo ($dataRow['sort_order']==1?"Y":""); ?></a></td>
			<td class='list center'><a class="record-link" data-primary_id="<?php echo $dataRow['banner_id']; ?>"><?php echo ($dataRow['mall_exclusive']==1?"Y":""); ?></a></td>
			<td class='list center'><a class="record-link" data-primary_id="<?php echo $dataRow['banner_id']; ?>"><?php echo ($dataRow['internal_use_only']==1?"Y":""); ?></a></td>
                        <td class='list center'><a class="record-link" data-primary_id="<?php echo $dataRow['banner_id']; ?>"><?php echo ($dataRow['is_newwindow']==1?"Y":""); ?></a></td>
			</tr>
			<?php } ?>
		</table>
		
		<?php } else { ?>
		
		<div id="empty_list">
			Search by Description or Brand.
		</div>
		
		<?php } ?>
		
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
	
	$('#photo_upload').on('change',(function(e) {
        e.preventDefault();
		var imgFile = $('#photo_upload').val().split('.').pop().toLowerCase();
		
		if($.trim(imgFile)) {
			var formData = new FormData();
			formData.append('file', $('#photo_upload')[0].files[0]);
			$("#item_image").html('<img src="tmpl/ajax-loader-bar.gif" alt="Uploading...."/>');
			
			if($.inArray(imgFile, ['gif','png','jpg','jpeg']) == -1) {
				$("#image").val("");
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
							$("#image").val(resData[1]); //will process and store in database on save
							$("#item_image").html("<img src='imagecache/" + resData[1] + "' style='max-width: 190px; max-height: 110px;'>");
							$("#photo_upload_message").text(resData[1] + ' was uploaded.').css("color","#1fc352");
						}
						else {						
							$("#image").val("");
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


	$("#filter_button_id").click(function() {
		if ($("#filter_text_id").val().length > 0 && $("#filter_text_id").val() != "") {
			$("#g_data_offset").val(0);
			$("#edit_form").attr("action",$("#g_php_self").val()+"?url_page=list").submit();
		} else {
			return false;
		}
	});
	
	$("#filter_text_id").keypress(function(event) {
		if (event.which == 13) {
			if ($("#filter_text_id").val().length > 0 && $("#filter_text_id").val() != "") {
				$("#g_data_offset").val(0);
	   			$("#edit_form").attr("action",$("#g_php_self").val()+"?url_page=list").submit();
			} else {
				return false;
			}
	  	}
	});
	
	$("#filter_text").focus(function() {
		$("#g_filter_text_id").val("");
		$("#filter_text_id").val("");
	});
	$("#filter_text_id").focus(function() {
		$("#g_filter_text").val("");
		$("#filter_text").val("");
	});
	
});

function verifyFields() {
	var error = false;
	if (!error && ($("#brand").val() == "" || $("#brand").val().length < 1)) {
		error = showError($("#brand"),"Brand cannot be blank");
	}
	if (!error && ($("#banner_link").val() == "" || $("#banner_link").val().length < 1)) {
		error = showError($("#banner_link"),"Banner Link cannot be blank");
	}
	if (!error && ($("#description").val() == "" || $("#description").val().length < 1)) {
		error = showError($("#description"),"Description cannot be blank");
	}
	if (!error && ($("#banner_category_id").val() == "" || $("#banner_category_id").val().length < 1)) {
		error = showError($("#banner_category_id"),"Select a category");
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
