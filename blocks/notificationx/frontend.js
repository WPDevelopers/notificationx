(function(){
    var wrappers = document.getElementsByClassName("notificationx-block-wrapper");

    if(wrappers && wrappers.length){
        for (var i = 0; i < wrappers.length; i++) {
            if(typeof wrappers[i] != 'undefined' && typeof wrappers[i].dataset != 'undefined' && typeof wrappers[i].dataset.nx_id != 'undefined'){
                var nx_id = wrappers[i].dataset.nx_id;
                var link  = wrappers[i].getElementsByTagName("a");
                if(link && link.length && nx_id){
                    link[0].addEventListener("click", function(){
                        wp.apiFetch({
                            path: '/notificationx/v1/analytics/?frontend=true',
                            method: "POST",
                            data: {
                                nx_id: nx_id
                            },
                        });
                    });
                }
            }
        }
    }
})();