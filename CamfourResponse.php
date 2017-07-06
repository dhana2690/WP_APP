<?php 
$gProgramCode = "IMPORT";
error_reporting(0);

include_once "scpt/utilities.cron.inc";
$dealerSet = executeQuery("select distinct(dealer_id) from order_fulfillment_events where order_fulfillment_event_type_id =? and distributor_id = ?",2,4);
echo "dd";
print_r($dealerSet); 
while($row = getNextRow($dealerSet)){
	echo "here";
	print_r(downloadCamfourConfirmationFiles($row['dealer_id']));
	print_r(processCamfourConfirmationFiles($row['dealer_id']));
}

function downloadCamfourConfirmationFiles($dealerId, $debug = 0, $keepFiles = 0) {
    $distributorId = 4;
	$ftpUsername = "clients";
    $ftpPassword = "slipStems14";        
    $filePath = str_replace("/scpt", "", dirname(__FILE__)) . "/filecache/camfour/";	
    $returnArray = array();
	$fileNameArray = array();
    $resultSet = executeQuery("select * from dealer_distributors where dealer_id = ? and distributor_id = $distributorId and inactive = 0", $dealerId);
	
    if ($row = getNextRow($resultSet)) {    	
        $returnArray['count'] = 0;       
			$connectionId = ftp_connect("ezgun.net"); 				
            if (ftp_login($connectionId, $ftpUsername, $ftpPassword)) {				
                ftp_pasv($connectionId, true);  				
			    $fileList = ftp_nlist($connectionId, "/");					
                    foreach ($fileList as $fileName) {	
					    $fileNameArray = explode("-",$fileName);
						if(strpos($fileName, "ACK") !== false && $fileNameArray[4] == $dealerId)
						{
							if (ftp_get($connectionId, $filePath . $fileName, "/" . $fileName, FTP_ASCII)) {
								echo $filePath . $fileName."\n";
								$returnArray['count']++;
								
							} else {
								$returnArray['error'] = $fileName . " download error";
							}
						}
                    }				
			}
        
    } 
    return $returnArray;
}

function processCamfourConfirmationFiles($dealerId, $debug = 0, $keepFiles = 0) {
    $distributorId = 4;
    $filePath = str_replace("/scpt", "", dirname(__FILE__)) . "/filecache/camfour/";
    $filePathBak = str_replace("/scpt", "", dirname(__FILE__)) . "/filecache.bak/";
    $returnArray = array();
    $returnArray['processed'] = 0;
    $productCodeArray = array(); // store distributor product codes with status codes for each
	if ($handle = opendir($filePath)) {			
        while (false !== ($fileName = readdir($handle))) {				
			$fileNameArray = explode("-",$fileName);
			if($fileNameArray[4] == $dealerId){
				$orderID = $fileNameArray[2];		
				$productCodeArray = array(); // store distributor product codes with status codes for each
				$returnArray['filelist'] .= $fileName . " | ";                
				$fileContents = file_get_contents($filePath . $fileName);
				
				$resultSet = executeQuery("select * from dealer_distributors where dealer_id = ? and distributor_id = $distributorId and inactive = 0", $dealerId);
				if ($row = getNextRow($resultSet)) {
				
								$eventType = 4; // Final Disposition
								$query = "select * from order_fulfillment_events where order_id = ? and dealer_id = ? and order_fulfillment_event_type_id = ? ";
								$query .= "and distributor_id = ?";
								$resultSet = executeQuery($query, $orderID, $dealerId, $eventType, $distributorId);  						
								if ($resultSet['affected_rows'] == 0) {                            
									$query = "insert into order_fulfillment_events ";
									$query .= "(order_fulfillment_event_time,order_fulfillment_event_type_id,distributor_id,dealer_id,order_id,content) ";
									$query .= "values (now(),?,?,?,?,?)";
									$resultSet = executeQuery($query, $eventType, $distributorId, $dealerId, $orderID, $fileName . "\n" . $fileContents);
								}
				}
			}  
		}		
    } else {
        $returnArray['error'] = "Dealer distributor not found";
    }
	
    return $returnArray;
}

?>