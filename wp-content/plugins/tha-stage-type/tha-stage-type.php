<?php
/*
Plugin Name: Tha stage Type
Plugin URI: http://www.webisti.cz
Description: Declares a plugin that will create a custom post type displaying stages. Within the stage's edit screen, admin can quick-add events and artists for the stage, which get generated on the fly.
Version: 1.0
Author: Fanky
Author URI: http://www.webisti.cz
License: GPLv2
*/


$timezone = 'Europe/Prague';
date_default_timezone_set($timezone);

function enqueue_tha_stages_adminstuff()
{

    //datepicker js
    wp_enqueue_script('tha_field_date', plugins_url('/js/field-date.js', __FILE__), array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'), '1.0');

    //stageadmin js
    wp_enqueue_script('tha_stageadmin', plugins_url('/js/stageadmin.js', __FILE__), array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'tha_field_date'), '1.0');

    wp_enqueue_style('wp-jquery-ui-datepicker');
    wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
    wp_enqueue_style('thickbox');
    wp_enqueue_script('thickbox');


    //styling post.php edit page

    wp_enqueue_style('thaeventedit-style', plugins_url('/css/thaeventedit.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . "/css/thaeventedit.css"));
}

add_action('admin_enqueue_scripts', 'enqueue_tha_stages_adminstuff');


// create the custom post type
function thastagetype_create_tha_stage()
{
    register_post_type(
        'tha_stages',
        array(
            'labels' => array(
                'name' => 'Tha Stages',
                'singular_name' => 'Tha Stage',
                'add_new' => 'Add New',
                'add_new_item' => 'Add Tha New Stage',
                'edit' => 'Edit',
                'edit_item' => 'Edit Tha Stage',
                'new_item' => 'Make Tha New Stage',
                'view' => 'View',
                'view_item' => 'View Tha Stage',
                'search_items' => 'Search Tha Stages',
                'not_found' => 'No Stages found',
                'not_found_in_trash' => 'No Stages found in Trash',
                'parent' => 'Tha Parent Stage'
            ),

            'public' => true,
            'hierarchical' => true,
            'rewrite' => true,
            'menu_position' => 4,
            'register_meta_box_cb' => 'thastagetype_add_stages_metaboxes',
            //'rewrite' => array('slug' => 'stages','with_front' => false),
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'taxonomies' => array('category'),
            'menu_icon' => plugins_url('img/stages.png', __FILE__),
            'has_archive' => true
        )
    );
}

add_action('init', 'thastagetype_create_tha_stage');


// Add stages Meta Boxes

function thastagetype_add_stages_metaboxes()
{
    global $post;
    add_meta_box('thageo', 'GPS (lat,long) (e.g. "50.0598058,14.3255398") <a href="https://www.latlong.net/" target="_blank">PICK GPS HERE</a>', 'thastagetype_addbox', 'tha_stages', 'normal', 'low', array('name' => 'thageo'));

    add_meta_box('treebox', 'Vystoupení', 'showtree', 'tha_stages', 'normal', 'high', array('stageid' => $post->ID));
}

// The stage Metabox

function thastagetype_addbox($post, $metabox)
{


    // Noncename needed to verify where the data originated
    echo '<input type="hidden" name="stagemeta_noncename" id="stagemeta_noncename" value="' .
        wp_create_nonce(plugin_basename(__FILE__)) . '" />';

    // Get the location data if its already been entered
    $entered = get_post_meta($post->ID, $metabox['args']['name'], true);

    // Echo out the field

    // if($metabox['args']['name']=="thadate") {
    // if($entered=="") $entered=time();
    // }

    echo '<input type="text" name="' . $metabox['args']['name'] . '" value="' . $entered . '" class="widefat" />';



}


// Save the Metabox Data

function thastagetype_wpt_save_stages_meta($post_id, $post)
{



    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times

    if (!isset($_POST['stagemeta_noncename']) || !wp_verify_nonce($_POST['stagemeta_noncename'], plugin_basename(__FILE__))) {
        return $post->ID;
    }
    // Is the user allowed to edit the post or page?
    if (!current_user_can('edit_post', $post->ID))
        return $post->ID;

    // OK, we're authenticated: we need to find and save the data
    // We'll put it into an array to make it easier to loop though	

    // Add values of stages meta as custom fields
    foreach ($_POST as $key => $value) { // Cycle through the stages meta array!

        if ($post->post_type == 'revision')
            return; // Don't store custom data twice
        $value = implode(',', (array) $value); // If $value is an array, make it a CSV (unlikely)
        if (get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value



            update_post_meta($post->ID, $key, $value);
        } else { // If the custom field doesn't have a value
            add_post_meta($post->ID, $key, $value);
        }

        if (!$value)
            delete_post_meta($post->ID, $key); // Delete if blank

    }

}

add_action('save_post', 'thastagetype_wpt_save_stages_meta', 1, 2); // save the custom fields









//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//events list
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////

//helper validator function

/* Valid Examples: */
// isValidDate("2017-05-31");
// isValidDate("23:15:00", 'H:i:s');
// isValidDate("2017-05-31 11:15:00", 'Y-m-d h:i:s');

/* Invalid: */
// isValidDate("2012-00-21");
// isValidDate("25:15:00", 'H:i:s');
// isValidDate("Any string that's not a valid date/time");
function isValidDate($date, $format = 'j. m. Y')
{
    $dateObj = DateTime::createFromFormat($format, $date);
    return $dateObj && $dateObj->format($format) == $date;
}

function showtree($post, $metabox)
{
    $stageid = $metabox["args"]["stageid"];
    $args = array(
        'posts_per_page' => -1,
        'post_type' => array('tha_events'),
        //Sort on meta value but include posts that don't have one
        'meta_query' => array(
            'relation' => 'AND',
            'metastageid' => array(
                'key' => 'thastageid',
                'value' => $stageid
            ),
            'metastartdate' => array(
                'key' => 'thadate',
                'compare' => 'EXISTS'
            ),
        ),
        'orderby' => array(
            'metastartdate' => 'ASC'
        ),
        'post_status' => array("publish", "private", "draft")
    );
    $daynames = array("ne", "po", "út", "st", "čt", "pá", "so");
    ?>
    <div id='subtree'>
        <div class="stagecont" data-stageid="<?php echo $stageid; ?>">
            <div id="stagehead"><!--
        -->
                <div>start</div><!--
        -->
                <div>konec</div><!--
        -->
                <div>umělec</div><!--
        -->
                <div>žánr/podtitul</div><!--
        -->
                <div>public</div><!--
        -->
                <div>headliner</div><!--
-->
            </div>
            <div class="stageevents">
                <?php
                foreach (get_posts($args) as $v) {

                    if ($v->thaartistid) {
                        $a = get_post((int) $v->thaartistid);
                        $image = get_post_thumbnail_id($v->thaartistid);
                    } else
                        $a = NULL;
                    ?>
                    <div class="eventspace<?php if ($v->thaartistid)
                        echo " knownartist"; ?>" id="<?php echo $v->ID; ?>">
                        <div class="eventrow">
                            <?php
                            echo '<div class="calendarcont"><a title="start date"><input disabled size="10" class="datepicker" type="text" name="thanew_datepart" value="' . date(get_option('date_format'), $v->thadate) . '" /><input class="dayname" type="text" disabled value="' . $daynames[date("w", $v->thadate)] . '"></a></div><input disabled size="5" placeholder="00:00" maxlength="5" type="text" name="thanew_timepart" value="' . date("H:i", $v->thadate) . '" /><div class="calendarcont"><a title="end date"><input disabled size="10" class="datepicker" type="text" name="thanew_end_datepart" value="' . date(get_option('date_format'), $v->thaenddate) . '" /><input class="dayname" type="text" disabled value="' . $daynames[date("w", $v->thaenddate)] . '"></a></div><input disabled size="5" placeholder="00:00" maxlength="5" type="text" name="thanew_end_timepart" value="' . date("H:i", $v->thaenddate) . '" />';
                            ?>
                            <div class="livesearch_space">
                                <div class="livesearch_cont"><input disabled autocomplete="off" class="searchartist artistinput"
                                        name="searchartist" placeholder="začněte psát..." type="text"
                                        value="<?php echo $a->post_title; ?>" /><input disabled class="artistid" name="artistid"
                                        type="hidden" value="<?php echo $v->thaartistid; ?>" />
                                    <div class="livesearch"></div>
                                </div>
                            </div><!--
                    --><input disabled name="subtitle" class="artistinput" size="80" placeholder='žánr/podtitul' type="text"
                                value="<?php echo $a->subtitle; ?>"><!--
                    --><span class="indicators"><a title="chose artist image"
                                    class="iconcontrol ic_image<?php if ($image)
                                        echo " urlset"; ?>"><?php include("img/img.svg"); ?></a><!--
                    --><a title="single video url"
                                    class="iconcontrol ic_video<?php if ($a->video)
                                        echo " urlset"; ?> socialcolapse enabled"><?php include("img/vid.svg"); ?></a><!--
                    --><a title="artist fb page"
                                    class="iconcontrol ic_fbpage<?php if ($a->fbpage)
                                        echo " urlset"; ?> socialcolapse enabled"><?php include("img/fb.svg"); ?></a><!--
                    --><a title="artist website"
                                    class="iconcontrol ic_website<?php if ($a->website)
                                        echo " urlset"; ?> socialcolapse enabled"><?php include("img/globe.svg"); ?></a></span><!--
                    --><input disabled type="checkbox" value="on" name="public" <?php if ($v->post_status == "publish")
                        echo ' checked="checked"'; ?> /><!--
                    --><input disabled type="checkbox" value="on" name="headliner" <?php if ($a->headliner == "on")
                        echo ' checked="checked"'; ?> /><!--
                    --><a title="delete event" class="iconcontrol ic_delete thaeventdelete">
                                <?php include("img/delete.svg"); ?>
                            </a><a title="expand row" class="iconcontrol ic_edit enabled">
                                <?php include("img/edit.svg"); ?>
                            </a>
                        </div>
                        <div class="colapsearea">
                            <div class="eventrow">
                                <a title="single video url" class="iconcontrol ic_video">
                                    <?php include("img/vid.svg"); ?>
                                </a>
                                <input disabled class="urlinput artistinput" name="video" size="80"
                                    placeholder='https://www.youtube.com/watch?v=EZ0KJ5uTlLk' type="text"
                                    value="<?php echo $a->video; ?>">
                                <a title="artist fb page" class="iconcontrol ic_fbpage">
                                    <?php include("img/fb.svg"); ?>
                                </a>
                                <input disabled class="urlinput artistinput" name="fbpage" size="80"
                                    placeholder='https://fb.me/veprovekomety' type="text" value="<?php echo $a->fbpage; ?>">
                                <a title="artist website" class="iconcontrol ic_website">
                                    <?php include("img/globe.svg"); ?>
                                </a>
                                <input disabled class="urlinput artistinput" name="website" size="80"
                                    placeholder='https://www.veprovekomety.cz' type="text" value="<?php echo $a->website; ?>">
                                <input type="hidden" name="image" value="<?php echo $image; ?>">
                            </div>
                            <div class="eventrow">
                                <textarea name="thanew_artistdesc" class="artistinput"
                                    placeholder='popis umělce'><?php echo wp_strip_all_tags($a->post_content); ?></textarea>
                            </div>
                            <div class="eventrow buttonsrow">
                                <button class="thaeventdelete" href="#">Smazat událost</button>
                                <button class="thaeventreset" href="#">Zahodit změny</button>
                                <button class="thaeventquickedit thaeventsaver" href="#">Uložit změny</button>
                            </div>
                        </div>
                    </div>
                    <?php
                    $last_v = $v;
                }





                ?>
            </div><!--stageevents-->


            <div class="eventspace spaceopen" id="adder">
                <div class="eventrow">
                    <?php
                    echo '<div class="calendarcont';
                    if (!$last_v)
                        echo ' unset';
                    echo '"><a title="start date"><input disabled size="10" class="datepicker" type="text" name="thanew_datepart" value="';
                    if ($last_v)
                        echo date(get_option('date_format'), $last_v->thaenddate);
                    echo '" /><input class="dayname" type="text" disabled value="';
                    if ($last_v)
                        echo $daynames[date('w', $last_v->thaenddate)];
                    echo '"></a></div><input size="5" placeholder="00:00" maxlength="5" type="text" name="thanew_timepart" value="';
                    if ($last_v)
                        echo date('H:i', $last_v->thaenddate);
                    echo '" /><div class="calendarcont';
                    if (!$last_v)
                        echo ' unset';
                    echo '"><a title="end date"><input disabled size="10" class="datepicker" type="text" name="thanew_end_datepart" value="';
                    if ($last_v)
                        echo date(get_option('date_format'), $last_v->thaenddate);
                    echo '" /><input class="dayname" type="text" disabled value="';
                    if ($last_v)
                        echo $daynames[date('w', $last_v->thaenddate)];
                    echo '"></a></div><input size="5" placeholder="00:00" maxlength="5" type="text" name="thanew_end_timepart" value="" />';
                    ?>
                    <div class="livesearch_space">
                        <div class="livesearch_cont"><input autocomplete="off" class="searchartist artistinput"
                                name="searchartist" placeholder="začněte psát..." type="text" value="" /><input
                                class="artistid" name="artistid" type="hidden" value="" />
                            <div class="livesearch"></div>
                        </div>
                    </div><!--
                    --><input name="subtitle" class="artistinput" size="80" placeholder='žánr/podtitul' type="text" value=""><!--
                    --><span class="indicators"><a title="chose artist image" class="iconcontrol ic_image">
                            <?php include("img/img.svg"); ?>
                        </a><!--
                    --><a title="single video url" class="iconcontrol ic_video socialcolapse">
                            <?php include("img/vid.svg"); ?>
                        </a><!--
                    --><a title="artist fb page" class="iconcontrol ic_fbpage socialcolapse">
                            <?php include("img/fb.svg"); ?>
                        </a><!--
                    --><a title="artist website" class="iconcontrol ic_website socialcolapse">
                            <?php include("img/globe.svg"); ?>
                        </a></span><!--
                    
                    DEFAULT SAVING AS PUBLIC
                    
                    --><input type="checkbox" value="on" name="public" checked="checked" /><!--
                    --><input type="checkbox" value="on" name="headliner" /><!--
                    --><a title="expand row" class="iconcontrol ic_edit enabled">
                        <?php include("img/edit.svg"); ?>
                    </a>
                </div>
                <div class="colapsearea">
                    <div class="eventrow">
                        <a title="single video url" class="iconcontrol ic_video">
                            <?php include("img/vid.svg"); ?>
                        </a>
                        <input class="urlinput artistinput" name="video" size="80"
                            placeholder='https://www.youtube.com/watch?v=EZ0KJ5uTlLk' type="text" value="">
                        <a title="artist fb page" class="iconcontrol ic_fbpage">
                            <?php include("img/fb.svg"); ?>
                        </a>
                        <input class="urlinput artistinput" name="fbpage" size="80"
                            placeholder='https://fb.me/veprovekomety' type="text" value="">
                        <a title="artist website" class="iconcontrol ic_website">
                            <?php include("img/globe.svg"); ?>
                        </a>
                        <input class="urlinput artistinput" name="website" size="80"
                            placeholder='https://www.veprovekomety.cz' type="text" value="">
                        <input type="hidden" name="image" value="">
                    </div>
                    <div class="eventrow">
                        <textarea name="thanew_artistdesc" class="artistinput" placeholder='popis umělce'></textarea>
                    </div>
                    <div class="eventrow buttonsrow">
                        <!--<button href="#">Zahodit změny</button>-->
                        <button class="thaeventreset" href="#">Zahodit změny</button>
                        <button class="thanewadder thaeventsaver" disabled href="#">Vytvořit</button>
                    </div>
                </div>
            </div>
        </div><!--stagecont-->
        <?php

        ?>
    </div>


<?php
}



//included in adding and in creating of events
function add_or_update_artist_from_post()
{


    //if artist has no thumbnail but has fbpage, get fb page profile picture and make it thumbnail
    if ($_POST["thanew_artistfbpage"] && !has_post_thumbnail($_POST["thanewartistid"]) && !$_POST["thanew_artistimage"]) {
        $fbslug = str_replace("https://", "", $_POST["thanew_artistfbpage"]);
        $fbslug = str_replace("http://", "", $fbslug);
        $fbslugarr = explode("/", $fbslug);
        $fbslug = $fbslugarr[1];
        $URI = "https://graph.facebook.com/" . $fbslug . "/picture?type=large";


        $cookie_path = 'cookie.txt';
        if (defined('COOKIE_PATH_FOR_CURL') && !empty(COOKIE_PATH_FOR_CURL)) {
            $cookie_path = COOKIE_PATH_FOR_CURL;
        }
        $curl = curl_init($URI);

        $headers = [];

        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$headers) {
            $matches = array();

            if (preg_match('/^([^:]+)\s*:\s*([^\x0D\x0A]*)\x0D?\x0A?$/', $header, $matches)) {
                $headers[$matches[1]][] = $matches[2];
            }

            return strlen($header);
        });

        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_HOST'] . "/1.0");
        // START SIZE LIMIT
        // We need progress updates to break the connection mid-way
        curl_setopt($curl, CURLOPT_BUFFERSIZE, 128); // more progress info
        curl_setopt($curl, CURLOPT_NOPROGRESS, false);
        curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, function ($DownloadSize, $Downloaded, $UploadSize, $Uploaded) {
            // If $Downloaded exceeds 5MB, returning non-0 breaks the connection!
            return ($Downloaded > (5000 * 1024)) ? 1 : 0;
        });
        // END SIZE LIMIT

        //The following 2 set up lines work with sites like www.nytimes.com
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_path); //you can change this path to whetever you want.
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_path); //you can change this path to whetever you want.

        $response = mb_convert_encoding(curl_exec($curl), 'HTML-ENTITIES', 'UTF-8');
        curl_close($curl);
        $last_url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

        if (!empty($response)) {
            $fbimage = $headers["Location"][0];
        }

        //upload and attach to post
        $fbmediaid = media_sideload_image($fbimage, $_POST["thanewartistid"], "from fb", "id");
        //set the attached media as thumbnail
        set_post_thumbnail($_POST["thanewartistid"], $fbmediaid);

    }

    if ($_POST["thanewartistid"] && $_POST["thanewartistid"] != "") {
        //hook to update artist
        $artistid = (int) $_POST["thanewartistid"];
        $updated_artist_id = wp_update_post(
            array(
                "ID" => $_POST["thanewartistid"],
                "post_content" => (string) $_POST["thanew_artistdesc"],
                "meta_input" => array(
                    "headliner" => $_POST["thanew_artistheadliner"],
                    "subtitle" => $_POST["thanew_artistsubtitle"],
                    "video" => $_POST["thanew_artistvideo"],
                    "fbpage" => $_POST["thanew_artistfbpage"],
                    "fbimage" => $fbimage,
                    "website" => $_POST["thanew_artistwebsite"]
                )
            )
        );
        error_log("updated artist:" . $_POST["thanewartistid"]);
        set_post_thumbnail($_POST["thanewartistid"], $_POST["thanew_artistimage"]);
        // error_log("thumbnail set");
    } elseif ($_POST["thanew_artistname"] && $_POST["thanew_artistname"] != "") {
        //hook to add artist

        $added_artist_id = wp_insert_post(
            array(
                "post_type" => "tha_artists",
                "post_status" => "publish",
                "post_title" => (string) $_POST["thanew_artistname"],
                "post_content" => (string) $_POST["thanew_artistdesc"],
                "meta_input" => array(
                    "headliner" => $_POST["thanew_artistheadliner"],
                    "subtitle" => $_POST["thanew_artistsubtitle"],
                    "video" => $_POST["thanew_artistvideo"],
                    "fbpage" => $_POST["thanew_artistfbpage"],
                    "website" => $_POST["thanew_artistwebsite"]
                )
            )
        );
        $artistid = $added_artist_id;
        // error_log("created artist $added_artist_id");
        set_post_thumbnail($added_artist_id, $_POST["thanew_artistimage"]);
    } else
        $artistid = 0;
    return $artistid;
}



add_action('wp_ajax_quick_add_tha_event', 'quick_add_tha_event');

function quick_add_tha_event()
{
    global $wpdb; // this is how you get access to the database
    global $timezone;

    $thanew_datepart = str_replace(" ", "", $_POST["thanew_datepart"]);

    if (isValidDate($thanew_datepart, get_option('date_format'))) {

        //if invalid time, use only datepart
        if ($_POST["thanew_timepart"] && isValidDate($_POST["thanew_timepart"], 'H:i'))
            $value = $thanew_datepart . " " . $_POST["thanew_timepart"];
        else
            $value = $thanew_datepart;

        //store unix timestamp considering timezone
        $thadate = strtotime($value . " " . $timezone);
    }

    $thanew_end_datepart = str_replace(" ", "", $_POST["thanew_end_datepart"]);
    if (isValidDate($_POST["thanew_end_datepart"], get_option('date_format'))) {
        //if invalid time, use only datepart
        if ($_POST["thanew_end_timepart"] && isValidDate($_POST["thanew_end_timepart"], 'H:i'))
            $value = $thanew_end_datepart . " " . $_POST["thanew_end_timepart"];
        else
            $value = $thanew_end_datepart;

        //store unix timestamp considering timezone
        $thaenddate = strtotime($value . " " . $timezone);
    }
    if (!$thadate) {
        echo "Neplatné datum začátku!\n";
    }
    if (!$thaenddate) {
        echo "Neplatné datum konce!\n";
    }
    if ($thadate && $thaenddate && $thaenddate < $thadate) {
        echo "Událost nesmí končit dříve, než začne ;) \n";
    } elseif ($thadate && $thaenddate) {

        if ($_POST["thanewpublishevent"] == "on")
            $status = "publish";
        else
            $status = "private";

        if ($_POST["thaneweventstage"]) {

            $newid = wp_insert_post(
                array(
                    "post_type" => "tha_events",
                    "post_status" => $status,
                    "meta_input" => array(
                        "thadate" => $thadate,
                        "thaenddate" => $thaenddate,
                        "thastageid" => $_POST["thaneweventstage"],
                        "thaartistid" => add_or_update_artist_from_post()
                    )
                )
            );
            if ($newid)
                echo "saved";
        } else
            echo 0;
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_quick_edit_tha_event', 'quick_edit_tha_event');




function quick_edit_tha_event()
{
    // error_log("quick editing");
    global $wpdb; // this is how you get access to the database
    global $timezone;

    $thanew_datepart = str_replace(" ", "", $_POST["thanew_datepart"]);
    if (isValidDate($thanew_datepart, get_option('date_format'))) {
        //if invalid time, use only datepart
        if ($_POST["thanew_timepart"] && isValidDate($_POST["thanew_timepart"], 'H:i'))
            $value = $thanew_datepart . " " . $_POST["thanew_timepart"];
        else
            $value = $thanew_datepart;

        //store unix timestamp considering timezone
        $thadate = strtotime($value . " " . $timezone);
    }

    $thanew_end_datepart = str_replace(" ", "", $_POST["thanew_end_datepart"]);
    if (isValidDate($_POST["thanew_end_datepart"], get_option('date_format'))) {
        //if invalid time, use only datepart
        if ($_POST["thanew_end_timepart"] && isValidDate($_POST["thanew_end_timepart"], 'H:i'))
            $value = $thanew_end_datepart . " " . $_POST["thanew_end_timepart"];
        else
            $value = $thanew_end_datepart;

        //store unix timestamp considering timezone
        $thaenddate = strtotime($value . " " . $timezone);
    }
    if (!$thadate) {
        echo "Neplatné datum začátku!\n";
    }
    if (!$thaenddate) {
        echo "Neplatné datum konce!\n";
    }
    if ($thadate && $thaenddate && $thaenddate < $thadate) {
        echo "Událost nesmí končit dříve, než začne ;) \n";
    } elseif ($thadate && $thaenddate) {

        // error_log("time is propper");

        if ($_POST["thanewpublishevent"] == "on")
            $status = "publish";
        else
            $status = "private";

        if ($_POST["id"]) {

            $updated_event_id = wp_update_post(
                array(
                    "ID" => $_POST["id"],
                    "post_status" => $status,
                    "meta_input" => array(
                        "thadate" => $thadate,
                        "thaenddate" => $thaenddate,
                        "thaartistid" => add_or_update_artist_from_post()
                    )
                )
            );
            error_log("updated event" . $updated_event_id);
            if ($updated_event_id)
                echo "saved";
            // error_log("saved");
        } else
            echo 0;

    }
    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_quick_delete_tha_event', 'quick_delete_tha_event');

function quick_delete_tha_event()
{
    global $wpdb; // this is how you get access to the database

    if ($_POST["id"]) {
        $trashed_post_data = wp_trash_post($_POST["id"]);
        if ($trashed_post_data == null || $trashed_post_data == false)
            echo "Nepodařilo se smazat událost.";
        else
            echo "deleted";
    } else
        echo 0;

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_refresh_tree', 'refresh_tree');

function refresh_tree()
{
    error_log("refreshing tree");
    $metabox["args"]["stageid"] = $_POST["stageid"];
    showtree($_POST["stageid"], $metabox);
    error_log("tree shown");
    wp_die(); // this is required to terminate immediately and return a proper response
}



/***********************/
/***********************/
/** LIVE SEARCH ********/
/***********************/
/***********************/



/*post title starts with helper for livesearch*/

function wpse_298888_posts_where($where, $query)
{
    global $wpdb;

    $searchstring = $query->get('searchstring');

    if ($searchstring) {
        /*any of words starts with searchstring*/
        $where .= " AND ($wpdb->posts.post_title LIKE '$searchstring%' OR $wpdb->posts.post_title LIKE '% $searchstring%')";
    }

    return $where;
}
add_filter('posts_where', 'wpse_298888_posts_where', 10, 2);



add_action('wp_ajax_livesearch_artists', 'livesearch_artists');

function livesearch_artists()
{
    if ($_POST["searchartist"] == "") {
        echo 0;
    } elseif ($_POST["searchartist"]) {
        global $wpdb; // this is how you get access to the database


        $args = array(
            'post_type' => 'tha_artists',
            'searchstring' => $_POST["searchartist"]
        );
        $the_query = new WP_Query($args);

        // The Loop
        if ($the_query->have_posts()) {
            while ($the_query->have_posts()) {
                $the_query->the_post();
                $id = get_the_ID();
                $outputarr[$id]["title"] = get_the_title();
                $outputarr[$id]["subtitle"] = get_post_meta(get_the_ID(), "subtitle", true);
                $outputarr[$id]["headliner"] = get_post_meta(get_the_ID(), "headliner", true);
                $outputarr[$id]["video"] = get_post_meta(get_the_ID(), "video", true);
                $outputarr[$id]["fbpage"] = get_post_meta(get_the_ID(), "fbpage", true);
                $outputarr[$id]["website"] = get_post_meta(get_the_ID(), "website", true);
                // trouble: on click on image icon the current will not be preset
                $outputarr[$id]["image"] = get_post_thumbnail_id(get_the_ID());
                $outputarr[$id]["desc"] = get_the_content();
                $outputarr[$id]["desc"] = wp_strip_all_tags($outputarr[$id]["desc"]);
                // if($firstwas) echo "\n"; else $firstwas=true;//newline necessary for splitting to compare strings
                // echo "<a href='#' data-id='".get_the_ID()."' data-subtitle='".get_post_meta(get_the_ID(),"subtitle",true)."'>".get_the_title()."</a>"; 
            }
            echo json_encode($outputarr);
        } else
            echo "new";

    } else
        echo 0;

    wp_die(); // this is required to terminate immediately and return a proper response
}








// add custom data to post's REST API


function tha_stages_get_post_meta_for_api($object, $attr)
{
    //get the id of the post object array
    $post_id = $object['id'];
    $fields = get_post($post_id);
    $metafields = get_post_meta($post_id);
    return array(
        "thageo" => $metafields["thageo"][0]
    );


    return get_post_meta($post_id);
}

function tha_stages_create_api_posts_meta_field()
{
    // register_rest_field ( 'name-of-post-type', 'name-of-field-to-return', array-of-callbacks-and-schema() )
    register_rest_field('tha_stages', 'thajson', array('get_callback' => 'tha_stages_get_post_meta_for_api', 'schema' => null));
}

add_action('rest_api_init', 'tha_stages_create_api_posts_meta_field');


// add_filter( 'wpsso_json_data_https_schema_org_city',
// 'thastagetype_filter_json_data_stage', 10, 5 );

// //edit JSON-LD output
// function thastagetype_filter_json_data_stage( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {
// if ( $is_main && $mod['is_post'] && $mod['id'] ) {
// //need to overwrite  generated description, as it generates characters like &iacute; not readable e.g. in firebase
// $json_data['description'] = (string) get_post_field('post_content', $mod['id']);
// $latlong=explode(",", get_post_field('thageo', $mod['id']));

// $json_data['geo']=array("@type"=>"GeoCoordinates","latitude"=>$latlong[0], "longitude"=>$latlong[1]);
// }
// return $json_data;
// }


/**
 * The filter is named rest_{post_type}_collection_params. So you need to hook a new filter for each 
 * of the custom post types you need to sort.
 * @link https://www.timrosswebdevelopment.com/wordpress-rest-api-post-order/
 */

// And this for a custom post type called 'tha_stages'
add_filter('rest_tha_stages_collection_params', 'filter_add_rest_orderby_params', 10, 1);

/**
 * Add menu_order to the list of permitted orderby values
 */
function filter_add_rest_orderby_params($params)
{
    $params['orderby']['enum'][] = 'menu_order';
    return $params;
}

?>