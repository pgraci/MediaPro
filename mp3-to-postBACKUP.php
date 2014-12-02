<?php
/*
  Plugin Name: MP3 to Post
  Plugin URI: http://www.fractured-state.com/2011/09/mp3-to-post-plugin/
  Description: Creates posts using ID3 information in MP3 files.
  Author: Paul Sheldrake
  Version: 1.2.3
  Author URI: http://www.fractured-state.com
 */


/**
 * Variables, store them in the options array to grab as necessary
 */
$uploadsDetails = wp_upload_dir();
$mp3FolderName = 'mp3-to-post';
$folderPath = $uploadsDetails['basedir'] . '/' . $mp3FolderName;
$base_path = parse_url($uploadsDetails['baseurl'], PHP_URL_PATH);


$mp3ToPostOptions = array(
  'folder_name' => $mp3FolderName,
  'folder_path' => $folderPath,
  'base_url_path' => $base_path,
);
update_option('mp3-to-post', serialize($mp3ToPostOptions));


/* create the menu item and link to to an admin function */
function mp3_admin_actions() {
  add_options_page(__('MP3 to Post','mp3-to-post'), __('MP3 to Post','mp3-to-post'), 1, "mp3-to-post", "mp3_admin");
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
   * http://wordpress.org/support/topic/plugin-blubrry-powerpress-podcasting-plugin-conflict-with-mp3-to-post-plugin?replies=1#post-2833002
   */
  require_once('getid3/getid3.php');

  ?>
  <div class="wrap">
    <h2>MP3 to Post</h2>
    <?php
    // load our variables in to an array
    $mp3ToPostOptions = unserialize(get_option('mp3-to-post'));
    ?>
    <p><?php _e('This plugin will scan for MP3 files in the directory below and then add them as posts.  It takes the ID3v2 title and comment and sets it as the post title and content respectively.  It also takes the file and attaches it to the post and adds a link to the post content.','mp3-to-post'); ?></p>
    <p><?php _e('The way the ID3 information is processed, <strong>the file needs to have the title and comment set in v1 and v2</strong>','mp3-to-post'); ?></p>
    <p><?php _e('If the genre is set on the file, that will be turned in to the category. If more than one genre is set in the ID3 information MP3 to Post only takes the first one.  If the genre is not set the category on the post is set to the default option.','mp3-to-post'); ?></p>
    <?php create_folder($mp3ToPostOptions['folder_path']); ?>
    <p><?php _e('Upload your files here:','mp3-to-post'); ?>
      <?php echo $mp3ToPostOptions['base_url_path'] . '/' .
        $mp3ToPostOptions['folder_name']; ?> </p>
    <form method="post" action="">
      <input type="submit" class="button-primary" name="create-all-posts" value="<?php _e('Create All Posts','mp3-to-post') ?>" />
      <input type="submit" class="button-primary" name="create-first-post" value="<?php _e('Create 1st Post','mp3-to-post') ?>" />
    </form>
    <?php
    // create some posts already!
    if (isset($_POST['create-all-posts'])) {
      echo '<pre>';
      print_r(mp3_to_post('all', $mp3ToPostOptions['folder_path']));
      echo '</pre>';
    }
    if (isset($_POST['create-first-post'])) {
      echo '<pre>';
      print_r(mp3_to_post(1, $mp3ToPostOptions['folder_path']));
      echo '</pre>';
    }
    // end POST check
    ?>
    <hr />
    <h3><?php _e('Files listed in the order they will be added','mp3-to-post'); ?></h3>
    <ol>
      <?php
      // get files
      $mp3Files = mp3_array($mp3ToPostOptions['folder_path']);
      // list files and details
      foreach ($mp3Files as $file) {
        $filePath = $mp3ToPostOptions['folder_path'].'/'.$file;
        $id3Details = get_ID3($filePath);
        echo '<li>
          <strong>' . $file . '</strong>
            <ul>
              <li><strong>' . _e('Title:', 'mp3-to-post') . '</strong> '.$id3Details['title'].'</li>
              <li><strong>' . _e('Category:', 'mp3-to-post') . '</strong> '.$id3Details['category'].'</li>
              <li><strong>' . _e('Comment:', 'mp3-to-post') . '</strong> '.$id3Details['comment'].'</li>
            </ul>
        </li>';
      }
      ?>
    </ol>

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
function mp3_to_post($limit = 'all', $folderPath) {
  $messages = array();

  // get an array of mp3 files
  $mp3Files = mp3_array($folderPath);

  // check of there are files to process
  if(count($mp3Files) == 0){
    array_push($messages, _e('There are no files to process', 'mp3-to-post'));
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

        // move the file to the right month/date directory in wordpress
        $wpFileInfo = wp_upload_bits(basename($filePath), null, file_get_contents($filePath));
        // if moved correctly delete the original
        if (empty($wpFileInfo['error'])) {
          unlink($filePath);
        }

        // add the mp3 file to the post as an attachment
        $wp_filetype = wp_check_filetype(basename($wpFileInfo['file']), null);
        $attachment = array(
          'post_mime_type' => $wp_filetype['type'],
          'post_title' => preg_replace('/\.[^.]+$/', '', basename($wpFileInfo['file'])),
          'post_content' => '',
          'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $wpFileInfo['file'], $postID);

        // you must first include the image.php file
        // for the function wp_generate_attachment_metadata() to work
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $wpFileInfo['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // add the link to the attachment to the post
        $attachmentLink = wp_get_attachment_link($attach_id, 'thumbnail', FALSE, FALSE, 'Download file');
        $updatePost = get_post($postID);
        $updated_post = array();
        $updated_post['ID'] = $postID;
        $updated_post['post_content'] = $updatePost->post_content . '<p>' . $attachmentLink . '</p>';
        wp_update_post($updated_post);

        // 
        array_push($messages, _e('Post created:', 'mp3-to-post') . ' ' . $title);
      } else {
        array_push($messages, _e('Post already exists:', 'mp3-to-post') . ' ' . $title);
      }
    } else {
      array_push($messages, _e('Either the title or comments are not set in the ID3 information.   Make sure they are both set for v1 and v2.', 'mp3-to-post'));
    }
    $i++;
  endwhile; //

  // return the messages
  return $messages;
}

/**
 * Creates a folder based on the path provided
 *
 * @param $folderpath
 */
function create_folder($folderPath){
  // check if directory exists and makes it if it isn't
  if (!is_dir($folderPath)) {
    if (!mkdir($folderPath, 0777)) {
      echo '<p><strong>Couldnt make the folder for you to put your files in, please check your permissions.</strong></p>';
    }
  }
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
?>
