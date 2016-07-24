<?php


class FinancialDates{
	
	
	function get_current_fiscal_year_start_date($outputFormat = "" ){
	
		/*
		
		 */
	
		$config = CRM_Core_Config::singleton( );
		$tmp_fiscal_config=  $config->fiscalYearStart;
	
		$fyDate = $tmp_fiscal_config[d];
		$fyMonth = $tmp_fiscal_config[M];
	
		$fyYear  = CRM_Utils_Date::calculateFiscalYear( $fyDate, $fyMonth  );
		$tmp = $fyYear."-".$fyMonth."-".$fyDate;
		if($outputFormat == "sql"){
			// print "<br><br>Inside get fiscal start date as sql format ";
			$tmp_ts = strtotime($tmp);
			$tmp_d = date ( 'Ymd', $tmp_ts) ;
			$tmp = $tmp_d."000000";
		}else{
			$tmp = $fyYear."-".$fyMonth."-".$fyDate;
		}
		// print "<Br><br> ".$tmp;
	
		return $tmp;
	
	
	}
	
	
	
	
}