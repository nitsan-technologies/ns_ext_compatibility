// IIFE for faster access to $ and save $ use
jQuery(function($) {
   
    $("body .modal-wrapper").hide();
   

    $(document).on('click','.viewAllVersionLink',function(){
        laodModelData($(this).attr('data-uri'));
    });

    $(document).on('click','.ext-overview',function(){
        laodModelData($(this).attr('data-uri'));
    });

    $(document).on('click','.close,.modal-overlay',function(){
        $(".modal,.modal-overlay").fadeOut('fast');
    });

    //Load Data model Start
    var loadModel = function($this) {
        $("#nsModel").fadeIn('fast');
        $('.modal-overlay').fadeIn('fast');
        $('body,html').animate({
            scrollTop: 0
        }, 800);
    }
    //Load Data model End

    // Load Main DataTable Start
    var nsDataTable = function() {
        datatable = $('#typo3-extension-list').dataTable({
            "bPaginate": false,
            "bLengthChange": false,
            "info": false,
        });
    }
    // Load Main DataTable End

    //Clear data-table search start
   var clearTable= function(){
        $('.dataTables_wrapper .dataTables_filter input').clearable({
            onClear: function() {
                datatable.fnFilter('');
            }
        }); 
    }
    //Clear data-table search end
   
    //Load all version of extension start
    var  laodModelData= function(url){
        $("#nsModel").find('.modal-content').html("")
        $.ajax({
            type: "GET",
            url: url,
            cache: false,
            success: function (data) {
                $("#nsModel").find('.modal-content').html(data);
                loadModel()
                if($("#extensionAllVersionlist").length>0){
                    var versioTable = $('#extensionAllVersionlist').DataTable({
                        "bPaginate": false,
                        "bLengthChange": false,
                    });
                    $('#nsModel .dataTables_wrapper .dataTables_filter input').clearable({
                        onClear: function() {
                            versioTable.fnFilter('');
                        }
                    });
                }
            }
        });
    }
    //Load all version of extension end
    nsDataTable();
    clearTable();

    $('#targetVersion').on('change',function (){
       $('.ext-wrapper').show();
    });
});
