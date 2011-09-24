<?php

class Item_Post extends Item{

    const TYPE = 'post';

    private $fields = array(
          'title'
        , 'content'
        , 'contentRendered'
        , 'format'
    );

}