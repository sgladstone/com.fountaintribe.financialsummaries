<?php

class FormattingUtils{


	function getPDFfontsize(){
		return "12px";

	}



	function determineDateRange( $date_range , &$start_date , &$end_date ){
		// output date format is yyyymmdd
	 	
		
		require_once 'utils/FinancialDates.php';
		$finDates = new FinancialDates();
		
		
		if( $date_range == 'curyear'  ){
			$tmp_year = date('Y');
			$start_date = $tmp_year."0101";
			$end_date = $tmp_year."1231";

		}else if( $date_range == 'prevyear') {
			$tmp_year = date('Y') - 1;
			$start_date = $tmp_year."0101";
			$end_date = $tmp_year."1231";
			 
		}else if( $date_range == 'curfiscalyear' ){
			 
			$start_date	= $finDates->get_current_fiscal_year_start_date() ;
			$end_date =  $finDates->get_last_day_cur_fiscal_year();
			 
		}else{
			$date_range_tmp_array   = explode("_", $date_range);
			$start_date = $date_range_tmp_array[0];
			
			if( isset( $date_range_tmp_array[1])){
				$end_date = $date_range_tmp_array[1];
			}else{
				$end_date = "";
			}
			 
		}
		 
	}

	function getSqlDate($date_raw){
		 
		$sql_date = "";
		if($date_raw == 'today'){
			$sql_date =  date("Ymd");
		}else{

			$sql_date = $date_raw;
		}
		 
		return $sql_date;
		 
		 
	}




	function get_date_formatted_short_form(&$parm, &$input_format){



		$config = CRM_Core_Config::singleton( );

		$tmp_system_date_format = 	$config->dateInputFormat;
		 
		// print "<br>system date format: ".$tmp_system_date_format;
		// print "<br>date parm: ".$parm;
		$year ;
		$month;
		$day;
		if($input_format == 'yyyy-mm-dd'){
			list($year, $month, $day) = split('[/.-]', $parm);
			 
		}else if($input_format == 'yyyymmdd'){
			$year = substr($parm, 0, 4);
			$month = substr($parm, 4, 2);
			$day = substr($parm, 6, 2);
			 
		}
		 
		 
		if($tmp_system_date_format == 'dd/mm/yy'){
			$output_format = 'j/n/Y';

		}else if($tmp_system_date_format == 'mm/dd/yy'){
			$output_format = 'n/j/Y';

		}else{
			//print "<br>Configuration Issue: Unrecognized System date format: ".$tmp_system_date_format;
			$output_format = 'j/n/Y';

		}
		 
		$parm_as_timestamp = mktime(0, 0, 0, $month, $day, $year);
		$output_date = date( $output_format, $parm_as_timestamp);
		 
		//print "<br>Output formatted date: ".$output_date ;

		return $output_date;
	}


	function get_date_formatted_long_form(&$parm, &$input_format){



		$config = CRM_Core_Config::singleton( );

		$tmp_system_date_format = 	$config->dateInputFormat;
		 
		// print "<br>system date format: ".$tmp_system_date_format;
		// print "<br>date parm: ".$parm;
		$year ;
		$month;
		$day;
		if($input_format == 'yyyy-mm-dd'){
			list($year, $month, $day) = split('[/.-]', $parm);
			 
		}else if($input_format == 'yyyymmdd'){
			$year = substr($parm, 0, 4);
			$month = substr($parm, 4, 2);
			$day = substr($parm, 6, 2);
			 
		}
		 
		 
		if($tmp_system_date_format == 'dd/mm/yy'){
			$output_format = 'j F Y';

		}else if($tmp_system_date_format == 'mm/dd/yy'){
			$output_format = 'F j, Y';

		}else{
			//print "<br>Configuration Issue: Unrecognized System date format: ".$tmp_system_date_format;
			$output_format = 'j F Y';

		}
		 
		$parm_as_timestamp = mktime(0, 0, 0, $month, $day, $year);
		$output_date = date( $output_format, $parm_as_timestamp);
		 
		//print "<br>Output formatted date: ".$output_date ;

		return $output_date;
	}






	function startsWith($haystack, $needle, $case = true) {
		if ($case) {
			return (strcmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
		}
		return (strcasecmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
	}


}

?>