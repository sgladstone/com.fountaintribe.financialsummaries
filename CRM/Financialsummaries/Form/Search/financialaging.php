<?php



class CRM_Financialsummaries_Form_Search_financialaging extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
   
   
   
    protected $_formValues;
    
     function __construct( &$formValues ) {
        parent::__construct( $formValues );

       // $this->_eventID = CRM_Utils_Array::value( 'event_id',
       //                                           $this->_formValues );
	
	if(isset( $this->_formValues['priceset_option_id'] )){
		$tmp_option_value_raw =   $this->_formValues['priceset_option_id'] ; 
	}else{
		$tmp_option_value_raw = "";
	}
	//$form_values = split('_' , $tmp_option_value_raw );
	
	$this->_userChoices = $tmp_option_value_raw; 
	
	$tmp_all_events = array();
	$tmp_all_priceset_options = array();
	
	if(is_array($this->_userChoices)){
		foreach ($this->_userChoices as $dontCare => $curUserChoice ) {
	   		$tmp_cur = split('_' ,$curUserChoice );
	   		$tmp_all_events[] = $tmp_cur[0]; 
	   		$tmp_all_priceset_options[] = $tmp_cur[1]; 		  
		}
	}
	
	
	$this->_allChosenEvents  = $tmp_all_events ; 
	$this->_allChosenPricesetOptions = $tmp_all_priceset_options;
	
	
	//print "<hr><br>User choice original array: ";
	//print_r($this->_userChoices);
	
	//print "<br>all events: ";
	//print_r($tmp_all_events);
	
	//print "<br>all priceset options: ";
	//print_r($tmp_all_priceset_options); 
	
	//$tmpEventId = $form_values[0];
	//$tmp_priceset_id = $form_values[1];
	
	//$this->_eventID = $tmpEventId; 
	// $this->_pricesetOptionId = $tmp_priceset_id ; 
	
	//print "<hr><br>Current event id: ".$this->_eventID; 	
	
      //  $this->setColumns( );

        

    }

    function __destruct( ) {
        /*
        if ( $this->_eventID ) {
            $sql = "DROP TEMPORARY TABLE {$this->_tableName}";
            CRM_Core_DAO::executeQuery( $sql );
        }
        */
    }


/***********************************************************************************************/

   
    function buildForm( &$form ) {
        

	/*   Create a select list of the various price set options */
	
	
        // $tmpPriceSetOptions[$fieldName] = ' test' ;
        // print "<br>Options array for select list: ";
        // print_r($tmpPriceSetOptions); 
        
                  

        /**
         * You can define a custom title for the search form
         */
         $this->setTitle('Financial Aging');
         
         /**
         * if you are using the standard template, this array tells the template what elements
         * are part of the search criteria
         */
        // $form->assign( 'elements', array(   'priceset_option_id'  ) );
        
        
         // TODO: Check user authority to CiviContribute
       /* 
       if (is_user_authorized('access CiviContribute') == false ){
      		 $this->setTitle('Not Authorized');
       		return; 
       
       }

    */
   	
         
	
	  $select2style = array(
	      'multiple' => TRUE,
	      'style' => 'width:100%; max-width: 100em;',
	      'class' => 'crm-select2',
	      'placeholder' => ts('- select -'),
	    );
	    
	
$group_ids = array();

	    $group_result = civicrm_api3('Group', 'get', array(
      'sequential' => 1,
      'is_active' => 1,
      'is_hidden' => 0,
       'options' => array('sort' => "title"),
    ));

    if( $group_result['is_error'] == 0 ){
             $tmp_api_values = $group_result['values'];
             foreach($tmp_api_values as $cur){
                   $grp_id = $cur['id'];

                   $group_ids[$grp_id] = $cur['title'];


  }
} 	


// get membership ids and org contact ids. 
           $mem_ids = array(); 
           $org_ids = array();
             $api_result = civicrm_api3('MembershipType', 'get', array(
      'sequential' => 1, 
'is_active' => 1,
  'options' => array('sort' => "name"),
        ));

          if( $api_result['is_error'] == 0 ){
             $tmp_api_values = $api_result['values'];
             foreach($tmp_api_values as $cur){
              
                 $tmp_id = $cur['id'];
               	 $mem_ids[$tmp_id] = $cur['name']; 
               	 
               	 $org_id = $cur['member_of_contact_id']; 
               	 // get display name of org
               	     $result = civicrm_api3('Contact', 'getsingle', array(
			      'sequential' => 1,
			      'id' => $org_id ,
			    ));
               	 $org_ids[$org_id] = $result['display_name']; 
                          
             
             }
          
          }
    	
     
       		     $form->add('select', 'group_of_contact',
		      ts('Contact is in the group(s)'),
		      $group_ids,
		      FALSE,
		      $select2style
		    );
       	
	         $form->add('select', 'membership_type_of_contact',
	      ts('Contact has the membership of type(s)'),
	     $mem_ids,
	      FALSE,
	      $select2style);
	      
	          $form->add('select', 'membership_org_of_contact',
	      ts('Contact has Membership In'),
	      $org_ids,
	      FALSE,
	      $select2style);
       
         
        
        $form->addDate('end_date', ts('Due By'), false, array( 'formatType' => 'custom' ) );
    
	 
	
	$financial_type_choices = array( ); 
	$accounting_code_choices = array( ); 
	
	
	
	$financial_type_sql = "";
	
	
		
		
		 $financial_type_sql = "Select ct.id, ct.name, fa.accounting_code from civicrm_financial_type ct 
        	 	LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
        	 	AND efa.account_relationship = 1 
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id 
			    where ct.is_active = 1 order by name";
	
	
	
	$contrib_dao = & CRM_Core_DAO::executeQuery( $financial_type_sql, CRM_Core_DAO::$_nullArray );
                                             
         while ($contrib_dao->fetch()){
         	
              $cur_id = $contrib_dao->id;
              $cur_name = $contrib_dao->name; 
              $accounting_code = $contrib_dao->accounting_code; 
              
              $pos_a = strpos($cur_name, 'adjustment-');
              $pos_b = strpos($cur_name, 'prepayment-');
              
              if ($pos_a === false && $pos_b === false) {
              
	              if( strlen($accounting_code) > 0 ){
                		$tmp_description = $cur_name." - ".$accounting_code;
                		$accounting_code_choices[$accounting_code] = $accounting_code;
              	}else{
              	 		$tmp_description = $cur_name;
             		}
             
              		$financial_type_choices[$cur_id] = $tmp_description;


 		}        
         }
         
        $contrib_dao->free();   
               
  
     
       	
	         $form->add('select', 'financial_type',
	      ts('Financial Type(s)'),
	     $financial_type_choices,
	      FALSE,
	      $select2style);
	    
        
        
        
         natcasesort ($accounting_code_choices);
         
          $form->add('select', 'accounting_code', ts('Accounting Code'),  $accounting_code_choices, FALSE,
          array('id' => 'accounting_code', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );
       
                     
                         
        $form->add ( 'text', 'num_days_overdue', ts('Number Days Overdue'));
        
        $layout_choices = array();
        $layout_choices[''] = '  -- Select Layout -- ';
        $layout_choices['details'] = 'Details';
        $layout_choices['summarize_contact_contribution_type'] = 'Summarized by Contact, Financial Type';
        $layout_choices['summarize_contact'] = 'Summarized by Contact';
     //   $layout_choices['summarize_household_contribution_type'] = 'Summarized by Household, '.$fin_type_label;
     //   $layout_choices['summarize_household'] = 'Summarized by Household';
        $layout_choices['summarize_contribution_type'] = 'Summarized by Financial Type';
      //  $layout_choices['summarize_accounting_code'] = 'Summarized by Accounting Code';
        
        $form->add  ('select', 'layout_choice', ts('Layout Choice'),
                     $layout_choices,
                     false);
                     
        
       // Get communication preferences
        $comm_prefs =  array(); 

              $api_result = civicrm_api3('OptionValue', 'get', array(
      'sequential' => 1,
      'option_group_id' => "preferred_communication_method",
      'is_active' => 1,
      'options' => array('sort' => "label"),
    ));

      $comm_prefs[''] = '  -- Select -- ';;  
      if( $api_result['is_error'] == 0 ){
             $tmp_api_values = $api_result['values'];
             foreach($tmp_api_values as $cur){
              
                 $tmp_id = $cur['id'];
                 $comm_prefs[$tmp_id] = $cur['label'];
                 
}

}
        
         $comm_prefs_select = $form->add  ('select', 'comm_prefs', ts('Communication Preference'),
         	      $comm_prefs, 
                     false);  
                     
                         
      $form->assign( 'elements', array( 'group_of_contact', 'membership_org_of_contact' , 'membership_type_of_contact' ,  'end_date' , 'num_days_overdue', 'financial_type' ,  'date_selection', 'comm_prefs',  'layout_choice') );
      
      //
     



   //	$form->assign( 'elements', array( 'target_date') );
   	

  
   
   
    }

    function &columns() {
    	
    	// TODO: check if user is authorized to CiviContribute

/*
    	
       if ( is_user_authorized('access CiviContribute') == false ){
       	$columns_to_show = array( ts('You are not authorized to this area' )    		=> 'total_amount', );  
        $this->_columns = $columns_to_show; 
        	return ; 
       
       }
       
*/       
    	
 	$fin_type_label  = "Financial Type"; 
 
 
    if(isset($this->_formValues['layout_choice'])){
    	$layout_choice = $this->_formValues['layout_choice'] ;
    }else{
    	$layout_choice = "";
    }
    	
        if(  $layout_choice == 'summarize_contribution_type'  ){
        	$columns_to_show = array(        			
        			ts('0-30 Days')	=> 'days_30',
                                 ts('31-60 Days')  => 'days_60',
                                 ts('61-90 Days')  => 'days_90',
                                 ts('91 or more Days') => 'days_91_or_more', 	
        			// ts('Contact Id')      => 'contact_id'    ,
                                 ts($fin_type_label)  => 'contribution_type_name',
                                // ts('Accounting Code') => 'accounting_code', 
                                 ts('Financial Set') => 'financial_category', 
                                 ts('Date Criteria')	=> 'date_parm', 
                               //  ts('Expected Date')	=> 'expected_date',
                                  ts('Currency')		=> 'currency',
                                  ts('Total Amount') 	=> 'total_amount',  
                                 ts('Days Overdue')	=> 'days_overdue',
                                
                                 ts('Num. Records Combined') => 'num_records' );
        
        
        
        }else if($layout_choice == 'summarize_contact' || $layout_choice == 'summarize_household' ){
         	
        	$columns_to_show = array(
        			ts('Name') 		=> 'sort_name', 
        			ts('0-30 Days')	=> 'days_30',
                                 ts('31-60 Days')  => 'days_60',
                                 ts('61-90 Days')  => 'days_90',
                                 ts('91 or more Days') => 'days_91_or_more', 	
        			// ts('Contact Id')      => 'contact_id'    ,
                                
                                 ts('Date Criteria')	=> 'date_parm', 
                               //  ts('Expected Date')	=> 'expected_date',
                                  ts('Currency')		=> 'currency',
                                  ts('Total Amount') 	=> 'total_amount',  
                                 ts('Days Overdue')	=> 'days_overdue',
                                
                                 ts('Num. Records Combined') => 'num_records' );
        
        
        
        
        }else if($layout_choice == 'summarize_accounting_code'){
        	$columns_to_show = array( 
        			ts('0-30 Days')	=> 'days_30',
                                 ts('31-60 Days')  => 'days_60',
                                 ts('61-90 Days')  => 'days_90',
                                 ts('91 or more Days') => 'days_91_or_more', 	
        			// ts('Contact Id')      => 'contact_id'    ,
                                
                              //   ts('Accounting Code') => 'accounting_code', 
                                 ts('Date Criteria')	=> 'date_parm', 
                               //  ts('Expected Date')	=> 'expected_date',
                                  ts('Currency')		=> 'currency',
                                  ts('Total Amount') 	=> 'total_amount',  
                                 ts('Days Overdue')	=> 'days_overdue',
                                
                                 ts('Num. Records Combined') => 'num_records' );
        
        
        }else{
    
    
        	$columns_to_show = array(
        			ts('Name') 		=> 'sort_name', 
        			ts('0-30 Days')	=> 'days_30',
                                 ts('31-60 Days')  => 'days_60',
                                 ts('61-90 Days')  => 'days_90',
                                 ts('91 or more Days') => 'days_91_or_more', 	
                                 ts($fin_type_label )  => 'contribution_type_name',
                            //     ts('Accounting Code') => 'accounting_code', 
                                 ts('Financial Set') => 'financial_category', 
                                 ts('Date Criteria')	=> 'date_parm', 
                                 ts('Expected Date')	=> 'expected_date',
                                  ts('Currency')		=> 'currency',
                                  ts('Total Amount') 	=> 'total_amount',  
                                 ts('Days Overdue')	=> 'days_overdue',
                                 ts('Type')		=> 'entity_type' , 
                                 ts('ID' )   		=> 'entity_id', 
                                 ts('Num. Records Combined') => 'num_records' );
                                 
                               //   ts('Email') 	       => 'email',
                               //  ts('Phone')	       => 'phone',
                               //  ts('Address' )	=> 'street_address',
                               //  ts('Address line 1') => 'supplemental_address_1',
                               //  ts('City') 		=> 'city',
                               //  ts('State') =>  'state',
                               //  ts('Postal Code') => 'postal_code' ); 
                                                                
    //  $this->_columns = array( ts('Contact Id')      => 'contact_id'  ); 
    
    	}
        
      //  $this->_columns = $columns_to_show; 
      return $columns_to_show;
        
    }

  


    function select($summary_section = false, $onlyIDs){
    
    
    
    
    	$select = "";
    	$end_date_parm = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
      
     $layout_choice = $this->_formValues['layout_choice'] ;
     
    
     //print "<br>End date: ".$end_date_parm ; 
     if(strlen( $end_date_parm ) > 0 ){
       
     $iyear = substr($end_date_parm, 0, 4);
     $imonth = substr($end_date_parm , 4, 2);
     $iday = substr($end_date_parm, 6, 2);
     $end_date_parm = $iyear.'-'.$imonth.'-'.$iday; 
      
     }
      
   // print "<br>End date: ".$end_date_parm ; 
      
  //    [receive_date_relative] => 0 [receive_date_from] => [receive_date_to]
   //   print_r( $this->_params );
   $tmp_contrib_where = '';	
   $tmp_pledge_pay_where = '';
   if(strlen($end_date_parm) > 0 ){
      $tmp_select_field = "'".$end_date_parm."'" ; 
      $base_date = "'".$end_date_parm."'";
   
   }else{
   	 $tmp_select_field = "DATE(now())"; 
   	 $base_date = "now()";	
   
   }
   
    $groupby = "";
        
        $tmp_30_days = "if(   (datediff( date($base_date) ,date(expected_date)) >= 0  AND datediff(date($base_date) ,date(expected_date)) <= 30) , total_amount,  NULL)";
    	$tmp_60_days = "if(   (datediff( date($base_date) ,date(expected_date)) > 30 AND datediff(date($base_date) ,date(expected_date)) <= 60) , total_amount,  NULL)";
    	$tmp_90_days = "if(   (datediff( date($base_date) ,date(expected_date)) > 60 AND datediff(date($base_date) ,date(expected_date)) <= 90) , total_amount,  NULL)";
    	$tmp_91_days = "if(   (datediff( date($base_date) ,date(expected_date)) > 90)  , total_amount,  NULL)";
    	
    	 

    	require_once('utils/FinancialCategory.php');
    	$tmpFinancialCategory = new FinancialCategory();
    	$financial_category_field_sql = $tmpFinancialCategory->getFinancialCategoryFieldAsSQL();
    
        if ( $onlyIDs ) {
        	$select  = "contact_a.id as contact_id, contact_a.id as id ";
    	}else{
    		if($summary_section){
    			$select = "contact_a.id as contact_id, max(".$tmp_select_field.") as date_parm , 
   sum(total_amount) as total_amount, currency, max( ct.name) as contribution_type_name, 
   max( date(expected_date)) as expected_date, max(datediff(date($base_date) , date(expected_date))) as days_overdue, 
  sum(".$tmp_30_days.") as days_30, sum(".$tmp_60_days.") as days_60, sum(".$tmp_90_days.") as days_90, sum(".$tmp_91_days.") as days_91_or_more, count(*) as num_records";
    		
    		
    		}else{	
    	
    	
    		  $select = "";
    		//print "<br><br>layout choice: ".$layout_choice;
    		if( $layout_choice == 'summarize_contact' || $layout_choice == 'summarize_contact_contribution_type' ||  $layout_choice == 'summarize_contribution_type' 
    		|| $layout_choice == 'summarize_accounting_code'  || $layout_choice == 'summarize_household_contribution_type' || $layout_choice == 'summarize_household'){
    		
    		    
    			$select = "contact_a.id as contact_id, contact_a.sort_name as sort_name, contact_a.contact_type, max(".$tmp_select_field.") as date_parm , 
    			 sum(total_amount) as total_amount, currency, ct.name as contribution_type_name, 
    			 ".$financial_category_field_sql."
   max( date(expected_date)) as expected_date, max(datediff(date($base_date) ,date(expected_date))) as days_overdue, 
  sum(".$tmp_30_days.") as days_30, sum(".$tmp_60_days.") as days_60, sum(".$tmp_90_days.") as days_90, sum(".$tmp_91_days.") as days_91_or_more, count(*) as num_records,
   ctype_a.image_URL as type_image, contact_a.contact_sub_type as contact_sub_type, ctype_b.image_URL as sub_type_image, underlying_contact_id";
  
  			
    			
    		}else{ 
    			
    		  $select = "contact_a.id as contact_id,  contact_a.sort_name as sort_name, contact_a.contact_type, ".$tmp_select_field." as date_parm , 
			t1.entity_id, t1.entity_type,  total_amount as total_amount, currency, ct.name as contribution_type_name,
			".$financial_category_field_sql."
   date(expected_date) as expected_date, datediff(date($base_date) ,date(expected_date)) as days_overdue,
  ".$tmp_30_days." as days_30, ".$tmp_60_days." as days_60, ".$tmp_90_days." as days_90, ".$tmp_91_days." as days_91_or_more, 1 as num_records,
   ctype_a.image_URL as type_image, contact_a.contact_sub_type as contact_sub_type, ctype_b.image_URL as sub_type_image, underlying_contact_id" ;
    			 
    			 
  			
  		}
	}
	}
        
        
      //  print "<hr><br><br>Layout choice: ".$layout_choice." <br> Inside figure out select: ".$select;
        
        
    return $select; 
    
    }
   // return $this->all( $offset, $rowcount, $sort, false, true );
   
    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false, $onlyIDs = false ) {
       
       // TODO: check authority of end-user
       /*
       if ( is_user_authorized('access CiviContribute') == false ){
       		return "select 'You are not authorized to this area' as total_amount from  civicrm_contact where 1=0 limit 1"; 
       		
       }

   */
     //   $end_date_parm  = $this->_params['end_date'] ;
        $groupby = "";
        $layout_choice = $this->_formValues['layout_choice'] ;
     	 if ( $onlyIDs ) {
        	$groupby = "";
    	}else{
    		//print "<br><br>layout choice: ".$layout_choice;
    		if( $layout_choice == 'summarize_contact_contribution_type'){
    	
    			$groupby = "Group By t1.contact_id , currency, ct.id";
    		}else if( $layout_choice == 'summarize_household_contribution_type'){
    	
    			$groupby = "Group By t1.contact_id , currency, ct.id";
    		}else if ($layout_choice == 'summarize_contribution_type'){
    		
    			$groupby = "Group By currency, ct.id";
    		}else if($layout_choice == 'summarize_accounting_code'){
    			
					$groupby = "Group By currency";	
			
    		}else if($layout_choice == 'summarize_contact'){
    		
    			$groupby = "Group By t1.contact_id , currency";
    		
    		}else if($layout_choice == 'summarize_household'){
    		
    			$groupby = "Group By t1.contact_id , currency";
    		
    		}else{ 
			
  			$groupby = "";
  		}

	}
    	// make sure selected smart groups are cached in the cache table
	$group_of_contact = $this->_formValues['group_of_contact'];
	

// TODO: test with smart groups
	//require_once('utils/CustomSearchTools.php');
	//$searchTools = new CustomSearchTools();
	//$searchTools::verifyGroupCacheTable($group_of_contact ) ;
	
   
        $grand_totals = false; 
      //  print "<br> grand totals? ".$grand_totals;
        $select = $this->select($grand_totals, $onlyIDs);
        $from = $this->from(); 
        $where = $this->where($includeContactIDs); 
      
      // Had to nest the real query as a sub-query because otherwise it cannot be used as a smart group.
      // Smart groups do NOT like queries with the distinct keyword.  
      // contact_a.id as contact_id
      	 if ( $onlyIDs ) {
      	 	$outer_select =  "contact_a.id as contact_id";
      	 }else{
      	 	$outer_select = "contact_b.*";
      	 
      	 
      	 }
      	 
      
        $sql  = "SELECT ".$outer_select." FROM (SELECT DISTINCT $select 
   	from $from 
	$where
	$groupby ) as contact_b
	LEFT JOIN civicrm_contact contact_a ON contact_b.contact_id = contact_a.id 
	WHERE 1=1"; 
	
	
	// -- this last line required to play nice with smart groups
      // INNER JOIN civicrm_contact contact_a ON contact_a.id = r.contact_id_a
      
	//for only contact ids ignore order.
      if ( !$onlyIDs ) {
          // Define ORDER BY for query in $sort, with default value
          if ( ! empty( $sort ) ) {
              if ( is_string( $sort ) ) {
                  $sql .= " ORDER BY $sort ";
              } else {
                  $sql .= " ORDER BY " . trim( $sort->orderBy() );
              }
          } else {
              //$sql .=   "ORDER BY contact_id, contribution_type_name";
          }
      }

  	if ( $rowcount > 0 && $offset >= 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }


    if ( $onlyIDs ){
    	// print "<br><br>full sql: ". $sql;   
	}
	
	//print "<br><br>full sql: ". $sql; 

CRM_Core_Error::debug_var('Aging sql:', $sql);	
        return $sql;
	                  
    
                           
                         
                           
 
    }
    
    
    
    
    
    function from( ) {
    
    // Need to do date related stuff
    $end_date_parm = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
      
     $layout_choice = $this->_formValues['layout_choice'] ;
     
    
     //print "<br>End date: ".$end_date_parm ; 
     if(strlen( $end_date_parm ) > 0 ){
       
     $iyear = substr($end_date_parm, 0, 4);
     $imonth = substr($end_date_parm , 4, 2);
     $iday = substr($end_date_parm, 6, 2);
     $end_date_parm = $iyear.'-'.$imonth.'-'.$iday; 
      
     }
      
   // print "<br>End date: ".$end_date_parm ; 
      
  //    [receive_date_relative] => 0 [receive_date_from] => [receive_date_to]
   //   print_r( $this->_params );
   $tmp_contrib_where = '';	
   $tmp_pledge_pay_where = '';
   if(strlen($end_date_parm) > 0 ){
      $tmp_contrib_where = " AND DATE(contrib.receive_date) < '".$end_date_parm."'";
      $tmp_pledge_pay_where = " and DATE(pp.scheduled_date) < '".$end_date_parm."'";
      $tmp_recur_where = " DATE(receive_date) < '".$end_date_parm."'";
     
   }else{
       $tmp_contrib_where = " AND DATE(contrib.receive_date) <= now()";	
       $tmp_pledge_pay_where = " and DATE(pp.scheduled_date) <=  now()";
       $tmp_recur_where = " DATE(receive_date) <=  now()";
   	
   }
   
   // end of date-related stuff
   
   
    
    //require_once('utils/FinancialProjections.php');
    //   $FinancialProjections = new FinancialProjections();
       
    //  $tmp_recur_table_name =   $FinancialProjections->build_recurring_payments_temp_table( $end_date_parm);
         $tmp_recur_table_name = ""; 
       
    	 $tmp_from = ""; 
  	/*
  	$tmp_group_join = "";
  	if(count( $this->_formValues['group_of_contact'] ) > 0 ){
  	        // Join on contact id of underlying individual, even if data is summarized by household. 
  		$tmp_group_join = "LEFT JOIN civicrm_group_contact as groups on t1.underlying_contact_id = groups.contact_id
  				   LEFT JOIN civicrm_group_contact_cache as groupcache ON t1.underlying_contact_id = groupcache.contact_id  "; 
  	
  	
  	}
  	
        
        $tmp_mem_join = "";
  	if( count( $this->_formValues['membership_type_of_contact'] ) > 0 || count( $this->_formValues['membership_org_of_contact'] ) > 0     ){
  		// Join on contact id of underlying individual, even if data is summarized by household. 
  		$tmp_mem_join = "LEFT JOIN civicrm_membership as memberships on t1.underlying_contact_id = memberships.contact_id
	 	 LEFT JOIN civicrm_membership_status as mem_status on memberships.status_id = mem_status.id
	 	 LEFT JOIN civicrm_membership_type mt ON memberships.membership_type_id = mt.id ";
  	
  	}
  	*/
  	
  	if(strlen( $comm_prefs = $this->_formValues['comm_prefs']) > 0  ){
  		$tmp_email_join = "LEFT JOIN civicrm_email ON t1.underlying_contact_id = civicrm_email.contact_id AND civicrm_email.is_primary = 1 "; 
  	}else{
  		$tmp_email_join = "";
  	}
  	
  	
	
	$contrib_type_sql = "";
	
	$tmp_from = ""; 
	
	// $layout_choice = $this->_formValues['layout_choice'] ;
	 if( $layout_choice == 'summarize_household_contribution_type' || $layout_choice == 'summarize_household'){
    	
    		$tmp_contact_sql_contrib = "rel.contact_id_b as household_id , ifnull( rel.contact_id_b,  contrib.contact_id ) as contact_id, contrib.contact_id as underlying_contact_id , ";
    		$tmp_contact_sql_pledge = "rel.contact_id_b as household_id, ifnull( rel.contact_id_b, p.contact_id ) as contact_id, p.contact_id as underlying_contact_id , ";
    		
    		$tmp_rel_type_ids = "7, 6";   // Household member of , Head of Household 
    		$tmp_from_sql_contrib = " LEFT JOIN civicrm_relationship rel ON contrib.contact_id = rel.contact_id_a AND rel.is_active = 1 AND rel.is_permission_b_a = 1 AND rel.relationship_type_id IN ( ".$tmp_rel_type_ids." ) ";
    		$tmp_from_sql_pledge = "LEFT JOIN civicrm_relationship rel ON p.contact_id = rel.contact_id_a AND rel.is_active = 1 AND rel.is_permission_b_a = 1 AND rel.relationship_type_id IN ( ".$tmp_rel_type_ids." ) ";
    		/*
    		$tmp_from_sql_pledge = "LEFT JOIN civicrm_relationship rel ON p.contact_id = (
    			 SELECT rel.contact_id_a 
		         FROM civicrm_relationship rel 
		         WHERE p.contact_id = rel.contact_id_a AND rel.is_active = 1 AND rel.relationship_type_id IN ( ".$tmp_rel_type_ids." ) 
		         GROUP BY rel.contact_id_a
		         ORDER BY rel.id 
		         LIMIT 1
		       ) AND rel.is_active = 1 AND rel.relationship_type_id IN ( ".$tmp_rel_type_ids." )  ";
    		*/
    	}else{
    		$tmp_contact_sql_contrib = " contrib.contact_id as contact_id, contrib.contact_id as underlying_contact_id ,";
    		$tmp_contact_sql_pledge =  " p.contact_id as contact_id, p.contact_id as underlying_contact_id , ";
    		
    		$tmp_from_sql_contrib = "";
    		$tmp_from_sql_pledge = ""; 
    		
    	
    	}
    		 
	 
	
	if( strlen($tmp_recur_table_name) > 0 ){
	
	$recur_section_sql = "SELECT ".$tmp_contact_sql_contrib." contrib.total_amount, contrib.id as entity_id,  'automatic recurring' as entity_type, 
	   	contrib.receive_date, contrib.currency, contrib.source, '' as label, contrib.financial_type_id, receive_date as expected_date
		FROM  ".$tmp_recur_table_name." as contrib ".$tmp_from_sql_contrib."
		LEFT JOIN civicrm_contribution_recur recur ON contrib.contribution_recur_id = recur.id
		WHERE recur.contribution_status_id <> 3 AND ".$tmp_recur_where;
		
		}else{
			$recur_section_sql = "SELECT id as contact_id, id as underlying_contact_id , 
			 0  as total_amount, 0 as entity_id,  'automatic recurring' as entity_type, 
	   	'0000-00-00' as receive_date,  'USD' as currency, '' as source, '' as label,  0 as financial_type_id, '0000-00-00' as expected_date
		FROM civicrm_contact LIMIT 1"; 
		
		}

	/*
	$recur_section_sql = "SELECT ".$tmp_contact_sql_contrib." contrib.total_amount, contrib.id as entity_id,  'automatic recurring' as entity_type, 
	   	contrib.receive_date, contrib.currency, contrib.source, '' as label, contrib.financial_type_id, receive_date as expected_date
		FROM  ".$tmp_recur_table_name." as contrib ".$tmp_from_sql_contrib."
		LEFT JOIN civicrm_contribution_recur recur ON contrib.contribution_recur_id = recur.id
		WHERE recur.contribution_status_id <> 3 AND ".$tmp_recur_where;
	*/	
			
	
			$tmp_from = "( (SELECT ".$tmp_contact_sql_contrib."  sum(li.line_total) as total_amount, contrib.id as entity_id, 'contribution' as entity_type,
	   	contrib.receipt_date, contrib.currency, contrib.source, val.label, li.financial_type_id, contrib.receive_date as expected_date
		FROM civicrm_line_item li JOIN civicrm_contribution contrib ON li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution' 
		 ".$tmp_from_sql_contrib." ,
		civicrm_option_value val, 
		civicrm_option_group grp
		WHERE 
		contrib.contribution_status_id = val.value
		AND  val.option_group_id = grp.id 
		AND grp.name = 'contribution_status'
		and contrib.contribution_status_id = val.value
		and val.name in ('Failed', 'Pending', 'Overdue', 'In Progress' )  
		and contrib.contribution_recur_id is null".$tmp_contrib_where.
		" and contrib.is_test = 0
		group by li.financial_type_id, contrib.id )
		UNION ALL (
		SELECT ".$tmp_contact_sql_contrib."  sum(li.line_total) as total_amount, contrib.id as entity_id, 'contribution' as entity_type,
	   	contrib.receipt_date, contrib.currency, contrib.source, val.label, li.financial_type_id, contrib.receive_date as expected_date
		FROM civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant' 
	 JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id
				join civicrm_contribution contrib ON  ep.contribution_id = contrib.id 
		 ".$tmp_from_sql_contrib." ,
		civicrm_option_value val, 
		civicrm_option_group grp
		WHERE 
		contrib.contribution_status_id = val.value
		AND  val.option_group_id = grp.id 
		AND grp.name = 'contribution_status'
		and contrib.contribution_status_id = val.value
		and val.name  in ('Failed', 'Pending', 'Overdue', 'In Progress' ) 
		and contrib.contribution_recur_id is null".$tmp_contrib_where.
		" and contrib.is_test = 0
		group by li.financial_type_id, contrib.id
		)
		UNION ALL
		( SELECT ".$tmp_contact_sql_pledge."  pp.scheduled_amount as total_amount, pp.id as entity_id , 'pledge payment' as entity_type, 
	pp.scheduled_date as date, p.currency as currency, 'pledge' as source, val.label as label, p.financial_type_id, pp.scheduled_date as expected_date
	FROM  `civicrm_pledge` AS p ".$tmp_from_sql_pledge." 
	, civicrm_pledge_payment as pp,
	civicrm_option_value  val, 
	civicrm_option_group grp
	WHERE p.id = pp.pledge_id
	and val.name in ('Failed', 'Pending', 'Overdue', 'In Progress' )".
	$tmp_pledge_pay_where.
	" and pp.status_id = val.value
	AND  val.option_group_id = grp.id 
	AND grp.name = 'contribution_status'
	AND p.status_id IN ( 1, 2, 5 ,6 )
	and p.is_test = 0
		order by 1 )
		UNION ALL
		(".$recur_section_sql."
		)
		) as t1 INNER JOIN civicrm_contact contact_a ON contact_a.id =  t1.contact_id  
		JOIN civicrm_financial_type as ct ON t1.financial_type_id = ct.id
		JOIN civicrm_contact_type as ctype_a  ON contact_a.contact_type = ctype_a.name
		LEFT JOIN civicrm_contact_type as ctype_b  ON contact_a.contact_sub_type = ctype_b.name
		$tmp_email_join";
		
	
	/*
	LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
        	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id 
        	
        	
        */ 	
	
    
        return $tmp_from;



    }

    function where( $includeContactIDs = false ) {
       $clauses = array( );

	 $financial_type_ids = $this->_formValues['financial_type'] ;
        
         if( ! is_array($financial_type_ids)){
         
         	//print "<br>No contrib type selected.";
         	
         
         }else{
         	
         	$i = 1;
         	$tmp_id_list = '';
         	foreach($financial_type_ids as $cur_id){
         		if(strlen($cur_id ) > 0){
	         		$tmp_id_list = $tmp_id_list." '".$cur_id."'" ; 
	         		if($i < sizeof($financial_type_ids)){
	         			$tmp_id_list = $tmp_id_list."," ; 
	         		}
	         	}	
         		$i += 1;
         	}
         	
         	if(!(empty($tmp_id_list)) ){
         		$clauses[] = "ct.id IN ( ".$tmp_id_list." ) ";
         	
         	}
         	
     		//if(strlen($contrib_type_id) > 0){
        	//	$clauses[] = "f1.contrib_type_id = '".$contrib_type_id."' ";
    		// }
    	 }

	// Check user choice of accounting code.
	$accounting_codes = $this->_formValues['accounting_code'] ;
        
         if( ! is_array($accounting_codes)){
         
         	//print "<br>No accounting code selected.";
         	
         
         }else if(is_array($accounting_codes)) {
         	//print "<br>accounting codes: ";
         	//print_r($accounting_codes);
         	$i = 1;
         	$tmp_id_list = '';
         	
         	foreach($accounting_codes as $cur_id){
         		if(strlen($cur_id ) > 0){
         			$tmp_id_list = $tmp_id_list." '".$cur_id."'" ; 
         			
         		
	         		if($i < sizeof($accounting_codes)){
	         			$tmp_id_list = $tmp_id_list."," ; 
	         		}
	         	}
         		$i += 1;
         	}
         	
         	
         	if(!(empty($tmp_id_list))  ){
         		//print "<br><br>id list: ".$tmp_id_list;
         		$clauses[] = "ct.accounting_code IN ( ".$tmp_id_list." ) ";
         		//print "<br>";
         		//print_r ($clauses);
         	
         	}
         	
     		//if(strlen($contrib_type_id) > 0){
        	//	$clauses[] = "f1.contrib_type_id = '".$contrib_type_id."' ";
    		// }
    	 }
    	 




	$groups_of_contact = $this->_formValues['group_of_contact'];
	
	$membership_types_of_contact = $this->_formValues['membership_type_of_contact'];
	
	$membership_orgs_of_contact = $this->_formValues['membership_org_of_contact'];
       ///////////////////////////////////////////////////////////////////////////////
	// Need to deal with group and membership filters. 
	/*
	require_once('utils/CustomSearchTools.php');
	$searchTools = new CustomSearchTools();
		
	$contact_field_name = "t1.underlying_contact_id"; 	
	$searchTools->updateWhereClauseForGroupsChosen($groups_of_contact, $contact_field_name, $clauses );
			
  	
	$searchTools->updateWhereClauseForMemberships( $membership_types_of_contact,  $membership_orgs_of_contact, $contact_field_name,  $clauses   ) ; 
	
	*/
	////////////////////////////////////////////////////////////////////////////////
	
	/*

	// Figure out if end-user is filtering results according to groups. 
	//require_once('utils/CustomSearchTools.php');
	//$searchTools = new CustomSearchTools();
	
	$comm_prefs = $this->_formValues['comm_prefs'];

	
	$searchTools->updateWhereClauseForCommPrefs($comm_prefs, $clauses  ) ; 
*/
	
	/*
	
	
	
	
	*/
	
	
	$num_days_overdue = $this->_formValues['num_days_overdue'];
	
	//print "<br>Num days overdue: ".$num_days_overdue;
	if (!(is_numeric($num_days_overdue ))){
		//print "<br><br>Error: Number of Days overdue entered is not a number: ".$num_days_overdue; 
		//return ;
	
	}else{
		if(strlen($num_days_overdue) > 0){
			//print "<br>filter given for num days overdue. ";
			
			 $end_date_parm = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
      
    
    
     			//print "<br>End date: ".$end_date_parm ; 
     			if(strlen( $end_date_parm ) > 0 ){
       
     				$iyear = substr($end_date_parm, 0, 4);
     				$imonth = substr($end_date_parm , 4, 2);
     				$iday = substr($end_date_parm, 6, 2);
     				$end_date_parm = $iyear.'-'.$imonth.'-'.$iday; 
     				
     			}
     			
			if(strlen($end_date_parm) > 0 ){
      				$base_date = "'".$end_date_parm."'";
   
  			}else{
 			  	 $base_date = "now()";	
   
  			 }
  			 $tmp = "datediff($base_date ,expected_date) >= $num_days_overdue" ;
  			// print "<br><br>tmp: ".$tmp;
			$clauses[] = $tmp;
	
		}
	}


	if ( $includeContactIDs ) {
         $contactIDs = array( );
         foreach ( $this->_formValues as $id => $value ) {
             if ( $value &&
                  substr( $id, 0, CRM_Core_Form::CB_PREFIX_LEN ) == CRM_Core_Form::CB_PREFIX ) {
                 $contactIDs[] = substr( $id, CRM_Core_Form::CB_PREFIX_LEN );
             }
         }

         if ( ! empty( $contactIDs ) ) {
                $contactIDs = implode( ', ', $contactIDs );
                $clauses[] = "contact_a.id IN ( $contactIDs )";
            }
        }
        	
	
       if(count($clauses) > 0){
       		 $partial_where_clause = implode( ' AND ', $clauses );
       		 $tmp_where = "WHERE ".$partial_where_clause; 
       
       
       }else{
       	   $tmp_where = "";
       }
       
      // print "<br><br>Where: ".$tmp_where;
       return $tmp_where;
    }

    function templateFile( ) {
        return 'CRM/Contact/Form/Search/Custom.tpl';
    }

    function setDefaultValues( ) {
        return array( );
    }

    function alterRow( &$row ) {
         /*
    	$days = $row['days_overdue'];
    	if($days > 0 && $days <= 30){
    		$row['30_days'] = $row['total_amount']; 
    	}else if($days > 30 && $days <=60){
    		$row['60_days'] = $row['total_amount']; 
    	}else if($days > 60 && $days <= 90 ){
    		$row['90_days'] = $row['total_amount']; 
    	}else if($days > 90 ){
		$row['91_or_more_days'] = $row['total_amount']; 
	}  
	
	*/  	
    	//'' as 30_days, '' as 60_days, '' as 90_days, '' as 91_or_more_days
    	
    
    }
    
    function setTitle( $title ) {
        if ( $title ) {
            CRM_Utils_System::setTitle( $title );
        } else {
            CRM_Utils_System::setTitle(ts('Financial Aging'));
        }
    }
   
    /* 
     * Functions below generally don't need to be modified
     */
    function count( ) {
           $sql = $this->all( );
         //  print "<br><br>sql : ".$sql; 
           $dao = CRM_Core_DAO::executeQuery( $sql,
                                             CRM_Core_DAO::$_nullArray );
           return $dao->N;
    }
       
    function contactIDs( $offset = 0, $rowcount = 0, $sort = null,  $returnSQL = false) { 
        return $this->all( $offset, $rowcount, $sort, false, true );
    }
       
       /*
    function &columns( ) {
        return $this->_columns;
    }
 */
   

   function summary( ) {
   	// TODO: Check if user is authorized to CiviContribute
   	
   	
      // if ( is_user_authorized('access CiviContribute') == false ){
        // print "<br>Not Authorized"; 
      // 		return ; 
       
      // }
   	
   	$sum_array = array();
   	
   	$grand_totals = true; 
   	$totalSelect = $this->select($grand_totals, false);
   	$from  = $this->from();
   	$where = $this->where();
   	$group_by = "currency";
   	
   	
   	$sql = "SELECT  $totalSelect
        FROM    $from
        $where
        GROUP BY $group_by";
   	 
   //	 print "<br><br>Summary sql: ".$sql;
   	 
   	 
   	 $dao = CRM_Core_DAO::executeQuery( $sql,         CRM_Core_DAO::$_nullArray );
      
        while ( $dao->fetch( ) ) {
   	
	   	$cur_sum = array();
	   	
	   	$cur_sum['0-30 Days'] = $dao->days_30;
	   	$cur_sum['31-60 Days'] = $dao->days_60;  
	   	$cur_sum['61-90 Days'] = $dao->days_90;  
	  	$cur_sum['91 or more Days'] = $dao->days_91_or_more;  
	   	$cur_sum['Date Criteria'] = $dao->date_parm;
	   	$cur_sum['Currency'] = $dao->currency;
	   	$cur_sum['Total Amount'] = $dao->total_amount;  
	   	$cur_sum['Num. Records Combined'] = $dao->num_records;  
	   	
	   	
	   	$sum_array[] = $cur_sum;   
   	
   	}
   	$dao->free();
   	
        return $sum_array;
       
       
   }
   
   
   }
   
   
   
   ?>