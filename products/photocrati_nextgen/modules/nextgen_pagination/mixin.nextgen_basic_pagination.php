<?php

/**
 * Contains function(s) to generate a basic pagination widget
 */
class Mixin_NextGen_Basic_Pagination extends Mixin
{

    /**
     * Returns a formatted HTML string of a pagination widget
     *
     * @param mixed $selected_page
     * @param int $number_of_entities
     * @param int $entities_per_page
     * @param string|null $current_url (optional)
     * @return array Of data holding prev & next url locations and a formatted HTML string
     */
    public function create_pagination($selected_page, $number_of_entities, $entities_per_page = 0, $current_url = NULL)
    {
        $prev_symbol = apply_filters('ngg_prev_symbol', '&#9668;');
        $next_symbol = apply_filters('ngg_next_symbol', '&#9658;');

        if (empty($current_url))
        {
            $current_url = $this->object->get_routed_url(TRUE);

            if (is_archive())
            {
                $id = get_the_ID();
	
                if ($id == null)
                {
                    global $post;
                    $id = $post ? $post->ID : null;
                }
				    
                if ($id != null && in_the_loop())
                {
                    $current_url = get_permalink($id);
                }
            }
        }

        // Early exit
        $return = array('prev' => '', 'next' => '', 'output' => "<div class='ngg-clear'></div>");
        if ($entities_per_page <= 0 || $number_of_entities <= 0) return $return;

        // Construct array of page urls
        $ending_ellipsis = $starting_ellipsis = FALSE;
        $number_of_pages = ceil($number_of_entities/$entities_per_page);
        $pages = [];
        for ($i=1; $i<=$number_of_pages; $i++) {

            if ($selected_page === $i) {
                $pages[] = "<span class='current'>{$i}</span>";
            }
            else {
                $add=TRUE;
                // We always show the first and last pages
                // However, if the number of pages is created than 4
                // then an ellipsis will sometimes appear after the first page
                // or before the last page, depending on what the current page is
                if ($number_of_pages > 4) {
                    if ($i == 1 || $i == $number_of_pages || $i == $selected_page-1 || $i == $selected_page+1) {
                        $add = TRUE;
                    }
                    else {
                        $add = FALSE;
                        if ($ending_ellipsis < 0 && $i > $selected_page) {
                            $pages[] = "<span class='ellipsis'>...</span>";
                            $ending_ellipsis = $i;
                        }
                        else if ($starting_ellipsis < 0 && $i < $selected_page) {
                            $pages[] = "<span class='ellipsis'>...</span>";
                            $starting_ellipsis = $i;
                        }
                    }
                }

                if ($add) {
                    $link = esc_attr($this->object->set_param_for($current_url, 'nggpage', $i));
                    $pages[] = "<a class='page-numbers' data-pageid='{$i}' href='{$link}'>{$i}</a>";
                }
                
            }
        }

        if ($pages) {
            // Next page
            if ($selected_page+1 <= $number_of_pages) {

                $next_page = $selected_page+1;
                $link = $return['next'] = $this->object->set_param_for($current_url, 'nggpage', $next_page);
                $pages[] = "<a class='prev' data-pageid={$next_page}>{$next_symbol}</a>";
            }

            // Prev page
            if ($selected_page-1 > 0) {
                $prev_page = $selected_page-1;
                $link = $return['next'] = $this->object->set_param_for($current_url, 'nggpage', $prev_page);
                array_unshift($pages, "<a class='next' data-pageid={$prev_page}>{$prev_symbol}</a>");
            }

            $return['output'] = "<div class='ngg-navigation'>" . implode("\n", $pages) . "</div>";
        }

        return $return;
    }
}
