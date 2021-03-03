cj(document).ready(function() {
  if (cj('#membership_type_id_1').val()) {
    cj('#membership_type_id_1').trigger('change');
  }
});
