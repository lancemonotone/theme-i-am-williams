<?php get_header(); ?>

	<div id="content" class="centered cf">

		<article id="post-0" class="post no-results not-found">
			<header class="entry-header">
				<h1 class="entry-title"><?php _e( 'Nothing Found' ); ?></h1>
			</header><!-- .entry-header -->

			<div class="entry-content">
				<p><?php _e( 'Sorry, but nothing matched your search criteria. Please try again with some different keywords.'); ?></p>
				<?php get_search_form(); ?>
			</div><!-- .entry-content -->

		</article><!-- #post-0 -->
	</div><!-- #content -->
		
<?php get_footer(); ?>
