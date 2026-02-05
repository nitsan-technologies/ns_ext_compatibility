import $ from "jquery";
// import "@nitsan/ns-ext-compatibility/Datatables.js";


$(document).ready(function() {
    const dropdown = document.getElementById("targetVersion");
    if (dropdown) {
        dropdown.addEventListener("change", function() {
            document.getElementById("changeTargtVersion").submit();
        });
    }

    $(document).on('click', '.ext-overview, .viewAllVersionLink', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var url = $(this).attr('data-uri') || $(this).data('uri');
        var modal = $('#nsModel');
        
        if (!url) {
            console.error('No data-uri found');
            return false;
        }
        
        // Show loading state
        modal.find('.modal-content').html('<div class="modal-body"><p>Loading...</p></div>');
        
        $.ajax({
            type: "GET",
            url: url,
            cache: false,
            success: function (data) {
                modal.find('.modal-content').html(data);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                modal.find('.modal-content').html(
                    '<div class="modal-body"><div class="alert alert-danger">Error loading content. Please try again.</div></div>'
                );
            }
        });
        
        return false;
    });

    $('#targetVersion').on('change', function() {
        $('.ext-wrapper').show();
    });

    $('.tx-ext-compatibility .alert .close').on('click', function() {
        $(this).parent().hide();
    });
});