{* HEADER *}

<div id="customData" class="crm-contribution-form-block-customData"></div>
{include file="CRM/Custom/Form/Edit.tpl" groupID=30}

{if $subsetID}
  {include file="CRM/Custom/Form/Edit.tpl" groupID=$subsetID}
{/if}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
