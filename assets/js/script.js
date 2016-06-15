
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
    var container = null;
    var row = null;
    $.each(data, function(id, val){
        container = $('<div></div>').addClass('well').attr('id',database+'-container-'+val.TABLE_NAME);
        table = $('<table></table>').addClass('table').attr('id',database+'-table-'+val.TABLE_NAME).appendTo(container);
        tbody = $('<tbody></tbody>').appendTo(table);

        row = $('<tr></tr>');
        $('<td></td>').text(val.TABLE_NAME).addClass('table-name').appendTo(row);
        $('<td></td>').attr('colspan','6').text(val.TABLE_COMMENT).appendTo(row);
        $(row).appendTo(tbody);

        row = $('<tr></tr>').addClass('table-header');
        $('<td></td>').text('Attribute').appendTo(row);
        $('<td></td>').text('Description').appendTo(row);
        $('<td></td>').text('Type').appendTo(row);
        $('<td></td>').text('Length').appendTo(row);
        $('<td></td>').text('Null').appendTo(row);
        $('<td></td>').text('Default').appendTo(row);
        $('<td></td>').text('Example of Values').appendTo(row);
        $(row).appendTo(tbody);

        var primaryKey = "";
        var foreignKeys = "";
        var uniqueKeys = "";
        $.each(val.columns, function(colId, colVal){
            if(colVal.COLUMN_KEY.indexOf('PRI') > -1) primaryKey = colVal.COLUMN_NAME;
            if(colVal.COLUMN_KEY.indexOf('UNI') > -1){
                if(uniqueKeys != "") uniqueKeys += ", ";
                uniqueKeys += colVal.COLUMN_NAME;
            }
            var colLengthRegex = colVal.COLUMN_TYPE.match(/[0-9]{1,3}/);
            var colLength = colLengthRegex !== null && colLengthRegex.length > 0 ? colLengthRegex[0] : 'N/A';
            var colTypeRegex = colVal.COLUMN_TYPE.match(/([a-z]+)([0-9]{1,3})?/);
            var colType = colTypeRegex !== null && colTypeRegex.length > 0 ? colTypeRegex[1] : 'N/A';
            row = $('<tr></tr>');
            $('<td></td>').text(colVal.COLUMN_NAME).appendTo(row);
            $('<td></td>').text(colVal.COLUMN_COMMENT).appendTo(row);
            $('<td></td>').text(colType).appendTo(row);
            $('<td></td>').text(colLength).appendTo(row);
            $('<td></td>').text(firstToUpperCase(colVal.IS_NULLABLE)).appendTo(row);
            $('<td></td>').text(colVal.COLUMN_DEFAULT == null ? 'Null' : colVal.COLUMN_DEFAULT).appendTo(row);
            $('<td></td>').text('TBI').appendTo(row);
            $(row).appendTo(tbody);

            var FkConst = colVal.FK_CONSTRAINT_NAME;
            var FkCol = colVal.FK_REFERENCED_COLUMN_NAME;
            var FkTab = colVal.FK_REFERENCED_TABLE_NAME;

            if(FkConst != null && FkCol != null && FkTab != null){
                if(foreignKeys != "") foreignKeys += ", ";
                foreignKeys += FkConst + "(References "
                    + FkCol + " on "
                    + FkTab + ")";
            }
        });

        var primaryRow = $('<tr></tr>').appendTo(tbody);
        $('<td></td>').text('Primary Key').attr('colspan','1').addClass('table-name').appendTo(primaryRow);
        $('<td></td>').text(primaryKey).attr('colspan','6').appendTo(primaryRow);

        var uniqueRow = $('<tr></tr>').appendTo(tbody);
        $('<td></td>').text('Unique Keys').attr('colspan','1').addClass('table-name').appendTo(uniqueRow);
        $('<td></td>').text(uniqueKeys).attr('colspan','6').appendTo(uniqueRow);

        var foreignRow = $('<tr></tr>').appendTo(tbody);
        $('<td></td>').text('Foreign Keys').attr('colspan','1').addClass('table-name').appendTo(foreignRow);
        $('<td></td>').text(foreignKeys).attr('colspan','6').appendTo(foreignRow);

        $('#' + database).append(container);
        $('#' + database).append($('<hr/>'));
    });

    function firstToUpperCase( str ) {
        return str.substr(0, 1).toUpperCase() + str.substr(1).toLowerCase();
    }
}