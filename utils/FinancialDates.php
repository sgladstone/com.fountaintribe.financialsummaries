<?php


class FinancialDates{
	
	function get_last_day_cur_fiscal_year($format = 'Y-m-d'){
		$config = CRM_Core_Config::singleton( );
	
		//print "<br>Config: ";
		//print_r($config);
		$tmp_fiscal_config=  $config->fiscalYearStart;
	
		$fyDate = $tmp_fiscal_config['d'];
		$fyMonth = $tmp_fiscal_config['M'];
	
		$fyYear  = CRM_Utils_Date::calculateFiscalYear( $fyDate, $fyMonth  );
	
		$tmpFirstDay_ts = mktime(1,1,1,$fyMonth,$fyDate,$fyYear+1) ;
	
		$tmpFirstDay =  date("Y-m-d", $tmpFirstDay_ts);
		$tmpFirstDay_date = date("Y-m-d", strtotime($tmpFirstDay));
	
		$tmpNextYearFirstDay_ts = strtotime( $tmpFirstDay_date . "+1 year");
		$tmpNextYearFirstDay_date = date("Y-m-d", strtotime($tmpFirstDay));
	
		// echo "After adding one Year: ".date('l dS \o\f F Y',  $tmpNextYearFirstDay_ts)."<br>";
		$tmp_last_day_ts = strtotime(  $tmpNextYearFirstDay_date  . "-1 day");
	
		// echo "After subtracting one day: ".date('l dS \o\f F Y',  $tmp_last_day_ts )."<br>";
	
		// $tmp = date($format,   $tmpNextYearFirstDay_ts  );
		$tmp = date($format,   $tmp_last_day_ts  );
		return $tmp;
	}
	
	function  get_first_day_next_fiscal_year($format = 'Y-m-d'){
	
		$config = CRM_Core_Config::singleton( );
	
		//print "<br>Config: ";
		//print_r($config);
		$tmp_fiscal_config=  $config->fiscalYearStart;
	
		$fyDate = $tmp_fiscal_config[d];
		$fyMonth = $tmp_fiscal_config[M];
	
		$fyYear  = CRM_Utils_Date::calculateFiscalYear( $fyDate, $fyMonth  );
	
		$tmpFirstDay_ts = mktime(1,1,1,$fyMonth,$fyDate,$fyYear) ;
	
		$tmpFirstDay =  date("Y-m-d", $tmpFirstDay_ts);
		$tmpFirstDay_date = date("Y-m-d", strtotime($tmpFirstDay));
	
		$tmpNextYearFirstDay_ts = strtotime( $tmpFirstDay_date . "+1 year");
		$tmpNextYearFirstDay_date = date("Y-m-d", strtotime($tmpFirstDay));
	
		// echo "After adding one Year: ".date('l dS \o\f F Y',  $tmpNextYearFirstDay_ts)."<br>";
		// $tmp_last_day_ts = strtotime(  $tmpNextYearFirstDay_date  . "-1 day");
	
		//echo "After subtracting one day: ".date('l dS \o\f F Y',  $tmp_last_day_ts )."<br>";
	
		$tmp = date($format,   $tmpNextYearFirstDay_ts  );
	
		return $tmp;
	
	}
	
	
	
	function get_current_fiscal_year_start_date($outputFormat = "" ){
	
		/*
		
		 */
	
		$config = CRM_Core_Config::singleton( );
		$tmp_fiscal_config=  $config->fiscalYearStart;
	
		$fyDate = $tmp_fiscal_config['d'];
		$fyMonth = $tmp_fiscal_config['M'];
	
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