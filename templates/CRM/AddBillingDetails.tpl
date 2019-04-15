{literal}
<script type="text/javascript">
CRM.$(function($) {
  $('#contact_id').change(function() {
    addBillingDetails($(this).val());
  });

  function addBillingDetails(cid) {
    // Set name fields.
    CRM.api3('Contact', 'get', {
      "sequential": 1,
      "return": ["first_name","middle_name","last_name"],
      "id": cid
    }).done(function(result) {
      if (result.values[0]) {
        $('#billing_first_name').val(result.values[0].first_name);
        $('#billing_middle_name').val(result.values[0].middle_name);
        $('#billing_last_name').val(result.values[0].last_name);
      }
    });

    // Set address fields.
    CRM.api3('Address', 'get', {
      "sequential": 1,
      "contact_id": cid,
      "location_type_id": "Billing"
    }).done(function(result) {	
      if (result.values[0]) {
        $('#billing_street_address-5').val(result.values[0].street_address);
        $('#billing_city-5').val(result.values[0].city);
        $('#billing_country_id-5').select2('val', result.values[0].country_id);
        $('#billing_country_id-5').trigger('change');
        $( document ).ajaxComplete(function( event, xhr, settings ) {
          var url = settings.url;
          if(url.indexOf('civicrm/ajax/jqState') != -1){
            $('#billing_state_province_id-5').select2('val', result.values[0].state_province_id);
          }
        });
        $('#billing_postal_code-5').val(result.values[0].postal_code);
      }		     
    });
  }
  
});
</script>
{/literal}