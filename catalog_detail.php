<?php
################################################################
# This software is the unpublished, confidential, proprietary, 
# intellectual property of zipperSNAP, LLC and may not be copied,
# duplicated, retransmitted or used in any manner without
# expressed written consent from zipperSNAP, LLC.   
# Copyright 2009 - Present, zipperSNAP, LLC.
################################################################
$gProgramCode = "CATALOGDETAIL";
include_once "scpt/utilities.inc";
$dealerArray = getDealerInfo($gDealerId);
if ((!isset($_SESSION[$globalSystemCode]['global_user_id']) || empty($_SESSION[$globalSystemCode]['global_user_id'])) && $dealerArray['enable_global_login'] == 1) {
   header("Location:http://" . $dealerArray['dealer_url']);
}
if (empty($_GET['product_id']) && empty($_GET['upc']) || !is_numeric($_GET['product_id']) && !is_numeric($_GET['upc'])) {
   header("Location: /");
   exit;
}


// Code added for redirecting to home page for inactive products - start

$resultSet = executeQuery("select * from products where product_id = ? and inactive = ?", $_GET['product_id'], 1);

if ($row = getNextRow($resultSet)) {
   header("Location: /");
   exit;
}

// Code added for redirecting to home page for inactive products - end
if ($gDealerId > 0 && $gDealerId != $gDefaultDealerId && !in_array($gDealerId, $master_id_array)) {
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
$templateArray = getTemplateInfo($gDealerId, $_GET['tmp']);
$cachedCatalogIds = array_fill_keys(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 62, 63, 64, 66, 67, 69, 70, 100104, 100660, 100712, 100713, 100714, 100823, 100827, 100828, 100837, 101088, 103367), null);


if (!empty($_GET['upc']) && is_numeric($_GET['upc']) && empty($_GET['product_id'])) {
   $resultSet = executeQuery("select * from products where upc=? and dealer_id is null", $_GET['upc']);
   if ($row = getNextRow($resultSet)) {
      $productId = $row['product_id'];
   }
} else {
   $productId = $_GET['product_id'];
}


// block items from exception list for this dealer's state?
if ($dealerArray['ignore_exceptions'] == 0) {
   $exceptionListBlock = getFieldFromId('exception_list_id', 'exception_lists', 'state', $dealerArray['dealer_state'], 'allowed = 0');
}

$leftSidebarLimit = 12;
$rightSidebarLimit = 12;

// get this dealer's retail price preference
$preferenceId = getFieldFromId('preference_id', 'preferences', 'preference_code', 'USE_RETAIL_PRICE');
$resultSet = executeQuery("select * from dealer_preferences where preference_id = ? and dealer_id = ?", $preferenceId, $dealerId);
if ($row = getNextRow($resultSet)) {
   $useRetailPrice = ($row['preference_value'] == "Y" ? 1 : 0);
} else {
   $useRetailPrice = 0;
}

// are there any hidden manufacturers?
$resultSet = executeQuery("select control_list_item_id from dealer_control_lists where dealer_id = ? and internal_use_only = 1 and control_list_id = 3", $gDealerId);
while ($row = getNextRow($resultSet)) {
   $hiddenManufacturers[] = $row['control_list_item_id'];
}

include_once "scpt/catalog_functions.inc";
$productArray = getProductInfoMicro($productId, $gDealerId);
$manufacturer = getFieldFromId('manufacturer_id', 'products', 'product_id', $productId);
if (in_array($manufacturer, $dealerExceptionManufacturers)) {
   $resultSet = executeQuery("select control_list_item_id from dealer_control_lists where dealer_id = ? and internal_use_only = 1 and control_list_id = 3 and control_list_item_id = ? ", $gDealerId, $manufacturer);
   if ($resultSet['row_count'] > 0) {
      header("Location: /");
      exit;
   }
}

//excluded products
$excludeArray = array();
$featureArray = array();
$featureDealerId = ($dealerArray['use_mall_features'] == 1 ? 1 : $gDealerId);
$query = "select ptl.product_id,p.manufacturer_id from product_tag_links ptl left join products p using (product_id) ";
$query .= "where ptl.dealer_id = ? and ptl.product_tag_id = 2 and p.category_id = ? and p.inactive = 0";
$resultSet = executeQuery($query, $featureDealerId, $productArray['category_id']);

while ($row = getNextRow($resultSet)) {
   $featureArray[] = $row['product_id'];
}
shuffle($featureArray);

if (count($featureArray) > 0) {
   $rowArray = array();
   for ($i = 0; $i < count($featureArray); $i++) {
      $rowArray[] = $featureArray[$i];
      if (count($rowArray) == 3) {
         $excludeArray = array_merge($excludeArray, $rowArray);
         $rowArray = array();
      }
   }
}


if (!$gDealerId == 1 || empty($dealerArray['distributorSet'])) {
   if (!$productArray['store_item']) {
      header("Location: /");
      exit;
   }
}
//POBF - 108, Meta Tags
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

        <base href="/">
        <!-- POBF - 108, Meta tag update -->
        <title><?php echo ($metaTagArray['istitleinchild'] == '1' ? $metaTagArray['title'] . " | " . $productArray['description'] : $metaTagArray['title']);
if (empty($metaTagArray['title'])) {
   echo $dealerArray['dealer_name'] . " | " . strip_tags($productArray['description']);
}
?></title>
        <meta name="description" content="<?php echo ($metaTagArray['isdescriptioninchild'] == '1' ? $productArray['description'] . " - " . $metaTagArray['description'] : $metaTagArray['description']);
if (empty($metaTagArray['description'])) {
   echo strip_tags($productArray['description']) . " - " . $dealerArray['dealer_name'] . " - " . $dealerArray['site_description'];
}
?>">
        <meta name="keywords" content="<?php echo ($metaTagArray['iskeywordinchild'] == '1' ? str_replace(" ", ", ", strtolower($productArray['description'])) . ", " . $metaTagArray['keyword'] : $metaTagArray['keyword']);
              if (empty($metaTagArray['keyword'])) {
                 echo str_replace(" ", ", ", strtolower(strip_tags($productArray['description']))) . ", " . $dealerArray['site_keywords'];
              }
?>">
        <!-- POBF - 108, Meta tag update -->
        <link rel="stylesheet" href="templates/default/universal-styles-v5.css">
        <link rel="stylesheet" href="<?php echo $templateArray['path'] ?>/styles-v1.css">
        <link rel="stylesheet" href="scpt/custom-theme/jquery-ui.css">
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
        <?php include_once "scpt/google_code.inc"; ?>
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
?>

        <table cellspacing="0" cellpadding="0"><tr>
                <td valign="top">
<?php include_once $templateArray['left'] . "/sidebar.inc"; ?>
                </td>
                <td valign="top">
                    <div id="center_column">
                        <div class="content">
                            <input type="hidden" id="commonwealth_finance_enabled" value="<?php echo $dealerArray['enable_commonwealth'] == 1 ? 1 : 0 ?>" class="info">
                            <div class="financeMessage"></div>
                            <div class="catalog">Product Detail</div>
                            <div class="detail_pane_top"></div>
                            <div class="detail_pane_middle">



                                <?php
                                if ($productArray['override_price'] > 0 && $productArray['override_price'] >= $productArray['map_price']) {
                                   $displayPrice = $productArray['override_price'];
                                } else {
                                   if ($useRetailPrice && $productArray['retail_price'] > 0) {
                                      $displayPrice = $productArray['retail_price'];
                                   } else {
                                      if ($productArray['live_price'] != 1) {
                                         if ($productArray['distributor_id'] > 0) {
                                            $displayPrice = getDisplayPrice($productArray);
                                         } else {
                                            $displayPrice = $productArray['dealer_cost'];
                                         }
                                      }
                                   }
                                }
                                if (($dealerArray['enable_commonwealth'] == 1) && $displayPrice != 0 && !empty($displayPrice)) {
                                   echo "<table width='100%'>";
                                   echo "<tr>";
                                   echo "<td id='chk'>";
                                   if ($displayPrice < 750) {
                                      echo "<div class='aslowas_details'>";
                                      echo "";
                                      echo "</div>";
                                   }
                                   if ($displayPrice >= 750) {
                                      echo "<div class='aslowasdetail'>";
                                      echo "as low as $" . estimatedMonthlyPayment($displayPrice) . "/month";
                                      echo "</div>";
                                   }
                                   echo "</td>";
                                   echo "</tr>";
                                   echo "</table>";
                                }
                                ?>
                                <div itemscope itemtype="http://schema.org/Product">
                                    <table><tr><td class="detail_image"><?php
                                                $productArray['detailed_description'] = getFieldFromId('detailed_description', 'products', 'product_id', $productId);
                                                $productArray['category_name'] = getFieldFromId('description', 'categories', 'category_id', $productArray['category_id']);
                                                $productArray['department_id'] = getFieldFromId('department_id', 'categories', 'category_id', $productArray['category_id']);
                                                $productArray['department_name'] = getFieldFromId('description', 'departments', 'department_id', $productArray['department_id']);
                                                $productArray['manufacturer_id'] = getFieldFromId('manufacturer_id', 'products', 'product_id', $productId);
                                                $productArray['manufacturer_name'] = getFieldFromId('description', 'manufacturers', 'manufacturer_id', $productArray['manufacturer_id']);
                                                if (!empty($row['caliber_id'])) {
                                                   $productArray['caliber_id'] = $row['caliber_id'];
                                                   $productArray['caliber_name'] = getFieldFromId('description', 'calibers', 'caliber_id', $row['caliber_id']);
                                                }

                                                // if ($useRetailPrice && $productArray['retail_price'] > 0) {
                                                //     $displayPrice = $productArray['retail_price'];
                                                // } else {
                                                //     if ($productArray['override_price'] > 0 && $productArray['override_price'] >= $productArray['map_price']) {
                                                //         $displayPrice = $productArray['override_price'];
                                                //     } else {
                                                //         if ($productArray['distributor_id'] > 0) {
                                                //             $displayPrice = getDisplayPrice($productArray);
                                                //         } else {
                                                //             $displayPrice = $productArray['dealer_cost'];
                                                //         }
                                                //     }
                                                // }
                                                if ($productArray['override_price'] > 0 && $productArray['override_price'] >= $productArray['map_price']) {
                                                   $displayPrice = $productArray['override_price'];
                                                   // echo "override".$displayPrice;
                                                } else {
                                                   if ($useRetailPrice && $productArray['retail_price'] > 0) {
                                                      $displayPrice = $productArray['retail_price'];
                                                   } else {
                                                      if ($productArray['live_price'] != 1) {
                                                         if ($productArray['distributor_id'] > 0) {
                                                            $displayPrice = getDisplayPrice($productArray);
                                                         } else {
                                                            $displayPrice = $productArray['dealer_cost'];
                                                         }
                                                      }
                                                   }
                                                }

                                                if (empty($productArray['image_id'])) {
                                                   echo "<img src='tmpl/no_image.jpg'>";
                                                } else {
                                                   echo "<img itemprop='image' src='" . $GLOBALS['cloudFrontURL'] . "imagedb/image" . $productArray['image_id'] . "-" . getImageHashCode($productArray['image_id']) . ".jpg' class='full_image'/>";
                                                }
                                                ?></td></tr></table>
                                    <?php
                                    echo "<h1 itemprop='name' class='detail_item_title'>" . trim($productArray['description']) . "</h1>";
                                    echo "<table cellpadding='0' cellspacing='0' width='100%' id='catalog_detail_table'>";
                                    echo "<tr><td class='detail_item_label' valign='top'>Product ID</td>";
                                    echo "<td class='pid'><span itemprop='productID'>" . $productId . "</span>" . " " . (($gDealerId != $master_id_array[1]) ? $productArray['distributor_mark'] : "") . "</td></tr>";

                                    if (!empty($productArray['upc'])) {
                                       echo "<tr><td class='detail_item_label' valign='top'>UPC</td>";
                                       echo "<td><span itemprop='gtin12'>" . $productArray['upc'] . "</span></td></tr>";
                                    }

                                    echo "<tr><td class='detail_item_label' valign='top'>Manufacturer</td><td><span itemprop='manufacturer'>" . $productArray['manufacturer_name'] . "</span></td></tr>";
                                    if (!empty($product_row['model'])) {
                                       echo "<tr><td class='detail_item_label' valign='top'>Model</td><td><span itemprop='model'>" . $productArray['model'] . "</span></td></tr>";
                                    }
                                    echo "<tr><td class='detail_item_label' valign='top'>Description</td><td><span itemprop='description'>" . nl2br($productArray['detailed_description']) . "<br>" . $product_row['detailed_description'] . "</span></td></tr>";
                                    if (!empty($productArray['caliber_name'])) {
                                       echo "<tr><td class='detail_item_label' valign='top'>Caliber</td><td>" . $productArray['caliber_name'] . "</td></tr>";
                                    }

                                    echo "<tr><td class='detail_item_label' valign='top'>Department</td>";
                                    $categoryLinkName = getFieldFromId("link_name", "categories", "category_id", $productArray['category_id']);
                                    $categoryLinkUrl = (empty($categoryLinkName) ? "/catalog.php?category=" . $productArray['category_id'] : "/category/" . $categoryLinkName);
                                    echo "<td><a href='" . $categoryLinkUrl . "' class='detail_item_link'><span itemprop='disambiguatingDescription'>" . $productArray['department_name'] . " › " . $productArray['category_name'] . "</span></a></td>";
                                    echo "</tr>";
                                    $resultSet = executeQuery("select * from product_descriptions where product_id = ?", $productId);
                                    if ($resultSet['row_count'] > 0) {
                                       while ($row = getNextRow($resultSet)) {
                                          $descriptor = getFieldFromId('description', 'product_descriptors', 'product_descriptor_id', $row['product_descriptor_id']);
                                          echo "<tr>";
                                          echo "<td class='detail_item_label'>" . $descriptorname = str_replace(" ", "&nbsp;", $descriptor) . "</td>";

                                          $PropArray = array('Type', 'Model', 'Weight');
                                          if (in_array($descriptor, $PropArray)) {
                                             if ($descriptor == 'Type') {
                                                $prop = 'category';
                                             }
                                             if ($descriptor == 'Model') {
                                                $prop = 'model';
                                             }
                                             if ($descriptor == 'Weight') {
                                                $prop = 'weight';
                                             }

                                             echo "<td><span itemprop='" . $prop . "'>" . nl2br($row['content']) . "</span></td>";
                                          } else {

                                             echo "<td>" . nl2br($row['content']) . "</td>";
                                          }
                                          echo "</tr>";
                                       }
                                    }

                                    echo "</table>";
                                    echo "</div>";

                                    echo "<div id='catalog_detail_price_bar'>";
                                    if ($gDealerId > 0 && !in_array($gDealerId, $master_id_array) && $gDealerId != $gDefaultDealerId) {

                                       if ($productArray['inactive'] == 0) {
                                          echo "<span itemprop='offers' itemscope itemtype='http://schema.org/Offer'>";
                                          echo "<table cellpadding='0' cellspacing='0' width='100%'><tr>";
                                          echo "<td>";
                                          if ($productArray['available_quantity'] > 20 || ($productArray['available_quantity'] > 0 && $productArray['store_item'])) {
                                             echo "Available<br>";
                                          } else {
                                             if ($productArray['available_quantity'] > 5) {
                                                echo $productArray['available_quantity'] . " available";
                                             } else {
                                                if ($productArray['available_quantity'] > 0) {
                                                   echo "<span class='low_quantity'>Only " . $productArray['available_quantity'] . " left!</span>";
                                                }
                                             }
                                          }
                                          echo "</td>";
                                          echo "<td>";

                                          if ($useRetailPrice != 1) {
                                             if ((float) $productArray['retail_price'] > (float) $displayPrice) {

                                                $Retail_Price = number_format($productArray['retail_price'], 2, '.', '');
                                                echo "<div>REG: <span itemprop='priceCurrency' content='USD'>$</span><span class='retail_price' itemprop='price'
                                        content='" . $Retail_Price . "'>" . $Retail_Price . "</span></div>";
                                             } else {
                                                echo "&nbsp;";
                                             }
                                          } else {
                                             echo "&nbsp;";
                                          }
                                          echo "</td>";

                                          if ($productArray['available_quantity'] > 0) {

                                             if ($gDealerId > 0 && $gDealerId != $gDefaultDealerId && !in_array($gDealerId, $master_id_array)) {
                                                if ($productArray['override_price'] > 0 && $productArray['override_price'] >= $productArray['map_price']) {
                                                   $displayPrice = $productArray['override_price'];

                                                   echo "<td class='display_price'>$" . number_format($productArray['override_price'], 2, '.', '') . "<td>";
                                                } else {

                                                   if ($useRetailPrice && $productArray['retail_price'] > 0) {
                                                      echo "<td class='display_price'>$" . number_format($productArray['retail_price'], 2, '.', '') . "<td>";
                                                   } else {
                                                      if ($productArray['live_price'] == 1) {
                                                         echo "<td class='live_price' id='live_price_" . $GLOBALS['gLivePriceIndex'] ++ . "' valign='bottom' data-product_id='{$productArray['product_id']}' data-distributor_id='{$productArray['distributor_id']}'><img src='tmpl/price-loader.gif'></td>";
                                                      } else {
                                                         echo "<td class='display_price' valign='bottom' align='right'>$" . $displayPrice . "</td>";
                                                      }
                                                   }
                                                }
                                             } else {
                                                echo "<td class='right'><i>Discount Available!</i></td>";
                                             }
                                          }
                                          echo "<td>&nbsp;&nbsp;&nbsp;</td>";
                                          echo "<td class='live_checkout'>";
                                          if ($gDealerId > 0 && !in_array($gDealerId, $master_id_array) && $gDealerId != $gDefaultDealerId) {
											
											//State Restricted Items
											$resultSet = executeQuery("select * from exception_list_products where product_ID = ? and Exception_list_ID = ?",$productId,$exceptionListBlock);  
                                            $rowcount=$resultSet['row_count'];
											 
											if($dealerArray['ignore_exceptions'] == 0 && $rowcount > 0) {
												echo "<span style='color:#f00;font-weight:bold' class='state_restrict'>State Restricted Item</span>";
											} //Cart if
                                            else if (isInCart($productId, $itemsInCart)) {
                                                //echo "<a href='#' class='go_to_checkout'>View Cart</a>";
                                                //code added by msts on 1 March
                                                echo "<a href='/scpt/transfer_to_checkout.php?ad-click=' " . $_GET['ad-link'] . "class='go_to_checkout'>View Cart</a>";
                                            } else {

                                                if ($productArray['live_price'] == 1 && !$useRetailPrice) {
                                                  echo "<a href='#' class='add_to_cart' data-product-id='$productId'>&nbsp;Add To Cart&nbsp;</a>";                                                  
                                                } else {
                                                   if ($productArray['available_quantity'] > 0) {
                                                      echo "<a href='#' class='add_to_cart' data-product-id='$productId'>&nbsp;Add To Cart&nbsp;</a>";
                                                   } else {
                                                      echo "<a href='contact.php' style='text-decoration: none;'><div class='center low_quantity'>Contact us for availability</div></a>";
                                                   }
                                                }
                                            }
                                          } else {
                                             if ($gDealerId == $master_id_array[1]) {
                                                echo "<a href='#zipcode_finder' class='find_a_dealer' data-product-id='$productId'>Get Your Discount</a>";
                                             } else {
                                                echo "<a href='#zipcode_finder' class='find_a_dealer' data-product-id='$productId'>Buy Now</a>";
                                             }
                                          }
                                          echo "</td>";
                                          echo "</tr></table>";
                                          echo "</span>"; //Span itemprop ="offers"
                                       } else {
                                          echo "<div class='item_call'><i>call for availability</i></div>";
                                       }
                                    } else {
                                       if ($productArray['store_item']) {
                                          echo "<a href='https://buynfdn.com/checkout-mall.php' class='go_to_checkout'>Buy Now!</a>";
                                       } else {
                                          if ($gDealerId == $master_id_array[1]) {
                                             echo "<a href='#zipcode_finder' class='find_a_dealer' data-product-id='$productId'>Get Your Discount</a>";
                                          } else {
                                             echo "<a href='#zipcode_finder' class='find_a_dealer' data-product-id='$productId'>Buy Now</a>";
                                          }
                                       }
                                    }
                                    echo "</div>";
                                    ?>
                                    <?php
                                    if (($dealerArray['enable_commonwealth'] == 1) && $displayPrice != 0 && !empty($displayPrice)) {
                                       echo "<table width='100%'>";
                                       echo "<tr>";
                                       echo "<td id='chk'>";
                                       if ($displayPrice < 750) {
                                          echo "<div class='aslowas_details'>";
                                          echo "";
                                          echo "</div>";
                                       }
                                       if ($displayPrice >= 750) {
                                          echo "<div class='aslowasdetail'>";
                                          echo "as low as $" . estimatedMonthlyPayment($displayPrice) . "/month";
                                          echo "</div>";
                                       }
                                       echo "</td>";
                                       echo "</tr>";
                                       echo "</table>";
                                    }
                                    ?>

                                </div>
                            </div>
                            <div class="detail_pane_bottom"></div>
                        </div>

                        <div class="spacer"></div>

                        <div class="content">
                            <?php
                            if (($gDealerId == 1 || !empty($dealerArray['distributorSet'])) && array_key_exists($productArray['category_id'], $cachedCatalogIds)) {
                               $queryParameters = array();

                               $start_date = date('Y-m-d', strtotime("-7 days"));
                               $end_date = date('Y-m-d', strtotime("-1 day"));

                               $queryParameters[] = $start_date;
                               $queryParameters[] = $end_date;
                               $queryParameters[] = $productArray['category_id'];

                               $cacheKey = "Category_Best_Sellers_" . $productArray['category_id'];

                               $query = "select p.product_id, avg(di.dealer_cost)*sum(dih.delta) as total_cost from products p ";
                               $query .= "left join distributor_inventory di using (product_id) left join distributor_inventory_history dih ";
                               $query .= "using (product_id,distributor_id) where di.quantity > 0 and dih.delta > 0 and dih.date_created between ? and ? ";
                               $query .= "and p.category_id = ? and p.thumbnail_image_id > 0 and p.internal_use_only = 0 and p.inactive = 0 and p.dealer_id is null ";

                               $catalogCache = false;
                               if (class_exists(Memcache) && $memcache = new Memcache()) {
                                  foreach ($gMemcacheServers as $server) {
                                     $memcache->addServer($server);
                                  }
                                  if ($cachedDataStr = $memcache->get($cacheKey)) {
                                     $dataAry = json_decode($cachedDataStr, true);
                                     $catalogCache = true;
                                  }
                               }

                               if (!$catalogCache) {
                                  $cacheQuery = "select p.product_id, p.manufacturer_id, p.category_id, di.distributor_id, avg(di.dealer_cost)*sum(dih.delta) as total_cost from products p ";
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
                                     $dataAry[] = $currData;
                                  }
                                  if ($memcache != null) {
                                     $memcache->set($cacheKey, json_encode($dataAry), false, 57600);
                                     $catalogCache = true;
                                  }
                               }



                               foreach ($dataAry as $currData) {
                                  $categoryBestSeller[] = $currData;
                               }

                               if (!empty($dealerArray['distributorSet'])) {

                                  $query .= "and di.distributor_id in (" . $dealerArray['distributorSet'] . ") ";

                                  if ($catalogCache) {
                                     $allowedDistributors = array_fill_keys(explode(",", $dealerArray['distributorSet']), null);
                                     foreach ($categoryBestSeller as $idx => $currData) {
                                        if (!array_key_exists($currData['d'], $allowedDistributors)) {
                                           unset($categoryBestSeller[$idx]);
                                        }
                                     }
                                  }
                               }


                               if (count($hiddenManufacturers) > 0) {

                                  $query .= "and p.manufacturer_id not in (" . implode(",", $hiddenManufacturers) . ") ";

                                  if ($catalogCache) {

                                     $manufacturerExclusionList = array_fill_keys($hiddenManufacturers, null);

                                     foreach ($categoryBestSeller as $idx => $currData) {
                                        if (array_key_exists($currData['m'], $manufacturerExclusionList)) {
                                           unset($categoryBestSeller[$idx]);
                                        }
                                     }
                                  }
                               }

                               if (!empty($exceptionListBlock)) {

                                  $query .= "and product_id not in (select product_id from exception_list_products where exception_list_id = ?) ";
                                  $queryParameters[] = $exceptionListBlock;

                                  if ($catalogCache) {
                                     $expProducts[] = exceptionBlockList($exceptionListBlock);
                                     $expProductLookup = array_fill_keys($expProducts, null);
                                     foreach ($categoryBestSeller as $idx => $currData) {
                                        if (array_key_exists($currData['p'], $expProductLookup)) {
                                           unset($categoryBestSeller[$idx]);
                                        }
                                     }
                                  }
                               }

                               $query .= "group by p.product_id order by total_cost desc limit 10";

                               $productSet = array();

                               if ($catalogCache) {
                                  $index = 0;
                                  foreach ($categoryBestSeller as $idx => $currData) {
                                     if ($index <= 9) {
                                        $productSet[] = $currData['p'];
                                        $index ++;
                                     }
                                  }
                               } else {
                                  $resultSet = executeQuery($query, $queryParameters);
                                  while ($row = getNextRow($resultSet)) {
                                     $productSet[] = $row['product_id'];
                                  }
                               }



                               if (count($productSet) >= 3) {
                                  echo "<h1 class='best_sellers'>National Bestsellers</h1>";
                                  showCatalogRow($productSet, $useRetailPrice);
                               } else {
                                  $query = "select product_id,sum(distributor_inventory.quantity) as quantity from distributor_inventory ";
                                  $query .= "left join products using (product_id) where quantity > 0 and products.category_id = ? ";
                                  $query .= "and products.internal_use_only = 0 and products.inactive = 0 group by products.product_id";
                                  $resultSet = executeQuery($query, $productArray['category_id']);
                                  while ($row = getNextRow($resultSet)) {
                                     $productSet[] = $row['product_id'];
                                  }
                                  if (count($productSet) >= 3) {
                                     echo "<h1 class='similar_items'>Similar Items</h1>";
                                     showCatalogRow($productSet, $useRetailPrice);
                                  }
                               }
                            }
                            ?>
                        </div>

                        <?php
                        echo "<a class='detail_item_back' href='catalog.php?" . (empty($mall) ? "" : "mall=$mall&") . "category=" . $productArray['category_id'] . "'>SHOW ALL " . $productArray['category_name'] . " " . $productArray['department_name'] . "</a>";
                        ?>
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

        <?php include_once $templateArray['footer'] . "/footer.inc"; ?>

        <?php
        if ($gDealerId == $gDefaultDealerId || in_array($gDealerId, $master_id_array)) {
           include_once "scpt/zipcode_finder.inc";
        }
        ?>

        <script src="scpt/jquery-ui.js"></script>
<?php if ($gDealerId == $gDefaultDealerId || in_array($gDealerId, $master_id_array)) { ?>
           <script src="scpt/fancybox/jquery.fancybox.js"></script>
           <script src="scpt/zipcode_finder.js"></script>
<?php } ?>

        <script src="scpt/shared_v3.js"></script>
        <input type="hidden" id="site_dealer_id" value="<?php echo $GLOBALS['gDealerId'] ?>" />

        <script>
           $(window).load(function () {
               $("#sidebar").css('overflow', 'hidden');
               $("#sidebar").height($("#center_column").height());
               $.each($(".ad_link"), function (index) {
                   if ($(this).offset().top + $(this).height() > $("#center_column").offset().top + $("#center_column").height()) {
                       $(this).remove();
                   }
               });
               $("#sidebar_right").css('overflow', 'hidden');
               $("#sidebar_right").height($("#center_column").height());
               $.each($(".ad_space"), function (index) {
                   if ($(this).offset().top + $(this).height() > $("#center_column").offset().top + $("#center_column").height()) {
                       $(this).remove();
                   }
               });
           });
        </script>
        <?php
        include_once "finance_faqs.htm";
        ?>
        <script> // For loading finance disclaimer content
           $(function () {
               if ($("#commonwealth_finance_enabled").val() == 1)
               {
                   $(".financeMessage").html('<img src="tmpl/CW-SPECIAL_FINANCING-594x55.png" />');
                   $("#disclaimerContent").load("cw_disclaimer.htm");
               }
           });

           $(window).load(function () {
               $.each($("#sidebar_right a img"), function () {
                   var val = /ad-link=([0-9]*)/.exec($(this).parent().attr('href'));
                   console.log(val[1]);
                   $.ajax({
                       url: 'scpt/increment_ad_impression.php',
                       type: "POST",
                       data: {advertising_id: val[1]},
                       dataType: "json"
                   });
               });

           });
        </script>       
<?php include_once "scpt/dealer_tracking_code.inc"; ?>   
    </body>
</html>

<style type="text/css">
    .catalog {
        background-color: #ea1c2b;
        margin: 0;
        padding: 7px 3px 6px 15px;
        clear: both;
        color: #ffffff;
        display: block;
        font-family: Helvetica,Arial,sans-serif;
        font-size: 18px;
        font-weight: normal;
        text-align: left;
        text-transform: uppercase;
    } 

    .detail_item_title
    {
        font-size: 21px;
        padding: 0 6px;
        text-align: center;
        color: #000;
        font-family: Helvetica,Arial,sans-serif;
    }

</style>
