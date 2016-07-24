<?php

   class Payment{

/*************************************************************/
   	function getContributionDetails(&$values, &$contactIDs,  &$ct_type_prefix_id, &$token_to_fill, &$output_wanted, &$start_date , &$end_date, &$token_format ){
   		

   if( count($contactIDs) == 0 ){
	// no contacts, nothing to do. 
 	return; 
   }
     require_once('utils/RelationshipTools.php');
   $tmpRelTools = new RelationshipTools();
   
   $cid_list =  $tmpRelTools->get_contact_ids_for_sql($contactIDs) ; 

   // $fiscal_start_date	= get_current_fiscal_year_start_date() ; 
   if( strlen( $start_date ) > 0 && strlen( $end_date) > 0){
     $date_where_clause = " ( date(contrib.receive_date) >= '$start_date'  AND date(contrib.receive_date) <= '$end_date' ) "; 
     
   }else{
   	print "<br><br><br><b>ERROR: getContributionDetails: Start date and end dates are required. </b>"; 
   	return ; 
   
   }
    $currency_symbol = "";

    //$tmp_contrib_type_ids_for_sql = 	getContributionTypeWhereClauseForSQL( $ct_type_prefix_id);
    require_once('utils/finance/FinancialCategory.php') ;
	
	$tmpFinancialCategory = new FinancialCategory();
	
	$prefix_array = array();
	$prefix_array[] = $ct_type_prefix_id; 
	$tmp_contrib_type_ids_for_sql   = $tmpFinancialCategory->getContributionTypeWhereClauseForSQL($prefix_array); 
	if(strlen($tmp_contrib_type_ids_for_sql ) > 0 ){
		$tmp_contrib_type_ids_for_sql  = " AND ".$tmp_contrib_type_ids_for_sql ; 
	}
	
  // $extra_contrib_info_table_sql = "civicrm_value_extra_contribution_info"; 
  // $third_party_col_name = "third_party_payor_26";


 require_once('utils/util_custom_fields.php');

   $custom_field_group_label = "Extra Contribution Info";
   $custom_field_third_party_label = "Third Party Payor";
  
    $customFieldLabels = array($custom_field_third_party_label );
   $extra_contrib_info_table_sql = "";
   $outCustomColumnNames = array();


$error_msg = getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, $extra_contrib_info_table_sql, $outCustomColumnNames ) ;

$third_party_col_name  =  $outCustomColumnNames[$custom_field_third_party_label];

 if(strlen( $third_party_col_name) == 0){
       print "<br>Error: There is no field with the name: '$custom_field_third_party_label' ";
       return; 
 }

		
		$sql_str = "";
		
		
			$third_party_sql_part_a = "SELECT contrib.id as contrib_id , contrib_info.".$third_party_col_name." as contact_id, ct.name as contrib_type , li.line_total as total_amount,
month( contrib.receive_date ) as mm_date, day(contrib.receive_date ) as dd_date , year(contrib.receive_date ) as yyyy_date,  
 contrib.currency, contrib.source, valA.label, valA.name as contrib_status_name, valB.label as pay_method, contrib.check_number, contrib.receive_date, c.display_name as paid_for_contact,  '' as rec_type_desc
	FROM civicrm_line_item li JOIN civicrm_contribution contrib ON  li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'  LEFT JOIN ".$extra_contrib_info_table_sql." as contrib_info ON contrib.id = contrib_info.entity_id
        LEFT JOIN civicrm_contact as c ON contrib.contact_id = c.id ,
	civicrm_financial_type ct,
	civicrm_option_value valA, 
	civicrm_option_group grpA,
	civicrm_option_value valB, 
	civicrm_option_group grpB
	WHERE 
	".$date_where_clause." 
	AND li.financial_type_id = ct.id
	AND contrib.contribution_status_id = valA.value
	AND  valA.option_group_id = grpA.id 
	AND grpA.name = 'contribution_status'
	AND contrib.total_amount <> 0 
	".$tmp_exclude_prepays_sql." 
        AND ( ct.name NOT LIKE 'adjustment-%'  AND ct.name NOT LIKE '%---adjustment-%' )  ".$tmp_contrib_type_ids_for_sql."
	AND contrib.payment_instrument_id = valB.value
	AND  valB.option_group_id = grpB.id 
	AND grpB.name = 'payment_instrument'
	and contrib_info.".$third_party_col_name." in ( $cid_list )
	and contrib.contribution_status_id = valA.value
	AND valA.name IN ('Completed', 'Refunded' )
	and contrib.is_test = 0";

	$third_party_sql_part_b = "SELECT contrib.id as contrib_id , contrib_info.".$third_party_col_name." as contact_id, ct.name as contrib_type ,li.line_total as total_amount, 
	month( contrib.receive_date ) as mm_date, day(contrib.receive_date ) as dd_date , year(contrib.receive_date ) as yyyy_date, 
	contrib.currency, contrib.source, valA.label, valA.name as contrib_status_name, '' as pay_method, contrib.check_number, contrib.receive_date, c.display_name as paid_for_contact ,  '' as rec_type_desc
 FROM civicrm_line_item li JOIN civicrm_contribution contrib ON  li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'
 LEFT JOIN ".$extra_contrib_info_table_sql." as contrib_info ON contrib.id = contrib_info.entity_id 
 LEFT JOIN civicrm_contact as c ON contrib.contact_id = c.id ,
   civicrm_financial_type ct, 
 civicrm_option_value valA, civicrm_option_group grpA 
WHERE 
 ".$date_where_clause." AND
 li.financial_type_id = ct.id AND contrib.contribution_status_id = valA.value AND 
 valA.option_group_id = grpA.id AND grpA.name = 'contribution_status' ".$tmp_exclude_prepays_sql." AND 
 ( ct.name NOT LIKE 'adjustment-%'  AND  ct.name NOT LIKE '%---adjustment-%' ) ".$tmp_contrib_type_ids_for_sql." 
AND  contrib.payment_instrument_id is null 
AND contrib.total_amount <> 0 
AND contrib_info.".$third_party_col_name." in (  $cid_list ) 
and contrib.contribution_status_id = valA.value AND 
valA.name IN ('Completed' , 'Refunded' ) and contrib.is_test = 0";


	$participant_contributions_sql = "SELECT  contrib.id as contrib_id ,contrib.contact_id as contact_id, ct.name as contrib_type , sum(li.line_total) as total_amount, 
	month( contrib.receive_date ) as mm_date, day(contrib.receive_date ) as dd_date , year(contrib.receive_date ) as yyyy_date, 
	contrib.currency, contrib.source, valA.label, valA.name as contrib_status_name, '' as pay_method, contrib.check_number, contrib.receive_date, '' as paid_for_contact ,  '' as rec_type_desc
	FROM civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant' 
	 JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id 
				join civicrm_contribution contrib ON  ep.contribution_id = contrib.id 
	 LEFT JOIN ".$extra_contrib_info_table_sql." as contrib_info ON contrib.id = contrib_info.entity_id ,
	   civicrm_financial_type ct, 
	 civicrm_option_value valA, civicrm_option_group grpA 
	WHERE 
	 ".$date_where_clause." AND
	 li.financial_type_id = ct.id AND contrib.contribution_status_id = valA.value AND 
	 valA.option_group_id = grpA.id AND grpA.name = 'contribution_status' ".$tmp_exclude_prepays_sql." AND 
	 ( ct.name NOT LIKE 'adjustment-%'  AND  ct.name NOT LIKE '%---adjustment-%' ) ".$tmp_contrib_type_ids_for_sql." 
	AND contrib.total_amount <> 0 
	AND contrib.contact_id in (  $cid_list ) and contrib.contribution_status_id = valA.value AND 
	valA.name IN ('Completed' , 'Refunded' ) and contrib.is_test = 0
	AND contrib_info.".$third_party_col_name." IS NULL
	group by contrib.id, ct.id " ; 			
				
				
	$participant_refund_sql = "SELECT  contrib.id as contrib_id ,contrib.contact_id as contact_id, ct.name as contrib_type , ( 0 - sum(li.line_total)) as total_amount, 
	month( contrib.cancel_date ) as mm_date, day(contrib.cancel_date ) as dd_date , year(contrib.cancel_date ) as yyyy_date, 
	contrib.currency, contrib.source, valA.label, valA.name as contrib_status_name, '' as pay_method, contrib.check_number, contrib.cancel_date as receive_date , '' as paid_for_contact ,  'refund_detail'  as rec_type_desc
	FROM civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant' 
	 JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id 
				join civicrm_contribution contrib ON  ep.contribution_id = contrib.id 
	 LEFT JOIN ".$extra_contrib_info_table_sql." as contrib_info ON contrib.id = contrib_info.entity_id ,
	   civicrm_financial_type ct, 
	 civicrm_option_value valA, civicrm_option_group grpA 
	WHERE 
	 ".$date_where_clause." AND
	 li.financial_type_id = ct.id AND contrib.contribution_status_id = valA.value AND 
	 valA.option_group_id = grpA.id AND grpA.name = 'contribution_status' ".$tmp_exclude_prepays_sql." AND 
	 ( ct.name NOT LIKE 'adjustment-%'  AND  ct.name NOT LIKE '%---adjustment-%' ) ".$tmp_contrib_type_ids_for_sql." 
	AND contrib.total_amount <> 0 
	AND contrib.contact_id in (  $cid_list ) and contrib.contribution_status_id = valA.value AND 
	valA.name IN ( 'Refunded' ) and contrib.is_test = 0
	AND contrib_info.".$third_party_col_name." IS NULL
	group by contrib.id, ct.id " ; 				
		//print "<br><br> participant REFUND contrib sql: ".$participant_refund_sql; 		
				
		
    $refund_details_sql = "SELECT contrib.id as contrib_id , contrib.contact_id as contact_id, ct.name as contrib_type , ( 0 - li.line_total ) as total_amount, 
month( contrib.cancel_date ) as mm_date, day(contrib.cancel_date ) as dd_date , year(contrib.cancel_date ) as yyyy_date,  
 contrib.currency, contrib.source, valA.label, valA.name as contrib_status_name,  valB.label as pay_method, contrib.check_number, contrib.cancel_date as receive_date, '' as paid_for_contact, 'refund_detail' as rec_type_desc
	FROM civicrm_line_item li JOIN civicrm_contribution contrib ON  li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'
        LEFT JOIN ".$extra_contrib_info_table_sql." as contrib_info ON contrib.id = contrib_info.entity_id, 
	civicrm_financial_type ct,
	civicrm_option_value valA, 
	civicrm_option_group grpA,
	civicrm_option_value valB, 
	civicrm_option_group grpB
	WHERE 
	 ".$date_where_clause."
	AND li.financial_type_id = ct.id
	AND contrib.contribution_status_id = valA.value
	AND  valA.option_group_id = grpA.id 
	AND grpA.name = 'contribution_status'
	AND contrib.total_amount <> 0 
	".$tmp_exclude_prepays_sql." 
        AND ( ct.name NOT LIKE 'adjustment-%'  AND ct.name NOT LIKE '%---adjustment-%' )  ".$tmp_contrib_type_ids_for_sql."
	AND contrib.payment_instrument_id = valB.value
	AND  valB.option_group_id = grpB.id 
	AND grpB.name = 'payment_instrument'
	AND contrib.contact_id in ( $cid_list )
	AND contrib.contribution_status_id = valA.value
	AND valA.name IN ('Refunded' )
	AND contrib.is_test = 0 
        AND contrib_info.".$third_party_col_name." IS NULL ";
         
         
          $tmp_first_contrib = " select contrib.id , contrib.contact_id ,contrib.source, contrib.currency, contrib.check_number, 
   contrib.contribution_status_id,   contrib.contribution_recur_id , contrib.receive_date, contrib.total_amount, contrib.payment_instrument_id,
   contrib.is_test
       FROM civicrm_contribution contrib 
       WHERE contrib.contribution_recur_id is NOT NULL
       AND (contrib.contribution_status_id = 1 OR contrib.contribution_status_id = 2 ) AND contrib.contact_id in ( $cid_list )     
       GROUP BY contrib.contribution_recur_id 
       HAVING contrib.receive_date = min(contrib.receive_date) ";
         
      /*
      
      
       
       
       // Only consider line item amounts from first contrib.
         $sql_for_recur_received = " select li.id as id, (li.line_total * recur_count.recur_count_completed) as received
        FROM 
        ( ".$tmp_first_contrib.") as c JOIN civicrm_line_item li ON  li.entity_id = c.id AND li.entity_table = 'civicrm_contribution' 
        join civicrm_contribution_recur recur on recur.id = c.contribution_recur_id 
        LEFT JOIN ( SELECT count(*) as recur_count_completed  , contribution_recur_id as id 
                    FROM civicrm_contribution c
                    WHERE c.contribution_recur_id IS NOT NULL
                    AND c.contribution_status_id = 1
                    ".$contrib_cid_sql."
                    GROUP BY c.contribution_recur_id ) as recur_count ON recur_count.id = recur.id 
         join civicrm_financial_type as ct on li.financial_type_id = ct.id
        JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
 			 AND efa.account_relationship = 1 
        	 	JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id 
        where (c.contribution_status_id  is null  || c.contribution_status_id = 1) 
        ".$tmp_recur_where." group by li.id, c.id ";
      
      
      
      */   
      
      /*   
	month( contrib.receive_date ) as mm_date, day(contrib.receive_date ) as dd_date , year(contrib.receive_date ) as yyyy_date, 
	contrib.currency, contrib.source, valA.label, valA.name as contrib_status_name, '' as pay_method, contrib.check_number, contrib.receive_date, '' as paid_for_contact,  '' as rec_type_desc
	*/
      $recurring_contribs_sql = "SELECT contrib.id as contrib_id , contrib.contact_id as contact_id, ct.name as contrib_type , li.line_total as total_amount,  
  month( rcontribs.receive_date ) as mm_date, day(rcontribs.receive_date ) as dd_date , year(rcontribs.receive_date ) as yyyy_date,  
 contrib.currency, contrib.source, valA.label, valA.name as contrib_status_name,  valB.label as pay_method, rcontribs.check_number, rcontribs.receive_date, '' as paid_for_contact, '' as rec_type_desc
	FROM 
	civicrm_line_item li JOIN ( $tmp_first_contrib ) contrib ON  li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'
	JOIN civicrm_contribution rcontribs ON rcontribs.contribution_recur_id = contrib.contribution_recur_id AND rcontribs.contribution_status_id = 1
        LEFT JOIN ".$extra_contrib_info_table_sql." as contrib_info ON contrib.id = contrib_info.entity_id ,
	civicrm_financial_type ct,
	civicrm_option_value valA, 
	civicrm_option_group grpA,
	civicrm_option_value valB, 
	civicrm_option_group grpB
	WHERE 
	 ".$date_where_clause."
	AND li.financial_type_id = ct.id
	AND contrib.contribution_status_id = valA.value
	AND  valA.option_group_id = grpA.id 
	AND grpA.name = 'contribution_status'
	AND contrib.total_amount <> 0 
	".$tmp_exclude_prepays_sql." 
        AND ( ct.name NOT LIKE 'adjustment-%'  AND ct.name NOT LIKE '%---adjustment-%' )  ".$tmp_contrib_type_ids_for_sql."
	AND contrib.payment_instrument_id = valB.value
	AND  valB.option_group_id = grpB.id 
	AND grpB.name = 'payment_instrument'
	AND contrib.contact_id in ( $cid_list )
	AND contrib.contribution_status_id = valA.value
	AND valA.name IN ('Completed', 'Refunded' )
	AND contrib.is_test = 0 
	AND contrib.contribution_recur_id IS NOT NULL
        AND contrib_info.".$third_party_col_name." IS NULL              
        ";
      
      $regular_contribs_sql_part_a = "SELECT contrib.id as contrib_id , contrib.contact_id as contact_id, ct.name as contrib_type , li.line_total as total_amount, 
month( contrib.receive_date ) as mm_date, day(contrib.receive_date ) as dd_date , year(contrib.receive_date ) as yyyy_date,  
 contrib.currency, contrib.source, valA.label, valA.name as contrib_status_name,  valB.label as pay_method, contrib.check_number, contrib.receive_date, '' as paid_for_contact, '' as rec_type_desc
	FROM civicrm_line_item li JOIN civicrm_contribution contrib ON  li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'
        LEFT JOIN ".$extra_contrib_info_table_sql." as contrib_info ON contrib.id = contrib_info.entity_id, 
	civicrm_financial_type ct,
	civicrm_option_value valA, 
	civicrm_option_group grpA,
	civicrm_option_value valB, 
	civicrm_option_group grpB
	WHERE 
	 ".$date_where_clause."
	AND li.financial_type_id = ct.id
	AND contrib.contribution_status_id = valA.value
	AND  valA.option_group_id = grpA.id 
	AND grpA.name = 'contribution_status'
	AND contrib.total_amount <> 0 
	".$tmp_exclude_prepays_sql." 
        AND ( ct.name NOT LIKE 'adjustment-%'  AND ct.name NOT LIKE '%---adjustment-%' )  ".$tmp_contrib_type_ids_for_sql."
	AND contrib.payment_instrument_id = valB.value
	AND  valB.option_group_id = grpB.id 
	AND grpB.name = 'payment_instrument'
	AND contrib.contact_id in ( $cid_list )
	AND contrib.contribution_status_id = valA.value
	AND valA.name IN ('Completed', 'Refunded' )
	AND contrib.is_test = 0 
	AND contrib.contribution_recur_id IS NULL
        AND contrib_info.".$third_party_col_name." IS NULL";
      
      $regular_contribs_sql_part_b = " SELECT contrib.id as contrib_id , contrib.contact_id as contact_id, ct.name as contrib_type , li.line_total as total_amount, 
	month( contrib.receive_date ) as mm_date, day(contrib.receive_date ) as dd_date , year(contrib.receive_date ) as yyyy_date, 
	contrib.currency, contrib.source, valA.label, valA.name as contrib_status_name, '' as pay_method, contrib.check_number, contrib.receive_date, '' as paid_for_contact,  '' as rec_type_desc
 FROM civicrm_line_item li join civicrm_contribution contrib ON  li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'
 LEFT JOIN ".$extra_contrib_info_table_sql." as contrib_info ON contrib.id = contrib_info.entity_id ,
   civicrm_financial_type ct, 
 civicrm_option_value valA, civicrm_option_group grpA 
WHERE 
 ".$date_where_clause." AND
 li.financial_type_id = ct.id AND contrib.contribution_status_id = valA.value AND 
 valA.option_group_id = grpA.id AND grpA.name = 'contribution_status' ".$tmp_exclude_prepays_sql." AND 
 ( ct.name NOT LIKE 'adjustment-%'  AND  ct.name NOT LIKE '%---adjustment-%' ) ".$tmp_contrib_type_ids_for_sql." 
AND  contrib.payment_instrument_id is null 
AND contrib.total_amount <> 0 
AND contrib.contact_id in (  $cid_list ) and contrib.contribution_status_id = valA.value AND 
valA.name IN ('Completed', 'Refunded' ) 
AND contrib.is_test = 0 
AND contrib.contribution_recur_id IS NULL
AND contrib_info.".$third_party_col_name." IS NULL ";   

    
    require_once( 'utils/finance/Prepayment.php');
    $tmpPrepayment = new Prepayment();
    $tmp_exclude_prepays_sql = $tmpPrepayment->getExcludePrepaymentsSQL();
    
    // Put it all together
$sql_str ="select t1.*, t2.symbol from (
  ( ".$regular_contribs_sql_part_a."  )
  UNION ALL ( ".$regular_contribs_sql_part_b."  )
  UNION ALL ( ".$third_party_sql_part_a." )
  UNION ALL ( ".$third_party_sql_part_b." )
  UNION ALL ( ".$participant_contributions_sql." )
  UNION ALL ( ".$refund_details_sql." )
  UNION ALL ( ".$participant_refund_sql." ) 
  UNION ALL ( ".$recurring_contribs_sql." ) 
 order by contact_id , receive_date ) as t1 
 left join civicrm_currency as t2 on t1.currency = t2.name";
 
 
 //print "<br>$recurring_contribs_sql"; 

 // $html_table_begin =  '<table border=0 style="border-spacing: 0; border-style: solid; border-collapse: collapse; width: 100%">';
 $html_table_begin =  '<table border= style="width: 100%">';
 $html_table_end = ' </table>  	 ';

  require_once('utils/FormattingUtils.php');
  $formatter = new FormattingUtils();
 
 $font_size = $formatter->getPDFfontsize();

  $tt_style  = "style='font-size: ".$font_size.";'";
  $total_style = "style='text-align: right; font-weight: bold; font-size: ".$font_size."' ";
  
  $num_style = "style='text-align: right; font-size: ".$font_size.";'";
 $prev_cid = "";
  $cur_cid_html = "";
  $sub_total = 0;
 
 
 $output_sub_total = 0;
$tmp_total_label = 'Total Amount Received:'; 

$tmp_completed_detail_rows = array(); 
$tmp_completed_sub_total = array();
 
 //print "<br><br> SQL: ".$sql_str; 
 
 $row_num =0; 
$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;
 
  while ( $dao->fetch( ) ) {
  	$row_num = $row_num + 1; 
        $contrib_id = $dao->contrib_id; 
  	$cur_cid = $dao->contact_id;
  	$cur_rec_date = $dao->receive_date;
  	$contrib_status_name = $dao->contrib_status_name ;
  	$currency_symbol = $dao->symbol;
  	$paid_for_contact = $dao->paid_for_contact ; 
  	$paid_for_description = "";
  	$rec_type_desc = $dao->rec_type_desc; 
        if(strlen($paid_for_contact) > 0 ){
               $paid_for_description = " - Paid as a third party for ".$paid_for_contact;
         } 
  	
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
     
     $source = $dao->source;
     // $currency = $dao->currency;
      $status = $dao->label;
      $pay_method = $dao->pay_method; 
      $pay_desc = ""; 
      
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
       
       $refunded_desc = "";
       if( strlen ($contrib_status_name) > 0 && $contrib_status_name == 'Refunded' ){
           if(  $rec_type_desc == 'refund_detail' ){
              $refunded_desc = " (This is a Refund) "; 
           }else{
       	    $refunded_desc = " (Later Refunded) "; 
       	    }
       
       } 
       if( $token_format == "backoffice_screen"){
	       $detail_url = "/civicrm/contact/view/contribution?reset=1&id=".
	       $contrib_id."&cid=".$cur_cid."&action=view&context=contribution&selectedChild=contribute";
	       
	       $detail_link = " <a href='".$detail_url."'>(detail)</a>"; 
       }
      
      $tmp_completed_detail_rows[$cur_cid] = $tmp_completed_detail_rows[$cur_cid]."<tr class=".$css_name."><td ".$tt_style." width='10%'>".$tmp_date_formated."</td><td ".$tt_style." width='50%'>".$tmp_description.$paid_for_description.$refunded_desc.$detail_link."</td><td width='20%' ".$num_style.">".$total_formated."</td></tr>";
      
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
  	if( $row_num > 0 ){
  		$tmp_sub_formatted = $currency_symbol.number_format($tmp_sub, 2);
  		$tmp_html = $tmp_html."<tr><td colspan=2 ".$total_style.">".$tmp_total_label."</td><td ".$total_style.">".$tmp_sub_formatted."</td></tr>"; 
   	}
   	
   	$tmp_html = $tmp_html.$html_table_end;
      
       
    // $cur_cid_html = $cur_cid_html.'<tr><td colspan=3> &nbsp; </td></tr><tr><td colspan=2 style="text-align: right; font-weight: bold;">'.$tmp_total_label.'</td><td style="font-weight: bold; text-align: right">'.$sub_total_formatted."</td></tr>"; 
     	    
    
    
  	$values[$cid][$token_to_fill] = $tmp_html;
  	/////  $values[$cid][$token_total_raw] = $tmp_sub; 
       // $values[$cid][$token_obligation_total_short] =  $values[$cid][$token_obligation_total_long]  = '$ '.number_format($tmp_sub, 2);
  	
  	//print_r($values);
  	//print "<br>total of everything for ".$cid." ".$total_of_everything[$cid];
  	
  
  
  }
  
 $format = '';
 populate_default_value(  $values, $contactIDs , $token_to_fill, $token_to_fill,   "Nothing Found for this contact", $format); 
 
 $money_format = 'USDmoney' ; 
 //populate_default_value(  $values, $contactIDs , $token_bal_short, $token_bal_long,   $total_of_everything, $money_format);
  	
}

/*************************************************************/
   	function getThirdPartyPaymentsForBeneficiary(&$values, &$contactIDs,  &$ct_type_prefix_id, &$token_to_fill, &$output_wanted, &$date_range){
   		

   if( count($contactIDs) == 0 ){
	// no contacts, nothing to do. 
 	return; 
   }
     require_once('utils/RelationshipTools.php');
   $tmpRelTools = new RelationshipTools();
   
   $cid_list =  $tmpRelTools->get_contact_ids_for_sql($contactIDs) ; 

    $fiscal_start_date	= get_current_fiscal_year_start_date() ; 
    $currency_symbol = "";

    //$tmp_contrib_type_ids_for_sql = 	getContributionTypeWhereClauseForSQL( $ct_type_prefix_id);
    require_once('utils/finance/FinancialCategory.php') ;
	
	$tmpFinancialCategory = new FinancialCategory();
	
	$prefix_array = array();
	$prefix_array[] = $ct_type_prefix_id; 
	$tmp_contrib_type_ids_for_sql   = $tmpFinancialCategory->getContributionTypeWhereClauseForSQL($prefix_array); 
	if(strlen($tmp_contrib_type_ids_for_sql ) > 0 ){
		$tmp_contrib_type_ids_for_sql  = " AND ".$tmp_contrib_type_ids_for_sql ; 
	}
	
  // $extra_contrib_info_table_sql = "civicrm_value_extra_contribution_info"; 
  // $third_party_col_name = "third_party_payor_26";


 require_once('utils/util_custom_fields.php');

   $custom_field_group_label = "Extra Contribution Info";
   $custom_field_third_party_label = "Third Party Payor";
  
    $customFieldLabels = array($custom_field_third_party_label );
   $extra_contrib_info_table_sql = "";
   $outCustomColumnNames = array();


$error_msg = getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, $extra_contrib_info_table_sql, $outCustomColumnNames ) ;

$third_party_col_name  =  $outCustomColumnNames[$custom_field_third_party_label];

 if(strlen( $third_party_col_name) == 0){
       print "<br>Error: There is no field with the name: '$custom_field_third_party_label' ";
       return; 
 }

	require_once ('utils/Entitlement.php');
		$entitlement = new Entitlement();
		
		 $third_party_sql = "";
		
		
	 $third_party_sql = "SELECT contrib.contact_id as contact_id, ct.name as contrib_type , contrib.total_amount as total_amount,
month( contrib.receive_date ) as mm_date, day(contrib.receive_date ) as dd_date , year(contrib.receive_date ) as yyyy_date,  
 contrib.currency, contrib.source, valA.label,  contrib.receive_date, c.display_name as paid_by_contact
	FROM civicrm_contribution contrib LEFT JOIN ".$extra_contrib_info_table_sql." as contrib_info ON contrib.id = contrib_info.entity_id
        LEFT JOIN civicrm_contact as c ON contrib_info.".$third_party_col_name." = c.id ,
	civicrm_financial_type ct,
	civicrm_option_value valA, 
	civicrm_option_group grpA
	WHERE 
	contrib.receive_date >= '$fiscal_start_date'
	AND contrib.financial_type_id = ct.id
	AND contrib.contribution_status_id = valA.value
	AND  valA.option_group_id = grpA.id 
	AND grpA.name = 'contribution_status'
	AND contrib.total_amount <> 0 
	".$tmp_exclude_prepays_sql." 
        AND ( ct.name NOT LIKE 'adjustment-%'  AND ct.name NOT LIKE '%---adjustment-%' )  ".$tmp_contrib_type_ids_for_sql."
	and contrib_info.".$third_party_col_name." is NOT NULL
	AND contrib.contact_id in ($cid_list) 
	and contrib.contribution_status_id = valA.value
	AND valA.name IN ('Completed' )
	and contrib.is_test = 0";


		

    
    require_once( 'utils/finance/Prepayment.php');
    $tmpPrepayment = new Prepayment();
    $tmp_exclude_prepays_sql = $tmpPrepayment->getExcludePrepaymentsSQL();
	$sql_str ="select t1.*, t2.symbol from ( ".$third_party_sql."
	 order by contact_id , receive_date ) as t1 left join civicrm_currency as t2 on t1.currency = t2.name";

  //	print "<br><br> sql:  ".$sql_str;


 // $html_table_begin =  '<table border=0 style="border-spacing: 0; border-style: solid; border-collapse: collapse; width: 100%">';
 $html_table_begin =  '<table border=0 style="width: 100%">';
 $html_table_end = ' </table>  	 ';

  require_once('utils/FormattingUtils.php');
  $formatter = new FormattingUtils();
 
 $font_size = $formatter->getPDFfontsize();

  $tt_style  = "style='font-size: ".$font_size.";'";
  $total_style = "style='text-align: right; font-weight: bold; font-size: ".$font_size."' ";
 $prev_cid = "";
  $cur_cid_html = "";
  $sub_total = 0;
 
 
 $output_sub_total = 0;
$tmp_total_label = 'Total Amount Received:'; 

$tmp_completed_detail_rows = array(); 
$tmp_completed_sub_total = array();
 
  
 
 $row_num =0; 
$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;
 
  while ( $dao->fetch( ) ) {
  	$row_num = $row_num + 1; 
  
  	$cur_cid = $dao->contact_id;
  	$cur_rec_date = $dao->receive_date;
  	$currency_symbol = $dao->symbol;
  	$paid_by_contact = $dao->paid_by_contact ; 
  	$paid_by_description = "";
        if(strlen($paid_by_contact) > 0 ){
               $paid_by_description = " - Paid by a third party: ".$paid_by_contact;
         } 
  	
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
     
     $source = $dao->source;
     // $currency = $dao->currency;
      $status = $dao->label;
      $pay_method = $dao->pay_method; 
      $pay_desc = ""; 
      
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
       
      $tmp_completed_detail_rows[$cur_cid] = $tmp_completed_detail_rows[$cur_cid]."<tr class=".$css_name."><td ".$tt_style." width='10%'>".$tmp_date_formated."</td><td ".$tt_style." width='50%'>".$tmp_description.$paid_by_description."</td><td width='20%' ".$tt_style." align=right>".$total_formated."</td></tr>";
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
  	if( $row_num > 0 ){
  		$tmp_sub_formatted = $currency_symbol.number_format($tmp_sub, 2);
  		$tmp_html = $tmp_html."<tr><td colspan=2 ".$total_style.">".$tmp_total_label."</td><td ".$total_style.">".$tmp_sub_formatted."</td></tr>"; 
   	}
   	
   	$tmp_html = $tmp_html.$html_table_end;
      
       
    // $cur_cid_html = $cur_cid_html.'<tr><td colspan=3> &nbsp; </td></tr><tr><td colspan=2 style="text-align: right; font-weight: bold;">'.$tmp_total_label.'</td><td style="font-weight: bold; text-align: right">'.$sub_total_formatted."</td></tr>"; 
     	    
    
    
  	$values[$cid][$token_to_fill] = $tmp_html;
  	/////  $values[$cid][$token_total_raw] = $tmp_sub; 
       // $values[$cid][$token_obligation_total_short] =  $values[$cid][$token_obligation_total_long]  = '$ '.number_format($tmp_sub, 2);
  	
  	//print_r($values);
  	//print "<br>total of everything for ".$cid." ".$total_of_everything[$cid];
  	
  
  
  }
  
 $format = '';
 populate_default_value(  $values, $contactIDs , $token_to_fill, $token_to_fill,   "Nothing Found for this contact", $format); 
 
 $money_format = 'USDmoney' ; 
 //populate_default_value(  $values, $contactIDs , $token_bal_short, $token_bal_long,   $total_of_everything, $money_format);
  	
}

  


}

?>