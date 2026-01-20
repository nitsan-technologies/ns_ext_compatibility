define([
    'jquery',
    'TYPO3/CMS/Backend/Modal',
    'TYPO3/CMS/NsExtCompatibility/Datatables'
], function ($, Model) {

    const dropdown = document.getElementById("targetVersion");
    dropdown.addEventListener("change", function() {
        document.getElementById("changeTargtVersion").submit();
    });

    var mainDataTable = $('#typo3-extension-list').DataTable({
        paging: false
    });
    $('.dataTables_wrapper .dataTables_filter input').addClass("form-control");

    var url;

    $('.ext-overview').click(function(){
        url = $(this).attr('data-uri');
        var modal = $('#nsModel');
        $.ajax({
            type: "GET",
            url: url,
            cache: false,
            success: function (data) {
                modal.find('.modal-content').html(data);
            }
        });
    });

    $('.viewAllVersionLink').click(function(){
        url = $(this).attr('data-uri');
        var modal = $('#nsModel');
        $.ajax({
            type: "GET",
            url: url,
            cache: false,
            success: function (data) {
                modal.find('.modal-content').html(data);
            }
        });
    });

    $('#targetVersion').on('change',function (){
       $('.ext-wrapper').show();
    });

    $('.tx-ext-compatibility .alert .close').on('click',function (){
        $(this).parent().hide()
    });

});
