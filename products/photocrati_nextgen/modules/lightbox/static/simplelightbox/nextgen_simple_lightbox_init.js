jQuery(function($) {

    var selector = null;
    var lightbox = null;
    
    var nextgen_simplebox_options = {
        history: false,
        animationSlide: false,
        animationSpeed: 100
    };

    var nextgen_simplelightbox_init = function() {
        selector = nextgen_lightbox_filter_selector($, $(".ngg-simplelightbox"));
        if (selector.length > 0) {
            lightbox = selector.simpleLightbox(nextgen_simplebox_options);
        }
    };

    nextgen_simplelightbox_init();

    $(window).on('refreshed', function() {
        if (typeof lightbox !== 'undefined') {
            lightbox.destroy();
        }
        selector = nextgen_lightbox_filter_selector($, $(".ngg-simplelightbox"));
        if (selector.length > 0) {
            lightbox = selector.simpleLightbox(nextgen_simplebox_options);
        }
    });
});
