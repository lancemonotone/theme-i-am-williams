// This is set in functions.php.  It is a snippet of CSS to initially hide .items until
// Isotope has done its damage and the styled content is safe to display.
document.write( localized.noscript );

jQuery( document ).ready( function () {
  jQuery( '.item .quote-sm .quote-content p' ).fitText( 0.8, { maxFontSize: '1.15em' } );
  jQuery( '.item .quote-lg .quote-content p' ).fitText( 0.8, { maxFontSize: '1.8em' } );

  // Masonry
  const $container = jQuery( '#items' ).imagesLoaded( function () {
    $container.isotope( {
      itemSelector: '.item',
      masonry: {
        columnWidth: 180,
        gutter: 15,
        fitWidth: true
      }
    } );

    $container.on( 'layoutComplete', fadeItemsIn );
    $container.on( 'layoutComplete', prepareFilters );

    let $iso = $container.data( 'isotope' );

    $container.infiniteScroll( {
      history: false,
      hideNav: '#nav-below',
      path: '#nav-below a',  // selector for the NEXT link (to page 2)
      append: '.item',     // selector for all items you'll retrieve
      outlayer: $iso,
      status: '.page-load-status'
    } );
  } ).isotope().addClass( 'no-transition' );

  // fix click bug on mobile devices
  jQuery( '.item' ).click( function () {
    $href = jQuery( this ).find( 'a' ).attr( 'href' );
    window.location.assign( $href );
  } );

  // filter items when filter link is clicked
  jQuery( '.blog #menu-navigation li:not(.ignore-filter) a' ).each( function () {
    jQuery( this ).click( function () {
      if ( jQuery( this ).hasClass( 'current-filter' ) ) {
        reset_filters();
      } else {
        reset_filters();
        jQuery( this ).addClass( 'current-filter' );
        jQuery( '#menu-navigation .reset-filters a' ).html( 'All' );
        var $selector = '.item.' + jQuery( this ).html().toLowerCase();
        doFilter( $selector );
      }
      return false;
    } );
  } );

  jQuery( '.blog .reset-filters' ).click( function () {
    reset_filters();
    return false;
  } );

  function reset_filters() {
    jQuery( '.item-filtered,.item-hidden,.current-filter' ).removeClass( 'item-filtered item-hidden current-filter' );
    $selector = '*';
    doFilter( $selector );
    jQuery( '#menu-navigation .reset-filters a' ).html( 'Home' );
  }

  function doFilter( $selector ) {
    $selector = $selector + ', .about';
    if ( wWidth() <= 768 ) {
      $container.isotope( { filter: $selector } );
    } else {
      jQuery( '.item', $container ).not( $selector ).addClass( 'item-hidden' );
    }
  }

  function prepareFilters( $elements ) {
    if ( jQuery( '.current-filter' ).length ) {
      $selector = '.item.' + jQuery( '.current-filter' ).html().toLowerCase();
      doFilter( $selector );
    }
  }

  // Filter grid on home page according to hash if coming from an internal page.
  // http://iam.williams.edu/#students
  if ( window.location.hash ) {
    $selector = '.item.' + window.location.hash.substring( 1 ); //Puts hash in variable, and removes the #
                                                                // character
    jQuery( '.menu-item-' + window.location.hash.substring( 1 ) + ' a' ).addClass( 'current-filter' );
    doFilter( $selector );
  }

  // Add internal link to next search result.
  jQuery( '.search article' ).not( ':last' ).find( '.entry-container' ).each( function () {
    jQuery( this ).append( '<a href="javascript:void(0)" class="next-result-link">Next result &#x25BC;</a>' )
  } );
  jQuery( '.next-result-link' ).click( function () {
    var $next_link = '#' + jQuery( this ).parents( 'article' ).next().attr( 'id' );
    scrollToDiv( jQuery( $next_link ), 0 );
    return false;
  } );

  function scrollToDiv( element, navheight ) {
    var offset = element.offset();
    var offsetTop = offset.top;
    var totalScroll = offsetTop - navheight;

    jQuery( 'body,html' ).animate( {
      scrollTop: totalScroll
    }, 500 );
  }

  function fadeItemsIn() {
    $container.fadeTo( "slow", 1 ).removeClass( 'no-transition' );
  }

  function wWidth() {
    return window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth || 0;
  }

  function wHeight() {
    return window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || 0;
  }

} );