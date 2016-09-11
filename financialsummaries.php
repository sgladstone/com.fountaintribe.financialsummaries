<?php

require_once 'financialsummaries.civix.php';


function financialsummaries_civicrm_tokens( &$tokens ){
	
	if( CRM_Core_Permission::check('access CiviContribute')){
		$tokens['finances'] = array();
		retrieveFinancialTokenNames( $tokens['finances'] ) ;
	
	}
	
}

function retrieveFinancialTokenNames( &$financial_tokens ){
	 
	require_once('utils/FinancialCategory.php');
	$tmpFinanceCat = new FinancialCategory();
	// Create an array of all the various contribution type prefixes, ie the part before the dash.
	$contribution_type_prefixes  = $tmpFinanceCat->retrieveFinancialPrefixes();

	// At this point we have all the contrib. type prefixes.
	 
	$finance_partial_token = array();
	$finance_partial_token['___total_balance'] = 'Total Balance';
	$finance_partial_token['___total_due___today'] = 'Total Due Today';
	$finance_partial_token['___obligations_show_due___today'] = 'Obligations';
	$finance_partial_token['___contributions___curfiscalyear'] = 'Contributions, current fiscal year';
	$finance_partial_token['___obligations_show_due___today___exclude_after_curfiscalyear_closeditems'] = 'Obligations, Exclude: future fiscal years, closed balances' ;
	$finance_partial_token['___obligations_show_due___today___exclude_after_curfiscalyear_closeditems_bal'] = 'Obligations, Exclude: future fiscal years, closed balances, bal col.' ;
	$finance_partial_token['___obligations_show_due___today___exclude_after_curfiscalyear_closeditems_bal_adj'] = 'Obligations, Exclude: future fiscal years, closed balances, bal col., adj col.' ;
	$finance_partial_token['___obligations_show_due___today___exclude_after_curfiscalyear_closeditems_due'] = 'Obligations, Exclude: future fiscal years, closed balances, due col.' ;
	$finance_partial_token['___obligations_show_due___today___exclude_after_curfiscalyear_closeditems_detailrows'] = 'Obligations, Exclude: detail rows, future fiscal years, closed balances' ;
	$finance_partial_token['___obligations_show_due___today___exclude_closeditems'] = 'Obligations, Exclude: closed balances' ;
	$finance_partial_token['___adjustments___curfiscalyear'] = 'Adjustments, current fiscal year' ;
	$finance_partial_token['___prepayments___curfiscalyear'] = 'Prepayments, current fiscal year' ;
	 
	 
	while (  $cur_ct_prefix = current($contribution_type_prefixes) ){
		reset($finance_partial_token) ;
		//print "<br>cur ct prefix: ".$cur_ct_prefix;
		//print "<br>cur ct prefix key: ".key($contribution_type_prefixes);
		while(  $cur_partial_token = current($finance_partial_token)){
			//print "<br>cur partial token: ".$cur_partial_token;
			$tmp_full_token_key = 'finances.'.key($contribution_type_prefixes).key($finance_partial_token) ;
			$tmp_full_token_label = 'Finances: '.$cur_ct_prefix .': '.$cur_partial_token;
			 
			//print "<br><br>full token key: ".$tmp_full_token_key;
			$financial_tokens[$tmp_full_token_key] = $tmp_full_token_label;
			next($finance_partial_token);
		}
		//print "<br><br>About to move to next, outer loop.";
		next($contribution_type_prefixes);
		 
	}
	 
}


function  financialsummaries_civicrm_tokenValues( &$values, &$contactIDs, $job = null, $tokens = array(), $context = null) {
   
	if ( !empty($tokens['finances'])  && CRM_Core_Permission::check('access CiviContribute') ) {
		 
		require_once ('utils/FinancialUtils.php');
		$tmpFinancialUtils = new FinancialUtils();
		 
		require_once('utils/Prepayment.php');
		$tmpPrepayment = new Prepayment();
	
		
		$tmp_token_format = ""; 
		while( $cur_finances_token_raw = current( $tokens['finances'] )){
			//print "<br>";
			//print "key: ".key($tokens['finances']);
			//print "   value: ".$cur_finances_token_raw;
			//print "<br>";
			$tmp_key = key($tokens['finances']);
			// CiviCRM is buggy here, if token is being used in CiviMail, we need to use the key
			// as the token. Otherwise ( PDF Letter, one-off email, etc) we
			// need to use the value.
			$cur_finances_token = '';
			if(  is_numeric( $tmp_key)){
				$cur_finances_token = $cur_finances_token_raw;
			}else{
				// Its being used by CiviMail.
				$cur_finances_token = $tmp_key;
			}
			 
			 
			$token_as_array = explode("___",  $cur_finances_token );
			$ct_prefix_id = $token_as_array[0];
			$ct_prefix_name =  $token_as_array[1];
			$partial_token =  $token_as_array[2];
			$token_date = $token_as_array[3];
			
			if( isset( $token_as_array[4] ) ){
				$exclusions = $token_as_array[4];
			}else{
				$exclusions = "";
			}
			 
			$exclusions_as_array = explode("_" , $exclusions);
			//print_r($exclusions_as_array);
	
			$exclude_after_date = "";
			if(count($exclusions_as_array) > 2 && $exclusions_as_array[1] == "after"){
				$exclude_after_date = $exclusions_as_array[2];
	
	
			}
			 
			//print "<br>prefix id: ".$ct_prefix_id." prefix name: ".$ct_prefix_name." partial token: ".$partial_token ;
			//print "<br> current token to fill: ".$cur_finances_token;
			$token_to_fill = 'finances.'.$cur_finances_token;
			//$output_wanted = $partial_token;
			// print "<br>partial token: ".$partial_token ;
			 
	
	
	
			require_once ('utils/FormattingUtils.php'  );
			$tmpFormattingUtils = new FormattingUtils();
			
			require_once( 'utils/FinancialDates.php');
			$tmpFinDates = new FinancialDates();
			// to make sure obligation tokens do not include closed balances from prior fiscal years.
			$tmp_fiscal_start_date_sql = $tmpFinDates->get_current_fiscal_year_start_date("sql");
			$where_clause_sql = " ( f1.balance <> 0   OR  date(f1.rec_date) >= date(".$tmp_fiscal_start_date_sql.") ) ";
			 
			// deal with date parameter within token
			$start_date = "";
			$end_date = "";
			$tmpFormattingUtils->determineDateRange( $token_date , $start_date , $end_date );
			 
				
			if( $partial_token  == 'obligations_show_due'){
				//print "<br>Exclusions: ".$exclusions;
				$tmp = strpos($exclusions, 'closeditems');
				//print "<br>tmp: ".$tmp;
				if($tmp === false ){
					// No mention of excluding closed items, so include them.
					$include_closed_items = true;
				}else{
					// token indicates exlude closeditems.
					//print "<br>Token indicates exclude closeditems";
					$include_closed_items = false;
				}
	
				// full token: obligations_show_due___today___exclude_after_curfiscalyear_closeditems_detailrows
				$tmp_details = strpos($exclusions, 'detailrows');
				if( $tmp_details === false){
					$include_detailrows = true;
				}else{
					$include_detailrows = false;
				}
				// TODO: Need to figure out if we should exclude records
				// after a certain date, such as the end of the current fiscal year.
	
				$output_wanted = "detail_table";
				// Get all available columns setup
				$include_cols = array();
				$include_cols["date"] = 1;
				$include_cols["id"] = 1;
				$include_cols["description"] = 1;
				$include_cols["tax"] = 1;
				$include_cols["billed"] = 1;
				$include_cols["recv"] = 1;
				$include_cols["adj"] = 1;
				$include_cols["bal"] = 1;
				$include_cols["due"] = 1;
			  
				// remove the ones the user does not want.
				$tmp_balcol = strpos($exclusions, 'bal');
				if( !( $tmp_balcol === false) ){
					unset($include_cols["bal"]);
				}else{
					//print "<br>user wants bal column";
				}
	
				$tmp_adjcol = strpos($exclusions, 'adj');
				if( !( $tmp_adjcol === false) ){
					unset($include_cols["adj"]);
				}else{
					//print "<br>user wants adj column";
				}
	
				$tmp_duecol = strpos($exclusions, 'due');
				if( !( $tmp_duecol === false) ){
					unset($include_cols["due"]);
				}else{
					//print "<br>user wants due column";
				}
	
	
				/*
		 		(&$values, &$contactIDs, &$ct_prefix_id, &$cur_finances_token, &$output_wanted, &$include_closed_items, &$exclude_after_date, &$end_date_raw,  &$extra_where_clause)
		 		*/
				$tmpFinancialUtils->getObligationsShowSubtotals( $values, $contactIDs,  $ct_prefix_id,
						$token_to_fill, $output_wanted,
		 		 $include_closed_items, $exclude_after_date, $token_date, $where_clause_sql , $include_detailrows, $include_cols  ) 	;
				 
			}else if( $partial_token  == 'contributions' ){
				//$date_range = 'cur_fiscal_year';
				$output_wanted = "detail_table";
				require_once('utils/Payment.php');
				$tmpPayment = new Payment();
				$tmpPayment->getContributionDetails($values, $contactIDs,  $ct_prefix_id, $token_to_fill, $output_wanted, $start_date, $end_date, $tmp_token_format);
				 
			}else if($partial_token  == 'adjustments'){
				//$date_range = 'cur_fiscal_year';
				$output_wanted = "detail_table";
				$tmpFinancialUtils->getAdjustmentDetails($values, $contactIDs,  $ct_prefix_id, $token_to_fill, $output_wanted, $start_date, $end_date);
				 
			}else if($partial_token  == 'prepayments'){
				//$date_range = 'cur_fiscal_year';
				$output_wanted = "detail_table";
	
				$tmpPrepayment->getPrepaymentDetails($values, $contactIDs,  $ct_prefix_id, $token_to_fill , $output_wanted, $start_date, $end_date);
				 
			}else if($partial_token  == 'total_due'){
				$end_date_tmp = $tmpFormattingUtils->getSqlDate($token_date);
				$start_date_tmp = '';
				$include_automated_payments = false;
				$output_wanted = "amount_due";
				//$tmpFinancialUtils->getAmountDue($values, $contactIDs,  $ct_prefix_id, $token_to_fill, $output_wanted, $end_date_tmp, $start_date_tmp, $include_automated_payments);
				$include_detailrows  = false;
	
				$tmpFinancialUtils->getObligationsShowSubtotals( $values, $contactIDs, $ct_prefix_id, $token_to_fill, $output_wanted, $include_closed_items, $exclude_after_date, $token_date, $where_clause_sql, $include_detailrows ) ;
	
				 
				 
			}else if($partial_token  == 'total_balance'){
				$include_closed_items = true;
				$output_wanted = "amount_balance";
	
				$include_detailrows = false;
				$tmpFinancialUtils->getObligationsShowSubtotals( $values, $contactIDs, $ct_prefix_id, $token_to_fill, $output_wanted, $include_closed_items, $exclude_after_date, $token_date, $where_clause_sql, $include_detailrows) ;
	
				 
			}else{
				print "<br>Error: Unrecognized Finances mail merge token:  ".$token_to_fill."<br>";
				 
			}
			 
			next($tokens['finances']);
			//print_r($token_as_array);
		}
	
	
	}
	
}


function financialsummaries_civicrm_tabs( &$tabs, $contactID ) {
// if (pogstone_is_user_authorized('access CiviContribute')){
	if ( 1==1){


	$contactIds = array();
	$contactIds[] = $contactID;

	$token_format = "backoffice_screen";

	require_once ('utils/FinancialUtils.php');
	$tmpFinancialUtils = new FinancialUtils();
	$ct_prefix_id = "0";   // get all contribution type data.
	 
	//require_once ('utils/FormattingUtils.php'  );
	//$tmpFormattingUtils = new FormattingUtils();
	$token_amount_due_long = 'contact.amount_due';
	$start_date_tmp = '';
	$today_date =  date("Ymd");
	$include_automated_payments = false;
	$output_wanted = "amount_due";

	$tmpFinancialUtils->process_obligation_with_balances_subtotals_tokens2($values, $contactIds, $ct_prefix_id ,$token_amount_due_long ,  $need_subtotals,  $token_format, $today_date,$default_start_date,  $need_due_column, $default_exclude_after_date, $output_wanted, $include_closed_items, $where_clause_sql );

	$amount_due_by_today = $values[$contactID][$token_amount_due_long];
	 
	$count_parm = $amount_due_by_today." due";
	//$count_parm = "0";


	$url = CRM_Utils_System::url( 'civicrm/fountaintribe/fstab',
			"reset=1&snippet=1&force=1&cid=$contactID" );
	$tabs[] = array( 'id'    => 'mySupercoolTab',
			'url'   => $url,
			'title' => 'Financial Summary',
			'count' => $count_parm,
			'weight' => 1 );
	 
}

}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function financialsummaries_civicrm_config(&$config) {
  _financialsummaries_civix_civicrm_config($config);
}



/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function financialsummaries_civicrm_xmlMenu(&$files) {
  _financialsummaries_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function financialsummaries_civicrm_install() {
  _financialsummaries_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function financialsummaries_civicrm_uninstall() {
  _financialsummaries_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function financialsummaries_civicrm_enable() {
  _financialsummaries_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function financialsummaries_civicrm_disable() {
  _financialsummaries_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function financialsummaries_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _financialsummaries_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function financialsummaries_civicrm_managed(&$entities) {
  _financialsummaries_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function financialsummaries_civicrm_caseTypes(&$caseTypes) {
  _financialsummaries_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function financialsummaries_civicrm_angularModules(&$angularModules) {
_financialsummaries_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function financialsummaries_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _financialsummaries_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function financialsummaries_civicrm_preProcess($formName, &$form) {

}

*/
