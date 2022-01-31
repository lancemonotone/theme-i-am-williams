<?php get_header(); ?>

<div id="main">
<?php if(is_home()){?>
	<div id="items" class="centered cf">
		<?php get_template_part( 'loop', 'iaw' ); ?>
	</div><!-- #items -->

	<?php /* Display navigation to next/previous pages when applicable */ ?>
	<?php if ( $wp_query->max_num_pages > 1 ) { ?>
	<nav id="nav-below" class="page-nav">
	    <span class="more-link"><?php next_posts_link( __( 'More &#x25BC;' ) ); ?></span>
	    <?php //previous_posts_link( __( 'Newer posts' ) ); ?>
	</nav>
	<?php } ?>

    <div class="page-load-status">
        <p class="infinite-scroll-request"><img src="https://i.imgur.com/qkKy8.gif" alt="Loading icon"> Loading more profiles...</p>
        <p class="infinite-scroll-last">That's all, folks!</p>
        <p class="infinite-scroll-error">No more pages to load</p>
    </div>
<?php } else { get_template_part( 'loop', 'single' ); } ?>
</div><!-- main -->

<?php get_footer()?>