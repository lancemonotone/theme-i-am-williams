jQuery(document).ready(function($){
	// Rename admin menu item. 
	// Weird issue: when unregistering the 'category' taxonomy, 
	// this menu item takes on its label, so we have to change it back.
	$('#adminmenu .wp-submenu a[href="edit-tags.php?taxonomy=class_year"]').html('Class Year');

    $('table.tablesorter, table#wms-jquery-tablesorter').each(function() {
        $(this).tablesorter({
            sortList: [
                [0, 0]
            ]
        });
    });
	var pager = $('<div id="pager" class="pager">'+
                	'<form>'+
                		'<span class="first"></span>'+
                		'<span class="prev"></span>'+
                		'<input type="text" class="pagedisplay"/>'+
                		'<span class="next"></span>'+
                		'<span class="last"></span>'+
                		'<select class="pagesize">'+
                			'<option value="10">10</option>'+
                			'<option value="20">20</option>'+
                			'<option value="30">30</option>'+
                			'<option value="40">40</option>'+
                			'<option value="9999" selected>All</option>'+
                		'</select>'+
                	'</form>'+
                '</div>')
	           .prependTo('table.data');
	$('table.tablesorter').tablesorterPager({container: pager, size: 9999, positionFixed: false});
});