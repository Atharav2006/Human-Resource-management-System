// Wait until DOM is ready
$(document).ready(function(){

    // Auto-hide alerts after 3 seconds
    setTimeout(function(){
        $(".alert").fadeOut("slow");
    }, 3000);

    // Confirm before deleting or approving actions
    $(".confirm-action").on("click", function(e){
        const message = $(this).data("message") || "Are you sure you want to proceed?";
        if(!confirm(message)){
            e.preventDefault();
        }
    });

    // Optional: Highlight active navbar link
    const path = window.location.pathname;
    $(".navbar-nav .nav-link").each(function(){
        if($(this).attr("href") === path || path.includes($(this).attr("href"))){
            $(this).addClass("active");
        }
    });

    // File input preview for profile pictures
    $("#profile_picture").change(function(){
        if(this.files && this.files[0]){
            let reader = new FileReader();
            reader.onload = function(e){
                $("#profile_preview").attr("src", e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Optional: simple table search/filter
    $(".table-search").on("keyup", function(){
        const value = $(this).val().toLowerCase();
        $(this).closest("table").find("tbody tr").filter(function(){
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

});
