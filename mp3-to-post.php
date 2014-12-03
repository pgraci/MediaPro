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


/**
 * Variables, store them in the options array to grab as necessary
 */
$uploadsDetails = wp_upload_dir();

// disk path of upload directory
$folderPath = $uploadsDetails['basedir'];
$urlPath = $uploadsDetails['baseurl'];

$SongToPostOptions = array(
  'folder_path' => $folderPath,
  'base_url_path' => $urlPath,
);

update_option('audio-to-song-post', serialize($SongToPostOptions));


/* create the menu item and link to to an admin function */
function song_admin_actions() {
  add_options_page(__('Audio to Song Post','audio-to-song-post'), __('Audio to Song Post','audio-to-song-post'), 1, "audio-to-song-post", "song_admin");
}

/* add the menu item */
add_action('admin_menu', 'song_admin_actions');

/**
 * Creates the admin page for the plugin
 *
 */
function song_admin() {
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
    $SongToPostOptions = unserialize(get_option('audio-to-song-post'));
    ?>
    <p><?php _e('This plugin will allow you to select songs in the Media Library, and turn them into Song type posts.','audio-to-song-post'); ?></p>
    <p><?php _e('It takes the ID3v2 title and sets it as the post title. ','audio-to-song-post'); ?></p>
    <p><?php _e('Comments are converted into Tags if option is checked.','audio-to-song-post'); ?></p>
    <p><?php _e('Grouping field is converted into SubGenre(s) or Subcategories depending on your needs.','audio-to-song-post'); ?></p>
    <p><?php _e('The MP3 artwork will be set as the featured image for the post.','audio-to-song-post'); ?></p>
    <p><?php _e('The way the ID3 information is processed, <strong>the file needs to have the title and comment set in v1 and v2???? RESEARCH AND MODIFY IF POSSIBLE</strong>','audio-to-song-post'); ?></p>
    <p><?php _e('If the genre is set on the file, that will be turned in to the category. If more than one genre is set in the ID3 information Audio to Song Post only takes the first one.  If the genre is not set the category on the post is set to the default option.','audio-to-song-post'); ?></p>


    <form method="post" action="">
      <input id="create_posts" name="create_posts" type="submit" class="button-primary" style="display: none;" value="<?php _e('Create Posts','audio-to-song-post') ?>" />
      <input id="posts_ids"name="posts_ids" type="hidden" size="36" value="" />
    </form>
    <?php
    // create post!
    if (isset($_POST['create_posts'])) {
      echo '<pre>';
      print_r(audio_to_song_post('all', $_POST['posts_ids'], $SongToPostOptions['folder_path'], $SongToPostOptions['base_url_path']));
      echo '</pre>';
    }
    // end POST check
    ?>
    <hr />

    <div class="uploader">
      <br />
      <input id="upload_image_button" class="button-primary" type="button" value="Select Songs" />
    </div>

  </div>
<?php
}
// end song_admin



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
function audio_to_song_post($limit = 'all', $list_of_urls, $folderPath, $urlPath) {
  $messages = array();
  $mp3Files = array();

  // get an array of mp3 files
  //$mp3Files = mp3_array($folderPath);

  $mp3Files_array = explode(',', $list_of_urls); //split string into array seperated by ', '
    foreach($mp3Files_array as $song_url) //loop over values
    {
      // lookup each song's url path by replacing url path with folder path
        $song_diskpath = str_replace($urlPath,$folderPath,$song_url);

        array_push($mp3Files, $song_diskpath);
    }

  //sort($mp3Files);

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
    $filePath = $mp3Files[$i];
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
      array_push($messages, _e($filePath . 'Either the title or comments are not set in the ID3 information.   Make sure they are both set for v1 and v2.', 'audio-to-song-post'));
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

add_action('admin_enqueue_scripts', 'my_admin_scripts');

function my_admin_scripts() {
    if (isset($_GET['page']) && $_GET['page'] == 'audio-to-song-post') {
        wp_enqueue_media();
        wp_register_script('my-admin-js', WP_PLUGIN_URL.'/mp3-to-post/my-admin.js', array('jquery'));
        wp_enqueue_script('my-admin-js');
    }
}
?>
