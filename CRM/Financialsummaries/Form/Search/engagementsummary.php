<?php

/**
 * A custom contact search
 */
class CRM_Financialsummaries_Form_Search_engagementsummary extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;
        
    function __construct( &$formValues ) {    

        parent::__construct($formValues);  
         
        $this->_formValues = $formValues;
            
       $tmp_is_auth = user_access('access CiviContribute');
    
       if (  $tmp_is_auth  <> "1" ){
       	$tmp_columns_to_show = array( ts('You are not authorized to this area' )    		=> 'sort_name', );  
        $this->_columns = $tmp_columns_to_show; 
        	return ; 
       
       }
       
     

       if(isset($this->_formValues['layout_choice'])){
         $layout_choice =  $this->_formValues['layout_choice'];  
       }else{
       	$layout_choice = "";
       }
         if($layout_choice == 'detail'){
         $tmp_columns_to_show = array( 
                                 ts('Name')       => 'sort_name',
                                
                                
                                                     
                                 );
                                 
                                 
                                 

                         $contrib_year = $this->_formValues['contrib_year']; 
                         
                         
                        // if( $config_style == "Alumni Membership Organization"){
                           if( 1 ==0 ){	
                          	 $tmp_columns_to_show['Region'] = 'region'; 
                                 $tmp_columns_to_show['Class of'] = 'mha_class_year' ;
                                 $tmp_columns_to_show['Other Class Year'] = 'other_class_year' ;
                                 $tmp_columns_to_show['Job Title']   = 'job_title'; 
                                 $tmp_columns_to_show['Current Organization'] =	'employer_display_name';
	                         if( count($contrib_year) == 1  ) {
	                                    $tmp_columns_to_show['Giving Level']  = 'givinglevel' ;
	                 
	                         }
                        
                         }

                         
                	 $tmp_columns_to_show['Deceased']   = 'is_deceased';                                                                                                                
                         $tmp_columns_to_show['Event Participation Count'] = 'participation_count' ; 
                         $tmp_columns_to_show['Relationship Types'] = 'relationship_names' ; 
                         $tmp_columns_to_show['Memberships'] = 'membership_names'; 

               }else if( $layout_choice == 'classyear'){

                         $tmp_columns_to_show = array(        ts('Class of') => 'mha_class_year'  ) ; 
                         $tmp_columns_to_show['Event Participation Count'] =  'participation_count'; 

                }else if($layout_choice == 'region'){
                       
                      $tmp_columns_to_show = array(        ts('Region') => 'region'  ) ; 
                      $tmp_columns_to_show['Event Participation Count'] =  'participation_count'; 

               }else if($layout_choice == 'general'){
                      $tmp_columns_to_show = array(); 
                      $tmp_columns_to_show['Event Participation Count'] =  'participation_count'; 

                }


      
      
      $financial_cols =  "show_financial_types" ;
        $financial_type =  $this->getFinancialTypeChoicesFromUser(); 
        $financial_set =  $this->getFinancialSetChoicesFromUser(); 
        
        $tmp = ""; 
        if( $financial_cols == 'show_financial_types'){
/*               
  */
                $get_labels_with_ids = TRUE; 
// print "<br><br>Inside init"; 
                $ft_names = $this->getFinancialTypeLabels(  $financial_type );

                if(isset($this->_formValues['contrib_year'])){
                 	$contrib_year = $this->_formValues['contrib_year']; 
                }else{
                	$contrib_year = array();
                }
                   if(count($contrib_year) == 0){
                      $contrib_years_to_show = array( 'all' ) ;
                   }else{
                      $contrib_years_to_show = $contrib_year; 

                   }  

            foreach( $contrib_years_to_show as $cur_contrib_year){
                foreach( $financial_type as $cur){
                       $tmp_key = "line_total_".$cur."_".$cur_contrib_year ;
                       $tmp_label = $cur_contrib_year." Sum of: ".$ft_names[$cur]; 
                       $tmp_columns_to_show[$tmp_label] = $tmp_key; 

                }

            }

           

        }else if( $financial_cols == 'show_financial_sets' ){
                  // $fset_names = $this->getFinancialTypeLabels($financial_type);
                  foreach( $financial_set as $cur){
                         $tmp_key = "line_total_".$cur ;
                         // $tmp_label = "Sum of: ".$ft_names[$cur]; 
                         $tmp_label = "Sum of Fin. Set: ".$cur; 
                         $tmp_columns_to_show[$tmp_label] = $tmp_key; 

                  }

 

       }

        if($layout_choice == 'detail'){
        		$tmp_columns_to_show['Total Amount'] = 'total_amount';
        		$tmp_columns_to_show['Primary Address Location']  = 'location_type_name'; 
        		 $tmp_columns_to_show['Street Address']   = 'street_address'; 
                        $tmp_columns_to_show['City'] = 'city';
                        $tmp_columns_to_show['State'] = 'state';
                        $tmp_columns_to_show['Zip/Postal Code'] = 'zip';
                        $tmp_columns_to_show['Country'] = 'country';
                        $tmp_columns_to_show['Phone'] = 'phone' ;
                        $tmp_columns_to_show['Email'] = 'email';  

        }
       // print "<br><br>";
       // print_r($tmp_columns_to_show );
        $this->_columns = $tmp_columns_to_show;                          
    }
        
    function buildForm( &$form ) {
        /**
         * You can define a custom title for the search form
         */
        $this->setTitle('Engagement Summary');
        
    
        $config_style = "";
           
        /**
         * Define the search form fields here
         */
      
          $for_user_filter = TRUE; 

        // dropdown for regions
//        if( $config_style == "Alumni Membership Organization"){
       if( 1 == 0 ){
        $mha_region = array(
        										'1' => 'Region 1 - New England',
        										'2' => 'Region 2 - Mid Atlantic',
        										'3' => 'Region 3 - Southeast',
        										'4' => 'Region 4 - Great Lakes',
        										'5' => 'Region 5 - Twin Cities West Metro',
        										'6' => 'Region 6 - Twin Cities East Metro',
        										'7' => 'Region 7 - North Dakota, South Dakota, Outstate MN',
        										'8' => 'Region 8 - Iowa, Wisconsin',
        										'9' => 'Region 9 - South Central',
        										'10' => 'Region 10 - Mountain',
        										'11' => 'Region 11 - Northwest',
        										'12' => 'Region 12 - California',
                                                                                        '\'Unknown\'' => 'Unknown Region'
        										) ;
       // $form->addElement('select', 'mha_region', ts('Business Region'), $mha_region);   
       
         
         $form->add('select', 'mha_region', ts('Region(s)'), $mha_region, FALSE,
          array('id' => 'mha_region', 'multiple' => 'multiple', 'title' => ts('-- any --'))
        );
        
        }
        
      
        
        
         

        // filter for groups. 
          $group_ids =   CRM_Core_PseudoConstant::group();  
             $form->add('select', 'group_of_contact', ts('Contact in Group(s)'), $group_ids, FALSE,
          array('id' => 'group_of_contact', 'multiple' => 'multiple', 'title' => ts('-- any --'))
        );
        
        
  	  // filter for tags. 
          //$tag_ids =   CRM_Core_PseudoConstant::tag();  
          $tag_ids = array(); 
          $params = array(
  'version' => 3,
  'sequential' => 1,
);
$result = civicrm_api('Tag', 'get', $params);
     $tag_val = $result['values'];
     foreach($tag_val as $cur){
       $tag_ids[$cur['id']] = $cur['name'];  
     
     
     }
     
             $form->add('select', 'tag_of_contact', ts('Contact has Tag(s)'), $tag_ids, FALSE,
          array('id' => 'tag_of_contact', 'multiple' => 'multiple', 'title' => ts('-- any --'))
        );
        
       
        if( $config_style == "Alumni Membership Organization"){
	         $all_class_years =  $this->getListClassYears();  
	         $form->add('select', 'class_year', ts('Class Year(s)'), $all_class_years, FALSE,
	          array('id' => 'class_year', 'multiple' => 'multiple', 'title' => ts('-- any --'))
	        );  
        }   


// get_fiscal_year_dates
$config = CRM_Core_Config::singleton( );
   $tmp_fiscal_config=  $config->fiscalYearStart;
  
  
  $fyDate = $tmp_fiscal_config['d'];
  $fyMonth = $tmp_fiscal_config['M']; 
  $month_name =  date("F", mktime(0, 0, 0, $fyMonth, 10));;
  $formatted_fisc = $month_name." ".$fyDate; 
         $all_contrib_years = $this->getListContribYears() ;
       $form->add('select', 'contrib_year', ts('Contribution Fiscal Year(s) (starts '.$formatted_fisc.')'), $all_contrib_years, FALSE,
          array('id' => 'contrib_year', 'multiple' => 'multiple', 'title' => ts('-- any --'))
        );     

       $event_list = $this->getEventsWithParticipants(); 
       $form->add('select', 'event_choice', ts('Event(s)'), $event_list, FALSE,
          array('id' => 'event_choice', 'multiple' => 'multiple', 'title' => ts('-- any --'))
        );  
        


      $rel_set_choices = $this->getListRelationshipSets( $for_user_filter );
      if( count( $rel_set_choices ) == 0){
      	$rel_set_choices[] = "No Relationship Sets Found"; 
      
      }
       $form->add('select', 'relationship_sts', ts('Relationship Set(s)'), $rel_set_choices, FALSE,
          array('id' => 'relationship_sts', 'multiple' => 'multiple', 'title' => ts('-- any --'))
        ); 


	if( $config_style == "Alumni Membership Organization"){
      $giving_level_choices = array();
       $giving_level_choices['\'Supporting\''] = 'Supporting: 1 - 99';
       $giving_level_choices['\'Participating\''] = 'Participating: 100 - 249';
       $giving_level_choices['\'Patron\''] = 'Patron: 250 - 499';
       $giving_level_choices['\'Donor\''] = 'Donor: 500 - 999';
       $giving_level_choices['\'Benefactor\''] = 'Benefactor: 1,000 - 2,499';
       $giving_level_choices['\'Minnesota Way\''] = 'Minnesota Way: 2,500 - 4,999';
       $giving_level_choices['\'Sustaining\''] = 'Sustaining: 5,000 or more';
   
    
   
        $form->add('select', 'giving_level', ts('Annual Giving Level(s)'), $giving_level_choices, FALSE,
          array('id' => 'giving_level', 'multiple' => 'multiple', 'title' => ts('-- any --'))
        );  
        }
        
        $ft_choices = $this->getListFinancialTypes($for_user_filter);
        $form->add('select', 'pog_financial_type', ts('Financial Type(s)'), $ft_choices, FALSE,
          array('id' => 'pog_financial_type', 'multiple' => 'multiple', 'title' => ts('-- any --'))
        ); 
  
        $fs_choices = $this->getListFinancialSets($for_user_filter);
        $form->add('select', 'financial_set', ts('Financial Set(s)'), $fs_choices, FALSE,
          array('id' => 'financial_set', 'multiple' => 'multiple', 'title' => ts('-- any --'))
        );

       $f_column_choice = array();
    //   $f_column_choice[''] = ' -- select -- ';
       $f_column_choice['show_financial_types'] = 'Show Financial Type Columns';
 //      $f_column_choice['show_financial_sets'] = 'Show Financial Set Columns'; 
       $form->addElement('select', 'financial_cols', ts('Which financial columns to show'), $f_column_choice); 


 
       $layout_choice_options = array();
       $layout_choice_options['detail'] = 'Donor Detail'; 
      // TODO: Only include next 2 options if this install is an Alumni association
      // $layout_choice_options['classyear'] = 'Class Year Summary';
      // $layout_choice_options['region'] = 'Region Summary';
       $layout_choice_options['general'] = 'General Summary' ;
       
       $form->addElement('select', 'layout_choice', ts('Layout Choice'), $layout_choice_options);    

        $donor_type_options = array();
        $donor_type_options['onlydonors'] ='Only Donors';
        
         $donor_type_options['onlynondonors'] ='Only Non-Donors';
          $donor_type_options['anyone'] ='Donors and Non-Donors';
         $form->addElement('select', 'donor_types', ts('Which Contacts to Show'),  $donor_type_options); 


           
            
        /**
         * If you are using the sample template, this array tells the template fields to render
         * for the search form.
         */
      if( $config_style == "Alumni Membership Organization"){

	     $form->assign( 'elements', array('group_of_contact' , 'tag_of_contact',  'class_year', 'mha_region' , 'pog_financial_type',
	 'financial_set', 'contrib_year', 'relationship_sts',  'event_choice' , 'donor_types',   'layout_choice' ) );
    }else{
    
	    $form->assign( 'elements', array('group_of_contact' , 'tag_of_contact' , 'pog_financial_type',
	 'financial_set', 'contrib_year', 'relationship_sts',  'event_choice' ,  'donor_types',   'layout_choice' ) );
    }
    
    
    }
    
    function getGrandTotalSQL(){
    
    
    	 // get total of all contrib. amount columns
     		 $financial_type =  $this->getFinancialTypeChoicesFromUser(); 

 		$contrib_year = $this->_formValues['contrib_year']; 
                   if(count($contrib_year) == 0){
                      $contrib_years_to_show = array( 'all' ) ;
                   }else{
                      $contrib_years_to_show = $contrib_year; 

                   }  
                   
                   
    	$tmp_total = ""; 
     		 foreach( $contrib_years_to_show as $cur_contrib_year){
                foreach( $financial_type as $cur){
                       $tmp_key = "ifnull( t_".$cur."_".$cur_contrib_year.".sum_line_total , 0 ) " ;  // t_6_2013.sum_line_total
                       if( strlen($tmp_total ) > 0 ){
                       		$tmp_total = $tmp_total." + ".$tmp_key; 
                       }else{
                       		$tmp_total = $tmp_key;
                       }

                }

            }
            
    	return $tmp_total; 
    
    }


    /*
     * Set search form field defaults here.
     */
    function setDefaultValues( ) {
        // Setting default search state to California
        //return array( 'state_province_id' => 1004, );
    }
        
    /**
     * Define the smarty template used to layout the search form and results listings.
     */
    function templateFile( ) {
       
       return 'CRM/Contact/Form/Search/Custom/Sample.tpl';
    
    }
        
    /**
     * Construct the search query
     */       
    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = FALSE,  $onlyIDs = FALSE ) {
            $tmp_is_auth = user_access('access CiviContribute');
            if (  $tmp_is_auth  <> "1" ){
            
               return "Select 'not authorized ' as sort_name  FROM civicrm_contact LIMIT 1 ";
            
            
            
            
             }
    //  function all( $offset = 0, $rowcount = 0, $sort = null,
    //              $includeContactIDs = FALSE, $onlyIDs = FALSE ) {
    
    
    
             $config_style = "";
             $total_where = "";
                        
         $layout_choice =  $this->_formValues['layout_choice'];  

         $region_column = $this->getRegionAsColumn( );

         if(isset($this->_formValues['giving_level'] )){
         	$giving_level_choice =  $this->_formValues['giving_level'];
         }else{
         	$giving_level_choice = "";
         }
         
         $contrib_year =  $this->_formValues['contrib_year'];

         if( count($contrib_year)  == 1){
              // $givinglevel_column = $this->getGivingLevelField( );

             $givinglevel_column = " ta1.givinglevel_name ";
         }else{
               $givinglevel_column = " '' ";
         }
            
            
         // $financial_cols =  $this->_formValues['financial_cols']; 
            $financial_cols =  "show_financial_types" ;
          
          
          /*   'financial_cols' */
          
        $financial_type =  $this->getFinancialTypeChoicesFromUser(); 
        $financial_set =  $this->getFinancialSetChoicesFromUser();
          
        // SELECT clause must include contact_id as an alias for civicrm_contact.id
        if ( $onlyIDs ) {
                 if($layout_choice == 'detail'){
        	$select = "contact_a.id as contact_id";
                  }
        
        }else{
            
            
        if($layout_choice == 'detail'){

     		 if( $config_style == "Alumni Membership Organization"){
     		 	$alumni_fields = " programinfo.class_of__mha__44 as mha_class_year , 
     		 	 if( programinfo.class_of__other__45 = 0, '', programinfo.class_of__other__45) as other_class_year,
     		 	  ".$region_column." as region, ".$givinglevel_column." as givinglevel , 
     		 	  contact_a.job_title as job_title ,
     		 	  employer.display_name as employer_display_name , " ; 
     		 }else{
     		 	$alumni_fields = ""; 
     		 
     		 
     		 }
     		 
     		
     		 $tmp_total = self::getGrandTotalSQL(); 
     		 
     		  $donor_types_choice =  $this->_formValues['donor_types']; 
     		  $total_where = ""; 
     		  if( $donor_types_choice == "anyone"){
     		  	 $total_where = ""; 
     		  }else if( $donor_types_choice == 'onlynondonors' ){
     		  	$total_where = " AND $tmp_total = 0 " ; 
     		  
     		  }else{
     			 $total_where = " AND $tmp_total > 0 " ; 
     			}
            
             $select  = " contact_a.id as contact_id,
                      contact_a.sort_name as sort_name,
                      if( contact_a.is_deceased = 0 , '', 'deceased') as is_deceased,  
                      phone.phone as phone, email.email as email, address.street_address as street_address,
                       address.city as city, 
                      state_province.abbreviation as state, address.postal_code as zip, 
                      loc.display_name as location_type_name,  
                      country.name as country, format( ceil( ".$tmp_total." ), 0)  as total_amount, 
                       count( distinct participant.id ) as participation_count,
                      ".$alumni_fields." 
                      group_concat( distinct rt.label_a_b ) as relationship_names ,  group_concat( distinct mt.name) as membership_names
                      ";

        }else if($layout_choice == 'classyear'){
              $select  = " programinfo.class_of__mha__44 as mha_class_year , count( distinct participant.id ) as participation_count  ";
  
        }else if( $layout_choice == 'region' ) {
                
              $select  =  $region_column." as region  , count( distinct participant.id ) as participation_count  ";  
        
       }else if($layout_choice == 'general'){
                $select  =      " count( distinct participant.id ) as participation_count "; 
                
       }else{
              //print "<br> This layout choice is not recognized: ".$layout_choice ;
              CRM_Core_Error::debug("Error: This layout choice is not recognized: ".$layout_choice); 

       }
       
       $financial_type = $this->_formValues['pog_financial_type']; 
          $financial_set = $this->_formValues['financial_set']; ; 
          if( count( $financial_type) == 0 && count( $financial_set) == 0 ) {
          
          	 $financial_type = $this->getListFinancialTypes(); 
          	//CRM_Core_Error::debug("Error: You need to choose a financial type or a financial set." ); 
          	//$sql = "Select 'You need to choose a financial type or a financial set. ' as sort_name  FROM civicrm_contact LIMIT 1 ";
          	//return $sql ; 
          
          } 

        // Add fields for each financial type, this is needed for all summary options. 
        $tmp = ""; 
        if( $financial_cols == 'show_financial_types'){

               $contrib_year = $this->_formValues['contrib_year']; 
                   if(count($contrib_year) == 0){
                      $contrib_years_to_show = array( 'all' ) ;
                   }else{
                      $contrib_years_to_show = $contrib_year; 

                   }  

            foreach( $contrib_years_to_show as $cur_contrib_year){
                  foreach( $financial_type as $cur){
                         $tmp = $tmp." format( ceil( t_".$cur."_".$cur_contrib_year.".sum_line_total ), 0) as line_total_".$cur."_".$cur_contrib_year." , "; 

                  }

            }

     

        }else if( $financial_cols == 'show_financial_sets' ){
               foreach( $financial_set as $cur){
                         $tmp = $tmp." format( ceil( t_".$cur.".sum_line_total ),0) as line_total_".$cur." , "; 

                 }


       }else{
            CRM_Core_Error::debug("Error: Unrecognized choice for financial cols: ".$financial_cols);
       }

        $select = $tmp.$select;


        

       
        
       }
        // all done with select clause
  
        $from  = $this->from( );
            

        $outer_where = true; 
        $where = $this->where( $includeContactIDs , $outer_where);
            
        
        
       // if ( !( $onlyIDs) ) {
          $having = $this->having( );
       	 if ( $having ) {
            $having = " HAVING $having ";
          }

        // Define GROUP BY here if needed.
       
       if($layout_choice == 'detail'){
           $grouping =  " GROUP BY contact_a.id  ";

       }else if($layout_choice == 'classyear'){
           $grouping = " GROUP BY mha_class_year "; 
       }else if( $layout_choice == 'region' ){
           $grouping = " GROUP BY ".$region_column." "; 

       }else if( $layout_choice == 'general' ){
            $grouping = " GROUP BY contact_a.contact_type "; 
       }
        
        
       // }
            
        $sql = "SELECT $select
            FROM  $from
            WHERE $where ".$total_where."
            $grouping
            $having
            ";
        // Define ORDER BY for query in $sort, with default value
         if ( !$onlyIDs ) {
           
        if ( ! empty( $sort ) ) {
            if ( is_string( $sort ) ) {
                $sql .= " ORDER BY $sort ";
            } else {
                $sql .= " ORDER BY " . trim( $sort->orderBy() );
            }
        } else {

            if( $layout_choice == 'detail'){
               $sql .= "ORDER BY contact_a.sort_name asc";

           }else if($layout_choice == 'classyear'){
               $sql .= "ORDER BY mha_class_year desc";
           }else if( $layout_choice == 'region'  ){
               $sql .= "ORDER BY region asc";

           }
        }
        
        }


        if ( $rowcount > 0 && $offset >= 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }
        
       if( $onlyIDs ){
           //print "<br><br>Only ids SQL: ".$sql; 
           
        }else{
          //	print "<br><br><br> sql:".$sql; 
        
        }
      return $sql;
    }
     
     
    function get_fiscal_year_dates($year_parm){
 	$config = CRM_Core_Config::singleton( );
 	$format = 'Y-m-d'; 
 	$final_dates = array();

         $tmp_year = $year_parm - 1; 
	// get_fiscal_year_dates
   $tmp_fiscal_config=  $config->fiscalYearStart;
  
  
  $fyDate = $tmp_fiscal_config[d];
  $fyMonth = $tmp_fiscal_config[M]; 
  
  //$fyYear  = CRM_Utils_Date::calculateFiscalYear( $fyDate, $fyMonth  );
  
  $tmpFirstDay_ts = mktime(1,1,1,$fyMonth,$fyDate, $tmp_year ) ;
  
  $tmpFirstDay =  date("Y-m-d", $tmpFirstDay_ts);
  $tmpFirstDay_date = date("Y-m-d", strtotime($tmpFirstDay));
  $final_dates['start'] = $tmpFirstDay_date; 
  
  $tmpNextYearFirstDay_ts = strtotime( $tmpFirstDay_date . "+1 year");
   $tmpNextYearFirstDay_date = date("Y-m-d", strtotime($tmpFirstDay)); 
  
  $tmp_start_plusOne_year_ts = strtotime(  $tmpNextYearFirstDay_date  . "+1 year" );
  
  // $tmp_last_day_ts = strtotime(  $tmpNextYearFirstDay_date  . "-1 day");
  
 // echo "After subtracting one day: ".date('l dS \o\f F Y',  $tmp_last_day_ts )."<br>";
  
  
  $final_dates['end']  = date($format,   $tmp_start_plusOne_year_ts ); 
   
  return  $final_dates;
 }  
        
    function from( ) {

     // TODO: IF this is an alumni association, add SQL for alumni-specific tables such as class year 
    	$config_style = "";
    	$cur = "";
    	$tmp_giving_level = ""; 
       $contrib_year = $this->_formValues['contrib_year']; 
       $year_list = implode( ", " , $contrib_year);
       




       // $financial_cols =  $this->_formValues['financial_cols']; 
       
       $financial_cols =  "show_financial_types" ;
        $financial_type =  $this->getFinancialTypeChoicesFromUser(); 
        $financial_set =  $this->getFinancialSetChoicesFromUser(); 
        
        if( $financial_cols == 'show_financial_types' ||  $financial_cols == 'show_financial_sets' ){
            

                 // Determine giving_level, such as Patron, Supporter, etc. Only done when single year selected. 
                          // This is limited to the financial type named 'Annual Giving Contribution'  
                        if( $config_style == "Alumni Membership Organization"){
	                        if( count($contrib_year) == 1 ) {
	                               // TODO: get event income.
	
	                             $tmp_gl_name = $this->getGivingLevelField( "ifnull( ceil(sum(contrib.total_amount)), 0 ) "); 
	                              
	                           $tmp_giving_level = " LEFT JOIN ( 
	                               select contrib.contact_id , ifnull( ceil(sum(contrib.total_amount)), 0 )  as givinglevel_amount ,
	                               ".$tmp_gl_name." as givinglevel_name 
	                               FROM 
	                               civicrm_contribution contrib LEFT join civicrm_line_item li ON contrib.id = li.entity_id AND li.entity_table = 'civicrm_contribution'
	                               LEFT join civicrm_financial_type ft ON li.financial_type_id = ft.id 
	                               WHERE  
	                                ft.name = 'Annual Giving Contribution' 
	                                AND contrib.is_test <> 1
	                               AND contrib.contribution_status_id = 1 ".$year_filter."
	                               GROUP BY contrib.contact_id 
	                              ) as ta1 ON ta1.contact_id = contact_a.id "; 
	                         }
                         }

                  $tmp_where = $this->where();  
                   $layout_choice =  $this->_formValues['layout_choice'];  
                  if( $layout_choice == 'detail'){
                       $tmp_group_by = " group by contact_a.id ";
                       $select_inner = " contact_a.id as contact_id , sum(li.line_total) as sum_line_total "; 
                     
                       $select_for_union = " contact_id , sum( sum_line_total) as sum_line_total "; 
                       $groupby_for_union = " group by contact_id ";
                       $on_left_field = " contact_a.id ";
                       $on_right_field = ".contact_id "; 

                          
 
                  }else if( $layout_choice == 'classyear'){
                     $tmp_group_by = " group by mha_class_year ";
                      $select_inner = " programinfo.class_of__mha__44 as mha_class_year , sum(li.line_total) as sum_line_total ";       

                     $select_for_union = "  mha_class_year , sum( sum_line_total) as sum_line_total "; 
                     $groupby_for_union = " group by mha_class_year ";
                     $on_left_field = " programinfo.class_of__mha__44 ";
                     $on_right_field = ".mha_class_year "; 
                  }else if( $layout_choice == 'region' ){
                       $region_column = $this->getRegionAsColumn( );

                       $tmp_group_by = " group by region ";
                      $select_inner = $region_column ." as region , sum(li.line_total) as sum_line_total "; 

                     $select_for_union = "  region , sum( sum_line_total) as sum_line_total "; 
                     $groupby_for_union = " group by region ";
                     $on_left_field =  "  programinfo.primary_region_52 " ; //  " ".$region_column." ";
                     $on_right_field = ".region ";


                  }else if( $layout_choice == 'general' ){
                       

                       $tmp_group_by = "  ";
                      $select_inner = " contact_a.contact_type as general,  sum(li.line_total) as sum_line_total "; 

                     $select_for_union = " general as general,   sum( sum_line_total) as sum_line_total "; 
                     $groupby_for_union = " group by general ";
                     $on_left_field =  "  contact_a.contact_type " ; //  " ".$region_column." ";
                     $on_right_field = ".general ";


                  }
    

                  $financial_cols_array = array(); 

                  if( $financial_cols == 'show_financial_types' ){
                         $financial_cols_array  = $financial_type; 

                         $financial_filter = " and li.financial_type_id = ".$cur." " ; 
                  }else if(  $financial_cols == 'show_financial_sets' ){
                         $financial_cols_array  = $financial_set; 

                  }
                   $contrib_year = $this->_formValues['contrib_year']; 
                   if(count($contrib_year) == 0){
                      $contrib_years_to_show = array( 'all' ) ;
                   }else{
                      $contrib_years_to_show = $contrib_year; 

                   } 
                   
             if( $config_style == "Alumni Membership Organization"){
				$alumni_sql = " LEFT JOIN civicrm_value_lastname_while_in_program_1 programinfo ON programinfo.entity_id = contact_a.id ";
			
		}else{
		
			$alumni_sql = ""; 
		
		}
			
              foreach(  $contrib_years_to_show as $cur_contrib_year ){   
              	$tmp_join = "";
                  foreach( $financial_cols_array as $cur){

                       if( $financial_cols == 'show_financial_types' ){
                           $financial_filter = " and li.financial_type_id = ".$cur." " ; 
                       }else if(  $financial_cols == 'show_financial_sets' ){

                           $cur_set_ids = $this->getFinancialTypeListFromSets( $cur);
                           $financial_filter = " and li.financial_type_id IN ( ".$cur_set_ids." ) " ; 

                       }

                       $contrib_year = $this->_formValues['contrib_year']; 
                      //$year_list = implode( ", " , $contrib_year);
       
                      if( count($contrib_year) > 0 ) {
                          $tmp =  $this->get_fiscal_year_dates( $cur_contrib_year );    
                          $year_filter = " AND date(contrib.receive_date) >= '".$tmp['start']."' AND  date(contrib.receive_date) < '".$tmp['end']."'  " ;
                          //print "<br>year filter: ".$year_filter;
                          //$year_filter = " AND year(contrib.receive_date) IN ( ".$cur_contrib_year." ) " ; 
                     }else{
                           $year_filter = ""; 
                             }
			
			
                       $sql_part_a = " Select ".$select_inner." FROM
                                                     civicrm_contact contact_a ".$alumni_sql."
                                                     Left join civicrm_contribution contrib ON contact_a.id = contrib.contact_id AND contrib.is_test <> 1
                                                         AND contrib.contribution_status_id = 1 ".$year_filter."
                                                     JOIN civicrm_line_item li ON li.entity_id = contrib.id 
                                                     AND li.entity_table = 'civicrm_contribution' ".$financial_filter. 
                                                     $tmp_giving_level." WHERE ".$tmp_where."
                                                     ".$tmp_group_by ; 


                    $sql_part_b = "Select ".$select_inner." FROM
	                                             civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id 
                                                     AND li.entity_table =   'civicrm_participant' ".$financial_filter."
	                                             JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id
				                     join civicrm_contribution contrib ON  ep.contribution_id = contrib.id AND contrib.is_test <> 1
                                                         AND contrib.contribution_status_id = 1 ".$year_filter."
                                                     JOIN civicrm_contact contact_a ON contact_a.id = contrib.contact_id ".$alumni_sql." 
                                                     ".$tmp_giving_level." WHERE ".$tmp_where."
                                                     ".$tmp_group_by;

  //print "<br><br>sql a: ".$sql_part_a ; 
  // print "<br><br>sql b: ".$sql_part_b ; 


                       $tmp_join = $tmp_join."  LEFT JOIN ( SELECT $select_for_union FROM (
                                                     ( ".$sql_part_a." ) 
                                                      UNION ALL 
                                                       ( ".$sql_part_b." ) 
                                                      ) tmp_a ".$groupby_for_union."  ) as t_".$cur."_".$cur_contrib_year." ON ".$on_left_field." = t_".$cur."_".$cur_contrib_year.$on_right_field; 
                  }

           }


         // print "<Br><br><br>SQL: ".$tmp_join; 

            
            

        }


        if( $layout_choice == 'detail' ){
        	$tmp_relationship_from = " LEFT JOIN civicrm_relationship r ON contact_a.id = r.contact_id_a AND r.is_active <> 0 
        	                   	   LefT JOIN civicrm_relationship_type rt ON r.relationship_type_id = rt.id 
        	                   	   	AND rt.is_active <> 0 AND label_a_b like '%---%'   ";  
        	                   	   	
        	                   	   	
        	$tmp_membership_from = " LEFT JOIN civicrm_membership membership ON contact_a.id = membership.contact_id AND membership.is_test <> 1 
        				 LEFT JOIN civicrm_membership_type mt ON membership.membership_type_id = mt.id AND mt.is_active <> 0 
        				 LEFT JOIN civicrm_membership_status ms ON membership.status_id = ms.id   ";                 	   	
        
        
        
        }

        // Primary FROM to return, put everything together. 
        $tmp_from =  " civicrm_contact contact_a ".$alumni_sql."
                ".$tmp_join."
                LEFT JOIN civicrm_address address ON (address.contact_id = contact_a.id AND address.is_primary = 1)  
                LEFT JOIN civicrm_location_type loc ON address.location_type_id = loc.id
                LEFT JOIN civicrm_state_province state_province ON state_province.id = address.state_province_id   
                LEFT JOIN civicrm_county county ON address.county_id = county.id
                LEFT JOIN civicrm_country country ON country.id = address.country_id 
                LEFT JOIN civicrm_phone phone ON contact_a.id = phone.contact_id AND phone.is_primary = 1
                LEFT JOIN civicrm_email email ON contact_a.id = email.contact_id AND email.is_primary = 1 
                LEFT JOIN civicrm_contact employer ON contact_a.employer_id = employer.id AND employer.is_deleted <> 1
                ".$tmp_relationship_from.$tmp_membership_from."
                LEFT JOIN civicrm_participant participant ON contact_a.id = participant.contact_id AND participant.is_test <> 1
                LEFT JOIN civicrm_participant_status_type pstatus ON participant.status_id = pstatus.id  AND pstatus.is_counted = 1 ".$tmp_giving_level
                ;

       return $tmp_from;


            
    }
    

     function getRegionAsColumn( ){
               $tmp_col = " programinfo.primary_region_52 "    ;     
               return $tmp_col;                 

    }
    
    function verifyGroupCacheTable($groupIDs_raw){
	
		
	
		 $groupIDs = implode( ", ", $groupIDs_raw);
		 
		 if(strlen($groupIDs) == 0 ){
		 	return ; 
		 
		 }
		 $sql = "
		SELECT id, cache_date, saved_search_id, children
		FROM   civicrm_group
		WHERE  id IN ( $groupIDs )
		  AND  ( saved_search_id != 0
		   OR    saved_search_id IS NOT NULL
		   OR    children IS NOT NULL )
		";
		    $dao = CRM_Core_DAO::executeQuery($sql);
		    $ssWhere = array();
		    while ($dao->fetch()) {
			      if ($tableAlias == NULL) {
			        $alias = "`civicrm_group_contact_cache_{$group->id}`";
			      }
			      else {
			        $alias = $tableAlias;
			      }
			
			      $this->_useDistinct = TRUE;
			
			// Make sure cache table is populated. 
			      if (!$this->_smartGroupCache || $dao->cache_date == NULL) {
			        CRM_Contact_BAO_GroupContactCache::load($dao);
			      }
			
			
			}
			
			$dao->free(); 

	}
	
    
    function updateWhereClauseForGroupsChosen(&$groups_of_contact,  &$contact_field_name, &$clauses ){
		if(count( $groups_of_contact ) > 0 ){
   // f1.contact_id
  		//print "<br><br><h2>Need do deal with where clause for groups filters.</h2>"; 
	  		$tmp_sql_list = implode(", ", $groups_of_contact);
	  		
	  		$clauses[] = " (  ( ".$contact_field_name." IN ( SELECT groups.contact_id as contact_id 
	  							FROM civicrm_group_contact groups WHERE groups.group_id 
	  							IN (".$tmp_sql_list.") AND groups.status = 'Added') )
	  				      OR 
	  				 (  ".$contact_field_name." IN (
	  							SELECT groups.contact_id as contact_id 
	  							FROM civicrm_group_contact_cache groups WHERE groups.group_id 
	  							IN (".$tmp_sql_list.") 
	  						) )  )";  												
  		}
	
	
	}
	
	function getFinancialTypeChoicesFromUser(){
		
		if(isset($this->_formValues['pog_financial_type'])){
	    	$tmp =  $this->_formValues['pog_financial_type'];
		}else{
			$tmp = array();
		}
	    if( count( $tmp) == 0){
               // If the user didn't choose any financial types, then get everything. 
               $tmp = $this->getListFinancialTypes();
            }
	    
	    return $tmp; 
	
	}


       function getFinancialSetChoicesFromUser(){
       	if(isset($this->_formValues['financial_set'])){
	    	$tmp =  $this->_formValues['financial_set'];
       	}else{
       		$tmp = array();
       	}
	    if( count( $tmp) == 0){
               // If the user didn't choose any financial sets, then get everything. 
               $tmp = $this->getListFinancialSets();
            }
	    
	    return $tmp; 
	
	}
	
        
    /*
     * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
     *  ($includeContactIDs = false)
     */
    function where( $includeContactIDs = false , $primary_outer_where = false ) {
      
 
      
        $clauses = array( );

       $clauses[] = " ( contact_a.contact_type = 'Individual' ) ";  // Is this needed?
       $clauses[] = " ( contact_a.is_deleted <> 1 ) "; 
      
    
       

        // These are conditional filters based on user input_filters_list()
        
     
        $group_of_contact = $this->_formValues['group_of_contact'];
	
	//require_once('utils/CustomSearchTools.php');
	//$searchTools = new CustomSearchTools();
	$this->verifyGroupCacheTable($group_of_contact ) ;
	$contact_field_name = "contact_a.id"; 	
	$this->updateWhereClauseForGroupsChosen($group_of_contact, $contact_field_name, $clauses );
	
	
	// deal with tags
	 $tag_of_contact = $this->_formValues['tag_of_contact'];
	if( count($tag_of_contact) > 0){
       		$tmp_sql_list = implode(", ", $tag_of_contact);
	  		
	  	$clauses[] = " (  ( ".$contact_field_name." IN ( SELECT tags.entity_id as contact_id 
	  							FROM civicrm_entity_tag tags WHERE 
	  							tags.entity_table = 'civicrm_contact' 
	  							AND tags.tag_id 
	  							IN (".$tmp_sql_list.") ) ) ) ";  							
	 }
         // done with tags 							
	  							       
        
        // primary address region
       // $mha_region = CRM_Utils_Array::value( 'mha_region', $this->_formValues );
        if(isset($this->_formValues['mha_region'])){
         	$mha_region  =  $this->_formValues['mha_region']; 
        }else{
        	$mha_region = array();
        }
        if( count($mha_region) > 0  ) {
                    $tmp_regions_str = implode(" , ", $mha_region); 
                    $tmp_region_field = $this->getRegionAsColumn( );
                    
                    $clauses[]  = " ( $tmp_region_field IN ( ".$tmp_regions_str." ) ) ";       
            
        }

         // Filter on university class year if needed. 
        if( isset($this->_formValues['class_year'])){
         	$class_year  =  $this->_formValues['class_year']; 
        }else{
        	$class_year  = array();
        }
         if( count($class_year) > 0  ) {
             $years_list = implode( ", " , $class_year);
             $clauses[] = " ( programinfo.class_of__mha__44 IN (".$years_list.") ) ";

         }

        // filter on contrib. year 
       $contrib_year = $this->_formValues['contrib_year']; 
       $year_filter_tmp = ""; 
       if( count($contrib_year) > 0  ) {
           
           
             $years_list = implode( ", " , $contrib_year);
             
            
             foreach($contrib_year as $curyear){
              
             	  $tmp =  $this->get_fiscal_year_dates( $curyear ); 
             	  if( strlen(  $year_filter_tmp) > 0 ){
             	  	$year_filter_tmp = $year_filter_tmp." OR "; 
             	  
             	  }
                  $year_filter_tmp = $year_filter_tmp." ( date(contrib.receive_date) >= '".$tmp['start']."' AND  date(contrib.receive_date) < '".$tmp['end']."' )  " ;
             
             }
             
             // print "<br> new style year filter: ".$year_filter_tmp;
                     
             
             //$year_filter_tmp = " year(contrib.receive_date) IN ( ".$years_list." )"; 
             // print "<br> old style year filter: ".$year_filter_tmp;
            
             $clauses[] = " (  contact_a.id IN ( SELECT distinct contrib.contact_id as contact_id 
	  							FROM civicrm_contribution contrib WHERE 
	  							(  ".$year_filter_tmp."  )
	  							AND contrib.is_test <> 1
                                                                AND contrib.contribution_status_id = 1   )  ) "; 

         }
         
        

          // filter on giving levels, only valid if the user selected a single year.
       $contrib_year = $this->_formValues['contrib_year']; 
       if(isset($this->_formValues['giving_level'])){
  	     $giving_level = $this->_formValues['giving_level']; 
       }else{
       	$giving_level = array();
       }
        
       if(  count($contrib_year) <> 1  && count($giving_level ) > 0) {

          // print "<br><br>Error with Filters: When using 'Giving_level', you can only choose a single year ";
           CRM_Core_Error::debug("Error with Filters: When using 'Giving_level', you can only choose a single year ");
          // exit(); 

       }else{
            if( count( $giving_level) > 0  ){  

                 $givinglevel_list = implode( ", ", $giving_level); 
                 $tmp_gl_name = $this->getGivingLevelField( "ifnull( ceil(sum(contrib.total_amount)), 0 ) "); 
$years_list = implode( ", " , $contrib_year);

                 $clauses[] = " (  contact_a.id IN ( select distinct contrib.contact_id   
                               FROM 
                               civicrm_contribution contrib LEFT join civicrm_line_item li ON contrib.id = li.entity_id AND li.entity_table = 'civicrm_contribution'
                               LEFT join civicrm_financial_type ft ON li.financial_type_id = ft.id 
                               WHERE  
                                ft.name = 'Annual Giving Contribution' 
                                AND contrib.is_test <> 1
                               AND contrib.contribution_status_id = 1 
                               AND (".$year_filter_tmp.")
                               AND ".$tmp_gl_name." IN ( ".$givinglevel_list." )
                                   )  ) ";

                 // $clauses[] = " ( ta1.givinglevel_name IN ( ".$givinglevel_list." ) ) ";
                  /*
                  // Determine giving_level, such as Patron, Supporter, etc. Only done when single year selected. 
                          // This is limited to the financial type named 'Annual Giving Contribution'  
                        if( count($contrib_year) == 1 ) {
                               // TODO: get event income.

                             $tmp_gl_name = $this->getGivingLevelField( "ifnull( ceil(sum(contrib.total_amount)), 0 ) "); 
                              
                           $tmp_giving_level = " LEFT JOIN ( 
                               select contrib.contact_id , ifnull( ceil(sum(contrib.total_amount)), 0 )  as givinglevel_amount ,
                               ".$tmp_gl_name." as givinglevel_name 
                               FROM 
                               civicrm_contribution contrib LEFT join civicrm_line_item li ON contrib.id = li.entity_id AND li.entity_table = 'civicrm_contribution'
                               LEFT join civicrm_financial_type ft ON li.financial_type_id = ft.id 
                               WHERE  
                                ft.name = 'Annual Giving Contribution' 
                                AND contrib.is_test <> 1
                               AND contrib.contribution_status_id = 1 ".$year_filter."
                               GROUP BY contrib.contact_id 
                              ) as ta1 ON ta1.contact_id = contact_a.id "; 
                         }
                 

                    */
 
                 
                  
            } 

       }
                                
        // filter on financial types
      // $financial_type = $this->_formValues['pog_financial_type']; 
      $financial_type = $this->getFinancialTypeChoicesFromUser() ;
       if( count( $financial_type) > 0  ) {
             $fin_types_list = implode( ", " ,  $financial_type);
             $clauses[] = " (  contact_a.id IN ( SELECT DISTINCT contrib.contact_id as contact_id FROM 
                               civicrm_line_item li join civicrm_contribution contrib ON li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'  
                               WHERE li.financial_type_id IN (".$fin_types_list.") )
                              OR
                               contact_a.id IN ( SELECT DISTINCT contrib.contact_id as contact_id FROM
                               civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant' 
	                        JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id
				join civicrm_contribution contrib ON  ep.contribution_id = contrib.id  
                               WHERE li.financial_type_id IN (".$fin_types_list.") )      ) 
                                 ";

            // print_r( $clauses); 
               

         }
         
         // filter on financial sets
          $financial_set = $this->_formValues['financial_set']; ; 
          if( count( $financial_set) > 0  ) {
          	 $fin_set_prefix_ids = implode( ", ",  $financial_set ) ;
        	$tmp_ids = $this->getFinancialTypeListFromSets( $fin_set_prefix_ids);
        	//  $clauses[] = "  li.financial_type_id IN (".$tmp_ids.")  ";

                  $clauses[] = " (  contact_a.id IN ( SELECT DISTINCT contrib.contact_id as contact_id FROM 
                               civicrm_line_item li join civicrm_contribution contrib ON li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'  
                               WHERE li.financial_type_id IN (".$tmp_ids.") )
                              OR
                               contact_a.id IN ( SELECT DISTINCT contrib.contact_id as contact_id FROM
                               civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant' 
	                        JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id
				join civicrm_contribution contrib ON  ep.contribution_id = contrib.id  
                               WHERE li.financial_type_id IN (".$tmp_ids.") )      ) 
                                 ";
        	
            // print_r( $clauses ) ;
         }
          
          
          
          // filter on relationship sets
          $rel_choice  = $this->_formValues['relationship_sts'];
          if( count( $rel_choice) > 0 ){
          	 $tmp_set_list = implode(", ", $rel_choice);
	  		
                $tmp_sql_list = $this->getRelationshipTypeListFromSets( $tmp_set_list ); 

	  	//print "<br>sql snippet: ".$tmp_sql_list;	
	  	$tmp_rel_clause = " (  ( contact_a.id IN ( SELECT r.contact_id_a
	  							FROM civicrm_relationship r
                                                                WHERE 
	  							r.is_active = 1
	  							AND r.relationship_type_id 
	  							IN (".$tmp_sql_list.") ) ) ) ";


              $clauses[] = $tmp_rel_clause; 
          
          
          } 
          
            
        // filter on event participants
         $event_choice = $this->_formValues['event_choice'];
         if( count(  $event_choice ) > 0  ) {

               $tmp_sql_list = implode(", ", $event_choice);
	  		
	  	$tmp_event_clause = " (  ( contact_a.id IN ( SELECT p.contact_id  
	  							FROM civicrm_participant p 
                                                                join civicrm_participant_status_type st ON p.status_id = st.id
                                                                WHERE 
	  							p.is_test <> 1 
                                                                AND st.is_counted = 1
	  							AND p.event_id 
	  							IN (".$tmp_sql_list.") ) ) ) ";


              $clauses[] = $tmp_event_clause; 


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
            
            
        
        
            
        return implode( ' AND ', $clauses );
    }

    function having( $includeContactIDs = false ) {
        $clauses = array( );
        return "";
    }

   function getGivingLevelField( $parm = "ta1.givinglevel_amount"  ){


        $tmp =    " CASE  
                      WHEN $parm = 0 THEN ''
                      WHEN ceil($parm) > 0 AND ceil($parm) <= 99  THEN 'Supporting'
                      WHEN ceil($parm) >= 100 AND ceil($parm) <= 249  THEN 'Participating'
                      WHEN ceil($parm) >= 250 AND ceil($parm) <= 499  THEN 'Patron'
                      WHEN ceil($parm) >= 500 AND ceil($parm) <= 999  THEN 'Donor'
                      WHEN ceil($parm) >= 1000 AND ceil($parm) <= 2499  THEN 'Benefactor'
                      WHEN ceil($parm) >= 2500 AND ceil($parm) <= 4999  THEN 'Minnesota Way'
                      WHEN ceil($parm) >= 5000 THEN 'Sustaining'
                      ELSE 'Unknown' 
                    END ";


        return $tmp; 
   }

   function getEventsWithParticipants( ){


      $events_list = array();
      $sql = "SELECT e.id as event_id , e.title, e.start_date, count( distinct p.contact_id) as part_count
              FROM civicrm_participant p join civicrm_event e ON p.event_id = e.id AND p.is_test <> 1
              join civicrm_participant_status_type st ON p.status_id = st.id AND st.is_counted = 1
              join civicrm_contact contact ON contact.id = p.contact_id AND contact.is_deleted <> 1
              GROUP BY e.id 
              ORDER BY e.start_date desc, e.title   
                "; 
        $dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;

        while($dao->fetch()){
              $event_id = $dao->event_id;
              $event_title = $dao->title; 
              $event_start_date = $dao->start_date;
              $part_count = $dao->part_count; 

              $events_list[$event_id] = $event_title." - ".$event_start_date." (count: ".$part_count.")"; 
        }
        $dao->free();
        return $events_list; 
   }

   function getFinancialTypeLabels( $ft_ids ){
      if( count( $ft_ids) > 0 ){

        
      $id_str = implode( ", ", $ft_ids);
          
      $sql = "select ft.id as ft_id, ft.name as ft_name from civicrm_financial_type ft where ft.id IN ( ".$id_str." )";
      //print "<br><br><br> SQL : ".$sql."<br>";
     // print_r( $ft_ids);
      $dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
	$ft_names = array();
	while($dao->fetch()){
            $ft_id = $dao->ft_id;
            $ft_name = $dao->ft_name;
             
            $ft_names[$ft_id] = $ft_name;
        }
        $dao->free();

       }else{
           $ft_names = array();
       }

       return $ft_names;  
   }
    
    /***************************************************************************************************/
  function getFinancialTypeListFromSets( $ct_type_prefix_ids){
	
	
	$prefix_seperator = "---";
	
	$tmp_ct_sql = "SELECT SUBSTRING(ct_a.name , 1, locate( '".$prefix_seperator."' , ct_a.name) -1 )  as ct_prefix_name ,  ct_b.id as ct_id
			from civicrm_financial_type ct_a , civicrm_financial_type ct_b  
			where ct_a.id IN (".$ct_type_prefix_ids.")
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
	
	$tmp_contrib_type_ids_for_sql = implode(", ", $ct_ids); 
	
	return $tmp_contrib_type_ids_for_sql;
	
	
	
  }

  /***************************************************************************************************/
  function getRelationshipTypeListFromSets( $rt_type_prefix_ids){
	
	
	$prefix_seperator = "---";
	
	$tmp_rel_sql = "SELECT SUBSTRING(rt_a.label_a_b , 1, locate( '".$prefix_seperator."' , rt_a.label_a_b) -1 )  as rt_prefix_name ,  rt_b.id as rt_id
			from civicrm_relationship_type rt_a , civicrm_relationship_type rt_b  
			where rt_a.id IN (".$rt_type_prefix_ids.")
			AND SUBSTRING(rt_a.label_a_b , 1, locate( '".$prefix_seperator."' , rt_a.label_a_b) -1 )  =
			 SUBSTRING(rt_b.label_a_b , 1, locate( '".$prefix_seperator."' , rt_b.label_a_b) -1 ) ";
	
	
	//	print "<br>sql: ".$tmp_ct_sql ;
	$dao =& CRM_Core_DAO::executeQuery( $tmp_rel_sql,   CRM_Core_DAO::$_nullArray ) ;
	$rt_ids = array();
	while($dao->fetch()){
		$rt_prefix_name = $dao->rt_prefix_name;
		$rt_id = $dao->rt_id; 
		$rt_ids[] = $rt_id;
	
	}
	$dao->free();	
	
	$tmp_rel_type_ids_for_sql = implode(", ", $rt_ids); 
	
	return $tmp_rel_type_ids_for_sql;
	
	
	
  }


    function getListContribYears(){

       $all_contrib_years = array(); 
       $sql = "select distinct year(contrib.receive_date) as contrib_year 
               from civicrm_contribution contrib 
               where contrib.is_test <> 1 and contrib.receive_date is not null
               ORDER BY year(contrib.receive_date) desc ";

    $dao = & CRM_Core_DAO::executeQuery( $sql, CRM_Core_DAO::$_nullArray );
                                             
         while ($dao->fetch()){
             $year = $dao->contrib_year; 

            $all_contrib_years[$year] = $year; 
         }
     
     $dao->free(); 

      return $all_contrib_years; 
    


   }
   

function getListRelationshipSets($for_user_filter = FALSE){
          $all_rel_sets = array(); 

      $prefix_seperator = "---";
       $sql = "SELECT distinct( SUBSTRING(label_a_b , 1, locate( '".$prefix_seperator."' , label_a_b) -1 ) )  as rel_type_prefix , min(id) as id
	   		FROM civicrm_relationship_type
	   		WHERE is_active = 1
	   		AND length( SUBSTRING( label_a_b, 1, locate( '---', label_a_b ) -1 ) ) > 0
	   		GROUP BY SUBSTRING(label_a_b , 1, locate( '".$prefix_seperator."' , label_a_b) -1 )
			ORDER BY  rel_type_prefix asc" ;

    	$dao = & CRM_Core_DAO::executeQuery( $sql, CRM_Core_DAO::$_nullArray );
                                             
         while ($dao->fetch()){
             $id = $dao->id; 
             $prefix = $dao->rel_type_prefix; 
             if( $for_user_filter){    
                 $all_rel_sets[$id] = $prefix; 
             }else{
                 $all_rel_sets[] = $id; 
             }
   
   	}
   	$dao->free();
   	
   	return $all_rel_sets;

}
function getListFinancialSets($for_user_filter = FALSE){
   	 $all_financial_sets = array(); 

      $prefix_seperator = "---";
       $sql = "SELECT distinct( SUBSTRING(name , 1, locate( '".$prefix_seperator."' , name) -1 ) )  as financial_type_prefix , min(id) as id
	   		FROM civicrm_financial_type
	   		WHERE is_active = 1
	   		AND length( SUBSTRING( name, 1, locate( '---', name ) -1 ) ) > 0
	   		GROUP BY SUBSTRING(name , 1, locate( '".$prefix_seperator."' , name) -1 )
			ORDER BY  financial_type_prefix asc" ;

    	$dao = & CRM_Core_DAO::executeQuery( $sql, CRM_Core_DAO::$_nullArray );
                                             
         while ($dao->fetch()){
             $id = $dao->id; 
             $prefix = $dao->financial_type_prefix; 
             if( $for_user_filter){    
                 $all_financial_sets[$id] = $prefix; 
             }else{
                 $all_financial_sets[] = $id; 
             }
   
   	}
   	$dao->free();
   	
   	return $all_financial_sets;
   	
   
   }
   
   function getListFinancialTypes($for_user_filter = FALSE){
   	 $all_financial_types = array(); 
       $sql = "select distinct li.financial_type_id as ft_id , ft.name as ft_name
               from civicrm_line_item li
               join civicrm_financial_type ft ON li.financial_type_id = ft.id 
               where ft.is_active = 1  
               ORDER BY ft.name ";

    	$dao = & CRM_Core_DAO::executeQuery( $sql, CRM_Core_DAO::$_nullArray );
                                             
         while ($dao->fetch()){
             $ft_id = $dao->ft_id; 
             $ft_name = $dao->ft_name; 
               if( $for_user_filter){
                        $all_financial_types[$ft_id] = $ft_name;
               }else{
                        $all_financial_types[] = $ft_id; 
               } 
   
   	}
   	$dao->free();
   	
   	return $all_financial_types;
   	
   
   }



    function getListClassYears(){

         $all_class_years = array();

           

        $sql = "SELECT DISTINCT classyear FROM (select distinct yeartable.class_of__mha__44 as classyear 
 from civicrm_value_lastname_while_in_program_1 yeartable 
  where class_of__mha__44 is not null AND class_of__mha__44 <> 0 
  UNION 
      select distinct yeartable.class_of__other__45 as classyear 
    from civicrm_value_lastname_while_in_program_1 yeartable
    where class_of__other__45 is not null AND class_of__other__45 <> 0 
) AS T1 order by classyear desc ";

  // print "<br><br><br>".$sql; 
     $dao = & CRM_Core_DAO::executeQuery( $sql, CRM_Core_DAO::$_nullArray );
                                             
         while ($dao->fetch()){
             $year = $dao->classyear; 

            $all_class_years[$year] = $year; 
         }
     
     $dao->free(); 

      return $all_class_years;  

   }
        
    /* 
     * Functions below generally don't need to be modified
     */
    function count( ) {
        $sql = $this->all( );
            
        $dao = CRM_Core_DAO::executeQuery( $sql,
                                           CRM_Core_DAO::$_nullArray );
        return $dao->N;
    }
        
     function contactIDs( $offset = 0, $rowcount = 0, $sort = null, $returnSQL = false) { 
        return $this->all( $offset, $rowcount, $sort, false, true );
    }
        
    function &columns( ) {
        return $this->_columns;
    }
        
    function setTitle( $title ) {
        if ( $title ) {
            CRM_Utils_System::setTitle( $title );
        } else {
            CRM_Utils_System::setTitle(ts('Search'));
        }
    }

    function summary( ) {
        return null;
    }
        


}
