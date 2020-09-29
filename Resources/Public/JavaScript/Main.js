define([
    'jquery',
    'TYPO3/CMS/Backend/Modal',
    'TYPO3/CMS/NsExtCompatibility/Datatables',
    'TYPO3/CMS/Backend/jquery.clearable'
], function ($, Model) {
 
    var mainDataTable = $('#typo3-extension-list').DataTable({
        paging: false
    });
    $('.dataTables_wrapper .dataTables_filter input').addClass("form-control");
    $('.dataTables_wrapper .dataTables_filter input').clearable({
        onClear: function () {
            mainDataTable.fnFilter('');
        }
    });

    var url;
    $('.viewAllVersionLink').click(function(){
        url = $(this).attr('data-uri');
    });
    $('.ext-overview').click(function(){
        url = $(this).attr('data-uri');
    });
    $('#targetVersion').on('change',function (){
       $('.ext-wrapper').show();
    });


    //Load data in model start
    $('#nsModel').on('show.bs.modal', function (event) {
        var modal = $(this);
        modal.find('.modal-content').html("");
        $.ajax({
            type: "GET",
            url: url,
            cache: false,
            success: function (data) {
                modal.find('.modal-content').html(data);

                if($("#extensionAllVersionlist").length>0){
                    var dataTable = $('#extensionAllVersionlist').DataTable({
                        paging: false
                    });
                    $('#nsModel .dataTables_wrapper .dataTables_filter input').clearable({
                        onClear: function () {
                            datatable.fnFilter('');
                        }
                    });
                }
            }
        });
    });
    //Load data in model end
});
