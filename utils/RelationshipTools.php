<?php

class RelationshipTools{



function get_contact_ids_for_sql($contactIDs){



	$comma = ",";
	 $i = 1;
	
        foreach ( $contactIDs as $cid ) {
          //$cid_list = $cid_list.$cid;
          $rel_ids = self::get_all_permissioned_ids($cid);
          $cid_list = ""; 
           //print "<br> rel ids: ";
           //print_r($rel_ids); 
	   $k = 1;        
          foreach($rel_ids as $rel_cid){
             
             $cid_list = $cid_list.$rel_cid ;
             if( $k < count($rel_ids) || $i < count($contactIDs )   ){
                 $cid_list = $cid_list.' ,';
             }
              $k = $k + 1;
              
          }
          
          $i = $i + 1;
         
        }
        
        //if(substr_compare($cis_list, $comma, -strlen($comma), strlen($comma)) === 0;
        
        
        return $cid_list; 


} 


/********************************************************************************/
/* Figure out everyone this contact is permissioned to.    */
/********************************************************************************/

function get_all_permissioned_ids($contact_id){

$contact_ids = array(); 
// Someone is always permissioned to themselves. 
$contact_ids[] = $contact_id;

/*
$sql_str = "SELECT r1.contact_id_a AS cid_a, r1.contact_id_b AS cid_b,  r1.is_permission_a_b, r1.is_permission_b_a, name_a_b
FROM civicrm_relationship AS r1, 
civicrm_relationship_type AS reltype
WHERE 
 r1.relationship_type_id = reltype.id and 
(
reltype.label_a_b IN (
'Household Member of',  'Head of Household for', 'Spouse of',  'Parent of',  'Employee of', 'Child of', 'Grandparent of', 'Grandchild of', 'widow/widower of' )
)
AND r1.is_active =1
AND ( (r1.contact_id_a = ".$contact_id." and r1.is_permission_a_b =1) OR (r1.contact_id_b =".$contact_id." and r1.is_permission_b_a)
)
ORDER BY r1.contact_id_b, reltype.name_a_b";
*/

$sql_str = "SELECT r1.contact_id_a AS cid_a, r1.contact_id_b AS cid_b,  r1.is_permission_a_b, r1.is_permission_b_a, name_a_b
	FROM civicrm_relationship AS r1, 
	civicrm_relationship_type AS reltype
	WHERE  r1.relationship_type_id = reltype.id 
	AND r1.is_active =1
	AND ( (r1.contact_id_a = ".$contact_id." and r1.is_permission_a_b =1) OR (r1.contact_id_b =".$contact_id." and r1.is_permission_b_a)
	)
	ORDER BY r1.contact_id_b, reltype.name_a_b";


//print "<br><br>sql: ".$sql_str; 
$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;
 
  while ( $dao->fetch( ) ) {
       $cid_a = $dao->cid_a; 
       $cid_b = $dao->cid_b;
       
       if( $cid_a != $contact_id && ( in_array( $cid_a, $contact_ids ) == false) ){
    		$contact_ids[] = $dao->cid_a;
    	}
    	
    	
       if( $cid_b != $contact_id && ( in_array( $cid_b, $contact_ids ) == false)){
    		$contact_ids[] = $dao->cid_b;
    	}
  
  
  }
  
  $dao->free(); 
 
 
 return $contact_ids;

}





	



}




?>