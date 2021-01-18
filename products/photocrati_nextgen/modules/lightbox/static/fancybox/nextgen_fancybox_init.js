jQuery(function($) {
    var nextgen_fancybox_init = function() {
        var selector = nextgen_lightbox_filter_selector($, $(".ngg-fancybox"));

        window.addEventListener(
            "click",
            e => {
                let $target = $(e.target);
                if ($target.is(selector) || $target.parents('a').is(selector)) {
                    $link = e.target.nodeName == "IMG" ? $target : $target.find('img')
                    e.preventDefault()
                    $.fancybox($link[0].outerHTML, {
                        titlePosition: 'inside',
                        // Needed for twenty eleven
                        onComplete: function() {
                            $('#fancybox-wrap').css('z-index', 10000);
                        }
                    })
                    
                    e.stopPropagation();
                }
            },
            true
        )
    };
    $(window).on('refreshed', nextgen_fancybox_init);
    nextgen_fancybox_init();
});
