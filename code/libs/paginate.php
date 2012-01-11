<?php
/*
    The all powerful paginate function.

    It slices, it dices, it took a whole day to write this bad boy
    and it need never be done again. It's got options and configurations
    and all sorts of doo-dads and hoo-hickeys to make sure that making
    a simple

     Prev Next 1 ... 54 55 56 57 ... 490

    type link set will never be a hassle again! Woo!
*/
function paginate($user_params=array()) {

    if( !is_array($user_params) ) {
        throw new Exception('Incorrect usage of paginate');
    }

    // TODO: 'outputFormat' => '{details} {prev} {next} {links}'
    //       Do that instead of all the true/fall testing for how to do arrangement
    // TODO:
    $defaults = array(
        'cur_page'        => false, // should be INT
        'total_results'   => false, // should be INT
        'per_page'        => false, // should be INT
        'prev_next'       => true,
        'prev_next_adjacent'=>false,
        'prev_next_ptext' => 'Prev',
        'prev_next_ntext' => 'Next',
        'emptyVal'        => 'No Pages',
        'details'         => true,
        'items'           => 10,
        'format'          => '<a href="?p={P}">{Pt}</a>',
        'drop1'           => false  // if set to an integer, that many characters will be removed from the string here the {P} shows up
                                    // for example:
                                    //      <a href="?p={P}">{Pt}</a>
                                    // with a drop1=3 for page one would echo
                                    //      <a href="">1</a>
                                    // As you can see, it did NOT number the first page and removed several 3 characters from in front of the {P} element
    );

    $vars = array_merge($defaults, $user_params);
    extract($vars);

    if(    $cur_page      === false
        || $total_results === false
        || $per_page      === false
        || !is_numeric($cur_page)
        || !is_numeric($total_results)
        || !is_numeric($per_page) ) { // these are REQUIRED
        throw new Exception('Incorrect page counts passed to paginate');
    }

    $return = array();
    
    if( $total_results > 0 ) {

        $total_results  = (int) $total_results;
        $per_page       = (int) $per_page;
        $num_pages      = ceil($total_results / $per_page);

        $page = max($cur_page, 1);
        $page = min($page, $num_pages);

        $cur_page--; // for these next calcs, current page should be zero based.

        $cur_record     = ($cur_page * $per_page) + 1;
        $cur_record_end = ($cur_page==0) ? $per_page : ($cur_page * $per_page) + $per_page;
        if( $cur_record_end > $total_results || ($cur_record_end < $total_results && $num_pages == 1) ) $cur_record_end = $total_results;

        if( $details ) {
            $return[] =  '<strong class="details">'. number_format($cur_record) . ' &ndash; ' . number_format($cur_record_end) . ' of ' . number_format($total_results) .'</strong>';
        }

        // "previous" button
        if( $prev_next ) {
            if( $num_pages > 1 && $page > 1 ) {
                $return[] = pageinateLink($page-1, $prev_next_ptext, $format, $drop1);
            } else { // $num_pages == 1
                $return[] = '<span class="inactive">'.$prev_next_ptext.'</span>';
            }
            if( $prev_next_adjacent ) {
                if( $num_pages > 1 && $page < $num_pages ) {
                    $return[] = pageinateLink($page+1, $prev_next_ntext, $format, $drop1);
                } else { // $num_pages == 1
                    $return[] = '<span class="inactive">'.$prev_next_ntext.'</span>';
                }
            }
        }

        if( $num_pages > $items ) {
            // not enough item slots to show all pages
            // the idea is this, start at the center and "radiate out"
            // from the current page till all item slots are filled

            $v = true;
            $r = 1;
            $pages = array($page);
            $failed_unshift = $failed_push = false;
            $ebrake = 0;

            while( count($pages) <= $items ) {
                if( $v ) {
                    // add after
                    $tpage = $r + $page;
                    if( $tpage < $num_pages ) {
                        array_push($pages, $tpage);
                        $failed_push = false;
                    } else {
                        $failed_push = true;
                    }
                } else {
                    // add before
                    $tpage = $page - $r;
                    if( $tpage > 1 ) {
                        array_unshift($pages, $tpage);
                        $failed_unshift = false;
                    } else {
                        $failed_unshift = true;
                    }
                    $r++;
                }
                $v = ($v) ? false : true;
                if( $failed_push && $failed_unshift || $ebrake++ > 30 ) break;
            }

            // set the first and last, but check to see if the 2nd(to last) should thus be hellips

            if( $pages[0] == 2 ) {
                array_unshift($pages, 1);
            } else if( $pages[0] != 1 ) {
                array_unshift($pages, '<span class="dots">&hellip;</span>');
                array_unshift($pages, 1);
            }

            $last = $pages[count($pages)-1];
            if( $last == $num_pages-1 ) {
                array_push($pages, $num_pages);
            } else if( $last < $num_pages-1 ) {
                array_push($pages, '<span class="dots">&hellip;</span>');
                array_push($pages, $num_pages);
            }

            // now, go through our pages array and link, bold, or leave them along -- but add each to results.
            foreach( $pages as $pages_t ) {
                if( is_numeric($pages_t) && $pages_t == $page )
                    $return[] = '<strong>'.number_format($pages_t).'</strong>';
                else if( is_numeric($pages_t) )
                    $return[] = pageinateLink($pages_t, number_format($pages_t), $format, $drop1);
                else
                    $return[] = $pages_t;
            }

        } else {
            // more item slots than pages, show all pages
            for( $i=1; $i<=$num_pages; $i++ ) {
                if( $page == $i ) {
                    $return[] = '<strong>'.number_format($i).'</strong>';
                } else {
                    $return[] = pageinateLink($i, number_format($i), $format, $drop1);
                }
            }
        }

        // "next" button, unless placed adjactenly
        if( $prev_next && !$prev_next_adjacent ) {
            if( $num_pages > 1 && $page < $num_pages ) {
                $return[] = pageinateLink($page+1, $prev_next_ntext, $format, $drop1);
            } else { // $num_pages == 1
                $return[] = '<span class="inactive">'.$prev_next_ntext.'</span>';
            }
        }

    } else {
        $return[] = '<strong>'. $emptyVal .'</strong>';
    }

    return $return;

}

function pageinateLink($page, $text, $format, $drop1) {
    if( $drop1 && $page == 1) {
        $parts = explode('{P}', $format);
        $newFormat = substr($parts[0], 0, -1*($drop1)) . $parts[1];
        return str_replace(array('{P}','{Pt}'), array($page, $text), $newFormat);
    } else {
        return str_replace(array('{P}','{Pt}'), array($page, $text), $format);
    }
}
