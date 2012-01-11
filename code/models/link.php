<?php

class Item_link extends Item{

    const TYPE = 'link';

    static $fields = array(
          array('id' => 'title', 'label' => 'Link Title', 'type' => Form::TYPE_TEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
        , array('id' => 'url', 'label' => 'URL', 'type' => Form::TYPE_URL, 'rules' => array(Form::RULE_REQUIRED => '', Form::RULE_URL => ''))
        , array('id' => 'comment', 'label' => 'Comment', 'type' => Form::TYPE_LONGTEXT, 'rules' => array())
    );

}