<?php
/*
Plugin Name: HyperCheckList
Plugin URI: 
Description: Create checklists in WordPress and choose users who can use that checklist. You can also add checklist groups for common list items.
Author: Johan Ahlbäck @ Hypernode
Version: 0.9.3
Author URI: http://www.hypernode.se
*/


/**
 * HyperCheckList
 * The main class
 * @todo add support for non js
 */
class HyperCheckList
{
    //the dir path to this file
    protected $dir = '';
    
    /**
     * Call hooks
     */
    function __construct()
    {
        $this->dir = WP_PLUGIN_URL . '/' . str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) );
        
        add_action( 'pre_get_posts', array(
             $this,
            'display_lists' 
        ) );
        
        add_action( 'wp_enqueue_scripts', array(
             $this,
            'add_my_stylesheet' 
        ) );
        add_action( 'wp_enqueue_scripts', array(
             $this,
            'add_scripts' 
        ) );
        add_action( 'wp_ajax_hcl_set_status', array(
             $this,
            'set_status' 
        ) );
        add_action( 'wp_ajax_hcl_check_status', array(
             $this,
            'check_changed' 
        ) );
        add_action( 'loop_start', array(
             $this,
            'block_user' 
        ) );
        add_action( 'the_content', array(
             $this,
            'change_content' 
        ) );
        
    }
    
    /**
     * Display all list on a page
     *
     * @param querystring
     * @global $current_user
     * @return querystring
     */
    function display_lists( $query )
    {
        global $current_user;
        get_currentuserinfo();
        if ( is_page( get_option( 'hclpage' ) ) && is_main_query() ) {
            $args = array(
                 'post_type' => 'checklist',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'OR',
                     array(
                         'key' => 'hcllistusers',
                        'value' => $current_user->user_login,
                        'compare' => 'LIKE' 
                    ),
                    array(
                         'key' => 'hclitemusers',
                        'value' => $current_user->user_login,
                        'compare' => 'LIKE' 
                    ) 
                ) 
            );
            $query->query( $args );
            return $query;
        } //is_page( get_option( 'hclpage' ) ) && is_main_query()
        return $query;
    }
    
    /**
     * Add the checklist to the_content
     * 
     * @param string. The content
     * @global object. The post object
     * @return string. The checklist or a message
     */
    function change_content( $content )
    {
        global $post;
        if ( !is_single() )
            return $content;
        if ( $this->block_user() ) {
            return $this->print_checklist();
        } //$this->block_user()
        else {
            return '<h3>You can not see this list</h3>';
        }
        
        
    }
    
    /**
     * The html for a checklist
     * @todo add a hidden field for current status to check if changed
     * @global $post. the post object
     * @global $wpdb
     * @return string. the html for a list
     */
    function print_checklist()
    {
        global $post, $wpdb, $current_user;
        $html  = '';
        $table = $wpdb->prefix . 'hcl_listitems';
        $items = $wpdb->get_results( "SELECT * FROM $table WHERE list_id = $post->ID ORDER BY listorder ASC" );
        $html .= '<form action="' . get_permalink() . '" method="post" id="hcllist">';
        $meta = unserialize( get_post_meta( $post->ID, 'hcllistusers', true ) );
        foreach ( $items as $key => $item ) {
            $pending = '';
            $done    = '';
            $error   = '';
            $status  = '';
            if ( $item->status == 0 ) {
                $status  = 'pending';
                $pending = 'checked="checked"';
            } //$value->status == 0
            if ( $item->status == 1 ) {
                $status = 'done';
                $done   = 'checked="checked"';
            } //$value->status == 1
            if ( $item->status == 2 ) {
                $status = 'error';
                $error  = 'checked="checked"';
            } //$value->status == 2
            
            $html .= '<div class="hclitem ' . $status . '">';
            $html .= '<div class="hclinfo">';
            $html .= '<h2>' . $item->name . '</h2>';
            $html .= '<p>' . $item->description . '</p>';
            $html .= '<p class="lastchanged">' . __( 'Last changed', 'hyperchecklist' ) . ': ' . $item->lastchanged . ' ' . __( 'by', 'hyperchecklist' ) . ': ' . $item->lastchangedby . '</p>';
            $html .= '</div>';
            $html .= '<div class="hclstatus">';
            if (in_array($current_user->user_login,$meta) || $item->users == $current_user->user_login) {
                 $html .= '<label>';
                $html .= '<input type="radio" name="listitem' . $item->id . '" class="error" value="0" ' . $pending . '/>';
                $html .= __( 'Pending', 'hyperchecklist' );
                $html .= '</label>';
                $html .= '<label>';
                $html .= '<input type="radio" name="listitem' . $item->id . '" class="done" value="1" ' . $done . '/>';
                $html .= __( 'Done', 'hyperchecklist' );
                $html .= '</label>';
                $html .= '<label>';
                $html .= '<input type="radio" name="listitem' . $item->id . '" class="error" value="2" ' . $error . '/>';
                $html .= __( 'Error', 'hyperchecklist' );
                $html .= '</label>';
                $html .= '<input type="hidden" name="listitemstatus' . $item->id . '" value="' . $value->status . '"/>';
            }
           
            $html .= '</div>';
            $html .= '<div class="hclitemuser">'.$item->users.'</div>';
            $html .= '<div class="clear"></div>';
            $html .= '</div>';
        } //$items as $key => $value
        $html .= '</form>';
        return $html;
    }
    
    /**
     * Decide if a user can see a checklist
     *
     * @global $current_user.
     * @global $post. the post object
     * @return bool.
     */
    function block_user()
    {
        global $current_user, $post;
        get_currentuserinfo();
        $meta = unserialize( get_post_meta( $post->ID, 'hcllistusers', true ) );
        $itemusers = unserialize( get_post_meta( $post->ID, 'hclitemusers', true ) );
        if ( !is_array( $meta ) )
            $meta = array();
        
        if ( !is_array( $itemusers ) )
            $itemusers = array();
        
        if ( is_single() && get_post_type() == 'checklist' ) {
            if ( !in_array( $current_user->user_login, $meta, true ) && !in_array($current_user->user_login, $itemusers, true) ) {
                return false;
            } //!in_array( $current_user->user_login, $meta, true )
            else {
                return true;
            }
            
        } //is_single() && get_post_type() == 'checklist'
    }

    
    /**
     * Get items from a checklist
     *
     * @global $post. The post object
     * @global $wpdb.
     * @return array. The items
     */
    function get_list_items()
    {
        global $post, $wpdb;
        $table = $wpdb->prefix . 'hcl_listitems';
        $items = $wpdb->get_results( "SELECT * FROM $table WHERE list_id = $post->ID ORDER BY listorder ASC", ARRAY_A );
        return $items;
    }
    
    /**
     * Get meta and unserialize
     *
     * @return array
     */
    function getMeta( $id )
    {
        $fields = unserialize( get_post_meta( $id, 'hcllisttemplate', true ) );
        $desc   = get_post_meta( $id, 'hcllisttemplatedesc', true );
        
        $arr = array(
             'description' => $desc,
            'fields' => $fields 
        );
        return $arr;
    }
    
    
    /**
     * Add style
     */
    function add_my_stylesheet()
    {
        if ( get_post_type() != 'checklist' )
            return;
        $string = 
        $myStyleUrl = file_exists(TEMPLATEPATH.'/hcllists.css') ? get_bloginfo('template_directory').'/hcllists.css' : $this->dir . 'css/hcllists.css';
        $test = TEMPLATEPATH;
        //wp_die($test);

        wp_deregister_style( 'hcllists');
        wp_register_style( 'hcllists', $myStyleUrl );
        wp_enqueue_style( 'hcllists' );
    }
    
    /**
     * Add scripts
     */
    function add_scripts()
    {
        if ( get_post_type() == 'checklist' ) {
            global $post;
            wp_enqueue_script( 'jquery' );
            
            $myScriptUrl = $this->dir . 'js/lists.min.js';
            wp_register_script( 'hcllistsjs', $myScriptUrl );
            wp_enqueue_script( 'hcllistsjs' );
            wp_localize_script( 'hcllistsjs', 'hcllistobj', array(
                 'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'postid' => $post->ID 
            ) );
        } //get_post_type() == 'checklist'
        
    }
    
    /**
     * Set status with ajax
     */
    function set_status()
    {
        global $wpdb, $current_user;
        get_currentuserinfo();
        $itemid    = str_replace( 'listitem', '', $_POST['id'] );
        $status    = $_POST['status'];
        $tablename = $wpdb->prefix . 'hcl_listitems';
        $wpdb->update( $tablename, array(
             'status' => $status,
            'lastchanged' => date( 'Y-m-d H:i:s' ),
            'lastchangedby' => $current_user->display_name 
        ), array(
             'id' => $itemid 
        ), array(
             '%d',
            '%s',
            '%s' 
        ) );
        die();
    }
    
    /**
     * Check if changed
     */
    function check_changed()
    {
        global $wpdb;
        $listid    = $_GET['listid'];
        $tablename = $wpdb->prefix . 'hcl_listitems';
        $results   = $wpdb->get_results( "SELECT status FROM $tablename WHERE list_id = $listid" );
        $json      = json_encode( $results );
        echo $json;
        die();
    }
    
    /**
     * Install plugin
     */
    function install()
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hcl_listitems';
        
        if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql   = "CREATE TABLE $table (
    			id INT NULL AUTO_INCREMENT,
    			list_id INT NOT NULL,
    			name TEXT NULL,
    			description LONGTEXT NULL,
    			users LONGTEXT NULL,
    			status INT(11)  NULL,
    			listorder INT(11)  NULL,
    			lastchangedby VARCHAR(45) NULL,
    			lastchanged DATETIME NULL,
    			PRIMARY KEY (id) );";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
        error_log(print_r(get_post(get_option('hclpage')),true));
        $oldpost = get_post(get_option('hclpage'));
        if( $oldpost == NULL || $oldpost->post_status != 'publish' ){
            error_log('creating page');
            $post = array(
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
                'post_author'    => 1,
                'post_date'      => date( 'Y-m-d H:i:s' ),
                'post_name'      => 'Checklists',
                'post_status'    => 'publish',
                'post_title'     => 'Checklists',
                'post_type'      => 'page' 
            );
            
            $newvalue = wp_insert_post( $post, false );
            
            update_option( 'hclpage', $newvalue );
        }
    }
}

register_activation_hook( __FILE__, array(
     'HyperCheckList',
    'install' 
) );
$hyperchecklist = new HyperCheckList;
require_once( 'classes/class.Hcl_Admin.php' );
$hyperchecklistadmin = new Hcl_Admin;

?>