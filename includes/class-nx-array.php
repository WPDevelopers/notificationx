<?php

/**
 * This class is responsible for Handling Array.
 */

class NotificationX_Array {
    private $limit = 5;
    private $values;
    private $priority = [];
    public $sortBy = 'priority';

    public function setValues( $values ) {
        $this->values = $values;
        $this->sort();
    }

    public function values() {
        $this->values = array_slice( $this->values, 0, $this->limit );
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
            if( ! isset( $value[ $this->sortBy ] ) ) {
                unset( $this->values[ $key ] );
                continue;
            }
            $this->priority[ $key ] = $value[ $this->sortBy ];
        }
        array_multisort( $this->priority, $flags, $this->values );
        $this->priority = [];
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