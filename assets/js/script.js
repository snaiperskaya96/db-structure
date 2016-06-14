
$(document).ready(function() {
    $('.get-structure').click(function () {
        var database = $(this).attr('data-id');
        var id = $(this).parent().parent().attr('id');
        $('.overlay').fadeIn();
        $.ajax({
            url: 'inc/ajax.php',
            data: {database: database}
        }).done(function(response){
            generateTable(JSON.parse(response), id);
        }).always(function(){
            $('.overlay').fadeOut();
        });
    });
});

function generateTable(data, database){
    var table = null;
    var tbody = null;
    var row = null;
    $.each(data, function(id, val){
        table = $('<table></table>').addClass('table table-striped').attr('id',database+'-table-'+val.TABLE_NAME);
        tbody = $('<tbody></tbody>').appendTo(table);

        row = $('<tr></tr>');
        $('<td></td>').text('Table Name').appendTo(row);
        $('<td></td>').attr('colspan','6').text(val.TABLE_NAME).appendTo(row);
        $(row).appendTo(tbody);

        row = $('<tr></tr>');
        $('<td></td>').text('Attribute').appendTo(row);
        $('<td></td>').text('Description').appendTo(row);
        $('<td></td>').text('Type').appendTo(row);
        $('<td></td>').text('Length').appendTo(row);
        $('<td></td>').text('Null').appendTo(row);
        $('<td></td>').text('Default').appendTo(row);
        $('<td></td>').text('Example of Values').appendTo(row);
        $(row).appendTo(tbody);

        $.each(val.columns, function(colId, colVal){
            var colLengthRegex = colVal.COLUMN_TYPE.match(/[0-9]{1,3}/);
            var colLength = colLengthRegex !== null && colLengthRegex.length > 0 ? colLengthRegex[0] : 'NA';
            var colTypeRegex = colVal.COLUMN_TYPE.match(/([a-z]+)([0-9]{1,3})?/);
            var colType = colTypeRegex !== null && colTypeRegex.length > 0 ? colTypeRegex[1] : 'NA';
            row = $('<tr></tr>');
            $('<td></td>').text(colVal.COLUMN_NAME).appendTo(row);
            $('<td></td>').text(colVal.COLUMN_COMMENT).appendTo(row);
            $('<td></td>').text(colType).appendTo(row);
            $('<td></td>').text(colLength).appendTo(row);
            $('<td></td>').text(firstToUpperCase(colVal.IS_NULLABLE)).appendTo(row);
            $('<td></td>').text(colVal.COLUMN_DEFAULT == null ? 'Null' : colVal.COLUMN_DEFAULT).appendTo(row);
            $('<td></td>').text('TBI').appendTo(row);
            $(row).appendTo(tbody);
        });
        $('#' + database).append(table);
        $('#' + database).append($('<hr/>'));
    });

    function firstToUpperCase( str ) {
        return str.substr(0, 1).toUpperCase() + str.substr(1).toLowerCase();
    }
}