<?php

$smsCronRoot = str_replace("\SyncDistributorPasswordSent", "", getcwd());
define('__PATH21__', $smsCronRoot.'\sites\all\modules\VestigePOS\VestigePOS');
include_once (__PATH21__.'\Business\SendSMS.php');
include_once (__PATH21__.'\Business\DBHelper.php');
		
InvoiceVoucherConsumeSMSSent();

function InvoiceVoucherConsumeSMSSent(){
	try{
		$connectionString = new DBHelper();
		$pdo_db = $connectionString->dbConnection();
		$pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "Select DistributorMobNumber MobileNo,Dm.DistributorId,VoucherSrNo,Location,ivct.InvoiceNo,Convert(date,ivct.modifiedDate) modifiedDate 
					from [dbo].[InvoiceVoucherConsumptionTrack] ivct(NOLOCK)
					Inner Join DistributorMaster DM (NOLOCK)
					On ivct.DistributorId = DM.DistributorId
					Where Status=1";
		$stmt = $pdo_db->prepare($sql);
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if(sizeof($results) > 0)
		{
			for($i=0;$i<sizeof($results);$i++){
				$distributorId = $results[$i]['DistributorId'];
				$mobileNo = $results[$i]['MobileNo'];
				$voucherSrNo = $results[$i]['VoucherSrNo'];
				$location = $results[$i]['Location'];
				$invoiceNo = $results[$i]['InvoiceNo'];
				$date = $results[$i]['modifiedDate'];
				$SendSMS = new SendSMS();
				$response = $SendSMS -> sendInvoiceVoucherConsumeSMS($mobileNo,$voucherSrNo,$location,$distributorId,$date);
				$msgApiResponse = json_decode($response,true);
				$smsResponse = substr($msgApiResponse['msg'],0,4);
				if($smsResponse == '1701'){
					$sql2="Update InvoiceVoucherConsumptionTrack Set Status=2 where DistributorId=$distributorId and InvoiceNO='$invoiceNo'";
					$stmt = $pdo_db->prepare($sql2);
					$stmt->execute();	
				}				
			}
		}
		$ReturnData= formatJSONResult(json_encode('Successfully executed'),'','');
	}catch(Exception $e){
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