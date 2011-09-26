<?php

class Item_post extends Item{

    const TYPE = 'post';

    static $fields = array(
          array('id' => 'title', 'label' => 'Title', 'type' => Form::TYPE_TEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
        , array('id' => 'content', 'label' => 'Post Content', 'type' => Form::TYPE_LONGTEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
    );

}