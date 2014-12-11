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
            title: 'Upload or Select songs from your library',
            button: {
                text: 'Select Songs'
            },
            library: { type : 'audio'},
            multiple: true
        });

        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function() {

            var all_the_ids = [];

            var selection = custom_uploader.state().get('selection').toJSON();
              for (var key in selection) {
                if (selection.hasOwnProperty(key)) {
                  all_the_ids.push(selection[key].id);
                }
              }

            $('#posts_ids').val(all_the_ids.join());
            $('#upload_image_button').css('display','none');
            $('.uploader').html(all_the_ids.length + ' songs selected.  Next select your options below, and click Create Posts.');

            if (all_the_ids.length > 1) {
              $('#posting_mode_div').css('display','block');
            }

            $('.messages').html('');

            $('#create_posts').css('display','block');

        });


        //Open the uploader dialog
        custom_uploader.open();

    });


});
