<?php


/*
  Plugin Name: Audio to Song Post
  Plugin URI: http://www.triagency.com/MAKEAURL/
  Description: Creates posts using ID3 information in audio file from Media Library.
  Author: Phil Graci
  Version: 1.2.3
  Author URI: http://www.triagency.com
 */


/**
 * Variables, store them in the options array to grab as necessary
 */
$uploadsDetails = wp_upload_dir();
$mp3FolderName = 'audio-to-song-post';
$folderPath = $uploadsDetails['basedir'] . '/' . $mp3FolderName;
$base_path = parse_url($uploadsDetails['baseurl'], PHP_URL_PATH);


$mp3ToPostOptions = array(
  'folder_name' => $mp3FolderName,
  'folder_path' => $folderPath,
  'base_url_path' => $base_path,
);
update_option('audio-to-song-post', serialize($mp3ToPostOptions));


/* create the menu item and link to to an admin function */
function mp3_admin_actions() {
  add_options_page(__('Audio to Song Post','audio-to-song-post'), __('Audio to Song Post','audio-to-song-post'), 1, "audio-to-song-post", "mp3_admin");
}

/* add the menu item */
add_action('admin_menu', 'mp3_admin_actions');

/**
 * Creates the admin page for the plugin
 *
 */
function mp3_admin() {
  /**
   * Add the ID3 library.  Adding it here so it's only used as needed
   * http://wordpress.org/support/topic/plugin-blubrry-powerpress-podcasting-plugin-conflict-with-audio-to-song-post-plugin?replies=1#post-2833002
   */
  require_once('getid3/getid3.php');

  ?>
  <div class="wrap">
    <h2>Audio to Song Post</h2>
    <?php
    // load our variables in to an array
    $mp3ToPostOptions = unserialize(get_option('audio-to-song-post'));
    ?>
    <p><?php _e('This plugin will allow you to select songs in the Media Library, and turn them into Song type posts.','audio-to-song-post'); ?></p>
    <p><?php _e('It takes the ID3v2 title and sets it as the post title. ','audio-to-song-post'); ?></p>
    <p><?php _e('Comments are converted into Tags if option is checked.','audio-to-song-post'); ?></p>
    <p><?php _e('Grouping field is converted into SubGenre(s) or Subcategories depending on your needs.','audio-to-song-post'); ?></p>
    <p><?php _e('The MP3 artwork will be set as the featured image for the post.','audio-to-song-post'); ?></p>
    <p><?php _e('The way the ID3 information is processed, <strong>the file needs to have the title and comment set in v1 and v2???? RESEARCH AND MODIFY IF POSSIBLE</strong>','audio-to-song-post'); ?></p>
    <p><?php _e('If the genre is set on the file, that will be turned in to the category. If more than one genre is set in the ID3 information Audio to Song Post only takes the first one.  If the genre is not set the category on the post is set to the default option.','audio-to-song-post'); ?></p>


    <form method="post" action="">
      <input type="submit" class="button-primary" name="create-all-posts" value="<?php _e('Create All Posts','audio-to-song-post') ?>" />
    </form>
    <?php
    // create some posts already!
    if (isset($_POST['create-all-posts'])) {
      echo '<pre>';
      print_r(audio_to_song_post('all', $mp3ToPostOptions['folder_path']));
      echo '</pre>';
    }
    // end POST check
    ?>
    <hr />
    <script language="JavaScript">
        jQuery(document).ready(function() {

          var file_frame;

            jQuery('#upload_image_button').on('click', function( event ){

              event.preventDefault();

              // If the media frame already exists, reopen it.
              if ( file_frame ) {
                file_frame.open();
                return;
              }

              // Create the media frame.
              file_frame = wp.media.frames.file_frame = wp.media({
                title: jQuery( this ).data( 'uploader_title' ),
                button: {
                  text: jQuery( this ).data( 'uploader_button_text' ),
                },
                multiple: false  // Set to true to allow multiple files to be selected
              });

              // When an image is selected, run a callback.
              file_frame.on( 'select', function() {
                // We set multiple to false so only get one image from the uploader
                attachment = file_frame.state().get('selection').first().toJSON();

                // Do something with attachment.id and/or attachment.url here

                alert(attachment.url);
              });

              // Finally, open the modal
              file_frame.open();
            });

        });
    </script>


    		<input id="upload_image" type="text" size="36" name="upload_image" value="" />
        <input id="upload_image_button" class="button-primary" type="button" value="Select Media" />


  </div>
<?php
}
// end mp3_admin



/**
 * Adds a select query that lets you search for titles more easily using WP Query
 */
function title_like_posts_where($where, &$wp_query) {
  global $wpdb;
  if ($post_title_like = $wp_query->get('post_title_like')) {
    $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'' .
      esc_sql(like_escape($post_title_like)) . '%\'';
  }
  return $where;
}
add_filter('posts_where', 'title_like_posts_where', 10, 2);

/**
 * Takes a string and only returns it if it has '.mp3' in it.
 *
 * @param $string
 *   A string, possibly containing .mp3
 *
 * @return
 *   Returns a string.  Only if it contains '.mp3' or it returns FALSE
 */
function mp3_only($filename) {
  $findme = '.mp3';
  $pos = strpos($filename, $findme);

  if ($pos !== false) {
    return $filename;
  } else {
    return FALSE;
  }
}

/**
 * Creates a post from an mp3 file.
 *
 * @param $limit
 *  Limits the number of items created at one time.  Use an intager
 *
 * @param $path
 *  The base path to the folder containing the mp3s to convert to posts
 *
 * @return $array
 *   Will provide an array of messages
 */
function audio_to_song_post($limit = 'all', $folderPath) {
  $messages = array();

  // get an array of mp3 files
  $mp3Files = mp3_array($folderPath);

  // check of there are files to process
  if(count($mp3Files) == 0){
    array_push($messages, _e('There are no files to process', 'audio-to-song-post'));
    return $messages;
  }

  // Initialize getID3 engine
  $getID3 = new getID3;

  // loop through all the files and create posts
  $i = 0;
  if ($limit == 'all') {
    $limit = count($mp3Files) - 1;
  } else {
    $limit--; // subtract one to work with arrays
  }
  while ($i <= $limit):

    // Analyze file and store returned data in $ThisFileInfo
    $filePath = $folderPath . '/' . $mp3Files[$i];
    $ThisFileInfo = $getID3->analyze($filePath);

    /*
      Optional: copies data from all subarrays of [tags] into [comments] so
      metadata is all available in one location for all tag formats
      metainformation is always available under [tags] even if this is not called
     */
    getid3_lib::CopyTagsToComments($ThisFileInfo);
    $title = $ThisFileInfo['tags']['id3v2']['title'][0];
    $category = $ThisFileInfo['tags']['id3v2']['genre'][0];
    $comment = $ThisFileInfo['tags']['id3v2']['comments'][0];

    // check if we have a title and a comment
    if ($title && $comment){

      // check if post exists by search for one with the same title
      $searchArgs = array(
        'post_title_like' => $title
      );
      $titleSearchResult = new WP_Query($searchArgs);

      // If there are no posts with the title of the mp3 then make the post
      if ($titleSearchResult->post_count == 0) {
        // create basic post with info from ID3 details
        $my_post = array(
          'post_title' => $title,
          'post_content' => $comment,
          'post_author' => 1,
          'post_name' => $title,
        );
        // Insert the post!!
        $postID = wp_insert_post($my_post);

        //set post tags
        wp_set_post_tags($postID, $comment);

        // If the category/genre is set then update the post
        if(!empty($category)){
          $category_ID = get_cat_ID($category);
          // if a category exists
          if($category_ID) {
            $categories_array = array($category_ID);
            wp_set_post_categories($postID, $categories_array);
          }
          // if it doesn't exist then create a new category
          else {
            $new_category_ID = wp_create_category($category);
            $categories_array = array($new_category_ID);
            wp_set_post_categories($postID, $categories_array);
          }
        }


        array_push($messages, _e('Post created:', 'audio-to-song-post') . ' ' . $title);
      } else {
        array_push($messages, _e('Post already exists:', 'audio-to-song-post') . ' ' . $title);
      }
    } else {
      array_push($messages, _e('Either the title or comments are not set in the ID3 information.   Make sure they are both set for v1 and v2.', 'audio-to-song-post'));
    }
    $i++;
  endwhile; //

  // return the messages
  return $messages;
}



/**
 * Gives an array of mp3 files to turn in to posts
 *
 * @param $folderPath
 *
 * @return $array
 *  Returns an array of mp3 file names from the directory created by the plugin
 */
function mp3_array($folderPath){
  // scan folders for files and get id3 info
  $mp3Files = array_slice(scandir($folderPath), 2); // cut out the dots..
  // filter out all the non mp3 files
  $mp3Files = array_filter($mp3Files, "mp3_only");
  // sort the files
  sort($mp3Files);

  return $mp3Files;
}


/**
 * Gets the ID3 info of a file
 *
 * @param $filePath
 * String, base path to the mp3 file
 *
 * @return array
 * Keyed array with title, comment and category as keys.
 */
function get_ID3($filePath) {
  // Initialize getID3 engine
  $get_ID3 = new getID3;
  $ThisFileInfo = $get_ID3->analyze($filePath);

  /**
   * Optional: copies data from all subarrays of [tags] into [comments] so
   * metadata is all available in one location for all tag formats
   * metainformation is always available under [tags] even if this is not called
   */
  getid3_lib::CopyTagsToComments($ThisFileInfo);
  $title = $ThisFileInfo['tags']['id3v2']['title'][0];
  $comment = $ThisFileInfo['tags']['id3v2']['comments'][0];
  $category = $ThisFileInfo['tags']['id3v2']['genre'][0];

  $details = array(
    'title' => $title,
    'comment' => $comment,
    'category' => $category,
  );

  return $details;
}

function setting_up() {
  wp_enqueue_media();
}

add_action('admin_setting_up', 'setting_up');
?>
