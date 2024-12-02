function initDataTables() {
    setTimeout(function() {
        var rtl_table = $('#rpt-tables');
        if (rtl_table.length) {
            var menuText = ' _MENU_ Entries per page'
            var pagingText = 'Showing _START_ to _END_ of _TOTAL_ '
            var withPaging = true

            if (rtl_table.data('menu-text')) {
                menuText = ' _MENU_ ' + rtl_table.data('menu-text')
            }
            if (rtl_table.data('paging-text')) {
                pagingText = rtl_table.data('paging-text')
            }
            if (rtl_table.is('[data-with-paging]')) {
                var value = rtl_table.attr('data-with-paging');

                if (value === '0' || value === 'false') {
                    withPaging = false
                    pagingText = ''
                }
            }

            if (!$.fn.DataTable.isDataTable('#rpt-tables .reports_datatable')) {
                $('#rpt-tables .reports_datatable').DataTable({
                    searching: false,
                    "language": {
                        "lengthMenu": menuText,
                        "info": pagingText
                    },
                    "paging": withPaging,
                    "dom": '<"row"t><"row"<"col-sm-6"p><"col-sm-6"l>>', // Добавлено для выравнивания
                    "lengthMenu": [10, 25, 50, 100] // Варианты для количества записей
                });
            }

        } else {
            // console.log('Не прошло')
        }
    }, 1000);
}
