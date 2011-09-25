<?php

class Item_link extends Item{

    const TYPE = 'link';

    static $fields = array(
          array('id' => 'title', 'type' => Form::TYPE_TEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
        , array('id' => 'url', 'type' => Form::TYPE_URL, 'rules' => array(Form::RULE_REQUIRED => '', Form::RULE_URL => ''))
        , array('id' => 'comment', 'type' => Form::TYPE_LONGTEXT, 'rules' => array())
        , array('id' => 'format', 'type' => Form::TYPE_CHOICE, 'rules' => array(Form::RULE_VALID_OPTION => ''), 'options' => array('plain', 'html', 'textile'))
    );

}