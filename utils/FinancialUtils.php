<?php

class FinancialUtils{
	function createPaymentInstrumentId(&$payment_instrument_label, &$fa_id ){
		$id_tmp = "";
		$params = array(
		  'version' => 3,
		  'sequential' => 1,
		  'name' => 'payment_instrument',
		);
		$result = civicrm_api('OptionGroup', 'get', $params);

		$option_group_id = $result['id'];
		//print "\n og id: ".$option_group_id;
		// "is_active","option_group_id","name","label","weight","value","is_default","is_optgroup","filter"

		$params = array(
				'version' => 3,
				'sequential' => 1,
				'option_group_id' => $option_group_id,
				'label' => $payment_instrument_label,
		);
		$result = civicrm_api('OptionValue', 'create', $params);
		$opt_id = $result['id'];
		if(strlen( $opt_id) > 0){
	  // link payment instrument to financial account
	  print "<br>\n Just created payment instrument: ".$payment_instrument_label;
	  $tmp = $this->verify_createLink_PaymentInstrumentToFinancialAccount($payment_instrument_label,  $opt_id  );
	   

		}


	}
	 
	 
	function verify_update_batch_mode_ids(){
		 
		$cleanup_needed = false;
		$params = array(
				'version' => 3,
				'sequential' => 1,
				'name' => 'batch_mode',
		);
		$result = civicrm_api('OptionGroup', 'get', $params);

		$og_id = $result['id'] ;
		 
		if( strlen( $og_id ) > 0){
			// get list of option group values for batch_mode
			$params = array(
					'version' => 3,
					'sequential' => 1,
					'option_group_id' => $og_id,
			);
			$result = civicrm_api('OptionValue', 'get', $params);
			$bs_options = $result['values'];
			foreach( $bs_options as $cur){
				if( $cur['value'] == "0"){
					$cleanup_needed = true;
					if( $cur['name'] == "Automatic Batch" ){
						$proper_value = "2";
					}else if( $cur['name'] == "Manual Batch" ){
						$proper_value = "1";
					}

					if( strlen( $proper_value ) > 0){
	   		print "<br>\nNeed to update value for: ".$cur['name'];
	   		// update the option value
	   		$params = array(
	   				'version' => 3,
	   				'sequential' => 1,
	   				'option_group_id' => $og_id,
	   				'id' => $cur['id'],
	   				'value' => $proper_value,
	   				'weight' => $proper_value,
	   				'description' => $cur['name'],
	   				'is_reserved' => 1,
	   		);
	   		$result = civicrm_api('OptionValue', 'create', $params);
					}
					 
				}



			}



		}

	}

	function verify_update_batch_status_ids( ) {
		 
		$cleanup_needed = false;
		$params = array(
				'version' => 3,
				'sequential' => 1,
				'name' => 'batch_status',
		);
		$result = civicrm_api('OptionGroup', 'get', $params);

		$og_id = $result['id'] ;
		 
		if( strlen( $og_id ) > 0){
			// get list of option group values for batch_status
			$params = array(
					'version' => 3,
					'sequential' => 1,
					'option_group_id' => $og_id,
			);
			$result = civicrm_api('OptionValue', 'get', $params);
			$bs_options = $result['values'];
			foreach( $bs_options as $cur){
				if( $cur['value'] == "0"){
					$cleanup_needed = true;
					if( $cur['name'] == "Data Entry" ){
						$proper_value = "3";
					}else if( $cur['name'] == "Exported" ){
						$proper_value = "5";
					}else if( $cur['name'] == "Reopened" ){
						$proper_value = "4";
					}

					if( strlen( $proper_value ) > 0){
	   		print "<br>\nNeed to update value for: ".$cur['name'];
	   		// update the option value
	   		$params = array(
	   				'version' => 3,
	   				'sequential' => 1,
	   				'option_group_id' => $og_id,
	   				'id' => $cur['id'],
	   				'value' => $proper_value,
	   				'weight' => $proper_value,
	   		);
	   		$result = civicrm_api('OptionValue', 'create', $params);
					}
					 
				}



			}

			if( $cleanup_needed ){
				print "<br>\n batch status needs cleanup";
	   // Set batches that used the invalid value of 0 to "exported"
	   $sql = "update civicrm_batch set status_id = 5
	   WHERE status_id = 0
	   AND type_id is null
	   AND mode_id is null" ;

	   $empty_arr = array();

	   $dao = CRM_Core_DAO::executeQuery( $sql, $empty_arr );
	   $dao->free();

			}

		}

	}
	 
	function verify_createLink_PaymentInstrumentToFinancialAccount( &$payment_instrument_label, &$opt_id  ){

		$id_tmp = "";
		$sql = "SELECT id FROM `civicrm_entity_financial_account`
		WHERE entity_table = 'civicrm_option_value'
		AND entity_id = $opt_id
		AND account_relationship = '6' ";

		$empty_arr = array();

		//  print "<br>\nLook for pi '$payment_instrument_label' relationship: ".$sql;
		$dao = CRM_Core_DAO::executeQuery( $sql, $empty_arr );

		if( $dao->fetch() ){
			$id_tmp = $dao->id ;

		}

		$dao->free();

		if(strlen( $id_tmp) == 0){
			// we need to create a relationship between this payment instrument and a financial account.
			print "<br>\n Need to create relationship between payment instrument and financial account.";

			print "<br>\nFind asset account with same name as: ".$payment_instrument_label;
		 $f_acct_type_id = "1"; // Asset type account
		 $f_acct_name = $payment_instrument_label;
		 $fa_id = $this->getFinancialAccountId($f_acct_name, $f_acct_type_id );

		 if( strlen($fa_id )== 0){
		 	print "<br>\nERROR: Could not find financial account with name: ".$f_acct_name;
		 	return 1 ;

		 }

		 $insert = "INSERT INTO civicrm_entity_financial_account ( entity_table, entity_id , account_relationship, financial_account_id )
		 VALUES ( 'civicrm_option_value' ,  $opt_id,  '6' , '$fa_id')   ";

		 $empty_arr = array();

		 $dao = CRM_Core_DAO::executeQuery( $insert, $empty_arr );

		 $dao->free();

		}


		return 0;
	}






	function getPaymentInstrumentId($payment_instrument_label){
		 
		//  print "\n<br><br>---Inside get pi label:".$payment_instrument_label;
		$id_tmp = "";
		$raw_option_id = "";
		$params = array(
		  'version' => 3,
		  'sequential' => 1,
		  'name' => 'payment_instrument',
		);
		$result = civicrm_api('OptionGroup', 'get', $params);

		$option_group_id = $result['id'];
		$params = array(
		  'version' => 3,
		  'sequential' => 1,
		  'option_group_id' => $option_group_id,

		);
		$result = civicrm_api('OptionValue', 'get', $params);
		//print "<br>ov result: <br> ";
		//print_r( $result);
		$pi_values = $result['values'];

		$all_payment_instruments = array();
		foreach($pi_values as $cur){
			$all_payment_instruments[$cur['value']] =  strtolower( $cur['label'] );
			if( strtolower( $cur['label'] ) ==  strtolower( $payment_instrument_label ) ){
				$id_tmp = $cur['value'] ;
				$raw_option_id = $cur['id'];
				$pi_label = $cur['label'];
				 
			}


		}

		if( strlen($id_tmp) >  0 ){
			// found payment instrument, check if there is an associated financial account.
			$this->verify_createLink_PaymentInstrumentToFinancialAccount( $pi_label,  $raw_option_id );

		}
		 
		return $id_tmp ;
		 
		 
	}
	 
	function getFinancialAccountId($f_acct_name, $f_acct_type_id ){
		// This is case-insensitive.

		$id_tmp = "";
		$ft_name = str_replace( "'", "''",  $ft_name) ;
		$sql = "select id from civicrm_financial_account
       		WHERE lower(name) = lower('".$f_acct_name."')
       		AND financial_account_type_id = '".$f_acct_type_id."'  ";

		 
		$empty_arr = array();

		$dao = CRM_Core_DAO::executeQuery( $sql, $empty_arr );

		if( $dao->fetch() ){
			$id_tmp = $dao->id ;

		}

		$dao->free();
		 
		return $id_tmp ;

	}
	 
	function getCustomDueByColHeading(){
		$custom_field_group_label = "Financial Token Preferences";
		$customFieldLabels = array();

		$custom_field_dueby_label = "Custom DueBy Column Heading ";
		$customFieldLabels[] = $custom_field_dueby_label;
		$outCustomColumnNames = array();

		require_once( 'util_custom_fields.php');
		$error_msg = getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, $sql_table_name, $outCustomColumnNames ) ;



		if(strlen( $error_msg) > 0){
			// print "<br>Configuration error: ".$error_msg;
			return "";


		}
		$sql_dueby_field  =  $outCustomColumnNames[$custom_field_dueby_label];
		$dueby_label = "";
		$sql = "Select
	".$sql_dueby_field." as dueby_label
	from civicrm_contact AS contact_a
	left join ".$sql_table_name." as tok_prefs on contact_a.id = tok_prefs.entity_id
	WHERE
	contact_a.contact_sub_type =  'Primary_Organization'
	order by contact_a.id ";


		// print "<br>sql: ".$sql;
		$dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;

		if ( $dao->fetch( ) ) {
			$dueby_label = $dao->dueby_label;
			// print "<br>due by label: ".$dueby_label;
		}


		$dao->free( );
		return $dueby_label;

	}


	 
	function getObligationsShowSubtotals(&$values, &$contactIDs, &$ct_prefix_id, &$cur_finances_token, &$output_wanted, &$include_closed_items, &$exclude_after_date, &$end_date_raw,  &$extra_where_clause, &$include_detailrows, &$include_cols ){
		$start_date = '' ;
		 
		require_once ('utils/FormattingUtils.php'  );
		$tmpFormattingUtils = new FormattingUtils();


		$end_date = $tmpFormattingUtils->getSqlDate($end_date_raw);
		 
		 
		 
		$need_subtotals = true;
		$need_due_column = true;
		$token_format = 'pdf_token';
		/*

		(  &$extra_where_clause )

		*/
			
		self::process_obligation_with_balances_subtotals_tokens2($values, $contactIDs, $ct_prefix_id,   $cur_finances_token,  $need_subtotals,  $token_format, $end_date, $start_date, $need_due_column, $exclude_after_date, $output_wanted, $include_closed_items, $extra_where_clause, $include_detailrows, $include_cols );



	}




	/**************************************************************/
	/*****************************************************************************************************/

	// This does NOT include automated recurring contributions, as this number is traditionally used
	// to let the person know how to make out a check to satisfy outstanding balances that are due before the end_date.
	function XXXgetAmountDue( &$values, &$contactIDs,  &$ct_prefix_id, &$token_to_fill, &$output_wanted, &$end_date_parm,&$start_date_parm,  &$include_automated_payments){
		print "<br>inside get amount due:";

		// &$values, &$contactIDs , &$token_long , &$token_short, &$end_date_parm,&$start_date_parm,  &$include_automated_payments )
		// print "<br>Inside getAmountDue, end_date_parm: ".$end_date_parm;
		if( count($contactIDs) == 0 ){
			// no contacts, nothing to do.
			return;
		}
		 
		require_once('RelationshipTools.php');
		$tmpRelTools = new RelationshipTools();
		$cid_list =  $tmpRelTools->get_contact_ids_for_sql($contactIDs) ;

		$tmp_contrib_where = '';
		$tmp_pledge_pay_where = '';
		if(strlen($end_date_parm) > 0 ){
			$tmp_contrib_where = " AND contrib.receive_date < '".$end_date_parm."'";
			$tmp_pledge_pay_where = " and pp.scheduled_date < '".$end_date_parm."'";
			 
		}else{
			$tmp_contrib_where = " AND contrib.receive_date <= now()";
			$tmp_pledge_pay_where = " and pp.scheduled_date <=  now()";
			 
			 
		}


		if(strlen($start_date_parm) > 0){
			$tmp_contrib_where = $tmp_contrib_where." AND contrib.receive_date >= '".$start_date_parm."'";
			$tmp_pledge_pay_where = $tmp_pledge_pay_where." AND p.start_date >= '".start_date_parm."' AND pp.scheduled_date >= '".$start_date_parm."'";
			 
			 
		}else{
			 
			 
			 
		}


		$currency_symbol = "";
		//print "<br>List for process amount due:".$cid_list;
		require_once('utils/finance/FinancialCategory.php');
		$tmpFinancialCategory = new FinancialCategory();
		 
		 
		$tmp_prefix_array = array();
		$tmp_prefix_array[] = $ct_prefix_id;

		$tmp_contrib_type_ids_for_sql = 	$tmpFinancialCategory->getContributionTypeWhereClauseForSQL( $tmp_prefix_array );

		if(strlen($tmp_contrib_type_ids_for_sql) > 0 ){
			$tmp_contrib_type_ids_for_sql = " AND ".$tmp_contrib_type_ids_for_sql;

		}

		$sql_str = "select t1.*, t2.symbol from  (SELECT contrib.contact_id, contrib.total_amount, contrib.receipt_date, contrib.currency, contrib.source, val.label
		FROM civicrm_contribution contrib left join civicrm_contribution_type ct ON contrib.contribution_type_id = ct.id,
		civicrm_option_value val,
		civicrm_option_group grp
		WHERE
		contrib.contribution_status_id = val.value
		AND  val.option_group_id = grp.id
		AND grp.name = 'contribution_status'
		and contrib.contribution_status_id = val.value
		and contrib.contact_id in ( $cid_list )
		and val.name not in ('Completed', 'Cancelled', 'Failed' )
		and contrib.contribution_recur_id is null".$tmp_contrib_type_ids_for_sql.$tmp_contrib_where.
		" and contrib.is_test = 0
		UNION ALL
		SELECT con.id as contact_id,  pp.scheduled_amount as total_amount,
		pp.scheduled_date as date, pp.currency as currency, 'pledge' as source, val.label as label
		FROM  `civicrm_pledge` AS p left join civicrm_contribution_type ct ON p.contribution_type_id = ct.id,   civicrm_contact AS con, civicrm_pledge_payment as pp,
		civicrm_option_value  val,
		civicrm_option_group grp
		WHERE p.contact_id = con.id
		and p.contact_id in ( $cid_list )
		and p.id = pp.pledge_id
		and val.name in ('Overdue', 'Pending' )".
		$tmp_pledge_pay_where.$tmp_contrib_type_ids_for_sql.
		" and pp.status_id = val.value
AND  val.option_group_id = grp.id
AND grp.name = 'contribution_status'
and p.is_test = 0
	order by 1) as t1 left join civicrm_currency as t2 on t1.currency = t2.name ";

		//print "<br><br>SQL:   ".$sql_str;

		$prev_cid = "";
		$cur_cid_html = "";
		$sub_total = 0;

		$tmp_amount_due = array();

		$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;

		while ( $dao->fetch( ) ) {
			$cur_cid = $dao->contact_id;
			if ( $cur_cid != $prev_cid ){
				if ( $prev_cid != ""){
					 
					$tmp_amount_due[$prev_cid] = $sub_total;
					// Wrap up table for previous contact.
					//$sub_total_formatted  = '$'.$sub_total;
					//$values[$prev_cid][$token_short] = $values[$prev_cid][$token_long] = $sub_total_formatted;
				}
				// start new subtotal for this contact

				$sub_total = 0;
				 

			}
			$total_amount= $dao->total_amount;
			$receipt_date = $dao->receipt_date;
			$source_desc = $dao->source;
			$currency = $dao->currency;
			$status = $dao->label;
			$currency_symbol = $dao->symbol;
			 
			//print "<br>Inside amount due: currency symbol : ".$currency_symbol;
			 
			$sub_total =  $sub_total + $total_amount;


			$prev_cid = $cur_cid;
		}

		$dao->free( );
		 
		$tmp_amount_due[$prev_cid] = $sub_total;


		// print_r($tmp_amount_due);
		require_once('RelationshipTools.php');
		$tmpRelTools = new RelationshipTools();
		 
		foreach ( $contactIDs as $cid ) {
			 
			$tmp_html = "";
			$tmp_sub = 0;
			 

			$rel_ids = $tmpRelTools->get_all_permissioned_ids($cid);

			 
			foreach($rel_ids as $rel_cid){
				// print "<br> rid: ".$rel_cid." owes ".$tmp_amount_due[$rel_cid];
				$tmp_sub = $tmp_sub + $tmp_amount_due[$rel_cid];
				 
			}
			 
			$tmp_sub_formatted = $currency_symbol.number_format($tmp_sub, 2);
			 
			// print "<br><br> CID: ".$cid." owes ".$tmp_sub_formatted ;

			$values[$cid][$token_to_fill]  = $tmp_sub_formatted ;
			 
			//print_r($values);

		}
		// SGladstone - serious bug occurs if the next "if" is missing.
		/* if ( $prev_cid != ""){
		 /  $sub_total_formatted  = '$ '.number_format( $sub_total, 2 );
		 $values[$prev_cid][$token_short] = $values[$prev_cid][$token_long] = $sub_total_formatted;
		 }
		 */

		$format = '';
		populate_default_value(  $values, $contactIDs , $token_to_fill, $token_to_fill,   "Nothing due at this time.", $format);

	}

	function ShowIdColumn(){
		// Check For Australian Tax-related custom fields. ( ie GST) as they need extra info.
		require_once('utils/util_custom_fields.php');
		$show_id_column = false;
		$show_gst_column = false;
		$custom_aussie_field_group_label = "Australian GST Info";
		$customAussieFieldLabels = array( "GST Amount"  );

		$extended_aussie_contrib_table = "";
		$outAussieCustomColumnNames = array();

		$error_msg_aussie = getCustomTableFieldNames($custom_aussie_field_group_label, $customAussieFieldLabels, $extended_aussie_contrib_table, $outAussieCustomColumnNames ) ;

		if(strlen( $extended_aussie_contrib_table) > 0){
			//print "<br>Aussie GST table found: ".$extended_aussie_contrib_table;
			$show_id_column = true;
			$show_gst_column = true;

		}else{
			$show_id_column = false;
			$show_gst_column = false;

		}


		return $show_id_column;




	}


	function ShowTaxColumn(){
		// Check For Australian Tax-related custom fields. ( ie GST) as they need extra info.
		require_once('utils/util_custom_fields.php');
		$show_id_column = false;
		$show_tax_column = false;
		$custom_aussie_field_group_label = "Australian GST Info";
		$customAussieFieldLabels = array( "GST Amount"  );

		$extended_aussie_contrib_table = "";
		$outAussieCustomColumnNames = array();

		$error_msg_aussie = getCustomTableFieldNames($custom_aussie_field_group_label, $customAussieFieldLabels, $extended_aussie_contrib_table, $outAussieCustomColumnNames ) ;

		if(strlen( $extended_aussie_contrib_table) > 0){
			//print "<br>Aussie GST table found: ".$extended_aussie_contrib_table;
			$show_id_column = true;
			$show_tax_column = true;

		}else{
			$show_id_column = false;
			$show_tax_column = false;

		}


		return $show_tax_column;




	}



	function getAdjustmentDetails(&$values, &$contactIDs,  &$ct_type_prefix_id, &$token_to_fill, &$output_wanted, &$start_date, &$end_date  ){


		if( count($contactIDs) == 0 ){
			// no contacts, nothing to do.
			return;
		}
		 
		 
		if( strlen( $start_date ) > 0 && strlen( $end_date) > 0){
			$date_where_clause = " ( date(contrib.receive_date) >= '$start_date'  AND date(contrib.receive_date) <= '$end_date' ) ";
			 
		}else{
			print "<br><br><br><b>ERROR: getAdjustmentDetails: Start date and end dates are required. </b>";
			return ;
			 
		}
		require_once('RelationshipTools.php');
		$tmpRelTools = new RelationshipTools();
		$cid_list =  $tmpRelTools->get_contact_ids_for_sql($contactIDs) ;

		// $fiscal_start_date = get_current_fiscal_year_start_date() ;

		$html_table_begin =  '<table border=0 style="width: 100%">';
		$html_table_end = ' </table>  	 ';
		require_once('utils/FormattingUtils.php');
		$formatter = new FormattingUtils();

		$font_size = $formatter->getPDFfontsize();

		$tt_style  = "style='font-size: ".$font_size.";'";
		$total_style = "style='text-align: right; font-weight: bold; font-size: ".$font_size.";'";



		// $tmp_contrib_type_ids_for_sql = 	getContributionTypeWhereClauseForSQL( $ct_type_prefix_id);

		require_once('utils/FinancialCategory.php') ;

		$tmpFinancialCategory = new FinancialCategory();

		$prefix_array = array();
		$prefix_array[] = $ct_type_prefix_id;
		$tmp_contrib_type_ids_for_sql   = $tmpFinancialCategory->getContributionTypeWhereClauseForSQL($prefix_array);
		if(strlen($tmp_contrib_type_ids_for_sql ) > 0 ){
			$tmp_contrib_type_ids_for_sql  = " AND ".$tmp_contrib_type_ids_for_sql ;
		}



		

		$sql_str = "";

		
			$sql_str = "select contrib.id, contrib.contact_id as contact_id, ct.name as contrib_type,
			contrib.source as source, contrib.total_amount as total_amount,
			month( contrib.receive_date ) as mm_date, day(contrib.receive_date ) as dd_date ,
			year(contrib.receive_date ) as yyyy_date , civicrm_currency.symbol
			from civicrm_contribution as contrib left join civicrm_financial_type as ct on contrib.financial_type_id = ct.id
			left join civicrm_currency on contrib.currency = civicrm_currency.name
			where contrib.contact_id IN ( $cid_list )
			and ".$date_where_clause."
			and (ct.name LIKE 'adjustment-%' OR ct.name LIKE '%---adjustment-%' ) ".$tmp_contrib_type_ids_for_sql."
			and contrib.is_test =0
			ORDER BY contact_id";

		
		// print "<br><br>sql for adjustments: ".$sql_str;


		$prev_cid = "";
		$cur_cid_html = "";
		$sub_total = 0;


		$tmp_obligation_detail_rows = array();
		$tmp_obligation_sub_total = array();

		$row_num =0;

		require_once 'FormattingUtils.php';

		$FormattingUtil = new FormattingUtils();
		 

		$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;


		while ( $dao->fetch( ) ) {

			$row_num = $row_num + 1;

			$cur_cid = $dao->contact_id;
			 
			if ( $cur_cid != $prev_cid ){
				$tmp_obligation_detail_rows[$cur_cid] = "";
				if ( $prev_cid != ""){
					// Wrap up table for previous contact.
					$obligation_total = $sub_total;
					//$out_total_of_everything[$prev_cid] =  $sub_total;
					$tmp_obligation_sub_total[$prev_cid] = $sub_total;
					// print "<br>cid: ".$prev_cid." sub total ".$sub_total;
					 


				}

				// start html table for this contact
				$cur_cid_html = "";
				$sub_total = 0;
				// $cur_cid_html = $cur_cid_html.$html_table_begin;

			}
			$total_amount= $dao->total_amount;
			// $currency = $dao->currency;
			$contrib_type = $dao->contrib_type;
			$source = $dao->source;
			$received_mm_date = $dao->mm_date;
			$received_dd_date = $dao->dd_date;
			$received_yyyy_date = $dao->yyyy_date;

			$tmp_description = $contrib_type.' '.$source ;
			$currency_symbol = $dao->symbol;

			$total_formated = $currency_symbol.number_format($total_amount, 2 );
			$tmp_date = $received_yyyy_date.'-'.$received_mm_date.'-'.$received_dd_date ;






			$input_format = 'yyyy-mm-dd';
			$tmp_date_formated  = $FormattingUtil->get_date_formatted_short_form($tmp_date, $input_format);



			$sub_total =  $sub_total + $total_amount;
			 
			 

			if( $row_num % 2  == 0){
				$css_name = "even-row";
				 
			}else{
				$css_name = "odd-row";
			}

			$tmp_description = $contrib_type.' '.$source.' '.$pay_desc;
			$tmp_obligation_detail_rows[$cur_cid] = $tmp_obligation_detail_rows[$cur_cid]."<tr class=".$css_name."><td ".$tt_style."width='10%'>".$tmp_date_formated."</td><td ".$tt_style." width='50%'>".$tmp_description."</td><td ".$tt_style." width='20%' align=right>".$total_formated."</td></tr>";
			 
			 


			$prev_cid = $cur_cid;
			 
		}

		$dao->free( );


		$obligation_total = $sub_total;
		$tmp_sub_total = $currency_symbol.number_format($sub_total, 2);
		$tmp_obligation_sub_total[$prev_cid] = $sub_total;

		//print "<br>cid: ".$prev_cid." sub total ".$sub_total;

		 
		require_once('RelationshipTools.php');
		$tmpRelTools = new RelationshipTools();
		// Create html and subtotals for each contact, inlcuding every contact they are authorized to.
		foreach ( $contactIDs as $cid ) {
			 
			$tmp_html = "";
			$tmp_sub = 0;
			$tmp_html = $tmp_html.$html_table_begin;


			 
			$rel_ids = $tmpRelTools->get_all_permissioned_ids($cid);

			 
			foreach($rel_ids as $rel_cid){

				$tmp_html = $tmp_html.$tmp_obligation_detail_rows[$rel_cid];
				$tmp_sub = $tmp_sub + $tmp_obligation_sub_total[$rel_cid];
				 
			}
			 
			$tmp_html = $tmp_html."<tr><td colspan=3> &nbsp; </td></tr>";
			if( $row_num > 0 ){
				$tmp_sub_formatted = $currency_symbol.number_format($tmp_sub, 2);
				$tmp_html = $tmp_html."<tr><td colspan=2 ".$total_style.">Total Amount Adjusted:</td><td ".$total_style.">".$tmp_sub_formatted."</td></tr>";
			}
			$tmp_html = $tmp_html.$html_table_end;

			 

			$values[$cid][$token_to_fill]  = $tmp_html;
			 


		}
		 



		$format = '';
		$this->populate_default_value(  $values, $contactIDs , $token_to_fill, $token_to_fill,   "Nothing Found for this contact", $format);

		// $money_format = 'USDmoney' ;
		// populate_default_value(  $values, $contactIDs , $token_obligation_total_short, $token_obligation_total_long,   $obligation_total, $money_format);

	}


	 







	/*
	 ($values, $contactIds, $token_oblig_with_balances_long, $token_oblig_with_balances_short, $token_balance_long, $token_balance_short,  $need_subtotals,  $token_format,  $default_end_date, $default_start_date, $default_need_due_column, $default_exclude_after_date);

	 */
	 
	   

	/************************************************************/
	function process_obligation_with_balances_subtotals_tokens2(&$values, &$contactIDs, &$ct_prefix_id, &$token_long, &$need_subtotals, &$format_parm, &$end_date_parm, &$start_date_parm, &$show_due_column, &$exclude_after_date_parm, &$output_wanted, &$include_closed_items, &$extra_where_clause, $include_detailrows = true, $include_cols = null ) {

		/*
		 print "<hr><br><br>Inside tokens: ".$token_long."<br>";

		 print "<br>ct prefix id: ".$ct_prefix_id;
		 print "<br>need sub totals: ".$need_subtotals;
		 print "<br>format parm: ".$format_parm;
		 print "<br>end date: ".$end_date_parm;
		 print "<br>start date: ".$start_date_parm;
		 print "<br>show due col: ".$show_due_column;
		 print "<br>exclude after date: ".$exclude_after_date_parm;
		 print "<br>output wanted: ".$output_wanted;
		 print "<br>include closed items: ".$include_closed_items;
		 */
		//print_r( $include_cols) ;
		//print "<br>Inside process obligation: ".$ct_prefix_id;
		
		// This section is to avoid PHP 'undefined variable' warnings.
		$contrib_type_total_due = "";
		$contrib_type_total_tax_amount = "";
		$tmp_due_date = "";

		
		$cur_cid_html = "";
		$sub_total = 0;
		$sub_received_total = 0;
		$sub_adjustments_total = 0 ;
		$sub_balance_total = 0;
		$sub_due_total =0 ;
		$sub_tax_amount_total = 0;
		// end of section to avoid warnings. 
		
		if( count($contactIDs) == 0 ){
			// no contacts, nothing to do.
			return;
		}
		 
	//	require_once 'utils/util_money.php';

		if( $include_cols == null){
			$include_cols = array();
		}

		if( count($include_cols) == 0 ){
			// include all possible columns.

			$include_cols["date"] = 1;
			$include_cols["id"] = 1;
			$include_cols["description"] = 1;
			$include_cols["tax"] = 1;
			$include_cols["billed"] = 1;
			$include_cols["recv"] = 1;
			$include_cols["adj"] = 1;
			$include_cols["bal"] = 1;
			$include_cols["due"] = 1;
		}

		// set up formatting for each possible column
		$currency_symbol = "";
		$desc_width = '55%';
		require_once('utils/FormattingUtils.php');
		$formatter = new FormattingUtils();
		// require_once 'FormattingUtils.php';
		// $FormattingUtil = new FormattingUtils();

		$font_size = $formatter->getPDFfontsize();
		// TODO: Decide how to handle pledges/contributions with a status of "Failed" or "Cancelled". Currently those records are ignored.

		$num_style = "text-align:right;padding-right: 10px; font-size:".$font_size.";";
		$num_total_style = "font-weight: bold; text-align:right; font-size:".$font_size."; " ;
		$sub_total_num_style = "text-align:right;padding-right: 10px; font-weight: bold; font-size:".$font_size.";";
		$description_style = "font-size:".$font_size.";";
		$sub_total_style = "style='text-align: right; font-weight: bold; font-size: ".$font_size.";' ";
		$tt_style = "style='font-size: ".$font_size.";'";

		$col_formats = array();
		$col_formats["date"] = "font-size: $font_size;";
		$col_formats["id"] = $num_style;
		$col_formats["tax"] = $num_style;
		$col_formats["description"] = "font-size: $font_size;";
		$col_formats["billed"] = $num_style;
		$col_formats["recv"] = $num_style;
		$col_formats["adj"] = $num_style;
		$col_formats["bal"] = $num_style;
		$col_formats["due"] = $num_style;
		 
		 
		$col_labels = array();
		$col_labels["date"] = "Date";
		$col_labels["id"] = "ID";
		$col_labels["tax"] = "GST";
		$col_labels["description"] = "Description";
		$col_labels["billed"] = "Amount";
		$col_labels["recv"] = "Received";
		$col_labels["adj"] = "Adj";
		$col_labels["bal"] = "Bal";
		// Due by is hard
		if($show_due_column){
			 
			 
			$input_format = 'yyyymmdd';
			$end_date_formatted  = $formatter->get_date_formatted_short_form($end_date_parm, $input_format);
			 
			// Check if there is a custom due by column.
			$tmp_dueby_heading = self::getCustomDueByColHeading();
			if(strlen($tmp_dueby_heading) > 0 ) {

				$tmp_heading_array  = explode ( "{" , $tmp_dueby_heading );

				$part_a = $tmp_heading_array[0];
				$part_b_raw = $tmp_heading_array[1];

				$part_b = str_replace("}", "", $part_b_raw) ;
				$date_modifier = $part_b ; //   ' +1 month' ;

				$next_month_raw = strtotime( $end_date_parm.$date_modifier );

				$next_month =  date('n/j/Y',  $next_month_raw);
				//print "<br> new date: ".$next_month;
				$tmp_dueby_heading = $part_a.'<br>'.$next_month;
				 
			}else{
				$tmp_dueby_heading = 'Due by<br>'.$end_date_formatted;
				 
			}

			$col_labels["due"] = $tmp_dueby_heading;
			$due_by_html = '<th style="'.$num_style.'">'.$tmp_dueby_heading.'</th>';
		}



		if($format_parm == "self-service"){
			$html_table_begin =  '<table border=0 style="width: 75%">';
		}else{
			$html_table_begin =  '<table border=0 style="width: 100%">';
		}

		$show_id_col = self::ShowIdColumn();
		$show_tax_col = self::ShowTaxColumn();

		// print "<br>show id col:".$show_id_col;
		if($show_id_col){

			// $id_col_head = "<th style='".$num_style."'>ID:</th>";


		}else{
			//$id_col_head  = "";
			 
			unset($include_cols["id"]);
		}


		if($show_tax_col){

			//$tax_col_head = "<th style='".$num_style."'>GST:</th>";


		}else{
			//$tax_col_head  = "";
			unset($include_cols["tax"]);

		}
		if( $include_detailrows ){
			// $date_col_label = "Date:";

		}else{
			//$date_col_label = ""; // When detail rows are not shown, there is no date data in the first column.
			unset($include_cols["date"]);

		}

		if( array_key_exists("date",  $include_cols) && array_key_exists("id",  $include_cols) && array_key_exists("description",  $include_cols) ){
			// $col_span is used to pad first cell in  subtotal and total rows.
			$col_span= 	"colspan=3";
		}else if(  array_key_exists("date",  $include_cols) && array_key_exists("description",  $include_cols) ){
			$col_span= 	"colspan=2";
		}else{
			$col_span= 	"colspan=1";
		}

		$html_table_headings = "\n<tr>";
		foreach( $include_cols as $col_key => $value){
			if(isset($col_formats[$col_key])){
				$cur_style = $col_formats[$col_key];
			}
			if(isset($col_labels[$col_key])){
				$cur_label = $col_labels[$col_key];
			}
			
			if( $col_key == "description" ){
				$cur_width = " width='$desc_width' ";
				 
			}else{
				$cur_width = "";
			}
			$html_table_headings = $html_table_headings."<th style='$cur_style' $cur_width>$cur_label:</th>";
			 
		}
		 
		$html_table_headings = $html_table_headings."</tr>";
		 
		/*
		 $html_table_headings = "\n<tr><th ".$tt_style.">$date_col_label</th>".$id_col_head."<th ".$tt_style." width='".$desc_width."'>Description:</th>".$tax_col_head."<th style='".$num_style."'>Charged:</th><th style='".$num_style."'>Received:</th><th style='".$num_style."'>Adj: </th><th style='".$num_style."'>Bal:</th>".$due_by_html;


		 */

		$html_table_end = ' </table>  	 ';



		$tmp_order_by = "contact_id, rec_date";
		if( $need_subtotals){
			$tmp_order_by = "contact_id, contrib_type, rec_date";
		}else{
			$tmp_order_by = "contact_id, rec_date";
		}

		$error_msg = "";
		// (&$contactIDs, &$order_by_parm, &$end_date_parm , &$start_date_parm,  &$error){
		// print "<br><br>Next step: get sql, start date: ".$start_date_parm;
		require_once('utils/Obligation.php');
		$obligation = new Obligation();

		/*
		 (&$contactIDs, &$order_by_parm, &$end_date_parm , &$start_date_parm, &$exclude_after_date_parm,
		 &$error, $all_contacts = false, $get_contact_name  = false, $extra_where_clause = '', $extra_groupby_clause = '', $ct_type_prefix_id = '', $include_closed_items = true )
		 */
		 
		$empty_string = "";
		$tmp_false = false;
		$sql_str = $obligation->get_sql_string_for_obligations($contactIDs,  $tmp_order_by, $end_date_parm, $start_date_parm, $exclude_after_date_parm,
				$error_msg, $tmp_false, $tmp_false, $extra_where_clause , $empty_string,  $ct_prefix_id, $include_closed_items);

		$prev_cid = "";
		$cur_cid_html = "";
		$sub_total = 0;


		$tmp_obligation_detail_rows = array();

		$tmp_obligation_sub_total = array();
		$tmp_received_sub_total = array();
		$tmp_adjustments_sub_total = array();
		$tmp_balance_sub_total = array();
		$tmp_due_sub_total = array();
		$tmp_tax_amount_sub_total = array();


		//print "<hr><br><br>SQL: ".$sql_str;

		require_once ('FinEntitlement.php');
		$entitlement = new FinEntitlement();
		$can_update_subscription = $entitlement->accessPaymentProcessorUpdateSubscription();
		$can_post_backoffice_creditcard = $entitlement->canPostBackOfficeCreditCard();

		$has_active_payment_processor = $entitlement->HasAtLeastOneLivePaymentProcessor();

		$has_active_recurring_payment_processor = $entitlement->HasRecurringPaymentProcessor();
		$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;

		$row_num =0;
		$prev_contrib_type = "";

		$contrib_type_total_charged =  0;
		$contrib_type_total_received = 0 ;
		$contrib_type_total_adjusted =  0;
		$contrib_type_total_balance = 0 ;
		 
		 
		//require_once('FinancialUtils.php');
		$financialUtils = new FinancialUtils();

		while ( $dao->fetch( ) ) {

			$row_num = $row_num + 1;
			$cur_cid = $dao->contact_id;
			$cur_contrib_type = $dao->contrib_type;
			$currency = $dao->currency;
			$currency_symbol = $dao->symbol;
			 
			 
			 
			//print "<br>Currency symbol: ".$currency_symbol;
			 
			 
			 
			// Do subtotals for contrib types, if needed.
			if($need_subtotals){
		  if (  ($prev_contrib_type <> $cur_contrib_type) || ($cur_cid != $prev_cid) ){

		   $fmt_contrib_type_total_charged = $currency_symbol.number_format($contrib_type_total_charged, 2);
		   $fmt_contrib_type_total_received = $currency_symbol.number_format($contrib_type_total_received, 2);
		   $fmt_contrib_type_total_adjusted = $currency_symbol.number_format($contrib_type_total_adjusted, 2);
		   $fmt_contrib_type_total_balance = $currency_symbol.number_format($contrib_type_total_balance, 2);
		   $fmt_contrib_type_total_due = $currency_symbol.number_format($contrib_type_total_due, 2);
		    
		   $fmt_contrib_type_total_tax_amount = $currency_symbol.number_format($contrib_type_total_tax_amount, 2);
		    
		   $tmp_sub_cid = $cur_cid;
		   if($cur_cid != $prev_cid){
		   	$tmp_sub_cid = $prev_cid ;
		   }
		    
		   if(strlen($end_date_parm) > 0 ){
		   	$tmp_due_cell = "<td style='".$sub_total_num_style."'>". $fmt_contrib_type_total_due."</td>";
		   	 
		   }else{
		   	 
		   	$tmp_due_cell = '';
		   }
		   	
		   	
		   	
		   if($show_tax_col){
		   	$tmp_tax_col = "<td style='".$sub_total_num_style."'>". $fmt_contrib_type_total_tax_amount."</td>";
		   		
		   }else{
		   	$tmp_tax_col = "";
		   		
		   }
		   // handle SubTotal row
		   $tmp_row = "\n<tr><td $col_span $sub_total_style>$prev_contrib_type Sub Total:&nbsp; </td>";
		   foreach( $include_cols as $col_key => $val){
		   	$cur_style = $col_formats[$col_key];
		   	$cur_label = $col_labels[$col_key];
		   	if( $col_key == "description" ){
		   		$cur_width = " width='$desc_width' ";
		   		 
		   	}else{
		   		$cur_width = "";
		   	}
		   	// get actual data.
		   	if( $col_key == "tax"  ){
		   		$cur_data_value = $fmt_contrib_type_total_tax_amount;
		   	}else if( $col_key == "billed"  ){
		   		$cur_data_value = $fmt_contrib_type_total_charged;
		   	}else if( $col_key == "recv"  ){
		   		$cur_data_value = $fmt_contrib_type_total_received;
		   	}else if( $col_key == "adj"  ){
		   		$cur_data_value = $fmt_contrib_type_total_adjusted;
		   	}else if( $col_key == "bal"  ){
		   		$cur_data_value = $fmt_contrib_type_total_balance;
		   	}else if( $col_key == "due"  ){
		   		$cur_data_value = $fmt_contrib_type_total_due;
		   	}else{
		   		continue;
		   	}

		   	$tmp_row = $tmp_row."<td style='$sub_total_num_style'>$cur_data_value</td>";

		   }
		   $tmp_total_col_count = count( $include_cols);
		   $tmp_row = $tmp_row."</tr><tr><td colspan=$tmp_total_col_count>&nbsp;</td></tr>";

		   //print "\n<br><br> ".$tmp_row;
		   $tmp_obligation_detail_rows[$tmp_sub_cid] =  $tmp_obligation_detail_rows[$tmp_sub_cid].$tmp_row;

		 	 $contrib_type_total_charged =  0;
		 	 $contrib_type_total_received = 0 ;
		 	 $contrib_type_total_adjusted =  0;
		 	 $contrib_type_total_balance = 0 ;
		 	 $contrib_type_total_due =0;
		 	 $contrib_type_total_tax_amount = 0;
		 	 	
		  }
			}

			 

			// Check if we need to do table column headings
			if( $row_num == 1){
				$html_table_begin = $html_table_begin.$html_table_headings;
				 
			}
			 
			if ( $cur_cid != $prev_cid ){
				$tmp_obligation_detail_rows[$cur_cid] = "";
				if ( $prev_cid != ""){
					// Wrap up table for previous contact.
					$obligation_total = $sub_total;
					 
					 
					 
					 
					// print "<br>Wrap up prev. contact id : ".$prev_cid." sub adjustments total: ".$sub_adjustments_total." sub received total: ".$sub_received_total;
					$tmp_obligation_sub_total[$prev_cid] = $sub_total;
					$tmp_received_sub_total[$prev_cid] = $sub_received_total;
					$tmp_adjustments_sub_total[$prev_cid] = $sub_adjustments_total;
					$tmp_balance_sub_total[$prev_cid] = $sub_balance_total;
					$tmp_due_sub_total[$prev_cid] = $sub_due_total;
					$tmp_tax_amount_sub_total[$prev_cid] = $sub_tax_amount_total;
					 
					//  print "<br>cid: ".$prev_cid.": sub tax amount total: ".$sub_tax_amount_total;
				}

				// start up for this contact
				$cur_cid_html = "";
				$sub_total = 0;
				$sub_received_total = 0;
				$sub_adjustments_total = 0 ;
				$sub_balance_total = 0;
				$sub_due_total =0 ;
				$sub_tax_amount_total = 0;
				 
				// $cur_cid_html = $cur_cid_html.$html_table_begin;

			}
			 
			 
			 

			 
			$total_amount= $dao->total_amount;
			$entity_id = $dao->id;
			$entity_type = $dao->entity_type;
			$status_id = $dao->status;
			$status_label =$dao->status_label;
			//print "<br>entity type: ".$entity_type;
			//print "<br>status label: ".$status_label;
			$pledge_id = $dao->id;
			// $currency = $dao->currency;
			$contrib_type = $dao->contrib_type;
			$source = $dao->source;
			if( isset( $dao->recur_paid_so_far )){
				$recur_paid_so_far = $dao->recur_paid_so_far;
			}else{
				$recur_paid_so_far = "0";
			}
			$recur_installment_amount = $dao->recur_amount;
			$recur_installment_number = $dao->recur_installments;
			$payment_processor_type = $dao->payment_processor_type;
			$recur_crm_id = $dao->recur_crm_id;
			$line_item_recur_amount = $dao->line_item_recur_amount;

			//print "<br><br>Recur crm id: ".$recur_crm_id;
			$tmp_description = $contrib_type.' '.$source ;

			$received_mm_date = $dao->mm_date;
			$received_dd_date = $dao->dd_date;
			$received_yyyy_date = $dao->yyyy_date;
			 

			// NEW: SQL calculated received, adjusted, and balance.
			$dao_received = $dao->received;
			$dao_adjusted = $dao->adjusted;
			$dao_balance = $dao->balance;
			$dao_line_item_id = $dao->line_item_id;
			
			if(isset($dao->tax_amount)){
				$dao_tax_amount = $dao->tax_amount;
			}else{
				$dao_tax_amount = "0";
			}
			$dao_amt_due = $dao->amt_due;   // only filled in for contributions, not pledges or recur.

			 
			//$tmp_date_formated = $received_mm_date.'/'.$received_dd_date.'/'.$received_yyyy_date ;

			$tmp_date  = $received_yyyy_date.'-'.$received_mm_date.'-'.$received_dd_date ;
			$input_format = 'yyyy-mm-dd';
			$tmp_date_formated  = $formatter->get_date_formatted_short_form($tmp_date, $input_format);


			 

			$tmp_cur_line_recieved = 0;
			$tmp_cur_line_adjustments = 0;
			$tmp_cur_line_balance = 0;
			$tmp_cur_line_due =0;
			$tmp_cur_line_tax_amount = 0;
			 
			 
			 
			 
			if( strlen($end_date_parm) > 0){
				 
				if( $entity_type == 'contribution' ){
					$tmp_cur_line_due = $dao_amt_due;
				}else{
					 
					// TODO: Should eliminate this function and get value from $dao_amt_due
					$tmp_cur_line_due = $this->get_due_to_date_amount( $entity_type , $entity_id,  $end_date_parm, $dao_line_item_id) ;
				}
			}
			 
			 
			$tmp_cur_line_recieved = $total_amount;
			$tmp_cur_line_balance = $dao_balance;
			$tmp_cur_line_adjustments = $dao_adjusted;
			$tmp_cur_line_recieved = $dao_received;
			$tmp_cur_line_tax_amount = $dao_tax_amount;
			 
			 
			 

			$total_formated = $currency_symbol.number_format($total_amount, 2 );
			$recieved_formated =    $currency_symbol.number_format($tmp_cur_line_recieved, 2 );
			$adjustments_formated =    $currency_symbol.number_format($tmp_cur_line_adjustments, 2 );
			$balance_formated =    $currency_symbol.number_format($tmp_cur_line_balance, 2 );
			$due_formated = $currency_symbol.number_format($tmp_cur_line_due, 2 );

			$tax_amount_formated = $currency_symbol.number_format( $tmp_cur_line_tax_amount, 2 );


			$contrib_type_total_charged =  $contrib_type_total_charged + $total_amount;
			$contrib_type_total_received =  $contrib_type_total_received + $tmp_cur_line_recieved ;
			$contrib_type_total_adjusted =  $contrib_type_total_adjusted + $tmp_cur_line_adjustments ;
			$contrib_type_total_balance =  $contrib_type_total_balance + $tmp_cur_line_balance ;
			$contrib_type_total_due =  $contrib_type_total_due + $tmp_cur_line_due ;
			$contrib_type_total_tax_amount = $contrib_type_total_tax_amount  + $tmp_cur_line_tax_amount;


			$sub_total =  $sub_total + $total_amount;
			$sub_received_total = $sub_received_total + $tmp_cur_line_recieved;
			 
			//   print "<br>Current tmp cur line adjustments: ".$tmp_cur_line_adjustments ;
			//   print "<br>Current sub total for adjustments: ".$sub_adjustments_total;
			//   print "<br>Current grand total for adjustments: ".$contrib_type_total_adjusted ;
			$sub_adjustments_total = $sub_adjustments_total + $tmp_cur_line_adjustments ;
			$sub_balance_total =  $sub_balance_total + $tmp_cur_line_balance ;
			$sub_due_total = $sub_due_total + $tmp_cur_line_due;
			$sub_tax_amount_total = $sub_tax_amount_total + $tmp_cur_line_tax_amount;
			 
			 
			if( $row_num % 2  == 0){
				$css_name = "even-row";
				 
			}else{
				$css_name = "odd-row";
			}
			 
			// If format is backoffice, provide drill-down links to view details.
			if($format_parm == "backoffice_screen"){
				 

				$tmp_detail_url = "";
				$tmp_detail_label = '('.$entity_type.' detail)';
				if($entity_type == 'pledge'){
					$next_pledge_payment = $financialUtils->getNextPledgePaymentId($entity_id) ;
					// print "<br>pledge id: ".$entity_id;
					 
					if( sizeof($next_pledge_payment) > 0 ){
						// print "<br>Prepare url for making next payment";
						$tmp_pledge_payment_id = $next_pledge_payment['id'];
						$tmp_make_payment_url = '/civicrm/contact/view/contribution?reset=1&action=add&cid='.$cur_cid.'&context=pledge&ppid='.$tmp_pledge_payment_id;
						$tmp_make_cc_payment_url = $tmp_make_payment_url.'&mode=live';
					}else{
						$tmp_make_payment_url = '';
					}
					$tmp_detail_url = '/civicrm/contact/view/pledge?reset=1&id='.$entity_id.'&cid='.$cur_cid.'&action=view&context=pledge&selectedChild=pledge';
					if( strlen($tmp_detail_url) > 0 ){
						$tmp_description = $tmp_description.' <a href="'.$tmp_detail_url.'">'.$tmp_detail_label.'</a>';
					}else{
						$tmp_description = $tmp_description.$tmp_detail_label;
					}
					 
				}else if( $entity_type == 'contribution' ){

					if( $status_label == 'Pending'  ){
						$tmp_make_payment_url = '/civicrm/contact/view/contribution?reset=1&action=update&id='.$entity_id.'&cid='.$cur_cid.'&context=contribution';
					}else{

						$tmp_make_payment_url = '' ;
					}
					$tmp_detail_url = '/civicrm/contact/view/contribution?reset=1&id='.$entity_id.'&cid='.$cur_cid.'&action=view&context=contribution&selectedChild=contribute';
					 
					if( strlen($tmp_detail_url) > 0 ){
						$tmp_description = $tmp_description.' <a href="'.$tmp_detail_url.'">'.$tmp_detail_label.'</a>';
					}else{
						$tmp_description = $tmp_description.$tmp_detail_label;
					}
					 
				}else if( $entity_type == 'recurring' ){
					$tmp_make_payment_url = '';
					$tmp_detail_url = '';
					$currency_symbol = "$";
					if( $line_item_recur_amount <> $recur_installment_amount ){
						$amt_description_tmp = $currency_symbol.$line_item_recur_amount.' - part of '.$currency_symbol.$recur_installment_amount;
						 
					}else{
						$amt_description_tmp = $currency_symbol.$line_item_recur_amount;
						 
						 
					}
					 
					if( $can_update_subscription  && strcmp( $payment_processor_type, "AuthNet") == 0){
						 
						$tmp_edit_installments = "<a href='/civicrm/contribute/updaterecur?reset=1&action=update&crid=".$recur_crm_id."&cid=".$cur_cid."&context=contribution'>Edit Installments</a> ";
						$tmp_edit_card =  "<a class='edit button' href='/civicrm/contribute/updatebilling?reset=1&crid=".$recur_crm_id."&cid=".$cur_cid."&context=contribution'>Edit Card Details</a>  ";
						$edit_links =  "<br>".$tmp_edit_card." &nbsp; &nbsp;".$tmp_edit_installments;
						$tmp_detail_label = "(automated, set up as ".$amt_description_tmp." for ".$recur_installment_number." installments) ".$edit_links;
					}else{
						$tmp_detail_label = "(automated, set up as ".$amt_description_tmp." for ".$recur_installment_number." installments)";
					}
					 
					 
					$tmp_description = $tmp_description.$tmp_detail_label;
					 
					 
				}
				 
				/*
				 if( strlen($tmp_detail_url) > 0 ){
				 $tmp_description = $tmp_description.' <a href="'.$tmp_detail_url.'">'.$tmp_detail_label.'</a>';
				 }else{
				 $tmp_description = $tmp_description.$tmp_detail_label;
				 }
				  
				 */
				if( strlen($tmp_make_payment_url) > 0){

					$tmp_description = $tmp_description.' &nbsp;<br><a class="edit button" href="'.$tmp_make_payment_url.'" >Record '.$tmp_due_date.' Payment or Adjustment </a>';

					if($entity_type == 'pledge'){

						//print "<br><br>can post cc".$can_post_backoffice_creditcard;
						if( $can_post_backoffice_creditcard  ){

							$tmp_description = $tmp_description.' &nbsp; <a href="'.$tmp_make_cc_payment_url.'">'.$tmp_due_date.' Credit Card</a>';
						}

					}
				}
				 
			}
			 
			// Add hyperlink for self-service area
			if($format_parm == "self-service"){
				$tmp_detail_url = "";
				$tmp_detail_label = '(view '.$entity_type.' detail)';
				if($entity_type == 'pledge' && $has_active_payment_processor){
					if( $tmp_cur_line_balance > 0 && $has_active_recurring_payment_processor){

						$tmp_detail_label = ' (Make Payment)';

						$pledge_contribution_page_id = get_pledge_payment_page($entity_id);
						// require_once 'CRM/Contact/BAO/Contact/Utils.php';

						// $checksum = CRM_Contact_BAO_Contact_Utils::generateChecksum( $cur_cid );


						// <a href="{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$rel_row.pledge_contribution_page_id`&pledgeId=`$rel_row.pledge_id`&cs=`$rel_row.contact_checksum`"}">{ts}Make Payment{/ts}</a><br/>
						$tmp_detail_url = '/civicrm/contribute/transact?reset=1&id='.$pledge_contribution_page_id.'&pledgeId='.$entity_id;    // .'cs='.$checksum;

					}else{
						//  Zero balance, no need for a 'make payment' link
						$tmp_detail_url = '';
						$tmp_detail_label = '';
						 
					}

					// $tmp_detail_url = '/civicrm/contact/view/pledge?reset=1&id='.$entity_id.'&cid='.$cur_cid.'&action=view&context=pledge&selectedChild=pledge';
				}else if( $entity_type == 'contribution' ){
					$tmp_detail_url = '';
					$tmp_detail_label = '';
				}else if( $entity_type == 'recurring' ){
					$tmp_detail_url = '';
					$tmp_detail_label = "(automated, set up as $".$recur_installment_amount." for ".$recur_installment_number." installments)";
				}


				// BROKEN PAYBALANCE  extension, hide link
				//$tmp_detail_url = '';

				 
				if( strlen($tmp_detail_url) > 0 ){
					$tmp_description = $tmp_description.' <br><b><a href="'.$tmp_detail_url.'">'.$tmp_detail_label.'</a></b>';
				}else{
					$tmp_description = $tmp_description.$tmp_detail_label;
				}
			}
			 
			if(strlen($end_date_parm) > 0){
				$due_table_cell = "<td style='".$num_style."'>".$due_formated."</td>";
			}else{
				$due_table_cell = "";
			}


			if($show_id_col){
				$id_column = "<td style='".$num_style."'>".$entity_id."</td>";

			}else{
				$id_column = "";


			}

			if($show_tax_col){
				$tax_column = "<td style='".$num_style."'>".$tax_amount_formated."</td>";

			}else{
				$tax_column = "";


			}

			if( $include_detailrows ){
				 

				$tmp_row = "\n<tr class=$css_name>";
				foreach( $include_cols as $col_key => $val){
					$cur_style = $col_formats[$col_key];
					if(isset($col_labels[$col_key])){
					   $cur_label = $col_labels[$col_key];
					}else{
						$cur_label =  "";
					}
					if( $col_key == "description" ){
						$cur_width = " width='$desc_width' ";
						 
					}else{
						$cur_width = "";
					}
					// get actual data.
					if( $col_key == "date"){
						$cur_data_value = $tmp_date_formated ;
					}else if( $col_key == "id" ){
						$cur_data_value = $entity_id;
					}else if( $col_key == "description"){
						$cur_data_value = $tmp_description;
					}else if( $col_key == "tax"  ){
						$cur_data_value = $tax_amount_formated;
					}else if( $col_key == "billed"  ){
						$cur_data_value = $total_formated;
					}else if( $col_key == "recv"  ){
						$cur_data_value = $recieved_formated;
					}else if( $col_key == "adj"  ){
						$cur_data_value = $adjustments_formated;
					}else if( $col_key == "bal"  ){
						$cur_data_value = $balance_formated;
					}else if( $col_key == "due"  ){
						$cur_data_value = $due_formated;
					}else{
						continue;
					}

					$tmp_row = $tmp_row."<td style='$cur_style' $cur_width >$cur_data_value</td>";

				}
				//$tmp_total_col_count = count( $include_cols);
				$tmp_row = $tmp_row."</tr>";

				$tmp_obligation_detail_rows[$cur_cid] =  $tmp_obligation_detail_rows[$cur_cid].$tmp_row;
				/*
				 $tmp_obligation_detail_rows[$cur_cid] =  $tmp_obligation_detail_rows[$cur_cid]."\n<tr class=".$css_name."><td ".$tt_style.">".$tmp_date_formated."</td>".$id_column.
				 "<td style='".$description_style."'>".$tmp_description."</td>".$tax_column."<td style='".$num_style."'>".$total_formated."</td><td style='".$num_style."'>".$recieved_formated."</td><td style='".$num_style."'>".$adjustments_formated."</td><td style='".$num_style."'>".$balance_formated."</td>".$due_table_cell."</tr>";
				 */

			}
			 
			$prev_contrib_type = $cur_contrib_type;

			$prev_cid = $cur_cid;

		}

		$dao->free( );


		if($need_subtotals){
			// Do subtotals for contrib types, if needed.
			$fmt_contrib_type_total_charged = $currency_symbol.number_format($contrib_type_total_charged, 2);
			$fmt_contrib_type_total_received =$currency_symbol.number_format($contrib_type_total_received, 2);
			$fmt_contrib_type_total_adjusted = $currency_symbol.number_format($contrib_type_total_adjusted, 2);
			$fmt_contrib_type_total_balance = $currency_symbol.number_format($contrib_type_total_balance, 2);
			$fmt_contrib_type_total_due = $currency_symbol.number_format($contrib_type_total_due, 2);
			$fmt_contrib_type_total_tax_amount = $currency_symbol.number_format($contrib_type_total_tax_amount, 2);
			 
			if(strlen($end_date_parm) > 0 ){
				$tmp_due_cell = "<td style='".$sub_total_num_style."'>". $fmt_contrib_type_total_due."</td>";
				 
			}else{
				 
				$tmp_due_cell = '';
			}
			 
			 
				
		 if($show_tax_col){
		 	$tmp_tax_col = "<td style='".$sub_total_num_style."'>". $fmt_contrib_type_total_tax_amount."</td>";
		 		
		 }else{
		 	$tmp_tax_col = "";
		 		
		 }
		  
		  
	  // handle SubTotal row
		 $tmp_row = "\n<tr><td $col_span $sub_total_style>$prev_contrib_type Sub Total:&nbsp; </td>";
		 foreach( $include_cols as $col_key => $val){
		 	$cur_style = $col_formats[$col_key];
		 	$cur_label = $col_labels[$col_key];
		 	if( $col_key == "description" ){
		 		$cur_width = " width='$desc_width' ";
		 		 
		 	}else{
		 		$cur_width = "";
		 	}
		 	// get actual data.
		 	if( $col_key == "tax"  ){
		 		$cur_data_value = $fmt_contrib_type_total_tax_amount;
		 	}else if( $col_key == "billed"  ){
		 		$cur_data_value = $fmt_contrib_type_total_charged;
		 	}else if( $col_key == "recv"  ){
		 		$cur_data_value = $fmt_contrib_type_total_received;
		 	}else if( $col_key == "adj"  ){
		 		$cur_data_value = $fmt_contrib_type_total_adjusted;
		 	}else if( $col_key == "bal"  ){
		 		$cur_data_value = $fmt_contrib_type_total_balance;
		 	}else if( $col_key == "due"  ){
		 		$cur_data_value = $fmt_contrib_type_total_due;
		 	}else{
		 		continue;
		 	}

		 	$tmp_row = $tmp_row."<td style='$sub_total_num_style'>$cur_data_value</td>";

		 }

		 $tmp_total_col_count = count( $include_cols);
		 $tmp_row = $tmp_row."</tr><tr><td colspan=$tmp_total_col_count>&nbsp;</td></tr>";
		 /*
		  $subtotal_tmp1_html  = "\n<tr><td ".$col_span." ".$sub_total_style.">".$prev_contrib_type." Sub Total:&nbsp; </td>".$tmp_tax_col."<td style='".$sub_total_num_style."'>".$fmt_contrib_type_total_charged."</td><td style='".$sub_total_num_style."'>".$fmt_contrib_type_total_received."</td> <td style='".$sub_total_num_style."'>".$fmt_contrib_type_total_adjusted."</td> <td style='".$sub_total_num_style."'>".$fmt_contrib_type_total_balance."</td>".$tmp_due_cell."</tr><tr><td colspan=6>&nbsp;</td></tr>";
		  */
		  
		 $tmp_obligation_detail_rows[$cur_cid] =  $tmp_obligation_detail_rows[$cur_cid].$tmp_row ;
		 //print "<br>HTML for xx subtotal: <hr>".$subtotal_tmp1_html

		}


		$obligation_total = $sub_total;
		$tmp_sub_total = '$ '.number_format($sub_total, 2);
		$tmp_obligation_sub_total[$prev_cid] = $sub_total;

		
		$tmp_received_sub_total[$prev_cid] = $sub_received_total;
		$tmp_adjustments_sub_total[$prev_cid] = $sub_adjustments_total;
		$tmp_balance_sub_total[$prev_cid] = $sub_balance_total;
		$tmp_due_sub_total[$prev_cid] = $sub_due_total;
		$tmp_tax_amount_sub_total[$prev_cid] = $sub_tax_amount_total;
		 
		//   print "<br>cid ".$prev_cid.": ".$sub_tax_amount_total;
		 
		// Create html and totals for each contact, including every contact they are authorized to.
		foreach ( $contactIDs as $cid ) {
			 
			$tmp_html = "";
			$tmp_sub_amount = 0;
			$tmp_sub_received = 0;
			$tmp_sub_adjustments = 0 ;
			$tmp_sub_balance =0 ;
			$tmp_sub_due = 0;
			$tmp_sub_tax_amount = 0;
			$tmp_html = $tmp_html.$html_table_begin;


			require_once('RelationshipTools.php');
			$tmpRelTools = new RelationshipTools();

			$rel_ids = $tmpRelTools->get_all_permissioned_ids($cid);

			$display_contact_info = false;

			$num_contacts_with_data =0;
			foreach($rel_ids as $rel_cid){
				if( isset( $tmp_obligation_detail_rows[$rel_cid]) && strlen( $tmp_obligation_detail_rows[$rel_cid] ) > 0 ){
					$num_contacts_with_data = $num_contacts_with_data + 1;
				}
			}

			if(  $num_contacts_with_data > 1 ){
				$display_contact_info = true;
			}
			foreach($rel_ids as $rel_cid){
				if($display_contact_info ){
					if( strlen( $tmp_obligation_detail_rows[$rel_cid] ) > 0 ){
						require_once 'api/api.php';
						$tmp_contact = civicrm_api('Contact','GET',array('contact_id' => $rel_cid,  'version' =>3));
						//$fields_tmp = civicrm_api('Contact', 'GETFIELDS', array('version' =>3));
						//print_r($fields_tmp);
						//print "<br>contact from api: ".$tmp_contact['values'][1]['display_name'];
						// print_r($tmp_contact['value"']);    ".$tt_style."

						$tmp_total_col_count = count( $include_cols);
						if($format_parm == "backoffice_screen"){
							$tmp_html = $tmp_html."<tr><td ".$tt_style." colspan=$tmp_total_col_count><strong>Contact: <a href='/civicrm/contact/view?reset=1&cid=".$rel_cid."' target='_details'>".$tmp_contact['values'][$rel_cid]['display_name']."</a></td></tr>";
						}else{
							$tmp_html = $tmp_html."<tr><td ".$tt_style." colspan=$tmp_total_col_count><strong>Contact: ".$tmp_contact['values'][$rel_cid]['display_name']."</td></tr>";
						}
					}
				}
				
				if( isset( $tmp_obligation_detail_rows[$rel_cid] )){
					$tmp_html = $tmp_html.$tmp_obligation_detail_rows[$rel_cid];
				}else{
					
				}
				if(isset( $tmp_obligation_sub_total[$rel_cid] )){ 
					$tmp_sub_amount = $tmp_sub_amount + $tmp_obligation_sub_total[$rel_cid];
				}
				
				if(isset($tmp_received_sub_total[$rel_cid] )){
					$tmp_sub_received = $tmp_sub_received + $tmp_received_sub_total[$rel_cid] ;
				}
				
				if(isset($tmp_adjustments_sub_total[$rel_cid])){
					$tmp_sub_adjustments = $tmp_sub_adjustments + $tmp_adjustments_sub_total[$rel_cid]  ;
				}
				
				if(isset($tmp_balance_sub_total[$rel_cid] )){
					$tmp_sub_balance = $tmp_sub_balance + $tmp_balance_sub_total[$rel_cid]  ;
				}
				
				if(isset( $tmp_due_sub_total[$rel_cid] )){
					$tmp_sub_due = $tmp_sub_due + $tmp_due_sub_total[$rel_cid]  ;
				}
				
				if(isset( $tmp_tax_amount_sub_total[$rel_cid]  )){
					$tmp_sub_tax_amount = $tmp_sub_tax_amount + $tmp_tax_amount_sub_total[$rel_cid]  ;
				}
	   // print "<br>Rel id tax amount: ".$rel_cid.": ".$tmp_sub_tax_amount;
			}
			 
			 
			 
			// format numbers as currency, for display to the user.
			$tmp_sub_amount_formatted = $currency_symbol.number_format($tmp_sub_amount, 2);
			$tmp_sub_received_formatted = $currency_symbol.number_format($tmp_sub_received, 2);
			$tmp_sub_adjustments_formatted = $currency_symbol.number_format($tmp_sub_adjustments, 2);
			$tmp_sub_balance_formatted = $currency_symbol.number_format( $tmp_sub_balance , 2);
			$tmp_sub_due_formatted = $currency_symbol.number_format( $tmp_sub_due , 2);
			$tmp_sub_tax_amount_formatted  = $currency_symbol.number_format( $tmp_sub_tax_amount , 2);
			 
			// $tmp_html = $tmp_html."<tr><td colspan=6> &nbsp; </td></tr>";
			if( $row_num > 0 ){

				if(strlen($end_date_parm) > 0){
					$tmp_table_cell = "<td style='".$num_total_style."'>".$tmp_sub_due_formatted."</td>";
				}else{
					$tmp_table_cell = "";
				}
				 
				 
				 
				if($show_tax_col){
					$tmp_tax_col = "<td style='".$sub_total_num_style."'>".$tmp_sub_tax_amount_formatted."</td>";
						
				}else{
					$tmp_tax_col = "";
						
				}
				 
				// handle grandtotals
				$tmp_total_col_count = count( $include_cols);
				$tmp_row = "\n<tr><td colspan=$tmp_total_col_count> &nbsp; </td></tr><tr><td $col_span $sub_total_style>Totals:</td>";
				foreach( $include_cols as $col_key => $val){
					$cur_style = $col_formats[$col_key];
					if( isset( $col_labels[$col_key] )){
						$cur_label = $col_labels[$col_key];
					}else{
						$cur_label = "";
					}
					if( $col_key == "description" ){
						$cur_width = " width='$desc_width' ";
						 
					}else{
						$cur_width = "";
					}
					// get actual data.
					if( $col_key == "tax"  ){
						$cur_data_value = $tmp_sub_tax_amount_formatted;
					}else if( $col_key == "billed"  ){
						$cur_data_value = $tmp_sub_amount_formatted;
					}else if( $col_key == "recv"  ){
						$cur_data_value = $tmp_sub_received_formatted;
					}else if( $col_key == "adj"  ){
						$cur_data_value = $tmp_sub_adjustments_formatted;
					}else if( $col_key == "bal"  ){
						$cur_data_value = $tmp_sub_balance_formatted;
					}else if( $col_key == "due"  ){
						$cur_data_value = $tmp_sub_due_formatted;
					}else{
						continue;
					}

					$tmp_row = $tmp_row."<td style='$sub_total_num_style'>$cur_data_value</td>";

				}


				$tmp_row = $tmp_row."</tr>";
				 
				$tmp_html = $tmp_html.$tmp_row;
				/*
				 $tmp_html = $tmp_html."<tr><td ".$col_span." ".$sub_total_style.">Totals:</td>".$tmp_tax_col."<td style='".$num_total_style."'>".$tmp_sub_amount_formatted."</td><td style='".$num_total_style."'>".$tmp_sub_received_formatted."</td><td style='".$num_total_style."'>".$tmp_sub_adjustments_formatted."</td><td style='".$num_total_style."'>".$tmp_sub_balance_formatted."</td>".$tmp_table_cell."</tr>";

				 */
			}
			//	print "<br>(total from after if) Amount due: ".$tmp_sub_due_formatted;
			$tmp_html = $tmp_html.$html_table_end;



			if( $output_wanted == 'detail_table'){
				$values[$cid][$token_long] = $tmp_html;
				$format = '';
				$this->populate_default_value(  $values, $contactIDs , $token_long, $token_long,   "Nothing Found for this contact", $format);

				//$money_format = 'USDmoney' ;

			}else if($output_wanted == 'amount_balance'){
				//print "<Br><br>out put wanted : ".$output_wanted;
				//print "<br> value: ".$tmp_sub_balance_formatted."<br>";
				$values[$cid][$token_long] =  $tmp_sub_balance_formatted;

			}else if($output_wanted == 'amount_due'){
				$values[$cid][$token_long] =  $tmp_sub_due_formatted ;
				// print "<br>Output wanted: amount_due (total) Amount due: ".$tmp_sub_due_formatted;
			}



		}
		 





		//populate_default_value(  $values, $contactIDs , $token_obligation_total_short, $token_obligation_total_long,   $obligation_total, $money_format);


	}

	
	function populate_default_value(  &$values, $contactIDs , $token_short, $token_long,   $default_msg, $format ){
	
		if( count($contactIDs) == 0 ){
			// no contacts, nothing to do.
			return;
		}
		 
	
		if( count($values) == 0 ){
	
			return;
		}
	
		foreach ( $contactIDs as $cid ) {
			if(array_key_exists($cid,  $values)){
				if ( $values[$cid][$token_short] == ""){
					if (is_array ($default_msg )){
						if($format == 'USDmoney'){
							$tmp_msg = '$ '.number_format($default_msg[$cid] , 2)  ;
						}else{
							$tmp_msg = $default_msg[$cid] ;
						}
						 
						$values[$cid][$token_short] = $values[$cid][$token_long] = $tmp_msg;
						 
					}else{
						$values[$cid][$token_short] = $values[$cid][$token_long] = $default_msg;
					}
	
				}
	
			}
		}
	
	}
	 
	function getNextPledgePaymentId($pledge_id){
		 
		$next_pledge_payment = array();
		$sql = "SELECT id, scheduled_date, status_id
			FROM civicrm_pledge_payment
			WHERE
			status_id IN ( 2, 6 )
			AND pledge_id = ".$pledge_id."
			GROUP BY pledge_id
			ORDER BY pledge_id, scheduled_date" ;

		//	print "<br>sql: ".$sql."<br>";


		$dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
		if($dao->fetch()){
			$next_pledge_payment['id'] = $dao->id;
			$next_pledge_payment['due_date'] = $dao->scheduled_date;
			$next_pledge_payment['status_id'] = $dao->status_id;

		}

		$dao->free();

		//	print "Size of array: ".sizeof($next_pledge_payment)."<br>";
		//	print_r( $next_pledge_payment);

		return $next_pledge_payment;



	}

	function modifyPledgeSchedule($pledge_ids, $payment_start_date, $pledge_freq_unit, $pledge_freq_interval, $pledge_num_installments){

		 
		$pledges_updated = 0;
		 
		$pledge_ids_as_list = "";
		$i = 1;
		foreach($pledge_ids as $pid){
			 
			$pledge_ids_as_list = $pledge_ids_as_list." ".$pid;
			if($i < sizeof($pledge_ids) ){
				$pledge_ids_as_list = $pledge_ids_as_list.",";
			}
			$i += 1;
		}
		 
		$pledge_freq_day = 1 ;
		 
		$sql_not_completed =  "SELECT p.id as pledge_id, p.amount AS pledge_amount, sum( pp.scheduled_amount ) AS amount_expected , c.id AS contact_id, c.external_identifier ,
   c.sort_name AS sort_name
FROM civicrm_contact c, civicrm_pledge p, civicrm_pledge_payment pp
WHERE c.id = p.contact_id
AND p.id = pp.pledge_id
and pp.status_id IN ( 2, 6)
AND p.status_id IN ( 5, 6, 2 )
GROUP BY pp.pledge_id ";


		$sql_completed = "SELECT p.id as pledge_id , p.status_id as pledge_status_id,  p.amount AS pledge_amount, ifnull( sum( pp.actual_amount ), 0  )  AS amount_already_paid,  c.id AS contact_id, c.external_identifier ,
 c.sort_name AS sort_name, max( date(pp.scheduled_date) ) as last_payment_date  , count(pp.id) as num_completed_installments
FROM civicrm_contact c, civicrm_pledge p, civicrm_pledge_payment pp
WHERE c.id = p.contact_id
AND p.id = pp.pledge_id
and pp.status_id = 1
AND p.status_id IN ( 5, 6, 2 )
GROUP BY pp.pledge_id";

		 
		// Only get record if pledge is pending, in progress or overdue.
		$sqlstr = "select p.id AS pledge_id, p.status_id, p.amount AS pledge_amount,
   		  ifnull(t1.amount_already_paid, 0 ) as amount_already_paid , ifnull(t2.amount_expected, 0 ) as amount_expected,
   		 p.amount - ifnull(t1.amount_already_paid, 0 )   as remaining_amount,
   		 t1.num_completed_installments as num_completed_installments
		FROM civicrm_pledge p
		LEFT JOIN ( ".$sql_completed." ) as t1 ON  p.id = t1.pledge_id
		LEFT JOIN ( ".$sql_not_completed."  ) as t2 ON p.id = t2.pledge_id
		WHERE
		p.status_id IN (5, 6, 2)
		and p.id in (".$pledge_ids_as_list.")
		and p.amount - ifnull(t1.amount_already_paid, 0 )  > 0" ;

		// print "<br>sql: ".$sqlstr;

		$dao  =  & CRM_Core_DAO::executeQuery( $sqlstr,   CRM_Core_DAO::$_nullArray ) ;

		$rec_count = 0;
		while ( $dao->fetch( ) ) {
			$pledge_id = $dao->pledge_id;
			$remaining_amount = $dao->remaining_amount;
			$pledge_total_amount = $dao->pledge_amount;
			$num_completed_installments = $dao->num_completed_installments;

			// print "<br>remaining amount: ".$remaining_amount;
			self::deleteOpenPledgePayments( $pledge_id ) ;

			$installment_amount = $remaining_amount / $pledge_num_installments;
			//print "<br>pledge id: ".$pledge_id." remaining amount: ".$remaining_amount." installment amount: ".$installment_amount;

			$install_rounded = round( $installment_amount, 2 );

			$tmp_full_amt = $install_rounded * $pledge_num_installments;
			$tmp_full_amt_rounded = round( $tmp_full_amt, 2 );

			//print "<hr><Br><br> Install amount rounded: ".$install_rounded;
			//print "<Br><br> Full amount rounded: ".$tmp_full_amt_rounded;
			//print "<Br><br> Pledge balance amount: ".$remaining_amount;
			if( $tmp_full_amt_rounded <> $remaining_amount ){
				//print "<br>Rounding issue, first installment amount will be different than others. ";
				$diff_tmp = $tmp_full_amt_rounded  - $remaining_amount;
				$first_installment_amount = $install_rounded -  $diff_tmp;
			}else{
				//print "<br> No rounding issue ";
				$first_installment_amount = $install_rounded;
			}

			$cur_inst = 0;
			while($cur_inst < $pledge_num_installments){
				// Do date arithmetic via MySQL
				// SQL date must be formatted as yyyymmdd
				list($month, $day, $year) = explode('/', $payment_start_date);
				$tmp_start_date = $year.$month.$day;
					
					
				$interval_num = $cur_inst * $pledge_freq_interval;
				$tmp_cur_payment_date = "'$tmp_start_date' + INTERVAL $interval_num $pledge_freq_unit";
					
				if( $cur_inst == 0){
					$install_amt_for_sql = $first_installment_amount;
						
				}else{
					$install_amt_for_sql = $install_rounded;
				}


				$sql_insert = "INSERT INTO civicrm_pledge_payment ( `pledge_id`, `contribution_id`, `scheduled_amount`,
				`actual_amount`, `currency`, `scheduled_date`, `reminder_date`, `reminder_count`, `status_id`)
				VALUES ( '$pledge_id', NULL, '$install_amt_for_sql', NULL, NULL, $tmp_cur_payment_date , NULL, '0', '2') ";

				//print "<br>sql for insert: ".$sql_insert;
				$dao_insert = & CRM_Core_DAO::executeQuery( $sql_insert,   CRM_Core_DAO::$_nullArray ) ;
				$dao_insert->free();
				/*
				 //print "<br>pledge date: ".$pledge_date;
				 // create the next pending pledge payment. ( Status of 2 = pending )
				 $pledge_payment_parms = array(
				 'pledge_id' => $pledge_id ,
				 'status_id'  => 2,
				 'scheduled_amount' => $installment_amount ,
				 'option.create_new' => true,
				 'scheduled_date' => date("Ymd",strtotime($tmp_cur_payment_date)),
				 'version' =>3
				 );

				 //  'scheduled_date' => date("Ymd",strtotime($tmp_cur_payment_date)),
				 $pledge_payment_tmp =& civicrm_api('PledgePayment','Create', $pledge_payment_parms ) ;
				 if( $pledge_payment_tmp['is_error'] == 1 ){
					print "<br><b>Error creating pledge payment: ".$pledge_payment_tmp['error_message']."</b><br>";
					print_r( $pledge_payment_tmp);
					}else{

					}
					*/
				// Done creating pledge payment

				$cur_inst += 1;
			}
			//
			// Need to update civicrm_pledge table with new schedule.

			// Next variable is necessary to prevent CiviCRM from thinking that the original pledge amount was changed,
			// as it assumes that num_installments * original_installment_amount = total_amount
			$tmp_installments =  $num_completed_installments + $pledge_num_installments;
			$tmp_install_amount = $pledge_total_amount / $tmp_installments;

			$pledge_start_date = $payment_start_date ;
			$start_date_as_ts = strtotime ( $pledge_start_date );
			if( $pledge_freq_unit == "month"){
				$pledge_freq_day = date ( "j", $start_date_as_ts);

			}else if($pledge_freq_unit == "week" ){
				$pledge_freq_day = date ( "N", $start_date_as_ts);
				 
			}else{
				$pledge_freq_day = "1";
			}

			$sql_update = "UPDATE civicrm_pledge p
			SET p.frequency_unit  = '$pledge_freq_unit',
			frequency_interval = '$pledge_freq_interval',
			installments = '$tmp_installments' ,
			original_installment_amount = '$tmp_install_amount' ,
			frequency_day = '$pledge_freq_day'
			WHERE p.id = ".$pledge_id  ;

			$dao_tmp = & CRM_Core_DAO::executeQuery( $sql_update,   CRM_Core_DAO::$_nullArray ) ;
			$dao_tmp->free();
				
				
			//print "<br>pledge id: ".$pledge_id." amount remaining: ".$remaining_amount;
			$pledges_updated += 1;
			 
		}
		 
		 
		$dao->free();
		return $pledges_updated;

	}

	function deleteOpenPledgePayments($pledge_id) {
		$delete_sqlstr = "DELETE from civicrm_pledge_payment
		where civicrm_pledge_payment.pledge_id = ".$pledge_id."
		and (civicrm_pledge_payment.status_id = 2 OR civicrm_pledge_payment.status_id = 6)  ";
		 
		//print "<br><br>delete_sqlstr: ".$delete_sqlstr;
		 
		$dao2  =  & CRM_Core_DAO::executeQuery( $delete_sqlstr,   CRM_Core_DAO::$_nullArray ) ;
		 
		$dao2->free( );
		 
	}
	
	/*****************************************************************************************************/
	function get_due_to_date_amount( $entity_type , $entity_id,  $end_date_parm, $line_item_id) {
		// TODO: This data sould be calculated in the main SQL statement, not here.
		 
		// echo "<br>Inside get due to date amount, ".$entity_type." , ".$entity_id." , ".$end_date_parm;
		 
		//  $cid_list =  get_contact_ids_for_sql($contactIDs) ;
	
		$tmp_contrib_where = '';
		$tmp_pledge_pay_where = '';
		if(strlen($end_date_parm) > 0 ){
			$tmp_contrib_where = " AND contrib.receive_date <= '".$end_date_parm."'";
			$tmp_pledge_pay_where = " and pp.scheduled_date <= '".$end_date_parm."'";
			 
		}else{
			$tmp_contrib_where = " AND contrib.receive_date <= now()";
			$tmp_pledge_pay_where = " and pp.scheduled_date <=  now()";
			 
			 
		}
	
		$currency_symbol = "$";
		$sql_str = "";
		//print "<br>List for process amount due:".$cid_list;
		if( $entity_type == 'contribution'){
			print "<br><h2>ERROR: entity_type: contribution NOT supported.</h2>";
			return "";
			require_once ('utils/Entitlement.php');
			$entitlement = new Entitlement();
	
		 if( $entitlement->isRunningCiviCRM_4_3()){
		 	 
		 	 
		 	if(strlen( $line_item_id) == 0 ){
	
		 		print "<br><h2>Error: Line item id is empty! ";
		 		return "";
	
		 	}
		 	 
		 	$sql_str = "( SELECT li.line_total as total_amount,
   	contrib.receipt_date, contrib.currency, contrib.source, val.label
	FROM civicrm_line_item li JOIN civicrm_contribution contrib ON li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution' ,
	civicrm_option_value val,
	civicrm_option_group grp
	WHERE
	li.id = ".$line_item_id.
		" AND contrib.contribution_status_id = val.value
	AND  val.option_group_id = grp.id
	AND grp.name = 'contribution_status'
	and contrib.contribution_status_id = val.value
	and val.name not in ('Completed', 'Cancelled' )
	and contrib.contribution_recur_id is null".$tmp_contrib_where.
		" and contrib.is_test = 0)
	UNION ALL (
		SELECT li.line_total as total_amount,
   	contrib.receipt_date, contrib.currency, contrib.source, val.label
	FROM civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant'
	 JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id
				join civicrm_contribution contrib ON  ep.contribution_id = contrib.id ,
	civicrm_option_value val,
	civicrm_option_group grp
	WHERE
	li.id = ".$line_item_id.
		" AND contrib.contribution_status_id = val.value
	AND  val.option_group_id = grp.id
	AND grp.name = 'contribution_status'
	and contrib.contribution_status_id = val.value
	and val.name not in ('Completed', 'Cancelled' )
	and contrib.contribution_recur_id is null".$tmp_contrib_where.
		" and contrib.is_test = 0
	
	
	)";
	
		 }else{
		 	$sql_str = "SELECT contrib.total_amount,
   	contrib.receipt_date, contrib.currency, contrib.source, val.label
	FROM civicrm_contribution contrib  ,
	civicrm_option_value val,
	civicrm_option_group grp
	WHERE
	contrib.id = ".$entity_id.
		" AND contrib.contribution_status_id = val.value
	AND  val.option_group_id = grp.id
	AND grp.name = 'contribution_status'
	and contrib.contribution_status_id = val.value
	and val.name not in ('Completed', 'Cancelled' )
	and contrib.contribution_recur_id is null".$tmp_contrib_where.
		" and contrib.is_test = 0";
	
	
		 }
	
	
		}else if($entity_type == 'pledge'){
	
			$sql_str = "SELECT   pp.scheduled_amount as total_amount,
pp.scheduled_date as date, ' ' as currency, 'pledge' as source, val.label as label
FROM  `civicrm_pledge` AS p, civicrm_pledge_payment as pp,
civicrm_option_value  val,
civicrm_option_group grp
WHERE p.id = ".$entity_id."
and p.id = pp.pledge_id
and val.name in ('Overdue', 'Pending' )".
	$tmp_pledge_pay_where.
	" and pp.status_id = val.value
AND  val.option_group_id = grp.id
AND grp.name = 'contribution_status'
and p.is_test = 0
	order by 1";
		}else if( $entity_type == 'recurring'){
			$sql_str = "SELECT (li.line_total * t2.missing_count) as total_amount,
   		contrib.receipt_date, contrib.currency, contrib.source
	FROM civicrm_line_item li
	JOIN civicrm_contribution contrib ON li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'
	LEFT JOIN (select r.id as recur_id ,  count( contrib.id) as missing_count
                  		FROM `civicrm_contribution_recur` r
                  		LEFT JOIN civicrm_pogstone_recurring_contribution contrib on r.id = contrib.contribution_recur_id
				WHERE contrib.receive_date < DATE( now( ) )
				GROUP BY r.id ) as t2 ON t2.recur_id = contrib.contribution_recur_id
	WHERE
	li.id = ".$line_item_id."   ";
	
		}else{
			print "<br>Unknown entity type: ".$entity_type;
	
		}
	
		//  print "<br><br>SQL:   ".$sql_str;
	
		$prev_cid = "";
		$cur_cid_html = "";
		$sub_total = 0;
	
		$tmp_amount_due = array();
		if(strlen($sql_str) == 0) {
	
			return 0;
		}
	
		$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;
		$sub_total = 0;
		while ( $dao->fetch( ) ) {
			 
			 
			 
			$total_amount= $dao->total_amount;
			//$receipt_date = $dao->receipt_date;
			//$source_desc = $dao->source;
			$currency = $dao->currency;
			//$status = $dao->label;
	
			 
			$sub_total =  $sub_total + $total_amount;
	
	
		}
	
		$dao->free( );
		 
	
	
	
		//  print_r($sub_total);
		 
	
		return $sub_total;
	}
	 
	 
}


?>