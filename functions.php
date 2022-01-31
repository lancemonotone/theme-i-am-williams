<?php
/**
 * This function will return one key from the supplied array.
 * The likelihood of the element chosen is based on the weights
 * provided as the value in the array. Like putting fruit into
 * a basket and blindly grabbing one.
 * Our basket contains 5 apples, 3 oranges, a lemon and a pear.
 * If we reach in and grab one, what will we grab?
 *
 * $basket = array(
 * "apples"=>5,
 * "oranges"=>3,
 * "lemons"=>1,
 * "pears"=>1
 * );
 *
 * echo wrandom($basket); //I bet it's an apple
 *
 * @param mixed $array
 *
 * @return mixed weighted random element
 * @link http://www.danmorgan.net/programming/php-programming/weighted-random-and-weighted-shuffle/
 */

function iaw_wrandom($array) {
    foreach ($array as $k => $v) $max += $v;
    $roll = mt_rand(0, $max);
    foreach ($array as $k => $v) if ($roll <= ($rcount += $v)) return $k;

    return $k;
}

/**
 * Get space delimited list of post term slugs
 *
 * @param int $id
 *
 * @return string Post term slugs
 */
function iaw_get_term_slugs($id) {
    return join(' ', wp_get_post_terms($id, 'iaw_type', array("fields" => "slugs")));
}

/**
 * Returns a useable WP Multisite src for use with TimThumb script.
 *
 * @param string $src
 */
function get_timthumb_src($src) {
    global $blog_id;
    if (isset($blog_id) && $blog_id > 0) {
        $imageParts = explode('/files/', $src);
        if (isset($imageParts[1])) {
            return '/wp-content/blogs.dir/' . $blog_id . '/files/' . $imageParts[1];
        }
    }

    return false;
}

if ( ! class_exists('IAW')) {
    class IAW {
        public $iaw_seed, $iaw_new_request, $iaw_last_request, $disable_filters = false;

        function __construct() {

            // Remove built-in 'category' and 'tag' taxonomies and add a few of our own
            add_action('init', array(&$this, 'register_custom_taxonomies'), 20);
            add_action('init', array(&$this, 'convert_seniors_to_alumni'), 30);

            // Custom styles for TinyMCE
            add_filter('mce_css', array(&$this, 'my_editor_style'));

            // Load custom plugins
            add_action('after_setup_theme', array(&$this, 'load_theme_plugins'));

            // Enable custom menus
            add_action('after_setup_theme', array(&$this, 'setup_theme_support'));

            // Include page slug in body class
            add_filter('body_class', array(&$this, 'add_body_classes'));

            // Add css and js
            add_action('admin_head', array(&$this, 'load_admin_css'));
            add_action('admin_footer', array(&$this, 'load_admin_js'));
            add_action('wp_enqueue_scripts', array(&$this, 'load_frontend_js'));
            add_action('wp_enqueue_scripts', array(&$this, 'load_frontend_css'));

            // Filter Home Pages and Landing Pages submenu pages
            add_action('admin_menu', array(&$this, 'add_page_menus'));
            add_action('load-edit.php', array(&$this, 'filter_pages_mysql_action'));

            // Change WP posts label to IAW, Remove unnecessary admin menu items
            add_action('init', array(&$this, 'change_post_object_label'));
            add_action('admin_menu', array(&$this, 'change_post_menu_label'));
            add_action('admin_menu', array(&$this, 'remove_menus'));

            // Randomly order posts upon home page load and allow pagination
            add_filter('posts_orderby', array(&$this, 'edit_posts_orderby'), 20);
            add_filter('posts_distinct', array(&$this, 'distinct_filter'), 20);
            add_filter('posts_where', array(&$this, 'filter_where_active'), 20);
            add_filter('posts_join', array(&$this, 'filter_join_active'), 20);

            // Excerpt control
            add_filter('excerpt_length', array(&$this, 'custom_excerpt_length'), 999);
            add_filter('excerpt_more', array(&$this, 'new_excerpt_more'), 999);

            // Shortcodes
            add_filter('init', array(&$this, 'add_shortcodes'));

            // Registration
            add_filter('gform_post_data', array(&$this, 'registration_tweaks'), 10, 3);
            add_filter('gform_after_submission', array(&$this, 'registration_taxonomies'), 10, 2);
        }


        /**
         * Is iaw_converted_[year] flag set? If not...
         * Determine date of first Sunday in June
         * Is today after that date?
         * Query all 'iaw_class_year' = date('Y')
         * Recategorize all from Student to Alumni
         * Set iaw_converted_[year] flag
         *
         * @return void
         */
        function convert_seniors_to_alumni() {
            $debug = false;

            if (defined('DOING_AJAX') && DOING_AJAX === true || isset($_REQUEST['doing_wp_cron'])) return;

            $this_year = date('Y');

            // Have we already converted?
            if (get_option('iaw_converted') === $this_year && ! $debug) {
                return;
            }

            // Is today after commencement?
            $commencement_date = date('Ymd', strtotime('first Sunday of June ' . $this_year));
            $today             = date('Ymd');

            if ($today <= $commencement_date && ! $debug) {
                return;
            }

            // Get all students of current or prior year.
            $wp_query = new WP_Query;
            $posts    = $wp_query->query(
                array(
                    'post_type'  => 'post',
                    'nopaging'   => 'true',
                    'tax_query'  => array(
                        array(
                            'taxonomy' => 'iaw_type',
                            'field'    => 'slug',
                            'terms'    => 'students'
                        )
                    ),
                    'meta_query' => array(
                        array(
                            'key'   => 'iaw_class_year',
                            'value' => $this_year,
                            'compare' => '<='
                        )
                    )
                )
            );

            // Alumni
            $new_cat = get_term_by('slug', 'alumni', 'iaw_type');

            foreach ($posts as $post) {
                // Remove old categories
                $current_cats = array($new_cat->term_id);
                wp_set_object_terms($post->ID, $current_cats, 'iaw_type');
            }

            update_option('iaw_converted', $this_year);
        }


        function registration_tweaks($post_data, $form, $entry) {
            // Massage the input data
            $class_year = $post_data['post_custom_fields']['iaw_class_year'];
            $job_title  = $post_data['post_custom_fields']['iaw_job_title'];
            $other      = $post_data['post_custom_fields']['iaw_other'];

            if ($class_year) {
                $class_year = ' \'' . substr($class_year, 2, 2);
            }
            if ($job_title) {
                $job_title = ', ' . $job_title;
            }
            if ($other) {
                $other = ', ' . $other;
            }
            $post_data['post_title'] .= $class_year . $job_title . $other;

            return $post_data;
        }

        function registration_taxonomies($entry, $form) {
            // if no post was created, return
            if ( ! $entry['post_id'])
                return;

            $affiliation_map = array(
                'Student' => 'students',
                'Faculty' => 'faculty',
                'Alum'    => 'alumni',
                'Staff'   => 'staff',
                'Parent'  => 'parents',
                'Other'   => 'other'
            );

            $args = array(
                'taxonomy'   => 'photoshoot_year',
                'orderby'    => 'term_id',
                'order'      => 'DESC',
                'hide_empty' => false);

            $current_photoshoot = get_terms($args)[0]->slug;

            $found = 0;
            foreach ($form['fields'] as $field) {
                if ($found === 2) {
                    wp_set_object_terms($entry['post_id'], $current_photoshoot, 'photoshoot_year');
                    break;
                }
                if ($field['postCustomFieldName'] === 'iaw_class_year') {
                    if ($term_slug = $entry[ $field['id'] ]) {
                        wp_set_object_terms($entry['post_id'], $term_slug, 'class_year', false);
                    }
                    $found++;
                } else if ($field['adminLabel'] === 'Affiliation') {
                    foreach ($field['inputs'] as $input) {
                        if ($term_slug = $affiliation_map[ $entry[ $input['id'] ] ]) {
                            wp_set_object_terms($entry['post_id'], $term_slug, 'iaw_type', true);
                        }
                    }
                    $found++;
                }
            }
        }

        function add_shortcodes() {
            add_shortcode('wpbsearch', array(&$this, 'wpbsearchform'));
        }

        function wpbsearchform($form) {
            $form = '
			<form method="get" id="searchform" action="' . esc_url(home_url('/')) . '">
				<label for="s" class="assistive-text">' . __('Search') . '</label>
				<input type="search" size="60" class="field" name="s" id="s" placeholder="' . __('Search') . '" />
				<input type="submit" class="submit" name="submit" id="searchsubmit" value="' . __('Search') . '" />
			</form>';

            return $form;
        }

        /**
         * Show front-end styles in editor
         *
         * @param string $url
         *
         * @return string URL of editor style sheet
         */
        function my_editor_style($url) {
            if ( ! empty($url))
                $url .= ',';
            // Change the path here if using sub-directory
            $url .= trailingslashit(get_stylesheet_directory_uri()) . 'css/editor-style.css';

            return $url;
        }

        function setup_theme_support() {
            add_theme_support('post-thumbnails');
            add_theme_support('menus');
        }

        function load_admin_css() {
            wp_enqueue_style('jquery-tablesorter-pager', WMS_LIB_URL . '/assets/js/vendor/jquery.tablesorter/addons/pager/jquery.tablesorter.pager.min.css');
            wp_enqueue_style('jquery-tablesorter-filter', WMS_LIB_URL . '/assets/js/vendor/jquery.tablesorter/addons/filter/jquery.filter.min.css');
            wp_enqueue_style('admin', get_stylesheet_directory_uri() . '/css/admin.css');
        }

        function load_admin_js() {
            wp_enqueue_script('jquery-tablesorter-pager', WMS_LIB_URL . '/assets/js/vendor/jquery.tablesorter/addons/pager/jquery.tablesorter.pager.min.js', array('jquery'), '', true);
            wp_enqueue_script('jquery-tablesorter-filter', WMS_LIB_URL . '/assets/js/vendor/jquery.tablesorter/addons/filter/jquery.filter.min.js', array('jquery'), '', true);
            wp_enqueue_script('jquery-tablesorter', WMS_LIB_URL . '/assets/js/vendor/jquery.tablesorter/jquery.tablesorter.min.js', array('jquery', 'jquery-tablesorter-pager', 'jquery-tablesorter-filter'), '', true);
            wp_enqueue_script('admin', get_stylesheet_directory_uri() . '/js/admin.js', array('jquery', 'jquery-tablesorter'), '', true);
        }

        function load_frontend_css() {
            wp_enqueue_style('ss', get_stylesheet_directory_uri() . '/css/ss-standard.css');
        }

        function load_frontend_js() {
            wp_enqueue_script('fittext', get_stylesheet_directory_uri() . '/js/fittext.js', array('jquery'));
            wp_enqueue_script('modernizr', get_stylesheet_directory_uri() . '/js/modernizr.custom.js', array('jquery'));
            wp_enqueue_script('infinitescroll', get_stylesheet_directory_uri() . '/js/jquery.infinitescroll.js', array('jquery'));
            wp_enqueue_script('isotope', get_stylesheet_directory_uri() . '/js/jquery.isotope.min.js', array('jquery', 'infinitescroll'));
            wp_enqueue_script('main', get_stylesheet_directory_uri() . '/js/main.js', array('jquery', 'infinitescroll', 'isotope'));

            // initially hide the grid content until it loads, preventing the FOUC.
            $noscript          = "<style>#items { opacity:0 }</style>";
            $translation_array = array('noscript' => $noscript);
            wp_localize_script('main', 'localized', $translation_array);
        }

        function load_theme_plugins() {
            include_once(rtrim(dirname(__FILE__), '/') . '/plugins/duplicate-post/duplicate-post.php');
            include_once(rtrim(dirname(__FILE__), '/') . '/plugins/custom-field-bulk-editor/custom-field-bulk-editor.php');
        }

        /**
         * Set excerpt length
         *
         * @param int $length
         *
         * @return int new length
         */
        function custom_excerpt_length($length) {
            return 15;
        }

        /**
         * Set excerpt more
         *
         * @param string $more
         *
         * @return string
         */
        function new_excerpt_more($more) {
            return '';
        }

        //add page slug as body class. also include the page parent
        function add_body_classes($classes, $class = '') {
            global $wp_query;
            $post_id = $wp_query->post->ID;
            if (is_page($post_id)) {
                $page = get_page($post_id);
                //check for parent
                if ($page->post_parent > 0) {
                    $parent    = get_page($page->post_parent);
                    $classes[] = 'page-' . sanitize_title($parent->post_name);
                }
                $classes[] = 'page-' . sanitize_title($page->post_name);
            }

            return $classes;// return the $classes array
        }

        /**
         * Change WP posts label to IAW, Remove unnecessary admin menu items
         *
         */
        function remove_menus() {
            global $menu;
            //$restricted = array(__('Dashboard'), __('Posts'), __('Media'), __('Links'), __('Pages'), __('Appearance'), __('Tools'), __('Users'), __('Settings'), __('Comments'), __('Plugins'));
            $restricted = array(__('Posts'), __('Links'), __('Comments'), __('Options'));
            end($menu);
            while (prev($menu)) {
                $value = explode(' ', $menu[ key($menu) ][0]);
                if (in_array($value[0] != null ? $value[0] : "", $restricted)) {
                    unset($menu[ key($menu) ]);
                }
            }
        }

        function change_post_menu_label() {
            global $menu;
            global $submenu;
            $menu[5][0]                 = 'IAW Profiles';
            $submenu['edit.php'][5][0]  = 'IAW Profiles';
            $submenu['edit.php'][10][0] = 'Add IAW Profile';
            $submenu['edit.php'][16][0] = 'IAW Profile Tags';
            echo '';
        }

        function change_post_object_label() {
            global $wp_post_types;
            $labels                     = &$wp_post_types['post']->labels;
            $labels->name               = 'IAW Profiles';
            $labels->singular_name      = 'IAW Profile';
            $labels->add_new            = 'Add IAW Profile';
            $labels->add_new_item       = 'Add IAW Profile';
            $labels->edit_item          = 'Edit IAW Profile';
            $labels->new_item           = 'IAW Profile';
            $labels->view_item          = 'View Profile';
            $labels->search_items       = 'Search IAW Profiles';
            $labels->not_found          = 'No IAW Profiles found';
            $labels->not_found_in_trash = 'No IAW Profiles found in Trash';
        }

        /**
         * Register functions to add extra menus for Home and Landing page listings
         *
         */
        function add_page_menus() {
            add_posts_page('Incomplete', 'Incomplete', 'manage_options', 'edit.php?post_type=post&filter=incomplete');
            add_posts_page('Inactive', 'Inactive', 'manage_options', 'edit.php?post_type=post&filter=inactive');
            add_posts_page('Inactive & Incomplete', 'Inactive & Incomplete', 'manage_options', 'edit.php?post_type=post&filter=inactive_incomplete');
            //add_posts_page('Has Poster', 'Has Poster', 'manage_options', 'edit.php?post_type=post&filter=poster');
            add_posts_page('All Profiles', 'All Profiles', 'manage_options', 'all-profiles', array(&$this, 'display_all_profiles'));
        }

        function display_all_profiles() {
            global $wpdb;
            $query = "SELECT DISTINCT 
                        p.ID,
                        p.post_title as Name,
                        MAX(CASE WHEN tt3.taxonomy = 'iaw_type' then t3.name ELSE NULL END) as 'Type',
                        MAX(CASE WHEN tt2.taxonomy = 'photoshoot_year' then t2.name ELSE NULL END) as 'Photoshoot',
                        MAX(CASE WHEN tt1.taxonomy = 'class_year' then t1.name ELSE NULL END) as 'Class',
                        MAX(CASE WHEN pm1.meta_key = 'iaw_grid_text' then pm1.meta_value ELSE NULL END) as 'Grid Text',
                        p.post_content as Content,
                        MAX(CASE WHEN pm1.meta_key = 'iaw_complete' then pm1.meta_value ELSE NULL END) as 'Complete',
                        MAX(CASE WHEN pm1.meta_key = 'iaw_active' then pm1.meta_value ELSE NULL END) as 'Active',
                        MAX(CASE WHEN pm1.meta_key = 'iaw_poster' then pm1.meta_value ELSE NULL END) as 'Poster',
                        MAX(CASE WHEN pm1.meta_key = 'iaw_notes' then pm1.meta_value ELSE NULL END) as 'Notes'
                
                        FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm1 ON pm1.post_id = p.ID
                        
                        LEFT JOIN {$wpdb->term_relationships} tr1 ON tr1.object_id = p.ID
                        LEFT JOIN {$wpdb->term_taxonomy} tt1 ON tt1.term_taxonomy_id = tr1.term_taxonomy_id
                        LEFT JOIN {$wpdb->terms} t1 ON t1.term_id = tt1.term_id
                        
                        LEFT JOIN {$wpdb->term_relationships} tr2 ON tr2.object_id = p.ID
                        LEFT JOIN {$wpdb->term_taxonomy} tt2 ON tt2.term_taxonomy_id = tr2.term_taxonomy_id
                        LEFT JOIN {$wpdb->terms} t2 ON t2.term_id = tt2.term_id
                        
                        LEFT JOIN {$wpdb->term_relationships} tr3 ON tr3.object_id = p.ID
                        LEFT JOIN {$wpdb->term_taxonomy} tt3 ON tt3.term_taxonomy_id = tr3.term_taxonomy_id
                        LEFT JOIN {$wpdb->terms} t3 ON t3.term_id = tt3.term_id
                        
                        WHERE p.post_type = 'post' 
                        AND p.post_status = 'publish'
                        #AND p.post_status = 'draft'
                        #AND t2.name = 'Mark McCarty, 2017'
                        GROUP BY p.ID";

            $results = $wpdb->get_results($query, ARRAY_A);
            ?>
            <div class="tablewrap">
                <table class="data tablesorter">
                    <thead>
                    <tr>
                        <?php foreach (reset($results) as $header => $v) { ?>
                            <th><?php echo $header ?></th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results as $result) { ?>
                        <tr>
                            <?php foreach ($result as $key => $val) { ?>
                                <td valign="top" <?php echo in_array($key, array('ID', 'Complete', 'Active', 'Poster')) ? 'align="center"' : '' ?>>
                                    <?php if ($key === 'Name'){ ?>
                                    <a href="<?php echo get_edit_post_link($result['ID']) ?>">
                                        <?php } ?>
                                        <?php echo $val ?>
                                        <?php if ($key === 'Name'){ ?>
                                    </a>
                                <?php } ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div><!--#tablewrap -->
            <?php
        }

        function filter_pages_mysql_action() {
            if ($_GET['post_type'] !== 'post') return;
            switch ($_GET['filter']) {
                case 'inactive_incomplete':
                    add_filter('posts_distinct', array(&$this, 'distinct_filter'));
                    add_filter('posts_where', array(&$this, 'inactive_where_filter'));
                    add_filter('posts_where', array(&$this, 'incomplete_where_filter'));
                    add_filter('posts_join', array(&$this, 'join_filter'));
                    break;
                case 'inactive':
                    add_filter('posts_distinct', array(&$this, 'distinct_filter'));
                    add_filter('posts_where', array(&$this, 'inactive_where_filter'));
                    add_filter('posts_join', array(&$this, 'join_filter'));
                    break;
                case 'incomplete':
                    add_filter('posts_distinct', array(&$this, 'distinct_filter'));
                    add_filter('posts_where', array(&$this, 'incomplete_where_filter'));
                    add_filter('posts_join', array(&$this, 'join_filter'));
                    break;
                case 'poster':
                    add_filter('posts_distinct', array(&$this, 'distinct_filter'));
                    add_filter('posts_where', array(&$this, 'poster_where_filter'));
                    add_filter('posts_join', array(&$this, 'join_filter'));
                    break;
                default:
                    break;
            }
        }

        function inactive_where_filter($where) {
            global $wp_query;
            global $wpdb;

            $where .= $wpdb->prepare("
				AND $wpdb->posts.ID 
				NOT IN ( SELECT post_id 
					FROM $wpdb->postmeta 
					WHERE ($wpdb->postmeta.post_id = $wpdb->posts.ID) 
					AND $wpdb->postmeta.meta_key = %s 
					AND $wpdb->postmeta.meta_value = %s)
				", 'iaw_active', '1');

            return $where;
        }

        function incomplete_where_filter($where) {
            global $wp_query;
            global $wpdb;

            $where .= $wpdb->prepare("
				AND $wpdb->posts.ID 
				NOT IN ( SELECT post_id 
					FROM $wpdb->postmeta 
					WHERE ($wpdb->postmeta.post_id = $wpdb->posts.ID) 
					AND $wpdb->postmeta.meta_key = %s 
					AND $wpdb->postmeta.meta_value = %s)
				", 'iaw_complete', '1');

            return $where;
        }

        function poster_where_filter($where) {
            global $wp_query;
            global $wpdb;

            $where .= $wpdb->prepare("
				AND $wpdb->posts.ID 
				NOT IN ( SELECT post_id 
					FROM $wpdb->postmeta 
					WHERE ($wpdb->postmeta.post_id = $wpdb->posts.ID) 
					AND $wpdb->postmeta.meta_key = %s 
					AND $wpdb->postmeta.meta_value = %s)",
                'iaw_poster', '1');

            return $where;
        }

        function distinct_filter($sql) {
            global $wpdb;
            $sql = " DISTINCT " . $sql;

            return $sql;
        }

        function join_filter($sql) {
            global $wpdb;
            $sql = " JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id " . $sql;

            return $sql;
        }


        /**
         * Filter mysql queries to show only Home and Landing pages on respective Admin page listings
         *
         */
        function filter_where_active($where) {
            global $iaw;
            if ( ! is_admin() && ! is_page() && ! $iaw->disable_filters) {
                $where .= " AND pm.meta_key = 'iaw_complete' AND pm.meta_value = '1'" .
                    " AND pm1.meta_key = 'iaw_active' AND pm1.meta_value = '1'";
            }

            return $where;
        }

        function filter_join_active($join) {
            global $iaw;
            if ( ! is_admin() && ! is_page() && ! $iaw->disable_filters) {
                global $wpdb;
                $join .= " JOIN $wpdb->postmeta pm ON pm.post_id = $wpdb->posts.ID" .
                    " JOIN $wpdb->postmeta pm1 ON pm1.post_id = $wpdb->posts.ID";
            }

            return $join;
        }

        /**
         * Randomize posts, keeping same order on subsequent Home page and single post views
         * if referer is within the site.  Home page will randomize again if reloaded.
         *
         * @global       $_GET ['order'] default or 'random' sets random, 'date' sets date.
         *
         * @param string $orderby_statement
         *
         * @return string Modified orderby statement
         */
        function edit_posts_orderby($orderby_statement) {
            global $iaw;
            if ( ! is_admin() && ! $iaw->disable_filters) {
                $order = isset($_GET['order']) ? $_GET['order'] : '';
                switch ($_GET['order']) {
                    case "date":
                        break;
                    case "random":
                        unset($_COOKIE['iaw_seed']);
                        unset($_COOKIE['iaw_new_request']);
                        unset($_COOKIE['iaw_last_request']);
                    default:
                        // $refesh_flag will be true if $request_signatures match between page loads.
                        $request_signature = md5($_SERVER['REQUEST_URI'] . $_SERVER['QUERY_STRING'] . implode('', $_POST));

                        // $interior_flag will be true if the last page URL contains the base url but is not the base url.
                        $interior_flag = is_int(strpos(wp_get_referer(), home_url())) && home_url() != wp_get_referer();

                        if ( ! is_admin() && ! is_single()) {
                            if ( ! isset($_COOKIE['iaw_seed'])) {
                                //echo 'New Grid';
                                $this->iaw_new_request = 1;
                            } else {
                                // if we are home AND the page has been refreshed AND the last page was not an interior page,
                                // unset the seed and flag as a new request so the grid will appear the same upon return.
                                if (is_home() && $_COOKIE['iaw_last_request'] === $request_signature && $interior_flag === true) {
                                    //echo 'New Grid';
                                    $this->iaw_new_request = 1;
                                } else {
                                    //echo 'Same Grid';
                                    $this->iaw_new_request = 0;
                                    setcookie('iaw_last_request', $request_signature, strtotime('+1 days'), '/');
                                }
                            }
                            $this->iaw_seed = $this->iaw_new_request ? null : $_COOKIE['iaw_seed'];
                            if ($this->iaw_seed === null) {
                                $this->iaw_seed = rand();
                                setcookie('iaw_seed', $this->iaw_seed, strtotime('+1 days'), '/');
                            }
                        } elseif (is_single()) {
                            setcookie('iaw_last_request', $request_signature, strtotime('+1 days'), '/');
                        }
                        $orderby_statement = 'RAND(' . $this->iaw_seed . ')';
                }
            }

            return $orderby_statement;
        }

        /**
         * Display adjacent post link with random seed.
         *
         * @param string $format
         * @param string $link
         * @param bool   $previous
         */
        function get_randomized_adjacent_post_link($format = '&laquo; %link', $link = '%title', $previous = true, $echo = true) {
            global $wpdb, $post;
            $seed          = $_COOKIE['iaw_seed'];
            $query         = "SELECT DISTINCT {$wpdb->posts}.ID
					FROM {$wpdb->posts} 
					JOIN {$wpdb->postmeta} pm ON pm.post_id = {$wpdb->posts}.ID 
					JOIN {$wpdb->postmeta} pm1 ON pm1.post_id = {$wpdb->posts}.ID 
					WHERE {$wpdb->posts}.post_type = 'post' 
					AND pm.meta_key = 'iaw_complete' AND pm.meta_value = '1' 
					AND pm1.meta_key = 'iaw_active' AND pm1.meta_value = '1' 
					AND {$wpdb->posts}.post_status = 'publish' 
					ORDER BY RAND($seed)";
            $results       = $wpdb->get_col($query);
            $current_index = array_search($post->ID, $results);

            // Find the index of the next/prev items
            if ($previous) {
                $id = $results[ ($current_index - 1 < 0) ? count($results) - 1 : $current_index - 1 ];
            } else {
                $id = $results[ ($current_index + 1 === count($results)) ? 0 : $current_index + 1 ];
            }

            $rel = $previous ? 'prev' : 'next';

            $title  = get_the_title($id);
            $string = '<a href="' . get_permalink($id) . '" rel="' . $rel . '">';
            $link   = str_replace('%title', $title, $link);
            $link   = $string . $link . '</a>';

            $format = str_replace('%link', $link, $format);

            $adjacent = $previous ? 'previous' : 'next';
            $the_link = apply_filters("{$adjacent}_post_link", $format, $link);
            if ($echo) echo $the_link;
            else return $the_link;
        }
        /**
         * end mysql filter functions
         */

        /**
         * Register Home Page grid image sizes
         * NOT USED
         */
        function add_grid_image_sizes() {
            //add_image_size( 'iaw_home_grid', 380, 380, true );
        }

        /**
         * Register custom post types and ACF fields
         * NOT USED
         */
        function register_custom_post_types() {
            // register_code_here

            flush_rewrite_rules(false);
        } // end function

        /**
         * Register ACF custom taxonomies for Homepage and Landing Pages
         */
        function register_custom_taxonomies() {
            $this->unregister_taxonomy('category');
            $this->unregister_taxonomy('post_tag');
            register_taxonomy('iaw_type', array(0 => 'post',),
                array(
                    'hierarchical'   => true,
                    'label'          => 'IAW Type',
                    'show_ui'        => true,
                    'query_var'      => true,
                    'rewrite'        => array('slug' => 'type'),
                    'singular_label' => 'Type'
                )
            );
            register_taxonomy('class_year', array(0 => 'post',),
                array(
                    'hierarchical'   => true,
                    'label'          => 'Class Year',
                    'show_ui'        => true,
                    'query_var'      => true,
                    'rewrite'        => array('slug' => 'c_year'),
                    'singular_label' => 'Class Year'
                )
            );
            register_taxonomy('photoshoot_year', array(0 => 'post',),
                array(
                    'hierarchical'   => true,
                    'label'          => 'Photoshoot Year',
                    'show_ui'        => true,
                    'query_var'      => true,
                    'rewrite'        => array('slug' => 'p_year'),
                    'singular_label' => 'Photoshoot Year'
                )
            );

            flush_rewrite_rules();
        } // end function

        /**
         * Reverse the effects of register_taxonomy()
         *
         * @package WordPress
         * @subpackage Taxonomy
         * @since 3.0
         * @uses $wp_taxonomies Modifies taxonomy object
         *
         * @param string       $taxonomy Name of taxonomy object
         * @param array|string $object_type Name of the object type
         *
         * @return bool True if successful, false if not
         */
        function unregister_taxonomy($taxonomy, $object_type = '') {
            global $wp_taxonomies;

            if ( ! isset($wp_taxonomies[ $taxonomy ]))
                return false;

            if ( ! empty($object_type)) {
                $i = array_search($object_type, $wp_taxonomies[ $taxonomy ]->object_type);

                if (false !== $i)
                    unset($wp_taxonomies[ $taxonomy ]->object_type[ $i ]);

                if (empty($wp_taxonomies[ $taxonomy ]->object_type))
                    unset($wp_taxonomies[ $taxonomy ]);
            } else {
                unset($wp_taxonomies[ $taxonomy ]);
            }

            return true;
        }
    } // end class
}

//----- INSTANTIATE -----//

if ( ! $iaw) $iaw = new IAW();
?>