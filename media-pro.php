<?php
/*
  Plugin Name: MediaPro
  Plugin URI: http://philgraci.com/work/mediapro
  Description: Creates posts using ID3 information in MP3 file from Media Library.
  Author: Phil Graci
  Version: 1.0.1
  Author URI: http://philgraci.com
 */

/**
 * Variables, store them in the options array to grab as necessary
 */
$uploadsDetails = wp_upload_dir();

// disk path of upload directory
$folderPath = $uploadsDetails['basedir'];
$urlPath = $uploadsDetails['baseurl'];

$MediaProOptions = array(
  'folder_path' => $folderPath,
  'base_url_path' => $urlPath,
);

update_option('media-pro', serialize($MediaProOptions));


/* add the menu item */
add_action('admin_menu', 'song_admin_actions');

function song_admin_actions(){
    add_menu_page( 'MediaPro', 'MediaPro', 'manage_options', 'media-pro', 'song_admin', 'dashicons-playlist-audio', 9 );
}

/**
 * Creates the admin page for the plugin
 *
 */
function song_admin() {
  /**
   * Add the ID3 library.
   */
  require_once('getid3/getid3.php');

  ?>
  <style>
    fieldset {
      padding: 10px;
    }
    .mode_label {
      width: 100px;
      float: left;
      }

    .messages,
    .media-pro-header,
    .media-pro-form {
      background-color: #fff;
      padding: 10px;
      margin: 10px;
    }

    .media-pro-form h1 {
      font-size: 110%;
    }

    #posting_mode_div {
      display: none;
    }
  </style>

  <div class="wrap">
    <div class="media-pro-header">
      <h2>MediaPro</h2>

      <div class="uploader">
        <input id="upload_image_button" class="button-primary" type="button" value="Select Songs" />
      </div>
    </div >
    <?php
    // load our variables in to an array
    $MediaProOptions = unserialize(get_option('media-pro'));

    $selected_type_of_post = $_POST['type_of_post'];
    $selected_post_mode = $_POST['post_mode'];
    $selected_date_mode = $_POST['date_mode'];
    $selected_title_mode = $_POST['title_mode'];
    $selected_artist_mode = $_POST['artist_mode'];
    $selected_description_mode = $_POST['description_mode'];
    $selected_tags_mode = $_POST['tags_mode'];
    $selected_autoplay_mode = $_POST['autoplay_mode'];
    $selected_subgenre_mode = $_POST['subgenre_mode'];

    ?>

      <form method="post" action="">
      <div class="media-pro-form">
        <h1>Post Options</h1>
        <fieldset>
          <label class="mode_label" for="type_of_post">Post Type</label>
          <select id="type_of_post" name="type_of_post">
            <option value="post">Blog Post</option>
            <option value="songs" <?php if ($selected_type_of_post=='songs') {echo "selected";} ?>>REMIX Song</option>
          </select>
        </fieldset>

        <div id="posting_mode_div">
        <fieldset>
          <label class="mode_label" for="post_mode">Post Mode</label>
          <select id="post_mode" name="post_mode">
            <option value="1">Create multiple posts</option>
            <option value="2" <?php if ($selected_post_mode=='2') {echo "selected";} ?>>Create one post with playlist of tracks</option>
          </select>
        </fieldset>
        </div>

        <fieldset>
          <label class="mode_label" for="date_mode">Post Date</label>
          <select id="date_mode" name="date_mode">
            <option value="0">Now</option>
            <option value="1" <?php if ($selected_date_mode=='1') {echo "selected";} ?>>Release Year</option>
          </select>
        </fieldset>

        <fieldset>
          <label class="mode_label" for="title_mode">Post Title</label>
          <select id="title_mode" name="title_mode">
            <option value="1">from Song Title</option>
            <option value="2" <?php if ($selected_title_mode=='2') {echo "selected";} ?>>from Album</option>
            <option value="3" <?php if ($selected_title_mode=='3') {echo "selected";} ?>>Artist + Song Title</option>
          </select>
        </fieldset>

        <fieldset>
          <label class="mode_label" for="description_mode">Post Content</label>
          <select id="description_mode" name="description_mode">
            <option value="1">from Comments</option>
            <option value="2" <?php if ($selected_description_mode=='2') {echo "selected";} ?>>from Description</option>
            <option value="3" <?php if ($selected_description_mode=='3') {echo "selected";} ?>>"Song Title" from "Album" by Artist. Year + Genre + BPM + ISRC</option>
          </select>
        </fieldset>

        <fieldset>
          <label class="mode_label" for="tags_mode">Tagging</label>
          <select id="tags_mode" name="tags_mode">
            <option value="0">No tagging</option>
            <option value="1" <?php if ($selected_tags_mode=='1') {echo "selected";} ?>>from Grouping</option>
            <option value="2" <?php if ($selected_tags_mode=='2') {echo "selected";} ?>>from Comments</option>
            <option value="3" <?php if ($selected_tags_mode=='3') {echo "selected";} ?>>from Description</option>
          </select>
        </fieldset>

      </div>

      <div class="media-pro-form">
        <h1>Song Options</h1>

        <fieldset>
          <label class="mode_label" for="artist_mode">Artist</label>
          <select id="artist_mode" name="artist_mode">
            <option value="0">from Artist</option>
            <option value="1" <?php if ($selected_artist_mode=='1') {echo "selected";} ?>>from Album Artist</option>
          </select>
        </fieldset>

        <fieldset>
          <label class="mode_label" for="autoplay_mode">Autoplay</label>
          <select id="autoplay_mode" name="autoplay_mode">
            <option value="false">Off</option>
            <option value="true" <?php if ($selected_autoplay_mode=='true') {echo "selected";} ?>>On</option>
          </select>
        </fieldset>

        <fieldset>
          <label class="mode_label" for="subgenre_mode">Subgenre</label>
          <select id="subgenre_mode" name="subgenre_mode">
            <option value="0">Off</option>
            <option value="1" <?php if ($selected_subgenre_mode=='1') {echo "selected";} ?>>from Grouping</option>
          </select>
        </fieldset>
      </div>
      <div class="media-pro-form">
        <input id="create_posts" name="create_posts" type="submit" class="button-primary" style="display: none;" value="<?php _e('Create Posts','media-pro') ?>" />
        <input id="posts_ids" name="posts_ids" type="hidden" size="36" value="" />
      </div>
      </form>


    <div class="messages">
    <?php
    // create post!
    if (isset($_POST['create_posts'])) {
      $songs_array = (audio_to_song_post($_POST['posts_ids'], $MediaProOptions['folder_path'], $MediaProOptions['base_url_path'], $selected_type_of_post, $selected_post_mode, $selected_autoplay_mode, $selected_date_mode, $selected_title_mode, $selected_description_mode, $selected_tags_mode, $selected_artist_mode, $selected_subgenre_mode));

      $arrlength = count($songs_array);


      for($x = 0; $x < $arrlength; $x++) {
        echo $songs_array[$x];
      }

    }
    // end POST check
    ?>
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
function audio_to_song_post($list_of_ids, $folderPath, $urlPath, $post_type, $posting_mode, $autoplay_mode, $date_mode, $title_mode, $description_mode, $tags_mode, $artist_mode, $subgenre_mode) {
  $messages = array();

  // check of there are files to process
  if(count($list_of_ids) == 0){
    array_push($messages, _e('There are no files to process', 'media-pro'));
    return $messages;
  } else {


      $master_list = array();

      // Initialize getID3 engine
      $getID3 = new getID3;

      $mp3Files_array = explode(',', $list_of_ids); //split string into array seperated by ', '
        foreach($mp3Files_array as $song_id) //loop over and extract values
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

              // allow user to select Title - Artist for format of playlist (for compilations etc)

              //ISRC

              $title = $ThisFileInfo['tags_html']['id3v2']['title'][0];
              $album = $ThisFileInfo['tags_html']['id3v2']['album'][0];

              $artist = $ThisFileInfo['tags_html']['id3v2']['artist'][0];
              $album_artist = $ThisFileInfo['tags_html']['id3v2']['band'][0];
              $category = $ThisFileInfo['tags_html']['id3v2']['genre'][0];
              $description = $ThisFileInfo['tags_html']['id3v2']['subtitle'][0];
              $bpm = $ThisFileInfo['tags_html']['id3v2']['bpm'][0];
              $released_year = $ThisFileInfo['tags_html']['id3v2']['year'][0];
              $created_date = $ThisFileInfo['tags_html']['id3v2']['creation_date'][0];
              $composer = $ThisFileInfo['tags_html']['id3v2']['composer'][0];
              $grouping = $ThisFileInfo['tags_html']['id3v2']['content_group_description'][0];

              if (empty($grouping)) {
                $grouping = $ThisFileInfo['tags']['id3v2']['content_group_description'][0];
              }

              if (empty($grouping)) {
                $grouping = $ThisFileInfo['comments']['content_group_description'][0];
              }

              if (empty($grouping)) {
                $grouping = $ThisFileInfo['id3v2']['TT1'][0]['data'];
              }

              $encoded_by = $ThisFileInfo['tags_html']['id3v2']['encoded_by'][0];
              $isrc = $ThisFileInfo['tags_html']['id3v2']['isrc'][0];

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


              // generate playlist data

              if ($post_type == 'post') {

                //rewrite this and move to final logic

                  if ($posting_mode == '1') {
                    $playlist_ids = $song_id;
                  } else {
                    $playlist_ids = $list_of_ids;
                  }

                  $post_playlist = "<p>[playlist ids=" . $playlist_ids . "]</p>";

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

              }

              //remap artist to album artist
              if ($artist_mode == '1') {
                $artist = $album_artist;
              }


              //remap post content field
              if ($description_mode == '1') {
                $post_content = $comments;
              }elseif ($description_mode == '2') {
                $post_content = $description;
              } elseif ($description_mode == '3') {
                $post_content = '<p>"' . $title . '" from "' . $album . '" by ' . $artist . '</p>';

                if (!empty($released_year)) {
                  $post_content = $post_content . '<p>Released: ' . $released_year . '</p>';
                }

                if (!empty($category)) {
                  $post_content = $post_content . '<p>Genre: ' . $category . '</p>';
                }

                if (!empty($bpm)) {
                  $post_content = $post_content . '<p>BPM: ' . $bpm . '</p>';
                }

                if (!empty($isrc)) {
                  $post_content = $post_content . '<p>ISRC: ' . $isrc . '</p>';
                }

              }

              if ($post_type == 'post') {
                // check to see if we need to prepend playlist to post_content
                $post_content = $post_playlist . $post_content;
              }


              //remap post title field
              if ($title_mode == '2') {
                $title = $album;
              } elseif ($title_mode == '3') {
                $title = $title . " - " . $artist;
              }


              //remap post tags
              if ($tags_mode == '0') {
                $post_tags = '';
              } elseif ($tags_mode == '1') {
                $post_tags = $grouping;
              } elseif ($tags_mode == '2') {
                $post_tags = $comment;
              } elseif ($tags_mode == '3') {
                $post_tags = $description;
              }


                $the_song_tags = array(
                  'title' => $title,
                  'artist' => $artist,
                  'album' => $album,
                  'category' => $category,
                  'post_thumbnail_id' => $post_thumbnail_id,
                  'post_type' => $post_type,
                  'post_content' => $post_content,
                  'post_tags' => $post_tags,
                  'the_playlist_array' => $the_playlist_array,
                  'year' => $released_year,
                  'grouping' => $grouping,
                  'bpm' => $bpm,
                  'composer' => $composer,
                  'isrc' => $isrc,
                );



              // check if we have a title
              // proceed to make post if this works
               if ($title){
                   array_push($master_list, $the_song_tags);
               } else {
                 array_push($messages, _e('Title not set in the ID3 information for - ' . $title, 'media-pro'));
               }

        }

          $song_limit = count($master_list);

          if ($posting_mode == '1') {
            // loop array and make a post for each song

            for ($i = 0; $i < $song_limit; $i++) {
              $the_playlist_array_final = array();
              array_push($the_playlist_array_final, $master_list[$i]['the_playlist_array']);

              do_the_posting(
                $master_list[$i]['title'],
                $master_list[$i]['artist'],
                $master_list[$i]['album'],
                $master_list[$i]['category'],
                $master_list[$i]['post_thumbnail_id'],
                $master_list[$i]['post_type'],
                $master_list[$i]['post_content'],
                $master_list[$i]['post_tags'],
                $the_playlist_array_final,
                $autoplay_mode,
                $date_mode,
                $master_list[$i]['year'],
                $subgenre_mode,
                $master_list[$i]['grouping'],
                $master_list[$i]['bpm'],
                $master_list[$i]['composer'],
                $master_list[$i]['isrc']
                );
            }

          } else {

            // make an array to stick all tracks into for remix playlist
            $the_playlist_array_final = array();

            for ($i = 0; $i < $song_limit; $i++) {
              array_push($the_playlist_array_final, $master_list[$i]['the_playlist_array']);
            }

            // compile description
            // normalize lists of tags

            // post first song for now as the album
            do_the_posting(
              $master_list[0]['title'],
              $master_list[0]['artist'],
              $master_list[0]['album'],
              $master_list[0]['category'],
              $master_list[0]['post_thumbnail_id'],
              $master_list[0]['post_type'],
              $master_list[0]['post_content'],
              $master_list[0]['post_tags'],
              $the_playlist_array_final,
              $autoplay_mode,
              $date_mode,
              $master_list[0]['year'],
              $subgenre_mode,
              $master_list[0]['grouping'],
              $master_list[0]['bpm'],
              $master_list[0]['composer'],
              $master_list[0]['isrc']
              );

          }

  }

  // return the messages
  return $messages;
}




function do_the_posting($title, $artist, $album, $category, $post_thumbnail_id, $post_type, $post_content, $post_tags, $the_playlist_array_final, $autoplay_mode, $date_mode, $released_year, $subgenre_mode, $grouping, $bpm, $composer, $isrc) {

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
      'post_content' => $post_content,
      'post_author' => 1,
      'post_name' => $title,
    );
    // Insert the post!!
    $postID = wp_insert_post($my_post);

    // set post type
    set_post_type($postID, $post_type);

    if(!empty($post_tags)){
      //set post tags
      wp_set_post_tags($postID, $post_tags);
    }

    //set featured image
    set_post_thumbnail($postID, $post_thumbnail_id);

    if (($date_mode=='1') && (!empty($released_year))) {
      // try to get Created Date, otherwise use Year

      $postdate = $released_year . '-01-01 00:00:00';

      // Update post status to publish so the date can be reset
      $my_post_status = array(
          'ID'           => $postID,
          'post_status' => 'publish',
      );
      wp_update_post( $my_post_status );

      // Update post date
      $my_post_date = array(
          'ID'           => $postID,
          'post_date' => $postdate
      );
      wp_update_post( $my_post_date );

      // Reset post status to draft
      $my_post_status2 = array(
          'ID'           => $postID,
          'post_status' => 'draft',
      );
      wp_update_post( $my_post_status2 );

    }



    if ($post_type == 'songs') {

      add_post_meta($postID, "playlist", $the_playlist_array_final);
      add_post_meta($postID, "auto_play", $autoplay_mode);


      if(!empty($album)){
        add_post_meta($postID, "album", $album);
      }

      if(!empty($bpm)){
        add_post_meta($postID, "bpm", $bpm);
      }

      if(!empty($composer)){
        add_post_meta($postID, "composer", $composer);
      }

      if(!empty($isrc)){
        add_post_meta($postID, "isrc", $isrc);
      }

      // If the artist is set try to find matching artist page
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

      if(!empty($category)){
        // LOOKUP TAXONOMY TERM FOR GENRE

        $genre_term = term_exists( $category, 'songs_cat' );
        $genre_term_ID = $genre_term['term_id']; // get numeric term id

        // if a category exists
        if($genre_term_ID) {
          $genre_array = array($genre_term_ID);
        }
        // if it doesn't exist then create a new category
        // TODO - make sure that its a word and not an number in parens ie (16)
        else {
          //$new_genre_ID = wp_create_category($category);
          wp_insert_term(
            $category, // the term
            'songs_cat', // the taxonomy
            array(
              'description'=> $category,
              'slug' => sanitize_title($category),
            )
          );

          $new_genre_term = term_exists( $category, 'songs_cat' );
          $genre_term_ID = $new_genre_term['term_id']; // get numeric term id

          $genre_array = array($genre_term_ID);
        }
        wp_set_post_terms( $postID, $genre_array, 'songs_cat' );

        // set subgenres if selected
         if (($subgenre_mode=='1') && (!empty($grouping))) {

           $subgenres_array = explode(',', $grouping); //split string into array seperated by ', '
             foreach($subgenres_array as $grouping_id) //loop over and extract values
             {

               $subgenre_term = term_exists( $grouping_id, 'songs_cat', $genre_term_ID );
               $subgenre_term_ID = $subgenre_term['term_id']; // get numeric term id

               // if a category exists
               if($subgenre_term_ID) {
                 $subgenre_array = array($subgenre_term_ID);
               }
               // if it doesn't exist then create a new category
               // TODO - make sure that its a word and not an number in parens ie (16)
               else {
                 //$new_genre_ID = wp_create_category($category);
                 wp_insert_term(
                   $grouping_id, // the term
                   'songs_cat', // the taxonomy
                   array(
                     'description'=> $grouping_id,
                     'slug' => sanitize_title($grouping_id),
                     'parent'=> $genre_term_ID,
                   )
                 );

                 $new_subgenre_term = term_exists( $grouping_id, 'songs_cat', $genre_term_ID );
                 $subgenre_term_ID = $new_subgenre_term['term_id']; // get numeric term id

                 $subgenre_array = array($subgenre_term_ID);
               }
               wp_set_post_terms( $postID, $subgenre_array, 'songs_cat', true );
             }

         }

      }

    } else {
      // If the category is set then update the post
      if(!empty($category)){
        $category_ID = get_cat_ID($category);
        // if a category exists
        if($category_ID) {
          $categories_array = array($category_ID);
        }
        // if it doesn't exist then create a new category
        // TODO - make sure that its a word and not an number in parens ie (16)
        else {
          $new_category_ID = wp_create_category($category);
          $categories_array = array($new_category_ID);
        }
        wp_set_post_categories($postID, $categories_array);
      }

    }

    array_push($messages, _e('<p>' . $post_type . ' created: '  . $title . '</p>', 'media-pro'));
  } else {
    array_push($messages, _e('<p>' . $post_type . ' already exists: ' . $title . '</p>', 'media-pro'));
  }

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

add_action('admin_enqueue_scripts', 'mediapro_admin_scripts');

function mediapro_admin_scripts() {
    if (isset($_GET['page']) && $_GET['page'] == 'media-pro') {
        wp_enqueue_media();
        wp_register_script('media-pro-admin-js', WP_PLUGIN_URL.'/MediaPro/media-pro-admin.js', array('jquery'));
        wp_enqueue_script('media-pro-admin-js');
    }
}
?>
