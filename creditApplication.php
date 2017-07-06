<?php
if(isset($_GET['process']))
{
    if($_GET['process'] == 'checkout')
    $gProgramCode = "CHECKOUT";
}
include_once "scpt/utilities.inc";
include_once "scpt/catalog_functions.inc";
include_once "scpt/cw_config.inc";

if(!empty($_GET['token'])){
    $token = $_GET['token'];
    if(!checkToken($token, $_COOKIE['PHPSESSID'])){
         header("Location:http://".$dealerArray['dealer_url']);
    }
}
$shoppingCartId = $_SESSION["shopping_cart_id"];
if (empty($shoppingCartId)) {
    $shoppingCartDisabled = true;
    $dealerArray = getDealerInfo($gDealerId);
    $dealerArray['dealer_url'] = getFieldFromId('domain_name', 'domain_names', 'dealer_id', $gDealerId);
    $templateArray = getTemplateInfo($gDealerId);
} else {
    // set dealer id using shopping cart since domain is currently nfdnetwork.com
    $gDealerId = getFieldFromId('dealer_id', 'shopping_carts', 'shopping_cart_id', $shoppingCartId);
    $dealerArray = getDealerInfo($gDealerId);
    $dealerArray['dealer_url'] = getFieldFromId('domain_name', 'domain_names', 'dealer_id', $gDealerId);
    $templateArray = getTemplateInfo($gDealerId);
} 
$returnArray = array();
$urlAction = $_GET['url_action'];
switch ($urlAction) {
    

    case 'unsetcartinfo':
        $status = Array();
        $res = executeQuery("delete from shopping_cart_items where shopping_cart_id = ?", $_SESSION["shopping_cart_id"]);
        foreach ($_SESSION['cart_info'] as $arrayKey => $arrayValue) {
            unset($_SESSION['cart_info'][$arrayKey]);
        }
        unset($_SESSION['cart_info']);
        $status['cart_empty'] = $res['affected_rows'];
        echo json_encode($status);
        exit;


    case 'save':
        $contactId = getFieldFromId('contact_id','users','user_name',$gUserName);
        
        $_SESSION['cart_info']['tax_charge'] = $_POST['tax_charge'];
        $_SESSION['cart_info']['shipping_charge'] = $_POST['shipping_charge'];
        $_SESSION['cart_info']['additional_charge'] = $_POST['additional_charge'];
        $_SESSION['cart_info']['ship_to_bill'] = $_POST['ship_to_bill'];
        $_SESSION['cart_info']['ship_firearms'] = $_POST['ship_firearms'];
        $_SESSION['cart_info']['ship_nonfirearms'] = $_POST['ship_nonfirearms'];

        /*$_SESSION['cart_info']['email_address'] = $_POST['email_address'];
        $_SESSION['cart_info']['phone_number'] = $_POST['phone_number'];
        $_SESSION['cart_info']['notes'] = $_POST['notes'];
        $_SESSION['cart_info']['cc_name'] = $_POST['cc_name'];
        $_SESSION['cart_info']['cc_address'] = $_POST['cc_address'];
        $_SESSION['cart_info']['cc_city'] = $_POST['cc_city'];
        $_SESSION['cart_info']['cc_state'] = $_POST['cc_state'];
        $_SESSION['cart_info']['cc_zip'] = $_POST['cc_zip'];
        $_SESSION['cart_info']['cc_country'] = $_POST['cc_country'];
        $_SESSION['cart_info']['cc_exp_month'] = $_POST['cc_exp_month'];
        $_SESSION['cart_info']['cc_exp_year'] = $_POST['cc_exp_year'];
        $_SESSION['cart_info']['order_total'] = $_POST['order_total'];*/
        $_SESSION['cart_info']['contact_id'] = $contactId;
        $_SESSION['cart_info']['email_address'] = $_POST['cw_profile_email'];
        $_SESSION['cart_info']['phone_number'] = $_POST['cw_profile_phone'];
        //$_SESSION['cart_info']['notes'] = $_POST['notes'];
        $_SESSION['cart_info']['cc_firstname'] = $_POST['cw_profile_fname'];
        $_SESSION['cart_info']['cc_lastname'] = $_POST['cw_profile_lname'];
        $_SESSION['cart_info']['cc_address1'] = $_POST['cw_profile_address1'];
        $_SESSION['cart_info']['cc_address2'] = $_POST['cw_profile_address2'];
        $_SESSION['cart_info']['cc_city'] = $_POST['cw_profile_city'];
        $_SESSION['cart_info']['cc_state'] =$_POST['cw_profile_ship_state'];
        $zipcode = $_POST['cw_profile_zipcode'];
        $zipcode = str_replace('-','',$zipcode);
        for ($i = 0; $i < 9; $i++)
        {
            $zip.=substr($zipcode, $i, 1)!='' ? substr($zipcode, $i, 1):0;
            if (strlen($zip)==5) {
                $zip.= '-';
            } 
        }
        $_SESSION['cart_info']['cc_zip']=$zip;     
        
        //$_SESSION['cart_info']['cc_country'] = $_POST['cc_country'];
        //$_SESSION['cart_info']['cc_exp_month'] = $_POST['cc_exp_month'];
        //$_SESSION['cart_info']['cc_exp_year'] = $_POST['cc_exp_year'];
        $_SESSION['cart_info']['order_total'] = $_POST['order_total'];
        
        #$_SESSION['cart_info']['gift_card_number'] = $_POST['gift_card_number'];
        $_SESSION['cart_info']['discount_code'] = $_POST['discount_code'];
        $_SESSION['cart_info']['add_shipping_insurance'] = $_POST['add_shipping_insurance'];

        $_SESSION['cart_info']['customer_shipping_address_validated'] = $_POST['customer_shipping_address_validated'];
        $_SESSION['cart_info']['ship_name'] = $_POST['ship_name'];
        $_SESSION['cart_info']['ship_address'] = $_POST['ship_address'];
        $_SESSION['cart_info']['ship_city'] = $_POST['ship_city'];
        $_SESSION['cart_info']['ship_state'] = $_POST['ship_state'];
        $_SESSION['cart_info']['ship_zip'] = $_POST['ship_zip'];
        $_SESSION['cart_info']['ship_country'] = $_POST['ship_country'];
        $_SESSION['cart_info']['ship_address_type'] = $_POST['customer_shipping_address_type'];

        $_SESSION['cart_info']['ffl_shipping_address_validated'] = $_POST['ffl_shipping_address_validated'];
        $_SESSION['cart_info']['ffl_name'] = $_POST['ffl_name'];
        $_SESSION['cart_info']['ffl_address'] = $_POST['ffl_address'];
        $_SESSION['cart_info']['ffl_city'] = $_POST['ffl_city'];
        $_SESSION['cart_info']['ffl_state'] = $_POST['ffl_state'];
        $_SESSION['cart_info']['ffl_zip'] = $_POST['ffl_zip'];
        $_SESSION['cart_info']['ffl_country'] = $_POST['ffl_country'];
        $_SESSION['cart_info']['ffl_phone'] = $_POST['ffl_phone'];
        $_SESSION['cart_info']['ffl_address_type'] = $_POST['ffl_shipping_address_type'];
      
        /*$dealer_id = $gDealerId;
        $firstName = $_POST['cw_profile_fname'];
        $lastName = $_POST['cw_profile_lname'];
        $email_address = $_POST['cw_profile_email'];
        $phone_number= $_POST['cw_profile_phone'];
        $address_1 = $_POST['cw_profile_address1'];
        $address_2 = $_POST['cw_profile_address2'];
        $city = $_POST['cw_profile_city'];
        $state = $_POST['cw_profile_ship_state'];
        $zip_code = $_POST['cw_profile_zipcode'];
        $res = executeQuery("update contacts set
            dealer_id = ?,
            first_name = ?,
            last_name = ?,
            address_1 = ?,
            address_2 = ?,
            city = ?,
            state = ?,
            zip_code = ?,
            email_address = ?,
            phone_number = ?
            where contact_id =?",$dealer_id,$firstName,$lastName,$address_1,$address_2,$city,$state,$zip_code,$email_address,$phone_number,$contactId);
            $returnArray['message'] = $res['query'];
            $returnArray['status'] = $res['affected_rows'];*/
            $returnArray['status'] = 'saved';
            echo json_encode($returnArray);
            exit;
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?php echo $dealerArray['dealer_name']; ?></title>
        <meta name="description" content="Checkout - <?php echo $dealerArray['dealer_name'] . " - " . $dealerArray['site_description']; ?>">
        <link rel="stylesheet" href="templates/default/universal-styles-v5.css">
        <link rel="stylesheet" href="<?php echo $templateArray['path'] ?>/styles.css">
        <link rel="stylesheet" href="<?php echo $templateArray['path'] ?>/checkout-d.css">
        <link rel="stylesheet" href="scpt/custom-theme/jquery-ui.css">
        <link rel="stylesheet" href="scpt/fancybox/jquery.fancybox.css">
        <style>
            #login_message { font-size: 11px; font-style: italic; color: #ae2c2c; text-align: center; display: none; }
            #account_create_message { font-size: 11px; font-style: italic; color: #ae2c2c; text-align: center; display: none; }
            #login_account, #cancel_login_account, #create_account, #cancel_create_account { font-size: 12px; }
            #process_loading { margin: 12px 0 0 0; }
            #process_close { display: none; }
            #process_message { margin: 12px; font-size: 15px; line-height: 27px; color: #666; font-style: italic; }
            #additional_charge_note { padding: 3px 12px; color: #666; text-align: center; font-size: 11px; }
            #processor_div { width: 500px; }
            .processor_div { font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #333; text-align: center; }
            .process_text { font-size: 15px; color: #666; }
            .confirm, .cancel { font-size: 12px }
            .fixed-dialog{position: fixed;top: 50px;left: 50px;z-index: 1100;}
            .ui-button-icon-only .ui-icon{left:0%;top:0%}
            .ui-widget-header{background: #a11300}
            .ui-dialog .ui-dialog-title{color:#fff;}
            .ui-widget-overlay { position: fixed;z-index: 1050;}
            .ui-dialog{z-index: 1100;}
            .noclose .ui-dialog-titlebar-close{display:none;} 
            #creditApplicationContent{background:#ffffff;}
            #loader { position:absolute;top:42%;padding-left:450px;padding-top: 150px;}
        </style>
         
      
        <script src="scpt/jquery.js"></script>
        
        <script type="text/javascript">
	/*	jQuery(document).ready(function($) {
		$(window).on('popstate', function() {
	alert('This operation is not permitted'); 
		//window.history.forward();   
                });
         }); */
         
        </script>     
        
        
        <script src="scpt/jquery-ui.js"></script>
        <script src="scpt/fancybox/jquery.fancybox.js"></script>
        <?php if (file_exists($templateArray['path'] . "/custom_checkout.js")) { ?>
            <script src="<?php echo $templateArray['path'] ?>/custom_checkout.js"></script>
        <?php } ?>
        <!--[if lt IE 9]>
        <script src="scpt/modernizr-2.0.6.js"></script>
        <![endif]-->
        <?php include_once "scpt/google_code.inc"; ?>
        <script type="text/javascript">
        window.history.forward();
        function noBack()
        {
               window.history.forward();    
        }     
        </script>   
    </head>
    <body onload="noBack();" onpageshow="if (event.persisted) noBack();" onunload="">  
        <input type="hidden" id="site_dealer_id" value="<?php echo $gDealerId ?>" />
        <input type="hidden" id="dealer_url" value="<?php echo $dealerArray['dealer_url']; ?>">
        <input type="hidden" id="g_php_self" value="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" id="contactid" value="<?php echo empty($_SESSION['cart_info']['contact_id'])?'':$_SESSION['cart_info']['contact_id'];?>">
        <input type="hidden" id="orderid" value="<?php echo empty($_SESSION['cart_info']['order_id'])?'':$_SESSION['cart_info']['order_id'];?>">
         <?php if ($dealerArray['enable_global_login'] == 1 && (isset($_SESSION['global_user_session_data']) && !empty($_SESSION['global_user_session_data']))) {
                $globalUsername = getFieldFromId('session_data', 'sessions', 'session_id', $_SESSION['global_user_session_data']);
                ?>
                <div id="welcomenote">
                    <span style="vertical-align: middle; margin-right: 10px;color:#fff">Welcome, <?php echo $globalUsername; ?></span>                                       
                    <span style="color: #fff;">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
                    <a id="gLogout" href="http://<?php echo $dealerArray['dealer_url'] ?>/globallogout.php?zy=<?php echo $_SESSION['global_user_session_data'];?>" style="color:#fff;vertical-align: bottom; margin-right: 10px;">Logout</a>
                </div>
                <?php
            }?>
            <?php include_once (empty($templateArray['header']) ? "templates/default" : $templateArray['header']) . "/header.inc"; ?>
        
      <div id="creditApplicationContent">
            <div class="section_h1">Fill in our Credit Application</div>
            <img id="loader" src="tmpl/ajax-loader.gif" width="auto" height="auto" alt="loading gif"/>
            <iframe id="iframeid" src="<?php echo $creditApplicationUrl; ?>?fromWeb=Y&WDLR=NFDN&MDLRL=<?php echo $gDealerId; ?>&WPAPPA5=<?php echo $_SESSION['cart_info']['contact_id']; ?>&WPAPPN5=<?php echo $_SESSION['cart_info']['order_id']; ?>&WPROCD=<?php echo $_SESSION['cart_info']['order_total']; ?>&WPFNAME=<?php echo $_SESSION['cart_info']['cc_firstname']; ?>&WPLNAME=<?php echo $_SESSION['cart_info']['cc_lastname']; ?>&WPEMAIL=<?php echo $_SESSION['cart_info']['email_address'] ?>&WPADDR1=<?php echo $_SESSION['cart_info']['cc_address1']; ?>&WPHPHON=<?php echo $_SESSION['cart_info']['phone_number']; ?>&WPCITY=<?php echo $_SESSION['cart_info']['cc_city']; ?>&WPSTATE=<?php echo $_SESSION['cart_info']['cc_state']; ?>&WPZIP=<?php echo $_SESSION['cart_info']['cc_zip']; ?>" frameborder="1" width="100%" height="4700" align="top"></iframe>
         </div>
        <?php include_once (empty($templateArray['footer']) ? "templates/default" : $templateArray['footer']) . "/footer.inc"; ?>

        <div id="error_tip"><div id="error_tip_blip"><img src="tmpl/error_blip.png"></div><div id="error_tip_contents"><span id="error_tip_message"></span></div></div>

        <a href="#processor_div" id="do_process"></a>

        <script>
            var dealerUrl = $("#dealer_url").val();
            var sessionData = "<?php echo $_SESSION['global_user_session_data'];?>";
            $(document).ready(function () { 
               
                  $('#iframeid').on('load', function () {
                    $('#loader').hide();
                }); 
                $('#gLogout').click(function(){
                    window.onbeforeunload = null;
                    window.open("http://"+dealerUrl+"/globallogout.php?zy="+sessionData,"_top");
                });

    </script>
   <script>
        function disableF5(e) { if ((e.which || e.keyCode) == 116) e.preventDefault(); };
        $(document).on("keydown", disableF5);
            $(window).on("focus", function(e) {
        
        })
        $(document).mousedown(function(e){ 
            if( e.button == 2 ) { 
             // window.location.reload();
              return false; 
            } 
            return true; 
          });         
           
        </script>

    </body>
</html>

