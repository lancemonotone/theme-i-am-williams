/*global jQuery */
/*!	
* FitText.js 1.1
*
* Copyright 2011, Dave Rupert http://daverupert.com
* Released under the WTFPL license 
* http://sam.zoy.org/wtfpl/
*
* Date: Thu May 05 14:23:00 2011 -0600
*/
(function(a){a.fn.fitText=function(e,f){var g=e||1,b=a.extend({minFontSize:Number.NEGATIVE_INFINITY,maxFontSize:Number.POSITIVE_INFINITY},f);return this.each(function(){var c=a(this),d=function(){c.css("font-size",Math.max(Math.min(c.width()/(10*g),parseFloat(b.maxFontSize)),parseFloat(b.minFontSize)))};d();a(window).on("resize",d)})}})(jQuery);