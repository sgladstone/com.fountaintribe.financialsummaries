<?php

class CustomSearchTools{

	function __construct () {
	
	}
	
	static function fillMembershipTypeArrays(&$mem_ids,  &$org_ids){
		
		$cur_domain_id = "";
		 
		$result = civicrm_api3('Domain', 'get', array(
				'sequential' => 1,
				'current_domain' => array('IS NOT NULL' => 1),
		));
		
		
		if( $result['is_error'] == 0 && $result['count'] == 1){
			if(isset( $result['id'] )){
				$cur_domain_id = $result['id'];
			}
		}
		 
		// get membership ids and org contact ids.
		if( strlen(  $cur_domain_id ) > 0 ){
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
		}
		
	}
	
	
	
	function getCommunicationPreferencesForSelectList( ){
		 $comm_prefs =  array('' => '-- select --' );   
         $sql = " SELECT ov.label, ov.value
			FROM civicrm_option_value ov
			JOIN `civicrm_option_group` og ON og.id = ov.option_group_id
			WHERE og.name = 'preferred_communication_method' 
			AND ov.is_active = 1  "; 
	
	$dao = & CRM_Core_DAO::executeQuery( $sql, CRM_Core_DAO::$_nullArray );
                                             
         while ($dao->fetch()){	
         
         	$pref_label = $dao->label;
         	$pref_id = $dao->value; 
         	$comm_prefs[$pref_id] = $pref_label;
         	
         }	   
         
         $dao->free();  
	
	return $comm_prefs; 
	
	
	}
	
	function updateWhereClauseForGroupsChosen(&$groups_of_contact,  &$contact_field_name, &$clauses ){
		if(count( $groups_of_contact ) > 0 ){
   // f1.contact_id
  		//print "<br><br><h2>Need do deal with where clause for groups filters.</h2>"; 
	  		$tmp_sql_list = self::getSQLStringFromArray($groups_of_contact);
	  		
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
	
	
	function updateWhereClauseForMemberships( &$mem_types_of_contact,  &$mem_orgs_of_contact, &$contact_field_name,  &$clauses   ){
		// deal with membership filters. 
	//	 $tmp_membership_sql_list = self::convertArrayToSqlString( $mem_types_of_contact  ) ; 
		$tmp_membership_sql_list = implode( ",", $mem_types_of_contact ); 
		if(strlen($tmp_membership_sql_list) > 0 ){
			$clauses[] =   "(  ( ".$contact_field_name." IN ( SELECT mem.contact_id
								 FROM civicrm_membership mem 
								 LEFT JOIN civicrm_membership_status mem_status ON mem.status_id = mem_status.id 
								  WHERE mem.membership_type_id IN (".$tmp_membership_sql_list.")
								  AND mem_status.is_current_member = '1'
								  AND mem_status.is_active = '1'  )  ) ) ";
			 
		
		} 
	
		//$tmp_membership_org_sql_list = self::convertArrayToSqlString( $mem_orgs_of_contact  ) ; 
		$tmp_membership_org_sql_list = implode( ",", $mem_orgs_of_contact  );
		if(strlen($tmp_membership_org_sql_list) > 0 ){
			 $clauses[] =   "(  ( ".$contact_field_name." IN (  SELECT mem.contact_id
								 FROM civicrm_membership mem 
								 LEFT JOIN civicrm_membership_status mem_status ON mem.status_id = mem_status.id
								 LEFT JOIN civicrm_membership_type mt ON mem.membership_type_id = mt.id 
								 WHERE 
								 mt.member_of_contact_id IN (".$tmp_membership_org_sql_list.")
								 AND mt.is_active = '1'
								 AND mem_status.is_current_member = '1'
								AND mem_status.is_active = '1' ) )  )";							 	
		} 
		
	
	
	}
	
	function updateWhereClauseForCommPrefs(&$comm_prefs, &$clauses, $contact_field_name = "contact_a" ){
	

        // The field contact_a.preferred_communication_method can have one or more option in it, each 
        // surrounded by a non-printable char. Therefore the where clauses have this non-printable char too.
	if(strlen($comm_prefs) > 0 ){
	    if( $comm_prefs == 2){
		// email.   Include contacts who have an email preference, 
		// also include contacts who have no preference but have a email address. 
		$clauses[] = "(    $contact_field_name.preferred_communication_method like '%".$comm_prefs."%'  OR
		                   (  (  length($contact_field_name.preferred_communication_method) = 0  OR  $contact_field_name.preferred_communication_method IS NULL  ) AND
		                     length(civicrm_email.email)  > 0 )
		               )  "; 
		}else if( $comm_prefs == 3 ){
			// postal mail. Include contacts who have a postal mail preference, 
			// also include contacts who have no preference but do not have a email address. 
			$clauses[] = "(    $contact_field_name.preferred_communication_method like '%".$comm_prefs."%' OR
		                   (  (  length($contact_field_name.preferred_communication_method) = 0  OR  $contact_field_name.preferred_communication_method IS NULL  ) AND
		                    ( length(civicrm_email.email)  =  0 OR  civicrm_email.email IS NULL ) )
		               )  "; 
		
		}else{
			$clauses[] = " $contact_field_name.preferred_communication_method like '%".$comm_prefs."%'  "; 
		}
		
	}
	
	}	
	
	// Get an array of all organizations/contacts in the db that have membership types defined as
	// that org as the org someone may join. 
	function getMembershipOrgsforSelectList(){
	
		$org_ids = array();
		$sql = "SELECT distinct c.id, c.display_name FROM civicrm_membership_type mt
		Left join civicrm_contact c on mt.member_of_contact_id = c.id
		where is_active=1 order by c.display_name, mt.name";
		 $params = array();
	
	   	$dao = CRM_Core_DAO::executeQuery($sql, $params);
	   
	   	while( $dao->fetch()){
	   		$cur_id = $dao->id;
   		
   			$cur_org_name = $dao->display_name;
   			$org_ids[$cur_id] =  $cur_org_name;
	   	
	   	}
	   	$dao->free();
	   	
		return $org_ids; 
	
	
	}
	
	
	
	
	function getMembershipsforSelectList(){
		require_once 'api/api.php';
	
	$params = array('action' => 'get' , 'version'    => '3.0');
	$mem_ids =           array( );
	//$myGroups =& civicrm_group_get($params);
	//$tmp = civicrm_api( 'membership', 'Get', $params);
	
	
	//print_r($myGroups['values']); 
	//$myMemberships = $tmp['values'];
	
	/*
	foreach($myMemberships as $curmem) {

	//print "<br><br><br>group :";
	//print_r($group);

   		if( $curmem['saved_search_id'] == ''){ 
  	 		$mem_ids[$group['id']] =  $curmem['title'];
  		 }
	}
	*/
	$sql = "SELECT mt.id, mt.name, c.display_name FROM civicrm_membership_type mt
		Left join civicrm_contact c on mt.member_of_contact_id = c.id
		where is_active=1 order by c.display_name, mt.name";
	 $params = array();

   	$dao = CRM_Core_DAO::executeQuery($sql, $params);
   
   	while( $dao->fetch()){
   		$cur_id = $dao->id;
   		$cur_name = $dao->name; 
   		$cur_org_name = $dao->display_name;
   		$mem_ids[$cur_id] =  $cur_org_name." - ".$cur_name;
	}
	$dao->free();	
	
	//asort($mem_ids); 
	return $mem_ids;
	
	
	}

	function verifyGroupCacheTable($groupIDs_raw){
	
		
	
		 $groupIDs = self::getSQLStringFromArray($groupIDs_raw);
		 
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
		
		//print "<br>sql: ".$sql; 
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
			//print "<br>Reload cache??"; 
			      if (!$this->_smartGroupCache || $dao->cache_date == NULL) {
			  //     print "<br>Need to reload!"; 
			        CRM_Contact_BAO_GroupContactCache::load($dao);
			      }
			
			
			}
			
			$dao->free(); 

	}

	function getRegularGroupsforSelectList(){
 	//require_once 'api/v2/Group.php';
	//require_once 'api/Group.php';
	require_once 'api/api.php';
	
	$params = array('version'    => '3.0');
	$group_ids =           array( );
	//$myGroups =& civicrm_group_get($params);
	$tmp = civicrm_api( 'Group', 'Get', $params);
	
	
	//print_r($myGroups['values']); 
	$myGroups = $tmp['values'];
	
	
	foreach($myGroups as $group) {

	//print "<br><br><br>group :";
	//print_r($group);

   		if( $group['saved_search_id'] == ''){ 
  	 		$group_ids[$group['id']] =  $group['title'];
  		 }
	}
	
	asort($group_ids); 
	return $group_ids;

}


	function convertArrayToSqlString($parm){
		$tmp_sql_list = "";
		$i = 1; 
		foreach($parm as $cur){
	
			$tmp_sql_list = $tmp_sql_list.$cur; 
			//if($i < count($tmp_groups)){
			$tmp_sql_list = $tmp_sql_list.",";
			//} 
			$i = $i + 1; 
		}
	
	$cleaned_str =  rtrim($tmp_sql_list, ",");
	return $cleaned_str ;
	
	
	}	

 	function getSQLStringFromArray( $parm ){
 		// Loop though the groups array, and build the sql IN LIST clause.
 		
 	$tmp_sql_list = ""; 	
 	
 	foreach($parm as $parent_group){
 			
	 	$tmp_groups = self::getAllNestedGroups($parent_group);	
	 	
	
		$i = 1; 
			foreach($tmp_groups as $cur){
				
				$tmp_sql_list = $tmp_sql_list.$cur; 
				//if($i < count($tmp_groups)){
				$tmp_sql_list = $tmp_sql_list.",";
				//} 
				$i = $i + 1; 
		
		
			}
	
	}
	// TODO: remove trailing comma
	$cleaned_str =  rtrim($tmp_sql_list, ",");
	return $cleaned_str ;
 	
 	
 	
 	}

	
	private function getAllNestedGroups($grp_id){
   $all_nested_groups = array();
   $all_nested_groups[] = $grp_id;
 
   $sql = "Select * from civicrm_group_nesting where parent_group_id =%1";
 
   $params = array();
   $params[1] =  array( $grp_id, 'Integer' );
   $dao = CRM_Core_DAO::executeQuery($sql, $params);
   
   while( $dao->fetch()){
     $tmp_nested_id = $dao->child_group_id;
     
     $all_nested_groups[] = $tmp_nested_id;    
     // This group may have groups nested under it. 
    $tmp_grps =  self::getAllNestedGroups($tmp_nested_id);	
    $all_nested_groups =  array_merge ($all_nested_groups , $tmp_grps);
   }
   
   $dao->free();
  
  return $all_nested_groups;
   
 }



}




?>