import React from "react";
import NGGFeaturedImage from './components/ngg-post-thumbnail.jsx';

const {select}   = wp.data;
const {Fragment} = wp.element

function setFeaturedImageDisplay(OriginalComponent) {
    return (props) => {
        const meta = select('core/editor').getCurrentPostAttribute('meta');
        let featuredImageWidget;
        if ('undefined' !== typeof meta) {
            featuredImageWidget = <NGGFeaturedImage {...props} meta={meta}/>;
        }
        return (
            <Fragment>
                <OriginalComponent {...props}/>
                {featuredImageWidget}
            </Fragment>
        );
    }
}

wp.hooks.addFilter('editor.PostFeaturedImage', 'imagely/featured-image-display', setFeaturedImageDisplay);