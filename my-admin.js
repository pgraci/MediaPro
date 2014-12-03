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
            attachment = custom_uploader.state().get('selection');

            $.each(attachment, function(idx, obj) {
	             alert(obj.url);
             });

            //$('#upload_image').val(attachment.url);
            //alert(attachment.url);
        });

        //Open the uploader dialog
        custom_uploader.open();

    });


});