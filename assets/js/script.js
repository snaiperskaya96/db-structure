var DEFAULT_DATA_LENGTH = {
    'tinytext' : '1',
    'text' : '2',
    'mediumtext' : '3',
    'longtext' : '4',
    'tinyblob' : '1',
    'blob' : '2',
    'mediumblob' : '3',
    'longblob' : '4',
    'float' : '4/8',
    'double' : '8',
    'enum' : '1/2',
    'year' : '1',
    'date' : '3',
    'time' : '3',
    'datetime' : '8',
    'timestamp' : '4'
};



$(document).ready(function() {
    $('.get-structure').click(function () {
        var database = $(this).attr('data-id');
        var databaseName = $(this).attr('data-name');
        debugger;
        $('.well-'+databaseName).remove();
        $('.hr-'+databaseName).remove();
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
        container = $('<div></div>').addClass('well well-'+database).attr('id',database+'-container-'+val.TABLE_NAME);
        table = $('<table></table>').addClass('table ' + database +'-table').attr('id',database+'-table-'+val.TABLE_NAME).appendTo(container);
        tbody = $('<tbody></tbody>').appendTo(table);

        row = $('<tr></tr>').addClass('table-description');
        $('<td></td>').text(val.TABLE_NAME).addClass('table-name name').appendTo(row);
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
            var colLength = colLengthRegex !== null && colLengthRegex.length > 0 ? colLengthRegex[0] : '';
            var colTypeRegex = colVal.COLUMN_TYPE.match(/([a-z]+)([0-9]{1,3})?/);
            var colType = colTypeRegex !== null && colTypeRegex.length > 0 ? colTypeRegex[1] : 'N/A';

            colLength = DEFAULT_DATA_LENGTH[colType.toLowerCase()] !== undefined ?
                DEFAULT_DATA_LENGTH[colType.toLowerCase()] + "*" : colLength;

            row = $('<tr></tr>').addClass('field');
            $('<td></td>').text(colVal.COLUMN_NAME).appendTo(row);
            $('<td></td>').text(colVal.COLUMN_COMMENT).appendTo(row);
            $('<td></td>').text(colType).appendTo(row);
            $('<td></td>').text(colLength).attr('style','text-align: right').appendTo(row);
            $('<td></td>').text(firstToUpperCase(colVal.IS_NULLABLE)).appendTo(row);
            $('<td></td>').text(colVal.COLUMN_DEFAULT == null ? 'Null' : colVal.COLUMN_DEFAULT).attr('style','text-align: right;').appendTo(row);

            var example = '';
            if(val.examples !== null && val.examples[colVal.COLUMN_NAME] != null){
                var value = val.examples[colVal.COLUMN_NAME].toString();
                if(value.length <= 30) {
                    example = value;
                }else{
                    example = value.substr(0,30) + "...";
                }
            }

            $('<td></td>').text(example).appendTo(row);
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

        var primaryRow = $('<tr></tr>').addClass('key').appendTo(tbody);
        $('<td></td>').text('Primary Key').attr('colspan','1').addClass('table-name primary').appendTo(primaryRow);
        $('<td></td>').text(primaryKey).attr('colspan','6').appendTo(primaryRow);

        var uniqueRow = $('<tr></tr>').addClass('key').appendTo(tbody);
        $('<td></td>').text('Unique Keys').attr('colspan','1').addClass('table-name unique').appendTo(uniqueRow);
        $('<td></td>').text(uniqueKeys).attr('colspan','6').appendTo(uniqueRow);

        var foreignRow = $('<tr></tr>').addClass('key').appendTo(tbody);
        $('<td></td>').text('Foreign Keys').attr('colspan','1').addClass('table-name foreign').appendTo(foreignRow);
        $('<td></td>').text(foreignKeys).attr('colspan','6').appendTo(foreignRow);

        var sqlRow = $('<tr></tr>').addClass('sql').appendTo(tbody);
        $('<td></td>').text('SQL Code').attr('colspan','1').addClass('table-name').appendTo(sqlRow);
        $('<td></td>').text(val.sql).attr('colspan','6').appendTo(sqlRow);

        var notesRow = $('<tr></tr>').addClass('notes').appendTo(tbody);
        $('<td></td>').text('Notes').attr('colspan','1').addClass('table-name').appendTo(notesRow);
        $('<td></td>').text('').attr('colspan','6').appendTo(notesRow);

        $('#' + database).append(container);
        $('#' + database).append($('<hr/>').addClass('hr-'+database));
    });

}


function firstToUpperCase( str ) {
    return str.substr(0, 1).toUpperCase() + str.substr(1).toLowerCase();
}

function exportDoc(database){
    var tables = $('.' + database + '-table');
    if(tables.length < 1){
        alert("No data available. \nYou must run Get Structure for this database first.");
        return false;
    }
    var data = {
        name: $('#'+database).children().first().text(),
        dbname: database,
        tables: {}
    };

    $.each(tables, function(id, val){
        var tbody = val.children[0];
        var tableName = "";
        $.each(tbody.children, function(trId, trVal){
            switch(trVal.className){
                case "table-description":
                    tableName = trVal.children[0].innerText.trim();
                    data.tables[tableName] = {name: '', comment: '', headers: [], fields: [],
                        keys: {primary:'', unique:[], foreign:[]}, sql: ''};
                    data.tables[tableName].name = trVal.children[0].innerText;
                    data.tables[tableName].comment = trVal.children[1].innerText;
                    break;
                case "table-header":
                    $.each(trVal.children, function(tdId, tdVal){
                       data.tables[tableName]['headers'].push(tdVal.innerText);
                    });
                    break;
                case "field":
                    var fields = [];
                    $.each(trVal.children, function(tdId, tdVal){
                        fields.push(tdVal.innerText);
                    });
                    data.tables[tableName]['fields'].push(fields);
                    break;
                case "key":
                    if($(trVal.children[0]).hasClass('primary'))
                        data.tables[tableName]['keys']['primary'] = trVal.children[1].innerText;
                    else if($(trVal.children[0]).hasClass('unique'))
                        data.tables[tableName]['keys']['unique'] = trVal.children[1].innerText;
                    else if($(trVal.children[0]).hasClass('foreign'))
                        data.tables[tableName]['keys']['foreign'] = trVal.children[1].innerText;
                    break;
                case "sql":
                    data.tables[tableName]['sql'] = trVal.children[1].innerText;
                    break;
                case "notes":
                    data.tables[tableName]['notes'] = trVal.children[1].innerText;
                    break;
            }
        });
    });

    $('.overlay').fadeIn();
    $.ajax({
        method: 'post',
        url: 'inc/ajax.php',
        data: {json: JSON.stringify(data)}
    }).done(function(response){
        var json = JSON.parse(response);
        if(json.status == 'ok'){
            window.location = '?get=' + json.source
        }
    }).always(function(){
        $('.overlay').fadeOut();
    });
}