<?php

namespace Pagup\Pctag\Controllers;

use Pagup\Pctag\Core\Option;
class TrackingController {
    public function __construct() {
        add_action( 'wp_head', array(&$this, 'pinterest_pixel') );
    }

    public function pinterest_pixel() {
        // echo $this->product_event();
        if ( Option::check( 'enable_pctag' ) && Option::check( 'pctag_id' ) ) {
            echo $this->pinterest( Option::get( 'pctag_id' ) );
        }
        if ( Option::check( 'pctag_website' ) ) {
            echo "<!-- Pinterest Claim Website --!>\n" . Option::get( 'pctag_website' ) . "\n";
        }
    }

    protected function pinterest( $tag ) {
        return "<!-- Pinterest Pixel Base Code --!>\n<script>!function(e){if(!window.pintrk){window.pintrk = function () { window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var n=window.pintrk;n.queue=[],n.version='3.0';var t=document.createElement('script');t.async=!0,t.src=e;var r=document.getElementsByTagName('script')[0]; r.parentNode.insertBefore(t,r)}}('https://s.pinimg.com/ct/core.js'); pintrk('load', '{$tag}'); pintrk('page'); </script> <noscript><img height='1' width='1' style='display:none;' alt='' src='https://ct.pinterest.com/v3/?tid={$tag}&noscript=1' /></noscript><!-- End Pinterest Pixel Base Code --!>\n<script> pintrk('track', 'pagevisit'); </script>\n";
    }

    protected function search_event( $search_query, $tag ) {
        return "<!-- Pinterest Search Event --!>\n<script>pintrk('track', 'search', { search_query: '{$search_query}' }); </script><noscript><img height='1' width='1' style='display:none;' alt='' src='https://ct.pinterest.com/v3/?tid={$tag}&event=search&ed[search_query]={$search_query}&noscript=1' /></noscript><!-- End Pinterest Search Event --!>\n";
    }

    protected function pinterest_event( string $type, string $tag, array $data = [] ) {
        $event_data = "";
        $noscript_data = "";
        if ( count( $data ) > 0 ) {
            // Event data is printed as a JSON object in which <, >, &, ' and " are written
            // as \u escapes, so every key and value stays text inside the script element.
            $json = wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_FORCE_OBJECT );
            // Data that cannot be encoded is left out of the script call and of the fallback image alike.
            if ( is_string( $json ) ) {
                $event_data = ", " . $json;
                // Every key and value is percent-encoded before it joins the fallback address.
                foreach ( $data as $key => $value ) {
                    $noscript_data .= "&ed[" . rawurlencode( (string) $key ) . "]=" . rawurlencode( (string) $value );
                }
            }
        }
        // The fallback address is escaped as an attribute value. esc_url() is not used here
        // because it rewrites the square brackets of the ed[key] parameter names.
        $noscript_url = esc_attr( "https://ct.pinterest.com/v3/?tid={$tag}&event={$type}{$noscript_data}&noscript=1" );
        return "<!-- Pinterest {$type} code --!>\n<script>pintrk('track', '{$type}'{$event_data});</script>\n<noscript><img height='1' width='1' style='display:none;' alt='' src='{$noscript_url}' /></noscript>\n";
    }

    protected function product_event( string $type, string $tag ) {
    }

    protected function view_category( string $tag ) {
        return "<!-- Pinterest ViewCategory Code --!>\n<script>pintrk('track', 'viewcategory');</script> <noscript><img height='1' width='1' style='display:none;' alt='' src='https://ct.pinterest.com/v3/?tid={$tag}&event=viewcategory&noscript=1' /></noscript>\n";
    }

    // Stored event rows are checked again each time they are printed, whatever wrote them.
    // A row is kept only when its type is made of letters, digits and underscores and its
    // value is a single piece of text or a number. Both are returned as text.
    function flattenArray( $multiArray ) : array {
        $flattenedArray = [];
        if ( !is_array( $multiArray ) ) {
            return $flattenedArray;
        }
        foreach ( $multiArray as $array ) {
            if ( !is_array( $array ) || !isset( $array['type'], $array['value'] ) ) {
                continue;
            }
            if ( !is_scalar( $array['type'] ) || !is_scalar( $array['value'] ) ) {
                continue;
            }
            $type = (string) $array['type'];
            $value = (string) $array['value'];
            if ( empty( $type ) || empty( $value ) ) {
                continue;
            }
            if ( !preg_match( '/^[a-zA-Z0-9_]+$/D', $type ) ) {
                continue;
            }
            $flattenedArray[$type] = $value;
        }
        return $flattenedArray;
    }

}

$TrackingControllers = new TrackingController();