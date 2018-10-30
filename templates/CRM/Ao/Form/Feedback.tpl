{* HEADER *}

<div id="customData" class="crm-contribution-form-block-customData"></div>
{include file="CRM/Custom/Form/Edit.tpl"}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var group = {/literal}'{$subset}'{literal};
    if (group) {
      $('.custom-group-' + group).hide();
    }
  });
</script>
{/literal}
