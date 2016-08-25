<?php

   class Obligation{
   
   
   
   	function get_sql_string_for_obligations(&$contactIDs, &$order_by_parm, &$end_date_parm , &$start_date_parm, &$exclude_after_date_parm, 
   	 &$error, &$all_contacts = false, &$get_contact_name  = false, &$extra_where_clause_parm = '', &$extra_groupby_clause = '', &$ct_type_prefix_id = '', 	        &$include_closed_items = true, 
   	&$groups_of_contact = array(), &$mem_types_of_contact = array() , &$mem_orgs_of_contact = array(),  &$columns_needed = "",
   	&$layout_choice = "", &$financial_types_parm = "", &$general_ledger_codes_parm = "", &$include_prepayments = "" ){

	//print "<br><br> Inside get sql string for obligations";
	//print_r( $contactIDs ); 
	//print "<br>Financial type ids: ".$financial_types_parm;
	//print "<br> where clause parm: ".$extra_where_clause_parm;
	// print "<br>include closed items: ".$include_closed_items;
	// print "<br>Inside sql string: exclude after date: ".$exclude_after_date_parm;
	// print "<br>Inside sql string start date parm: ".$start_date_parm; 
	//print "<br>Inside sql string end date parm: ".$end_date_parm; 
	//print "<br>groups of contact: ";
	//print_r($groups_of_contact );
	
	//print "<br>mem types of contact: ";
	//print_r($mem_types_of_contact );
	
   		$tmp_group_join = "";
   		$tmp_mem_join = "";
   		$tmp_extra_cid = "";
   		$tax_contrib_from_sql ="";
   		$tmp_group_join_contrib = "";
   		$tmp_mem_join_contrib = "";
   		$main_tax_select_sql = "";
   		
	//print "<br>mem orgs of contact: ";
	//print_r($mem_orgs_of_contact );
	//  Get extra custom field for "Original Obligation date"
	
	$custom_field_group_label = "Contribution Date Details";
	$custom_field_original_date_label = "Original Obligation Date";
	

$customFieldLabels = array($custom_field_original_date_label  );
$extended_contrib_table = "";
$outCustomColumnNames = array();

require_once('utils/util_custom_fields.php');
$error_msg = getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, $extended_contrib_table, $outCustomColumnNames ) ;

if(isset(  $outCustomColumnNames[$custom_field_original_date_label] )){ 
	$original_date_sql_name  =  $outCustomColumnNames[$custom_field_original_date_label];
}else{
	$original_date_sql_name  = "";
}


// Check For Australian Tax-related custom fields. ( ie GST) as they need extra info.
    $show_id_column = false;
    $show_tax_column = false;  
    $custom_aussie_field_group_label = "Australian GST Info";
    $tmp_contrib_tax_amount_label =  "GST Amount"; 
    $customAussieFieldLabels = array( $tmp_contrib_tax_amount_label );
    
    $extended_aussie_contrib_table = "";
    $outAussieCustomColumnNames = array();
    
    $error_msg_aussie = getCustomTableFieldNames($custom_aussie_field_group_label, $customAussieFieldLabels, $extended_aussie_contrib_table, $outAussieCustomColumnNames ) ;
    
    //
    $custom_aussie_pledge_field_group_label = "Extra Pledge Info";
    $tmp_pledge_tax_amount_label = "GST Pledge Amount"; 
    $customAussiePledgeFieldLabels = array( $tmp_pledge_tax_amount_label );
    
    $extended_aussie_pledge_table = "";
    $outAussiePledgeCustomColumnNames = array();
    
    $error_msg_aussie = getCustomTableFieldNames($custom_aussie_pledge_field_group_label, $customAussiePledgeFieldLabels,  $extended_aussie_pledge_table, $outAussiePledgeCustomColumnNames ) ;
   // print "<br>pledge custom field names: ";
   // print_r($outAussiePledgeCustomColumnNames);
    //
    
    if(strlen( $extended_aussie_contrib_table) > 0 && strlen( $extended_aussie_pledge_table) > 0 && ( false == (empty($outAussiePledgeCustomColumnNames))   ) ){
    	 // print "<br>Aussie GST contrib table found: ".$extended_aussie_contrib_table;
    	 //  print "<br>Aussie GST pledge table found: ".$extended_aussie_pledge_table;
    	   $show_id_column = true;
    	   $show_tax_column = true; 
    
    }else{
    	 $show_id_column = false;
    	$show_tax_column = false; 
    
    }
    
    
    if($show_tax_column){
    	$tax_pledge_from_sql =  " LEFT JOIN ".$extended_aussie_pledge_table." ptax ON  t1.pledge_id = ptax.entity_id ";
    	$tax_pledge_select_sql = "ptax.".$outAussiePledgeCustomColumnNames[$tmp_pledge_tax_amount_label]." as tax_amount, ";
    	
    	$tax_contrib_from_sql =  " LEFT JOIN ".$extended_aussie_contrib_table." ctax ON  c.id = ctax.entity_id ";
    	$tax_contrib_select_sql = " ctax.".$outAussieCustomColumnNames[$tmp_contrib_tax_amount_label]." as tax_amount, ";
    	
    	$tax_recur_select_sql = " '0' as tax_amount, ";
    	
    	$main_tax_select_sql = "f1.tax_amount as tax_amount, ";
    
    
    }else{
    	$tax_pledge_from_sql = "";
    	$tax_pledge_select_sql = "";
    	$tax_contrib_select_sql = "";
    	$tax_recur_select_sql = "";
    	
    
    }

	$tmp_clauses = array();
       ///////////////////////////////////////////////////////////////////////////////
	// Need to deal with group and membership filters. 
	$tmp_where_grps_mems = "";
	
	
	require_once('utils/CustomSearchTools.php');
	$searchTools = new CustomSearchTools();
	
	//$contact_field_name = "f1.contact_id"; 
	$contact_field_name = "f1.underlying_contact_id"; 
		
	$searchTools->updateWhereClauseForGroupsChosen($groups_of_contact, $contact_field_name,  $tmp_clauses );
			
  	
	$searchTools->updateWhereClauseForMemberships( $mem_types_of_contact,  $mem_orgs_of_contact, $contact_field_name,  $tmp_clauses   ) ; 
	
	
	////////////////////////////////////////////////////////////////////////////////
		 
	require_once 'utils/FinancialDates.php';
	$finDates = new FinancialDates();
		 // Make sure we include where clause passed in as a parm.
		 if(strlen( $extra_where_clause_parm) > 0){
		 	$tmp_clauses[] =  $extra_where_clause_parm ; 
		 }
		
		if($include_closed_items == false){
			//print "<br>Do NOT include closed items. ";
			$tmp_clauses[] = "f1.balance <> 0";
		}
		
		if(strlen($exclude_after_date_parm) > 0 ){
			//print "<br>exclude after date parm is: ".$exclude_after_date_parm;
			if($exclude_after_date_parm == 'curfiscalyear'){
				
				$format = "Ymd";
				$exclude_after_date_parm = $finDates->get_first_day_next_fiscal_year($format); 
				$tmp = " DATE_SUB( '".$exclude_after_date_parm."' , INTERVAL 1 DAY) ";
				
				
			}else{
				$tmp = "'".$exclude_after_date_parm."'";
			
			}
			$tmp_clauses[] = "f1.rec_date <= ".$tmp ;
		
		}
		
		if(count($tmp_clauses) > 0){
       		 $tmp_str = implode( ' AND ', $tmp_clauses );
       		 $extra_where_clause = "WHERE ".$tmp_str; 
       		// print "<br><br>extra where clause: ".$extra_where_clause;
       
       		}
		

if( strlen($order_by_parm) == 0){
  //$error = "Missing parm: order_by_parm is empty.";
  //print "<br>Error message: ".$error;
  //return ''; 
  $order_by_clause = '';
  
}else{
	$order_by_clause = "ORDER BY ".$order_by_parm;

}

	require_once('FinancialCategory.php') ;
	
	$tmpFinancialCategory = new FinancialCategory();
	
	$prefix_array = array();
	$prefix_array[] = $ct_type_prefix_id; 
	$where_clause_contrib_type_ids  = $tmpFinancialCategory->getContributionTypeWhereClauseForSQL($prefix_array); 
	if(strlen($where_clause_contrib_type_ids) > 0 ){
		$where_clause_contrib_type_ids = " AND ".$where_clause_contrib_type_ids ; 
	}

	//print "<br>where clause for contrib type ids: ".$where_clause_contrib_type_ids;
  	






require_once('utils/RelationshipTools.php');
   $tmpRelTools = new RelationshipTools();
   
   $cid_list =  $tmpRelTools->get_contact_ids_for_sql($contactIDs) ; 

if($all_contacts){
	$pledge_cid_sql = "";
	$contrib_cid_sql = ""; 
}else{   
	
	
	$pledge_cid_sql = " AND p.contact_id IN (  $cid_list )";
	$contrib_cid_sql = " AND c.contact_id IN ( $cid_list )";
	
	
	}
	
	require_once( 'FinancialDates.php');
	$tmpFinancialDates = new FinancialDates();
$fiscal_date = $tmpFinancialDates->get_current_fiscal_year_start_date();


$tmp_pledge_where = '';
$tmp_contrib_where = '';
$tmp_recur_where = ''; 

//print "<br>&nbsp;&nbsp;Inside get sql, start date: ".$start_date_parm; 
if(strlen($start_date_parm) > 0){
	$tmp_pledge_where = " AND date(p.start_date) >= date('".$start_date_parm."')";
  	$tmp_contrib_where = " AND date(c.receive_date) >= date('".$start_date_parm."')";
  	$tmp_recur_where = " AND date(c.receive_date) >= date('".$start_date_parm."')";
}
 
 if(strlen($end_date_parm) > 0){
   if(strlen($exclude_after_date_parm) > 0 ){
	$tmp_pledge_where = $tmp_pledge_where." AND date(p.start_date) <= date('".$end_date_parm."')";
  	$tmp_contrib_where = $tmp_contrib_where." AND date(c.receive_date) <= date('".$end_date_parm."')";
  	$tmp_recur_where = $tmp_recur_where." AND date(c.receive_date) <= date('".$end_date_parm."')";
  }	
}

  //require_once ('utils/Entitlement.php');
   //     $entitlement = new Entitlement();
       
       // print "<br> financial type parms: ".$financial_types_parm; 
        
        
        	if( sizeof($contactIDs) > 0 ){
        		//$tmp_cids = implode( ",", $contactIDs );
        		 
        		
        		$tmp_pledge_where = $tmp_pledge_where." AND p.contact_id IN (".$cid_list.") "; 
        		
        		$tmp_contrib_where = $tmp_contrib_where." AND c.contact_id IN (".$cid_list.") "; 
        		$tmp_recur_where = $tmp_recur_where." AND c.contact_id IN (".$cid_list.") "; 
        	
        	}
        
		if(strlen( $financial_types_parm) > 0){
			$tmp_pledge_where = $tmp_pledge_where." AND p.financial_type_id IN (".$financial_types_parm." ) ";
			
			
			$tmp_contrib_where = $tmp_contrib_where." AND li.financial_type_id IN (".$financial_types_parm." ) ";
			$tmp_recur_where = $tmp_recur_where." AND li.financial_type_id IN (".$financial_types_parm." ) ";

		}
		
		if( strlen( $general_ledger_codes_parm) > 0){
			$tmp_pledge_where = $tmp_pledge_where." AND fa.accounting_code IN (".$general_ledger_codes_parm." ) ";
			$tmp_contrib_where = $tmp_contrib_where." AND fa.accounting_code IN (".$general_ledger_codes_parm." ) ";
			$tmp_recur_where = $tmp_recur_where." AND fa.accounting_code IN (".$general_ledger_codes_parm." ) ";
		}
		
		if( sizeof($mem_orgs_of_contact) > 0  ){
			$tmp_mo = implode( ",", $mem_orgs_of_contact) ; 
		    $tmp_pledge_where = $tmp_pledge_where." AND  p.contact_id IN ( SELECT mem.contact_id FROM civicrm_membership mem LEFT JOIN civicrm_membership_status mem_status ON mem.status_id = mem_status.id LEFT JOIN civicrm_membership_type mt ON mem.membership_type_id = mt.id WHERE mt.member_of_contact_id IN ( ".$tmp_mo." ) AND mt.is_active = '1' AND mem_status.is_current_member = '1' AND mem_status.is_active = '1' ) ";
		    
		    $tmp_contrib_where = $tmp_contrib_where." AND  c.contact_id IN ( SELECT mem.contact_id FROM civicrm_membership mem LEFT JOIN civicrm_membership_status mem_status ON mem.status_id = mem_status.id LEFT JOIN civicrm_membership_type mt ON mem.membership_type_id = mt.id WHERE mt.member_of_contact_id IN ( ".$tmp_mo." ) AND mt.is_active = '1' AND mem_status.is_current_member = '1' AND mem_status.is_active = '1' ) ";
		    
		    $tmp_recur_where = $tmp_recur_where." AND  c.contact_id IN ( SELECT mem.contact_id FROM civicrm_membership mem LEFT JOIN civicrm_membership_status mem_status ON mem.status_id = mem_status.id LEFT JOIN civicrm_membership_type mt ON mem.membership_type_id = mt.id WHERE mt.member_of_contact_id IN ( ".$tmp_mo." ) AND mt.is_active = '1' AND mem_status.is_current_member = '1' AND mem_status.is_active = '1' ) ";
		    
		
		} 
		

	
	
	//print "<br>Layout choice: ".$layout_choice; 
	
	// Get household id if needed
 if( $layout_choice == 'summarize_household_contribution_type' || $layout_choice == 'summarize_household'){
    	
    		$tmp_contact_sql_contrib = " ifnull( rel.contact_id_b,  c.contact_id ) as contact_id , c.contact_id as underlying_contact_id , ";
    		$tmp_contact_sql_pledge = "rel.contact_id_b as household_id, ifnull( rel.contact_id_b, p.contact_id ) as contact_id, p.contact_id as underlying_contact_id , ";
    		
    		$tmp_rel_type_ids = "7, 6";   // Household member of , Head of Household 
    		$tmp_from_sql_contrib = " LEFT JOIN civicrm_relationship rel ON c.contact_id = rel.contact_id_a AND rel.is_active = 1 AND ( rel.is_permission_b_a = 1 OR rel.is_permission_a_b = 1 ) AND rel.relationship_type_id IN ( ".$tmp_rel_type_ids." ) ";
    		$tmp_from_sql_pledge = "LEFT JOIN civicrm_relationship rel ON p.contact_id = rel.contact_id_a AND rel.is_active = 1 AND ( rel.is_permission_b_a = 1 OR rel.is_permission_a_b = 1 )AND rel.relationship_type_id IN ( ".$tmp_rel_type_ids." ) ";
    		
    	}else{
    		$tmp_contact_sql_contrib = " c.contact_id as contact_id , c.contact_id as underlying_contact_id , ";
    		$tmp_contact_sql_pledge =  " p.contact_id as contact_id , p.contact_id as underlying_contact_id , ";
    		
    		$tmp_from_sql_contrib = "";
    		$tmp_from_sql_pledge = ""; 
    		
    	
    	}
    	// done with household id logic. 	
	
	require_once( 'Prepayment.php');
	
   if($include_prepayments == false ){
   
	    $tmpPrepayment = new Prepayment();
	    
	    $tmp_exclude_prepays_sql = $tmpPrepayment->getExcludePrepaymentsSQL();
    
    }
    
    
    
   // print "<br>prepayment sql: ".$tmp_exclude_prepays_sql; 
   //require_once ('utils/Entitlement.php');
    //    $entitlement = new Entitlement();
        $financial_type_sql = "";
        
    
        	
        	$pledge_extra_info = "";
        	$pledge_source_field = ""; 
        	
 		$params = array(
  'version' => 3,
  'sequential' => 1,
  'name' => 'Extra_Pledge_info',
);
$result = civicrm_api('CustomGroup', 'getsingle', $params);

   if(isset($result['table_name'] )){
	$pledge_extra_info = $result['table_name']; 
   	}else{
   		$pledge_extra_info = "";
   	}
        
       $params = array(
  'version' => 3,
  'sequential' => 1,
  'name' => 'Pledge_Source',
);
$result = civicrm_api('CustomField', 'getsingle', $params);

 if(isset($result['column_name'])){
	$pledge_source_field = $result['column_name'];
 }else{
 	$pledge_source_field = "";
 }
    if( strlen($pledge_source_field)  > 0 && strlen($pledge_extra_info) > 0 ){
    	
    	
    	$p_source_sql_snipet = " extra.".$pledge_source_field." as pledge_source "; 
    	$p_extra_table_snipet = " LEFT JOIN ".$pledge_extra_info." extra ON extra.entity_id = p.id ";
    	
    }else{
    	$p_source_sql_snipet = " '' as pledge_source ";  
    	$p_extra_table_snipet = "";
    	
    	}

        
        	 $pledge_sql_part_a =	"SELECT p.id as pledge_id , ".$tmp_contact_sql_pledge." p.status_id, p.start_date, valA.label as status_label, p.financial_type_id, p.currency,  p.amount AS pledge_amount, 
 ct.name AS contrib_type, fa.accounting_code as accounting_code	, ".$p_source_sql_snipet."  
FROM civicrm_pledge p  ".$tmp_from_sql_pledge ." ".$p_extra_table_snipet." ,  civicrm_financial_type ct
LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
			 AND efa.account_relationship = 1 
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id
        	 	 ,
civicrm_option_value as valA, 
civicrm_option_group as grpA
		WHERE p.financial_type_id = ct.id
		AND p.status_id = valA.value
		AND  valA.option_group_id = grpA.id 
		AND grpA.name = 'contribution_status'
		AND (
		       (valA.label  = 'Pending' || valA.label  = 'In Progress' || valA.label  = 'Overdue' ) 
		       OR
		       (valA.label  = 'Completed') 
		     )
		".$tmp_exclude_prepays_sql."
		AND ( ct.name NOT LIKE 'adjustment-%' AND ct.name NOT LIKE '%---adjustment-%') 
		AND p.amount <> 0 
		AND p.is_test =0".$where_clause_contrib_type_ids.$tmp_pledge_where.
		$pledge_cid_sql." group by p.id " ;
		
		
		
		$pledge_sql_part_b = "SELECT p.id as pledge_id , 
sum( pp.actual_amount ) AS received
		FROM civicrm_contact c, civicrm_pledge p, civicrm_pledge_payment pp,
              civicrm_contribution cont,  civicrm_financial_type ct
              LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
			 AND efa.account_relationship = 1 
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id
		WHERE c.id = p.contact_id
		AND p.id = pp.pledge_id
               and pp.contribution_id = cont.id
		AND cont.financial_type_id = ct.id
                and ( ct.name NOT LIKE 'adjustment-%' AND ct.name NOT LIKE '%---adjustment-%' ) ".$where_clause_contrib_type_ids."
		and pp.status_id = 1 ".$tmp_pledge_where." 
		GROUP BY pp.pledge_id";
		
		$pledge_sql_part_c = "SELECT p.id as pledge_id , 
sum( pp.actual_amount ) AS adjusted
		FROM civicrm_contact c, civicrm_pledge p LEFT JOIN civicrm_entity_financial_account efa ON p.financial_type_id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
			 AND efa.account_relationship = 1 
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id, civicrm_pledge_payment pp,
              civicrm_contribution cont,  civicrm_financial_type ct
		WHERE c.id = p.contact_id
		AND p.id = pp.pledge_id
               and pp.contribution_id = cont.id
		AND cont.financial_type_id = ct.id
                and ( ct.name LIKE 'adjustment-%' OR ct.name LIKE '%---adjustment-%') ".$where_clause_contrib_type_ids."
		and pp.status_id = 1 ".$tmp_pledge_where." 
		GROUP BY pp.pledge_id";
		
  //print "<br><br>pledge sql part a: ".$pledge_sql_part_a;
  
  //print "<br><br>pledge sql part b: ".$pledge_sql_part_b;
  
  //print "<br><br>pledge sql part c: ".$pledge_sql_part_c;
  
 	 $pledge_sql  = "select t1.pledge_id as id, t1.contact_id as contact_id , t1.underlying_contact_id, 
 	  t1.contrib_type as contrib_type, t1.pledge_source as source, 
   t1.pledge_amount as total_amount, month(t1.start_date) as mm_date, day(t1.start_date) as dd_date, year(t1.start_date) as yyyy_date,
   t1.start_date as rec_date, 'pledge' as entity_type , t1.status_id as status, t1.status_label as status_label, 
   '' as recur_amount, '' as recur_installments, '' as line_item_id, 
   ".$tax_pledge_select_sql." t1.currency as currency,
t1.financial_type_id as contrib_type_id ,   t1.accounting_code as accounting_code,
'' as line_item_recur_amount , '' as recur_crm_id , '' as processor_id , '' as	payment_processor_type, 
   ifnull( t2.received, 0 ) as received , ifnull( t3.adjusted , 0 ) as adjusted, (t1.pledge_amount - ifnull( t2.received, 0 ) - ifnull( t3.adjusted , 0 )) as balance , null as amt_due
 from ( ".$pledge_sql_part_a." ) as t1 
 left join ( ".$pledge_sql_part_b." )  as t2 on t1.pledge_id = t2.pledge_id
 left join ( ".$pledge_sql_part_c." )  as t3 on t1.pledge_id = t3.pledge_id".$tax_pledge_from_sql.$tmp_group_join.$tmp_mem_join.
		" WHERE 1=1 ".$tmp_where_grps_mems ;
         
	 
  //print "<br><br><br>4.3 Pledge sql: ".$pledge_sql; 


// include COMPLETED automated recurring contributions IF recurring subscription is cancelled or open-ended.  


$tmp_first_contrib = " select contrib.id , contrib.contact_id ,contrib.source, contrib.currency, 
   contrib.contribution_status_id,   contrib.contribution_recur_id , contrib.receive_date, contrib.total_amount, contrib.is_test
       FROM civicrm_contribution contrib 
       WHERE contrib.contribution_recur_id is NOT NULL
       AND (contrib.contribution_status_id = 1 OR contrib.contribution_status_id = 2 ) ".$tmp_extra_cid."      
       GROUP BY contrib.contribution_recur_id 
       HAVING contrib.receive_date = min(contrib.receive_date) ";
       
       
       
   $contrib_sql_with_recur = "select c.id as id, ".$tmp_contact_sql_contrib." ct.name as contrib_type, c.source as source, li.line_total as total_amount,
month( c.receive_date ) as mm_date, day(c.receive_date ) as dd_date , year(c.receive_date ) as yyyy_date, c.receive_date as rec_date,
'contribution' as entity_type, c.contribution_status_id as status, valA.label as status_label,
'' as recur_amount, '' as recur_installments, li.id as line_item_id,
".$tax_contrib_select_sql." c.currency as currency,
ct.id as contrib_type_id,  fa.accounting_code as accounting_code , 
'' as line_item_recur_amount , '' as recur_crm_id , '' as processor_id , '' as payment_processor_type, 
CASE WHEN ct.name NOT LIKE 'adjustment-%' AND ct.name NOT LIKE '%---adjustment-%' THEN  if( valA.label = 'Completed' , li.line_total,  0) ELSE 0 END  as received, CASE WHEN ct.name NOT LIKE 'adjustment-%' AND ct.name NOT LIKE '%---adjustment-%' THEN 0 ELSE if( valA.label = 'Completed' , li.line_total,  0)  END as adjusted ,  if( valA.label = 'Completed' , 0,  li.line_total) as balance, 
if( valA.label <>  'Completed'  , sum(li.line_total),  0) as amt_due
from   ( ".$tmp_first_contrib.") as first_contrib JOIN civicrm_line_item li ON  li.entity_id = first_contrib.id AND li.entity_table = 'civicrm_contribution' 
        join civicrm_contribution_recur recur on recur.id = first_contrib.contribution_recur_id 
        JOIN civicrm_contribution c ON c.contribution_recur_id = recur.id
 ".$tmp_from_sql_contrib."
 left join civicrm_pledge_payment as pp on c.id = pp.contribution_id
 LEFT JOIN civicrm_pledge p ON pp.pledge_id = p.id 
 left join civicrm_financial_type as ct on li.financial_type_id = ct.id 
 LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
 			 AND efa.account_relationship = 1 
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id ".$tax_contrib_from_sql.$tmp_group_join_contrib.$tmp_mem_join_contrib." ,
 civicrm_option_value as valA, 
civicrm_option_group as grpA
where(   (pp.pledge_id is null ) OR ( p.status_id = 3) )"
.$contrib_cid_sql.
" AND c.total_amount <> 0 
AND ( (c.contribution_recur_id is null ) OR ( recur.contribution_status_id = 3 && c.contribution_status_id = 1) OR ( (recur.installments is null OR 
   recur.installments = 0 )  && c.contribution_status_id = 1 ) )
AND
c.contribution_status_id = valA.value
AND  valA.option_group_id = grpA.id 
AND grpA.name = 'contribution_status'
AND (
       (valA.label  = 'Pending' || valA.label  = 'In Progress' || valA.label  = 'Overdue') 
       OR
       (valA.label  = 'Completed'  ) 
     )
".$tmp_exclude_prepays_sql.$where_clause_contrib_type_ids.$tmp_contrib_where.
" and c.is_test =0 ".$tmp_where_grps_mems." group by li.id, c.id";

 $contrib_sql_no_recur = "select c.id as id, ".$tmp_contact_sql_contrib." ct.name as contrib_type, c.source as source, li.line_total as total_amount,
month( c.receive_date ) as mm_date, day(c.receive_date ) as dd_date , year(c.receive_date ) as yyyy_date, c.receive_date as rec_date,
'contribution' as entity_type, c.contribution_status_id as status, valA.label as status_label,
'' as recur_amount, '' as recur_installments, li.id as line_item_id,
".$tax_contrib_select_sql." c.currency as currency,
ct.id as contrib_type_id,  fa.accounting_code as accounting_code , 
'' as line_item_recur_amount , '' as recur_crm_id , '' as processor_id , '' as payment_processor_type, 
CASE WHEN ct.name NOT LIKE 'adjustment-%' AND ct.name NOT LIKE '%---adjustment-%' THEN  if( valA.label = 'Completed' , li.line_total,  0) ELSE 0 END  as received, CASE WHEN ct.name NOT LIKE 'adjustment-%' AND ct.name NOT LIKE '%---adjustment-%' THEN 0 ELSE if( valA.label = 'Completed' , li.line_total,  0)  END as adjusted ,  if( valA.label = 'Completed' , 0,  li.line_total) as balance, 
if( valA.label <>  'Completed'  , sum(li.line_total),  0) as amt_due
from   civicrm_line_item li JOIN civicrm_contribution c ON   li.entity_id = c.id AND li.entity_table = 'civicrm_contribution'        
 ".$tmp_from_sql_contrib."
 left join civicrm_pledge_payment as pp on c.id = pp.contribution_id
 LEFT JOIN civicrm_pledge p ON pp.pledge_id = p.id 
 left join civicrm_financial_type as ct on li.financial_type_id = ct.id 
 LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
 			 AND efa.account_relationship = 1 
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id ".$tax_contrib_from_sql.$tmp_group_join_contrib.$tmp_mem_join_contrib." ,
 civicrm_option_value as valA, 
civicrm_option_group as grpA
where(   (pp.pledge_id is null ) OR ( p.status_id = 3) )"
.$contrib_cid_sql.
" AND c.total_amount <> 0 
AND ( c.contribution_recur_id is null  )
AND
c.contribution_status_id = valA.value
AND  valA.option_group_id = grpA.id 
AND grpA.name = 'contribution_status'
AND (
       (valA.label  = 'Pending' || valA.label  = 'In Progress' || valA.label  = 'Overdue') 
       OR
       (valA.label  = 'Completed'  ) 
     )
".$tmp_exclude_prepays_sql.$where_clause_contrib_type_ids.$tmp_contrib_where.
" and c.is_test =0 ".$tmp_where_grps_mems." group by li.id, c.id";
	
 // AND  ( ct.name NOT LIKE 'adjustment-%' AND ct.name NOT LIKE '%---adjustment-%' )	
	
 //if(strlen($original_date_sql_name) > 0 && strlen($extended_contrib_table) > 0 ){
 if(false){
 	$contrib_sql = "select c.id as id, ".$tmp_contact_sql_contrib." ct.name as contrib_type, c.source as source, li.line_total as total_amount,
month( ifnull( extra.".$original_date_sql_name." , c.receive_date) ) as mm_date, day(ifnull( extra.".$original_date_sql_name." , c.receive_date) ) as dd_date ,
 year(ifnull( extra.".$original_date_sql_name." , c.receive_date) ) as yyyy_date, ifnull( extra.".$original_date_sql_name." , c.receive_date) as rec_date,
'contribution' as entity_type, c.contribution_status_id as status, valA.label as status_label,
'' as recur_amount, '' as recur_installments, li.id as line_item_id, 
".$tax_contrib_select_sql." c.currency as currency,
ct.id as contrib_type_id,  fa.accounting_code as accounting_code ,
'' as line_item_recur_amount ,  '' as recur_crm_id , '' as processor_id , '' as	payment_processor_type, 
if( valA.label = 'Completed' , li.line_total,  0) as received, 0 as adjusted ,  if( valA.label = 'Completed' , 0,  li.line_total) as balance,
if( valA.label <>  'Completed'  , sum(li.line_total),  0) as amt_due
from civicrm_line_item li JOIN civicrm_contribution as c ON li.entity_id = c.id AND li.entity_table = 'civicrm_contribution' 
 ".$tmp_from_sql_contrib."
 left join civicrm_pledge_payment as pp on c.id = pp.contribution_id
 LEFT JOIN civicrm_pledge p ON pp.pledge_id = p.id 
 LEFT JOIN civicrm_contribution_recur recur ON c.contribution_recur_id = recur.id 
 left join civicrm_financial_type as ct on li.financial_type_id = ct.id
 left join ".$extended_contrib_table." as extra on extra.entity_id = c.id 
 LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
 			 AND efa.account_relationship = 1 
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id ".$tax_contrib_from_sql.$tmp_group_join_contrib.$tmp_mem_join." ,
 civicrm_option_value as valA, 
civicrm_option_group as grpA
where (   (pp.pledge_id is null ) OR ( p.status_id = 3) )"
.$contrib_cid_sql.
" AND c.total_amount <> 0 
AND ( (c.contribution_recur_id is null) OR ( recur.contribution_status_id = 3) )
AND
c.contribution_status_id = valA.value
AND  valA.option_group_id = grpA.id 
AND grpA.name = 'contribution_status'
AND (
       (valA.label  = 'Pending' || valA.label  = 'In Progress' || valA.label  = 'Overdue') 
       OR
       (valA.label  = 'Completed' ) 
     )
".$tmp_exclude_prepays_sql."
and ( ct.name NOT LIKE 'adjustment-%' AND ct.name NOT LIKE '%---adjustment-%' ) ".$where_clause_contrib_type_ids.$tmp_contrib_where.
" and c.is_test =0 ".$tmp_where_grps_mems." group by li.id, c.id";
 
 
 
 
 }
	
	
   //   print "<br><br> Contrib sql: ".$contrib_sql;
   /*
   $recur_part_a = " SELECT li.id as id, c.contact_id as contact_id , c.contact_id as underlying_contact_id , ct.name as contrib_type, MAX(c.source) as source, (li.line_total * recur.installments ) as total_amount, month( c.receive_date ) as mm_date, day(c.receive_date ) as dd_date , year(c.receive_date ) as yyyy_date, c.receive_date as rec_date, 'recurring' as entity_type, c.contribution_status_id as status, valA.label as status_label, recur.amount as recur_amount, MAX(recur.installments) as recur_installments, li.id as line_item_id, c.currency as currency, ct.id as contrib_type_id , fa.accounting_code as accounting_code, li.line_total as line_item_recur_amount, recur.id as recur_crm_id, recur.processor_id , ppt.name as payment_processor_type 
   
   FROM civicrm_line_item li JOIN civicrm_contribution c ON li.entity_id = c.id AND li.entity_table = 'civicrm_contribution' 
    JOIN `civicrm_contribution_recur` recur  ON c.contribution_recur_id = recur.id 
LEFT JOIN civicrm_payment_processor pp on recur.payment_processor_id = pp.id 
LEFT JOIN civicrm_payment_processor_type ppt ON pp.payment_processor_type_id = ppt.id 
left join civicrm_financial_type as ct on li.financial_type_id = ct.id 
LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type' AND efa.account_relationship = 1 
LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id , civicrm_option_value as valA, civicrm_option_group as grpA 
WHERE recur.contribution_status_id = valA.value AND valA.option_group_id = grpA.id AND grpA.name = 'contribution_status' AND ( (valA.label = 'Pending' || valA.label = 'In Progress' || valA.label = 'Overdue' || valA.label = 'Failed') OR (valA.label = 'Completed' ) ) AND recur.installments is not null AND date(c.receive_date) >= date('20130901000000') AND li.financial_type_id IN ( '116094' ) AND recur.is_test = 0 GROUP BY recur.id, ct.id ";

*/ 

  if($all_contacts){
        $tmp_extra_cid = "";  
  }else{
  	$tmp_extra_cid = " AND contrib.contact_id IN ( ".$cid_list." ) ";
  
  }


  
  $tmp_first_contrib = " select contrib.id , contrib.contact_id ,contrib.source, contrib.currency, 
   contrib.contribution_status_id,   contrib.contribution_recur_id , contrib.receive_date
       FROM civicrm_contribution contrib 
       WHERE contrib.contribution_recur_id is NOT NULL
       AND (contrib.contribution_status_id = 1 OR contrib.contribution_status_id = 2 ) ".$tmp_extra_cid."      
       GROUP BY contrib.contribution_recur_id 
       HAVING contrib.receive_date = min(contrib.receive_date) ";
       
       
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


 // print "<br><br>sql for rec recur: ".$sql_for_recur_received; 
  

  $recurring_sql = "SELECT t1.* , ifnull(t2.received, 0 )  as received , 0 as adjusted , (t1.total_amount - ifnull(t2.received, 0 )) as balance,
  null as amt_due  FROM ( 
  SELECT  li.id as id, ".$tmp_contact_sql_contrib." ct.name as contrib_type, c.source as source, (li.line_total * recur.installments ) as total_amount,
month( c.receive_date ) as mm_date, day(c.receive_date ) as dd_date , year(c.receive_date ) as yyyy_date, c.receive_date as rec_date,
'recurring' as entity_type, c.contribution_status_id as status, valA.label as status_label,
recur.amount as recur_amount, recur.installments as recur_installments,  li.id as line_item_id, 
".$tax_recur_select_sql." c.currency as currency,
ct.id as contrib_type_id , fa.accounting_code as accounting_code,
 li.line_total  as line_item_recur_amount,  recur.id as recur_crm_id,  recur.processor_id , ppt.name as payment_processor_type
FROM ( ".$tmp_first_contrib."
    ) as c JOIN civicrm_line_item li ON li.entity_id = c.id AND li.entity_table = 'civicrm_contribution' 
   AND c.contribution_recur_id is not null
    JOIN `civicrm_contribution_recur` recur  ON c.contribution_recur_id = recur.id 
     LEFT JOIN civicrm_payment_processor pp on recur.payment_processor_id = pp.id
LEFT JOIN civicrm_payment_processor_type ppt ON pp.payment_processor_type_id  = ppt.id
 ".$tmp_from_sql_contrib."
join civicrm_financial_type as ct on li.financial_type_id = ct.id
JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
		 AND efa.account_relationship = 1 
        JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id ".$tmp_group_join_contrib.$tmp_mem_join_contrib." , 
civicrm_option_value as valA, 
civicrm_option_group as grpA
WHERE 
 1=1  ".$contrib_cid_sql.
" AND recur.contribution_status_id = valA.value
AND  valA.option_group_id = grpA.id 
AND grpA.name = 'contribution_status'
AND (
       (valA.label  = 'Pending' || valA.label  = 'In Progress' || valA.label  = 'Overdue' || valA.label = 'Failed') 
       OR
       (valA.label  = 'Completed' ) 
     )
AND recur.installments <> 0 AND recur.installments is not null ".$where_clause_contrib_type_ids.$tmp_recur_where.
" AND recur.is_test = 0 ".$tmp_where_grps_mems."
GROUP BY li.id, c.id  ) as t1 LEFT JOIN (
	".$sql_for_recur_received."   
     ) as t2 on t1.id = t2.id";
     
    //  print "<br><br> Recurring Contrib sql: ".$recurring_sql ; 
     
     
   //  if(strlen($original_date_sql_name) > 0 && strlen($extended_contrib_table) > 0 ){
       if(false ){
     	 $recurring_sql = "SELECT t1.* , ifnull(t2.received, 0 )  as received , 0 as adjusted , (t1.total_amount - ifnull(t2.received, 0 )) as balance FROM ( 
  SELECT recur.id as id, ".$tmp_contact_sql_contrib." ct.name as contrib_type, MAX(c.source) as source, (recur.amount * recur.installments ) as total_amount,
month( ifnull( min(extra.".$original_date_sql_name." ), min(c.receive_date)) ) as mm_date, day(ifnull( min(extra.".$original_date_sql_name.") , min(c.receive_date)) ) as dd_date , 
year(ifnull( min(extra.".$original_date_sql_name.") , min(c.receive_date) )) as yyyy_date, ifnull( min(extra.".$original_date_sql_name.") , min(c.receive_date)) as rec_date,
'recurring' as entity_type, c.contribution_status_id as status, valA.label as status_label,
recur.amount as recur_amount, MAX(recur.installments) as recur_installments, '' as line_item_id, 
".$tax_recur_select_sql." c.currency as currency,
ct.id as contrib_type_id , fa.accounting_code as accounting_code, recur.id as recur_crm_id , recur.processor_id , pp.payment_processor_type
FROM `civicrm_contribution_recur` recur LEFT JOIN civicrm_payment_processor pp on recur.payment_processor_id = pp.id,
 civicrm_contribution c
  ".$tmp_from_sql_contrib."
left join civicrm_financial_type as ct on c.contribution_type_id = ct.id
left join ".$extended_contrib_table." as extra on extra.entity_id = c.id 
LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
			 AND efa.account_relationship = 1 
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id ".$tmp_group_join_contrib.$tmp_mem_join_contrib."
        	 	 ,
civicrm_option_value as valA, 
civicrm_option_group as grpA
WHERE 
recur.id = c.contribution_recur_id ".$contrib_cid_sql.
" AND recur.contribution_status_id = valA.value
AND  valA.option_group_id = grpA.id 
AND grpA.name = 'contribution_status'
AND recur.installments is not null".$where_clause_contrib_type_ids.$tmp_recur_where.
" AND recur.is_test = 0 ".$tmp_where_grps_mems."
GROUP BY recur.id
HAVING (valA.label  = 'Pending' || valA.label  = 'In Progress' || valA.label  = 'Overdue' || valA.label = 'Failed') 
 ) as t1 LEFT JOIN (
	select recur.id as id, sum(c.total_amount) as received
        FROM civicrm_contribution_recur recur LEFT JOIN civicrm_contribution c on recur.id = c.contribution_recur_id 
        where (c.contribution_status_id  is null  || c.contribution_status_id = 1) 
        group by recur.id   
     ) as t2 on t1.id = t2.id";
     
     
      // print "<br><br> Recurring Contrib sql: ".$recurring_sql ; 
     
     
     
     }
      
      // event participant contributions:
       $participant_contributions_sql = "select c.id as id, ".$tmp_contact_sql_contrib." ct.name as contrib_type, c.source as source, sum(li.line_total) as total_amount,
month( c.receive_date ) as mm_date, day(c.receive_date ) as dd_date , year(c.receive_date ) as yyyy_date, c.receive_date as rec_date,
'contribution' as entity_type, contribution_status_id as status, valA.label as status_label,
'' as recur_amount, '' as recur_installments, li.id as line_item_id,
".$tax_contrib_select_sql." c.currency as currency,
ct.id as contrib_type_id,  fa.accounting_code as accounting_code , 
'' as line_item_recur_amount , '' as recur_crm_id , '' as processor_id , '' as payment_processor_type, 
if( valA.label = 'Completed' , sum(li.line_total),  0) as received, 0 as adjusted ,  if( valA.label = 'Completed' , 0,  sum(li.line_total) ) as balance ,
if( valA.label <>  'Completed'  , sum(li.line_total),  0) as amt_due
from  civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant' 
	 JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id
				join civicrm_contribution c ON  ep.contribution_id = c.id
				 ".$tmp_from_sql_contrib."
 left join civicrm_pledge_payment as pp on c.id = pp.contribution_id
 left join civicrm_financial_type as ct on li.financial_type_id = ct.id 
 LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
 		 AND efa.account_relationship = 1 
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id ".$tax_contrib_from_sql.$tmp_group_join_contrib.$tmp_mem_join_contrib." ,
 civicrm_option_value as valA, 
civicrm_option_group as grpA
where pp.pledge_id is null"
.$contrib_cid_sql.
" AND c.total_amount <> 0 
AND c.contribution_recur_id is null
AND li.line_total <> 0 
AND
contribution_status_id = valA.value
AND  valA.option_group_id = grpA.id 
AND grpA.name = 'contribution_status'
AND (
       (valA.label  = 'Pending' || valA.label  = 'In Progress' || valA.label  = 'Overdue') 
       OR
       (valA.label  = 'Completed'  ) 
     )
".$tmp_exclude_prepays_sql."
and ( ct.name NOT LIKE 'adjustment-%' AND ct.name NOT LIKE '%---adjustment-%' ) ".$where_clause_contrib_type_ids.$tmp_contrib_where.
" and c.is_test =0 ".$tmp_where_grps_mems." group by c.id, ct.id";
	
	// print "<br><br>participant sql: ".$participant_contributions_sql;
	
	
	
	
 // if(strlen($original_date_sql_name) > 0 && strlen($extended_contrib_table) > 0 ){
 if(false){
 	$participant_contributions_sql = "select c.id as id, ".$tmp_contact_sql_contrib." ct.name as contrib_type, c.source as source, sum(li.line_total) as total_amount,
month( ifnull( extra.".$original_date_sql_name." , c.receive_date) ) as mm_date, day(ifnull( extra.".$original_date_sql_name." , c.receive_date) ) as dd_date ,
 year(ifnull( extra.".$original_date_sql_name." , c.receive_date) ) as yyyy_date, ifnull( extra.".$original_date_sql_name." , c.receive_date) as rec_date,
'contribution' as entity_type, contribution_status_id as status, valA.label as status_label,
'' as recur_amount, '' as recur_installments, li.id as line_item_id, 
".$tax_contrib_select_sql." c.currency as currency,
ct.id as contrib_type_id,  fa.accounting_code as accounting_code ,
'' as line_item_recur_amount ,  '' as recur_crm_id , '' as processor_id , '' as	payment_processor_type, 
if( valA.label = 'Completed' , sum(li.line_total),  0) as received, 0 as adjusted ,  if( valA.label = 'Completed' , 0,  sum(li.line_total) ) as balance
from  civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant' 
	 JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id
				join civicrm_contribution c ON  ep.contribution_id = c.id 
				".$tmp_from_sql_contrib."
 left join civicrm_pledge_payment as pp on c.id = pp.contribution_id
 left join civicrm_financial_type as ct on li.financial_type_id = ct.id
 left join ".$extended_contrib_table." as extra on extra.entity_id = c.id 
 LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
 			 AND efa.account_relationship = 1 
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id ".$tax_contrib_from_sql.$tmp_group_join_contrib.$tmp_mem_join." ,
 civicrm_option_value as valA, 
civicrm_option_group as grpA
where pp.pledge_id is null"
.$contrib_cid_sql.
" AND c.total_amount <> 0 
AND c.contribution_recur_id is null
AND
contribution_status_id = valA.value
AND  valA.option_group_id = grpA.id 
AND grpA.name = 'contribution_status'
AND (
       (valA.label  = 'Pending' || valA.label  = 'In Progress' || valA.label  = 'Overdue') 
       OR
       (valA.label  = 'Completed' ) 
     )
".$tmp_exclude_prepays_sql."
and ( ct.name NOT LIKE 'adjustment-%' AND ct.name NOT LIKE '%---adjustment-%' ) ".$where_clause_contrib_type_ids.$tmp_contrib_where.
" and c.is_test =0 ".$tmp_where_grps_mems." group by c.id, ct.id";
 
 
 
 
 }  
        
        
   

 /*      
   print "<h2>Summary of sql sections</h2>";   
   print "<br>Pledge sql: ".$pledge_sql;
   print "<br><br>Contrib sql: ".$contrib_sql;
   print "<br><br>Recurring sql: ".$recurring_sql;
 */
 		require_once('FinancialCategory.php');
    	$tmpFinancialCategory = new FinancialCategory();
    	$financial_category_field_sql = $tmpFinancialCategory->getFinancialCategoryFieldAsSQL();
    	
    	
    	$line_item_select = " f1.line_item_id , ";
    	
 

	if(strlen($extra_groupby_clause) > 0 ){
	       // print "sql function: Extra group by clause: ".$extra_groupby_clause;
		$group_fields = explode(',' , $extra_groupby_clause );
		if(in_array('contact_id', $group_fields)){
			$tmp_contact = "f1.contact_id, c1.sort_name" ;
		
		}else{
			$tmp_contact = "'' as contact_id , '' as sort_name";
	         }
	
		$select_str  =  "'' as id , ".$tmp_contact." , f1.contrib_type, f1.accounting_code,  '' as source, sum(f1.total_amount) as total_amount, 
		f1.mm_date, f1.dd_date, f1.yyyy_date, f1.rec_date, date_format(f1.rec_date, '%Y-%m-%d' ) as date_for_sort, 
		 '' as entity_type, '' as status,  '' as status_label,  '' as recur_amount, '' as recur_installments, ".$line_item_select." 
		  f1.currency,f1.contrib_type_id, f1.line_item_recur_amount, f1.recur_crm_id, 
		 f1.payment_processor_type, ".$main_tax_select_sql."
		sum(f1.received) as received ,  sum(f1.adjusted) as adjusted , sum(f1.balance) as balance , sum(f1.amt_due) as amt_due, 
		".$financial_category_field_sql."   civicrm_currency.symbol, concat(f1.mm_date , '/' , f1.dd_date , '/' , f1.yyyy_date ) as formatted_date, count(*) as rec_count";
		
		if( $columns_needed == "contact_id" ){
			$select_str  = " f1.contact_id ";
		
		}
		$sql_groupby_clause = " Group By ".$extra_groupby_clause;
	}else{
		$select_str  =  "f1.id, f1.contact_id, f1.contrib_type, f1.accounting_code,  f1.source, f1.total_amount, 
		f1.mm_date, f1.dd_date, f1.yyyy_date, f1.rec_date, date_format(f1.rec_date, '%Y-%m-%d' ) as date_for_sort,
		f1.entity_type, f1.status, f1.status_label, f1.recur_amount, f1.recur_installments, ".$line_item_select." 
		 f1.currency,f1.contrib_type_id, f1.line_item_recur_amount, f1.recur_crm_id, 
		f1.payment_processor_type, ".$main_tax_select_sql."
		f1.received,  f1.adjusted, f1.balance , f1.amt_due, 
		c1.sort_name, ".$financial_category_field_sql."  civicrm_currency.symbol, concat(f1.mm_date , '/' , f1.dd_date , '/' , f1.yyyy_date ) as formatted_date";
		
		if( $columns_needed == "contact_id" ){
			$select_str  = " f1.contact_id ";
		
		}
		
		$sql_groupby_clause = "";
	
	}
	
    //	print "<br>About to build sql, where clause: ".$extra_where_clause;
    $sql_str  = ""; 
    
    
    //print "<br><br>Pledge sql: ".$pledge_sql; 
    
	
		$sql_str =  "SELECT ".$select_str."
		 FROM (
		( ".$pledge_sql." ) 
		UNION ALL
		( ".$contrib_sql_with_recur." )
                UNION ALL
                ( ".$contrib_sql_no_recur." )
		UNION ALL
		( ".$participant_contributions_sql." )
		UNION ALL
		( ".$recurring_sql." ) 
			 )  AS f1 join civicrm_contact as c1 on f1.contact_id  = c1.id  
			 left join civicrm_currency on f1.currency = civicrm_currency.name
			 LEFT JOIN civicrm_financial_type ct on f1.contrib_type_id = ct.id 
		 ".$extra_where_clause.$sql_groupby_clause." ".$order_by_clause;
	
		//print "<br><br>extra where clause: ".$extra_where_clause;
		//print "<br><br><br>pledge sql: <br>".$pledge_sql; 
		
		//print "<br><br><br>contrib sql: <br>".$contrib_sql; 
		
		//print "<br><br>participant contrib sql: ".$participant_contributions_sql;
		
		//print "<br><br>recurring sql: ".$recurring_sql; 
	
	
	

 // print "<br><br>SQL: ".$sql_str;

return $sql_str ; 
}

   
   
   
   
   
   
   }





?>