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
    $('#customData').on('crmLoad', function() {
      var subset = {/literal}'{$subset}'{literal};
      if (subset == 'All') {
        $('.custom-group-Subset_2, .custom-group-Subset_3').hide();
      }
      else {
        $('.custom-group-' + subset).hide();
      }
    })
  });
</script>
{/literal}
