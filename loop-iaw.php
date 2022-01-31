<?php
global $iaw;
/**
 * 0 => thumbnail 		= 180 x 180
 * 1 => medium 			= 380 x 380
 * 2 => quote quote-sm	= 180 x 180
 * 3 => quote quote-lg	= 380 x 180
 */
$grid_map = array('thumbnail','medium','quote quote-sm','quote quote-lg');
$grid_sizes = array(10,2,1,1);

/**
 * ACF fields not used: iaw_first_name, iaw_last_name, iaw_title
 */

// We'll store sizes in the session so the same layout will be used when returning to home from an interior page.
// The layout will change when the home page is refreshed or when the referrer was not an internal page. 
if(($iaw->iaw_new_request) || !isset($_COOKIE['iaw_sizes'])) {
	$iaw->iaw_new_request = 1;
	$temp_sizes = array();
} else {
	$session_sizes = explode(',',urldecode($_COOKIE['iaw_sizes']));
}
?>

<div <?php post_class('quote quote-lg about'); ?>>
	<a href="<?php echo home_url('about')?>">
		<span class="details">
			<div class="logo"><img width="154" height="25" alt="I Am Williams logo" src="<?php echo get_stylesheet_directory_uri()?>/img/logo.png"></div>
			<h2 class="entry-title">I Am Williams</h2>
			<p>Documenting the many faces of Williams</p>
		</span><!-- .details -->
	</a><!-- .overlay -->
</div><!-- .item -->

<?php 
$i = 0;
if (have_posts()) while (have_posts()) { the_post(); ?>
	<?php 
	// Reset variables.
	$img_src = $grid_content = $grid_details = null;
	$the_title = get_field('iaw_grid_title') ? get_field('iaw_grid_title') :  get_the_title();
	$grid_details = '<h2 class="entry-title">'.$the_title.'</h2><p class="ss-icon ss-navigateright"><span class="visuallyhidden">&lt;</span></p>';
	if(!$iaw->iaw_new_request) {
		$size = $session_sizes[$i++]; // Get stored size from session.
	} else {
		$size = iaw_wrandom($grid_sizes); // Get random grid size from array above.
	}
	
	// These are the image grid sizes.
	if($grid_map[$size] == 'thumbnail' || $grid_map[$size] == 'medium'){
		// Get grid images from ACF.
	    $iaw->disable_filters = true; // needed for ACF 5 to prevent custom where and join filters
		$images = get_field('iaw_photos_repeater');
        $iaw->disable_filters = false;
		// If we have images.
		if(!empty($images)){
    		// Get random grid image if more than one has been uploaded.
    		$rand_key = array_rand($images);
    		// Get img src info according to size.
    		$img_src = wp_get_attachment_image_src($images[$rand_key]['iaw_photo'],$grid_map[$size]);
			
			// get WP multisite src for use with TimThumb
			$timthumb_src = get_timthumb_src($img_src[0]);
									
			$grid_content = "<img class=\"img_color\" src=\"{$img_src[0]}\" width=\"{$img_src[1]}\" height=\"{$img_src[2]}\" />";
			$grid_content .= "<img class=\"img_grayscale\" 
				src=\"".get_bloginfo('template_directory')."/functions/timthumb/timthumb.php?src={$timthumb_src}&amp;f=2|3,-30|4,40&amp;q=100&amp;w={$img_src[1]}&amp;h={$img_src[2]}\" 
				width=\"{$img_src[1]}\" 
				height=\"{$img_src[2]}\" />";
			
		}else{
			// If no image is available, change the size and use a quote.
			$size = $size == 0 ? 2 : 3; // thumbnail ? quote quote-sm : quote quote-lg
		}
	}
	if(!$grid_content) { // if $grid_content isn't populated, there either is no image available or this is a quote.
		$grid_content = get_field('iaw_grid_text');
		$grid_content = $grid_content != '' ? $grid_content : apply_filters('the_excerpt', get_the_excerpt());
		$grid_content = '<div class="quote-content">'.$grid_content.'</div><!-- .quote-content -->';
	}
	// Add size to session so same sizes/layout will be used when returning to home from an interior page.
	if($iaw->iaw_new_request) array_push($temp_sizes,$size);
	?>
	<?php 
	// Get iaw_type terms  
	$terms = iaw_get_term_slugs($post->ID);
	?>
<div id="post-<?php the_ID(); ?>" <?php post_class('item '.$grid_map[$size].' '.$terms); ?>>
	<?php edit_post_link('Edit', '<span class="edit-me edit-callout">', '</span>', $post_id); ?>
	<a href="<?php echo get_permalink()?>" class="overlay">
		<span class="details">	
			<?php echo $grid_details; ?>
		</span><!-- .details -->
	</a><!-- .overlay -->
	<div class="grid-content"><?php echo $grid_content; ?></div><!-- .grid_content -->
</div><!-- .item -->

<?php }
if($iaw->iaw_new_request) setcookie('iaw_sizes',urlencode(implode(',',$temp_sizes)),strtotime('+1 days'),'/');