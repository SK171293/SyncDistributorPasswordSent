<?php

$smsCronRoot = str_replace("\SyncDistributorPasswordSent", "", getcwd());
define('__PATH20__', $smsCronRoot.'\sites\all\modules\VestigePOS\VestigePOS');
include_once (__PATH20__.'\Business\SendSMS.php');
include_once (__PATH20__.'\Business\DBHelper.php');
		
SyncDistributorPasswordSent();

function SyncDistributorPasswordSent(){
	try{
		$connectionString = new DBHelper();
		$pdo_db = $connectionString->dbConnection();
		$pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "exec sp_sentMobilePasswordByCron ''";
		$stmt = $pdo_db->prepare($sql);
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if(sizeof($results) > 0)
		{
			for($i=0;$i<sizeof($results);$i++){
				$distributorId = $results[$i]['DistributorId'];
				$mobNo = $results[$i]['MobNo'];
				$Password = $results[$i]['Password'];
				$SendSMS = new SendSMS();
				$response = $SendSMS -> sendSMSToDistributor($mobNo, $distributorId, $Password);
				$smsResponse = substr($response,0,4);
				if($smsResponse == '1701'){
					$sql2="Update [pmmyvestigin].[dbo].[V2_DistributorMaster] Set is_import_ho=2 where DistributorId=$distributorId and is_import_ho=1";
					$stmt = $pdo_db->prepare($sql2);
					$stmt->execute();	
				}							
			}
		}
		$ReturnData= formatJSONResult(json_encode('Successfully executed'),'','');
	}
	catch(Exception $e)
	{
		$ReturnData = formatJSONResult('',$e->getMessage(),'');

	}
		
	return $ReturnData;
}

function formatJSONResult($data,$exception,$tag)
{
	$formattedJsonResultArray = array();
	
	if(count(json_decode($data,1))==0)
	{
		$resultsArray = array();
		$formattedJsonResultArray['Status'] = 0;
		$formattedJsonResultArray['Description'] = "No data found";
		$formattedJsonResultArray['Result'] = $resultsArray;
		$formattedJsonResultArray['Tag'] = $tag;
		$formattedJSONResult = json_encode($formattedJsonResultArray);
	}
	else
	{
		$formattedJsonResultArray['Status'] = 1;
		$formattedJsonResultArray['Description'] = "Successfully Executed";
		$formattedJsonResultArray['Tag'] = $tag;
		$status = 1;
		$description = "Successfully Executed"; 
		$formattedJSONResult = '{"Status"'.':'.$status.','.'"Description"'.':'.'"'.utf8_encode($description).'"'.','.'"Result"'.':'.$data.','.'"Tag"'.':"'.$tag.'"}' ;
	}
	
	return $formattedJSONResult;
}		
		
?>