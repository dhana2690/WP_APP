<?php
################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################
$gProgramCode = "CATALOG";
include_once $_SERVER['DOCUMENT_ROOT'] . "/scpt/utilities.inc";
include_once $_SERVER['DOCUMENT_ROOT'] . "/scpt/class.shoppingcart.php";
$dealerArray = getDealerInfo($gDealerId);

//dealer hidden categories 
$qryset = executeQuery("select * from dealer_control_lists where dealer_id = ? and control_list_id=2 and internal_use_only=1",$gDealerId);
while($result = getNextRow($qryset)){
    $dlrHiddenCatId[] = $result['control_list_item_id'];
} 


$headerInfo = $_SERVER['REMOTE_ADDR']."||".$_SERVER['HTTP_HOST']."||".$_SERVER['REQUEST_URI']."||".$_SERVER['PHP_SELF'];

if((!isset($_SESSION[$globalSystemCode]['global_user_id']) || empty($_SESSION[$globalSystemCode]['global_user_id'])) && $dealerArray['enable_global_login'] == 1) 
{
    header("Location:http://".$dealerArray['dealer_url']);
}
$referingPage = $_SERVER['HTTP_REFERER'];
if (count($_GET) == 0) {
	header("Location: $referingPage");
	exit;
}
if ($gDealerId > 1 && $gDealerId != $gDefaultDealerId && !in_array($gDealerId, $master_id_array)) {
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
$gItemsPerPage = 30;
$gRowsShown = 0;
if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
	$currentPage = (int) $_GET['page'];
} else {
	$currentPage = 1;
}
$caliber_id = (!empty($_GET['caliber']) && is_numeric($_GET['caliber']) ? $_GET['caliber'] : null );
$manufacturer_id = (!empty($_GET['manufacturer']) && is_numeric($_GET['manufacturer']) ? $_GET['manufacturer'] : null );
$showManufacturerOptions = true;
$useCacheData = true;
$cacheResultSet = array();
$cachedCatalogIds = array_fill_keys(array(1,2,3,4,5,6,7,8,9,10,11,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,62,63,64,66,67,69,70,100104,100660,100712,100713,100714,100823,100827,100828,100837,101088,103367), null);
if (!empty($_GET['category']) && class_exists(Memcache) && $memcache = new Memcache()) 
{
        foreach ($gMemcacheServers as $server) 
	{
        	$memcache->addServer($server);
        }
	foreach (explode(',',$_GET['category']) as $categoryId) 
	{
	    if(empty($categoryId)) continue;
	    if(!array_key_exists($categoryId, $cachedCatalogIds))
	    {
	      $useCacheData = false; 
	      break;
	    }
	    $memcacheKey = "Catalog_Products_" . $categoryId;
	    $dataAry = array();
            if($dataStr = $memcache->get($memcacheKey)) 
	    {
	       $dataAry = json_decode($dataStr, true);
	    }
	    else
	    {
	       $cacheQuery = "select product_id,products.caliber_id,products.manufacturer_id, distributor_id from distributor_inventory left join distributors using (distributor_id) left join products using (product_id) left join categories using (category_id) " .
	       "where products.internal_use_only = 0 and products.inactive = 0 and products.dealer_id is null and categories.inactive = 0  and distributors.inactive = 0 " .
	       "and distributor_inventory.quantity > 0  and products.category_id = " . $categoryId . " group by products.product_id,distributor_inventory.distributor_id ";
	       $resultSet = executeQuery($cacheQuery);
	       while ($row = getNextRow($resultSet)) 
	       {
	          $currData = array();
		  $currData['p'] = $row['product_id'];
		  $currData['c'] = $row['caliber_id'];
		  $currData['m'] = $row['manufacturer_id'];
		  $currData['d'] = $row['distributor_id'];
		  $currData['cId'] = $categoryId;
	          $dataAry[] = $currData;
	       }
	       $memcache->set($memcacheKey, json_encode($dataAry), false, 57600); 
	    }
	    foreach($dataAry as $currData)
	    {
	       $cacheResultSet[] = $currData;
	    }
	}
}
else
{
   $useCacheData = false;
}
$titleArray = array();
$storeItemParameters = array();
$storeItemParameters[] = $gDealerId;
$storeItemQuery  = "select product_id,quantity_in_stock,caliber_id,manufacturer_id from dealer_product_data left join products using (product_id) ";
$storeItemQuery .= "where dealer_product_data.inactive = 0 and dealer_product_data.quantity_in_stock > 0 and dealer_product_data.dealer_id = ? ";
$storeItemQuery .= "and products.internal_use_only = 0 and products.inactive = 0 ";
$itemParameters = array();
$itemQuery  = "select product_id,products.caliber_id,products.manufacturer_id,sum(distributor_inventory.quantity) as quantity,products.category_id ";
$itemQuery .= "from distributor_inventory left join distributors using (distributor_id) left join products using (product_id) ";
$itemQuery .= "left join categories using (category_id) ";
$itemQuery .= "where products.internal_use_only = 0 and products.inactive = 0 and products.dealer_id is null ";
$itemQuery .= "and categories.inactive = 0  and distributors.inactive = 0 and quantity > 0 ";
// block items from exception list for this dealer's state?
if ($dealerArray['ignore_exceptions'] == 0) {
	$exceptionListBlock = getFieldFromId('exception_list_id','exception_lists','state',$dealerArray['dealer_state'],'allowed = 0');
	if (!empty($exceptionListBlock)) {
		$itemQuery .= "and product_id not in (select product_id from exception_list_products where exception_list_id = ?) ";
		$itemParameters[] = $exceptionListBlock;
                $storeItemQuery .=  "and product_id not in (select product_id from exception_list_products where exception_list_id = ?) ";
                $storeItemParameters[] = $exceptionListBlock;               
	    if($useCacheData)
	    {
	       $expProducts[] = exceptionBlockList($exceptionListBlock);
	       $expProductLookup = array_fill_keys($expProducts, null);
	       foreach($cacheResultSet as $idx => $currData)
	       {
	          if(array_key_exists($currData['p'], $expProductLookup))
	          {
	             unset($cacheResultSet[$idx]);
	          }
	       }
	    }
	}
}
// limit to items carried by distributors allowed for this dealer
if (!empty($dealerArray['distributorSet'])) {
	$itemQuery .= "and distributor_inventory.distributor_id in (" . $dealerArray['distributorSet'] . ") ";
	if($useCacheData)
	{
	   $allowedDistributors = array_fill_keys(explode(",", $dealerArray['distributorSet']), null);
	   foreach($cacheResultSet as $idx => $currData)
	   {
	      if(!array_key_exists($currData['d'], $allowedDistributors))
	      {
	         unset($cacheResultSet[$idx]);
	      }
	   }
	}
}


$exceptManufacturer=array(); 

$checkSet = executeQuery("select control_list_item_id from dealer_control_lists where dealer_id = ? and internal_use_only = 1 and control_list_id = 3 and control_list_item_id in (" . implode(",", $dealerExceptionManufacturers) . ")", $gDealerId);

        if ($checkSet['row_count'] > 0) {  
            while ($row = getNextRow($checkSet)) {
                $exceptManufacturer[]=$row['control_list_item_id'];
            } 
             
        } 

$detailQuery = "";
if (!empty($_GET['search_for'])) {
        $useCacheData = false;
	$searchFor = preg_replace("/[^ \w-]+/", "", trim(htmlentities($_GET['search_for'])));
		
	if($searchFor!=$_GET['search_for'])
	{
  $redirect="catalog.php?search_for=$searchFor";
  header("HTTP/1.1 301 Moved Permanently");
  header("Location: $redirect"); // temporary redirect
  echo "<html></html>";  // - Tell the browser there the page is done
  flush();               // - Make sure all buffers are flushed
  ob_flush();            // - Make sure all buffers are flushed
  exit;    
    }

	if (!empty($searchFor)) {
		$resultSet = executeQuery("select * from search_strings where search_string = ?",$searchFor);
		if ($row = getNextRow($resultSet)) {
			executeQuery("update search_strings set use_count = use_count + 1 where search_string_id = ?",$row['search_string_id']);
		} else {
			executeQuery("insert into search_strings (search_string_id,search_string,use_count,version) values " .
				"(null,?,1,1)",strtolower($searchFor));
		}
	}
	$search_in = $_GET['search_in'];
	$titleArray[] = "Search Results";
	$titleArray[] = $_GET['search_for'];
	if (!empty($search_in) && is_numeric($search_in)) {
		$department_id = $search_in;
		$categorySet = array();
		$result = executeQuery("select * from categories where inactive = 0 and department_id = ?",$department_id);
		while ($row = getNextRow($result)) {
			if (!empty($row['category_id'])) {
				$categorySet[] = $row['category_id'];
			}
		}
		if (count($categorySet) > 0) {
			$detailQuery .= "and products.category_id in (" . implode(",",$categorySet) . ") ";
		}
		$titleArray[] = getFieldFromId("description","departments","department_id",$department_id);
	}
	
	$detailQuery .= "and (products.description like ? or products.detailed_description like ? or products.upc like ?) ";
	$storeItemParameters[] = "%" . $searchFor . "%";
	$storeItemParameters[] = "%" . $searchFor . "%";
	$storeItemParameters[] = "%" . $searchFor . "%";
	$itemParameters[] = "%" . $searchFor . "%";
	$itemParameters[] = "%" . $searchFor . "%";
	$itemParameters[] = "%" . $searchFor . "%";
	
	// are there any hidden departments or categories?
        $hiddenCategories = array();
        // first find hidden departments and add all catetories in that department to the list
        $depQuery  = "select department_id from departments where dealer_id is null and inactive = 0 and internal_use_only = 1 ";
        $depQuery .= "and department_id not in (select control_list_item_id from dealer_control_lists where dealer_id = ? and control_list_id = 1 ";
        $depQuery .= "and control_list_item_id = departments.department_id and internal_use_only = 0) ";
        $depQuery .= "or department_id in (select control_list_item_id from dealer_control_lists where dealer_id = ? and control_list_id = 1 ";
        $depQuery .= "and control_list_item_id = departments.department_id and internal_use_only = 1) ";
        $depQuery .= "order by sort_order,description";
        $resultSet = executeQuery($depQuery, $gDealerId,$gDealerId);
        while ($row = getNextRow($resultSet)) {
            $catResult = executeQuery("select category_id from categories where department_id = ? and dealer_id is null", $row['department_id']);
            while ($categoryRow = getNextRow($catResult)) {
                $hiddenCategories[] = $categoryRow['category_id'];
            }
        }
	// next add any additional hidden categories to the list
        $departmentQuery  = "select department_id from departments where dealer_id is null and inactive = 0 and internal_use_only = 0 ";
        $departmentQuery .= "and department_id not in (select control_list_item_id from dealer_control_lists where dealer_id = ? and control_list_id = 1 ";
        $departmentQuery .= "and control_list_item_id = departments.department_id and internal_use_only = 1) ";
        $departmentQuery .= "or department_id in (select control_list_item_id from dealer_control_lists where dealer_id = ? and control_list_id = 1 ";
        $departmentQuery .= "and control_list_item_id = departments.department_id and internal_use_only = 0) ";
        $departmentQuery .= "order by sort_order,description";
        $resultSet = executeQuery($departmentQuery,$gDealerId,$gDealerId);
        while ($row = getNextRow($resultSet)) {
        $departmentId = $row['department_id'];
        $catQuery  = "select category_id from categories where dealer_id is null and department_id = ? and inactive = 0 and (internal_use_only = 1 ";
        $catQuery .= "and category_id not in (select control_list_item_id from dealer_control_lists where dealer_id = ? and control_list_id = 2 ";
        $catQuery .= "and control_list_item_id = categories.category_id and internal_use_only = 0) ";
        $catQuery .= "or category_id in (select control_list_item_id from dealer_control_lists where dealer_id = ? and control_list_id = 2 ";
        $catQuery .= "and control_list_item_id = categories.category_id and internal_use_only = 1)) ";
        $catQuery .= "order by sort_order,description";
        $categoryResult = executeQuery($catQuery,$row['department_id'],$gDealerId,$gDealerId);
            while ($row = getNextRow($categoryResult)) {
                if (!in_array($row['category_id'], $hiddenCategories)) {
                    $hiddenCategories[] = $row['category_id'];
                }
            }
        }
	
	if (count($hiddenCategories) > 0) {
		$detailQuery .= "and products.category_id not in (" . implode(",",$hiddenCategories) . ") ";
	}
        
} 
$excludeArray = array();
if (!empty($_GET['category'])) {
	$categorySet = array();
	foreach (explode(',',$_GET['category']) as $categoryId) {
		if (!empty($categoryId)) {
			// is this a hidden category?
			$hiddenCategory = getFieldFromId('control_list_item_id','dealer_control_lists','control_list_item_id',$categoryId,'control_list_id = 2 and dealer_id = ' . $gDealerId . ' and internal_use_only = 1');
			// if so, don't add it to the list
			if (!is_numeric($hiddenCategory)) {
				$categoryId = getFieldFromId("category_id","categories","category_id",$categoryId);
				$categorySet[] = $categoryId;
			}
	                else if($useCacheData)
	                {
	                   foreach($cacheResultSet as $idx => $currData)
	                   {
	                      if($currData['cId'] == $categoryId)
	                      {
	                         unset($cacheResultSet[$idx]);
	                      }
	                   }
	                }
		}
	}
	if (count($categorySet) > 1) {
		$categoryList = "";
		foreach ($categorySet as $categoryId) {
			if (!empty($categoryId) && is_numeric($categoryId)) {
				$categoryId = getFieldFromId("category_id","categories","category_id",$categoryId);
				if (!empty($categoryId)) {
					if (!empty($categoryList)) {
						$categoryList .= ",";
					}
					$categoryList .= $categoryId;
				}
			}
		}
		if (!empty($categoryList)) {
			$department_id = getFieldFromId("department_id","categories","category_id",$categorySet[0]);
			$titleArray[] = getFieldFromId("description","departments","department_id",$department_id);
			$detailQuery .= "and products.category_id in (" . $categoryList . ") ";
		}
	} else if (count($categorySet) == 1) {
		$category_id = $categorySet[0];
		$department_id = getFieldFromId("department_id","categories","category_id",$category_id);
		$titleArray[] = getFieldFromId("description","departments","department_id",$department_id);
		$titleArray[] = getFieldFromId("description","categories","category_id",$category_id);
		$detailQuery .= "and products.category_id = ? ";
		$storeItemParameters[] = $category_id;
		$itemParameters[] = $category_id;
		
		if ($currentPage == 1 && empty($manufacturer_id)) { // only need features for 1st page
			$featureArray = array();
			$featureDealerId = ($dealerArray['use_mall_features'] == 1 ? 1 : $gDealerId);
			$query  = "select ptl.product_id,p.manufacturer_id from product_tag_links ptl left join products p using (product_id) ";
			$query .= "where ptl.dealer_id = ? and ptl.product_tag_id = 2 and p.category_id = ? and p.inactive = 0";
			$resultSet = executeQuery($query,$featureDealerId,$category_id);
			while ($row = getNextRow($resultSet)) {
				$featureArray[] = $row['product_id'];
			}
			shuffle($featureArray);
			
			if (count($featureArray)>0) {
				$rowArray = array();
				for ($i = 0; $i < count($featureArray); $i++) {
					$rowArray[] = $featureArray[$i];
					if (count($rowArray) == 3) {
						$excludeArray = array_merge($excludeArray,$rowArray);
						$rowArray = array();
					}
				}
				if (count($excludeArray)>0) {
					$detailQuery .= "and products.product_id not in (" . implode(",",$excludeArray) . ") ";
				}
				
				if(count($exceptManufacturer) > 0){				
				$detailQuery .= " and products.manufacturer_id not in (" . implode(",", $exceptManufacturer) . ")";
				}
			}
		}
	} else {
		// there are no categories in the list, so set up for a null result
		$detailQuery .= "and products.category_id = -1 ";
	}	
}

$hiddenManufacturers = array();
if (!empty($manufacturer_id)) {
	$titleArray[] = getFieldFromId("description","manufacturers","manufacturer_id",$manufacturer_id);
	$detailQuery .= "and products.manufacturer_id = ? ";
        if (count($dlrHiddenCatId) > 0)
            $detailQuery .= "and products.category_id not in (" . implode(",",$dlrHiddenCatId) . ") ";
	$storeItemParameters[] = $manufacturer_id;
	$itemParameters[] = $manufacturer_id;
	if($useCacheData)
	{
	   foreach($cacheResultSet as $idx => $currData)
	   {
	      if($currData['m'] != $manufacturer_id)
	      {
	         unset($cacheResultSet[$idx]);
	      }
	   }
           //remove based on dlrhiddencatid
           foreach($cacheResultSet as $idx => $currData)
           {
              foreach($dlrHiddenCatId as $hidcat)
              {
               if($currData['cId'] == $hidcat) 
               {
                  unset($cacheResultSet[$idx]);  
               }    
              } 
           }  
	}
} else {
	// are there any hidden manufacturers?
	$resultSet = executeQuery("select control_list_item_id from dealer_control_lists where dealer_id = ? and internal_use_only = 1 and control_list_id = 3",$gDealerId);
	while ($row = getNextRow($resultSet)) {
		$hiddenManufacturers[] = $row['control_list_item_id'];
	}
	if (count($hiddenManufacturers) > 0) {
		$detailQuery .= "and products.manufacturer_id not in (" . implode(",",$hiddenManufacturers) . ") ";
	}
	if($useCacheData)
	{
	   $expManufacturers = array_fill_keys($hiddenManufacturers, null);
	   foreach($cacheResultSet as $idx => $currData)
	   {
	      if(array_key_exists($currData['m'], $expManufacturers))
	      {
	         unset($cacheResultSet[$idx]);
	      }
	   }
	}
}
if (!empty($caliber_id)) {
	$heading_result = executeQuery("select description from calibers where caliber_id = ?",$caliber_id);
	$heading_row = getNextRow($heading_result);
	$titleArray[] = $heading_row['description'];
	$detailQuery .= "and products.caliber_id = ? ";
	$storeItemParameters[] = $caliber_id;
	$itemParameters[] = $caliber_id;
	if($useCacheData)
	{
	   foreach($cacheResultSet as $idx => $currData)
	   {
	      if($currData['c'] != $caliber_id)
	      {
	         unset($cacheResultSet[$idx]);
	      }
	   }
	}
}
$StoreItemSubQuery  = "";
$StoreItemSubQuery  .=  $detailQuery;
$StoreItemSubQuery  .= "group by products.product_id";
$detailQuery  .= "group by products.product_id,distributor_inventory.distributor_id";
$productSet = array();
$caliberSet = array();
$manufacturerSet = array();
$resultSet = executeQuery($storeItemQuery . $StoreItemSubQuery, $storeItemParameters);
while ($row = getNextRow($resultSet)) {
	$productSet[] = $row['product_id'];
	if ($row['caliber_id']>0 && !array_key_exists($row['caliber_id'],$caliberSet)) {
		$caliberSet[$row['caliber_id']] = getFieldFromId('description','calibers','caliber_id',$row['caliber_id']);
	}
	if ($showManufacturerOptions && $row['manufacturer_id']>0 && !array_key_exists($row['manufacturer_id'],$manufacturerSet)) {
		$manufacturerSet[$row['manufacturer_id']] = getFieldFromId('description','manufacturers','manufacturer_id',$row['manufacturer_id']);
	}
}
foreach ($excludeArray as $product_id) {
	$mfgId = getFieldFromId('manufacturer_id','products','product_id',$product_id);
	if (!array_key_exists($mfgId,$manufacturerSet)) {
		$manufacturerSet[$mfgId] = getFieldFromId('description','manufacturers','manufacturer_id',$mfgId);
	}
}
if ($gDealerId == 1 || !empty($dealerArray['distributorSet'])) {
   if($useCacheData)
   {	
      foreach($cacheResultSet as $idx => $currData)
      {
          if (!empty($productSet))
                {
                   if (!in_array($currData['p'], $productSet)){
                       $productSet[] = $currData['p']; 
                   }
                }
                else
                {
                 $productSet[] = $currData['p'];
                }
	 if ($currData['c']>0 && !array_key_exists($currData['c'],$caliberSet)) {
		$caliberSet[$currData['c']] = getFieldFromId('description','calibers','caliber_id',$currData['c']);
	 }
	 if ($showManufacturerOptions && $currData['m']>0 && !array_key_exists($currData['m'],$manufacturerSet)) {
		$manufacturerSet[$currData['m']] = getFieldFromId('description','manufacturers','manufacturer_id',$currData['m']);
	 }
      }
   }
   else
   {
	$resultSet = executeQuery($itemQuery . $detailQuery,$itemParameters);
	while ($row = getNextRow($resultSet)) {
		if (!empty($productSet))
                {
                   if (!in_array($row['product_id'], $productSet)){
                       $productSet[] = $row['product_id']; 
                   }
                }
                else
                {
                 $productSet[] = $row['product_id'];
                }
		if ($row['caliber_id']>0 && !array_key_exists($row['caliber_id'],$caliberSet)) {
			$caliberSet[$row['caliber_id']] = getFieldFromId('description','calibers','caliber_id',$row['caliber_id']);
		}
		if ($showManufacturerOptions && $row['manufacturer_id']>0 && !array_key_exists($row['manufacturer_id'],$manufacturerSet)) {
			$manufacturerSet[$row['manufacturer_id']] = getFieldFromId('description','manufacturers','manufacturer_id',$row['manufacturer_id']);
		}
	}
   }
}
		//POBF-301 Remove the exception product ids and reindex the array
		if (count($expProducts) > 0) {
			foreach($expProducts as $exparr) {
				foreach($exparr as $unsetexp) {
					$key = array_search($unsetexp,$productSet);
					if ($key !== false) {
						unset($productSet[$key]);
					}
				}
			}
		}
		
		$productSet = array_values($productSet); 
		// end of fix		
if(count($exceptManufacturer) > 0){	
unset($manufacturerSet['155']);}
asort($manufacturerSet);
asort($caliberSet);
$optionSet = array(
	"department_id" => $department_id,
	"category_id" => $category_id,
	"caliberSet" => $caliberSet,
	"caliber_id" => $caliber_id,
	"manufacturerSet" => $manufacturerSet,
	"manufacturer_id" => $manufacturer_id,
	"model" => $model,
	"item_count" => count($productSet),
	"items_per_page" => $gItemsPerPage,
	"current_page" => $currentPage
);
$heading = implode(" › ",$titleArray);
$leftSidebarLimit = 12;
$rightSidebarLimit = 26; // maybe have jQuery determine how many store badges to show?
// POBF - 108, Meta tag array
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
        
	<!-- POBF - 108, Meta Tag Changes -->
        <title><?php echo ($metaTagArray['istitleinchild']== '1'? $metaTagArray['title'] . " | " . $heading : $metaTagArray['title']); 
		 if (empty($metaTagArray['title'])){ echo $dealerArray['dealer_name'] . " | " . strip_tags($heading);}?></title>        
        <meta name="description" content="<?php echo ($metaTagArray['isdescriptioninchild']== '1' ? str_replace("›", "-", $heading) . " - ".$metaTagArray['description']: $metaTagArray['description']); 
		if (empty($metaTagArray['description'])){ echo str_replace("›", "-", strip_tags($heading)) . " - " . $dealerArray['dealer_name'] . " - " . $dealerArray['site_description'];}
		?>">
        <meta name="keywords" content="<?php echo ($metaTagArray['iskeywordinchild']== '1'? str_replace("›", ", ", strtolower($heading)) . ", " . $metaTagArray['keyword']:$metaTagArray['keyword']); 
		if (empty($metaTagArray['keyword'])){echo str_replace("›", ", ", strtolower(strip_tags($heading))) . ", " . $dealerArray['site_keywords'];}?>">
        <!-- POBF - 108, Meta Tag Changes -->
        <link rel="stylesheet" href="templates/default/universal-styles-v5.css">
        <link rel="stylesheet" href="<?php echo $templateArray['path'] ?>/styles-v1.css">
	<link rel="stylesheet " href="scpt/custom-theme/jquery-ui.css">
	<?php if ($gDealerId == $gDefaultDealerId || in_array($gDealerId, $master_id_array)) { ?>
	<link rel="stylesheet" href="scpt/fancybox/jquery.fancybox.css">
	<link rel="stylesheet" href="<?php echo $templateArray['zipcodes'] ?>/zipcode_finder.css">
	<?php } ?>
	<script type="text/javascript">
		if (top != self) {
			top.location.href = self.location.href;
		}
	</script>
	<script src="scpt/jquery.js"></script>
	<!--[if lt IE 9]>
	<script src="scpt/modernizr-2.0.6.js"></script>
	<![endif]-->
	<?php 
        include_once "scpt/google_code.inc";        
        ?>
</head>
<body>
	<?php
         if ($dealerArray['enable_global_login'] == 1 && isset($_SESSION[$globalSystemCode]['global_user_id'])) {
                $globalUsername = getFieldFromId('session_data', 'sessions', 'session_id', $_SESSION[$globalSystemCode]["global_user_id"]);
                ?>
                <div id="welcomenote">
                    <span style="vertical-align: middle; margin-right: 10px;color:#fff">Welcome, <?php echo $globalUsername; ?></span>                                       
                    <span style="color: #fff;">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
                    <a id="gLogout" href="/globallogout.php" style="color:#fff;vertical-align: bottom; margin-right: 10px;">Logout</a>
                </div>
                <?php
            }
	include_once $templateArray['header'] . "/header.inc";
	include_once $templateArray['catalogbanners'] . "/catalogbanners-c.inc"; 
	include_once "scpt/catalog_functions.inc";
	?>
		
	<table cellspacing="0" cellpadding="0"><tr>
		<td valign="top">
			<?php include_once $templateArray['left'] . "/sidebar.inc"; ?>
		</td>
		<td valign="top" width="590">
        <div class="financeMessage"></div>
			<div id="center_column" style="min-height: 800px;">
			<?php
				//show features if appropriate
				if (!empty($category_id) && count($featureArray) >= 3 && empty($caliber_id) && empty($manufacturer_id) && empty($model) && $currentPage == 1) {
					echo "<div class='content'>";
					echo "<h1 class='featured_items'>Featured Items</h1>";
					$rowArray = array();
					for ($i = 0; $i < count($featureArray); $i++) {
						$rowArray[] = $featureArray[$i];
						if (count($rowArray) == 3) {
							showCatalogRow($rowArray,$dealerArray['use_retail_price']);
							$gRowsShown++;
							$rowArray = array();
						}
					}
					echo "</div>";
					echo "<div class='spacer'></div>";
				}
				//show best-sellers if appropriate
				if ($gDealerId == 1 || !empty($dealerArray['distributorSet'])) {                                        
					$bestSellersSet = array();
					if ( ( count($categorySet) > 0 || !empty($category_id) ) && empty($caliber_id) && empty($manufacturer_id) && empty($model) && $currentPage == 1 && array_key_exists($category_id, $cachedCatalogIds)) {
						
                                                $start_date = date('Y-m-d',strtotime("-7 days"));
						$end_date = date('Y-m-d',strtotime("-1 day"));                                                
						
                                                $cacheKey = "Category_Best_Sellers_" .$category_id; 
                                                $query  = "select p.product_id, avg(di.dealer_cost)*sum(dih.delta) as total_cost from products p ";
						$query .= "left join distributor_inventory di using (product_id) left join distributor_inventory_history dih ";
						$query .= "using (product_id,distributor_id) where p.inactive = 0 and p.internal_use_only = 0 and di.quantity > 0 and dih.delta > 0 and ";
						$query .= "dih.date_created between ? and ? and p.category_id = ? and p.thumbnail_image_id > 0 and p.dealer_id is null ";
						$queryParameters = array();
                                                $categoryBestSeller = array();
						$queryParameters[] = $start_date;
						$queryParameters[] = $end_date; 
						$queryParameters[] = $category_id;
                                                $catalogCache = false;
                                                $CatalogMemcache = "CG-NoCache||".$cacheKey;
                                                if (class_exists(Memcache) && $memcache = new Memcache()) {                                                    
                                                    foreach ($gMemcacheServers as $server) {
                                                        $memcache->addServer($server);
                                                    }
                                                    if ($cachedDataStr = $memcache->get($cacheKey)) {                                                         
                                                       $prodDataAry = json_decode($cachedDataStr, true);                                                        
                                                       $catalogCache = true;
													   $CatalogMemcache = "CG-MemCache||".$cacheKey;
                                                    }
                                                }
                                                
                                                if(!$catalogCache){                         
														$CatalogMemcache = "CG-NoMemCache||".$cacheKey;
                                                        $cacheQuery  = "select p.product_id, p.manufacturer_id, p.category_id, di.distributor_id, avg(di.dealer_cost)*sum(dih.delta) as total_cost from products p ";
                                                        $cacheQuery .= "left join distributor_inventory di using (product_id) left join distributor_inventory_history dih ";
                                                        $cacheQuery .= "using (product_id,distributor_id) where p.inactive = 0 and p.internal_use_only = 0  and di.quantity > 0 and dih.delta > 0 and ";
                                                        $cacheQuery .= "dih.date_created between ? and ? and p.category_id = ? and p.thumbnail_image_id > 0 and p.dealer_id is null ";
                                                        $cacheQuery .= "group by p.product_id order by total_cost desc ";
                                                        $resultSet = executeQuery($cacheQuery, $queryParameters);  
                                                        
                                                        while ($row = getNextRow($resultSet)) {
                                                            $currData = array();
                                                            $currData['p'] = $row['product_id'];                                                        
                                                            $currData['d'] = $row['distributor_id'];                                                            
                                                            $currData['m'] = $row['manufacturer_id'];                                                           
                                                            $prodDataAry[] = $currData;                                                          
                                                        }  
                                                        if($memcache != null)
                                                        {
                                                              $memcache->set($cacheKey, json_encode($prodDataAry), false, 57600); 
                                                              $catalogCache = true;
                                                        }
                                                }
                                                
                                                
                                                foreach($prodDataAry as $prodData)
                                                {
                                                    $categoryBestSeller[] = $prodData;
                                                }
                                                                                           
                                                 
                                                 
						if (!empty($dealerArray['distributorSet'])) {
                                                    
                                                    $query .= "and di.distributor_id in (" . $dealerArray['distributorSet'] . ") ";
                                                    
                                                    if($catalogCache){ 
                                                        
                                                        $allowedDistributors = array_fill_keys(explode(",", $dealerArray['distributorSet']), null);
                                                        
                                                        foreach($categoryBestSeller as $idx => $currData)
                                                        {                                                             
                                                           if(!array_key_exists($currData['d'], $allowedDistributors))
                                                           {                                                                
                                                              unset($categoryBestSeller[$idx]);
                                                           }
                                                        }
                                                    }
						}
                                               
						if (count($excludeArray) > 0) {
                                                    
                                                    $query .= "and p.product_id not in (" . implode(",",$excludeArray) . ") ";
                                                        
                                                    if($catalogCache){ 
                                                        
                                                        $excludedProducts = array_fill_keys($excludeArray, null);
                                                        
                                                        foreach($categoryBestSeller as $idx => $currData)
                                                        {                                                             
                                                           if(array_key_exists($currData['p'], $excludedProducts))
                                                           {                                                                
                                                              unset($categoryBestSeller[$idx]);
                                                           }
                                                        }
                                                    }
						}
                                                
						if (count($hiddenManufacturers) > 0) {
                                                    
							$query .= "and p.manufacturer_id not in (" . implode(",",$hiddenManufacturers) . ") ";
                                                        
							if($catalogCache){ 
                                                        
                                                            $manufacturerExclusionList = array_fill_keys($hiddenManufacturers, null);
                                                            foreach($categoryBestSeller as $idx => $currData)
                                                            {                                                             
                                                               if(array_key_exists($currData['m'], $manufacturerExclusionList))
                                                               {                                                                
                                                                  unset($categoryBestSeller[$idx]);
                                                               }
                                                            }
                                                        }
						}
                                                
						if (!empty($exceptionListBlock)) {
                                                    
							$query .= "and product_id not in (select product_id from exception_list_products where exception_list_id = ?) ";
							$queryParameters[] = $exceptionListBlock;
							
                                                        if($catalogCache)
                                                        {
                                                            $expProducts = exceptionBlockList($exceptionListBlock);
							    if(is_array($expProducts[0]))
							    {
							    	unset($expProducts[0]); // remove item at index 0
							    	$expProducts = array_values($expProducts); // 'reindex' array
							    }
                                                            $expProductLookup = array_fill_keys($expProducts, null);
                                                            foreach($categoryBestSeller as $idx => $currData)
                                                            {
                                                               if(array_key_exists($currData['p'], $expProductLookup))
                                                               {
                                                                  unset($categoryBestSeller[$idx]);
                                                               }
                                                            }
                                                        } 
						}
                                                
						$query .= "group by p.product_id order by total_cost desc limit 12";
                                                
						
                                                
						if ($catalogCache) {
                                                    $index = 0;
                                                    foreach($categoryBestSeller as $idx => $currData){
                                                          if($index <= 11){                                                             
                                                             $bestSellersSet[]=$currData['p'];
                                                             $index ++;                                                                                                                                 
                                                           }
                                                    }							
						}else
                                                {
                                                    $resultSet = executeQuery($query,$queryParameters);
							$bestSellersSet = array();
							while ($row = getNextRow($resultSet)) {
								$bestSellersSet[] = $row['product_id'];
							}
                                                }
                                                
						
						if (count($bestSellersSet) >= 3 ) {
							echo "<div class='content'>";
							echo "<h1 class='best_sellers'>National Dealer Bestsellers</h1>";
							showCatalogRow($bestSellersSet,$dealerArray['use_retail_price']);
							$gRowsShown++;
						}
						if (count($bestSellersSet) >= 6) {
							showCatalogRow(array_slice($bestSellersSet,3),$dealerArray['use_retail_price']);
							$gRowsShown++;
						}
						if (count($bestSellersSet) >= 3 ) {
							echo "</div>";
							echo "<div class='spacer'></div>";
						}
					}
					
                                }
			?>
			
			<div class="content">
            
			<h1 class="catalog"><?php echo $heading; ?></h1>
			<?php
				if ($optionSet['item_count'] > 0) {
					showOptions($optionSet);
					if ($currentPage == 1) { $gItemsPerPage -= ($gRowsShown * 3); }
					for ($i = ($currentPage - 1) * $gItemsPerPage; $i < $currentPage * $gItemsPerPage; $i += 3) {
						$rowItems = array();
						for ($count = 0; $count < 3; $count++) {
							if (!empty($productSet[$i+$count])) {
								$rowItems[] = $productSet[$i+$count];
							}
						}
						if (count($rowItems) > 0) {
							showCatalogRow($rowItems,$dealerArray['use_retail_price']);
						}
					}
					echo "<div class='spacer'></div>";
					showOptions($optionSet);
				} else {
					echo "<div style='margin: 90px 0; text-align: center; color: #666;'>No items to display</div>";
				}
			?>
			</div>
                         <div class="financeMessage">                            
                        </div>
                         <div id="disclaimerContent"></div>
			</div> <!-- id=center_column -->
		</td>
		<td valign="top">
			<?php include_once $templateArray['right'] . "/right.inc"; ?>
		</td>
	</tr></table>
	
	<div class='spacer' style='font-size: 10px; color: #333;'><?php echo ($fromMemcache ? "." : "+") ?></div>
	
<?php include_once $templateArray['footer'] . "/footer.inc"; 
accessLogTrace($headerInfo."||".$CatalogMemcache);?>

<?php if ($gDealerId == $gDefaultDealerId || in_array($gDealerId, $master_id_array)) { include_once "scpt/zipcode_finder.inc"; } ?>

<input type="hidden" id="site_dealer_id" value="<?php echo $GLOBALS['gDealerId'] ?>" />
<input type="hidden" id="left_ad_block_margin" value="<?php echo $leftAdBlockMargin ?>" />
<input type="hidden" id="right_ad_block_margin" value="<?php echo $rightAdBlockMargin ?>" />
<input type="hidden" id="leaderboard_takeover_id" value="<?php echo $leaderBoardTakeoverId ?>" />
<input type="hidden" id="commonwealth_finance_enabled" value="<?php echo $dealerArray['enable_commonwealth'] == 1 ? 1 : 0 ?>" class="info">
<script src="scpt/jquery-ui.js"></script>
<?php if ($gDealerId == $gDefaultDealerId || in_array($gDealerId, $master_id_array)) { ?>
<script src="scpt/fancybox/jquery.fancybox.js"></script>
<script src="scpt/zipcode_finder.js"></script> 
<?php } ?>

<script src="scpt/shared_v3.js"></script>
<script>
$(window).load(function() { 
	$("#sidebar").css('overflow','hidden');
	$("#sidebar").height( $("#center_column").height() );
	$.each($(".ad_link"),function(index) {
		if ( $(this).offset().top + $(this).height() > $("#center_column").offset().top + $("#center_column").height() ) {
			$(this).remove();
		}
	});
	$("#sidebar_right").css('overflow','hidden');
	$("#sidebar_right").height( $("#center_column").height() );
	$.each($(".ad_space"),function(index) {
		if ( $(this).offset().top + $(this).height() > $("#center_column").offset().top + $("#center_column").height() ) {
			$(this).remove();
		}
	});
});
</script>
<script> // For loading finance disclaimer content
            $(function(){
             var finance_limit_setbydealer = "<?php echo $dealerArray['cw_min_purchase_amt'] ?>"
             if($("#commonwealth_finance_enabled").val()==1)
              {
                $(".financeMessage").html('<img src="tmpl/CW-SPECIAL_FINANCING-594x55.png" />');            
                $("#disclaimerContent").load("cw_disclaimer.htm");
             }
            });
        </script>
		
<?php include_once "scpt/dealer_tracking_code.inc";?>   
</body>
</html>
<?php
function showOptions($optionSet) {
	echo "<div id='catalog_options'>";
	echo "<table cellpadding='0' cellspacing='3' width='100%'><tr>";
	if (count($optionSet['caliberSet'])>0) {
		echo "<td align='center'>Caliber</td>";
	}
	if (count($optionSet['manufacturerSet'])>0) {
		echo "<td align='center'>Brand</td>";
	}
	if ($optionSet['item_count'] > $optionSet['items_per_page']) {
		echo "<td align='center' colspan='3'>Page</td>";
	}
	echo "</tr><tr>";
	if (count($optionSet['caliberSet'])>0) {
		echo "<td align='center'><select class='show_caliber field'>";
		echo "<option value='select'>Any</option>";
		foreach ($optionSet['caliberSet'] as $caliber_id => $description) {
			echo "<option value='$caliber_id'" . ($optionSet['caliber_id']==$caliber_id?" selected":"") . ">";
			echo $description . "</option>";
		}
		echo "</select></td>";
	}
	if (count($optionSet['manufacturerSet'])>0) {
		echo "<td align='center'><select class='show_manufacturer field'>";
		echo "<option value='select'>Any</option>";
		foreach ($optionSet['manufacturerSet'] as $manufacturer_id => $description) {
			echo "<option value='$manufacturer_id'" . ($optionSet['manufacturer_id']==$manufacturer_id?" selected":"") . ">";
			echo $description . "</option>";
		}
		echo "</select></td>";
	}
	
	if ($optionSet['item_count'] > $optionSet['items_per_page']) {
		echo "<td align='right'>";
		echo "<button class='arrow_button' data-page='" . ($optionSet['current_page']>1?$optionSet['current_page']-1:0) . "'>&larr;</button>";
		echo "</td>";
		echo "<td align='center' width='50'>";
		echo "<select class='goto_page field'>";
		if ( $optionSet['item_count'] / $optionSet['items_per_page'] > floor($optionSet['item_count'] / $optionSet['items_per_page']) ) {
			$pages = floor($optionSet['item_count'] / $optionSet['items_per_page']) + 1;
		} else {
			$pages = ($optionSet['item_count'] / $optionSet['items_per_page']);
		}
		
		for ($i = 1; $i < $pages + 1; $i++) {
			echo "<option value='$i'" . ($optionSet['current_page']==$i?" selected":"") . ">$i</option>";
		}
		echo "</select>";
		echo "</td>";
		echo "<td>";
		echo "<button class='arrow_button' data-page='" . ($optionSet['current_page']<$pages?$optionSet['current_page']+1:0) . "'>&rarr;</button>";
		echo "</td>";
	}
	echo "<td>&nbsp;</td></tr></table>";
	echo "</div>";
}
?>
<script> // For loading finance disclaimer content
           $(function(){             
            if($("#commonwealth_finance_enabled").val()==1)
             {
               $(".financeMessage").html('<img src="tmpl/CW-SPECIAL_FINANCING-594x55.png" />');
               $("#disclaimerContent").load("cw_disclaimer.htm");
            }
            });
            var val='';
           $(window).load(function() {
            $.each($("#sidebar_right a img"),function(){
              var getadlink= /ad-link=([0-9]*)/.exec($(this).parent().attr('href'));
              var intRegex = /^\d+$/;
                if(intRegex.test(getadlink[1]))
                {
                    if(val=='') 
                        { 
                            val=getadlink[1]; 
                        } 
                    else { 
                        val = val +','+getadlink[1];
                         }
                    
                }
                
           });
                $.ajax({
                url: 'scpt/increment_ad_impression.php',
                type: "POST",
		data: {advertising_id: val},
               
                dataType: "json"
                 });                  
                });
            
         
</script>
<?php
include_once "finance_faqs.htm";
?>
