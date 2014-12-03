jQuery(document).ready(function($){


    var custom_uploader;


    $('#upload_image_button').click(function(e) {

        e.preventDefault();

        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }

        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Process Media Library Custom Shit',
            button: {
                text: 'Select Songs'
            },
            multiple: true
        });

        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function() {


          var selection = file_frame.state().get('selection').toArray();

          selection.map( function( attachment ) {

            attachment = selection.toJSON();

            // Do something with attachment.id and/or attachment.url here

            the_final_list = attachment.url;
          });



            //attachment = custom_uploader.state().get('selection').toArray();

            $('#upload_image').val("foo " + the_final_list);
            //$('#upload_image').val(the_final_list);

        });

        //Open the uploader dialog
        custom_uploader.open();

    });


});


file_frame.on( 'select', function() {

    var selection = file_frame.state().get('selection');

    selection.map( function( attachment ) {

      attachment = attachment.toJSON();

      // Do something with attachment.id and/or attachment.url here
    });
  });
