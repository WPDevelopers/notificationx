<?php

/**
 * This class is responsible for Handling Array.
 */

class NotificationX_Array {
    private $limit = 5;
    private $values;
    private $priority = [];
    private $sortBy = 'priority';

    public function __construct( $input, $sortBy = 'priority' ){
        $this->values = $input;
        $this->sortBy = $sortBy;

        // $this->limit = NotificationX_DB::get_settings('cache_limit');

        $this->sort();
    }

    public function values() {
        return $this->values;
    }
    public function setLimit( $limit = 5 ) {
        $this->limit = $limit;
    }

    public function size(){
        return count( $this->values );
    }

    public function sort( $flags = SORT_DESC ) {
        if( empty( $this->values ) ) {
            return $this;
        }
        foreach( $this->values as $key => $value ) {
            $this->priority[ $key ] = $value[ $this->sortBy ];
        }
        array_multisort( $this->priority, $flags, $this->values );
        return $this;
    }

    public function append( $value, $key = null ) {
        if( $this->size() == $this->limit ) {
            $this->sort();
            array_pop( $this->values );
        }
        if( $key === null ) {
            array_push( $this->values, $value );
        } else {
            $this->values = $this->values + [ $key => $value ];
        }
        $this->sort();
        return $this;
    }

    public function prepend( $value, $key = null ){
        if( $this->size() == $this->limit ) {
            $this->sort();
            array_pop( $this->values );
        }

        if( $key === null ) { 
            array_unshift( $this->values, $value );
        } else {
            $this->values = [ $key => $value ] + $this->values;
        }
        $this->sort();
        return $this;
    }
}