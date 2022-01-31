<form method="get" id="searchform" action="<?php echo esc_url( home_url( '/' ) )?>">
	<label for="s" class="assistive-text"><?php _e( 'Search' )?></label>
	<input type="search" class="field" name="s" id="s" placeholder="<?php _e( 'Search' )?>" />
	<input type="submit" class="submit" name="submit" id="searchsubmit" value="<?php _e( 'Search' )?>" />
</form>