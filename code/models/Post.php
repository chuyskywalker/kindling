<?php

class Item_post extends Item{

    const TYPE = 'post';

    static $fields = array(
          array('id' => 'title', 'label' => 'Title', 'type' => Form::TYPE_TEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
                        // "comment" doesn't make much sense here, but it's more consistent with other items
        , array('id' => 'comment', 'label' => 'Post Content', 'type' => Form::TYPE_LONGTEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
    );

}