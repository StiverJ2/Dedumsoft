var DedumTableSort = (function() {
    var sortState = {};

    function getFirstChildByClass(parent, className) {
        var children = parent.childNodes;
        for (var i = 0; i < children.length; i++) {
            if (children[i].nodeType === 1) {
                var cls = children[i].className || '';
                if ((' ' + cls + ' ').indexOf(' ' + className + ' ') > -1) {
                    return children[i];
                }
            }
        }
        return null;
    }

    function trim(str) {
        return str.replace(/^\s+|\s+$/g, '');
    }

    function getText(el) {
        return el.innerText || el.textContent || '';
    }

    function initSortable(tableId) {
        var table = document.getElementById(tableId);
        if (!table) { return; }
        
        var thead = table.getElementsByTagName('thead')[0];
        if (!thead) { return; }
        
        var headerRow = thead.getElementsByTagName('tr')[0];
        if (!headerRow) { return; }
        
        var headers = headerRow.getElementsByTagName('th');
        if (!headers || headers.length === 0) { return; }

        for (var i = 0; i < headers.length; i++) {
            setupHeader(tableId, headers[i], i);
        }
        
        sortState[tableId] = { column: -1, ascending: true };
    }

    function setupHeader(tableId, th, colIndex) {
        th.style.cursor = 'pointer';
        th.title = 'Clic para ordenar';
        
        var indicator = document.createElement('span');
        indicator.className = 'ds-sort-ind';
        indicator.style.color = '#999';
        indicator.style.marginLeft = '4px';
        indicator.innerHTML = '&#9650;&#9660;';
        th.appendChild(indicator);
        
        th.onclick = function() {
            sortTable(tableId, colIndex);
        };
    }

    function sortTable(tableId, colIndex) {
        var table = document.getElementById(tableId);
        if (!table) { return; }
        
        var tbody = table.getElementsByTagName('tbody')[0];
        if (!tbody) { return; }
        
        var rows = tbody.getElementsByTagName('tr');
        if (!rows || rows.length === 0) { return; }
        
        var state = sortState[tableId] || { column: -1, ascending: true };
        var ascending = true;
        if (state.column === colIndex) {
            ascending = !state.ascending;
        }
        sortState[tableId] = { column: colIndex, ascending: ascending };
        
        var rowArray = [];
        for (var i = 0; i < rows.length; i++) {
            rowArray.push(rows[i]);
        }
        
        rowArray.sort(function(rowA, rowB) {
            var cellsA = rowA.getElementsByTagName('td');
            var cellsB = rowB.getElementsByTagName('td');
            
            if (!cellsA[colIndex] || !cellsB[colIndex]) { return 0; }
            
            var valA = trim(getText(cellsA[colIndex]));
            var valB = trim(getText(cellsB[colIndex]));
            
            var numA = parseFloat(valA.replace(/[,$]/g, ''));
            var numB = parseFloat(valB.replace(/[,$]/g, ''));
            
            var result;
            if (!isNaN(numA) && !isNaN(numB)) {
                result = numA - numB;
            } else {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
                if (valA < valB) { result = -1; }
                else if (valA > valB) { result = 1; }
                else { result = 0; }
            }
            
            return ascending ? result : -result;
        });
        
        for (var j = 0; j < rowArray.length; j++) {
            tbody.appendChild(rowArray[j]);
        }
        
        updateIndicators(tableId, colIndex, ascending);
    }

    function updateIndicators(tableId, activeCol, ascending) {
        var table = document.getElementById(tableId);
        if (!table) { return; }
        
        var thead = table.getElementsByTagName('thead')[0];
        if (!thead) { return; }
        
        var headers = thead.getElementsByTagName('th');
        for (var i = 0; i < headers.length; i++) {
            var indicator = getFirstChildByClass(headers[i], 'ds-sort-ind');
            if (!indicator) { continue; }
            
            if (i === activeCol) {
                indicator.style.color = '#333';
                indicator.innerHTML = ascending ? '&#9650;' : '&#9660;';
            } else {
                indicator.style.color = '#999';
                indicator.innerHTML = '&#9650;&#9660;';
            }
        }
    }

    return {
        init: initSortable,
        sort: sortTable
    };
})();
