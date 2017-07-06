<?php 
ini_set('error_reporting', 'E_ALL & ~E_NOTICE');
ini_set('display_errors', '1');
$gProgramCode = "INDEX";
 
include_once "/var/www/html/scpt/utilities.cron.inc";

$CatalogIds = array(1,2,3,4,5,6,7,8,9,10,11,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,62,63,64,66,67,69,70,100104,100660,100712,100713,100714,100823,100827,100828,100837,101088,103367);
 
$start_date = date('Y-m-d',strtotime("-7 days"));
$end_date = date('Y-m-d',strtotime("-1 day"));
echo "\nCatalog Data \n";

if (class_exists(Memcache) && $memcache = new Memcache()) 
{
        foreach ($gMemcacheServers as $server) 
	{
        	$memcache->addServer($server);
        }
        foreach($CatalogIds as $category)
        {
            $memcacheKey = "Category_Best_Sellers_" . $category;
	    $dataAry = array();
            $cacheQuery  = "select p.product_id, p.manufacturer_id, p.category_id, di.distributor_id, avg(di.dealer_cost)*sum(dih.delta) as total_cost from products p ";
            $cacheQuery .= "left join distributor_inventory di using (product_id) left join distributor_inventory_history dih ";
            $cacheQuery .= "using (product_id,distributor_id) where p.inactive = 0 and p.internal_use_only = 0 and di.quantity > 0 and dih.delta > 0 and ";
            $cacheQuery .= "dih.date_created between ? and ? and p.category_id = ? and p.thumbnail_image_id > 0 and p.dealer_id is null ";
            $cacheQuery .= "group by p.product_id order by total_cost desc ";
            
            $resultSet = executeQuery($cacheQuery,$start_date,$end_date,$category);
            while ($row = getNextRow($resultSet)) {
                $currData = array();
                $currData['p'] = $row['product_id'];
                $currData['d'] = $row['distributor_id'];
                $currData['m'] = $row['manufacturer_id'];
                $dataAry[] = $currData;
            }       
            echo $memcacheKey." "."CategoryID : ".$category." Count : ".count($dataAry)."\n";
            echo "Key: ".$memcacheKey." | QueriedDataSize: ".strlen(json_encode($dataAry))  ;
           
            if($cacheDataStr = $memcache->get($memcacheKey)) 
	    {
	          $cacheDataAry = json_decode($cacheDataStr, true);
              echo " | MemcachedDataSize:".strlen($cacheDataStr)."<br/>"; 
	    }

            $memcache->set($memcacheKey, json_encode($dataAry), false,43200);  
       }              
      
}


?>

