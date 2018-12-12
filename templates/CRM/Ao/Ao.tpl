{if $lang_spoken and $lang_other}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      var lang_spoken = '{/literal}{$lang_spoken}{literal}';
      var lang_other = 'editrow-{/literal}{$lang_other}{literal}';

      // Hide/show fields based on initial value
      if ($('#'+lang_spoken).val() == 'Other') {
        $('#'+lang_other).show();
      }
      else {
        $('#'+lang_other).hide();
      }
      
      // Hide/show fields based on selection
      $('#'+lang_spoken).change(function() {
        if ($(this).val() == 'Other') {
          $('#'+lang_other).show();
        }
        else {
          $('#'+lang_other).hide();
        }
      });
    });
  </script>
{/literal}
{/if}
