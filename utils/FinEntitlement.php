<?php


class FinEntitlement{
function canPostBackOfficeCreditCard(){

	//require_once( 'utils/CreditCardUtils.php');
	//$ccUtils = new CreditCardUtils();
	if(  $this->HasAtLeastOneLivePaymentProcessor()  ) {

		return true;
	}else{
		return false;

	}



}
function getDrushCommand($client_www_dir ){

	//	print "<br>About to check the version of drush";
	$valid_drush_version_found = false;

	// Verify Drush is available and is expected version.
	$check_drush_cmd = "drush --version";
	$check_drush_output = shell_exec($check_drush_cmd);
	//  print "\nDrush version: ".$check_drush_output;
	// $drush_tmp_needle = "drush version ".self::DRUSH_EXPECTED_VERSION;
	 
	//  print "<hr><br>valid versions: ";
	//  print_r( $this->DRUSH_SUPPORTED_VERSIONS );
	 
	foreach(  $this->DRUSH_SUPPORTED_VERSIONS as $cur){
		//$drush_tmp_needle = "drush version ".$cur;
		$pos =  strpos ($check_drush_output , $cur );
		//	print "<br>".$pos;
		if($pos !== false){
			$valid_drush_version_found = true;
			 
		}else{
			 
		}
		 
	}
	 
	 
	//  if( in_array( $check_drush_output, $this->DRUSH_SUPPORTED_VERSIONS )){
	//    print "\nCorrect version of drush found: ".$check_drush_output;
	if( $valid_drush_version_found ){
		$cd_cmd  = "cd ".$client_www_dir." ;";
		$drush_cmd_partial = "drush ";

		$drush_cmd = $cd_cmd.$drush_cmd_partial;

		return $drush_cmd;
		 
	}else{
		// $pos =  strpos ($check_drush_output , $drush_tmp_needle);
		// if($pos === false)
		print "\nError: Wrong version of drush: ".$check_drush_output;
		return "";

	}

}

function accessPaymentProcessorUpdateSubscription(){
	return  true;
	 
}
 

 

 



function getCMSVersion(){
	// Since the user module is part of Drupal core,
	// its version always reflects the version of Drupal in use.
	return self::getDrupalModuleVersion("user");

}

function HasRecurringPaymentProcessor(){
	$has_recurring = false;
	// The only payment processors that have functioning auto-recurring in CiviCRM are:
	// Authorize.net, PayPalPro (version 3.0), iATS (extension, not the one from core), and eWay (extension, not the one from core)
	//
	$sql = "SELECT count(*) as count FROM `civicrm_payment_processor` pp
	          JOIN civicrm_payment_processor_type pt ON pp.payment_processor_type_id = pt.id
	          WHERE pp.is_test = 0 AND pp.is_active =1
	          AND pt.name IN(  'AuthNet' , 'iATS Payments ACH/EFT' , 'iATS Payments Credit Card' ,  'PayPal', 'eWay_Recurring' )
	          AND  CHAR_LENGTH(pp.user_name) > 1  ";
	$dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
	$tmp_count = "";
	while($dao->fetch()){
		$tmp_count = $dao->count;
	}

	 

	if(strcmp( $tmp_count, "0") == 0 ){
		$has_recurring =  false;
	}else{
		$has_recurring = true;

	}
	return $has_recurring;

}



function HasAtLeastOneLivePaymentProcessor(){
	$sql = "SELECT count(*) as count FROM `civicrm_payment_processor`
		       where is_test = 0  AND is_active =1
		       AND CHAR_LENGTH(user_name) > 1";


	$dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
	$tmp_count = "";
	while($dao->fetch()){
		$tmp_count = $dao->count;
			
	}

	$dao->free();

	if(strcmp( $tmp_count, "0") == 0 ){
		return false;
	}else{
		return true;

	}


}




function getExtensionVersion( $ext_name){
	// get the version of a CiviCRM extension

	try{
		$info = CRM_Extension_System::singleton()->getMapper()->keyToInfo($ext_name);
		$tmp = $info->version;
	}catch( CRM_Extension_Exception_MissingException $exception){
		$tmp = "";
	}catch(  CRM_Extension_Exception_ParseException $exception){
		$tmp = "";
	}
	//print "\n\nExtension info: ".$tmp." \n";
	//print_r( $info) ;
	return $tmp;

	 
}

function getDrupalThemeVersion($theme_name ){
	$cur_user = get_current_user();
	$drupal_db_name = $cur_user."_main";
	//$filename_tmp  = "themes/".$theme_name."/".$theme_name.".theme";


	$sql = "SELECT info FROM ".$drupal_db_name.".system where name = '".$theme_name."' and type = 'theme'" ;

	//print "<br>sql: ".$sql;
	$dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
	$tmp_version = "";
	while($dao->fetch()){
		$tmp_version = $dao->info;
			
	}

	$dao->free();
	//print "<br><br>".$tmp_version;
	$tmp_theme_array = unserialize($tmp_version );



	$tmp_ver_str = $tmp_theme_array['version'];



	return $tmp_ver_str;


}

function getDrupalModuleVersion($module_name){

	$cur_user = get_current_user();
	$drupal_db_name = $cur_user."_main";
	$filename_tmp  = "modules/".$module_name."/".$module_name.".module";

	//$sql = "SELECT info FROM ".$drupal_db_name.".system where filename = '".$filename_tmp."' and type = 'module'" ;
	$sql = "SELECT info FROM ".$drupal_db_name.".system where name = '".$module_name."' and type = 'module'" ;

	//print "<br>sql: ".$sql;
	$dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
	$tmp_version = "";
	while($dao->fetch()){
		$tmp_version = $dao->info;
			
	}

	$dao->free();
	//print "<br><br>".$tmp_version;
	$tmp_module_array = unserialize($tmp_version );



	$tmp_ver_str = $tmp_module_array['version'];



	return $tmp_ver_str;



}







/**************************************************************************************/













}
