/**
 * Email Templates plugin for Craft CMS
 *
 * Email Templates JS
 *
 * @author    Infanion
 * @copyright Copyright (c) 2021 Infanion
 * @link      https://www.infanion.com/
 * @package   EmailTemplates
 * @since     1.0.0
 */

function myFunction() {
    // Declare variables 
    var input, filter, table, tr, td, i;
    input = document.getElementById("myInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("records_table");
    tr = table.getElementsByTagName("tr");

    for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[0];
    if (td) {
        if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
        } else {
        tr[i].style.display = "none";
    }
} 
}
}

$('table.paginated').each(function () {
    var $table = $(this);
    var itemsPerPage = 10;
    var currentPage = 0;
    var pages = Math.ceil($table.find("tr:not(:has(th))").length / itemsPerPage);
    $table.bind('repaginate', function () {
    if (pages > 1) {
        var pager;
        if ($table.next().hasClass("pager"))
        pager = $table.next().empty();  else
        pager = $('<div class="pager" style="direction:ltr; " ></div>');

        // Previous page button
        $('<button class="pg-goto"> < </button>').bind('click', function () {
        if (currentPage > 0)
            currentPage--;
            $table.trigger('repaginate');
        }).appendTo(pager);

        // next page button
        $('<button class="pg-goto"> > </button>').bind('click', function () {
        if (currentPage < pages - 1)
            currentPage++;
            $table.trigger('repaginate');
        }).appendTo(pager);
        

        if (!$table.next().hasClass("pager"))
            pager.insertAfter($table);
            
        // To display number of strings 
        var string = Math.ceil($table.find("tr:not(:has(th))").length);
        $('.pager').append('<span></span>' +'Page'+ " " + (currentPage+1));
        }
        $table.find(
        'tbody tr:not(:has(th))').hide().slice(currentPage * itemsPerPage, (currentPage + 1) * itemsPerPage).show();
        });
        
        $table.trigger('repaginate');
        
});
// # sourceMappingURL=comments-cp.js.map
