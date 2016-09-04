<?php

/**
 * A custom contact search
 */
class CRM_Financialsummaries_Form_Search_contactfinancialexclusionsummary extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

	protected $_formValues;
	protected $groupby_string ;
	public $_permissionedComponent;
	
	
	function __construct( &$formValues ) {
		parent::__construct( $formValues );
	
		// $this->_eventID = CRM_Utils_Array::value( 'event_id',
		//                                           $this->_formValues );
	
	
		if(isset( $this->_formValues['priceset_option_id'])){
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
	
	
		 
	
		$this->setColumns( );
		
		// define component access permission needed
		$this->_permissionedComponent = 'CiviContribute';
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
		$this->setTitle('Contact Financial Exclusion Summary - Households who do NOT have certain financial records');
		 
		/**
		 * if you are using the standard template, this array tells the template what elements
		 * are part of the search criteria
		 */
		 
	
	
		/* Make sure user can filter on groups and memberships  */
		
		$group_ids =  CRM_Core_PseudoConstant::nestedGroup();
		 
		$cur_domain_id = "-1";
		
		$result = civicrm_api3('Domain', 'get', array(
				'sequential' => 1,
				'current_domain' => "",
		));
		
		if( $result['is_error'] == 0 ){
			$cur_domain_id = $result['id'];
			 
		}
		// get membership ids and org contact ids.
		$mem_ids = array();
		$org_ids = array();
		$api_result = civicrm_api3('MembershipType', 'get', array(
				'sequential' => 1,
				'is_active' => 1,
				'domain_id' =>  $cur_domain_id ,
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
		
		
		$select2style = array(
				'multiple' => TRUE,
				'style' => 'width:100%; max-width: 100em;',
				'class' => 'crm-select2',
				'placeholder' => ts('- select -'),
		);
		 
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
		 /*
	
		$form->add('select', 'group_of_contact', ts('Contact is in the group'), $group_ids, FALSE,
				array('id' => 'group_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
				);
	
		$form->add('select', 'membership_type_of_contact', ts('Contact has the membership of type'), $mem_ids, FALSE,
				array('id' => 'membership_type_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
				);
	
	
		$form->add('select', 'membership_org_of_contact', ts('Contact has Membership In'), $org_ids, FALSE,
				array('id' => 'membership_org_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
				);
				*/
		/* end of filters for groups and memberships  */
	
	
		$contrib_type_choices = array( );
		$accounting_code_choices = array( );
	
	
		require_once('utils/Prepayment.php');
		$tmpPrepayment = new Prepayment();
		$tmp_exlude_prepayment_sql = $tmpPrepayment->getExcludePrepaymentsSQL();
	
	
		
		$financial_type_sql = "";
	
		
		$financial_type_sql = "Select ct.id, ct.name, fa.accounting_code from civicrm_financial_type ct
        	 	LEFT JOIN civicrm_entity_financial_account efa ON ct.id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
        	 	LEFT JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id
			    where ct.is_active = 1 ".$tmp_exlude_prepayment_sql." order by name";
	
		
	
	
	
	
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
		
		$form->add('select', 'contrib_type',
				ts('Financial Type(s)'),
				$contrib_type_choices,
				FALSE,
				$select2style
				);
		
		$form->add('select', 'accounting_code',
				ts('Accounting Code(s)'),
				$accounting_code_choices,
				FALSE,
				$select2style
				);
		 /*
		$form->add('select', 'contrib_type', ts($financial_type_label), $contrib_type_choices, FALSE,
				array('id' => 'contrib_type', 'multiple' => 'multiple', 'title' => ts('-- select --'))
				);
	
	
		$form->add('select', 'accounting_code', ts('Accounting Codes'),  $accounting_code_choices, FALSE,
				array('id' => 'accounting_code', 'multiple' => 'multiple', 'title' => ts('-- select --'))
				);
				
				*/
		 
	
		$balance_choices = array();
		$balance_choices[''] = '  -- Select Balances to Exclude -- ';
		$balance_choices['all'] = 'Exclude Any - open balance or closed balance';
		//   $balance_choices['open_balances'] = 'Exclude Those With Open Balances (Balance is not 0) (ie only list people with a closed balance, or no financial     // activity)' ;
		$balance_choices['closed_balances'] = 'Exclude those With closed/zero balances';  //  (ie only list contacts with an open balance, or no finacial activity)
	
	
		$form->add  ('select', 'balance_choice', ts('Exclude Based On Balance'),
				$balance_choices,
				false);
	
		$layout_choices = array();
		$layout_choices[''] = '  -- Select Layout -- ';
		$layout_choices['details'] = 'Details';
		$layout_choices['summarize_contact_contribution_type'] = 'Summarized by Contact, '.$summary_type_label;
		$layout_choices['summarize_contact'] = 'Summarized by Contact';
		$layout_choices['summarize_contribution_type'] = 'Summarized by '.$summary_type_label;
		$layout_choices['summarize_accounting_code'] = 'Summarized by Accounting Code';
		 
		$form->add  ('select', 'layout_choice', ts('Layout Choice'),
				$layout_choices,
				false);
		 
		 
		 
		 
		$form->addDate('start_date', ts('From'), false, array( 'formatType' => 'custom' ) );
	
		$form->addDate('end_date', ts('...through'), false, array( 'formatType' => 'custom' ) );
		 
		$form->assign( 'elements', array( 'group_of_contact', 'membership_org_of_contact' , 'membership_type_of_contact' ,'start_date', 'end_date', 'contrib_type', 'accounting_code',  'balance_choice') );
	
		 
		 
	}
	
	function setColumns( ) {
		 
		// print "<br>group by : ".$groupby ;
		 
	
	
	
		$this->_columns = array( 
				ts('Name') 		=> 'sort_name',
				ts('Phone') 		=> 'phone',
				ts('Email')		=> 'email',
				ts('Street Address') => 'street_address',
				ts('City')		=> 'city',
				ts('State/Province')	=> 'state',
				ts('Postal Code') 	=> 'postal_code',
				ts('Country') => 'country',
	
		);
	
	
	
	
	
	}
	
	
	
	
	function select($summary_section = false, $onlyIDs){
	
	
	
		 
	
	
		return $select;
	
	}
	// return $this->all( $offset, $rowcount, $sort, false, true );
	 
	function all( $offset = 0, $rowcount = 0, $sort = null,
			$includeContactIDs = false, $onlyIDs = false ) {
				  
				 
				// Force summarize by layout, for exlusion does not make sense otherwise
	
				$layout_choice = 'summarize_household';
				$groupby = "contact_id,currency" ;
	
	
				/*
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
				 }else{
				 $groupby = "";
				 }
	
				 $this->groupby_string = $groupby ;
	
				 }
				 */
				 
				$grand_totals = false;
				 
				// make sure selected smart groups are cached in the cache table
				$group_of_contact = $this->_formValues['group_of_contact'];
	
				require_once('utils/CustomSearchTools.php');
				$searchTools = new CustomSearchTools();
				$searchTools::verifyGroupCacheTable($group_of_contact ) ;
				 
				$where = $this->where();
	
				
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
					
					
				$columns_needed = "contact_id";
	
				$start_date_parm  = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
				$end_date_parm  = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
				$sql_inner_financials = $obligation->get_sql_string_for_obligations($contactIDs,  $tmp_order_by, $end_date_parm , $start_date_parm,  $exclude_after_date , $error,  $all_contacts, $get_contact_name, $where, $groupby,
						$ct_type_prefix_id , $include_closed_items ,
						$groups_of_contact, $mem_types_of_contact , $mem_orgs_of_contact , $columns_needed, $layout_choice );
	
	
	
				//   print "<br><br> sql: ".$sql;
				if ( $onlyIDs ) {
					$outer_select =  "contact_a.id as contact_id";
				}else{
					$outer_select = "contact_b.* , contact_a.sort_name, address.street_address, address.city, state.abbreviation as state,  address.postal_code, country.name as country, email.email, phone.phone";
	
	
				}
				$sql_inner = self::get_sql_contacts_to_include();
	
				 
				
				// Check if current user is restricted to certain contacts by ACLs.
				$acl_sql_fragment  = CRM_Contact_BAO_Contact_Permission::cacheSubquery();
				
				$acl_where_clause = "";
				if( strlen( $acl_sql_fragment ) > 0 ){
					 $acl_where_clause = " AND contact_b.contact_id ".$acl_sql_fragment;	
				}
				
				
				
				 // Now put the whole thing together.
				$sql  = "SELECT ".$outer_select." FROM ($sql_inner
				) as contact_b
				LEFT JOIN civicrm_email email ON contact_b.contact_id = email.contact_id AND email.is_primary = 1
				LEFT JOIN civicrm_phone phone ON contact_b.contact_id = phone.contact_id AND phone.is_primary = 1
				LEFT JOIN civicrm_address address ON contact_b.contact_id = address.contact_id AND address.is_primary = 1
				LEFT JOIN civicrm_state_province state ON address.state_province_id = state.id
				LEFT JOIN civicrm_country country ON address.country_id = country.id
				LEFT JOIN civicrm_contact contact_a ON contact_b.contact_id = contact_a.id
				WHERE contact_b.contact_id NOT IN ( ".$sql_inner_financials." ) ".$acl_where_clause. "
	AND contact_a.contact_type = 'Household'
	AND contact_a.is_deleted <> 1
	GROUP BY contact_id ";
	
	
	
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
	
	
	function get_sql_contacts_to_include(){
		$tmp_from = "";
		$tmp_group_join = "";
		 
		 
		 
		// Deal with households
	
		$tmp_contact_sql = "rel.contact_id_b as household_id , ifnull( rel.contact_id_b, contact_a.id ) as contact_id, contact_a.id as underlying_contact_id  ";
	
	
		$tmp_rel_type_ids = "7, 6";   // Household member of , Head of Household
		$tmp_from_sql = " LEFT JOIN civicrm_relationship rel ON contact_a.id = rel.contact_id_a AND rel.is_active = 1 AND rel.is_permission_b_a = 1 AND rel.relationship_type_id IN ( ".$tmp_rel_type_ids." ) ";
	
		// done dealing with households
		 
	
		if(count( $this->_formValues['group_of_contact'] ) > 0 ){
			$tmp_group_join = "LEFT JOIN civicrm_group_contact as groups on contact_a.id = groups.contact_id".
					" LEFT JOIN civicrm_group_contact_cache as groupcache ON contact_a.id = groupcache.contact_id ";
		}
		 
		 
		$tmp_mem_join = "";
		if( count( $this->_formValues['membership_type_of_contact'] ) > 0 || count( $this->_formValues['membership_org_of_contact'] ) > 0     ){
			$tmp_mem_join = "LEFT JOIN civicrm_membership as memberships on contact_a.id = memberships.contact_id
	 	LEFT JOIN civicrm_membership_status as mem_status on memberships.status_id = mem_status.id
	 	LEFT JOIN civicrm_membership_type mt ON memberships.membership_type_id = mt.id ";
			 
		}
	
		 
		if(isset(  $this->_formValues['comm_prefs'] )  && strlen(  $this->_formValues['comm_prefs']) > 0  ){
			$comm_prefs = $this->_formValues['comm_prefs'];
			$tmp_email_join = "LEFT JOIN civicrm_email ON contact_a.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1 ";
		}else{
			$comm_prefs = "";
			$tmp_email_join = "";
			
		}
		$tmp_from = " civicrm_contact contact_a
		$tmp_from_sql
		$tmp_email_join
		$tmp_group_join
		$tmp_mem_join";
	
	
		 
		// now do where clause
		$clauses = array( );
	
		$clauses[] = "contact_a.is_deleted <> 1";
		$clauses[] = "contact_a.is_deceased <> 1";
	
		if(isset($this->_formValues['oc_month_start'] )){
			$oc_month_start = $this->_formValues['oc_month_start'] ;
		}
		
		if( isset(  $this->_formValues['oc_month_end'] )){
			$oc_month_end = $this->_formValues['oc_month_end'] ;
		}
		
		if(isset( $this->_formValues['oc_day_start'] )){
			$oc_day_start = $this->_formValues['oc_day_start'];
		}
		
		if( isset(  $this->_formValues['oc_day_end'])){
			$oc_day_end = $this->_formValues['oc_day_end'];
		}
		
	
	
		$groups_of_individual = $this->_formValues['group_of_contact'];
	
		require_once('utils/CustomSearchTools.php');
		$searchTools = new CustomSearchTools();
	
	
		if( isset($this->_formValues['comm_prefs'] )){
			$comm_prefs = $this->_formValues['comm_prefs'];
		}else{
			$comm_prefs = "";
		}
		$searchTools->updateWhereClauseForCommPrefs($comm_prefs, $clauses ) ;
	
		$tmp_sql_list = $searchTools->getSQLStringFromArray($groups_of_individual);
		if(strlen($tmp_sql_list) > 0 ){
	
			// need to check regular groups as well as smart groups.
			$clauses[] = "( (groups.group_id IN (".$tmp_sql_list.") AND groups.status = 'Added') OR ( groupcache.group_id IN (".$tmp_sql_list.")  )) " ;
	
	
		}
	
		$membership_types_of_con = $this->_formValues['membership_type_of_contact'];
	
	
		$tmp_membership_sql_list = $searchTools->convertArrayToSqlString( $membership_types_of_con ) ;
		if(strlen($tmp_membership_sql_list) > 0 ){
			$clauses[] = "memberships.membership_type_id IN (".$tmp_membership_sql_list.")" ;
			$clauses[] = "mem_status.is_current_member = '1'";
			$clauses[] = "mem_status.is_active = '1'";
	
		}
	
		// 'membership_org_of_contact'
		$membership_org_of_con = $this->_formValues['membership_org_of_contact'];
		$tmp_membership_org_sql_list = $searchTools->convertArrayToSqlString( $membership_org_of_con ) ;
		if(strlen($tmp_membership_org_sql_list) > 0 ){
	
			$clauses[] = "mt.member_of_contact_id IN (".$tmp_membership_org_sql_list.")" ;
			$clauses[] = "mt.is_active = '1'" ;
			$clauses[] = "mem_status.is_current_member = '1'";
			$clauses[] = "mem_status.is_active = '1'";
	
		}
	
	
		if ( isset( $includeContactIDs ) && $includeContactIDs ) {
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
	
		$partial_where_clause = implode( ' AND ', $clauses );
	
	
	
	
	
		$sql = "select ".$tmp_contact_sql."
		FROM ".$tmp_from."
		WHERE ".$partial_where_clause ;
	
		//	print "<br> exclude sql inner : ".$sql;
	
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
		 
	
		/*
		 $groups_of_contact = $this->_formValues['group_of_contact'];
	
	
		 // Figure out if end-user is filtering results according to groups.
		 require_once('utils/CustomSearchTools.php');
		 $searchTools = new CustomSearchTools();
		 $tmp_sql_list = $searchTools->getSQLStringFromArray($groups_of_contact);
	
		 if(strlen($tmp_sql_list) > 0 ){
		 $clauses[] = "( groups.group_id IN (".$tmp_sql_list.") AND groups.status = 'Added') ";
	
		 }
		 //
	
		 $membership_types_of_con = $this->_formValues['membership_type_of_contact'];
	
	
		 $tmp_membership_sql_list = $searchTools->convertArrayToSqlString( $membership_types_of_con ) ;
		 if(strlen($tmp_membership_sql_list) > 0 ){
			$clauses[] = "memberships.membership_type_id IN (".$tmp_membership_sql_list.")" ;
			$clauses[] = "mem_status.is_current_member = '1'";
			$clauses[] = "mem_status.is_active = '1'";
	
			}
	
	
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
			*/
	
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
	}
	 
	function contactIDs( $offset = 0, $rowcount = 0, $sort = null, $returnSQL = false) {
		return $this->all( $offset, $rowcount, $sort, false, true );
	}
	 
	 
	function &columns( ) {
		return $this->_columns;
	}
	
	 
	
	function summary( ) {
		 
		 
		 
		 
		 
	
	}
	 
	 
	}
	 
	 
	 
	