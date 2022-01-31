<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
<?php $iaw_types = wp_get_post_terms($post->ID,'iaw_type'); ?>
<?php 
	$iaw_years = array();
	$iaw_photoshoot_year = wp_get_post_terms($post->ID, 'photoshoot_year');
	foreach($iaw_photoshoot_year as $iaw_year){
		array_push($iaw_years, $iaw_year->name);
	}
?>
<article  id="post-<?php the_ID(); ?>" <?php post_class('article cf'); ?>>
	<div class="photo">
		<?php 
			$img_id = get_post_thumbnail_id($post->ID);
			$img_src = wp_get_attachment_image_src($img_id,'large');
			echo "<img src=\"{$img_src[0]}\" />";
		?> 
	</div>
	<div class="entry-container">
		<?php edit_post_link('Edit', '<span class="edit-me edit-callout">', '</span>', $post_id); ?>
		<div class="entry-meta">
			<?php foreach ($iaw_types as $iaw_type){?>
			<a class="category-<?php echo $iaw_type->slug?>" href="<?php echo home_url();?>#<?php echo $iaw_type->slug?>"><?php echo $iaw_type->name?></a>
			<?php } ?>
		</div><!-- .entry-meta -->
		<div class="entry-content">
			<?php the_content(); ?>
		</div><!-- .entry-content -->	
		<h1 class="entry-title"><?php the_title(); ?></h1>
		<footer class="entry-utility">
			<?php /*
			<ul>
				<li><a href="tag.php">uganda</a></li>
				<li><a href="tag.php">africa</a></li>
				<li><a href="tag.php">international</a></li>
				<li><a href="tag.php">woman</a></li>
				<li><a href="tag.php">2012</a></li>
			</ul>
			*/ ?>
			<p>Photo by <?php echo implode('; ',$iaw_years)?></p>
		</footer>
				
		<div id="nav-below" class="centered cf navigation">
			<div class="nav-previous"><?php IAW::get_randomized_adjacent_post_link('%link', _x( '', 'Previous post link' ) . '<p class="ss-icon ss-navigateleft"><span class="visuallyhidden">&gt;</span></p>' , true);?></div>
			<div class="nav-next"><?php IAW::get_randomized_adjacent_post_link('%link', ' <p class="ss-icon ss-navigateright"><span class="visuallyhidden">&lt;</span></p>' . _x( '', 'Next post link' ), false);?></div>
		</div><!-- #nav-below -->
	</div><!-- .entry-container -->
</article> 

<?php endwhile; // end of the loop. ?>
