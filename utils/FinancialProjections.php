<?php

class FinancialProjections{


function build_recurring_payments_temp_table( $end_date_parm){
  
      //  print "<br>Inside build recurring payments temp table: date: ".$end_date_parm;
    	$temp_table_name = "civicrm_fountaintribe_recurring_contribution"; 
    
    	$sql_create_table = "create table IF NOT EXISTS ".$temp_table_name." like civicrm_contribution";
        $sql_truncate_table = "truncate ".$temp_table_name; 
    
    	$dao_a =& CRM_Core_DAO::executeQuery( $sql_create_table,   CRM_Core_DAO::$_nullArray ) ;
	
	$dao_a->free();
	
	$dao_b =& CRM_Core_DAO::executeQuery( $sql_truncate_table,   CRM_Core_DAO::$_nullArray ) ;
	$dao_b->free();
	
	
	
	$sql_str = "";
	
	
		$sql_str = "select  t1.recur_id, t1.amount, t1.currency, t1.installments, t1.frequency_unit, t1.frequency_interval, 
	   	t1.start_date, t1.contribution_type_id, t1.contact_id, t2.num_completed_payments , t1.currency
	   	 from (SELECT r.id as recur_id, r.amount, r.currency, r.installments, r.frequency_unit, r.frequency_interval, 
	   	date( min(c.receive_date)) as start_date, c.financial_type_id as contribution_type_id , c.contact_id 
	   	FROM `civicrm_contribution_recur` r join civicrm_contribution c on 
		r.id = c.contribution_recur_id
		where (r.installments is not null ) 
		group by r.id) as t1 left join 
		(SELECT r.id as recur_id, count(*) as num_completed_payments FROM `civicrm_contribution_recur` r, 
	civicrm_contribution contrib, 
	civicrm_option_value val, 
		civicrm_option_group grp
		WHERE 
		r.id = contrib.contribution_recur_id
		AND contrib.contribution_status_id = val.value
		AND  val.option_group_id = grp.id 
		AND grp.name = 'contribution_status'
		and contrib.contribution_status_id = val.value
		and val.name in ('Completed' )  
		and contrib.is_test = 0
		group by r.id) as t2
		on t1.recur_id = t2.recur_id 
		where
		(t2.num_completed_payments is null  ||  t1.installments > t2.num_completed_payments) " ;
	
	
    	
   	

		      
   	$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;
    
     	$tmp_display_name = "";
    	while ( $dao->fetch() ) {
      		$tmp_recur_id = $dao->recur_id; 
      		$tmp_recur_amount = $dao->amount;
      		$tmp_installments = $dao->installments; 
      		$tmp_frequency_unit = $dao->frequency_unit;
      		$tmp_frequency_interval = $dao->frequency_interval;
      		$tmp_start_date = $dao->start_date; 
      		$tmp_contribution_type_id = $dao->contribution_type_id; 
      		$tmp_contact_id = $dao->contact_id; 
      		$tmp_completed_payments = $dao->num_completed_payments;
      		$tmp_currency = $dao->currency;
      		
      		//print "<br>recur id: ".$tmp_recur_id." Completed payments: ".$tmp_completed_payments;
      		// TODO: Get number of completed contributions 
      		
      		// Insert appropriate records into temp table
      		for($i =0 ; $i < $tmp_installments; $i++){
      		    // Do date arithmetic. 	
      		    //$tmp_installment_date = "'".$tmp_start_date."'";
      		  
      		    if( $i > $tmp_completed_payments -1){
      		        $interval_num = $i * $tmp_frequency_interval;
      		        $tmp_installment_date = "'$tmp_start_date' + INTERVAL $interval_num $tmp_frequency_unit";  
      		    
      		    
      		     $cur_installment_to_insert = ""; 
      		    
      		     $cur_installment_to_insert = "INSERT INTO ".$temp_table_name." (contribution_recur_id, contact_id, total_amount, financial_type_id, receive_date, currency   ) 
      		        values ($tmp_recur_id, $tmp_contact_id, $tmp_recur_amount, $tmp_contribution_type_id, $tmp_installment_date, '$tmp_currency'   ) "; 
      		    
      		    
      		    	
      		       
      		    
      		    //    print "<BR>new row: ".$cur_installment_to_insert ; 
      		    
      		        $dao_insert =& CRM_Core_DAO::executeQuery( $cur_installment_to_insert,   CRM_Core_DAO::$_nullArray ) ;
      		        
      		        }
      		
      		}
      		
      
    	}
    	$dao->free( ); 
        

   
   	return $temp_table_name; 
   }





}





?>