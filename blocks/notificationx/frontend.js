(function(){
    var wrappers = document.getElementsByClassName("notificationx-block-wrapper");

    if(wrappers && wrappers.length){
        for (var i = 0; i < wrappers.length; i++) {
            if(typeof wrappers[i] != 'undefined' && typeof wrappers[i].dataset != 'undefined' && typeof wrappers[i].dataset.nx_id != 'undefined'){
                var nx_id = wrappers[i].dataset.nx_id;
                var link  = wrappers[i].getElementsByTagName("a");
                if(link && link.length && nx_id){
                    link[0].addEventListener("click", function(){
                        var url = notificationxBlockRest.root + 'notificationx/v1/analytics/?frontend=true';
                        fetch(url, {
                            method: 'POST',
                            credentials: 'omit',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                nx_id: nx_id
                            }),
                        });
                    });
                }
            }
        }
    }
})();