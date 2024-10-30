<?php
/**
 * Hcl_Admin
 * @todo functionallity to add statuses
 */
class Hcl_Admin extends HyperCheckList
{
    // path to plugin folder
    protected $dir = '';
    
    /**
     * Call hooks
     */
    function __construct()
    {
        add_action( 'init', array(
             $this,
            'create_post_types' 
        ) );
        add_action( 'init', array(
             $this,
            'forms' 
        ) );
        add_action( 'admin_init', array(
             $this,
            'add_scripts' 
        ) );
        add_action( 'admin_enqueue_scripts', array(
             $this,
            'load_scripts' 
        ) );
        add_action( 'add_meta_boxes', array(
             $this,
            'meta_box_init' 
        ) );
        add_action( 'save_post', array(
             $this,
            'savepost' 
        ) );
        add_action( 'wp_ajax_hcl_get_elements', array(
             $this,
            'get_elements' 
        ) );
        add_action('admin_menu', array(
            $this,
            'submenu'
        ) );
        parent::__construct();
    }
    
    
    /**
     * Create posttypes
     */
    function create_post_types()
    {
        $labels = array(
             'name' => __( 'Checklists', 'hyperchecklist' ),
            'singular_name' => __( 'Checklist', 'hyperchecklist' ),
            'add_new' => __( 'Add checklist', 'hyperchecklist' ),
            'add_new_item' => __( 'Add checklist', 'hyperchecklist' ),
            'edit_item' => __( 'Edit checklist', 'hyperchecklist' ),
            'view_item' => __( 'View checklist', 'hyperchecklist' ),
            'search_items' => __( 'Search for a checklist', 'hyperchecklist' ),
            'not_found' => __( 'No checklist found', 'hyperchecklist' ),
            'not_found_in_trash' => __( 'Nothing is in the trash', 'hyperchecklist' ),
            'parent_item_colon' => '' 
        );
        
        $args = array(
             'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array(
                 'title',
                'author',
                'comments' 
            ) 
        );
        register_post_type( 'checklist', $args );
        
        $labels = array(
             'name' => __( 'Checklist groups', 'hyperchecklist' ),
            'singular_name' => __( 'Checklist group', 'hyperchecklist' ),
            'add_new' => __( 'Add checklist group', 'hyperchecklist' ),
            'add_new_item' => __( 'Add checklist group', 'hyperchecklist' ),
            'edit_item' => __( 'Edit checklist group', 'hyperchecklist' ),
            'view_item' => __( 'View checklist group', 'hyperchecklist' ),
            'search_items' => __( 'Search for a checklist group', 'hyperchecklist' ),
            'not_found' => __( 'No checklist groups found', 'hyperchecklist' ),
            'not_found_in_trash' => __( 'Nothing is in the trash', 'hyperchecklist' ),
            'parent_item_colon' => '' 
        );
        
        $args = array(
             'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'page',
            'hierarchical' => false,
            'show_in_menu' => 'edit.php?post_type=checklist',
            'supports' => array(
                 'title' 
            ) 
        );
        register_post_type( 'checklistgroup', $args );
    }

    function submenu(){
        $theme_page = add_submenu_page( 'edit.php?post_type=checklist', __('Checklist options', 'hyperchecklist'), __('Checklist options'), 'manage_options', 'checklist-options',  array($this, 'print_page'));
    }

    function print_page(){
        global $wpdb;
        $current = get_option('hclpage', true);
        ?>
            <div class="wrap">
                <div id="icon-options-general" class="icon32"><br></div>
                <h2><?php _e('Checklist options', 'hyperchecklist') ?></h2>
                <form action="" method="post">
                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <th>
                                    <label for="hclpage">
                                        <?php _e('Choose a page to use as list page' , 'hyperchecklist') ?>
                                    </label>
                                </th>
                                <td>
                                    <?php
                                         $pages = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'page' AND post_status = 'publish'");
                                    ?>
                                    <select name="hclpage" id="hclpage">
                                        <option value="-1"><?php _e('No page', 'hyperchecklist'); ?></option>
                                        <?php foreach ($pages as $key => $page): ?>
                                            <option value="<?php echo $page->ID; ?>" <?php if($current == $page->ID)echo 'selected="selected"'; ?>><?php echo $page->post_title ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <input type="submit" class="button-primary" value="<?php _e('Save', 'hyperchecklist'); ?>">
                                </th>
                            </tr>
                        </tbody>
                    </table>
                </form>

                <form action="" method="post">
                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="css"></label><?php _e('Copy css to theme folder','hyperchecklist'); ?>
                            </th>
                            <td>
                                <input type="hidden" name="copycss" value="copycss">
                                <input type="submit" value="<?php _e('Copy css','hyperchecklist') ?>">
                                <?php if(file_exists(TEMPLATEPATH.'/hcllists.css')): ?>
                                <p><?php _e('File exists in template folder. Copy will override current file') ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </form>
                <?php if(file_exists(TEMPLATEPATH.'/hcllists.css')): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="css"></label><?php _e('Delete css from theme folder','hyperchecklist'); ?>
                            </th>
                            <td>
                                <input type="hidden" name="deletecss" value="deletecss">
                                <input type="submit" value="<?php _e('Delete css','hyperchecklist') ?>">
                            </td>
                        </tr>
                    </table>
                </form>
                <?php endif; ?>
            </div>
        <?php
        
    }

    function forms(){
        if(isset($_POST['copycss'])){
            copy($this->dir.'/css/hcllists.css', TEMPLATEPATH.'/hcllists.css');
        }
        if(isset($_POST['deletecss'])){
            unlink(TEMPLATEPATH.'/hcllists.css');
        }
        if (isset($_POST['hclpage'])) {
            if($_POST['hclpage'] != '-1'){
                update_option('hclpage', $_POST['hclpage']);
            }else {
                update_option('hclpage', 0);
            }
        }
    }
    
    /**
     * add scripts
     */
    function add_scripts()
    {
        $cssurl = $this->dir . 'css/admin.css';
        wp_register_style( 'hcladmincss', $cssurl );
        wp_enqueue_style( 'hcladmincss' );

        $scripturl = $this->dir . 'js/checklist.min.js';
        wp_deregister_script( 'hcllist' );
        wp_register_script( 'hcllist', $scripturl );
    }
    
    /**
     * Load Scripts. Only runs on selected pages 
     *
     * @global $current_screen. The current screen object
     */
    function load_scripts()
    {
        global $current_screen;
        if ( $current_screen->id == 'checklistgroup' || $current_screen->id == 'checklist' ) {

            
            wp_enqueue_script( 'jquery-ui-widget' );
            wp_enqueue_script( 'hcllist' );
            wp_localize_script( 'hcllist', 'hcllistjs', array(
                 'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'removetext' => __( 'Do you want to remove the earlier import?', 'hyperchecklist' ),
                'removeboxtext' => __( 'This is scheduled for removal', 'hyperchecklist' ),
                'canceltext' => __( 'Cancel', 'hyperchecklist' ),
                'removenowtext' => __( 'Remove now', 'hyperchecklist' ) 
            ) );
        } //$current_screen->id == 'checklistgroup' || $current_screen->id == 'checklist'
    }
    
    /**
     * Add meta boxes
     */
    function meta_box_init()
    {
        add_meta_box( 'hcldescription', __( 'Description', 'hyperchecklist' ), array(
             $this,
            'hclgroup_description' 
        ), 'checklistgroup', 'normal', 'high' );
        
        add_meta_box( 'hcllistitem', __( 'List', 'hyperchecklist' ), array(
             $this,
            'hclgroup_list' 
        ), 'checklistgroup', 'normal', 'high' );
        
        add_meta_box( 'hcllists', __( 'List', 'hyperchecklist' ), array(
             $this,
            'hcl_listusers' 
        ), 'checklist', 'side', 'high' );
        
        add_meta_box( 'hcllistsusers', __( 'Invite users', 'hyperchecklist' ), array(
             $this,
            'hcl_listsusers' 
        ), 'checklist', 'normal', 'high' );
        
        add_meta_box( 'hclcheckdescription', __( 'Description', 'hyperchecklist' ), array(
             $this,
            'hcl_description' 
        ), 'checklist', 'normal', 'high' );
        
        add_meta_box( 'hcllists', __( 'List', 'hyperchecklist' ), array(
             $this,
            'hcl_lists' 
        ), 'checklist', 'normal', 'high' );
        
    }
    
    /**
     * description metabox for list groups
     */
    function hclgroup_description( $post )
    {
        $text = get_post_meta( $post->ID, 'hcllisttemplatedesc', true );
    ?>
			<strong>
				<?php _e( 'A short description', 'hyperchecklist' ); ?>
			</strong>

			<textarea class="widefat" rows="5" name="hcldesc"><?php echo $text; ?></textarea>

		<?php
    }
    
    /**
     * Display list items
     */
    function hclgroup_list( $post )
    {
        $json = $this->getMeta( $post->ID );
        $arr  = $json['fields'];
        
        
    ?>				
			<table class="widefat" id="hcllist">

				<tbody>
					<tr>
						<th>
							<?php _e( 'Task title', 'hyperchecklist' ); ?>
						</th>

						<th>
							<?php _e( 'Description', 'hyperchecklist' ); ?>
						</th>
					</tr>

					<?php if ( !empty( $arr ) ): ?>
						<?php foreach ( $arr as $key => $field ): ?>
						<tr class="formfield">
							<td>
								<input type="text" placeholder="Task" name="hcltask[]" value="<?php echo stripslashes( htmlspecialchars( urldecode( $field['title'] ) ) ); ?>">
								
								<div>
									<a href="#" class="remover">remove</a>
								</div>
							</td>
							<td>
								<textarea class="widefat" cols="50" rows="4" name="hcltaskdesc[]"><?php echo stripslashes( htmlspecialchars( urldecode( $field['description'] ) ) ); ?></textarea>
							</td>
						</tr>
						<?php endforeach; ?>

						<?php else: ?>
									
						<tr class="formfield">
							<td>
								<input type="text" placeholder="Task" name="hcltask[]">
								
								<div>
									<a href="#" class="remover">remove</a>
								</div>
							</td>
							<td>
								<textarea class="widefat" cols="50" rows="4" name="hcltaskdesc[]"></textarea>
							</td>
						</tr>			
					<?php endif; ?>
					<tr class="hcladdbefore">
						<th colspan="3">
							<a href="#" id="hcladder" class="button-primary"><?php _e( 'Add a field', 'hyperchecklist' ); ?></a>
						</th>
					</tr>
				</tbody>
			</table>
		<?php
    }
    /**
     * Display users
     * @param $post object
     */
    function hcl_listsusers( $post )
    {
        $oldusers = unserialize( get_post_meta( $post->ID, 'hcllistusers', true ) ); 
        if (!is_array($oldusers))
            $oldusers = array();
        ?>
			<p><?php _e( 'invite users to this list', 'hyperchecklist' ); ?></p>
			<?php $users = $this->list_users(); ?>
			<div id="taxonomy-category" class="categorydiv">
				<ul id="category-tabs" class="category-tabs">
					<li class="tabs"><a href="#category-all" tabindex="3"><?php _e('Users', 'hyperchecklist'); ?></a></li>
				</ul>

				<div id="category-all" class="tabs-panel">
					<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
						<?php foreach ( $users as $key => $user ): ?>
							<li class="popular-category">
							<label class="selectit">
								<input value="<?php echo $user->user_login; ?>" type="checkbox" name="users[]" <?php if ( in_array( $user->user_login, $oldusers ) ) echo 'checked="checked"'; ?>> 
								<?php echo $user->display_name; ?></label>
						</li>
						<?php endforeach; ?>
						
					</ul>
				</div>
				
			</div>
		<?php
    }
    
    /**
     * Description box
     * @param post object
     */
    function hcl_description( $post )
    {
        $text  = get_post_meta( $post->ID , 'hcllistdesc', true);
        //$text = $arr['description']; ?>
			<strong><?php _e( 'A short description','hyperchecklist' ); ?></strong>
			<textarea class="widefat" rows="5" name="hcldesc" id="desc"><?php echo $text; ?></textarea>
		<?php
    }


    /**
     * Dislay list items
     */
    function hcl_lists( $post )
    {
        $args = array(
             'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'DESC',
            'post_type' => 'checklistgroup',
            'post_status' => 'publish' 
        );
        
        $lists  = get_posts( $args );
        $fields = $this->get_list_items();
       ?>
			<table class="widefat" id="hcllist">

				<tbody>
					<tr class="ui-state-disable">
						<th width="200">
							<?php _e( 'Task title', 'hyperchecklist' ); ?>
						</th>

						<th width="300">
							<?php _e( 'Description', 'hyperchecklist' ); ?>
						</th>

						<th>
							<?php _e( 'Status and users', 'hyperchecklist' ); ?>
						</th>
					</tr>

					<tr class="ui-state-disable">
						<td>
							<?php _e( 'Use a list', 'hyperchecklist' ); ?>
						</td>

						<td colspan="3">
							<select id="listchooser">
								<option value="0">Choose a list</option>
								<?php foreach ( $lists as $key => $list ): ?>
									<option value="<?php echo $list->ID; ?>"><?php echo $list->post_title; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<?php if ( !empty( $fields ) ): ?>
						<?php foreach ( $fields as $key => $field ): ?>
						<tr class="formfield">
							<td>
								<input type="text" placeholder="Task" name="hcltask[]" value="<?php echo stripslashes( urldecode( $field['name'] ) ); ?>">
								
								<div class="hclactions">
									<a href="#" class="remover">remove</a>
								</div>
							</td>
							<td>
								<textarea class="widefat" cols="50" rows="4" name="hcltaskdesc[]"><?php echo stripslashes( htmlspecialchars_decode( $field['desc'] ) ); ?></textarea>
							</td>


							<td>
								<select name="hclstatus[]" class="widefat">
									<option value="0" <?php if ( $field['status'] == '0' ) echo 'selected="selected"'; ?>>Pending</option>
									<option value="1" <?php if ( $field['status'] == '1' ) echo 'selected="selected"'; ?>>Done</option>
									<option value="2" <?php if ( $field['status'] == '2' ) echo 'selected="selected"'; ?>>Error</option>
								</select>

                                <label for="itemusers"><?php _e('Item users', 'hyperchecklist') ?></label>
                                <select name="itemusers[]" class="widefat">
                                <option value="-1"><?php _e('Choose a user'); ?></option>
                                    <?php $users = $this->list_users(); ?>
                                    <?php foreach ( $users as $key => $user ): ?>
                                        <option value="<?php echo $user->user_login ?>" <?php if($user->user_login == $field['users']) echo 'selected="selected"'; ?>><?php echo $user->display_name ?></option>
                                    <?php endforeach; ?>
                                </select>
								<input type="hidden" name="hclitemid[]" class="idbox" value="<?php echo $field['id']; ?>"/>
								<input type="hidden" name="hclitemdelete[]" class="deleter" value="false"/>
							</td>
						</tr>
						<?php endforeach; ?>

						<?php endif; ?>
									
						<tr class="formfield">
							<td>
								<input type="text" placeholder="Task" name="hcltask[]" value="">
								
								<div class="hclactions">
									<a href="#" class="remover">remove</a>
								</div>
							</td>
							<td>
								<textarea class="widefat" cols="50" rows="4" name="hcltaskdesc[]"></textarea>
							</td>


							<td>
								<select name="hclstatus[]" class="widefat">
									<option value="0">Pending</option>
									<option value="1">Done</option>
									<option value="2">Error</option>
								</select>
                                <p></p>
                                <label for="itemusers"><?php _e('Item users', 'hyperchecklist') ?></label>
                                <select name="itemusers[]" class="widefat">
                                <option value="-1"><?php _e('Choose a user'); ?></option>
                                    <?php $users = $this->list_users(); ?>
                                    <?php foreach ( $users as $key => $user ): ?>
                                        <option value="<?php echo $user->user_login ?>"><?php echo $user->display_name ?></option>
                                    <?php endforeach; ?>
                                </select>
								<input type="hidden" name="hclitemid[]" class="idbox" value="-1"/>
                                <input type="hidden" name="hclitemdelete[]" class="deleter" value="false"/>
							</td>
                            
						</tr>		
					<tr class="hcladdbefore ui-state-disable">
						<th colspan="4">
							<a href="#" id="hcladder" class="button-primary"><?php _e( 'Add a field', 'hyperchecklist' ); ?></a>
						</th>
					</tr>
				</tbody>
			</table>
		<?php
    }
    
    /**
     * Save meta and list items to database
     */
    function savepost( $post_id )
    {
        global $post, $wpdb, $current_user;
        get_currentuserinfo();
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;
        
        
        
        if ( 'page' == $_POST['post_type'] ) {
            if ( !current_user_can( 'edit_page', $post_id ) )
                return;
        } //'page' == $_POST['post_type']
        else {
            if ( !current_user_can( 'edit_post', $post_id ) )
                return;
        }
        
        if ( 'checklistgroup' == $_POST['post_type'] ) {
            $title = $_POST['hcltask'];
            $desc  = $_POST['hcltaskdesc'];
            $arr   = array();
            
            foreach ( $title as $key => $value ) {
                if ( empty( $value ) )
                    wp_die( 'sorry' );
                $arr[] = array(
                     'title' => urlencode( $value ),
                    'description' => urlencode( $desc[$key] ) 
                );
            } //$title as $key => $value
            
            $json = serialize( $arr );
            update_post_meta( $post_id, 'hcllisttemplate', $json );
            update_post_meta( $post_id, 'hcllisttemplatedesc', $_POST['hcldesc'] );

            
        } //'checklistgroup' == $_POST['post_type']
        
        if ( 'checklist' == $_POST['post_type'] ) {
            $title  = $_POST['hcltask'];
            $desc   = $_POST['hcltaskdesc'];
            $status = $_POST['hclstatus'];
            $oldid  = $_POST['hclitemid'];
            $users  = empty($_POST['users']) ? array() : $_POST['users'];
            if(!in_array($current_user->user_login, $users) ){
                $users[] = $current_user->user_login;
            }
            $users = serialize( $users );
            $delete = $_POST['hclitemdelete'];
            $itemusers = $_POST['itemusers'];
            update_post_meta( $post_id, 'hcllistdesc', $_POST['hcldesc'] );
            update_post_meta( $post_id, 'hcllistusers', $users );
            update_post_meta( $post_id, 'hclitemusers', serialize($itemusers) );
            
            $tablename = $wpdb->prefix . 'hcl_listitems';
            foreach ( $title as $key => $value ) {
                if($itemusers[$key] == '-1')
                    $itemusers[$key] = '';
                if ( $value == '' )
                    continue;
                if ( $delete[$key] == 'true' ) {
                    $id = $oldid[$key];
                    $wpdb->query( "DELETE FROM $tablename 
								WHERE id = $id" );
                    continue;
                } //$delete[$key] == 'true'
                $namestring = stripslashes( esc_attr( $value ) );
                $descstring = $desc[$key];
                
                if ( !current_user_can( 'unfiltered_html' ) ) {
                    $descstring = esc_textarea( $desc[$key] );
                } //current_user_can( 'unfiltered_html' )
                
                if ( $oldid[$key] != '-1' ) {
                    $wpdb->update( $tablename, array(
                         'list_id' => $post_id,
                        'name' => $namestring,
                        'description' => $descstring,
                        'users' => $itemusers[$key],
                        'status' => $status[$key],
                        'listorder' => $key + 1 
                    ), array(
                         'id' => $oldid[$key] 
                    ), array(
                         '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                        '%d' 
                    ) );
                } //$oldid[$key] != '-1'
                else {
                    $wpdb->insert( $tablename, array(
                         'list_id' => $post_id,
                        'name' => $value,
                        'description' => $desc[$key],
                        'users' => $itemusers[$key],
                        'status' => $status[$key],
                        'listorder' => $key + 1,
                        'lastchanged' => date( 'Y-m-d H:i:s' ),
                        'lastchangedby' => $current_user->display_name 
                    ), array(
                         '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                        '%d',
                        '%s',
                        '%s' 
                    ) );
                }
            } //$title as $key => $value
            
        } //'checklist' == $_POST['post_type']
        
    }
    
    function get_elements()
    {
        $fields = $this->getMeta( $_GET['id'] );
        $fields = $fields['fields'];
        
        foreach ( $fields as $key => $field ): ?>

	<tr class="formfield newimport">
		<td>
			<input type="text" placeholder="Task" name="hcltask[]" value="<?php echo stripslashes( htmlspecialchars( urldecode( $field['title'] ) ) ); ?>">
			
			<div class="hclactions">
				<a href="#" class="remover">remove</a>
			</div>
		</td>
		<td>
			<textarea class="widefat" cols="50" rows="4" name="hcltaskdesc[]"><?php echo stripslashes( htmlspecialchars( urldecode( $field['description'] ) ) ); ?></textarea>
		</td>


		<td>
			<select name="hclstatus[]" class="widefat">
				<option value="0" <?php if ( $field['status'] == '0' ) echo 'selected="selected"'; ?>>Pending</option>
				<option value="1" <?php if ( $field['status'] == '1' ) echo 'selected="selected"'; ?>>Done</option>
				<option value="2" <?php if ( $field['status'] == '2' ) echo 'selected="selected"'; ?>>Error</option>
			</select>

            <label for="itemusers"><?php _e('Item users', 'hyperchecklist') ?></label>
                                <select name="itemusers[]" class="widefat">
                                <option value="-1"><?php _e('Choose a user'); ?></option>
                                    <?php $users = $this->list_users(); ?>
                                    <?php foreach ( $users as $key => $user ): ?>
                                        <option value="<?php echo $user->user_login ?>"><?php echo $user->display_name ?></option>
                                    <?php endforeach; ?>
                                </select>
			<input type="hidden" name="hclitemid[]" class="idbox" value="-1"/>
			<input type="hidden" name="hclitemdelete[]" class="deleter" value="false"/>
		</td>
	</tr>

  	<?php
        endforeach;
        
        die();
    }
    
    function list_users( $group = false )
    {
        // prepare arguments
        $args = array(
            // search only for Authors role
             'role' => '' 
            
        );
        // Create the WP_User_Query object
        $wp_user_query = new WP_User_Query( $args );
        // Get the results
        $authors = $wp_user_query->get_results();
        return $authors;
        
    }
}
?>