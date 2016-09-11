<div id='help'><b>Current financial summary for this contact as well as other contacts with whom they have a permissioned relationship</b><br>Your fiscal year started {$fiscal_year_start_date_formatted} which impacts the information shown </div>
 
 <table border=0 cellspacing=0 cellpadding=0 style="border: 0;"><tr><td>
 


<a href='/civicrm/contact/view/contribution?reset=1&action=add&context=contribution&cid={$cid}' class='button'><span><i class="crm-i fa-plus-circle"> </i>New Contribution</span></a>
 <a href='/civicrm/contact/view/pledge?reset=1&action=add&context=pledge&cid={$cid}' class='button'><span><i class="crm-i fa-plus-circle"> </i>New Pledge</span></a>{if $can_post_back_office_credit_card eq true }&nbsp;&nbsp;
 <a href='/civicrm/contact/view/contribution?reset=1&action=add&cid={$cid}&context=contribution&mode=live' class='button'><span><i class="crm-i fa-plus-circle"> </i>New Automated Credit Card Contribution(s)</span></a>
  {/if} 
  </td></tr>
  
  {if $count_unpaid_pledges <> "0" }
    {if strlen( $paybal_page_id ) > 0 }
<tr><td><a class='button' href='/civicrm/contribute/transact?reset=1&id={$paybal_page_id}&cid={$cid}' target='_new'>Pay Existing Balance(s) Using Installments or In Full</a>
</td></tr>
  {/if}
{/if}
  </table>
  {if $count_ach_pages > 0 }
  <select onchange='if (this.value) window.location.href=this.value'>
  <option value=''>-- select page to use to create a new ACH/Direct Debit schedule --</option> 

  {foreach from=$ach_pages key=cur_id item=cur_title $ach_pages}
    {if $cur_title <> "Pay Balance"} 
    
  	<br><option value='/civicrm/contribute/transact?reset=1&cid={$cid}&id={$cur_id}'>via '{$cur_title}'</option>
    {/if}
    }

  {/foreach} 
   </select><br>
   {/if}


{literal}
  <script language='javascript'>

   	 function financial_summary_toggle_obligation(choice) {
               
   	        if(choice.value.length == 0) {
   	            return; 
   	        }  		
     		
     		 hide_all_sections();
     		 
     		if(document.getElementById(choice.value) ) {
     		      
     	               document.getElementById(choice.value).style.display = 'block';
     		}  		
     	}
     	 	
     	 	
     	function financial_summary_toggle_tax_deductables(choice) {
     	        
     	        
     		if(choice.value.length == 0) {
   	            return; 
   	        }
   	        
     		
     		 hide_all_sections();
     		
     		var choice_name =  choice.value;
     		 
     		if(document.getElementById(choice_name) ) {
     	               document.getElementById(choice_name).style.display = 'block';
     		}
     	}
     	
     	
     	function hide_all_sections() {
     		var select_list = document.getElementById('select_view');
     		var list_length = select_list.length ;
     		
     		for(var i=0;i<list_length;i++) {
     		      tmp_cur = select_list.options[i].value;
     		     
     		     if( i > 0) {
     		       tmp_cur_elem = document.getElementById(tmp_cur);
     		       
     		       tmp_cur_elem.style.display = 'none';
     		     }
     		} 
     	}
     		
     	</script>
     
     {/literal}
     
     
     
     
     <span id='view_area' style='width: 100%; text-align: left;'
     <br><br>
<div class="crm-accordion-wrapper">
<div class="crm-accordion-header">
Choose Information to Display
</div>
 <div class="crm-accordion-body">
 <div class="no-border form-layout-compressed">
    <form><select id='select_view' onChange="financial_summary_toggle_obligation(this.options[this.selectedIndex]);">
     <option value=''>-- select --</option>
     
   
     <option value='show_to_end_fiscal_year'>Obligations (show column 'due by end of fiscal year')</option>
     <option value='show_to_today'>Obligations (show column 'due by today')</option>
     <option value='show_to_today_exclude_closed_balances'>Obligations (show column 'due by today', exclude closed balances)</option>
     <option value='show_to_today_exclude_after_fiscalyear_closed_balances'>Obligations (show column 'due by today', exclude closed balances, future fiscal years)</option>
        
   
     <option value='{$token_tax_prevyear}'>Tax Information for Previous Calendar Year ({$prev_year})</option>
     <option value='{$token_tax_curyear}'>Tax Information for Current Calendar Year ({$cur_year})</option>
     </select>
     &nbsp; &nbsp;
     
     <input type=hidden id='select_tax_start_date' value=''> 
     
     </form></span>
     </div></div></div>
    
    
    
     {**********************************************************************
     ** Do section for 'show_to_end_fiscal_year'                         *
     **********************************************************************}
      <div id='show_to_end_fiscal_year' style='display: none;'>
        
      
        <strong>Open Balance: </strong>
 	 {$values[$cid][$token_balance_long]}
    
 	
 	<br><br><strong>Amount Due by {$end_date_formatted}: </strong>
 	{$values[$cid][$token_amount_due_end_fiscal_long]}
     {* print table *}
     <br><h3>Obligations Until End of Fiscal Year</h3> 
      {$values[$cid][$token_oblig_to_end_date_subtotals_long]}
       <br> 
 
 <h3>Received Details</h3>
  {$values[$cid][$token_completed_payments_long]}

  <h3>Adjustment Details</h3>
 {$values[$cid][$token_adjustments_payments_long]}

  <h3>Prepayments Received Details</h3>
  {$values[$cid][$token_prepayments_long]}
 
  <h3>Received as the Beneficiary of a Third Party</h3>
 	{$values[$cid][$token_payments_beneficiary]} 
     
     </div>


     {*******************************************************************
     ** Do section for 'show_to_today'                         **
     **********************************************************************}
     <div id='show_to_today' style='display: block;'>
        {* print balances *}
        <strong>Open Balance: </strong>
 	{$values[$cid][$token_balance_long]}
    
    
 	<br><br><strong>Amount Currently Due: </strong>
 	
 	{if $money_due}
    <span>
    {else}
      <span>
    {/if}
 	{$values[$cid][$token_amount_due_long]}
 	
 	</span>
 	
     {* print table. *}
      <br><h3>Obligations Until Today</h3>
      {$values[$cid][$token_oblig_to_today_subtotals_long]}
      
     <br> 
    <h3>Received Details</h3>
    {$values[$cid][$token_completed_payments_long]}

     <h3>Adjustment Details</h3>
  {$values[$cid][$token_adjustments_payments_long]}

   <h3>Prepayments Received Details</h3>
    {$values[$cid][$token_prepayments_long]}
 
 	<h3>Received as the Beneficiary of a Third Party</h3>
 	{$values[$cid][$token_payments_beneficiary]}
     
     </div>
     
     {********************************************************************
     ** Do section for 'show_to_today' , do not show closed balances.             **
     **********************************************************************}
      <div id='show_to_today_exclude_closed_balances' style='display: none;'>
        {* print balances  *}
        <strong>Open Balance: </strong>
 	{$values[$cid][$token_balance_long]}
    
 	<br><br><strong>Amount Currently Due: </strong>
 	{$values[$cid][$token_amount_due_long]}
 	
 	
     {* print table. *}
      <br><h3>Obligations Until Today Excluding Closed Balances</h3>
     {$values[$cid][$token_oblig_to_today_exclude_closed_bals]}
       <br>
 
     <h3>Received Details</h3>
     {$values[$cid][$token_completed_payments_long]}

   <h3>Adjustment Details</h3>
 {$values[$cid][$token_adjustments_payments_long]}

   <h3>Prepayments Received Details</h3>
   {$values[$cid][$token_prepayments_long]}
 
 	<h3>Received as the Beneficiary of a Third Party</h3>
 	{$values[$cid][$token_payments_beneficiary]}
     
     </div>
     
     
     
     {*********************************************************
      Do section for 'show_to_today' , do not show closed balances or obligations after this fiscal year.             
     *********************************************************************}
      <div id='show_to_today_exclude_after_fiscalyear_closed_balances' style='display: none;'>
        {* print balances  *}
        <strong>Open Balance: </strong>
 	{$values[$cid][$token_balance_long]}
    
 	<br><br><strong>Amount Currently Due: </strong>
 	{$values[$cid][$token_amount_due_long]}
 	
 	
     {* print table. *}
      <br><h3>Obligations Until Today Excluding Closed Balances and Excluding Future Fiscal Years</h3>
      {$values[$cid][$token_oblig_to_today_exclude_after_enddate_closed_bals]}
       <br>
        
 
     <h3>Received Details</h3>
 {$values[$cid][$token_completed_payments_long]}

  <h3>Adjustment Details</h3>
 {$values[$cid][$token_adjustments_payments_long]}

   <h3>Prepayments Received Details</h3>
  {$values[$cid][$token_prepayments_long]}
 
 	<h3>Received as the Beneficiary of a Third Party</h3>
 	 {$values[$cid][$token_payments_beneficiary]}
     
     </div>
     
     {**********************************************************************
     ** Do section for 'prev_tax_year'  , show everything                **
     **********************************************************************}
      <div id='{$token_tax_prevyear}' style='display: none;'>
        
        <h3>Tax Information for Previous Year ({$prev_year})</h3>

     {$values[$cid][$token_tax_prevyear]}
     
    </div>
     {*********************************************************************
     ** Do section for 'prev_tax_year' , only show deductible items      **
     **********************************************************************}
     <div id='show_tax_prev_year_show_only_deductables' style='display: none;'>
        
        <h3>Tax Information for Previous Calendar Year, starting January 1, only includes deductibles</h3>
      {$values[$cid][$token_tax_prev_year_only_deductables_long]}  
     
       </div>
      {**********************************************************************
       ** Do section for 'cur_tax_year'                         **
       **********************************************************************}
      <div id='{$token_tax_curyear}' style='display: none;'>
        
        <h3>Tax Information for Current Calendar Year ({$cur_year})</h3>
  
      {$values[$cid][$token_tax_curyear]} 
     
      </div>

     