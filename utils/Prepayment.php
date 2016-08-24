<?php


 class Prepayment{



  function getExcludePrepaymentsSQL(){
  	$sql = " AND (ct.name NOT LIKE 'prepayment-%' AND ct.name NOT LIKE '%---prepayment-%' ) ";
  	return $sql;
  
  }


  function getPrepaymentDetails(&$values, &$contactIDs , &$ct_prefix_id, &$token_to_fill, &$output_wanted, &$start_date, &$end_date   ){


   if( count($contactIDs) == 0 ){
	// no contacts, nothing to do. 
 	return; 
   }
     
     
   if( strlen( $start_date ) > 0 && strlen( $end_date) > 0){
    // $date_where_clause = " AND ( date(contrib.receive_date) >= '$start_date'  AND date(contrib.receive_date) <= '$end_date' ) "; 
     
   }else{
   	print "<br><br><br><b>ERROR: getPrepaymentDetails: Start date and end dates are required. </b>"; 
   	return ; 
   
   }
   
    require_once('utils/RelationshipTools.php');
   $tmpRelTools = new RelationshipTools(); 
   $cid_list =  $tmpRelTools->get_contact_ids_for_sql($contactIDs) ; 
   
   //$tmp_contrib_type_ids_for_sql = 	getContributionTypeWhereClauseForSQL( $ct_prefix_id);
   require_once('utils/FinancialCategory.php') ;
	
	$tmpFinancialCategory = new FinancialCategory();
	
	$prefix_array = array();
	$prefix_array[] = $ct_prefix_id; 
	$tmp_contrib_type_ids_for_sql   = $tmpFinancialCategory->getContributionTypeWhereClauseForSQL($prefix_array); 
	if(strlen($tmp_contrib_type_ids_for_sql ) > 0 ){
		$tmp_contrib_type_ids_for_sql  = " AND ".$tmp_contrib_type_ids_for_sql ; 
	}
	
		
		
		$sql_str = "";
		
		
		$sql_str ="select t1.* , t2.symbol from ( (SELECT contrib.contact_id as contact_id, ct.name as contrib_type , contrib.total_amount as total_amount,
month( contrib.receive_date ) as mm_date, day(contrib.receive_date ) as dd_date , year(contrib.receive_date ) as yyyy_date,  
 contrib.currency, contrib.source, valA.label, valB.label as pay_method, contrib.check_number
	FROM civicrm_contribution contrib,
	civicrm_financial_type ct,
	civicrm_option_value valA, 
	civicrm_option_group grpA,
	civicrm_option_value valB, 
	civicrm_option_group grpB
	WHERE 
	contrib.financial_type_id = ct.id
	AND contrib.contribution_status_id = valA.value
	AND  valA.option_group_id = grpA.id 
	AND grpA.name = 'contribution_status'
	AND (ct.name LIKE 'prepayment-%' OR ct.name LIKE '%---prepayment-%' )
	AND contrib.payment_instrument_id = valB.value
	AND  valB.option_group_id = grpB.id 
	AND grpB.name = 'payment_instrument' ".$tmp_contrib_type_ids_for_sql."
	and contrib.contact_id in ( $cid_list )
	and contrib.contribution_status_id = valA.value
	AND valA.name IN ('Completed' )
	and contrib.is_test = 0
	".$date_where_clause."
order by contrib.receive_date )
UNION ALL
( SELECT contrib.contact_id as contact_id, ct.name as contrib_type , contrib.total_amount as total_amount,
month( contrib.receive_date ) as mm_date, day(contrib.receive_date ) as dd_date , year(contrib.receive_date ) as yyyy_date,  
 contrib.currency, contrib.source, valA.label, '' as pay_method, contrib.check_number
	FROM civicrm_contribution contrib,
	civicrm_financial_type ct,
	civicrm_option_value valA, 
	civicrm_option_group grpA
	WHERE 
	contrib.financial_type_id = ct.id
	AND contrib.contribution_status_id = valA.value
	AND  valA.option_group_id = grpA.id 
	AND grpA.name = 'contribution_status'
	AND  contrib.payment_instrument_id is null 
	AND (ct.name LIKE 'prepayment-%' OR ct.name LIKE '%---prepayment-%' )
        ".$tmp_contrib_type_ids_for_sql."
	and contrib.contact_id in ( $cid_list )
	and contrib.contribution_status_id = valA.value
	AND valA.name IN ('Completed' )
	and contrib.is_test = 0
	".$date_where_clause."
) ) as t1 left join civicrm_currency as t2 on t1.currency = t2.name ";

		
		

 // $html_table_begin =  '<table border=0 style="border-spacing: 0; border-style: solid; border-collapse: collapse; width: 100%">';
 $html_table_begin =  '<table border=0 style="width: 100%">';
 $html_table_end = ' </table>  	 ';
 
 require_once('utils/FormattingUtils.php');
  $formatter = new FormattingUtils();
 
 $font_size = $formatter->getPDFfontsize();
 
  $tt_style  = "style='font-size: ".$font_size.";'";
  $total_style = "style='text-align: right; font-weight: bold; font-size: ".$font_size.";'";
 
 
 
 $prev_cid = "";
  $cur_cid_html = "";
  $sub_total = 0;
 
 
 $output_sub_total = 0;
$tmp_total_label = 'Total Amount Prepaid:'; 

$tmp_completed_detail_rows = array(); 
$tmp_completed_sub_total = array();
 
$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;
  $row_num = 0; 
  while ( $dao->fetch( ) ) {
  
  	 $row_num =  $row_num +1; 
  	$cur_cid = $dao->contact_id;
       if ( $cur_cid != $prev_cid ){
       	   $tmp_completed_detail_rows[$cur_cid] = "";
           if ( $prev_cid != ""){
           
             $tmp_completed_sub_total[$prev_cid] = $sub_total;
    	     // Wrap up table for previous contact.
    	    // $sub_total_formatted  = '$ '.number_format( $sub_total, 2 );
    	     // $cur_cid_html = $cur_cid_html.'<tr><td colspan=3> &nbsp; </td></tr><tr><td colspan=2 style="text-align: right; font-weight: bold;">'.$tmp_total_label.'</td><td style="font-weight: bold; text-align: right">'.$sub_total_formatted."</td></tr>"; 
     	     //$cur_cid_html = $cur_cid_html.$html_table_end;
       	     //$values[$prev_cid][$token_short] = $values[$prev_cid][$token_long] = $cur_cid_html;
       	   }
        
        // start html table for this contact
           $cur_cid_html = "";
           $sub_total = 0;
         // $cur_cid_html = $cur_cid_html.$html_table_begin;
          
       }
      $total_amount= $dao->total_amount;
     $received_mm_date = $dao->mm_date;
     $received_dd_date = $dao->dd_date;
     $received_yyyy_date = $dao->yyyy_date;
     $contrib_type = $dao->contrib_type;
     $currency_symbol = $dao->symbol; 
     
     $source = $dao->source;
     // $currency = $dao->currency;
      $status = $dao->label;
      $pay_method = $dao->pay_method; 
      $pay_desc = ""; 
     // $currency_symbol = "$"; 
      
      if(strlen($pay_method) > 0){
      	  $pay_desc = "Paid by ".$pay_method; 
      }
      
      
      if ( $dao->check_number <> "" ){
          $pay_desc = $pay_desc." ".$dao->check_number;
      }
      
      $total_formated = $currency_symbol.number_format($total_amount, 2 );
      
          $tmp_date = $received_yyyy_date.'-'.$received_mm_date.'-'.$received_dd_date ;
        
        
        require_once 'utils/FormattingUtils.php';
         $FormattingUtil = new FormattingUtils();
  
  
  	$input_format = 'yyyy-mm-dd';
  	$tmp_date_formated  = $FormattingUtil->get_date_formatted_short_form($tmp_date, $input_format);
        
        
      
      
      $tmp_description = $contrib_type.' '.$source.' '.$pay_desc;
      
      if( $row_num % 2  == 0){
           $css_name = "even-row";
       
       }else{
       	  $css_name = "odd-row";
       }
       
      $tmp_completed_detail_rows[$cur_cid] = $tmp_completed_detail_rows[$cur_cid]."<tr class=".$css_name."><td ".$tt_style." width='10%'>".$tmp_date_formated."</td><td  ".$tt_style." width='50%'>".$tmp_description."</td><td ".$tt_style." width='20%' align=right>".$total_formated."</td></tr>";
       $sub_total =  $sub_total + $total_amount;
  
  
     $prev_cid = $cur_cid; 
  }
  
  $dao->free( );
  
  $paid_sub_total = $sub_total;
   $tmp_completed_sub_total[$prev_cid] = $sub_total; 
   
  
 require_once('utils/RelationshipTools.php');
   $tmpRelTools = new RelationshipTools();
 
  // Create html and subtotals for each contact, inlcuding every contact they are authorized to.
  foreach ( $contactIDs as $cid ) {
  	
  	 $tmp_html = "";
           $tmp_sub = 0;
          $tmp_html = $tmp_html.$html_table_begin;
          
  	 $rel_ids = $tmpRelTools->get_all_permissioned_ids($cid);
      
	    
          foreach($rel_ids as $rel_cid){
  	   
  	    $tmp_html = $tmp_html.$tmp_completed_detail_rows[$rel_cid];
  	    $tmp_sub = $tmp_sub + $tmp_completed_sub_total[$rel_cid];
  	    
  	}
  	
  	$tmp_html = $tmp_html."<tr><td colspan=3> &nbsp; </td></tr>"; 
  	if( $row_num > 0){
  		$tmp_sub_formatted = $currency_symbol.number_format($tmp_sub, 2);
  		$tmp_html = $tmp_html."<tr><td colspan=2 ".$total_style.">".$tmp_total_label."</td><td ".$total_style.">".$tmp_sub_formatted."</td></tr>"; 
   	}
   	$tmp_html = $tmp_html.$html_table_end;
      
       
    
  	$values[$cid][$token_to_fill] = $tmp_html;
  	//$values[$cid][$token_total_raw] =  $tmp_sub;
  
  }
  
  
  
 
 $format = '';
 require_once ('utils/FinancialUtils.php');
 $tmpFinUtils = new FinancialUtils(); 
 $tmpFinUtils->populate_default_value(  $values, $contactIDs , $token_to_fill, $token_to_fill,   "Nothing Found for this contact", $format); 
 
 
  	
} 


}


?>