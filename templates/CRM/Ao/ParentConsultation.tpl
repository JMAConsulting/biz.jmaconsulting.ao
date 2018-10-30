{literal}
<script type="text/javascript">
CRM.$(function($) {
  $( document ).ajaxComplete(function( event, xhr, settings ) {
    var adult = 'custom_332_-1_AdultNeeds';
    var adultoptions = 'custom_333_-1-row';

    var aoinfo = 'custom_332_-1_AOinfo';
    var aoinfooptions = 'custom_334_-1-row';

    var school = 'custom_332_-1_School';
    var schooloptions = 'custom_335_-1-row';

    // Adult Needs
    if ($('#'+adult).is(':checked')) {
      $('tr.'+adultoptions).show();
    }
    else {
      $('tr.'+adultoptions).hide();
    }
    $('#'+adult).click(function () {
      $('tr.'+adultoptions).toggle();

      if(!$(this).is(':checked')) {
        $('tr.'+adultoptions).find('input[type=checkbox]:checked').removeAttr('checked');
      }
    });

    // AO Info
    if ($('#'+aoinfo).is(':checked')) {
      $('tr.'+aoinfooptions).show();
    }
    else {
      $('tr.'+aoinfooptions).hide();
    }
    $('#'+aoinfo).click(function () {
      $('tr.'+aoinfooptions).toggle();

      if(!$(this).is(':checked')) {
        $('tr.'+aoinfooptions).find('input[type=checkbox]:checked').removeAttr('checked');
      }
    });

    // School
    if ($('#'+school).is(':checked')) {
      $('tr.'+schooloptions).show();
    }
    else {
      $('tr.'+schooloptions).hide();
    }
    $('#'+school).click(function () {
      $('tr.'+schooloptions).toggle();

      if(!$(this).is(':checked')) {
        $('tr.'+schooloptions).find('input[type=checkbox]:checked').removeAttr('checked');
      }
    });
  });
});
</script>
{/literal}
