<?php

class FomoPress_Template {


    public $tags = [
        '::h2::',
    ];

    public static function template( $template ) {
        
        preg_match_all( '/{{([^}]*)}}/', $template, $params, PREG_PATTERN_ORDER );
        preg_match_all( '/::([^}]*)::/', $template, $tags, PREG_PATTERN_ORDER );

        


    }


}