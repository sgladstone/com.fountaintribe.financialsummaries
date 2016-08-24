<?php

class TaxTools{



	function get_sql_for_tax_letter(&$contactIDs, &$ct_prefix_id,  &$only_tax_deductable_parm, &$start_date, &$end_date, &$formatted_date_range, $section_type){

		if($section_type <> 'main' AND  $section_type <> 'special'){
			print "<br>Error: In function get_sql_for_tax_letter, parameter 'section_type' is unrecognized: ".$section_type;
			 
			 
		}
		//  print "<br>Inside get sql for tax year: ".$only_tax_deductable_parm ;

		require_once('utils/util_custom_fields.php');

		$custom_field_group_label = "Extra Contribution Info";
		$custom_field_third_party_label = "Third Party Payor";

		$customFieldLabels = array($custom_field_third_party_label );
		$extended_contrib_table = "";
		$outCustomColumnNames = array();


		$error_msg = getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, $extended_contrib_table, $outCustomColumnNames ) ;

		$third_party_column_name  =  $outCustomColumnNames[$custom_field_third_party_label];

		if(strlen( $third_party_column_name) == 0){
			//print "<br>Error: There is no field with the name: '$custom_field_third_party_label' ";
			return;
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
		// print "<br>TAX pledge custom field names: ";
		// print_r($outAussiePledgeCustomColumnNames);
		//

		if(strlen( $extended_aussie_contrib_table) > 0 && strlen( $extended_aussie_pledge_table) > 0 && ( false == (empty($outAussiePledgeCustomColumnNames))   )){
			//  print "<br>TAX Aussie GST contrib table found: ".$extended_aussie_contrib_table;
			//  print "<br>TAX Aussie GST pledge table found: ".$extended_aussie_pledge_table;
			$show_id_column = true;
			$show_tax_column = true;

		}else{
			$show_id_column = false;
			$show_tax_column = false;

		}

		if($show_tax_column){
			 
			 
			$tax_contrib_from_sql =  " LEFT JOIN ".$extended_aussie_contrib_table." ctax ON  contrib.id = ctax.entity_id ";
			$tax_contrib_select_sql = " ctax.".$outAussieCustomColumnNames[$tmp_contrib_tax_amount_label]." as tax_amount_held, ";
			 
			 
			 
			//$main_tax_select_sql = "f1.tax_amount as tax_amount, ";


		}else{
			 
			$tax_contrib_select_sql = "";
			//$tax_recur_select_sql = "";
			 

		}

		//print "<h2>Inside tax tokens, extra sql for AU: <h2>";
		//print $tax_contrib_select_sql;

		require_once('RelationshipTools.php');
		$tmpRelTools = new RelationshipTools();
		 
		$cid_list =  $tmpRelTools->get_contact_ids_for_sql($contactIDs) ;
		 
		if( strlen($start_date) > 0 && strlen($end_date) > 0){

			$s_year = substr( $start_date, 0, 4) ;
			$s_month = substr( $start_date, 4, 2) ;
			$s_day = substr( $start_date, 6, 2) ;

			$e_year = substr( $end_date, 0, 4) ;
			$e_month = substr( $end_date, 4, 2) ;
			$e_day = substr( $end_date, 6, 2) ;

			$sql_start_date = date( 'Y-m-d',  mktime(0, 0, 0, $s_month, $s_day, $s_year) );
			$sql_end_date = date( 'Y-m-d',  mktime(0, 0, 0, $e_month, $e_day, $e_year) ) ;
			 
			$tmp_date_clause = " AND date(contrib.receive_date) >= '$sql_start_date' AND date(contrib.receive_date) <= '$sql_end_date' ";

			require_once 'FormattingUtils.php';

			$FormattingUtil = new FormattingUtils();
			$input_format = 'yyyy-mm-dd';
			$formatted_start_date  = $FormattingUtil->get_date_formatted_short_form($sql_start_date, $input_format);
			$formatted_end_date  = $FormattingUtil->get_date_formatted_short_form($sql_end_date, $input_format);


			$formatted_date_range = $formatted_start_date." - ".$formatted_end_date;

			 
		}else{
			print "<br><br>Error: start_date and end_date are required.";
			 
		}
		 
		 
		 
		if($only_tax_deductable_parm){
			$tmp_only_tax_deductable = " and ct.is_deductible = 1 " ;
			 
		}else{
			$tmp_only_tax_deductable = "";
			 
		}
		 


		// new stuff, deal with financial types
		require_once('utils/finance/FinancialCategory.php') ;

		$tmpFinancialCategory = new FinancialCategory();

		$prefix_array = array();
		$prefix_array[] = $ct_prefix_id ;
		$where_clause_contrib_type_ids  = $tmpFinancialCategory->getContributionTypeWhereClauseForSQL($prefix_array);
		if(strlen($where_clause_contrib_type_ids) > 0 ){
			$where_clause_contrib_type_ids = " AND ".$where_clause_contrib_type_ids ;
		}

		// print "<br>where clause for contrib type ids: ".$where_clause_contrib_type_ids;

		//
		// require_once ('Entitlement.php');
		//	$entitlement = new Entitlement();

		$sql_str  = "";



		$deduct_amt_sql = " CASE WHEN ( ct.is_deductible = 1 AND contrib.non_deductible_amount is not null
	     AND contrib.non_deductible_amount > 0
	     AND contrib.total_amount = sum(li.line_total) )
	                               THEN contrib.total_amount - contrib.non_deductible_amount
	     WHEN (ct.is_deductible = 1 AND (contrib.non_deductible_amount is null
	     OR contrib.non_deductible_amount = 0
	     OR contrib.total_amount <> sum(li.line_total) ))  THEN  sum(li.line_total)
	     ELSE 0 END ";

	 $gen_where_clause = " AND li.line_total <> 0
	AND ( ct.name NOT LIKE 'adjustment-%'  AND  ct.name NOT LIKE '%---adjustment-%' )
	AND contrib.contribution_status_id = val.value
	AND  val.option_group_id = grp.id
	AND grp.name = 'contribution_status'
	and contrib.contribution_status_id = val.value
	 ".$where_clause_contrib_type_ids."
	AND val.name in ('Completed' ) ".$tmp_only_tax_deductable."
	and contrib.is_test = 0 ".$tmp_date_clause;




	 if($section_type == 'special'){

	 	$having_tmp = " having ct.is_deductible = 1 AND contrib.non_deductible_amount is not null
	AND contrib.non_deductible_amount > 0
	AND contrib.total_amount <> sum(li.line_total)  ";

	 	$gen_group_by  = " group by li.entity_id , li.financial_type_id ";

	 	$gen_select_clause =  "contrib.id,
	 	contrib.total_amount   as total_amount ,
	 	contrib.total_amount - contrib.non_deductible_amount as deductible_amount,
	 	civicrm_currency.symbol as symbol,
	 	contrib.non_deductible_amount as non_deductible_amount ,
	 	MONTH(contrib.receive_date) as mm_date,
	 	DAY(contrib.receive_date) as dd_date, YEAR(contrib.receive_date) as yyyy_date,
	 	$extended_contrib_table.$third_party_column_name as third_party,
	 	contrib.currency,  contrib.source  as source,
	 	".$tax_contrib_select_sql." val.label, ct.id as financial_type_id ,
	     ct.name as contrib_type_name, ct.is_deductible, contrib.receive_date as recv_date
	     ";


	 }else{
	 	$having_tmp = "";
	 	$gen_group_by  = " group by li.entity_id , li.financial_type_id ";
	 	$gen_select_clause =  "contrib.id,
		  sum(li.line_total)    as li_total_amount ,
		  sum(li.line_total) - ".$deduct_amt_sql." as li_non_deductible_amount,
		  civicrm_currency.symbol as symbol,
	          ".$deduct_amt_sql." as li_deductible_amount ,
	          MONTH(contrib.receive_date) as mm_date,
	          DAY(contrib.receive_date) as dd_date, YEAR(contrib.receive_date) as yyyy_date,
	          $extended_contrib_table.$third_party_column_name as third_party,
	          contrib.currency,  contrib.source  as source,
	          ".$tax_contrib_select_sql." val.label, ct.id as financial_type_id ,
	     ct.name as contrib_type_name, ct.is_deductible, contrib.receive_date as recv_date
	     ";
	 }


	 $sql_part_a = "SELECT contrib.contact_id, ".$gen_select_clause."
	 FROM civicrm_contribution contrib join civicrm_line_item li ON li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'
	 join civicrm_financial_type ct ON li.financial_type_id = ct.id
	 left join $extended_contrib_table on contrib.id = $extended_contrib_table.entity_id
	 left join civicrm_currency on contrib.currency = civicrm_currency.name
	 ".$tax_contrib_from_sql." ,
	 civicrm_option_value val,
	 civicrm_option_group grp
	 WHERE
	 contrib.contact_id in ( $cid_list )
	 and (  $extended_contrib_table.$third_party_column_name is NULL )
	 AND contrib.contribution_recur_id IS NULL ".
	 $gen_where_clause.$gen_group_by.$having_tmp;


	 $sql_part_a_event_income = "SELECT contrib.contact_id, ".$gen_select_clause."
	 FROM civicrm_line_item li join civicrm_participant p ON li.entity_id = p.id AND li.entity_table = 'civicrm_participant'
	 JOIN civicrm_participant_payment ep ON ifnull( p.registered_by_id, p.id) = ep.participant_id
	 join civicrm_contribution contrib ON  ep.contribution_id = contrib.id
	 join civicrm_financial_type ct ON li.financial_type_id = ct.id
	 left join $extended_contrib_table on contrib.id = $extended_contrib_table.entity_id
	 left join civicrm_currency on contrib.currency = civicrm_currency.name
	 ".$tax_contrib_from_sql." ,
	 civicrm_option_value val,
	 civicrm_option_group grp
	 WHERE
	 contrib.contact_id in ( $cid_list )
	 and (  $extended_contrib_table.$third_party_column_name is NULL )  ".
	 $gen_where_clause.$gen_group_by.$having_tmp ;



	 $sql_third_party = "SELECT $extended_contrib_table.$third_party_column_name  as contact_id, ".$gen_select_clause."
	 FROM civicrm_contribution contrib join civicrm_line_item li ON li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'
	 join civicrm_financial_type ct ON li.financial_type_id = ct.id
	 join $extended_contrib_table ON contrib.id = $extended_contrib_table.entity_id
	 left join civicrm_currency ON contrib.currency = civicrm_currency.name
	 ".$tax_contrib_from_sql." ,
	 civicrm_option_value val,
	 civicrm_option_group grp
	 WHERE
	 $extended_contrib_table.$third_party_column_name in ($cid_list)
	 AND contrib.contribution_recur_id IS NULL ".
	 $gen_where_clause.$gen_group_by.$having_tmp;

	 $sql_third_party_event_income = "SELECT $extended_contrib_table.$third_party_column_name  as contact_id, ".$gen_select_clause."
	 FROM civicrm_line_item li join civicrm_participant p ON li.entity_id = p.id AND li.entity_table = 'civicrm_participant'
	 JOIN civicrm_participant_payment ep ON ifnull( p.registered_by_id, p.id) = ep.participant_id
	 join civicrm_contribution contrib ON  ep.contribution_id = contrib.id
	 join civicrm_financial_type ct ON li.financial_type_id = ct.id
	 join $extended_contrib_table ON contrib.id = $extended_contrib_table.entity_id
	 left join civicrm_currency ON contrib.currency = civicrm_currency.name
	 ".$tax_contrib_from_sql." ,
	 civicrm_option_value val,
	 civicrm_option_group grp
	 WHERE
	 $extended_contrib_table.$third_party_column_name in ($cid_list) ".
	 $gen_where_clause.$gen_group_by.$having_tmp;


	 $tmp_first_contrib = " select contrib.id , contrib.contact_id ,contrib.source, contrib.currency, contrib.check_number,
	 contrib.contribution_status_id,   contrib.contribution_recur_id , contrib.receive_date, contrib.total_amount, contrib.payment_instrument_id,
	 contrib.is_test
	 FROM civicrm_contribution contrib
	 WHERE contrib.contribution_recur_id is NOT NULL
	 AND (contrib.contribution_status_id = 1 OR contrib.contribution_status_id = 2 ) AND contrib.contact_id in ( $cid_list )
	 GROUP BY contrib.contribution_recur_id
	 HAVING contrib.receive_date = min(contrib.receive_date) ";

	 /*
	  civicrm_line_item li JOIN ( $tmp_first_contrib ) contrib ON  li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'
	  JOIN civicrm_contribution rcontribs ON rcontribs.contribution_recur_id = contrib.contribution_recur_id AND rcontribs.contribution_status_id = 1
	  */

	 $sql_recurring_income = "SELECT contrib.contact_id, ".$gen_select_clause."
	 FROM civicrm_line_item li JOIN ( $tmp_first_contrib ) firstcontrib ON  li.entity_id = firstcontrib.id AND li.entity_table = 'civicrm_contribution'
	 JOIN civicrm_contribution contrib ON contrib.contribution_recur_id = firstcontrib.contribution_recur_id AND contrib.contribution_status_id = 1
	 join civicrm_financial_type ct ON li.financial_type_id = ct.id
	 left join $extended_contrib_table on firstcontrib.id = $extended_contrib_table.entity_id
	 left join civicrm_currency on contrib.currency = civicrm_currency.name
	 ".$tax_contrib_from_sql." ,
	 civicrm_option_value val,
	 civicrm_option_group grp
	 WHERE
	 contrib.contact_id in ( $cid_list )
	 and (  $extended_contrib_table.$third_party_column_name is NULL )
	 AND contrib.contribution_recur_id IS NOT NULL ".
	 $gen_where_clause. " Group by contrib.id, li.financial_type_id" ;


	 //print "<br><br>".$sql_recurring_income;
	 // Put the entire SQL statement together

	 if($section_type == 'special'){

	 	$sql_str =
	 	"select contact_id , id, total_amount , non_deductible_amount, (0 - non_deductible_amount) as   deductible_amount,
		symbol, currency, source,
		mm_date, dd_date, yyyy_date  from ( ( ".$sql_part_a.
		" ) UNION ALL
		( ".$sql_third_party.
		" ) UNION ALL
		( ".$sql_part_a_event_income.
		" ) UNION ALL
		( ".$sql_third_party_event_income.
		" ) ) as t1
		group by t1.id
		order by contact_id, contrib_type_name, recv_date";

	 }else{
	 	$sql_str =
	 	"( ".$sql_part_a.
	 	" ) UNION ALL
		( ".$sql_third_party.
		" ) UNION ALL
		( ".$sql_part_a_event_income.
		" ) UNION ALL
		( ".$sql_third_party_event_income.
		" ) UNION ALL
		( ".$sql_recurring_income." )
		order by contact_id, contrib_type_name, recv_date";

	 }





	 if($only_tax_deductable_parm){
	 	// print "<Br>tax sql: ".$sql_str;
	 }

	 // print  "<Br>tax sql part b : ".$sql_str_part_b;


	 //print "<br><br>Tax sql: ".$sql_str;
	 return $sql_str;




	}


	 

	function process_tax_year( &$values, &$contactIDs , &$ct_prefix_id,  &$token_long ,  &$only_tax_deductable_parm,
			&$start_date, &$end_date, &$format_parm, &$font_size, $only_show_totals = false ){
				 
				$need_subtotals = false;
				//print "<Br> format parm: ".$format_parm;
				if(strlen($font_size) == 0){
					$font_size = "12px";
					 
					// print "<br>Using default font size";
				}

				// print "<hr><br>Inside process tax year, token: ".$token_long." start date: ".$start_date." end date: ".$end_date. " only deductible: ".$only_tax_deductable_parm;
				if( count($contactIDs) == 0 ){
					// no contacts, nothing to do.
					print "<br> No contacts, nothing to do. ";
					return;
				}

				if(strlen($start_date) == 0){
					print "<br><br>Error: Inside process_tax_year function: start_date is required.".$start_date;
					return;
					 
				}
				 
				if(strlen($end_date) == 0){
					print "<br><br>Error: Inside process_tax_year function: end_date is required.".$end_date;
					return;
					 
				}
				 
				$formatted_date_range = "";
				$sql_str = self::get_sql_for_tax_letter($contactIDs, $ct_prefix_id, $only_tax_deductable_parm , $start_date, $end_date, $formatted_date_range, 'main');



				if(strlen($sql_str) == 0){
					//print "<br>Error: Could not get tax data, sql string is blank";
					return;

				}

				if($only_tax_deductable_parm){
					 
					$column_header_for_non_deductable = '';
				}else{
					$column_header_for_non_deductable =  "<th style='font-size: ".$font_size.";text-align:right;padding-right: 10px;'>Non-deductible Amount:</th>";

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
				//print "<br>pledge custom field names: ";
				//print_r($outAussiePledgeCustomColumnNames);
				//

				if(strlen( $extended_aussie_contrib_table) > 0 && strlen( $extended_aussie_pledge_table) > 0   ){

					//  print "<br>Aussie GST table found: ".$extended_aussie_contrib_table;
					$show_id_column = true;
					$show_tax_held_column = true;

				}else{
					//print "<br>Aussie GST table NOT found. Contrib table ".$extended_aussie_contrib_table;
					//print "<br>&nbsp; Aussie GST table NOT found. Pledge table ".$extended_aussie_pledge_table;
					//print "<br>&nbsp; col names: ";
					// print_r( $outAussiePledgeCustomColumnNames );
					$show_id_column = false;
					$show_tax_held_column = false;

				}
				// print "<Br>Inside process tax year: ".$year_parm;
				if($show_tax_held_column){
					$column_header_for_tax_held = '<th style="font-size: '.$font_size.';text-align:right;padding-right: 10px;">GST:</th>';
				}
				 
				if($show_id_column){
					$column_header_for_id = '<th style="font-size: '.$font_size.';" align=left>ID:</th>';
					 
					 
					 
					 
				}
				 
				require_once 'FormattingUtils.php';

				$FormattingUtil = new FormattingUtils();

				 
				$html_table_begin =  '<table border=0 style="border-spacing: 0;  border-collapse: collapse; width: 100%">';

				$special_section_ded_subtotal = 0;
				$special_section_non_subtotal = 0;

				if($only_show_totals){
					$html_table_headers = '<tr><th style="font-size: '.$font_size.';" align=left>Date:</th>'.
							$column_header_for_id.
							'<th style="font-size: '.$font_size.';" align=left>Description:</th>'.
							'<th style="font-size: '.$font_size.'; text-align:right; padding-right: 10px;">Total Amount:</th></tr>';

				}else{
					$html_table_headers = '<tr><th style="font-size: '.$font_size.';" align=left>Date:</th>'.
							$column_header_for_id.
							'<th style="font-size: '.$font_size.';" align=left>Description:</th>'.$column_header_for_tax_held.
							'<th style="font-size: '.$font_size.
							' ;text-align:right;padding-right: 10px;">Deductible Amount:</th>'.$column_header_for_non_deductable.
							'<th style="font-size: '.$font_size.';text-align:right;padding-right: 10px;">Total Amount:</th></tr>';

							$special_section_sql = self::get_sql_for_tax_letter($contactIDs, $ct_prefix_id, $only_tax_deductable_parm , $start_date, $end_date, $formatted_date_range, 'special');
							 
							// print "<br><br>Special section sql:<br>".$special_section_sql;

							$dao_special_section  =& CRM_Core_DAO::executeQuery( $special_section_sql,   CRM_Core_DAO::$_nullArray ) ;
							 
							$special_row_count = 0;
							$special_section_html = "";
							$currency_symbol = "";
							while( $dao_special_section->fetch( ) ) {
								$tmp_ded = $dao_special_section->deductible_amount;
								$tmp_non = $dao_special_section->non_deductible_amount;
								$tmp_tot = $dao_special_section->total_amount;
								$tmp_spec_source = $dao_special_section->source;
								$received_mm_date = $dao_special_section->mm_date;
								$received_dd_date = $dao_special_section->dd_date;
								$received_yyyy_date = $dao_special_section->yyyy_date;
								$currency_symbol = $dao_special_section->symbol;
								$contact_id = $dao_special_section->contact_id;
								$contrib_id = $dao_special_section->id;

		      // Format date for screen
		      $tmp_date = $received_yyyy_date.'-'.$received_mm_date.'-'.$received_dd_date ;

		      $input_format = 'yyyy-mm-dd';
		      $tmp_date_formated  = $FormattingUtil->get_date_formatted_short_form($tmp_date, $input_format);


		      if($special_row_count == 0){
		      	$special_section_html = '<tr><td>&nbsp; </td><td style="font-size: '.$font_size.';" align=left><b>'.
				      	'Non-deductible Amounts Recorded on the Contribution Level</b></td></tr>';
		      }
		      if( $format_parm ==  'backoffice_screen'){
		      	$bo_link = '&nbsp; (<a href="/civicrm/contact/view/contribution?reset=1&id='.
	          $contrib_id .'&cid='.$contact_id.'&action=view&context=contribution&selectedChild=contribute">view detail</a>) ';
	           
		      }else{
		      	$bo_link = '';
		      	 
		      }
		       
		      // format numbers as currency
		      $tmp_ded_formatted = $currency_symbol.number_format($tmp_ded, 2 );
		      $tmp_non_formatted = $currency_symbol.number_format($tmp_non, 2 );
		       
		      if($only_tax_deductable_parm){
		      	$tmp_non_col = '';
		      }else{
		      	$tmp_non_col =    '<td style="font-size: '.$font_size.';text-align:right;padding-right: 10px;">'.$tmp_non_formatted.'</td>';
		      }
		       
		      $special_section_html = $special_section_html.'<tr><td style="font-size: '.$font_size.';" align=left>'.$tmp_date_formated.'</td>'.
				      '<td style="font-size: '.$font_size.';" align=left>'.'Contrib. Total Amount: '.$tmp_tot.'  '.$tmp_spec_source.' &nbsp;'.$bo_link.'</td>'.
				      '<td style="font-size: '.$font_size.';text-align:right;padding-right: 10px;">'.$tmp_ded_formatted.'</td>'.$tmp_non_col.
				      '<td style="font-size: '.$font_size.';text-align:right;padding-right: 10px;">&nbsp</td></tr>';

		      $special_section_ded_subtotal = $special_section_ded_subtotal + $tmp_ded;
		      $special_section_non_subtotal = $special_section_non_subtotal + $tmp_non ;

		      $special_row_count++;
		       
							}
							 
							if( $special_row_count > 0 ){
								 
								$special_section_ded_subtotal_formatted = $currency_symbol.number_format($special_section_ded_subtotal, 2 );
								$special_section_non_subtotal_formatted = $currency_symbol.number_format($special_section_non_subtotal, 2 );
								 
								if($only_tax_deductable_parm){
									$tmp_non_col = '';
								}else{
									$tmp_non_col = '<td style="font-size: '.$font_size.';text-align:right;padding-right: 10px;"><b>'.$special_section_non_subtotal_formatted.'</b></td>';
								}

								$special_section_html = $special_section_html.'<tr><td style="font-size: '.$font_size.';" align=left>&nbsp;</td>'.
										'<td style="font-size: '.$font_size.';" align=right><b>Special Section Sub-Total:</b></td>'.
										'<td style="font-size: '.$font_size.';text-align:right;padding-right: 10px;"><b>'.$special_section_ded_subtotal_formatted.'</b></td>'.$tmp_non_col.
										'<td style="font-size: '.$font_size.';text-align:right;padding-right: 10px;">&nbsp</td></tr>';
								 
							}
							 
							$dao_special_section->free();
							 

							 
							//print "<br><br>SQL for special section: ".$special_section_sql;

				}
				$html_table_end = ' </table>  	 ';
				$prev_cid = "";
				$cur_cid_html = "";
				$sub_total = 0;


				$prev_cont_type = '';
				$cur_cont_type = '';

				$subtotal_deductable =0;
				$subtotal_nondeductable =0;
				$subtotal_tranx_amount =0;



				$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;

				$row_num =0;
				$prev_contrib_type = "";
				$tmp_tax_detail_rows = array();


				$sub_total_num_style =   "font-size: ".$font_size." ;text-align:right;padding-right: 10px; font-weight: bold;" ;

				$plain_num_style = "font-size: ".$font_size." ;text-align:right;padding-right: 10px; " ;
				$sub_total_style = "style='font-size: ".$font_size.";text-align: right; font-weight: bold;'";
				$description_style = "font-size: ".$font_size.";";
				$subtotal_deductable = 0;
				$subtotal_nondeductable = 0;
				$subtotal_tranx_amount = 0;

				$grand_total_deductable = 0;
				$grand_total_nondeductable = 0;
				$grand_total_tranx_amount = 0;


				$total_amount = 0;
				$tmp_year_sub_total = array();
				$tmp_year_sub_total_tax_deductable_amt = array();
				$tmp_year_sub_total_tax_non_deductable_amt = array() ;
				$tmp_year_sub_total_tax_held = array();



				//print "<hr><br>About to loop on dao";
				while( $dao->fetch( ) ) {
					$need_subtotals  = false;
					$row_num = $row_num + 1;
					$cur_cid = $dao->contact_id;
					$cur_contrib_type = $dao->contrib_type_name;
					// $rec_type = $dao->rec_type;
					$currency_symbol = $dao->symbol;
					$contrib_id = $dao->id;
					//     print "<br>contact: ".$cur_cid."  contrib id:".$contrib_id ;
					 
					$total_amount= $dao->li_total_amount;
					$received_mm_date = $dao->mm_date;
					$received_dd_date = $dao->dd_date;
					$received_yyyy_date = $dao->yyyy_date;

					// Format date for screen
					$tmp_date = $received_yyyy_date.'-'.$received_mm_date.'-'.$received_dd_date ;

					$input_format = 'yyyy-mm-dd';
					$tmp_date_formated  = $FormattingUtil->get_date_formatted_short_form($tmp_date, $input_format);




					$source_desc = $dao->source;
					$currency = $dao->currency;
					$type_name = $dao->contrib_type_name;
					$tax_status_raw = $dao->is_deductible;
					$third_party = $dao->third_party;
					$partial_non_deductible = $dao->li_non_deductible_amount ;
					$total_non_deductible =  $dao->li_deductible_amount;

					$tax_amount_held = $dao->tax_amount_held;

					if( $tax_status_raw == '1'){
						// Deal with partial deductible transactions, such as a $100 dinner where $20 is not deductible and $80 is.
						$tax_deductable_amt_raw = $total_non_deductible;
						$tax_non_deductable_amt_raw = $partial_non_deductible ;

					}else{
						$tax_deductable_amt_raw = 0;
						$tax_non_deductable_amt_raw = $total_amount;
					}


					/// Next 5 lines are a sarah test
					///	if($prev_cid != ""){
					$tmp_year_sub_total_tax_deductable_amt[$cur_cid]  =  $tmp_year_sub_total_tax_deductable_amt[$cur_cid] +  $tax_deductable_amt_raw ;
					$tmp_year_sub_total_tax_non_deductable_amt[$cur_cid] =  $tmp_year_sub_total_tax_non_deductable_amt[$cur_cid] +  $tax_non_deductable_amt_raw ;
					$tmp_year_sub_total[$cur_cid] =  $tmp_year_sub_total[$cur_cid] + $total_amount;
					$tmp_year_sub_total_tax_held[$cur_cid] =  $tmp_year_sub_total_tax_held[$cur_cid] + $tax_amount_held;
					//
					//	    }
					//  print "<hr>Current rec: cid: ".$cur_cid." amount: ".$total_amount;
					//  print "<br>tmp_year_sub_total for cur cid: ".$tmp_year_sub_total[$cur_cid];
					//   print "<br><br><br>prev cont. type: ".$prev_contrib_type ;
					//   print "<br>cur cont. type: ".$cur_contrib_type ;
					 
					//   print "<Br>Row num: ".$row_num." con. id: ".$cur_cid." cont. type: ".$cur_contrib_type;
					// Do subtotals for contrib types, if needed.
					if(   $prev_contrib_type <> $cur_contrib_type  &&  $prev_contrib_type <> "" ){
						//  print "<br><br><br>prev cont. type: ".$prev_contrib_type ;
						// print "<br>cur cont. type: ".$cur_contrib_type ;
						$need_subtotals = true;

						//  print "<br>Need subs";
						 
						 
					}else if($prev_cid <> $cur_cid && $prev_cid <> ""     ){
						$need_subtotals = true;
					}
					if ( ($row_num > 1) && (($prev_contrib_type <> $cur_contrib_type) || ($cur_cid != $prev_cid)) ){
						//if($need_subtotals){
						/*
						 if($prev_contrib_type <> $cur_contrib_type){
						 print "<br>Need subtotals for prev contrib type: ".$prev_contrib_type;
						 	
						 }
						 	
						 	
						 if($cur_cid != $prev_cid){
						 print "<br>Need subtotals for prev contact type: ".$prev_cid;

						 }
						 */



						$fmt_subtotal_deductable  =  $currency_symbol.number_format( $subtotal_deductable , 2);
						$fmt_subtotal_nondeductable  =  $currency_symbol.number_format( $subtotal_nondeductable , 2);
						$fmt_subtotal_tranx_amount  =  $currency_symbol.number_format( $subtotal_tranx_amount , 2);
						$fmt_subtotal_tax_held  =  $currency_symbol.number_format( $subtotal_tax_held , 2);
						 
						$tmp_sub_cid = $cur_cid;
						if($cur_cid <> $prev_cid){
							$tmp_sub_cid = $prev_cid ;
						}
						 
						 
						if($only_tax_deductable_parm){
							$tmp_total_cell_non_deductible = '';
						} else{

							$tmp_total_cell_non_deductible = "<td style='".$sub_total_num_style."'>".$fmt_subtotal_nondeductable."</td>";
						}
						 
						 
						if($show_tax_held_column){
							// print "<br>test ";
							$tmp_total_cell_tax_held = "<td style='".$sub_total_num_style."'>".$fmt_subtotal_tax_held."</td>";
							$tmp_colspan = "3";
							 
						}else{
							$tmp_total_cell_tax_held = '';
							$tmp_colspan = "2";
							 
						}
						 
		    if($only_show_totals){
		    	$tmp_tax_detail_rows[$tmp_sub_cid] = $tmp_tax_detail_rows[$tmp_sub_cid]."\n<tr >".
				    	"<td colspan=$tmp_colspan ".$sub_total_style.">".$prev_contrib_type." Sub Total:</td>".
				    	$tmp_total_cell_tax_held ."<td style='".$sub_total_num_style."'>". $fmt_subtotal_tranx_amount."</td></tr>";

		    }else{
		    	// format HTML table row of subtotals
		    	$tmp_tax_detail_rows[$tmp_sub_cid] = $tmp_tax_detail_rows[$tmp_sub_cid]."\n<tr >".
				    	"<td colspan=$tmp_colspan ".$sub_total_style.">".$prev_contrib_type." Sub Total:</td>".
				    	$tmp_total_cell_tax_held ."<td style='".$sub_total_num_style."'>".$fmt_subtotal_deductable."</td>".
				    	$tmp_total_cell_non_deductible.
				    	"<td style='".$sub_total_num_style."'>". $fmt_subtotal_tranx_amount."</td></tr>";

		    }
		     
		     
		    $subtotal_deductable = 0;
		    $subtotal_nondeductable = 0;
		    $subtotal_tranx_amount = 0;
		    $subtotal_tax_held = 0;
		     
		    	
		     
					}

					/*

					*/
					if ( $cur_cid != $prev_cid ){
						 
						// print "<br>new contact id: ".$cur_cid;
						 
						$tmp_tax_detail_rows[$cur_cid] = "";
						if ( $prev_cid != ""){
							// Wrap up table for previous contact.


							 
						}

						// start up for this contact
						$subtotal_deductable =0;
						$subtotal_nondeductable =0;
						$subtotal_tranx_amount =0;
						$subtotal_tax_held = 0;

						 
						 
						// $cur_cid_html = $cur_cid_html.$html_table_begin;

					}
					 




					$cur_cont_type = $type_name;
					// Handle subtotals if needed.
					if( $prev_cont_type <> '' &&  $cur_cont_type <> $prev_cont_type){
						 
						$cont_subtotal_deductable_fmt =  $currency_symbol.number_format($cont_subtotal_deductable, 2 );
						$cont_subtotal_nondeductable_fmt =  $currency_symbol.number_format($cont_subtotal_nondeductable, 2 );
						$cont_subtotal_tranx_amount_fmt =  $currency_symbol.number_format($cont_subtotal_tranx_amount, 2 );
						 
						$cur_cid_html = $cur_cid_html."<tr><td colspan=2 style='text-align: right;'><b>".$prev_cont_type ." Total:</b></td>".
								"<td><b>".$cont_subtotal_deductable_fmt."</b></td><td><b>".$cont_subtotal_nondeductable_fmt."</b></td><td><b>".$cont_subtotal_tranx_amount_fmt."</b></td></tr>";
						$cont_subtotal_deductable = 0 ;
						$cont_subtotal_nondeductable =0;
						$cont_subtotal_tranx_amount =0;
						 
						 
					}


					//  $tmp_date_formated = $received_mm_date.'/'.$received_dd_date.'/'.$received_yyyy_date ;


					 
					$total_amt_formatted =  $currency_symbol.number_format($total_amount, 2 );

					$tax_amount_held_formatted = $currency_symbol.number_format($tax_amount_held, 2 );

					if( $tax_status_raw == '1'){
						// Deal with partial deductible transactions, such as a $100 dinner where $20 is not deductible and $80 is.
						$tax_deductable_amt_raw = $total_non_deductible;
						$tax_non_deductable_amt_raw = $partial_non_deductible ;

					}else{
						$tax_deductable_amt_raw = 0;
						$tax_non_deductable_amt_raw = $total_amount;
					}


					$tax_deductable_amt_formated  =  $currency_symbol.number_format( $tax_deductable_amt_raw, 2 );
					$tax_non_deductable_amt_formated  =  $currency_symbol.number_format( $tax_non_deductable_amt_raw, 2 );
					// do math for subtotals.
					$subtotal_deductable = $subtotal_deductable + $tax_deductable_amt_raw;
					$subtotal_nondeductable = $subtotal_nondeductable + $tax_non_deductable_amt_raw;;
					$subtotal_tranx_amount = $subtotal_tranx_amount + $total_amount;


					// 	$cont_subtotal_deductable = $cont_subtotal_deductable  + $tax_deductable_amt_raw;
					//	$cont_subtotal_nondeductable = $cont_subtotal_nondeductable + $tax_non_deductable_amt_raw;
					//	$cont_subtotal_tranx_amount = $cont_subtotal_tranx_amount + $total_amount ;

					// Do math for grand Totals!!!
					$grand_total_deductable = $grand_total_deductable + $tax_deductable_amt_raw; ;
					$grand_total_nondeductable = $grand_total_nondeductable + $tax_non_deductable_amt_raw; ;
					$grand_total_tranx_amount = $grand_total_tranx_amount +  $total_amount;
					// $subtotal_deductable =  $subtotal_deductable + $tax_deductable_amt_raw;
					// $subtotal_nondeductable  = $subtotal_nondeductable + $tax_non_deductable_amt_raw;
					// $subtotal_tranx_amount =  $subtotal_tranx_amount + $total_amount;

					if( strlen($source_desc) > 0){
						$tmp_description = $type_name."-".$source_desc ;
					}else{
						$tmp_description = $type_name;


					}



					if( $row_num % 2  == 0){
						$css_name = "even-row";
						 
					}else{
						$css_name = "odd-row";
					}
					 
					 
					if($show_id_column){
						//print "<br>Need to show id column";
						$tmp_id_column = "<td style='font-size: ".$font_size.";'>".$contrib_id."</td>";
					}else{
						$tmp_id_column = '';
						 
					}
					 
					 
					if($only_tax_deductable_parm){
						$tmp_cell_non_deductible = '';
					} else{

						$tmp_cell_non_deductible = "<td style='font-size: ".$font_size.";'>".$tax_non_deductable_amt_formated."</td>";
					}
					 
					if($only_show_totals){
						$cur_cid_html = $cur_cid_html."<tr class='".$class_name."'><td style='font-size: ".$font_size.";'>".$tmp_date_formated."</td>".
								$tmp_id_column."<td>".$tmp_description."</td>".
								"<td>".$total_amt_formatted."</td></tr>";
								 
					}else{
						$cur_cid_html = $cur_cid_html."<tr class='".$class_name."'><td style='font-size: ".$font_size.";'>".$tmp_date_formated."</td>".
								$tmp_id_column."<td>".$tmp_description."</td><td>"
										.$tax_deductable_amt_formated."</td><td>".$total_amt_formatted."</td></tr>";

					}


					$prev_cont_type = $cur_cont_type;
					$prev_cid = $cur_cid;
					 
					 
					////
					if($only_tax_deductable_parm){
						$tmp_cell_non_deductible = '';
					} else{

						$tmp_cell_non_deductible = "<td style='".$plain_num_style."'>".$tax_non_deductable_amt_formated."</td>";
					}
					 
					if($show_tax_held_column){
						// print "<br>test ";
		   	$tmp_cell_tax_held = "<td style='".$plain_num_style."'>".$tax_amount_held_formatted."</td>";
		   	 
		   }else{
		   	$tmp_cell_tax_held = '';
		   	 
		   }
		    
		    
		    
		    
		   if($only_show_totals){

		   	$tmp_tax_detail_rows[$cur_cid] =  $tmp_tax_detail_rows[$cur_cid]."\n<tr class=".$css_name."><td style='font-size: ".$font_size.";'>".
				   	$tmp_date_formated."</td>".$tmp_id_column.
				   	"<td style='".$description_style."'>".$tmp_description."</td>".$tmp_cell_tax_held.
				   	"<td style='".$plain_num_style."'>".$total_amt_formatted."</td></tr>";


		   }else{
		   	 
		   	$tmp_tax_detail_rows[$cur_cid] =  $tmp_tax_detail_rows[$cur_cid]."\n<tr class=".$css_name."><td style='font-size: ".$font_size.";'>".
				   	$tmp_date_formated."</td>".$tmp_id_column.
				   	"<td style='".$description_style."'>".$tmp_description."</td>".$tmp_cell_tax_held.
				   	"<td style='".$plain_num_style."'>".$tax_deductable_amt_formated."</td>".$tmp_cell_non_deductible."<td style='".$plain_num_style."'>".$total_amt_formatted."</td></tr>";

		   }

		   // <td style='".$sub_total_num_style."'>"
		    
		   $prev_contrib_type = $cur_contrib_type;

		   $prev_cid = $cur_cid;
				}

				$dao->free( );

				if( $row_num > 0){

					// Handle subtotals for last row.
					$fmt_contrib_type_total_charged =  $currency_symbol.number_format($contrib_type_total_charged, 2);

		   $tmp_sub_cid = $cur_cid;
		   if($cur_cid != $prev_cid){
		   	$tmp_sub_cid = $prev_cid ;
		   }
		    
		   $fmt_subtotal_deductable  =  $currency_symbol.number_format( $subtotal_deductable , 2);
		   $fmt_subtotal_nondeductable  =  $currency_symbol.number_format( $subtotal_nondeductable , 2);
		   $fmt_subtotal_tranx_amount  =  $currency_symbol.number_format( $subtotal_tranx_amount , 2);
		    
		   $tmp_sub_cid = $cur_cid;
		   if($cur_cid != $prev_cid){
		   	$tmp_sub_cid = $prev_cid ;
		   }
		    
		   if($only_tax_deductable_parm){
		   	$tmp_total_cell_non_deductible = '';
		   } else{

		   	$tmp_total_cell_non_deductible = "<td style='".$sub_total_num_style."'>".$fmt_subtotal_nondeductable."</td>";
		   }
		    
		   if($show_tax_held_column){
		   	// print "<br>test ";
		   	$tmp_total_cell_tax_held = "<td style='".$sub_total_num_style."'>".$fmt_subtotal_tax_held."</td>";
		   	$tmp_colspan = "3";
		   	 
		   }else{
		   	$tmp_total_cell_tax_held = '';
		   	$tmp_colspan = "2";
		   	 
		   }
		    
		   if($only_show_totals){
		   	$tmp_tax_detail_rows[$cur_cid] = $tmp_tax_detail_rows[$cur_cid]."\n<tr ><td colspan=$tmp_colspan style='font-size: ".$font_size.";text-align: right; font-weight: bold;'>".$prev_contrib_type." Sub Total:</td>".$tmp_total_cell_tax_held.
		   	"<td style='".$sub_total_num_style."'>".$fmt_subtotal_tranx_amount."</td></tr>";
		   	 
		   }else{
		    $tmp_tax_detail_rows[$cur_cid] = $tmp_tax_detail_rows[$cur_cid]."\n<tr ><td colspan=$tmp_colspan style='font-size: ".$font_size.";text-align: right; font-weight: bold;'>".$prev_contrib_type." Sub Total:</td>".$tmp_total_cell_tax_held."<td style='".$sub_total_num_style."'>".$fmt_subtotal_deductable."</td>".$tmp_total_cell_non_deductible."<td style='".$sub_total_num_style."'>".$fmt_subtotal_tranx_amount."</td></tr>";
		   }
		    
		    


		   // handle totals for last contact.
		   //  $tmp_year_sub_total_tax_deductable_amt[$prev_cid]  =  $subtotal_deductable;
		   //  $tmp_year_sub_total_tax_non_deductable_amt[$prev_cid] = $subtotal_nondeductable ;
		   //  $tmp_year_sub_total[$prev_cid] =  $subtotal_tranx_amount;
				}

				//print "<br><br>html tmp array: <br>";
				//print_r( $tmp_tax_detail_rows) ;

				if($need_subtotals){
					// print "<br>Done with dao loop, do subtotals.";
					// Do subtotals for contrib types, if needed.
					$fmt_contrib_type_total_charged =  $currency_symbol.number_format($contrib_type_total_charged, 2);
					$fmt_contrib_type_total_received =  $currency_symbol.number_format($contrib_type_total_received, 2);
					$fmt_contrib_type_total_adjusted =  $currency_symbol.number_format($contrib_type_total_adjusted, 2);
					$fmt_contrib_type_total_balance =  $currency_symbol.number_format($contrib_type_total_balance, 2);
					$fmt_contrib_type_total_due =  $currency_symbol.number_format($contrib_type_total_due, 2);

					if(strlen($end_date_parm) > 0 ){
						$tmp_due_cell = "<td style='".$sub_total_num_style."'>". $fmt_contrib_type_total_due."</td>";
						 
		   }else{
		   	 
		   	$tmp_due_cell = '';
		   }
		   /*
	     $tmp_obligation_detail_rows[$cur_cid] =  $tmp_obligation_detail_rows[$cur_cid]."\n<tr><td colspan=2 style='font-size: ".$font_size.";text-align: right; font-weight: bold;'><b>".$prev_contrib_type." Total:</b>&nbsp; </td><td style='".$sub_total_num_style."'>".$fmt_contrib_type_total_charged."</td><td style='".$sub_total_num_style."'>".$fmt_contrib_type_total_received."</td> <td style='".$sub_total_num_style."'>".$fmt_contrib_type_total_adjusted."</td> <td style='".$sub_total_num_style."'>".$fmt_contrib_type_total_balance."</td>".$tmp_due_cell."</tr><tr><td colspan=6>&nbsp;</td></tr>";
	     */
				}

				// Prepare HTML for all contacts.

				// Create html and subtotals for each contact, inlcuding every contact they are authorized to.
				$year_sub_total = 0;
				$year_sub_total_tax_deductable_amt = 0 ;
				$year_sub_total_tax_non_deductable_amt = 0;
				foreach ( $contactIDs as $cid ) {

					$year_sub_total = 0;
					$year_sub_total_tax_deductable_amt = 0 ;
					$year_sub_total_tax_non_deductable_amt = 0;

					$tmp_html = "";
					//$tmp_sub_amount = 0;
					//$tmp_sub_received = 0;
					//$tmp_sub_adjustments = 0 ;
					//$tmp_sub_balance =0 ;
					 
					require_once('RelationshipTools.php');
					$tmpRelTools = new RelationshipTools();

					$rel_ids = $tmpRelTools->get_all_permissioned_ids($cid);

					//$display_contact_info = false;
					$display_contact_info = true;

					$num_contacts_with_data =0;
					// print "<Br><br>rel ids : ";
					//print_r($rel_ids );
					foreach($rel_ids as $rel_cid){
	     if( strlen( $tmp_tax_detail_rows[$rel_cid] ) > 0 ){
	     	$num_contacts_with_data = $num_contacts_with_data + 1;
	     }
					}
					 
					// print "<br><br>Num contacts with data: ".$num_contacts_with_data;
					if( $num_contacts_with_data > 0){
						$tmp_html = $tmp_html.$html_table_begin.$html_table_headers;

					}

					/*
					 if(  $num_contacts_with_data > 1 ){
					 $display_contact_info = true;

					 }

					 */

					foreach($rel_ids as $rel_cid){
						// if($display_contact_info ){
						if( strlen( $tmp_tax_detail_rows[$rel_cid] ) > 0 ){
	    		require_once 'api/api.php';
	    		$tmp_contact = civicrm_api('Contact','GET',array('contact_id' => $rel_cid,  'version' =>3));
	    			
	    		if($format_parm == "backoffice_screen"){
	    			$tmp_html = $tmp_html.'<tr><td colspan=6 style="font-size: '.$font_size.';"><strong>Contact: <a href="/civicrm/contact/view?reset=1&cid='.$rel_cid.'" target="_details">'.$tmp_contact['values'][$rel_cid]['display_name'].'</a></td></tr>';
	    		}else{
	    			$tmp_html = $tmp_html.'<tr><td colspan=6 style="font-size: '.$font_size.';"><strong>Contact: '.$tmp_contact['values'][$rel_cid]['display_name'].'</td></tr>';
	    		}
						}
						// }
						$tmp_html = $tmp_html.$tmp_tax_detail_rows[$rel_cid];
						 
						 
						$year_sub_total = $year_sub_total + $tmp_year_sub_total[$rel_cid];
						$year_sub_total_tax_deductable_amt = $year_sub_total_tax_deductable_amt + $tmp_year_sub_total_tax_deductable_amt[$rel_cid] ;
						$year_sub_total_tax_non_deductable_amt = $year_sub_total_tax_non_deductable_amt + $tmp_year_sub_total_tax_non_deductable_amt[$rel_cid] ;
						$year_subtotal_tax_held = $year_subtotal_tax_held +  $tmp_year_sub_total_tax_held[$rel_cid] ;

						 
					}
					 
					//  Need to add in special section numbers
					$year_sub_total_tax_deductable_amt = $year_sub_total_tax_deductable_amt +  $special_section_ded_subtotal ;
					$year_sub_total_tax_non_deductable_amt = $year_sub_total_tax_non_deductable_amt + $special_section_non_subtotal ;
					// done with special section numbers
					$year_sub_total_formatted = "";
					$year_sub_total_tax_deductable_amt_formatted = "";
					$tmp_total_cell_non_deductible = "";


					$year_sub_total_formatted =  $currency_symbol.number_format($year_sub_total, 2 );
					$year_sub_total_tax_deductable_amt_formatted =  $currency_symbol.number_format($year_sub_total_tax_deductable_amt, 2);
					$year_sub_total_tax_non_deductable_amt_formatted =  $currency_symbol.number_format( $year_sub_total_tax_non_deductable_amt , 2);
					$year_subtotal_tax_held_formatted = $currency_symbol.number_format( $year_subtotal_tax_held , 2 );
					//	 print "<br><br>later Num contacts with data: ".$num_contacts_with_data;
					if( $num_contacts_with_data > 0){

						// print "<br>Inside if";
						if($only_tax_deductable_parm){
							$tmp_total_cell_non_deductible = '';
						} else{

							$tmp_total_cell_non_deductible = '<td style="'.$sub_total_num_style.'">'.$year_sub_total_tax_non_deductable_amt_formatted.'</td>';
						}
						 
						 
						if($show_tax_held_column){
							 
							$tmp_total_cell_tax_held = "<td style='".$sub_total_num_style."'>".$year_subtotal_tax_held_formatted."</td>";
							$tmp_colspan = "3";
							 
						}else{
							$tmp_total_cell_tax_held = '';
							$tmp_colspan = "2";
							 
						}
						 

						if($only_show_totals){
							$tmp_html = $tmp_html.'<tr><td colspan=5> &nbsp; </td></tr><tr><td colspan='.$tmp_colspan.' style="font-size: '.
									$font_size.';text-align: right; font-weight: bold;">'.$formatted_date_range.' Totals:</td>'.$tmp_total_cell_tax_held.
									'<td style="'.$sub_total_num_style.'">'.$year_sub_total_formatted."</td></tr>";
									 
						}else{
							$tmp_html = $tmp_html.$special_section_html;
							 

							$tmp_html = $tmp_html.'<tr><td colspan=5> &nbsp; </td></tr><tr><td colspan='.$tmp_colspan.' style="font-size: '.
									$font_size.';text-align: right; font-weight: bold;">'.$formatted_date_range.' Totals:</td>'.$tmp_total_cell_tax_held.'<td style="'.$sub_total_num_style.'">'.$year_sub_total_tax_deductable_amt_formatted.'</td>'.$tmp_total_cell_non_deductible.'<td style="'.$sub_total_num_style.'">'.$year_sub_total_formatted."</td></tr>";
						}

						$tmp_html = $tmp_html.$html_table_end;
					}else{
						// print "<br>Inside else";
						$tmp_html = "<br><br>No records found ";
						 
					}


					$values[$cid][$token_long] = $tmp_html;
					 
				}


				$format = '';
				$token_short = $token_long;
				populate_default_value(  $values, $contactIDs , $token_short, $token_long,   "Nothing Found for this contact", $format);

	}  // end of function.


}  // end of class.


?>