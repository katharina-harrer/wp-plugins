jQuery( '#recipe_form' ).on( 'submit', function() {

    // usage of FormData to construct a set of key/value pairs (= form fields + their values)
    // constructor and append are supported on every common browser
    var form_data = new FormData(this);
    form_data.append('nonce', recipe_vars.send_form_nonce);

    jQuery.ajax({
        url: recipe_vars.ajax_url, // WordPress AJAX endpoint
        type: 'POST',
        data: form_data, 
        enctype: 'multipart/form-data',
        processData: false, // required because of the image
        contentType: false, // required because of the image
        success: function(response) {

            // echo that the server sent back
            // reset form data if submission was successful
            if(response.includes('success')) 
                document.getElementById("recipe_form").reset();

            // display message
            jQuery("#AjaxResponse").html(response);
        },
        fail: function(err) {
            
            // display error if something goes wrong when doing the AJAX request
            jQuery("#AjaxResponse").html(err);
        }
    })
     
    return false;
});