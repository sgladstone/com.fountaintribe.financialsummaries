<?php

/**
 * A custom contact search
 */
class CRM_Financialsummaries_Form_Search_contactfinancialsummary extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
 


  protected $_formValues;
  protected $groupby_string ;
  protected $FINANCIAL_TYPE_IDS ;
  protected $GENERAL_LEDGER_CODES;
  
  function __construct( &$formValues ) {
  	parent::__construct( $formValues );
  
  	// $this->_eventID = CRM_Utils_Array::value( 'event_id',
  	//                                           $this->_formValues );
  
  
  	$tmp_option_value_raw =   $this->_formValues['priceset_option_id'] ;
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
  
  
  	 
  
  	$this->setColumns( );
  
  
  
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
  
  	/**
  	 * You can define a custom title for the search form
  	 */
  	$this->setTitle('Contact Financial Summary');
  	 
  	/**
  	 * if you are using the standard template, this array tells the template what elements
  	 * are part of the search criteria
  	 */
  	 
  
  	/*
  	 require_once 'utils/util_money.php';
  	if ( pogstone_is_user_authorized('access CiviContribute') == false ){
  		$this->setTitle('Not Authorized');
  		return;
  		 
  	}
  	
  	*/
  
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
  
  	/* Make sure user can filter on groups and memberships  */
  //	require_once('utils/CustomSearchTools.php');
  //	$searchTools = new CustomSearchTools();
  	//$group_ids = $searchTools->getRegularGroupsforSelectList();
  
  	//$group_ids =   CRM_Core_PseudoConstant::group();
  
  	$form->add('select', 'group_of_contact', ts('Contact is in the group'), $group_ids, FALSE,
  			array('id' => 'group_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
  			);
  
  
  	//$mem_ids = $searchTools->getMembershipsforSelectList();
  
  
  
  	$form->add('select', 'membership_type_of_contact', ts('Contact has the membership of type'), $mem_ids, FALSE,
  			array('id' => 'membership_type_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
  			);
  
  	//$org_ids = $searchTools->getMembershipOrgsforSelectList();
  	$form->add('select', 'membership_org_of_contact', ts('Contact has Membership In'), $org_ids, FALSE,
  			array('id' => 'membership_org_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
  			);
  	/* end of filters for groups and memberships  */
  
  
  	$contrib_type_choices = array( );
  	$accounting_code_choices = array( );
  
  
  	
  	// TODO: Exclue prepayments. 
  	//require_once('utils/finance/Prepayment.php');
  	//$tmpPrepayment = new Prepayment();
  	//$tmp_exlude_prepayment_sql = $tmpPrepayment->getExcludePrepaymentsSQL();
  
  
  
  	$financial_type_sql = "";
  	/*
  	 left join civicrm_financial_type as ct on li.financial_type_id = ct.id
  	 LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
  	 AND efa.account_relationship = 1
  	 LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id
  	  
  	 */
  
  	$financial_type_sql = "Select ct.id, ct.name, fa.accounting_code from civicrm_financial_type ct
        	 	LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
        	 	AND efa.account_relationship = 1
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id
			    where ct.is_active = 1 order by name";
  
  
  
  
  
  
  	//print "<br>sql: ".$contrib_type_sql;
  	$contrib_dao = & CRM_Core_DAO::executeQuery(  $financial_type_sql, CRM_Core_DAO::$_nullArray );
  	 
  	while ($contrib_dao->fetch()){
  
  		$cur_id = $contrib_dao->id;
  		$cur_name = $contrib_dao->name;
  		$accounting_code = $contrib_dao->accounting_code;
  
  		$pos_a = strpos($cur_name, 'adjustment-');
  		// $pos_b = strpos($cur_name, 'prepayment-');
  
  		if ($pos_a === false ) {
  
  			if( strlen($accounting_code) > 0 ){
  				$tmp_description = $cur_name." - ".$accounting_code;
  				$accounting_code_choices[$accounting_code] = $accounting_code;
  			}else{
  				$tmp_description = $cur_name;
  			}
  			 
  			$contrib_type_choices[$cur_id] = $tmp_description;
  
  
  		}
  	}
  	 
  	$contrib_dao->free();
  
  
  	 
  	natcasesort ($accounting_code_choices);
  	 
  
  	 
  
  	$financial_type_label = "";
  	$summary_type_label = "";
  
  
  	$financial_type_label = "Financial Types";
  	$summary_type_label = "Financial Type";
  
  	 
  	$form->add('select', 'contrib_type', ts($financial_type_label), $contrib_type_choices, FALSE,
  			array('id' => 'contrib_type', 'multiple' => 'multiple', 'title' => ts('-- select --'))
  			);
  
  
  	$form->add('select', 'accounting_code', ts('Accounting Codes'),  $accounting_code_choices, FALSE,
  			array('id' => 'accounting_code', 'multiple' => 'multiple', 'title' => ts('-- select --'))
  			);
  	 
  	 
  	// TODO: Add handling for Financial Categories. 
  	//require_once( 'utils/finance/FinancialCategory.php') ;
  	//$tmpFinCategory = new FinancialCategory();
  	 
  
  	$tmpFinCatArray = array( ) ;
  	 
  	//$tmpFinCategory->getCategoryList($tmpFinCatArray);
  	 
  	//   array_unshift( $tmpFinCatArray, ' -- select --');
  	$tmpFinCatArray[0] = ' -- select -- ';
  	 
  	natcasesort ( $tmpFinCatArray) ;
  	$form->add  ('select', 'financial_set', ts('Financial Set'),
  			$tmpFinCatArray,
  			false);
  	/*
  	 $form->add('select', 'financial_category', ts('Financial Sets'), $tmpFinCatArray, FALSE,
  	 array('id' => 'financial_category', 'multiple' => '', 'title' => ts('-- select --'))
  	 );
  	 */
  
  	$balance_choices = array();
  	$balance_choices[''] = '  -- Select Balances to Include -- ';
  	$balance_choices['all'] = 'All Records';
  	$balance_choices['open_balances'] = 'Open Balances (Balance is not 0)' ;
  	$balance_choices['closed_balances'] = 'Closed Balances (Balance is 0)';
  
  
  	$form->add  ('select', 'balance_choice', ts('Balance Choice'),
  			$balance_choices,
  			false);
  
  	$layout_choices = array();
  	$layout_choices[''] = '  -- Select Layout -- ';
  	$layout_choices['details'] = 'Details';
  	$layout_choices['summarize_contact_contribution_type'] = 'Summarized by Contact, '.$summary_type_label;
  	$layout_choices['summarize_contact'] = 'Summarized by Contact';
  	//  $layout_choices['summarize_household_contribution_type'] = 'Summarized by Household, '.$summary_type_label;
  	//  $layout_choices['summarize_household'] = 'Summarized by Household';
  	 
  	$layout_choices['summarize_contribution_type'] = 'Summarized by '.$summary_type_label;
  	$layout_choices['summarize_accounting_code'] = 'Summarized by Accounting Code';
  	 
  	$form->add  ('select', 'layout_choice', ts('Layout Choice'),
  			$layout_choices,
  			false);
  	 
  	 
  	 
  	 
  	$form->addDate('start_date', ts('From'), false, array( 'formatType' => 'custom' ) );
  
  	$form->addDate('end_date', ts('...through'), false, array( 'formatType' => 'custom' ) );
  
  //	$comm_prefs =  $searchTools->getCommunicationPreferencesForSelectList();
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
  	 
  	$form->assign( 'elements', array( 'group_of_contact', 'membership_org_of_contact' , 'membership_type_of_contact' ,'start_date', 'end_date',  'contrib_type', 'accounting_code',  'balance_choice',   'layout_choice') );
  
  
  
  	 
  	 
  }
  
  function setColumns( ) {
  	$layout_choice = $this->_formValues['layout_choice'] ;
  	 
  	//print "<br><br>layout choice: ".$layout_choice;
  	if( $layout_choice == 'summarize_contact_contribution_type'){
  
  		$groupby = "contact_id,currency,contrib_type_id";
  	}else if($layout_choice == 'summarize_contribution_type'){
  		$groupby = "currency,contrib_type_id";
  	}else if($layout_choice == 'summarize_accounting_code'){
  		$groupby = "currency,accounting_code";
  
  	}else{
  
  		$groupby = "";
  	}
  	 
  	//   print "<br>group by : ".$groupby ;
  	$group_fields = explode(',' , $groupby );
  	$display_contact_name = false;
  	if(in_array('contact_id', $group_fields)     ){
  		$display_contact_name = true;
  
  	}
  
  	//  print "<br>Should display contact name? ".$display_contact_name;
  	// print "<br> group field array: ";
  	// print_r($group_fields);
  
  	$display_contrib_type = false;
  	if(in_array('contrib_type_id', $group_fields)){
  		$display_contrib_type = true;
  
  	}
  
  	if($layout_choice == 'summarize_contact' || $layout_choice == 'summarize_household' ){
  
  
  		$this->_columns = array( ts('' )    		=> 'contact_image',
  				ts('Name') 		=> 'sort_name',
  
  				ts('Currency')		=> 'currency',
  				ts('Amount') 	=> 'total_amount',
  				ts('Received') 	=> 'received',
  				ts('Adjusted') 	=> 'adjusted',
  				ts('Balance') 	=> 'balance',
  				ts('Records Combined') => 'rec_count',
  				ts('Street Address') => 'street_address',
  				ts('City')		=> 'city',
  				ts('State/Province')	=> 'state',
  				ts('Postal Code') 	=> 'postal_code',
  				ts('Country') => 'country',
  
  		);
  
  
  
  
  	}else{
  
  		 
  		$financial_type_label = "";
  
  		 
  		$financial_type_label = "Financial Type";
  
  
  		if((strlen($groupby) > 0 && $display_contact_name) ||   $layout_choice == 'summarize_household_contribution_type' ){
  			$this->_columns = array( ts('' )    		=> 'contact_image',
  					ts('Name') 		=> 'sort_name',
  					ts($financial_type_label )  => 'contrib_type',
  					ts('Accounting Code') => 'accounting_code',
  					ts('Financial Set') => 'financial_category',
  					ts('Currency')		=> 'currency',
  					ts('Amount') 	=> 'total_amount',
  					ts('Received') 	=> 'received',
  					ts('Adjusted') 	=> 'adjusted',
  					ts('Balance') 	=> 'balance',
  					ts('Records Combined') => 'rec_count',
  					ts('Street Address') => 'street_address',
  					ts('City')		=> 'city',
  					ts('State/Province') 	=> 'state',
  					ts('Postal Code') 	=> 'postal_code',
  					ts('Country') => 'country',
  
  			);
  
  
  
  		}else if(strlen($groupby) > 0 && !($display_contact_name) && ($display_contrib_type) ){
  			$this->_columns = array( ts('' )    		=> 'contact_image',
  
  					ts($financial_type_label )  => 'contrib_type',
  					ts('Accounting Code') => 'accounting_code',
  					ts('Financial Category') => 'financial_category',
  					ts('Currency')		=> 'currency',
  					ts('Amount') 	=> 'total_amount',
  					ts('Received') 	=> 'received',
  					ts('Adjusted') 	=> 'adjusted',
  					ts('Balance') 	=> 'balance',
  					ts('Records Combined') => 'rec_count',
  
  			);
  
  		}else if(strlen($groupby) > 0 && !($display_contact_name) && !($display_contrib_type) ){
  			$this->_columns = array( ts('' )    		=> 'contact_image',
  
  					 
  					ts('Accounting Code') => 'accounting_code',
  					ts('Currency')		=> 'currency',
  					ts('Amount') 	=> 'total_amount',
  					ts('Received') 	=> 'received',
  					ts('Adjusted') 	=> 'adjusted',
  					ts('Balance') 	=> 'balance',
  					ts('Records Combined') => 'rec_count',
  
  			);
  		}else{
  			$this->_columns = array( ts('' )    		=> 'contact_image',
  					ts('Name') 		=> 'sort_name',
  					ts($financial_type_label )  => 'contrib_type',
  					ts('Accounting Code') => 'accounting_code',
  					ts('Financial Set') => 'financial_category',
  					ts('Start Date')	=> 'formatted_date',
  					ts('Start Date (sortable)')  => 'date_for_sort',
  					//     ts('Year') => 'yyyy_date',
  					//     ts('Month') => 'mm_date',
  					//     ts('Day') => 'dd_date',
  					ts('Source')	=> 'source',
  					ts('Currency')		=> 'currency',
  					ts('Amount') 	=> 'total_amount',
  					ts('Received') 	=> 'received',
  					ts('Adjusted') 	=> 'adjusted',
  					ts('Balance') 	=> 'balance',
  
  					ts('Type')		=> 'entity_type' ,
  					ts('Entity ID') => 'id',
  					ts('Status') => 'status_label',
  					ts('Street Address') => 'street_address',
  					ts('City')		=> 'city',
  					ts('State/Province') 	=> 'state',
  					ts('Postal Code') 	=> 'postal_code',
  					ts('Country') => 'country',
  			);
  			//   ts('Num. Records Combined') => 'num_records' ,
  			 
  			//   ts('Email') 	       => 'email',
  			//  ts('Phone')	       => 'phone',
  			//  ts('Address' )	=> 'street_address',
  			//  ts('Address line 1') => 'supplemental_address_1',
  			//  ts('City') 		=> 'city',
  			//  ts('State') =>  'state',
  			//  ts('Postal Code') => 'postal_code' );
  		}
  
  	}
  	//  $this->_columns = array( ts('Contact Id')      => 'contact_id'  );
  
  }
  
  
  
  
  function select($summary_section = false, $onlyIDs){
  
  
  
  	 
  
  
  	return $select;
  
  }
  // return $this->all( $offset, $rowcount, $sort, false, true );
   
  function all( $offset = 0, $rowcount = 0, $sort = null,
  		$includeContactIDs = false, $onlyIDs = false ) {
  			 
  			// TODO:  check authority of end-user
  			//require_once 'utils/util_money.php';
  			//if ( pogstone_is_user_authorized('access CiviContribute') == false ){
  			//	return "select contact_a.id as contact_id from civicrm_contact contact_a where 1=0 ";
  				 
  			//}
  			 
  			 
  			 
  			$groupby = "";
  			$layout_choice = $this->_formValues['layout_choice'] ;
  			if ( $onlyIDs ) {
  				$groupby = "";
  			}else{
  				//print "<br><br>layout choice: ".$layout_choice;
  				if( $layout_choice == 'summarize_contact_contribution_type'){
  					$groupby = "contact_id,currency,contrib_type_id";
  				}else if($layout_choice == 'summarize_contribution_type'){
  					$groupby = "currency,contrib_type_id";
  				}else if($layout_choice == 'summarize_accounting_code'){
  					$groupby = "currency,accounting_code";
  				}else if($layout_choice == 'summarize_contact'){
  					$groupby = "contact_id,currency" ;
  				}else if( $layout_choice == 'summarize_household_contribution_type'){
  					$groupby = "contact_id,currency,contrib_type_id";
  				}else if( $layout_choice == 'summarize_household'){
  					$groupby = "contact_id,currency" ;
  				}else{
  					$groupby = "";
  				}
  
  				$this->groupby_string = $groupby ;
  
  			}
  
  			 
  			$grand_totals = false;
  			 
  			// make sure selected smart groups are cached in the cache table
  			$group_of_contact = $this->_formValues['group_of_contact'];
  
  			// TODO: Handle smart groups
  			//require_once('utils/CustomSearchTools.php');
  			//$searchTools = new CustomSearchTools();
  			//$searchTools::verifyGroupCacheTable($group_of_contact ) ;
  			 
  			$where = $this->where();
  
  			//require_once ('utils/util_money.php');
  			$tmp_order_by = "";
  			$all_contacts = true;
  			$get_contact_name = true;
  
  			//print "<br>where: ".$where;
  			$exclude_after_date = '';
  
  			require_once('utils/Obligation.php');
  			$obligation = new Obligation();
  
  			$groups_of_contact = $this->_formValues['group_of_contact'];
  			$mem_types_of_contact  = $this->_formValues['membership_type_of_contact'] ;
  			$mem_orgs_of_contact  =  $this->_formValues['membership_org_of_contact'] ;
  
  			$ct_type_prefix_id = '' ;
  			$include_closed_items = true;
  				
  			$start_date_parm  = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
  			$end_date_parm  = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
  			 
  			$financaial_set_id  = $this->_formValues['financial_set'] ;
  			 
  			 
  			 
  			if(strlen($financaial_set_id) > 0 ){
  				$ct_type_prefix_id = $financaial_set_id;
  				//	 print "<br> financial set id : ". $ct_type_prefix_id;
  				//print "<br><br> financial categories sql: ".$tmp_fc;
  			}
  
  			$empty_str = "";
  			$include_prepayments = true;
  			//  print "<br> custom search, financial type ids: ".$this->FINANCIAL_TYPE_IDS ;
  			$sql_innter = $obligation->get_sql_string_for_obligations($contactIDs,  $tmp_order_by, $end_date_parm , $start_date_parm,  $exclude_after_date , $error,  $all_contacts, $get_contact_name, $where, $groupby,
  					$ct_type_prefix_id , $include_closed_items ,
  					$groups_of_contact, $mem_types_of_contact , $mem_orgs_of_contact, $empty_str, $layout_choice , $this->FINANCIAL_TYPE_IDS, $this->GENERAL_LEDGER_CODES, $include_prepayments  );
  
  			/*
  
  			function get_sql_string_for_obligations(&$contactIDs, &$order_by_parm, &$end_date_parm , &$start_date_parm, &$exclude_after_date_parm,  &$error, $all_contacts = false, $get_contact_name  = false, $extra_where_clause_parm = '', $extra_groupby_clause = '', $ct_type_prefix_id = '', $include_closed_items = true,
  			$groups_of_contact = array(), $mem_types_of_contact = array() , $mem_orgs_of_contact = array(),  $columns_needed = "",
  			$layout_choice = "", $financial_types_parm = "", $gl_codes )
  
  			*/
  
  
  
  
  			//   print "<br><br> sql: ".$sql;
  			if ( $onlyIDs ) {
  				$outer_select =  "contact_a.id as contact_id";
  			}else{
  				$outer_select = "contact_b.* , address.street_address, address.city, state.abbreviation as state,  address.postal_code, country.name as country";
  
  
  			}
  
  
  			// $outer_group_by = " group by contact_a.id, contact_b.contrib_type" ;
  			 
  			// group by contact_a.id, contact_b.entity_type, contact_b.id  ";
  			 
  			 
  			 
  			 
  			 
  			$sql  = "SELECT ".$outer_select." FROM ($sql_innter
  			) as contact_b
  			LEFT JOIN civicrm_address address ON contact_b.contact_id = address.contact_id AND address.is_primary = 1
  			LEFT JOIN civicrm_state_province state ON address.state_province_id = state.id
  			LEFT JOIN civicrm_country country ON address.country_id = country.id
  			LEFT JOIN civicrm_contact contact_a ON contact_b.contact_id = contact_a.id $tmp_email_join
  			WHERE 1=1 ";
  
  
  
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
  
  
  
  			// print "<br><br>full sql: ". $sql;
  
  			return $sql;
  			 
  
  			 
  			 
  			 
  
  }
  
  
  
  
  
  function from( ) {
  
  
  
  
  	return $tmp_from;
  
  
  
  }
  
   
  
  function where( $includeContactIDs = false ) {
  	$clauses = array( );
  	 
  
  	// Now check user contrib type filter
  	 
  	$contrib_type_ids = $this->_formValues['contrib_type'] ;
  
  	if( ! is_array($contrib_type_ids)){
  		 
  		//print "<br>No contrib type selected.";
  
  		 
  	}else{
  
  		$i = 1;
  		$tmp_id_list = '';
  		foreach($contrib_type_ids as $cur_id){
  			if(strlen($cur_id ) > 0){
  				$tmp_id_list = $tmp_id_list." '".$cur_id."'" ;
  				if($i < sizeof($contrib_type_ids)){
  					$tmp_id_list = $tmp_id_list."," ;
  				}
  			}
  			$i += 1;
  		}
  
  		if(!(empty($tmp_id_list)) ){
  			$this->FINANCIAL_TYPE_IDS = $tmp_id_list;
  			$clauses[] = "f1.contrib_type_id IN ( ".$tmp_id_list." ) ";
  
  		}
  
  		 
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
  			$this->GENERAL_LEDGER_CODES = $tmp_id_list ;
  			$clauses[] = "f1.accounting_code IN ( ".$tmp_id_list." ) ";
  			//print "<br>";
  			//print_r ($clauses);
  
  		}
  
  		 
  	}
  
  
  
  	$balance_choice = $this->_formValues['balance_choice'] ;
  	//print "<br>balance choice: ".$balance_choice;
  	if(strcmp($balance_choice, 'open_balances') == 0){
  
  		$clauses[] = "f1.balance <> 0  ";
  	}else if(strcmp($balance_choice, 'closed_balances') == 0){
  		$clauses[] = "f1.balance = 0  ";
  
  
  	}
  
  
  	// filter for f1.rec_date
  	$startDate = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
  	if ( $startDate ) {
  		$clauses[] = " date(f1.rec_date) >= date($startDate)";
  	}
  
  	$endDate = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
  	if ( $endDate ) {
  		$clauses[] = " date(f1.rec_date) <= date($endDate)";
  	}
  	 
  
  
  	if(count($clauses) > 0){
  		$partial_where_clause = implode( ' AND ', $clauses );
  		$tmp_where = $partial_where_clause;
  		 
  		 
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
  
  function XXalterRow( &$row ) {
  	 
  
  	 
  	$row['full_date'] =$row['mm_date'].'/'.$row['dd_date'].'/'.$row['yyyy_date'];
  	 
  	 
  	$type = $row['entity_type'];
  	$entity_id = $row['id'];
  	$total_amount = $row['total_amount'];
  	$status_label = $row['status_label'];
  	 
  	if($type == 'pledge'){
  		 
  		if($status_label == 'Completed'){
  			/*
  			 $tmp_cur_line_balance = '';
  			 $tmp_cur_line_adjustments = '';
  			 $tmp_cur_line_recieved = '';
  
  			  
  			 $tmp_cur_line_recieved = $total_amount;
  			 $tmp_cur_line_balance = 0;
  			 $tmp_cur_line_adjustments = get_pledge_adjustments_total($entity_id ) ;
  			  
  			 */
  			if(strlen($end_date_parm) > 0){
  				$tmp_cur_line_due = 0 ;
  
  			}
  
  		}else if($status_label == 'Pending'  || $status_label == 'In Progress' || $status_label == 'Overdue' ){
  
  			if(strlen($end_date_parm) > 0){
  
  				$tmp_cur_line_due = get_due_to_date_amount( $entity_type , $entity_id,  $end_date_parm) ;
  				 
  			}
  		}
  
  
  	}else if ($type == 'contribution'){
  		if($status_label == 'Completed'){
  			//   $tmp_cur_line_recieved = $total_amount;
  			//   $tmp_cur_line_balance = 0;
  			$tmp_cur_line_due = 0 ;
  
  		}else if($status_label == 'Pending'){
  			// $tmp_cur_line_recieved = 0 ;
  			// $tmp_cur_line_balance = $total_amount;
  			if( strlen($end_date_parm) > 0){
  				$tmp_cur_line_due = get_due_to_date_amount( $entity_type , $entity_id,  $end_date_parm) ;
  			}
  		}
  
  		 
  		 
  		 
  	}else if($type == 'recurring'){
  		 
  		 
  
  		 
  		 
  	}
  	 
  
  
  	 
  
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
  	 
  	$dao = CRM_Core_DAO::executeQuery( $sql,
  			CRM_Core_DAO::$_nullArray );
  	return $dao->N;
  	 
  	// return 50;
  }
   
  function contactIDs( $offset = 0, $rowcount = 0, $sort = null) {
  	return $this->all( $offset, $rowcount, $sort, false, true );
  }
   
   
  function &columns( ) {
  	return $this->_columns;
  }
  
   
  
  function summary( ) {
  	 
  	//require_once 'utils/util_money.php';
  	//if ( pogstone_is_user_authorized('access CiviContribute') == false ){
  		 
  	//	return ;
  	//}
  	 
  
  	$sum_array = array();
  
  	$grand_totals = true;
  
  	$groupby = "currency";
  
  	 
  	$where = $this->where();
  
  //	require_once ('utils/util_money.php');
  	$tmp_order_by = "";
  	$all_contacts = true;
  	$get_contact_name = true;
  
  	//print "<br>where: ".$where;
  	$exclude_after_date = '';
  
  	$layout_choice = $this->_formValues['layout_choice'] ;
  		
  	$groups_of_contact = $this->_formValues['group_of_contact'];
  	$mem_types_of_contact  = $this->_formValues['membership_type_of_contact'] ;
  	$mem_orgs_of_contact  =  $this->_formValues['membership_org_of_contact'] ;
  
  	$ct_type_prefix_id = '' ;
  	$include_closed_items = true;
  		
  	$start_date_parm  = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
  	$end_date_parm  = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
  	 
  	$financaial_set_id  = $this->_formValues['financial_set'] ;
  	 
  	 
  	 
  	if(strlen($financaial_set_id) > 0 ){
  		$ct_type_prefix_id = $financaial_set_id;
  		//	 print "<br> financial set id : ". $ct_type_prefix_id;
  		//print "<br><br> financial categories sql: ".$tmp_fc;
  	}
  
  	$empty_str = "" ;
  	$include_prepayments = true;
  	require_once('utils/Obligation.php');
  	$obligation = new Obligation();
  	$sql_inner = $obligation->get_sql_string_for_obligations($contactIDs,  $tmp_order_by, $end_date_parm , $start_date_parm,  $exclude_after_date , $error,  $all_contacts, $get_contact_name, $where, $groupby,
  			$ct_type_prefix_id , $include_closed_items ,
  			$groups_of_contact, $mem_types_of_contact , $mem_orgs_of_contact, $empty_str,  $layout_choice, $this->FINANCIAL_TYPE_IDS, $this->GENERAL_LEDGER_CODES, $include_prepayments  );
  
  
  	 
  	 
  	// print "<br><br>Summary sql: ".$sql;
  	 
  
  	 
  	$sql =  $sql_inner;
  	$dao = CRM_Core_DAO::executeQuery( $sql,         CRM_Core_DAO::$_nullArray );
  
  	while ( $dao->fetch( ) ) {
  
  		$cur_sum = array();
  	  
  		$cur_sum['Currency'] = $dao->currency;
  		$cur_sum['Amount'] = $dao->total_amount;
  		$cur_sum['Received'] = $dao->received;
  		$cur_sum['Adjusted'] = $dao->adjusted;
  		$cur_sum['Balance'] = $dao->balance;
  	  
  		$cur_sum['Records Combined'] = $dao->rec_count;
  		/*
  		 ts('Contribution Type' )  => 'contrib_type',
  		 ts('Accounting Code') => 'accounting_code',
  		 ts('Financial Category') => 'financial_category',
  		 ts('Currency')		=> 'currency',
  		 ts('Charged') 	=> 'total_amount',
  		 ts('Received') 	=> 'received',
  		 ts('Adjusted') 	=> 'adjusted',
  		 ts('Balance') 	=> 'balance',
  		 ts('Records Combined') => 'rec_count',
  		 */
  	  
  		$sum_array[] = $cur_sum;
  
  	}
  	$dao->free();
  
  	return $sum_array;
  	 
  	 
  	 
  
  }
}
