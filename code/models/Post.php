<?php

class Item_post extends Item{

    const TYPE = 'post';

    static $fields = array(
          array('id' => 'title', 'type' => Form::TYPE_TEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
        , array('id' => 'content', 'type' => Form::TYPE_LONGTEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
    );

}