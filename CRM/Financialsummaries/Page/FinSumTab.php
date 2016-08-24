<?php

require_once 'CRM/Core/Page.php';

class CRM_Financialsummaries_Page_FinSumTab extends CRM_Core_Page {


  function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('FinSumTab'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));
    
    
     $token_tax_prevyear = 'contact.tax_prev_year';
 

     $token_tax_curyear = 'contact.tax_cur_year';
   
    
     $token_tax_prev_year_only_deductables_long = 'contact.tax_prev_year_only_deductables';
    
    $token_tax_cur_year_only_deductables_long = 'contact.tax_cur_year_only_deductables';
    
     $cid = $_REQUEST['cid'] ;
     
    // CRM_Core_Error::debug("Contact ID from URL: ", $cid);
 // require_once 'util_families.php';
 // require_once 'utils/util_money.php';
    $input_format = 'yyyy-mm-dd';
 require_once ( 'utils/FormattingUtils.php');
 require_once('utils/FinancialDates.php');
 
 $financialDates = new FinancialDates();
 $FormatterUtil = new FormattingUtils(); 
  $fiscal_year_start_date_formatted  = $FormatterUtil->get_date_formatted_short_form($financialDates->get_current_fiscal_year_start_date(), $input_format);
  
  require_once ('utils/FinEntitlement.php');
  $entitlement = new FinEntitlement();
		
		
    $pp_sql = "SELECT pp.id as processor_id FROM civicrm_payment_processor pp 
   join civicrm_payment_processor_type pt ON pp.payment_processor_type_id = pt.id 
   where pt.name = 'iATS Payments ACH/EFT' AND pp.is_active = 1 AND pp.is_test <> 1"; 
 
 $tmp_all_iats_pp = array(); 
   $dao =  & CRM_Core_DAO::executeQuery(  $pp_sql ,   CRM_Core_DAO::$_nullArray ) ;
   while($dao->fetch()){
       $tmp_all_iats_pp[] = $dao->processor_id; 
   
   }
   $dao->free();
 if( count( $tmp_all_iats_pp) > 0){
   // provide back-office user links to ContributionPages using iATS
 $params = array(
  'version' => 3,
  'sequential' => 1,
  'is_active' => 1, 
    
);
 $tmp_ach_pages = array(); 
 $result = civicrm_api('ContributionPage', 'get', $params);
 $values = $result['values'];
 foreach( $values as $cur_contrib_page){
         $pp_ids = $cur_contrib_page['payment_processor'];
         $is_this_iats_page = false; 
         if( is_array( $pp_ids ) ){
           foreach( $pp_ids as $cur){
             $tmp = in_array($cur , $tmp_all_iats_pp ) ; 
             if($tmp){
             	$is_this_iats_page = true; 
             }
           }
         
         }else{
           $is_this_iats_page =  in_array($pp_ids , $tmp_all_iats_pp ) ; 
         
         }
         if($is_this_iats_page ){
         	//print "<br>Page: ".$cur_contrib_page['title']."<br>"; 
 		// print_r($pp_id); 
 		 $tmp_ach_pages[$cur_contrib_page['id'] ] = $cur_contrib_page['title']; 
 	}
 }
 
 
 
 
 }// done with iats payment links

  $sql = "";


    $token_completed_payments_long  = 'contact.completed_payments';
    
    $token_amount_due_long = 'contact.amount_due';
    
    
    $token_amount_due_end_fiscal_long = 'contact.amount_due_end_fiscal';
    
    $token_balance_long = 'contact.balance_remaining';
    
    $token_adjustments_payments_long  = 'contact.adjustment_payments';
    $token_prepayments_long  = 'contact.prepayments';
   
    $token_oblig_to_end_date_subtotals_long =  'contact.obligations_to_end_date_and_subtotals' ; 
    $token_oblig_to_today_subtotals_long =  'contact.obligations_to_today_and_subtotals' ; 
    $token_oblig_to_today_exclude_closed_bals = 'finances.obligations_to_today_exclude_closed_bals';
    $token_oblig_to_today_exclude_after_enddate_closed_bals = 'finances.obligations_to_today_exclude_after_fiscalyear_closed_bals';
          
      
    
   
    
    // Correct mail merge token format.
    $token_finance_obligations_past_and_current   = 'financials.obligations_past_and_current_incl_due_by_today_col_and_subtotals' ; 
    
    $token_payments_beneficiary = 'financials.third_party_payments_for_beneficiary';
    
   //  $total_of_everything = array();
    
 $contactIds = array();
 $contactIds[] = $cid; 
 
 $token_format = "backoffice_screen"; 

 require_once ('utils/FinancialUtils.php');
 $tmpFinancialUtils = new FinancialUtils();
   $ct_prefix_id = "0";   // get all contribution type data.
   
   require_once ('utils/FormattingUtils.php'  );
		$tmpFormattingUtils = new FormattingUtils();
  
 
   
   
   
   // TODO: Need to create parm "WHERE date(f1.rec_date) >= date(20120601000000)"  
   // to make sure obligation tokens do not include closed balances from prior fiscal years. 
   $tmp_fiscal_start_date_sql = $financialDates->get_current_fiscal_year_start_date("sql"); 
   $where_clause_sql = " ( f1.balance <> 0   OR  date(f1.rec_date) >= date(".$tmp_fiscal_start_date_sql.") ) ";
   
   
 // $end_date =  get_first_day_next_fiscal_year(); 
  $fiscal_date_raw =  $financialDates->get_last_day_cur_fiscal_year();
  
 // print "<br>End date: ".$end_date; 
  $end_date_formatted  = $FormatterUtil->get_date_formatted_short_form($fiscal_date_raw, $input_format);
  
  $fiscal_date =  $financialDates->get_last_day_cur_fiscal_year('Ymd');
        
  $need_subtotals = true;  
  $need_due_column = true;    
  $default_start_date = ''; 
   $output_wanted =   "detail_table";
      $include_closed_items = "1";
  $tmpFinancialUtils->process_obligation_with_balances_subtotals_tokens2($values, $contactIds,  $ct_prefix_id , $token_oblig_to_end_date_subtotals_long, $need_subtotals,  $token_format,  $fiscal_date, $default_start_date, $need_due_column, $default_exclude_after_date, $output_wanted, $include_closed_items, $where_clause_sql  );
     
     // get obligations, with end-date of today. // detail_table
    
      $today_date =  date("Ymd");   
     
      $output_wanted =   "detail_table";
      $include_closed_items = "1";
  $tmpFinancialUtils->process_obligation_with_balances_subtotals_tokens2($values, $contactIds, $ct_prefix_id ,$token_oblig_to_today_subtotals_long,  $need_subtotals,  $token_format, $today_date ,$default_start_date,  $need_due_column, $default_exclude_after_date, $output_wanted, $include_closed_items, $where_clause_sql );
     

	// get obligations, with end-date of today, exclude closed balances.
	 $today_date =  date("Ymd");   
      $output_wanted =   "detail_table";
      $include_closed_items_tmp = "0";
  $tmpFinancialUtils->process_obligation_with_balances_subtotals_tokens2($values, $contactIds, $ct_prefix_id ,$token_oblig_to_today_exclude_closed_bals,  $need_subtotals,  $token_format, $today_date,$default_start_date,  $need_due_column, $default_exclude_after_date, $output_wanted, $include_closed_items_tmp, $where_clause_sql );
  
  // get obligations, with end-date of today, exclude future charges (after end of fiscal year) and exclude closed balances
	 $today_date =  date("Ymd");   
      $output_wanted =   "detail_table";
      $include_closed_items_tmp = "0";
      $exclude_after_date_tmp = 'curfiscalyear';
  $tmpFinancialUtils->process_obligation_with_balances_subtotals_tokens2($values, $contactIds, $ct_prefix_id ,$token_oblig_to_today_exclude_after_enddate_closed_bals,  $need_subtotals,  $token_format, $today_date,$default_start_date,  $need_due_column, $exclude_after_date_tmp, $output_wanted, $include_closed_items_tmp, $where_clause_sql );
   
   
   // Now get total balance:
   $today_date =  "";   
      $output_wanted =   "amount_balance";
      $include_closed_items = "1";
  $tmpFinancialUtils->process_obligation_with_balances_subtotals_tokens2($values, $contactIds, $ct_prefix_id ,$token_balance_long ,  $need_subtotals,  $token_format, $today_date,$default_start_date,  $need_due_column, $default_exclude_after_date, $output_wanted, $include_closed_items, $where_clause_sql );
   
   // Now get amount due
  //  $include_automated_payments = false; 
// $empty_end_date = ''; 
// $empty_start_date = '';
 // process_amount_due( $values, $contactIds , $token_amount_due_long , $token_amount_due_long, $empty_end_date, $empty_start_date, $include_automated_payments );
 	//$end_date_tmp = $tmpFormattingUtils->getSqlDate(date("Ymd"));	
	$start_date_tmp = '';
	 $today_date =  date("Ymd");
	$include_automated_payments = false; 
	$output_wanted = "amount_due";
	//$tmpFinancialUtils->getAmountDue($values, $contactIds,  $ct_prefix_id,  $token_amount_due_long, $output_wanted, $end_date_tmp, $start_date_tmp, $include_automated_payments);
	$tmpFinancialUtils->process_obligation_with_balances_subtotals_tokens2($values, $contactIds, $ct_prefix_id ,$token_amount_due_long ,  $need_subtotals,  $token_format, $today_date,$default_start_date,  $need_due_column, $default_exclude_after_date, $output_wanted, $include_closed_items, $where_clause_sql );
  
  // Now get amout due for end of fiscal year. 
 // process_amount_due( $values, $contactIds , $token_amount_due_end_fiscal_long , $token_amount_due_end_fiscal_long, $end_date, $empty_start_date, $include_automated_payments );  
  $end_date_tmp = $fiscal_date;
	$start_date_tmp = '';
	$include_automated_payments = false; 
	$output_wanted = "amount_due";
	//$exclude_after_date_tmp = 'curfiscalyear';
	//$tmpFinancialUtils->getAmountDue($values, $contactIds,  $ct_prefix_id, $token_amount_due_end_fiscal_long, $output_wanted, $end_date_tmp, $start_date_tmp, $include_automated_payments);
  	$tmpFinancialUtils->process_obligation_with_balances_subtotals_tokens2($values, $contactIds, $ct_prefix_id , $token_amount_due_end_fiscal_long ,  $need_subtotals,  $token_format, $end_date_tmp  ,$default_start_date,  $need_due_column, $default_exclude_after_date, $output_wanted, $include_closed_items, $where_clause_sql );
  
  // deal with date parameter within token
 	$start_date = "";
 	$end_date = "";
 	$token_date_tmp = 'curfiscalyear'; 
 	
 	require_once('utils/FormattingUtils.php');
  	$formatter = new FormattingUtils();
 
 	$formatter->determineDateRange( $token_date_tmp , $start_date , $end_date );
 	
 	//print "<br><br>Start date: ".$start_date."  end date: ".$end_date; 
 	
  $date_range = 'cur_fiscal_year';
  $output_wanted = "detail_table";
  $ct_prefix_id = ''; 
  $tmpFinancialUtils->getAdjustmentDetails($values, $contactIds,  $ct_prefix_id, $token_adjustments_payments_long, $output_wanted,  $start_date , $end_date);


  require_once('utils/Payment.php');
  $tmpPayment = new Payment();
  $tmpPayment->getContributionDetails($values, $contactIds,  $ct_prefix_id, $token_completed_payments_long, $output_wanted, $start_date , $end_date, $token_format);
  
  
  $tmpPayment->getThirdPartyPaymentsForBeneficiary($values, $contactIds,  $ct_prefix_id, $token_payments_beneficiary, $output_wanted, $date_range);
   
   require_once('utils/Prepayment.php');
   $tmpPrepayment = new Prepayment();
   $tmpPrepayment->getPrepaymentDetails($values, $contactIds,  $ct_prefix_id, $token_prepayments_long, $output_wanted, $start_date , $end_date);	
	 	
  
 require_once('utils/TaxTools.php');
 $tmpTaxTools = new TaxTools();
 
    $cur_year = date('Y') ;
    $prev_year = date('Y') - 1 ;
 
    $start_date_prev_year = $prev_year."0101";
    $end_date_prev_year = $prev_year."1231";
    
 $only_show_deductables = false;    
// $default_start_month = '1';
// $default_start_day = '1';
 // $tmpTaxTools->process_tax_year( $values,  $contactIDs , $ct_prefix_id,   $token_to_fill ,
 //				 $only_show_deductibles,  $start_date, $end_date, $format_parm , $font_size );
 		
 		
 $tmp_token_prevyear = $token_tax_prevyear; 				 
 $tmpTaxTools->process_tax_year( $values, $contactIds , $ct_prefix_id,  $tmp_token_prevyear , 
         $only_show_deductables, $start_date_prev_year, $end_date_prev_year, $token_format );
 
 
 
 // calc. tax letter for current calendar year.
 $cur_year = date('Y');
  //$prev_year = date('Y') - 1 ;
 
    $start_date_cur_year = $cur_year."0101";
    $end_date_cur_year = $cur_year."1231";
 
 $tmp_token_curyear = $token_tax_curyear; 
  $tmpTaxTools->process_tax_year( $values, $contactIds , $ct_prefix_id,   $tmp_token_curyear,   
    $only_show_deductables,$start_date_cur_year, $end_date_cur_year, $token_format );
 
  
  // TODO: Verify that PayBalances extension is active
  //   $paybal_page_id = CRM_Core_DAO::singleValueQuery("SELECT contribution_page_id from civicrm_contribution_paybalance_id");
   
     if(   $values[$cid][$token_amount_due_long] == "0.00" ){
      $money_due = false;
   }else{
      $money_due =  true;
   }
   
     require_once('utils/RelationshipTools.php');
   $tmpRelTools = new RelationshipTools();
   
   $cid_list_arr =  $tmpRelTools->get_all_permissioned_ids($cid) ; 
   
   $cid_list = implode("," ,  $cid_list_arr); 

   if( strlen( $cid_list) > 0){
   $sql = "SELECT count(*) as count from civicrm_pledge p
    WHERE p.contact_id IN ( $cid_list ) AND p.status_id IN (2, 5, 6 )  ";
    
   $count_unpaid_pledges = CRM_Core_DAO::singleValueQuery($sql);
   }else{
     $count_unpaid_pledges = 0; 
   }
// Assign all the variables that are needed by the smarty template
 
 

   $this->assign('count_unpaid_pledges' ,  $count_unpaid_pledges); 

   $this->assign('cid' , $cid);

    $this->assign( 'paybal_page_id',  $paybal_page_id ); 

  //  $this->assign('can_post_back_office_credit_card' ,  $entitlement->canPostBackOfficeCreditCard());
    $this->assign('count_ach_pages', count( $tmp_ach_pages ));

    $this->assign('ach_pages', $tmp_ach_pages); 	

   $this->assign('token_tax_prevyear', $token_tax_prevyear );	
   $this->assign('token_tax_curyear', $token_tax_curyear );
   $this->assign('token_tax_prev_year_only_deductables_long', $token_tax_prev_year_only_deductables_long);

   $this->assign('token_balance_long', $token_balance_long);

   $this->assign('token_amount_due_long', $token_amount_due_long); 
 
   $this->assign( 'money_due', $money_due);
   

  $this->assign( 'token_oblig_to_today_exclude_after_enddate_closed_bals', $token_oblig_to_today_exclude_after_enddate_closed_bals ); 
  $this->assign( 'token_oblig_to_today_exclude_closed_bals',  $token_oblig_to_today_exclude_closed_bals );
  $this->assign( 'token_oblig_to_today_subtotals_long', $token_oblig_to_today_subtotals_long );
  $this->assign( 'token_amount_due_end_fiscal_long', $token_amount_due_end_fiscal_long );
  $this->assign( 'end_date_formatted', $end_date_formatted );
  $this->assign( 'fiscal_year_start_date_formatted' , $fiscal_year_start_date_formatted ); 
  $this->assign( 'token_oblig_to_end_date_subtotals_long', $token_oblig_to_end_date_subtotals_long );

  $this->assign( 'token_completed_payments_long', $token_completed_payments_long ); 
  $this->assign( 'token_adjustments_payments_long' , $token_adjustments_payments_long);
  $this->assign( 'token_prepayments_long', $token_prepayments_long );
  $this->assign( 'token_payments_beneficiary' , $token_payments_beneficiary );

 $this->assign('cur_year', $cur_year);
 $this->assign('prev_year', $prev_year);

    $this->assign('values', $values); 

    parent::run();
    
    
  }
  
  
}