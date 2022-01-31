<?php
ob_start();
// note - DO NOT remove the empty conditional before the doctype or the if !HTML5 tags. 
// see http://stackoverflow.com/questions/2518256/override-intranet-compatibility-mode-ie8
?><!--[]--><!DOCTYPE html>
<!--[if IEMobile 7 ]><html <?php language_attributes(); ?> class="no-js iem7" lang="en"><![endif]-->
<!--[if lt IE 7 ]><html <?php language_attributes(); ?> class="no-js ie ie6" lang="en"><![endif]-->
<!--[if IE 7 ]><html <?php language_attributes(); ?> class="no-js ie ie7" lang="en"><![endif]-->
<!--[if IE 8 ]><html <?php language_attributes(); ?> class="no-js ie ie8" lang="en"><![endif]-->
<!--[if IE 9 ]><html <?php language_attributes(); ?> class="no-js ie ie9" lang="en"><![endif]-->
<!--[if (gte IE 10)|(gt IEMobile 7)|!(IEMobile)|!(IE)]><!--><html <?php language_attributes(); ?> class="no-js" lang="en"><!--<![endif]-->
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width" />
<link rel="image_src" href="http://iam.williams.edu/files/iam_williams.jpg" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title><?php
 
    global $page, $paged;
 
    wp_title( '|', true, 'right' );
 
    bloginfo( 'name' );
 
    $site_description = get_bloginfo( 'description', 'display' );
    if ( $site_description && ( is_home() || is_front_page() ) )
        echo " | $site_description";
 
    if ( $paged >= 2 || $page >= 2 )
        echo ' | ' . sprintf( __( 'Page %s' ), max( $paged, $page ) );
 
    ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="shortcut icon" href="<?php bloginfo('stylesheet_directory'); ?>/favicon.ico" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />

<!--[if lte IE 9]>
    <script src="<?php echo get_stylesheet_directory_uri() . '/js/html5.js'; ?>"></script>
<![endif]-->
 
<?php
    /* We add some JavaScript to pages with the comment form
     * to support sites with threaded comments (when in use).
     */
    if ( is_singular() && get_option( 'thread_comments' ) )
        wp_enqueue_script( 'comment-reply' );
 
    wp_head();
?>
    <link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
<!-- Typekit script / Williams account credentials -->
<script type="text/javascript" src="//use.typekit.net/sly7lsr.js"></script>
<script type="text/javascript">try{Typekit.load();}catch(e){}</script> 
</head>
 
<body <?php body_class(); ?>>
 	<!--[if lt IE 7]><p class="chromeframe">Holy crow, your browser is <em>ancient</em>! <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</p><![endif]-->
    <header id="navigation">
        <hgroup>
            <h1><a href="http://www.williams.edu" title="Williams College <?php _e('Home' ); ?>" rel="home"><span class="visuallyhidden">Williams College</span></a></h1>
        	<?php wp_nav_menu(array( 'menu' => 'navigation', 'container_class' => 'menu-header' )); ?>
        </hgroup>
    </header>