<?php

/**
 * A custom contact search
 */
class CRM_Financialsummaries_Form_Search_contribswithpricesets 
extends CRM_Contact_Form_Search_Custom_Base
implements CRM_Contact_Form_Search_Interface {

	
  
  protected $_allChosenPricesetOptions = null;
  
  protected $_tableName = null;
  
  protected $columns_for_temp_table = null;
  protected $_userChoices = null;
  protected $_layoutChoice = null;
  
  protected $_listitem_names = null;
  
  protected $_all_column_names = null;
  
  public $_permissionedComponent;
  
  function __construct( &$formValues ) {
  	parent::__construct( $formValues );
  
  
  	// define component access permission needed
  	$this->_permissionedComponent = 'CiviContribute';
  
  
  	$tmp_all_priceset_options = array();
  
  	if(is_array($this->_userChoices)){
  		foreach ($this->_userChoices as $dontCare => $curUserChoice ) {
  			$tmp_cur = split('_' ,$curUserChoice );
  			$tmp_all_events[] = $tmp_cur[0];
  			$tmp_all_priceset_options[] = $tmp_cur[1];
  		}
  	}
  
  
  
  	$this->_allChosenPricesetOptions = $tmp_all_priceset_options;
  
  	if(isset($this->_formValues['layout_choice'])){
	  	$this->_layoutChoice = $this->_formValues['layout_choice'] ;
  	}else{
  		$this->_layoutChoice = ""; 
  	}
  
  	$this->setColumns( );
  	
  
  
  }
  
  function __destruct( ) {
  
  }
  
  
  /****************************************************************************************************************/
  
  
  function priceSetDAO( $eventID = null ) {
  
  	// get all the events that have a price set associated with it
  	$sql = "
SELECT e.id    as id,
       e.title as title,
       e.start_date as start_date,
       p.price_set_id as price_set_id
FROM   civicrm_event      e,
       civicrm_price_set_entity  p
WHERE  p.entity_table = 'civicrm_event'
AND    p.entity_id    = e.id
";
  
  
  	if(count($this->_allChosenEvents ) > 0 ){
  	 // user has already picked some events, they cannot make a change.
  		$i = 1;
  		foreach($this->_allChosenEvents as $cur_eid){
  	 	$sql_eid_list = $sql_eid_list.$cur_eid;
  	 	if($i < count($this->_allChosenEvents ) ){
  	 		$sql_eid_list = $sql_eid_list.", ";
  	 		 
  	 	}
  	 	 
  	 	$i = $i + 1;
  	 	 
  	 }
  
  	 $sql = $sql." AND e.id IN ( ".$sql_eid_list.") ";
  	}
  
  	$sql .= " ORDER BY e.start_date desc";
  
  	//print "<Br>About to execute sql: ".$sql;
  	$params = array( );
  	$dao = CRM_Core_DAO::executeQuery( $sql,
  			$params );
  	return $dao;
  }
  
  function buildForm( &$form ) {
  	// $dao = $this->priceSetDAO( );
  
  	/*
  	 $event = array( );
  	 while ( $dao->fetch( ) ) {
  	 $event[$dao->id] = $dao->title.' at '.$dao->start_date;
  	 }
  
  	 if ( empty( $event ) ) {
  	 CRM_Core_Error::fatal( ts( 'There are no events with Price Sets' ) );
  	 }
  
  
  
  	 $tmpEventIds = array();
  	 */
  
  	/*   Create a select list of the various events that use price sets
  		$dao = $this->priceSetDAO( );
  
  		 
  		while ( $dao->fetch( ) ) {
  		$cur_event_id = $dao->id;
  		$cur_event_label = $dao->title.' at '.$dao->start_date;
  		// TODO: Finish testing
  		//$tmpPriceSetOptions['event_id_'.$cur_event_id] = '---- Select an option for '.$cur_event_label.' ----'  ;
  		//$tmpPriceSetOptions[$cur_event_id] = '---- Select an option for '.$cur_event_label.' ----'  ;
  		$tmpEventIds[$cur_event_id] = '--'.$cur_event_label.' --'  ;
  
  
  
  		}
  		$dao->free();
  
  		*/
  
  	/////
  
  	$tmp_ps_lineitems = array();
  	$sql = "SELECT li.id as lineitem_id , li.label as lineitem_label ,e.id as event_id ,  e.title as event_title, e.start_date as event_start_date
		FROM civicrm_line_item li LEFT JOIN civicrm_participant p on li.entity_id = p.id
		LEFT JOIN civicrm_event e ON p.event_id = e.id
		WHERE entity_table = 'civicrm_participant'
		AND p.is_test  <> 1
		group by e.id, li.label
		ORDER BY e.start_date desc, li.label ";
  
  	$params = array( );
  	$dao = CRM_Core_DAO::executeQuery( $sql,  $params );
  	while ( $dao->fetch( ) ) {
  		$cur_lineitem_id = $dao->lineitem_id ;
  		$cur_event_id = $dao->event_id;
  		$cur_event_label = $dao->event_title.' at '.$dao->event_start_date.' --- priceset item: '.$dao->lineitem_label;
  		$tmp_key = $cur_event_id."_".$dao->lineitem_label;
  		$tmp_ps_lineitems[$tmp_key] = $cur_event_label;
  	}
  	$dao->free();
  
  
  	/*
  	 $form->add('select', 'event_id', ts('Event(s)'), $tmpEventIds, TRUE,
  	 array('id' => 'event_id', 'multiple' => 'multiple', 'title' => ts('-- select --'))
  	 );
  	  
  	  
  	 if(count($this->_allChosenEvents ) > 0 ){
  
  	 $line_item_choices = self::getLineItemChoicesForEvents( );
  	 $form->add('select', 'lineitem_id', ts('Line Item(s)'), $line_item_choices, FALSE,
  	 array('id' => 'lineitem_id', 'multiple' => 'multiple', 'title' => ts('-- select --'))
  	 );
  	  
  	 }
  
  	 */
  	// 'lineitem_id'
  	// li_pricefieldID_valueID = 'valueID'
  	$select2style = array(
  			'multiple' => TRUE,
  			'style' => 'width:100%; max-width: 100em;',
  			'class' => 'crm-select2',
  			'placeholder' => ts('- select -'),
  	);
  	
  
  	 
  	$layout_options = array();
  	//   $layout_options['detail_broad'] = "Participant Detail (one row per participant, extra columns for each line item)";
  	$layout_options['detail'] = "Contribution Line Item Detail (one row per line item)";
  	//    $layout_options['summary'] = "Summarized";
  
  	$layout_select = $form->add( 'select',
  			'layout_choice',
  			ts( 'Layout Choice' ),
  			$layout_options,
  			false );
  
  	 
  
  
  
  	$this->util_get_all_column_names_to_display();
  	$tmp_all_columns = $this->_all_column_names;
  	 
  	 
  	$form->add('select', 'user_columns_to_display', ts('Columns to Display'), $tmp_all_columns, FALSE,
  			array('id' => 'user_columns_to_display', 'multiple' => 'multiple', 'title' => ts('-- select --'))
  			);
  
  	$form->addDate('start_date', ts('Contribution Date From'), false, array( 'formatType' => 'custom' ) );
  
  	$form->addDate('end_date', ts('...Through'), false, array( 'formatType' => 'custom' ) );
  
  
  	$form->addDate('age_date', ts('Age Based on Date'), false, array( 'formatType' => 'custom' ) );
  
  
  	// Create filter on price set id
  	$all_price_sets = array();
  	$sql_ps = "select id, if( length(title), title, '(No Title) ') as title FROM civicrm_price_set ORDER BY title";
  	$params = array( );
  	$dao = CRM_Core_DAO::executeQuery( $sql_ps,  $params );
  	while ( $dao->fetch( ) ) {
  		$ps_id_tmp  = $dao->id;
  		$ps_title_tmp  = $dao->title;
  		$all_price_sets[$ps_id_tmp] = $ps_title_tmp  ;
  
  	}
  	$dao->free();
  
  	
  	
  	$form->add('select', 'price_set_ids',
  			ts('Price Set(s)'),
  			$all_price_sets,
  			FALSE,
  			$select2style
  			);
  	
  	
  	 /*
  	$form->add('select', 'price_set_ids', ts('Price Sets'), $all_price_sets, FALSE,
  			array('id' => 'price_set_ids', 'multiple' => 'multiple', 'title' => ts('-- select --'))
  			);
  */
  
  	// Create filter on price set field value ids
  	$all_price_set_values = array();
  	$sql_pv = "select v.id as value_id, v.label as value_label, f.label as field_label,  if( length(pset.title), pset.title, '(No Title) ') as pset_title
        FROM civicrm_price_field_value v
        	LEFT JOIN civicrm_price_field f ON v.price_field_id  = f.id
        	LEFT JOIN civicrm_price_set pset ON f.price_set_id = pset.id
        	 ORDER BY pset.title, f.label, v.label";
  	$params = array( );
  	$dao = CRM_Core_DAO::executeQuery( $sql_pv,  $params );
  	while ( $dao->fetch( ) ) {
  		$value_id  = $dao->value_id;
  		$value_label  = $dao->value_label;
  		$field_label  = $dao->field_label;
  		$set_label = $dao->pset_title ;
  		 
  		$all_price_set_values[$value_id] = $set_label." : ".$field_label." : ".$value_label  ;
  
  	}
  	$dao->free();
  
  	$form->add('select', 'price_value_ids',
  			ts('Price Set Value(s)'),
  			$all_price_set_values,
  			FALSE,
  			$select2style
  			);
  	
  	/* 
  	$form->add('select', 'price_value_ids', ts('Price Set Values'), $all_price_set_values, FALSE,
  			array('id' => 'price_value_ids', 'multiple' => 'multiple', 'title' => ts('-- select --'))
  			);
  			*/
  
  	/**
  	 * You can define a custom title for the search form
  	 */
  	$this->setTitle('Find Completed Contributions by Line Item');
  	 
  	/**
  	 * if you are using the standard template, this array tells the template what elements
  	 * are part of the search criteria
  	 */
  	 
  
  	// 'user_columns_to_display'
  	$form->assign( 'elements', array( 'price_set_ids',  'price_value_ids', 'start_date', 'end_date',  'age_date',   'layout_choice'  ) );
  	 
  	 
  }
  
  
  /*********************************************************************************************/
  
  function setColumns( ) {
  
  	if($this->_layoutChoice == 'summary'){
  		$this->_columns = array(
  				//ts('Count') => 'rec_count',
  				ts('Total Quantity') => 'total_qty',
  				ts('Total Amount') => 'total_amount',
  				//	ts('Actual Participant Count') => 'actual_participant_count',
  	  	ts('Unit Price') => 'unit_price',
  				ts('Label') => 'label',
  				ts('Currency') => 'currency',
  				ts('Event Title') => 'event_title',
  				ts('Event Start Date') => 'event_start_date'
  		);
  		 
  		 
  		 
  		 
  	}else if($this->_layoutChoice == 'detail'){
  		$this->_columns = array(
  				// ts('Price Set ID' ) => 'price_set_id',
  				ts('Price Set Title') => 'price_set_title',
  				ts('Item')  => 'label',
  				ts('Contrib. ID') => 'contrib_id',
  				ts('Financial Type' ) => 'financial_type_name',
  				ts('Total Amount')    => 'line_total',
  				//  ts('Entity Type') => 'entity_type',
  				ts('Contrib. Date') => 'contrib_date',
  				ts('Contrib. Source' ) => 'contrib_source',
  				ts('Contrib. Contact Name') => 'contrib_sort_name',
  				ts('Contrib. Contact ID') => 'contrib_contact_id' ,
  				 
  				ts('Part. Contact Id')      => 'contact_id'    ,
  				ts('Part. Id')	=> 'participant_id',
  				ts('Click to View') => 'participant_link',
  				ts('Line Item Id')	=> 'line_item_id',
  				// ts('Registered by' )  => 'registered_by_name',
  				ts('Participant Name')            => 'display_name' ,
  				//   ts('First Name')  => 'first_name',
  				//   ts('Last Name') => 'last_name',
  				ts('Participant Age') => 'age',
  
  
  				 
  				ts('Quantity')	       => 'qty',
  				ts('Unit Price')	=> 'unit_price',
  				ts('Register Date')    => 'register_date',
  				ts('Event Title')	=> 'event_title',
  				ts('Event Start Date')  => 'event_start_date',
  				ts('Part. Membership Type') => 'membership_type',
  				ts('Part. Membership Status') => 'membership_status',
  				 
  				//  ts('Number of Memberships') => 'num_memberships',
  				ts('Part. Email') 	       => 'email',
  				ts('Part. Phone')	       => 'phone',
  				ts('Part. Address' )	=> 'street_address',
  				ts('Part. Address line 1') => 'supplemental_address_1',
  				ts('Part.  City') 		=> 'city',
  				ts('Part. State') =>  'state',
  				ts('Part. Postal Code') => 'postal_code',
  
  		);
  		 
  		 
  		//     print_r($this->_columns);
  		 
  	}/*else if($this->_layoutChoice == 'detail_broad'){
  	 
  	$user_columns_to_display =   $this->_formValues['user_columns_to_display'] ;
  	$all_columns_to_display =  $this->util_get_all_column_names_to_display();
  
  	$check_columns_to_display = false;
  	if($user_columns_to_display.is_array() && count($user_columns_to_display) > 0 ){
  	$check_columns_to_display = true;
  	}
  	 
  	$tmp = array();
  	if( !($check_columns_to_display)){
  	// always show all columns.
  	while ($cur_col_label = current($all_columns_to_display )) {
  	$cur_col_name =  key($all_columns_to_display);
  	$tmp[$cur_col_label] =  $cur_col_name ;
  	 
  	next($all_columns_to_display);
  
  	}
  	 
  	}else if( $check_columns_to_display ){
  	// Only show column if the user selected it.
  	while ($cur_col_label = current($all_columns_to_display )) {
  	$cur_col_name =  key($all_columns_to_display);
  	if(  in_array( $cur_col_name,  $user_columns_to_display, true) ){
  	$tmp[$cur_col_label] = $cur_col_name;
  	}
  	 
  	next($all_columns_to_display);
  
  	}
  	}
  
  
  
  	$this->_columns  = $tmp;
  
  
  	}
  
  	*/
  	 
  	/*  $this->columns_for_temp_table  =  array( ts('Contact Id')      => 'contact_id'    ,
  	 ts('Participant Id' ) => 'participant_id'
  	 );
  	*/
  	 
  	   
  	     
  
  	/*
  	  
  	if( $this->_eventID == 'event'){
  	return;
  	}
  
  	 
  
  	if ( ! is_array($this->_allChosenEvents) ) {
  	return;
  	}
  
  
  	//  Loop through each event selected by user
  	foreach($this->_allChosenEvents as $tmpEventId){
  	//print "<hr> Inside loop of event ids";
  
  
  	// for the selected event, find the price set and all the columns associated with it.
  	// create a column for each field and option group within it
  
  	if( $tmpEventId == "event"){
  	continue;
  	}
  	$dao = $this->priceSetDAO( $tmpEventId );
  
  	if ( $dao->fetch( ) &&
  	! $dao->price_set_id ) {
  	CRM_Core_Error::fatal( ts( 'There are no events with Price Sets' ) );
  	}
  
  
  	// get all the fields and all the option values associated with it
  	require_once 'CRM/Price/BAO/Set.php';
  	$priceSet = CRM_Price_BAO_Set::getSetDetail( $dao->price_set_id );
  
  
  
  	if ( is_array( $priceSet[$dao->price_set_id] ) ) {
  
  	//  print "<br>" ;
  	//  print_r($priceSet[$dao->price_set_id]) ;
  	foreach ( $priceSet[$dao->price_set_id]['fields'] as $key => $value ) {
  	// print "<hr><br>";
  	if ( is_array( $value['options'] ) ) {
  
  	foreach ( $value['options'] as $oKey => $oValue ) {
  	//print "<br><br>Current oValue: <br>";
  	//print_r($oValue);
  	if($oValue['id'] == $tmp_priceset_id ){
  	// print "<br>We have a match";
  	// $columnHeader = CRM_Utils_Array::value( 'label', $oValue );
  	$columnHeader = "price_field_".$oValue['id'] ;
  	//$this->_columns[$columnHeader] = $columnHeader ;
  	 
  	if ( CRM_Utils_Array::value( 'html_type', $value) != 'Text' ) $columnHeader .= ' - '. $oValue['label'];
  
  
  	}
  	$columnHeader = "price_field_".$oValue['id'] ;
  	// print "<Br>columnHeader for tempTable:  ".$columnHeader;
  	$this->columns_for_temp_table[$columnHeader] = $columnHeader ;
  	 
  	//$this->_columns[$columnHeader] = "price_field_{$oValue['id']}";
  
  
  
  	}
  	}
  	}
  
  
  	// Get priceset field options for "orphaned" options, meaning that a contribution record came in before an admin removed this option.
  	$tmp_sql = "SELECT distinct price_field_id , price_field_value_id, label FROM `civicrm_line_item`";
  	$dao_options  = CRM_Core_DAO::executeQuery( $tmp_sql );
  	while($dao_options->fetch( )){
  	$tmp_price_field_value_id = $dao_options->price_field_value_id;
  	$columnHeader = "price_field_".$tmp_price_field_value_id ;
  
  	$this->columns_for_temp_table[$columnHeader] = $columnHeader ;
  	 
  	}
  
  	$dao_options->free();
  
  	}
  
  	}
  	*/
  }
  
  
  function XXXgetLineItemChoicesForEvents(){
  
  
  	$tmp_choices = array();
  	$parms = array();
  	 
  	 
  	 
  	$sql = self::util_get_priceset_lineitems_list_sql();
  	$dao =    CRM_Core_DAO::executeQuery(  $sql , $parms);
  	while($dao->fetch( )){
  		// distinct(pf.id) as priceset_field_id, pf.name as priceset_field_name,
  		// pf.label as priceset_field_label, li.label as line_item_name, price_field_value_id as price_field_value_id
  		$field_label = $dao->priceset_field_label;
  		$item_label =  $dao->line_item_name ;
  		 
  		 
  		//print "<br><br>field label: ".$field_label." <br>item label: ".$item_label;
  		if($field_label == $item_label){
  			$tmp_label = $field_label;
  
  		}else{
  			$tmp_label =  $field_label.' --- '.$item_label ;
  			 
  		}
  		 
  		$field_id = $dao->priceset_field_id ;
  		$item_id = $dao->price_field_value_id;
  		 
  		 
  		$tmp_id = "li_".$field_id."_".$item_id;
  		$tmp_choices[$tmp_id] = substr( $tmp_label, 0, 100) ;
  		 
  	}
  	$dao->free();
  	 
  	// print_r( $tmp_choices);
  	return $tmp_choices;
  
  }
  function util_escape_name_for_sql(&$rawstr){
  
  	$clean_str ="";
  	 
  	$remove = array(' ', '-', '/', '(', ')' , ':', '.', ';',  ',' , '\\', '\'', '&', '%', '@', '#', '^', '*', '!', '=', '+', '<', '>', '?', '~', '`', '|', '[', ']', '{', '}' );
  	$clean_str = str_replace($remove, '_',  $rawstr );
  	 
  
  	return $clean_str;
  
  }
  
  
  
  
  function util_get_priceset_lineitems_list_sql(){
  
  	$li_where = self::getListItemWhere();
  
  	$tmp_sql =   "SELECT distinct(pf.id) as priceset_field_id, pf.name as priceset_field_name, pf.label as priceset_field_label,
    	                      li.label as line_item_name, price_field_value_id as price_field_value_id
         			 FROM civicrm_participant p
				 LEFT JOIN civicrm_line_item li ON p.id = li.entity_id
				 AND li.entity_table = 'civicrm_participant'
				 LEFT JOIN civicrm_event e ON p.event_id = e.id
				 LEFT JOIN civicrm_price_field pf ON li.price_field_id = pf.id  ".$li_where.
  				 "GROUP BY pf.id, price_field_value_id
				  ORDER BY pf.id , li.label";
  
  
  	//print "<br><br>Price set sql: ".$tmp_sql;
  	return $tmp_sql;
  
  
  
  
  
  }
  
  function util_get_custom_field_name_list_for_display(){
  	$cf_names = array();
  	$cf_name_sql  = "SELECT cg.title as table_label, cg.table_name as table_name, cf.column_name as column_name, cf.label as label
				FROM civicrm_custom_group cg LEFT JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id
				WHERE cg.extends = 'Participant' and cf.name is NOT NULL";
  
  	$parms = array();
  	$names = array();
  	//  print "<br>custom field sql: ". $cf_name_sql ;
  	$dao =    CRM_Core_DAO::executeQuery(  $cf_name_sql , $parms);
  	while($dao->fetch( )){
  		$cur_table_name = $dao->table_name;
  		$cur_table_label = $dao->table_label;
  		$cur_field_name  = $dao->column_name ;
  		$cur_field_label = $dao->label;
  		$names[$cur_field_name] = $cur_table_label."::".$cur_field_label;
  		 
  	}
  	$dao->free();
  
  	 
  
  	return $names;
  
  
  
  }
  
  
  function util_get_custom_field_name_list_for_select(){
  	$cf_names = array();
  	$cf_name_sql  = "SELECT cg.table_name as table_name, cf.column_name as name
				FROM civicrm_custom_group cg LEFT JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id
				WHERE cg.extends = 'Participant' and cf.name is NOT NULL";
  
  	$parms = array();
  	$sql = "";
  	//  print "<br>custom field sql: ". $cf_name_sql ;
  	$dao =    CRM_Core_DAO::executeQuery(  $cf_name_sql , $parms);
  	while($dao->fetch( )){
  		$cur_table_name = $dao->table_name;
  		$cur_field_name  = $dao->name ;
  		$sql =  $sql." ".$cur_table_name.".".$cur_field_name." , ";
  		 
  	}
  	$dao->free();
  
  	 
  
  	return $sql;
  
  
  
  }
  
  
  function util_get_custom_field_sql(){
  
  	$cf_table_names = array();
  	$cf_table_name_sql  = "SELECT table_name
				FROM civicrm_custom_group cg WHERE extends = 'Participant'";
  
  	$parms = array();
  	$dao =    CRM_Core_DAO::executeQuery(  $cf_table_name_sql , $parms);
  	while($dao->fetch( )){
  		$cur_table_name = $dao->table_name;
  		$cf_table_names[] = $cur_table_name;
  		 
  	}
  	$dao->free();
  
  	// now we have a nice array of table names for all custom field sets used for participants.
  	$sql = "";
  	foreach( $cf_table_names as $cur_table){
  		$sql = $sql." LEFT JOIN ".$cur_table." ON p.id = ".$cur_table.".entity_id ";
  
  	}
  
  	return $sql;
  	 
  }
  
  
  function util_get_all_column_names_to_display(){
  	$tmp_all_column_names = array();
  
  
  	$tmp_all_column_names['contact_id'] =  'CID';
  	$tmp_all_column_names['participant_id'] =  'PID';
  	$tmp_all_column_names['participant_link'] =  'Link';
  	$tmp_all_column_names['display_name'] =  'Display Name';
  	$tmp_all_column_names['first_name'] =  'First Name';
  	$tmp_all_column_names['last_name'] = 'Last Name' ;
  	$tmp_all_column_names['age'] = 'Age' ;
  	$tmp_all_column_names['email'] =  'Email';
  	$tmp_all_column_names['phone'] =  'Phone';
  	$tmp_all_column_names['street_address'] =  'Street Address';
  	$tmp_all_column_names['supplemental_address_1'] =  'Supplemental Address 1';
  	$tmp_all_column_names['city'] =  'City';
  	$tmp_all_column_names['postal_code'] =  'Postal Code';
  	$tmp_all_column_names['state'] =  'State';
  	$tmp_all_column_names['registered_by_name'] =  'Registered by';
  	$tmp_all_column_names['register_date'] = 'Register Date';
  	$tmp_all_column_names['event_title'] = 'Event Title' ;
  	$tmp_all_column_names['event_start_date'] = 'Event Start Date' ;
  	$tmp_all_column_names['membership_type'] = 'Membership Type';
  	$tmp_all_column_names['membership_status'] = 'Membership Status';
  	$tmp_all_column_names['price_set_id'] = 'Price Set ID';
  	$tmp_all_column_names['price_set_title'] = 'Price Set Title';
  	$tmp_all_column_names['contrib_id'] = 'Contrib. ID';
  	$tmp_all_column_names['entity_type'] = 'Entity Type';
  
  	// $tmp_all_column_names['currency'] = 'Currency';
  
  
  	$sql_li_name_sql = $this->util_get_priceset_lineitems_list_sql();
  		
  	$params = array();
  	$li_names_dao = CRM_Core_DAO::executeQuery( $sql_li_name_sql, $params );
  	$li_names = array();
  	$li_select = "";
  	$li_from = "";
  
  	$i = 1;
  	while($li_names_dao->fetch()){
  		$cur_name = $li_names_dao->line_item_name;
  		$priceset_field_id = $li_names_dao->priceset_field_id;
  		$priceset_field_name = $li_names_dao->priceset_field_name;
  		$priceset_field_label = $li_names_dao->priceset_field_label;
  		$priceset_field_value_id = $li_names_dao->price_field_value_id;
  		 
  		if(strlen($cur_name) == 0){
  			$cur_name = "blank";
  		}
  		 
  		 
  		 
  		 
  		 
  		$col_label = "";
  		if($priceset_field_label <> $cur_name ){
  			$col_label = $priceset_field_label.' - '.$cur_name.' Qty' ;
  		}else{
  			$col_label = $cur_name.' Qty' ;
  		}
  
  
  		$cur_table_name = "li_".$priceset_field_id."_".$priceset_field_value_id;
  		$cur_col_name = $cur_table_name.'_qty';
  		 
  		 
  
  		$tmp_all_column_names[$cur_col_name] = $col_label;
  		 
  		 
  
  	}
  	$li_names_dao->free();
  
  
  	// Add colums for each custom data field.
  	$cf_names = $this->util_get_custom_field_name_list_for_display();
  	while ($cf_col_label = current($cf_names )) {
  		$cur_col_name =  key($cf_names);
  		$tmp_all_column_names[$cur_col_name] = $cf_col_label;
  
  		next($cf_names);
  		 
  	}
  
  
  
  	$this->_all_column_names = 	$tmp_all_column_names;
  	return $this->_all_column_names;
  
  
  	//   print "<br><br>All columns to display: ";
  	//   print_r( $this->_all_column_names );
  
  
  
  }
  
  function util_get_priceset_field_options( $event_id_parm ) {
  	$tmp_priceset_field_option_labels =  array();
  	$tmp_priceset_field_option_values =  array();
  
  	$dao = $this->priceSetDAO( $event_id_parm );
  
  	if ( $dao->fetch( ) && ! $dao->price_set_id ) {
  		CRM_Core_Error::fatal( ts( 'There are no events with Price Sets' ) );
  	}
  	 
  	 
  	// get all the fields and all the option values associated with it
  	require_once 'CRM/Price/BAO/Set.php';
  	$priceSet = CRM_Price_BAO_Set::getSetDetail( $dao->price_set_id );
  
  	return $priceSet;
  	 
  	 
  }
  
  function select($sum_flag){
  
  	if($sum_flag == 'sum_only'){
  		$tmp_select =	" count( distinct p.id  ) as rec_count,
sum(li.qty) as total_qty, min(li.unit_price) as min_unit_price, max(li.unit_price) as max_unit_price, avg(li.unit_price) as avg_unit_price,
sum(li.line_total) as total_amount ,
sum(li.participant_count) as actual_participant_count, li.label, e.currency as currency,
 e.title as event_title, e.start_date as event_start_date
 ";
  
  	}else{
  		print "<br> Todo";
  
  
  	}
  
  	return 	$tmp_select;
  
  
  
  }
   
  
   
  function all( $offset = 0, $rowcount = 0, $sort = null,
  		$includeContactIDs = false,  $onlyIDs = false ) {
  			 
  			 
  			$this->util_get_all_column_names_to_display();
  			 
  			$tmp_full_sql = '';
  			 
  			$where = $this->where();
  			 
  			 
  			$ageDate = CRM_Utils_Date::processDate( $this->_formValues['age_date'] );
  			if ( $ageDate ) {
  				$yyyy = substr( $ageDate , 0, 4);
  				$mm = substr( $ageDate , 4, 2);
  				$dd = substr( $ageDate , 6, 2);
  				 
  				$tmp = $yyyy."-".$mm."-".$dd ;
  				$age_cutoff_date =  "'".$tmp."'";
  			}else{
  				$age_cutoff_date = "now()";
  				 
  			}
  			 
  			$tmp_age_calc = "((date_format($age_cutoff_date,'%Y') - date_format(contact_a.birth_date,'%Y')) -
  			(date_format($age_cutoff_date,'00-%m-%d') < date_format(contact_a.birth_date,'00-%m-%d'))) as age, ";
  
  			 
  			if($this->_layoutChoice == 'summary'){
  				$grand_totals = true;
  				$totalSelect = " count( p.id  ) as rec_count,  pf.name as priceset_field_name, pf.label as priceset_field_label,
  sum(li.qty) as total_qty  , li.unit_price,  sum( li.line_total) as total_amount  ,
li.participant_count, if( pf.label <> li.label,  concat(pf.label, ' - ', li.label), li.label) as label ,e.currency as currency,
 e.title as event_title, e.start_date as event_start_date
 ";
  
  
  				$from = " FROM
civicrm_participant p
LEFT JOIN civicrm_line_item li ON p.id = li.entity_id
AND li.entity_table = 'civicrm_participant'
LEFT JOIN civicrm_event e ON p.event_id = e.id
JOIN civicrm_contact contact_a on p.contact_id = contact_a.id
LEFT JOIN civicrm_price_field pf ON li.price_field_id = pf.id ";
  
  				$where = $this->where();
  				//$groupBy = "GROUP BY li.price_field_id, li.price_field_value_id , e.title, e.start_date";
  				$groupBy = " GROUP BY li.price_field_id, li.price_field_value_id,  e.title, e.start_date ";
  
  				$inner_sql = "select ".$totalSelect." ".$from." WHERE ".$where.$groupBy;
  
  				/* $tmp_full_sql =   $this->sql(  $totalSelect,
  				 $offset, $rowcount, $sort,
  				 $includeContactIDs, $groupBy );
  				*/
  				 
  				 
  				$tmp_full_sql  = "select ".$totalSelect.$from." WHERE ".$where.$groupBy;
  				// $tmp_full_sql = "select sum(t1.qty) as total_qty, sum(t1.line_total) as total_amount,  t1.* FROM ( ".$inner_sql."  ) as t1";
  				//   " GROUP BY t1.price_field_id, t1.price_field_value_id , t1.title, t1.start_date ";
  				//print "<br><br>summary sql:  ".$tmp_full_sql;
  				 
  			}else if($this->_layoutChoice == 'detail'){
  				$selectClause = " contact_a.id            as contact_id  , p.id as participant_id, '' as participant_link,
contact_a.sort_name   as display_name, contact_a.first_name, contact_a.last_name,
civicrm_email.email as email, civicrm_phone.phone as phone, civicrm_address.street_address as street_address,
civicrm_address.supplemental_address_1 as supplemental_address_1, civicrm_address.city as city ,civicrm_address.postal_code as postal_code,
civicrm_state_province.abbreviation as state, p.registered_by_id, contact_b.sort_name as registered_by_name,
li.id as line_item_id,
li.qty, li.unit_price, li.line_total as line_total, li.participant_count, if( pf.label <> li.label,  concat(pf.label, ' - ', li.label), li.label) as label,
e.currency as currency, ".$tmp_age_calc."
p.register_date, e.title as event_title, e.start_date as event_start_date,
mt.name as membership_type, ms.label as membership_status,
pset.id as price_set_id , pset.title as price_set_title,
main_contrib.id as contrib_id,
main_contrib.contact_id as contrib_contact_id ,
date( main_contrib.receive_date ) as contrib_date,
contrib_contact.sort_name as contrib_sort_name,
li.entity_table as entity_type,
main_contrib.source as contrib_source,
fin_type.name as financial_type_name
 ";
  
  				// mt.name as membership_type, ms.label as membership_status, count(m.id) as num_memberships
  				 
  				$groupBy = " group by li.id";
  				$tmp_full_sql =  $this->sql( $selectClause,
  						$offset, $rowcount, $sort,
  						$includeContactIDs, $groupBy );
  				 
  				 
  			}else{
  				print "<br><br>Unrecognized layout choice: ".$this->_layoutChoice;
  				 
  			}
  			 
  			 
  
  
  			// 	print "<br><br> all column names:";
  			// 	print_r($this->_all_column_names);
  			//  print "<br><br>full sql: ".$tmp_full_sql;
  			 
  			return $tmp_full_sql;
  			 
  			 
  
  }
  
  function from( ) {
  	//  print "<br>Inside from function ";
  
  	
  	$financial_type_sql = " LEFT JOIN civicrm_financial_type fin_type ON li.financial_type_id = fin_type.id ";
  	
  
  	return " FROM
 civicrm_line_item li LEFT JOIN civicrm_participant p ON p.id = li.entity_id AND li.entity_table = 'civicrm_participant'
LEFT JOIN civicrm_event e ON p.event_id = e.id
Left  JOIN civicrm_participant p2 on p.registered_by_id = p2.id
LEFT JOIN civicrm_contact contact_b on p2.contact_id = contact_b.id
LefT JOIN civicrm_contact contact_a on p.contact_id = contact_a.id
  left join civicrm_membership m on contact_a.id = m.contact_id
left join civicrm_membership_type mt on m.membership_type_id = mt.id
left join civicrm_membership_status ms on m.status_id = ms.id
left join civicrm_email on contact_a.id = civicrm_email.contact_id AND (civicrm_email.is_primary = 1 OR civicrm_email.email is null)
left join civicrm_phone on contact_a.id = civicrm_phone.contact_id  AND (civicrm_phone.is_primary = 1 OR civicrm_phone.phone is null)
left join civicrm_address on contact_a.id = civicrm_address.contact_id AND (civicrm_address.is_primary = 1 OR civicrm_address.street_address is null)
left join civicrm_state_province on civicrm_address.state_province_id = civicrm_state_province.id AND (civicrm_state_province.abbreviation like '%' or civicrm_state_province.abbreviation is null)
LEFT JOIN civicrm_price_field pf ON li.price_field_id = pf.id
LEFT JOIN civicrm_price_set pset ON pf.price_set_id = pset.id
left join civicrm_contribution contrib ON contrib.id =  li.entity_id AND li.entity_table = 'civicrm_contribution'
left join civicrm_participant_payment pp ON ifnull( p.registered_by_id, p.id) = pp.participant_id
LEFT JOIN civicrm_contribution p_contrib ON pp.contribution_id = p_contrib.id
LEFT JOIN civicrm_contribution main_contrib ON main_contrib.id = CASE li.entity_table WHEN  'civicrm_contribution' THEN  li.entity_id WHEN 'civicrm_participant' THEN p_contrib.id ELSE  '' END
LEFT JOIN civicrm_contact contrib_contact ON main_contrib.contact_id  = contrib_contact.id
".$financial_type_sql;
  
  
  
  }
  
  
  
  function getListItemWhere(){
  	// print "<hr><br>Inside where function.";
  	// 'filter_type',  'priceset_option_id', 'priceset_option_id_lineitems'
  	$tmp_where = '';
  	$partial_sql = '';
  	
  	
  	if(isset( $this->_formValues['filter_type'] ) ){
  		$filter_type =  $this->_formValues['filter_type'] ;
  	}else{
  		$filter_type = "";
  	}
  	//  print "<br>Filter type: ".$filter_type;
  	if($filter_type == 'priceset_items'){
  
  		$tmp_lineitems_array = 	$this->_formValues['priceset_option_id_lineitems'] ;
  		//print_r($tmp_lineitems_array ) ;
  		if( ! is_array($tmp_lineitems_array) ){
  			return ;
  		}
  		 
  		$i = 1;
  		$tmp_lineitem_ids = '';
  		foreach( $tmp_lineitems_array as $cur_lineitem){
  			$tmp_lineitem_ids  = $tmp_lineitem_ids.$cur_lineitem;
  			if($i < sizeof( $tmp_lineitems_array)){
  				$tmp_lineitem_ids = $tmp_lineitem_ids.", ";
  			}
  
  			$i = $i + 1;
  		}
  		 
  		if(strlen($tmp_lineitem_ids) > 0 ){
  			$partial_sql = "li.id IN ( ".$tmp_lineitem_ids.")" ;
  
  			// $partial_sql = $partial_sql." AND "	;
  		}
  		 
  		 
  	}else{
  		 
  		 
  		 
  		//$tmp_priceset_id =  $this->_pricesetOptionId ;
  		// print_r( $this->_allChosenPricesetOptions) ;
  		if( ! is_array($this->_allChosenPricesetOptions)){
  			return;
  		}
  		 
  
  		$need_or = false;
  		$first_item = true;
  		 
  		if( isset( $this->_allChosenEvents ) && is_array( $this->_allChosenEvents )){ 
		  		foreach($this->_allChosenEvents as $curOption){
		  			// foreach($this->_allChosenPricesetOptions as $curOption){
		  			//	print "<br><br>cur option in where loop: ".$curOption;
		  			if ($curOption == 'id' ||  $curOption == 'event'|| (strlen($curOption) == 0)){
		  
		  				continue;
		  
		  			}
		  			if($first_item){
		  				$partial_sql = " ( ";
		  			}
		  			 
		  			if( $need_or){
		  				$partial_sql = $partial_sql." OR ";
		  			}
		  			// $tmp_fieldname = "price_field_".$curOption;
		  			 
		  			// $partial_sql = $partial_sql.$tmp_fieldname." > 0 ";
		  			$partial_sql = $partial_sql." e.id = ".$curOption;
		  			$first_item = false;
		  			 
		  			$need_or = true;
		  			 
		  			 
		  		}
  		
  		}
  		
  		if($need_or ){
  			$partial_sql = $partial_sql." )  ";
  		}
  		 
  	}
  
  
  	if(strlen($partial_sql) > 0){
  		$tmp_where = " WHERE ".$partial_sql;
  	}else{
  		$tmp_where = "";
  	}
  	 
  	return $tmp_where;
  
  
  }
  
  
  function where( $includeContactIDs = false, $summary_section = false ) {
  
  	//  print "<br>Inside where ";
  	$clauses = array();
  	$tmp_rtn = '';
  
  	//    'price_value_ids'
  	$price_set_ids = $this->_formValues['price_set_ids'] ;
  	if( is_array( $price_set_ids ) && count(  $price_set_ids ) > 0 ){
  		$tmp_sql_ps_ids = implode(",", $price_set_ids);
  		$clauses[]  =  " ( pset.id IN ( ".$tmp_sql_ps_ids."  ) ) ";
  
  	}
  
  	 
  	$price_value_ids = $this->_formValues['price_value_ids'] ;
  	if( is_array( $price_value_ids ) && count(  $price_value_ids ) > 0 ){
  		$tmp_sql_pv_ids = implode(",", $price_value_ids);
  		$clauses[]  =  " ( li.price_field_value_id IN ( ".$tmp_sql_pv_ids."  ) ) ";
  
  	}
  	 
  
  	$layout_choice =  $this->_formValues['layout_choice'];
  	
  	if( isset($this->_formValues['lineitem_id'] ) ){
  		$line_item_id =  $this->_formValues['lineitem_id'];
  	}else{
  		$line_item_id = "";
  	}
  	$tmp_li_sql = "";
  	// print_r($line_item_id);
  	if(is_array( $line_item_id ) && count( $line_item_id ) > 0 ){
  		 
  		if( $summary_section <> true     &&  $layout_choice == 'detail_broad'){
  			foreach( $line_item_id as $cur_li){
  				$tmp_split_id = explode('_', $cur_li );
  				$field_id = $tmp_split_id[1];
  				$value_id = $tmp_split_id[2];
  				 
  				if( strlen( $tmp_li_sql ) > 0){
  					$tmp_li_sql = $tmp_li_sql." OR ";
  				}
  
  				$tmp_li_sql =  $tmp_li_sql."  (".$cur_li.".id IS NOT NULL ) ";
  
  			}
  
  			if( strlen( $tmp_li_sql ) > 0){
  				$tmp_li_sql = " ( ".$tmp_li_sql." )";
  			}
  		}else{
  			foreach( $line_item_id as $cur_li){
  				$tmp_split_id = explode('_', $cur_li );
  				$field_id = $tmp_split_id[1];
  				$value_id = $tmp_split_id[2];
  				 
  				if( strlen( $tmp_li_sql ) > 0){
  					$tmp_li_sql = $tmp_li_sql." OR  ";
  				}
  
  				$tmp_li_sql =  $tmp_li_sql."  (li.price_field_id = '".$field_id."' AND li.price_field_value_id = '".$value_id."'  ) ";
  
  			}
  
  			if( strlen( $tmp_li_sql ) > 0){
  				$tmp_li_sql = "  ( ".$tmp_li_sql." )";
  			}
  			 
  			 
  			// li.price_field_id
  			 
  			 
  			 
  		}
  		 
  		$clauses[] = $tmp_li_sql ;
  	}
  	 
  	$startDate = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
  	if( $startDate ){
  		$clauses[]  =  " (date(main_contrib.receive_date) >= date( ".$startDate."))  ";
  	}
  	 
  	 
  	$endDate = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
  	if ( $endDate ) {
  		$clauses[] = " (date(main_contrib.receive_date) <= date( $endDate )) ";
  	}
  
  	$clauses[]  = " (p.id IS NULL OR p.is_test <> 1 ) " ;
  	$clauses[]  = " (contact_a.id IS NULL OR contact_a.is_deleted <> 1) ";
  	$clauses[] =  " (main_contrib.id IS NOT NULL AND main_contrib.id <> '' ) ";
  	$clauses[] =  " (main_contrib.is_test <> 1 ) ";
  	$clauses[] =  " (main_contrib.contribution_status_id = 1 )" ;
  	 
  	// Check if current user is restricted to certain contacts by ACLs.
  	$acl_sql_fragment  = CRM_Contact_BAO_Contact_Permission::cacheSubquery();
  	if( strlen( $acl_sql_fragment ) > 0 ){
  		$clauses[]  = " (contact_a.id ".$acl_sql_fragment." ) ";
  	
  	}
  	 
  	$tmp_rtn = implode( ' AND ', $clauses );
  	 
  
  	//   print "<br> where :".$tmp_rtn;
  	 
  	 
  	return $tmp_rtn;
  }
  
  function summaryxxx( ) {
  	 
  
  	$sum_array = array();
  
  	$grand_totals = true;
  	/*  SELECT count( distinct p.id ) as rec_count, pf.name as priceset_field_name, pf.label as priceset_field_label, sum(li.qty) as total_qty, min(li.unit_price) as min_unit_price, avg(li.unit_price) as avg_unit_price, sum(li.line_total) as total_amount , sum(li.participant_count) as actual_participant_count, if( pf.label <> li.label, concat(pf.label, ' - ', li.label), li.label) as label ,e.currency as currency, e.title as event_title, e.start_date as event_start_date
  
  	*/
  	$tmp_select =	"
sum(t1.total_qty) as total_qty,  t1.min_unit_price,  t1.avg_unit_price,
t1.total_amount ,
sum(t1.actual_participant_count) as actual_participant_count, t1.label, t1.currency as currency,
 t1.event_title, t1.event_start_date
 ";
  	 
  	$tmp_inner_sql = self::all();
  	$sql = "Select ". $tmp_select." from ( ".$tmp_inner_sql." ) as t1";
  	 
  	 
  	 
  	$totalSelect = $this->select('sum_only');
  	$from  = $this->from();
  	$where = $this->where(false, true);
  	$group_by = "e.currency, li.label , e.title, e.start_date";
  	 
  	$sql = "SELECT  $totalSelect
  	$from
  	WHERE $where ";
  
  	// GROUP BY $group_by";
  	 
  	 
  	 
  
  	// print "<br><br>Summary Section  sql: ".$sql;
  
  	$dao = CRM_Core_DAO::executeQuery( $sql,         CRM_Core_DAO::$_nullArray );
  
  	while ( $dao->fetch( ) ) {
  
  		$cur_sum = array();
  		if( $layout_choice == 'detail_broad'){
  			$cur_sum['Currency'] = $dao->currency;
  			$cur_sum['Event Title'] = $dao->event_title;
  			$cur_sum['Event Start Date'] = $dao->event_start_date;
  			 
  		}else{
  			//$cur_sum['Registration Count'] = $dao->rec_count;
  			$cur_sum['Total Quantity'] = $dao->total_qty;
  			$cur_sum['Total Amount'] = $dao->total_amount;
  			//$cur_sum['Actual Participant Count'] = $dao->actual_participant_count;
  			$cur_sum['Min. Unit Price'] = $dao->min_unit_price;
  			$cur_sum['Max. Unit Price'] = $dao->max_unit_price;
  			//$cur_sum['Avg. Unit Price'] = $dao->avg_unit_price;
  			//$cur_sum['Label'] = $dao->label;
  			$cur_sum['Currency'] = $dao->currency;
  			$cur_sum['Event Title'] = $dao->event_title;
  			$cur_sum['Event Start Date'] = $dao->event_start_date;
  		}
  	  
  		$sum_array[] = $cur_sum;
  
  	}
  	$dao->free();
  
  	return $sum_array;
  	 
  
  }
   
  function count( ) {
  	$sql = $this->all( );
  	 
  	//  print "<Br>Inside count, sql: ".$sql;
  	 
  	$dao = CRM_Core_DAO::executeQuery( $sql,
  			CRM_Core_DAO::$_nullArray );
  	return $dao->N;
  }
   
  function contactIDs( $offset = 0, $rowcount = 0, $sort = null,  $returnSQL = false) {
  	return $this->all( $offset, $rowcount, $sort, false, true );
  }
  
  function templateFile( ) {
  	return 'CRM/Contact/Form/Search/Custom.tpl';
  }
  
  function setDefaultValues( ) {
  	return array( );
  }
  
  function alterRow( &$row ) {
  
  	if( strlen( $row['participant_id'] ) > 0 ){
  		$row['participant_link'] = "<a href='/civicrm/contact/view/participant?reset=1&id=".$row['participant_id']."&cid=".$row['contact_id']."&action=view&context=participant&selectedChild=event'>View Participant</a>";
  		//	$participant_url =" /civicrm/contact/view/participant?reset=1&id=31&cid=176&action=view&context=participant&selectedChild=event";
  		 
  	}
  
  }
  
  function setTitle( $title ) {
  	if ( $title ) {
  		CRM_Utils_System::setTitle( $title );
  	} else {
  		CRM_Utils_System::setTitle(ts('Contributions by priceset'));
  	}
  }
  
}
  
