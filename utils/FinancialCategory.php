<?php

  class FinancialCategory{
  
  function retrieveFinancialPrefixes(){
    // used to create mail merge tokens. 
        $contribution_type_prefixes = array();
   // Create an array of all the various contribution type prefixes, ie the part before the dash.
    	$contribution_type_prefixes['0___all'] =  'All Financial Types';
   
   $prefix_seperator = "---";
   // Get all unique prefixes in use
   
   
	
	
	$sql = "";
	
	
		$sql  = "SELECT distinct( SUBSTRING(name , 1, locate( '".$prefix_seperator."' , name) -1 ) )  as contrib_type_prefix , min(id) as id
		FROM `civicrm_financial_type`
		WHERE is_active = 1
		AND length( SUBSTRING( name, 1, locate( '---', name ) -1 ) ) > 0
		GROUP BY SUBSTRING(name , 1, locate( '".$prefix_seperator."' , name) -1 )
		ORDER BY  contrib_type_prefix asc" ;
	

   
		
  //	print "<br>sql: ".$sql;	
  $dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
	
  while($dao->fetch()){		
  	$tmp_ct_prefix_raw = $dao->contrib_type_prefix ;
  	$tmp_ct_id  = $dao->id;
  	$tmp_ct_prefix = $tmp_ct_id."___".str_replace( ' ', '_', $tmp_ct_prefix_raw);
  	//print "<br> cur rec: ".$tmp_ct_prefix."<br>" ;
  	$contribution_type_prefixes[$tmp_ct_prefix] = $tmp_ct_prefix_raw; 
  	//print_r ($contribution_type_prefixes);
  
  }
  $dao->free();  
  
    
    return $contribution_type_prefixes; 
    
    }
  
  
  	function getFinancialCategoryFieldAsSQL(){
  	
  		$prefix_seperator = "---";
  		return " SUBSTRING(ct.name , 1, locate( '".$prefix_seperator."' , ct.name) -1 ) as financial_category, ";
  	
  	}
  
  
  
  	function getCategoryList(&$tmp_array){
  	 
  	
   
   	$prefix_seperator = "---";
   	
   	
	
		 // Get all unique prefixes in use
	   $sql  = "SELECT distinct( SUBSTRING(name , 1, locate( '".$prefix_seperator."' , name) -1 ) )  as contrib_type_prefix , min(id) as id
	   		FROM civicrm_financial_type
	   		WHERE is_active = 1
	   		AND length( SUBSTRING( name, 1, locate( '---', name ) -1 ) ) > 0
	   		GROUP BY SUBSTRING(name , 1, locate( '".$prefix_seperator."' , name) -1 )
			ORDER BY  contrib_type_prefix asc" ;
	
	
		
  
  	$dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
	
	  while($dao->fetch()){	
	  
	  	$tmp_prefix = $dao->contrib_type_prefix; 	
	  	
	  	$tmp_ct_id  = $dao->id;
	  	
	  	$tmp_array[$tmp_ct_id] = $tmp_prefix ;
	  	//print_r ($contribution_type_prefixes);
	  
	  }
	  $dao->free();  
  
  	 return $tmp_array ; 
  }
  
  
  /***************************************************************************************************/
  function getContributionTypeWhereClauseForSQL( $ct_type_prefix_ids){
	
	
	
	if(!(is_array( $ct_type_prefix_ids) ) ){
		return "";
	}
	
	
	$ct_type_prefix_ids_as_sql = "";
	$i = 1;
	foreach($ct_type_prefix_ids as $cur){
		if(strlen($cur) > 0 && strcmp($cur, "0") <> 0   ){
			$ct_type_prefix_ids_as_sql = $ct_type_prefix_ids_as_sql.$cur;
			
			if($i < sizeof($ct_type_prefix_ids)){
				$ct_type_prefix_ids_as_sql = $ct_type_prefix_ids_as_sql.", ";
				
			}
		
		}
		
		$i += 1;
	}

	
	if(strlen($ct_type_prefix_ids_as_sql) == 0){
		return "";
	
	}

	$tmp_contrib_type_ids_for_sql = "";
	
	
	$prefix_seperator = "---";
	
	
	
	$tmp_ct_sql = "SELECT SUBSTRING(ct_a.name , 1, locate( '".$prefix_seperator."' , ct_a.name) -1 )  as ct_prefix_name ,  ct_b.id as ct_id
				from civicrm_financial_type ct_a , civicrm_financial_type ct_b  
				where ct_a.id IN (".$ct_type_prefix_ids_as_sql.")
				AND SUBSTRING(ct_a.name , 1, locate( '".$prefix_seperator."' , ct_a.name) -1 )  =
				 SUBSTRING(ct_b.name , 1, locate( '".$prefix_seperator."' , ct_b.name) -1 ) ";
	
	
	//	print "<br>sql: ".$tmp_ct_sql ;
	$dao =& CRM_Core_DAO::executeQuery( $tmp_ct_sql,   CRM_Core_DAO::$_nullArray ) ;
	$ct_ids = array();
	while($dao->fetch()){
		$ct_prefix_name = $dao->ct_prefix_name;
		$ct_id = $dao->ct_id; 
		$ct_ids[] = $ct_id;
	
	}
	$dao->free();	
	
	$i = 1;
	foreach($ct_ids as $cur_id){
		$tmp_contrib_type_ids_for_sql = $tmp_contrib_type_ids_for_sql.$cur_id; 
		if($i < sizeof($ct_ids) ){
			$tmp_contrib_type_ids_for_sql = $tmp_contrib_type_ids_for_sql.", "; 
		}
		$i += 1;
	}
	
	//print "<br>contrib type ids for sql: ".$tmp_contrib_type_ids_for_sql;
	if(strlen($tmp_contrib_type_ids_for_sql) > 0 ){
		$where_clause_contrib_type_ids = " ct.id in (".$tmp_contrib_type_ids_for_sql.") ";
	
	}
		
	
	//print "<br><br>About to return: ". $where_clause_contrib_type_ids;
	return $where_clause_contrib_type_ids;
	
  }
  
  
  
  }




?>