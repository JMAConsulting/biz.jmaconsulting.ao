{* HEADER *}

{* FIELD EXAMPLE: OPTION 1 (AUTOMATIC LAYOUT) *}

{foreach from=$membershipTypes item=membershipTypeName key=membershipTypeID}
  <div class="crm-section">
    <div class="label">{$form.contribution_page_id.$membershipTypeID.label}</div>
    <div class="content">{$form.contribution_page_id.$membershipTypeID.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}


{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
