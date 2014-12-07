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

// disk path of upload directory
$folderPath = $uploadsDetails['basedir'];
$urlPath = $uploadsDetails['baseurl'];

$SongToPostOptions = array(
  'folder_path' => $folderPath,
  'base_url_path' => $urlPath,
);

update_option('audio-to-song-post', serialize($SongToPostOptions));


/* create the menu item and link to to an admin function */
// function song_admin_actions() {
//   add_options_page(__('Audio to Song Post','audio-to-song-post'), __('Audio to Song Post','audio-to-song-post'), 1, "audio-to-song-post", "song_admin");
// }

/* add the menu item */
add_action('admin_menu', 'song_admin_actions');

function song_admin_actions(){
    add_menu_page( 'AudioPost', 'AudioPost', 'manage_options', 'audio-to-song-post', 'song_admin', 'dashicons-playlist-audio', 11 );
}

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
    <h2>AudioPost</h2>
    <?php
    // load our variables in to an array
    $SongToPostOptions = unserialize(get_option('audio-to-song-post'));
    ?>
    <form method="post" action="">

      <p>select type of post - blog, remix song, or podcast</p>
      <select id="type_of_post" name="type_of_post">
        <option value="songs">REMIX Song Post</option>
        <option value="post">Blog Post</option>
      </select>
      <br />

      <p>posting mode</p>
      <select id="post_mode" name="post_mode">
        <option value="1">Create a post from each selected song</option>
        <option value="2">Create a single post with playlist of tracks</option>
      </select>
      <br />

      <p>title</p>
      <select id="title_mode" name="title_mode">
        <option value="1">from Title</option>
        <option value="2">from Album</option>
        <option value="3">Artist - Title</option>
      </select>
      <br />

      <p>description</p>
      <select id="description_mode" name="description_mode">
        <option value="1">from Comments</option>
        <option value="2">from Description</option>
        <option value="3">Comments + Description</option>
        <option value="3">Comments + Description + Genre + BPM</option>
      </select>
      <br />

      <p>tags</p>
      <select id="tags_mode" name="tags_mode">
        <option value="1">from Grouping</option>
        <option value="2">from Comments</option>
        <option value="3">from Description</option>
      </select>
      <br />

      <input id="create_posts" name="create_posts" type="submit" class="button-primary" style="display: none;" value="<?php _e('Create Posts','audio-to-song-post') ?>" />
      <input id="posts_ids" name="posts_ids" type="hidden" size="36" value="" />
    </form>
    <?php
    // create post!
    if (isset($_POST['create_posts'])) {
      $songs_array = (audio_to_song_post('all', $_POST['posts_ids'], $SongToPostOptions['folder_path'], $SongToPostOptions['base_url_path'], $_POST['type_of_post'], $_POST['post_mode']));

      $arrlength = count($songs_array);


      for($x = 0; $x < $arrlength; $x++) {
        echo $songs_array[$x];
      }

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
function audio_to_song_post($limit = 'all', $list_of_ids, $folderPath, $urlPath, $post_type, $posting_mode) {
  $messages = array();
  $the_playlist_array_final = array();

  // check of there are files to process
  if(count($list_of_ids) == 0){
    array_push($messages, _e('There are no files to process', 'audio-to-song-post'));
    return $messages;
  } else {

      // Initialize getID3 engine
      $getID3 = new getID3;

      $mp3Files_array = explode(',', $list_of_ids); //split string into array seperated by ', '
        foreach($mp3Files_array as $song_id) //loop over values
        {

          // get url of media attachment
          $song_url = wp_get_attachment_url($song_id);

          // get image id of attached featured image
          $post_thumbnail_id = get_post_thumbnail_id($song_id);

          // TODO also get the original description and use that if nothing else present

          // lookup each song's url path by replacing url path with folder path
          $filePath = str_replace($urlPath,$folderPath,$song_url);

          $ThisFileInfo = $getID3->analyze($filePath);


              // TODOS
              // check to see if it has idv2 tags if so use them
              // if not use id3v1 tags
              // for itunes purchases, make sure to use other field names
              // allow user to insert songs / playlist into existing post!
              // move playlist insertion logic for remix into seperate routine

              // create array of song data to be processed


              $title = $ThisFileInfo['tags_html']['id3v2']['title'][0];
              $album = $ThisFileInfo['tags_html']['id3v2']['album'][0];

              if ($posting_mode == '2') {
                $title = $album;
              } else {
                $title = $title;
              }

              $artist = $ThisFileInfo['tags_html']['id3v2']['artist'][0];
              $album_artist = $ThisFileInfo['tags_html']['id3v2']['band'][0];
              $category = $ThisFileInfo['tags_html']['id3v2']['genre'][0];
              $description = $ThisFileInfo['tags_html']['id3v2']['subtitle'][0];
              $bpm = $ThisFileInfo['tags_html']['id3v2']['bpm'][0];
              $composer = $ThisFileInfo['tags_html']['id3v2']['composer'][0];
              $grouping = $ThisFileInfo['tags_html']['id3v2']['content_group_description'][0];
              $encoded_by = $ThisFileInfo['tags_html']['id3v2']['encoded_by'][0];

              $comment_array = $ThisFileInfo['tags_html']['id3v2']['comments'];

              foreach($comment_array as $comment_value) //loop over values
              {
                if (testcommentsforvalid($comment_value)) {
                  $comment = $comment_value;
                  continue;
                } else {
                  $comment = "";
                }
              }

              if ($post_type == 'post') {

                  if ($posting_mode == '1') {
                    $playlist_ids = $song_id;
                  } else {
                    $playlist_ids = $list_of_ids;
                  }

                  $description = "<p>[playlist ids=" . $playlist_ids . "]</p>" . $description;

              } else {
                $the_playlist_array = array(
                  'title' => $title,
                  'mp3' => $song_url,
                  'buy_title_a' => '',
                  'buy_icon_a' => 'cloud-download',
                  'buy_link_a' => '',
                  'buy_title_b' => '',
                  'buy_icon_b' => 'cloud-download',
                  'buy_link_b' => '',
                  'buy_title_c' => '',
                  'buy_icon_c' => 'cloud-download',
                  'buy_link_c' => '',
                  'buy_title_d' => '',
                  'buy_icon_d' => 'cloud-download',
                  'buy_link_d' => '',
                );

                //for remix song playlists push this into
                array_push($the_playlist_array_final, $the_playlist_array);

              }

              // check if we have a title
              // proceed to make post if this works
              if ($title){

                // check if post exists by search for one with the same title
                // filtering by song name not working

                $searchArgs = array(
                  'post_title_like' => $title,
                  'post_type' => $post_type,
                );

                $titleSearchResult = new WP_Query($searchArgs);

                // If there are no posts with the title of the mp3 then make the post
                if ($titleSearchResult->post_count == 0) {
                  // create basic post with info from ID3 details
                  $my_post = array(
                    'post_title' => $title,
                    'post_content' => $description,
                    'post_author' => 1,
                    'post_name' => $title,
                  );
                  // Insert the post!!
                  $postID = wp_insert_post($my_post);

                  // set post type
                  set_post_type($postID, $post_type);

                  if ($post_type == 'songs') {
                    // TODO set artist for songs posts
                    // don't insert playlist for playlist post

                    add_post_meta($postID, "playlist", $the_playlist_array_final);
                    add_post_meta($postID, "auto_play", 'false');


                    // If the artist is set try to find matching artist page
                    // create new artist page if option chosen
                    if(!empty($artist)){

                        $searchArgs = array(
                          'post_title_like' => $artist,
                          'post_type' => 'artists',
                        );

                        $artistSearchResult = new WP_Query($searchArgs);

                        if ($artistSearchResult->post_count == 0) {
                            // create new artist
                            $artist_page_post = array(
                              'post_title' => $artist,
                              'post_content' => "bio coming soon...",
                              'post_author' => 1,
                              'post_name' => $artist,
                            );
                            // Insert the post!!
                            $artist_page_id = wp_insert_post($artist_page_post);

                            // set post type
                            set_post_type($artist_page_id, "artists");

                        } else {
                            // get artist id
                            $artist_page_id = $artistSearchResult->post->ID;
                        }

                        // update song post with artist id
                        add_post_meta($postID, "artist_nameaa", $artist_page_id);

                    }
                  }



                  //set post tags
                  wp_set_post_tags($postID, $comment);

                  //set featured image
                  set_post_thumbnail($postID, $post_thumbnail_id);

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


                  array_push($messages, _e('<p>' . $post_type . ' created: '  . $title . '</p>', 'audio-to-song-post'));
                } else {
                  array_push($messages, _e('<p>' . $post_type . ' already exists: ' . $title . '</p>', 'audio-to-song-post'));
                }
              } else {
                array_push($messages, _e('Title not set in the ID3 information.   Make sure it is set for v1 and v2.', 'audio-to-song-post'));
              }


              // do final playlist insert into post here for remix playlist songs

        }
  }

  // return the messages
  return $messages;
}


function testcommentsforvalid($comment) {
  $comment_ary = explode(" ", $comment);

  // check to see if there are 10 elements to the array, and if the first 3 are 8 chars in length
  // http://id3.org/iTunes%20Normalization%20settings
  //

    if (($comment=='0&#0;&#0;')||empty($comment)) {
      return false;
    } elseif ((count($comment_ary) == 10)||(count($comment_ary)== 12)) {
       return false;
    } else {
      return true;
    }
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
