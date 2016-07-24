<?php




function getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, &$outCustomTableName, &$outCustomColumnNames ){

$error_msg = '';

 
// figure out the table name and column names for custom fields. 
$tablename_query = "SELECT civicrm_custom_group.table_name as tablename from civicrm_custom_group
 where title = '$custom_field_group_label' ";

$outCustomTableName= '';

$table_dao =& CRM_Core_DAO::executeQuery( $tablename_query );
 if ( $table_dao->fetch( ) ) {
       $outCustomTableName = $table_dao->tablename;        
 }else{
        
	$error_msg =  "Cannot find table for custom field group '$custom_field_group_label'";	
	// print 'Inside getCustomTableFieldNames: '.$error_msg;
	
	return $error_msg;
} 
 $table_dao->free( );
       

if( $outCustomTableName == ''){

	$error_msg =  "outCustomTableName variable is empty";
	
	return $error_msg;
}


// Assert: We have the custom table name at this point.
// echo "custom table name: ".$outCustomTableName."<br>";

$i = 1;
$labels_list = ""; 
        foreach ( $customFieldLabels as $clabel ) {
          $labels_list = $labels_list.'\''.$clabel.'\' ';
          if( $i < count($customFieldLabels) ){
              $labels_list = $labels_list.' ,';
          }
          $i = $i +1;
        }
        
        
$custom_fields_query = " SELECT civicrm_custom_field.column_name as column_name, civicrm_custom_field.label as label 
FROM  civicrm_custom_group left join civicrm_custom_field
 on civicrm_custom_group.id = civicrm_custom_field.custom_group_id
 where civicrm_custom_group.title = '$custom_field_group_label' 
 and ( civicrm_custom_field.label in ( $labels_list) ) ";


$fieldnames_dao =& CRM_Core_DAO::executeQuery( $custom_fields_query );

while ( $fieldnames_dao->fetch( ) ) {
	
       $tmp_label = $fieldnames_dao->label;    
       foreach ( $customFieldLabels as $clabel ) {
          if( $tmp_label == $clabel ) 
              $outCustomColumnNames[$clabel] = $fieldnames_dao->column_name;
          
        }
        
 }
 $fieldnames_dao->free( );

// print_r( $outCustomColumnNames); 

 foreach ( $customFieldLabels as $clabel ) {
          if(   $outCustomColumnNames[$clabel] == "" ){
            return "Error: Could NOT find custom field $clabel";  
            }     
        }

//***   end of section to get table and column names  ***/

return "";

}


?>