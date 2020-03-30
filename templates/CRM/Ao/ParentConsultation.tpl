{literal}
<script type="text/javascript">
CRM.$(function($) {
  $( document ).ajaxComplete(function( event, xhr, settings ) {
    var str = settings.url;
    var txt = 'civicrm/custom';
    if(str.indexOf(txt) > -1) {
      // Contrast Ratios
      $('.crm-activity-form-block').find('a').css('color', '#464354');
      $('.crm-activity-form-block').find('a:link:hover').css('color', '#464354');
      $('.crm-activity-form-block').find('a:link').css('color', '#464354');
      $('.crm-activity-form-block').find('.description').css('color', '#555044');
      $('.crm-activity-form-block').find('input').css('border-color', '#464354');
      $('.crm-activity-form-block').find('.select2-search input, .select2-search, .select2-results, .select2-results__option--highlighted, .select2-results__option[aria-selected=true], .select2-result-label').css('color', '#464354');

      var adult = 'custom_332_-1_AdultNeeds';
      var adultoptions = 'custom_333_-1-row';

      var aoinfo = 'custom_332_-1_AOinfo';
      var aoinfooptions = 'custom_334_-1-row';

      var school = 'custom_332_-1_School';
      var schooloptions = 'custom_335_-1-row';

      var oap = 'custom_332_-1_OAP';
      var oapoptions = 'custom_829_-1-row';

      // Adult Needs
      if ($('#'+adult).is(':checked')) {
        $('tr.'+adultoptions).show();
      }
      else {
        $('tr.'+adultoptions).hide();
      }
      $('#'+adult).change(function (e) {
        if(!$(this).is(':checked')) {
          $('tr.'+adultoptions).hide();
          $('tr.'+adultoptions).find('input[type=checkbox]:checked').removeAttr('checked');
        }
        else {
          $('tr.'+adultoptions).show();
        }
      });

      // AO Info
      if ($('#'+aoinfo).is(':checked')) {
        $('tr.'+aoinfooptions).show();
      }
      else {
        $('tr.'+aoinfooptions).hide();
      }
      $('#'+aoinfo).change(function () {

        if(!$(this).is(':checked')) {
          $('tr.'+aoinfooptions).hide();
          $('tr.'+aoinfooptions).find('input[type=checkbox]:checked').removeAttr('checked');
        }
        else {
          $('tr.'+aoinfooptions).show();
        }
      });

      // School
      if ($('#'+school).is(':checked')) {
        $('tr.'+schooloptions).show();
      }
      else {
        $('tr.'+schooloptions).hide();
      }
      $('#'+school).change(function () {

        if(!$(this).is(':checked')) {
          $('tr.'+schooloptions).hide();
          $('tr.'+schooloptions).find('input[type=checkbox]:checked').removeAttr('checked');
        }
        else {
          $('tr.'+schooloptions).show();
        }
      });

      // OAP
      if ($('#'+oap).is(':checked')) {
        $('tr.'+oapoptions).show();
      }
      else {
        $('tr.'+oapoptions).hide();
      }
      $('#'+oap).change(function () {

        if(!$(this).is(':checked')) {
          $('tr.'+oapoptions).hide();
          $('tr.'+oapoptions).find('input[type=checkbox]:checked').removeAttr('checked');
        }
        else {
          $('tr.'+oapoptions).show();
        }
      });
    }
  });
});
</script>
{/literal}
