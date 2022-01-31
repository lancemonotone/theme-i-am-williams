<?php get_header(); ?>

	<div id="content" class="centered cf">
	<?php if(have_posts()) while(have_posts()) { the_post();?>
		<article>
			<h1 class="entry-title"><?php the_title()?></h1>
			<div class="entry-content">
				<?php the_content(); ?>
			</div><!-- .entry-content-->
		</article>
	<?php } ?>
	</div><!-- #content -->
		
<?php get_footer(); ?>
