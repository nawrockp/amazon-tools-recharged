function AmazonLib()
{this.addASIN=function()
{var list=this.parentNode.parentNode;var asins=$amazon_jq('textarea',list).val();var name=$amazon_jq('h3',list).html();var data={action:'amazon_add_list_item',list:name,asins:asins};$amazon_jq.post(ajaxurl,data,function(resp){if(resp!=''&&resp!=0&&resp!=-1){$amazon_jq('table tr:last',list).after(resp);$amazon_jq('.remove_asin_btn',list).unbind('click');$amazon_jq('.remove_asin_btn',list).click(amazon_lib.removeASIN);}
$amazon_jq('textarea',list).val('');}).error(function(){window.location.href=window.location.href;});}
this.deleteList=function()
{var list=this.parentNode.parentNode;var name=$amazon_jq('h3',list).html();var conf=confirm('Are you sure you want to delete this list?\nList: '+name);if(!conf)
return false;var data={action:'amazon_delete_list',list:name};$amazon_jq.post(ajaxurl,data,function(resp){list.parentNode.removeChild(list);});}
this.removeASIN=function()
{var parts=this.id.replace('remove-','').split('-');var name=parts[0];var asin=parts[1];var target=this;var data={action:'amazon_remove_list_item',list:name,asin:asin};$amazon_jq.post(ajaxurl,data,function(resp){target.parentNode.parentNode.parentNode.removeChild(target.parentNode.parentNode);}).error(function(){window.location.href=window.location.href;});}
this.checkListForm=function()
{var ret=true;if($amazon_jq('[name="new_list"]',this).val().replace('/\s/g','')=='')
{$amazon_jq('[name="new_list"]',this).css('border-color','#ff0000');ret=false;}
if($amazon_jq('[name="new_list_asins"]',this).val().replace('/\s/g','')=='')
{ret=false;$amazon_jq('[name="new_list_asins"]',this).css('border-color','#ff0000');}
return ret;}
this.search=function()
{var query=$amazon_jq('[name="amazon_search"]').val();var results=$amazon_jq('[name="amazon_search_count"]').val();if(query=='')
return;$amazon_jq('#amazon_search_results').html('');$amazon_jq('#amazon_search_results').addClass('amazon_search_loading');var data={action:'amazon_search',security:amazon_nonce,count:results,search:query};$amazon_jq.post(ajaxurl,data,amazon_lib.searchSuccess,'html').error(amazon_lib.searchFailed);return false;}
this.searchSuccess=function(response)
{$amazon_jq('#amazon_search_results').removeClass('amazon_search_loading');$amazon_jq('#amazon_search_results').html(response);}
this.searchFailed=function()
{$amazon_jq('#amazon_search_results').removeClass('amazon_search_loading');$amazon_jq('#amazon_search_results').html('<em>Search results could not be retrieved</em>');}
this.newWindow=function()
{$amazon_jq('.new_window').attr('target','_blank');$amazon_jq('.new_window').attr('title','open in a new window');}
this.fieldInfo=function(link_id,div_id)
{$amazon_jq(link_id).hover(function(){$amazon_jq(div_id).css('display','block');},function(){$amazon_jq(div_id).css('display','none');});}
this.expand=function()
{var asin=$amazon_jq(this).attr('id');$amazon_jq('.'+asin).removeClass('cache_sub');$amazon_jq(this).removeClass('cache_expand');$amazon_jq(this).addClass('cache_collapse');$amazon_jq(this).unbind('click');$amazon_jq(this).click(amazon_lib.collapse);}
this.collapse=function()
{var asin=$amazon_jq(this).attr('id');$amazon_jq('.'+asin).addClass('cache_sub');$amazon_jq(this).addClass('cache_expand');$amazon_jq(this).removeClass('cache_collapse');$amazon_jq(this).unbind('click');$amazon_jq(this).click(amazon_lib.expand);}
this.addField=function()
{var index=Number($amazon_jq('[name="template_field_index"]').val());$amazon_jq('#post_fields').append('<strong>Field Name: </strong> <input type="text" name="template_field_names_'+index+'" /> ');$amazon_jq('#post_fields').append('<strong>Default Value: </strong> <input type="text" name="template_field_defaults_'+index+'" /> <br /><br />');index++;$amazon_jq('[name="template_field_index"]').val(index);}
this.selectTemplate=function()
{var temp_id=$amazon_jq('[name="amazon_post_template_select"]').val();var old_temp_id=$amazon_jq('#amazon_current_template').val();if(temp_id=='')
{$amazon_jq('#amazon_tools_template_fields').css('display','none');return;}
var template_fields={};$amazon_jq('.amazon_tools_field').each(function(index){template_fields[this.name]=$amazon_jq(this).val();});var data={action:'amazon_change_template',security:amazon_nonce,current_template:old_temp_id,new_template:temp_id,post_id:amazon_post_id,fields:template_fields};$amazon_jq.post(ajaxurl,data,amazon_lib.ajaxSuccess,'html').error(ajaxError);}
this.ajaxSuccess=function(response)
{$amazon_jq('#amazon_tools_template_fields').css('display','block');if(response==0)
{$amazon_jq('#amazon_post_fields_container').html('<em>Could not retrieve custom fields. Save this post to view custom fields.</em>');}
else
{$amazon_jq('#amazon_post_fields_container').html(response);}}
this.ajaxError=function()
{$amazon_jq('#amazon_tools_template_fields').css('display','block');$amazon_jq('#amazon_post_fields_container').html('<em>Could not retrieve custom fields. Save this post to view custom fields.</em>');}
this.newWindow();}